<?php
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
$jobid = $data['jobid'] ?? null;

if (!$jobid) {
    echo json_encode(['success' => false, 'message' => 'Job ID not provided.']);
    exit;
}

// Start a transaction
$conn->begin_transaction();

try {
    // Security Check: Verify this recruiter owns the job post
    $check_sql = "SELECT compid FROM `job-post` WHERE jobid = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $jobid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result || $result['compid'] != $compid) {
        throw new Exception('Permission denied.');
    }

    // Update the job status to 'Archived'
    $archive_sql = "UPDATE `job-post` SET status = 'Archived' WHERE jobid = ?";
    $stmt = $conn->prepare($archive_sql);
    $stmt->bind_param("i", $jobid);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Job not found or status was already Archived.');
    }
    $stmt->close();

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Job archived successfully.']);

} catch (Exception $e) {
    // Roll back if any error occurs
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 