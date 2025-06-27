<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Unauthorized');
}
require_once 'db.php';

$jobid = $_POST['jobid'] ?? null;
$action = $_POST['action'] ?? null;

if ($jobid && in_array($action, ['approve', 'reject'])) {
    $status = $action === 'approve' ? 'Active' : 'Rejected';
    $stmt = $conn->prepare("UPDATE `job-post` SET status = ? WHERE jobid = ?");
    $stmt->bind_param('si', $status, $jobid);
    $stmt->execute();
}
header('Location: ../main/admin-dashboard.php?tab=jobs');
exit; 