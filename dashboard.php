<?php
session_start();
// Only allow normal users (type 'U')
if (empty($_SESSION['username']) || ($_SESSION['user_type'] ?? '') !== 'U') {
  header('Location: main/login.html');
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Job-Seeker Dashboard</title>
</head>
<body>
  <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
  <?php include 'main/job-list.php'; ?>
</body>
</html>
