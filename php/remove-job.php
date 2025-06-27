<?php
session_start();
require_once 'db.php';

// Only allow admins
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : (isset($_GET['jobid']) ? intval($_GET['jobid']) : 0);

if ($jobid <= 0) {
    http_response_code(400);
    echo "Invalid job ID";
    exit;
}

// Set job status to Pending instead of deleting
$stmt = $conn->prepare("UPDATE `job-post` SET status = 'Pending' WHERE `jobid` = ?");
$stmt->bind_param("i", $jobid);

if ($stmt->execute()) {
    header("Location: ../main/admin-dashboard.php?tab=jobs&msg=job_requeued");
    exit;
} else {
    echo "Error updating job status: " . $conn->error;
}
$stmt->close();
$conn->close();
?> 