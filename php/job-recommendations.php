<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userid = $_SESSION['userid'];

// Get job recommendations based on user profile and preferences
function getJobRecommendations($conn, $userid, $limit = 10) {
    // Get user profile information
    $user_stmt = $conn->prepare("SELECT * FROM user WHERE userid = ?");
    $user_stmt->bind_param("i", $userid);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
    
    if (!$user) {
        return [];
    }
    
    // Extract keywords from user profile
    $profile_text = implode(' ', array_filter([
        $user['about'] ?? '',
        $user['education'] ?? '',
        $user['experience'] ?? '',
        $user['location'] ?? ''
    ]));
    
    // Get user's application history to understand preferences
    $history_stmt = $conn->prepare("
        SELECT j.designation, j.location, j.salary, c.name as company_name
        FROM applied a
        JOIN `job-post` j ON a.jobid = j.jobid
        JOIN company c ON j.compid = c.compid
        WHERE a.userid = ?
        ORDER BY a.applied_at DESC
        LIMIT 5
    ");
    $history_stmt->bind_param("i", $userid);
    $history_stmt->execute();
    $history = $history_stmt->get_result();
    $history_stmt->close();
    
    // Build recommendation query with multiple factors
    $recommendations = [];
    
    // Factor 1: Location-based recommendations
    if (!empty($user['location'])) {
        $location_stmt = $conn->prepare("
            SELECT j.*, c.name as company_name, c.location as company_location,
                   'location' as match_type, 3 as score
            FROM `job-post` j
            JOIN company c ON j.compid = c.compid
            WHERE j.status = 'Active' 
            AND (j.location LIKE ? OR c.location LIKE ?)
            AND j.jobid NOT IN (
                SELECT jobid FROM applied WHERE userid = ?
            )
            ORDER BY j.created_at DESC
            LIMIT ?
        ");
        $location_param = "%" . $user['location'] . "%";
        $location_stmt->bind_param("ssii", $location_param, $location_param, $userid, $limit);
        $location_stmt->execute();
        $location_results = $location_stmt->get_result();
        
        while ($row = $location_results->fetch_assoc()) {
            $recommendations[] = $row;
        }
        $location_stmt->close();
    }
    
    // Factor 2: Skills-based recommendations (keyword matching)
    if (!empty($profile_text)) {
        $keywords = extractKeywords($profile_text);
        if (!empty($keywords)) {
            $skills_stmt = $conn->prepare("
                SELECT j.*, c.name as company_name, c.location as company_location,
                       'skills' as match_type, 2 as score
                FROM `job-post` j
                JOIN company c ON j.compid = c.compid
                WHERE j.status = 'Active'
                AND (
                    j.designation LIKE ? OR 
                    j.description LIKE ? OR
                    c.name LIKE ?
                )
                AND j.jobid NOT IN (
                    SELECT jobid FROM applied WHERE userid = ?
                )
                ORDER BY j.created_at DESC
                LIMIT ?
            ");
            
            $keyword_param = "%" . implode("%", $keywords) . "%";
            $skills_stmt->bind_param("sssii", $keyword_param, $keyword_param, $keyword_param, $userid, $limit);
            $skills_stmt->execute();
            $skills_results = $skills_stmt->get_result();
            
            while ($row = $skills_results->fetch_assoc()) {
                $recommendations[] = $row;
            }
            $skills_stmt->close();
        }
    }
    
    // Factor 3: Salary-based recommendations
    $salary_stmt = $conn->prepare("
        SELECT j.*, c.name as company_name, c.location as company_location,
               'salary' as match_type, 1 as score
        FROM `job-post` j
        JOIN company c ON j.compid = c.compid
        WHERE j.status = 'Active'
        AND j.salary BETWEEN 30000 AND 150000
        AND j.jobid NOT IN (
            SELECT jobid FROM applied WHERE userid = ?
        )
        ORDER BY j.created_at DESC
        LIMIT ?
    ");
    $salary_stmt->bind_param("ii", $userid, $limit);
    $salary_stmt->execute();
    $salary_results = $salary_stmt->get_result();
    
    while ($row = $salary_results->fetch_assoc()) {
        $recommendations[] = $row;
    }
    $salary_stmt->close();
    
    // Remove duplicates and sort by score
    $unique_recommendations = [];
    $seen_jobids = [];
    
    foreach ($recommendations as $rec) {
        if (!in_array($rec['jobid'], $seen_jobids)) {
            $seen_jobids[] = $rec['jobid'];
            $unique_recommendations[] = $rec;
        } else {
            // If duplicate, increase score
            foreach ($unique_recommendations as &$unique) {
                if ($unique['jobid'] == $rec['jobid']) {
                    $unique['score'] += $rec['score'];
                    break;
                }
            }
        }
    }
    
    // Sort by score and return top recommendations
    usort($unique_recommendations, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($unique_recommendations, 0, $limit);
}

// Extract relevant keywords from text
function extractKeywords($text) {
    // Common job-related keywords
    $keywords = [
        'software', 'developer', 'engineer', 'manager', 'analyst', 'designer',
        'marketing', 'sales', 'customer', 'service', 'support', 'admin',
        'project', 'product', 'data', 'research', 'finance', 'accounting',
        'human', 'resources', 'hr', 'it', 'technology', 'web', 'mobile',
        'frontend', 'backend', 'fullstack', 'ui', 'ux', 'design',
        'python', 'javascript', 'java', 'php', 'sql', 'html', 'css',
        'react', 'angular', 'vue', 'node', 'aws', 'cloud', 'devops'
    ];
    
    $text = strtolower($text);
    $found_keywords = [];
    
    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $found_keywords[] = $keyword;
        }
    }
    
    return $found_keywords;
}

// Get trending jobs (most applications in recent time)
function getTrendingJobs($conn, $limit = 5) {
    $trending_stmt = $conn->prepare("
        SELECT j.*, c.name as company_name, c.location as company_location,
               COUNT(a.S) as application_count
        FROM `job-post` j
        JOIN company c ON j.compid = c.compid
        LEFT JOIN applied a ON j.jobid = a.jobid
        WHERE j.status = 'Active'
        AND j.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY j.jobid
        ORDER BY application_count DESC, j.created_at DESC
        LIMIT ?
    ");
    $trending_stmt->bind_param("i", $limit);
    $trending_stmt->execute();
    $trending_jobs = $trending_stmt->get_result();
    $trending_stmt->close();
    
    $results = [];
    while ($row = $trending_jobs->fetch_assoc()) {
        $results[] = $row;
    }
    
    return $results;
}

// Get recently posted jobs
function getRecentJobs($conn, $limit = 5) {
    $recent_stmt = $conn->prepare("
        SELECT j.*, c.name as company_name, c.location as company_location
        FROM `job-post` j
        JOIN company c ON j.compid = c.compid
        WHERE j.status = 'Active'
        ORDER BY j.created_at DESC
        LIMIT ?
    ");
    $recent_stmt->bind_param("i", $limit);
    $recent_stmt->execute();
    $recent_jobs = $recent_stmt->get_result();
    $recent_stmt->close();
    
    $results = [];
    while ($row = $recent_jobs->fetch_assoc()) {
        $results[] = $row;
    }
    
    return $results;
}

// Handle different request types
$action = $_GET['action'] ?? 'recommendations';

switch ($action) {
    case 'recommendations':
        $recommendations = getJobRecommendations($conn, $userid, 10);
        echo json_encode([
            'success' => true,
            'recommendations' => $recommendations
        ]);
        break;
        
    case 'trending':
        $trending = getTrendingJobs($conn, 5);
        echo json_encode([
            'success' => true,
            'trending' => $trending
        ]);
        break;
        
    case 'recent':
        $recent = getRecentJobs($conn, 5);
        echo json_encode([
            'success' => true,
            'recent' => $recent
        ]);
        break;
        
    case 'all':
        $recommendations = getJobRecommendations($conn, $userid, 5);
        $trending = getTrendingJobs($conn, 3);
        $recent = getRecentJobs($conn, 3);
        
        echo json_encode([
            'success' => true,
            'recommendations' => $recommendations,
            'trending' => $trending,
            'recent' => $recent
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?> 