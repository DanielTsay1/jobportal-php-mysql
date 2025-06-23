<?php
// php/update_application_status.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Authentication: Only recruiters can access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A' || !isset($_SESSION['compid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$compid = $_SESSION['compid'];
$data = json_decode(file_get_contents('php://input'), true);
$appId = $data['app_id'] ?? null;
$newStatus = $data['status'] ?? null;

if (!$appId || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Incomplete data provided.']);
    exit;
}

// Check that the application belongs to the recruiter's company and get current app data
$check_sql = "SELECT a.userid, a.jobid, a.status as old_status 
              FROM applied a
              JOIN `job-post` j ON a.jobid = j.jobid
              WHERE a.`S. No` = ? AND j.compid = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $appId, $compid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Application not found or access denied.']);
    exit;
}
$app_data = $result->fetch_assoc();
$userid = $app_data['userid'];
$jobid = $app_data['jobid'];
$oldStatus = $app_data['old_status'];
$stmt->close();

// If status is not changing, do nothing
if ($newStatus === $oldStatus) {
    echo json_encode(['success' => true, 'message' => 'Status is already set.']);
    exit;
}

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // 1. Update the application status
    $update_sql = "UPDATE applied SET status = ? WHERE `S. No` = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $newStatus, $appId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to update application status.");
    }
    $stmt->close();

    // 2. Adjust spots based on status changes to/from 'Hired'
    $spot_adjustment_sql = null;
    if ($newStatus === 'Hired' && $oldStatus !== 'Hired') {
        // Decrement spots when hiring
        $spot_adjustment_sql = "UPDATE `job-post` SET spots = GREATEST(0, spots - 1) WHERE jobid = ?";
    } elseif ($newStatus !== 'Hired' && $oldStatus === 'Hired') {
        // Increment spots when un-hiring
        $spot_adjustment_sql = "UPDATE `job-post` SET spots = spots + 1 WHERE jobid = ?";
    }

    if ($spot_adjustment_sql) {
        $job_stmt = $conn->prepare($spot_adjustment_sql);
        $job_stmt->bind_param("i", $jobid);
        $job_stmt->execute();
        $job_stmt->close();
    }

    // 3. Check if spots are now zero to close the job, or reopen if spots are available
    $spots_stmt = $conn->prepare("SELECT spots FROM `job-post` WHERE jobid = ?");
    $spots_stmt->bind_param("i", $jobid);
    $spots_stmt->execute();
    $spots_result = $spots_stmt->get_result()->fetch_assoc();
    $spots_stmt->close();

    if ($spots_result) {
        if ($spots_result['spots'] <= 0) {
            $status_update_sql = "UPDATE `job-post` SET status = 'Inactive' WHERE jobid = ?";
        } else {
            $status_update_sql = "UPDATE `job-post` SET status = 'Active' WHERE jobid = ?";
        }
        $status_stmt = $conn->prepare($status_update_sql);
        $status_stmt->bind_param("i", $jobid);
        $status_stmt->execute();
        $status_stmt->close();
    }
    
    // 4. Create a notification for the job seeker
    $job_title_stmt = $conn->prepare("SELECT designation FROM `job-post` WHERE jobid = ?");
    $job_title_stmt->bind_param("i", $jobid);
    $job_title_stmt->execute();
    $job_title = $job_title_stmt->get_result()->fetch_assoc()['designation'];
    $job_title_stmt->close();
    
    $message = "Your application status for '<b>" . htmlspecialchars($job_title) . "</b>' was updated to <b>" . htmlspecialchars($newStatus) . "</b>.";
    $link = "/main/my-applications.php";
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (userid, message, link) VALUES (?, ?, ?)");
    $notif_stmt->bind_param("iss", $userid, $message, $link);
    $notif_stmt->execute();
    $notif_stmt->close();

    // If all queries were successful, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // If any query fails, roll back the entire transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 