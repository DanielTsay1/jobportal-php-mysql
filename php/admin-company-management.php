<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once 'db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_company_details':
        $compid = intval($_GET['compid'] ?? 0);
        if ($compid <= 0) {
            echo json_encode(['error' => 'Invalid company ID']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT j.jobid) as total_jobs_posted,
                   COUNT(DISTINCT a.`S. No`) as total_applications_received
            FROM company c 
            LEFT JOIN `job-post` j ON c.compid = j.compid
            LEFT JOIN applied a ON j.jobid = a.jobid
            WHERE c.compid = ?
            GROUP BY c.compid
        ");
        $stmt->bind_param('i', $compid);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        
        if (!$company) {
            echo json_encode(['error' => 'Company not found']);
            exit;
        }
        
        // Get company's recent job postings
        $stmt = $conn->prepare("
            SELECT j.jobid, j.designation, j.status, j.created_at,
                   COUNT(a.`S. No`) as applications_count
            FROM `job-post` j 
            LEFT JOIN applied a ON j.jobid = a.jobid
            WHERE j.compid = ?
            GROUP BY j.jobid
            ORDER BY j.created_at DESC 
            LIMIT 10
        ");
        $stmt->bind_param('i', $compid);
        $stmt->execute();
        $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $company['recent_jobs'] = $jobs;
        
        echo json_encode(['success' => true, 'company' => $company]);
        break;
        
    case 'suspend_company':
        $compid = intval($_POST['compid'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($compid <= 0) {
            echo json_encode(['error' => 'Invalid company ID']);
            exit;
        }
        if (!$reason) {
            echo json_encode(['error' => 'Suspension reason required']);
            exit;
        }
        // Suspend company and set reason
        $stmt = $conn->prepare("UPDATE company SET suspended = 1, suspension_reason = ? WHERE compid = ?");
        $stmt->bind_param('si', $reason, $compid);
        $stmt->execute();
        $stmt->close();
        // Suspend all active jobs (set to Suspended status)
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Suspended' WHERE compid = ? AND status = 'Active'");
        $stmt->bind_param('i', $compid);
        $stmt->execute();
        $stmt->close();
        // Notify recruiter
        $rec_stmt = $conn->prepare("SELECT recid FROM recruiter WHERE compid = ?");
        $rec_stmt->bind_param('i', $compid);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();
        while ($rec = $rec_result->fetch_assoc()) {
            $msg = "Your company has been suspended. Reason: " . htmlspecialchars($reason) . ". Contact support at JobPortalSupport@gmail.com";
            $conn->query("INSERT INTO notifications (userid, message, link) VALUES (" . intval($rec['recid']) . ", '" . $conn->real_escape_string($msg) . "', '/main/edit-company.php')");
        }
        $rec_stmt->close();
        // Notify all applicants
        $app_stmt = $conn->prepare("SELECT DISTINCT a.userid FROM applied a JOIN `job-post` j ON a.jobid = j.jobid WHERE j.compid = ?");
        $app_stmt->bind_param('i', $compid);
        $app_stmt->execute();
        $app_result = $app_stmt->get_result();
        while ($app = $app_result->fetch_assoc()) {
            $msg = "A job you applied to has been suspended due to company suspension. Reason: " . htmlspecialchars($reason);
            $conn->query("INSERT INTO notifications (userid, message, link) VALUES (" . intval($app['userid']) . ", '" . $conn->real_escape_string($msg) . "', '/main/job-list.php')");
        }
        $app_stmt->close();
        echo json_encode(['success' => true, 'message' => 'Company suspended and notifications sent.']);
        break;
        
    case 'activate_company':
        $compid = intval($_POST['compid'] ?? 0);
        if ($compid <= 0) {
            echo json_encode(['error' => 'Invalid company ID']);
            exit;
        }
        // Unsuspend company and clear reason
        $stmt = $conn->prepare("UPDATE company SET suspended = 0, suspension_reason = NULL WHERE compid = ?");
        $stmt->bind_param('i', $compid);
        $stmt->execute();
        $stmt->close();
        // Reactivate suspended jobs (set back to Active)
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Active' WHERE compid = ? AND status = 'Suspended'");
        $stmt->bind_param('i', $compid);
        $stmt->execute();
        $stmt->close();
        // Notify recruiter
        $rec_stmt = $conn->prepare("SELECT recid FROM recruiter WHERE compid = ?");
        $rec_stmt->bind_param('i', $compid);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();
        while ($rec = $rec_result->fetch_assoc()) {
            $msg = "Your company has been unsuspended. You may now post and manage jobs again.";
            $conn->query("INSERT INTO notifications (userid, message, link) VALUES (" . intval($rec['recid']) . ", '" . $conn->real_escape_string($msg) . "', '/main/edit-company.php')");
        }
        $rec_stmt->close();
        // Notify all applicants
        $app_stmt = $conn->prepare("SELECT DISTINCT a.userid FROM applied a JOIN `job-post` j ON a.jobid = j.jobid WHERE j.compid = ?");
        $app_stmt->bind_param('i', $compid);
        $app_stmt->execute();
        $app_result = $app_stmt->get_result();
        while ($app = $app_result->fetch_assoc()) {
            $msg = "A job you applied to is now active again as the company has been unsuspended.";
            $conn->query("INSERT INTO notifications (userid, message, link) VALUES (" . intval($app['userid']) . ", '" . $conn->real_escape_string($msg) . "', '/main/job-list.php')");
        }
        $app_stmt->close();
        echo json_encode(['success' => true, 'message' => 'Company unsuspended and notifications sent.']);
        break;
        
    case 'get_companies_list':
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT compid, name, email, industry, created_at, 
                   COALESCE(suspended, 0) as suspended
            FROM company 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get total count
        $total = $conn->query("SELECT COUNT(*) as count FROM company")->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true, 
            'companies' => $companies, 
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        break;
        
    case 'get_company_stats':
        // Get company statistics
        $total_companies = $conn->query("SELECT COUNT(*) as count FROM company")->fetch_assoc()['count'];
        $active_companies = $conn->query("SELECT COUNT(*) as count FROM company WHERE COALESCE(suspended, 0) = 0")->fetch_assoc()['count'];
        $suspended_companies = $conn->query("SELECT COUNT(*) as count FROM company WHERE suspended = 1")->fetch_assoc()['count'];
        
        // Get companies with most job postings
        $top_companies = $conn->query("
            SELECT c.name, COUNT(j.jobid) as job_count
            FROM company c
            LEFT JOIN `job-post` j ON c.compid = j.compid
            GROUP BY c.compid
            ORDER BY job_count DESC
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_companies' => $total_companies,
                'active_companies' => $active_companies,
                'suspended_companies' => $suspended_companies
            ],
            'top_companies' => $top_companies
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
?> 