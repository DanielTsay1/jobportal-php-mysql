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

$stmt = $conn->prepare("DELETE FROM `job-post` WHERE `jobid` = ?");
$stmt->bind_param("i", $jobid);

if ($stmt->execute()) {
    // Optionally, redirect back to dashboard
    header("Location: ../main/admin-dashboard.php?msg=job_removed");
    exit;
} else {
    echo "Error removing job: " . $conn->error;
}
$stmt->close();
$conn->close();
?> 