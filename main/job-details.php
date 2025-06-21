<?php
session_start();
require_once '../php/db.php';

$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;

// Fetch job info
$stmt = $conn->prepare("SELECT jp.*, c.name AS company_name FROM `job-post` jp JOIN company c ON jp.compid = c.compid WHERE jp.jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

$userid = $_SESSION['userid'] ?? null;

// Check if user already applied
$already_applied = false;
$app_id = null;
if ($userid) {
    $stmt = $conn->prepare("SELECT `S. No` FROM applied WHERE userid = ? AND jobid = ?");
    $stmt->bind_param("ii", $userid, $jobid);
    $stmt->execute();
    $stmt->bind_result($app_id);
    $already_applied = $stmt->fetch();
    $stmt->close();
}

$removed = isset($_GET['removed']) && $_GET['removed'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($job['designation'] ?? 'Job Details') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-4">
    <?php if (!$job): ?>
        <div class="alert alert-danger">Job not found.</div>
    <?php else: ?>
        <?php if ($removed): ?>
            <div class="alert alert-info">Your application has been removed.</div>
        <?php endif; ?>
        <h2><?= htmlspecialchars($job['designation']) ?> at <?= htmlspecialchars($job['company_name']) ?></h2>
        <div class="mb-2 text-muted"><?= htmlspecialchars($job['location']) ?></div>
        <div class="mb-4"><?= nl2br(htmlspecialchars($job['description'])) ?></div>
        <!-- Apply/View/Remove Application Button Logic -->
        <?php if (!isset($_SESSION['userid'])): ?>
            <a href="/main/login.html" class="btn btn-primary">Login to Apply</a>
        <?php elseif ($already_applied): ?>
            <a href="/main/my-applications.php#app<?= $app_id ?>" class="btn btn-success mt-3">View My Application</a>
            <form method="post" action="/php/remove-application.php" style="display:inline;">
                <input type="hidden" name="jobid" value="<?= $jobid ?>">
                <button type="submit" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to remove your application?');">Remove Application</button>
            </form>
        <?php else: ?>
            <a href="/main/apply.php?jobid=<?= $jobid ?>" class="btn btn-primary">Apply Now</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>