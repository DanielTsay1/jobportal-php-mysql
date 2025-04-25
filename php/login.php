<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo "Request method is not POST. Redirecting to login page.\n";
  header('Location: ../main/login.html');
  exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$userType = $_POST['user_type'] ?? '';

if (!$username || !$password || !$userType) {
  echo "Missing fields: username, password, or user type.\n";
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

// Debug: Log the table and username being queried
echo "Querying table: $table for username: $username\n";

// fetch stored hash
$stmt = $conn->prepare("SELECT `password` FROM `$table` WHERE `username`=?");
if (!$stmt) {
  echo "Prepare failed: " . $conn->error . "\n";
  header('Location: ../main/login.html?error=Server+error');
  exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
  echo "No user found with username: $username\n";
  header('Location: ../main/login.html?error=No+such+user');
  exit;
}

$stmt->bind_result($hash);
$stmt->fetch();

// Debug: Log the retrieved hash and its length
echo "Retrieved hash for username $username: $hash (Length: " . strlen($hash) . ")\n";


if (strlen($hash) < 60) {
  echo "Error: Retrieved hash is too short. Check database schema.\n";
  header('Location: ../main/login.html?error=Server+error');
  exit;
}

if (!password_verify($password, $hash)) {
  echo "Password verification failed for username: $username\n";
  echo "Retrieved username: $username\n";
  echo "Retrieved hash: $hash\n";
  header('Location: ../main/login.html?error=Bad+credentials');
  exit;
}

// success: set session and redirect
$_SESSION['username']  = $username;
$_SESSION['user_type'] = $userType;

// Debug: Log successful login
echo "Login successful for username: $username, user type: $userType\n";

$dest = $userType === 'A' ? '../recruiter.php' : '../dashboard.php';
header("Location: $dest");
exit;