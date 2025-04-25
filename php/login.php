<?php
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
  $table    = 'recruiter';
  $idColumn = 'recid';
} else {
  $table    = 'user';
  $idColumn = 'userid';
}

// fetch stored hash
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

if (!password_verify($password, $hash)) {
  header('Location: ../main/login.html?error=Bad+credentials');
  exit;
}

// success: set session and redirect
$_SESSION['username']  = $username;
$_SESSION['user_type'] = $userType;
$dest = $userType === 'A' ? '../recruiter.php' : '../dashboard.php';
header("Location: $dest");
exit;
