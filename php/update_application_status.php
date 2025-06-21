<?php
// php/update_application_status.php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Ensure user is a recruiter and logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A' || !isset($_SESSION['compid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$app_id = $_POST['app_id'] ?? null;
$status = $_POST['status'] ?? null;
$compid = $_SESSION['compid'];

if (!$app_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

// Security Check: Verify this recruiter has permission to modify this application
$check_sql = "SELECT j.compid FROM applied a JOIN `job-post` j ON a.jobid = j.jobid WHERE a.`S. No` = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('i', $app_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['compid'] != $compid) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    $stmt->close();
    exit;
}
$stmt->close();


// Update the status
$update_sql = "UPDATE applied SET status = ? WHERE `S. No` = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param('si', $status, $app_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}
$stmt->close();
$conn->close();
?> 