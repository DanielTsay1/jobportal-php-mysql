<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../main/login.html');
  exit;
}

// Collect & trim
$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email']    ?? '');
$password         = $_POST['password']       ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$userType         = $_POST['user_type']      ?? '';

// Basic validation
if (!$username || !$email || !$password || !$confirm_password || !$userType) {
  header('Location: ../main/login.html?error=Missing+fields');
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: ../main/login.html?error=Invalid+email');
  exit;
}
if ($password !== $confirm_password) {
  header('Location: ../main/login.html?error=Passwords+do+not+match');
  exit;
}

// Hash the password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Decide table & its PK column
if ($userType === 'A') {
  $table    = 'recruiter';
  $idColumn = 'recid';
} else {
  $table    = 'user';
  $idColumn = 'userid';
}

// Check for existing username/email
$sql = "SELECT `$idColumn` FROM `$table` WHERE `username` = ? OR `email` = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  header('Location: ../main/login.html?error=User+or+email+exists');
  exit;
}

// Insert new user/recruiter
$sql = "INSERT INTO `$table` (`username`,`password`,`email`) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $username, $hash, $email);
if ($stmt->execute()) {
  $_SESSION['username'] = $username;
  $dest = ($userType === 'A') ? '../recruiter.php' : '../dashboard.php';
  header("Location: $dest");
  exit;
} else {
  header('Location: ../main/login.html?error=Registration+failed');
  exit;
}
?>
