<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// 1. Authentication and Authorization
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userid = $_SESSION['userid'];

// 2. Begin Transaction
$conn->begin_transaction();

try {
    // 3. Update the status of all other active applications to 'Withdrawn'
    // We target statuses that are considered "active" from a job seeker's perspective.
    $update_sql = "UPDATE applied 
                   SET status = 'Withdrawn' 
                   WHERE userid = ? 
                   AND status NOT IN ('Hired', 'Rejected', 'Withdrawn')";
                   
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->close();
    
    // Note: A further enhancement could be to notify the recruiters of the
    // withdrawn applications, but that requires a recruiter notification system.

    // 4. Commit Transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // If any error occurs, roll back the transaction
    $conn->rollback();
    error_log("Withdrawal Error for UserID {$userid}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while withdrawing applications.']);
}

$conn->close();
?> 