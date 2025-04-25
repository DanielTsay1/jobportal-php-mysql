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
  header('Location: ../main/login.html?error=Missing+credentials');
  exit;
}

$table = $userType === 'A' ? 'recruiter' : 'user';

// Fetch hashed password
$stmt = $conn->prepare("SELECT username,password FROM $table WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
  header('Location: ../main/login.html?error=User+not+found');
  exit;
}

$stmt->bind_result($dbUser, $dbHash);
$stmt->fetch();

if (password_verify($password, $dbHash)) {
  $_SESSION['username'] = $dbUser;
  $dest = $userType === 'A' ? '../recruiter.php' : '../dashboard.php';
  header("Location: $dest");
  exit;
} else {
  header('Location: ../main/login.html?error=Incorrect+password');
  exit;
}
?>
