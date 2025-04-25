<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../main/login.html');
  exit;
}

// collect & trim
$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email']    ?? '');
$password         = $_POST['password']      ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$userType         = $_POST['user_type']     ?? '';  // 'A' or 'U'

// basic validation
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

// hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// choose table & id column
if ($userType === 'A') {
  $table    = 'recruiter';
  $idColumn = 'recid';
} else {
  $table    = 'user';
  $idColumn = 'userid';
}

// check existing username/email
$stmt = $conn->prepare("SELECT `$idColumn` FROM `$table` WHERE `username`=? OR `email`=?");
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  header('Location: ../main/login.html?error=User+or+email+exists');
  exit;
}

// insert new record
$stmt = $conn->prepare("INSERT INTO `$table` (`username`,`password`,`email`) VALUES (?,?,?)");
$stmt->bind_param('sss', $username, $hash, $email);
if ($stmt->execute()) {
  $_SESSION['username']  = $username;
  $_SESSION['user_type'] = $userType;
  $dest = $userType === 'A' ? '../recruiter.php' : '../dashboard.php';
  header("Location: $dest");
  exit;
} else {
  header('Location: ../main/login.html?error=Registration+failed');
  exit;
}
