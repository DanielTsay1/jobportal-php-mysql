<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['userid']) && !isset($_SESSION['recid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userid = $_SESSION['userid'] ?? null;
$recid = $_SESSION['recid'] ?? null;

// Get notifications for the current user
function getNotifications($conn, $userid, $recid) {
    if ($userid) {
        // Job seeker notifications
        $stmt = $conn->prepare("
            SELECT id, message, link, is_read, created_at 
            FROM notifications 
            WHERE userid = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->bind_param("i", $userid);
    } else {
        // Recruiter notifications - get notifications for all jobs from their company
        $stmt = $conn->prepare("
            SELECT n.id, n.message, n.link, n.is_read, n.created_at 
            FROM notifications n
            JOIN `job-post` j ON n.jobid = j.jobid
            WHERE j.recid = ? 
            ORDER BY n.created_at DESC 
            LIMIT 20
        ");
        $stmt->bind_param("i", $recid);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'link' => $row['link'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'time_ago' => getTimeAgo($row['created_at'])
        ];
    }
    
    $stmt->close();
    return $notifications;
}

// Mark notification as read
function markAsRead($conn, $notification_id, $userid, $recid) {
    if ($userid) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND userid = ?");
        $stmt->bind_param("ii", $notification_id, $userid);
    } else {
        // For recruiters, mark notifications for their company's jobs
        $stmt = $conn->prepare("
            UPDATE notifications n
            JOIN `job-post` j ON n.jobid = j.jobid
            SET n.is_read = 1 
            WHERE n.id = ? AND j.recid = ?
        ");
        $stmt->bind_param("ii", $notification_id, $recid);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Mark all notifications as read
function markAllAsRead($conn, $userid, $recid) {
    if ($userid) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE userid = ?");
        $stmt->bind_param("i", $userid);
    } else {
        $stmt = $conn->prepare("
            UPDATE notifications n
            JOIN `job-post` j ON n.jobid = j.jobid
            SET n.is_read = 1 
            WHERE j.recid = ?
        ");
        $stmt->bind_param("i", $recid);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// Get unread count
function getUnreadCount($conn, $userid, $recid) {
    if ($userid) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE userid = ? AND is_read = 0");
        $stmt->bind_param("i", $userid);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications n
            JOIN `job-post` j ON n.jobid = j.jobid
            WHERE j.recid = ? AND n.is_read = 0
        ");
        $stmt->bind_param("i", $recid);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'];
}

// Helper function to format time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Handle different request types
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        $notifications = getNotifications($conn, $userid, $recid);
        $unread_count = getUnreadCount($conn, $userid, $recid);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_read':
        $notification_id = $_POST['notification_id'] ?? null;
        if ($notification_id) {
            $success = markAsRead($conn, $notification_id, $userid, $recid);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Notification ID required']);
        }
        break;
        
    case 'mark_all_read':
        $success = markAllAsRead($conn, $userid, $recid);
        echo json_encode(['success' => $success]);
        break;
        
    case 'unread_count':
        $unread_count = getUnreadCount($conn, $userid, $recid);
        echo json_encode(['unread_count' => $unread_count]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?> 