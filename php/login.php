<?php
ob_start();
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$userType = $_POST['user_type'] ?? '';

if (!$username || !$password || !$userType) {
    header('Location: ../main/login.html?error=Missing+fields');
    exit;
}

if ($userType === 'A') {
    $table = 'recruiter';
    $idColumn = 'recid';
    $extraColumn = 'compid';
} else {
    $table = 'user';
    $idColumn = 'userid';
    $extraColumn = null;
}

// Fetch stored hash and id (and compid for recruiters)
if ($userType === 'A') {
    $stmt = $conn->prepare("SELECT `$idColumn`, `password`, `compid` FROM `$table` WHERE `username`=?");
} else {
    $stmt = $conn->prepare("SELECT `$idColumn`, `password` FROM `$table` WHERE `username`=?");
}
if (!$stmt) {
    header('Location: ../main/login.html?error=Server+error');
    exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    header('Location: ../main/login.html?error=No+such+user');
    exit;
}

if ($userType === 'A') {
    $stmt->bind_result($userId, $hash, $compid);
} else {
    $stmt->bind_result($userId, $hash);
}
$stmt->fetch();

if (strlen($hash) < 60) {
    header('Location: ../main/login.html?error=Server+error');
    exit;
}

if (!password_verify($password, $hash)) {
    header('Location: ../main/login.html?error=Bad+credentials');
    exit;
}

// Success: Set session and redirect
$_SESSION['username'] = $username;
$_SESSION['user_type'] = $userType;

if ($userType === 'A') {
    $_SESSION['recid'] = $userId;
    $_SESSION['compid'] = $compid; // <-- This line sets the company ID in the session
    header("Location: ../main/recruiter.php");
} else {
    $_SESSION['userid'] = $userId;
    header("Location: ../main/job-list.php");
}
exit;