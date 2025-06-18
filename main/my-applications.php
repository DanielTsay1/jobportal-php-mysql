<?php
session_start();
require_once '../php/db.php';

$userid = $_SESSION['userid'] ?? null;
$username = $_SESSION['username'] ?? null;

// If not logged in, redirect to login
if (!$userid && !$username) {
    header("Location: login.html");
    exit;
}

// If only username is set, fetch userid
if (!$userid && $username) {
    $user_stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $user_stmt->bind_param("s", $username);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    $userid = $user['userid'] ?? null;
} elseif ($userid) {
    // If userid is set, fetch user info
    $user_stmt = $conn->prepare("SELECT * FROM user WHERE userid = ?");
    $user_stmt->bind_param("i", $userid);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
}

if (!$userid) {
    header("Location: login.html");
    exit;
}

// Fetch applied jobs
$stmt = $conn->prepare("SELECT a.*, jp.designation, c.name AS company_name FROM applied a JOIN `job-post` jp ON a.jobid = jp.jobid JOIN company c ON jp.compid = c.compid WHERE a.userid = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Applications</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-4">
    <h2 class="mb-4"><i class="fa fa-briefcase"></i> My Applications</h2>
    <?php if ($applications): ?>
        <div class="list-group">
            <?php foreach ($applications as $app): ?>
                <a href="job-details.php?jobid=<?= urlencode($app['jobid']) ?>" class="list-group-item list-group-item-action">
                    <h5 class="mb-1"><?= htmlspecialchars($app['designation']) ?></h5>
                    <p class="mb-1">Company: <?= htmlspecialchars($app['company_name']) ?></p>
                    <small>Applied on: <?= htmlspecialchars($app['applied_at']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have not applied for any jobs yet.</div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
