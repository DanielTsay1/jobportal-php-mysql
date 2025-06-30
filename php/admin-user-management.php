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
    case 'get_user_details':
        $userid = intval($_GET['userid'] ?? 0);
        if ($userid <= 0) {
            echo json_encode(['error' => 'Invalid user ID']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT u.*, 
                   COUNT(DISTINCT a.`S. No`) as total_applications
            FROM user u 
            LEFT JOIN applied a ON u.userid = a.userid
            WHERE u.userid = ?
            GROUP BY u.userid
        ");
        $stmt->bind_param('i', $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Get user's recent activity
        $stmt = $conn->prepare("
            SELECT 'application' as type, a.applied_at as date, j.designation as title
            FROM applied a 
            JOIN `job-post` j ON a.jobid = j.jobid 
            WHERE a.userid = ?
            UNION ALL
            SELECT 'job_posted' as type, j.created_at as date, j.designation as title
            FROM `job-post` j 
            WHERE j.recid = ?
            ORDER BY date DESC 
            LIMIT 10
        ");
        $stmt->bind_param('ii', $userid, $userid);
        $stmt->execute();
        $activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $user['recent_activity'] = $activity;
        
        echo json_encode(['success' => true, 'user' => $user]);
        break;
        
    case 'suspend_user':
        $userid = intval($_POST['userid'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($userid <= 0) {
            echo json_encode(['error' => 'Invalid user ID']);
            exit;
        }
        if (!$reason) {
            echo json_encode(['error' => 'Suspension reason required']);
            exit;
        }
        // Suspend user and set reason
        $stmt = $conn->prepare("UPDATE user SET suspended = 1, suspension_reason = ? WHERE userid = ?");
        $stmt->bind_param('si', $reason, $userid);
        $stmt->execute();
        $stmt->close();
        // Withdraw all applications for this user
        $stmt = $conn->prepare("UPDATE applied SET status = 'Withdrawn' WHERE userid = ? AND status != 'Withdrawn'");
        $stmt->bind_param('i', $userid);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'User suspended, reason saved, and all applications withdrawn.']);
        break;
        
    case 'activate_user':
        $userid = intval($_POST['userid'] ?? 0);
        if ($userid <= 0) {
            echo json_encode(['error' => 'Invalid user ID']);
            exit;
        }
        // Unsuspend user and clear reason
        $stmt = $conn->prepare("UPDATE user SET suspended = 0, suspension_reason = NULL WHERE userid = ?");
        $stmt->bind_param('i', $userid);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'User activated successfully']);
        break;
        
    case 'get_users_list':
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT userid, username, email, user_type, created_at, last_login, 
                   COALESCE(suspended, 0) as suspended
            FROM user 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get total count
        $total = $conn->query("SELECT COUNT(*) as count FROM user")->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true, 
            'users' => $users, 
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        break;
        
    case 'get_user_history':
        $userid = intval($_GET['userid'] ?? 0);
        if ($userid <= 0) {
            echo json_encode(['error' => 'Invalid user ID']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT 'application' as type, a.applied_at as date, j.designation as title
            FROM applied a 
            JOIN `job-post` j ON a.jobid = j.jobid 
            WHERE a.userid = ?
            UNION ALL
            SELECT 'job_posted' as type, j.created_at as date, j.designation as title
            FROM `job-post` j 
            WHERE j.userid = ?
            ORDER BY date DESC
        ");
        $stmt->bind_param('ii', $userid, $userid);
        $stmt->execute();
        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'history' => $history]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
?> 