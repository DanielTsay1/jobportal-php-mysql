<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['userid'])) {
    header("Location: /main/login.html");
    exit;
}

$userid = $_SESSION['userid'];
$jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : 0;

if ($jobid > 0) {
    $stmt = $conn->prepare("DELETE FROM applied WHERE userid = ? AND jobid = ?");
    $stmt->bind_param("ii", $userid, $jobid);
    $stmt->execute();
    $stmt->close();
}

header("Location: /main/job-details.php?jobid=$jobid&removed=1");
exit;
?>