<?php
session_start();
// Only allow recruiters (type 'A')
if (empty($_SESSION['username']) || ($_SESSION['user_type'] ?? '') !== 'A') {
  header('Location: main/login.html');
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Recruiter Dashboard</title>
</head>
<body>
  <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (Recruiter)</h1>
  <?php include 'main/recruiter-jobpost.html'; ?>
</body>
</html>
