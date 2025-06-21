<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['userid'])) {
    header("Location: /main/login.html");
    exit;
}
$userid = $_SESSION['userid'];

// Fetch all applications for this user
$stmt = $conn->prepare("
    SELECT a.*, j.designation, j.company, j.location
    FROM applied a
    JOIN `job-post` j ON a.jobid = j.jobid
    WHERE a.userid = ?
    ORDER BY a.applied_at DESC
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">My Applications</h2>
    <?php if (empty($applications)): ?>
        <div class="alert alert-info">You have not applied to any jobs yet.</div>
    <?php else: ?>
        <div class="list-group">
        <?php foreach ($applications as $app): ?>
            <div class="list-group-item mb-3">
                <h5><?= htmlspecialchars($app['designation']) ?> at <?= htmlspecialchars($app['company']) ?></h5>
                <div class="mb-2 text-muted"><?= htmlspecialchars($app['location']) ?> | Applied: <?= htmlspecialchars($app['applied_at']) ?></div>
                <div>
                    <strong>Cover Letter:</strong>
                    <?php if ($app['cover_letter_file']): ?>
                        <a href="/<?= htmlspecialchars($app['cover_letter_file']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Resume:</strong>
                    <?php if ($app['resume_file']): ?>
                        <a href="/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
                <?php if (!empty($app['answers'])): ?>
                    <div class="mt-2">
                        <strong>Your Answers:</strong>
                        <ul>
                        <?php
                        $answers = json_decode($app['answers'], true);
                        foreach ($answers as $idx => $answer) {
                            echo '<li>' . htmlspecialchars($answer) . '</li>';
                        }
                        ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>