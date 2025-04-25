<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../main/login.html');
  exit;
}

$username = trim($_POST['username'] ?? '');  // Trim whitespace around the username
$password = $_POST['password'] ?? '';
$userType = $_POST['user_type'] ?? '';

if (!$username || !$password || !$userType) {
  header('Location: ../main/login.html?error=Missing+fields');
  exit;
}

if ($userType === 'A') {
  $table    = 'recruiter';
  $idColumn = 'recid';
} else {
  $table    = 'user';
  $idColumn = 'userid';
}

// Fetch stored hash
$stmt = $conn->prepare("SELECT `password` FROM `$table` WHERE `username`=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
  header('Location: ../main/login.html?error=No+such+user');
  exit;
}

$stmt->bind_result($hash);
$stmt->fetch();

// Debugging: Check what password and hash are being compared
error_log("Password: $password");
error_log("Hash: $hash");

// Verify password
if (!password_verify($password, $hash)) {
  // Debugging: Show the values being compared if password verification fails
  error_log("Password verification failed. Input password: $password, Stored hash: $hash");
  header('Location: ../main/login.html?error=Bad+credentials');
  exit;
}

// Success: Set session and redirect
$_SESSION['username']  = $username;
$_SESSION['user_type'] = $userType;
$dest = $userType === 'A' ? '../recruiter.php' : '../dashboard.php';
header("Location: $dest");
exit;
