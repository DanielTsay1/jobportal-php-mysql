<?php
ob_start(); // Start output buffering
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
} else {
    $table = 'user';
    $idColumn = 'userid';
}

// Fetch stored hash
$stmt = $conn->prepare("SELECT `password` FROM `$table` WHERE `username`=?");
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

$stmt->bind_result($hash);
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

// Debugging: Log redirection
error_log("User type: $userType");
error_log("Redirecting to: " . ($userType === 'A' ? '../recruiter.php' : '../main/job-list.php'));

// Redirect based on user type
if ($userType === 'A') {
    // Redirect recruiters to recruiter.php
    header("Location: ../recruiter.php");
} else {
    // Redirect jobseekers to job-list.php
    header("Location: ../main/job-list.php");
}
exit;