<?php
session_start();
// Add this block to check for a valid job seeker session
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    // Redirect them to the job details page with a message
    header('Location: /main/job-details.php?jobid=' . ($_GET['jobid'] ?? 0) . '&error=not_logged_in');
    exit;
}
require_once '../php/db.php';

$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;
$userid = $_SESSION['userid'];

$stmt = $conn->prepare("SELECT jp.*, c.name AS company_name FROM `job-post` jp JOIN company c ON jp.compid = c.compid WHERE jp.jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

$questions = [];
if ($job && !empty($job['questions'])) {
    $questions = json_decode($job['questions'], true) ?: [];
}

// Check for existing resume
$resume_stmt = $conn->prepare("SELECT resume FROM user WHERE userid = ?");
$resume_stmt->bind_param("i", $userid);
$resume_stmt->execute();
$user_resume = $resume_stmt->get_result()->fetch_assoc();
$has_resume = !empty($user_resume['resume']);
$resume_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Apply for <?= htmlspecialchars($job['designation'] ?? 'Job') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Apply for <?= htmlspecialchars($job['designation'] ?? 'Job') ?> at <?= htmlspecialchars($job['company_name'] ?? '') ?></h2>
    <?php if (!$job): ?>
        <div class="alert alert-danger">Job not found.</div>
    <?php else: ?>
    <form method="post" action="/php/apply.php" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <input type="hidden" name="jobid" value="<?= $jobid ?>">
        <div class="mb-3">
            <label class="form-label">Cover Letter (PDF/DOC/DOCX)</label>
            <input type="file" name="cover_letter_file" class="form-control" accept=".pdf,.doc,.docx" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Resume (PDF/DOC/DOCX)</label>
            <?php if ($has_resume): ?>
                <div class="alert alert-info">
                    <i class="fas fa-check-circle me-2"></i>
                    Using your saved resume: <strong><?= htmlspecialchars(basename($user_resume['resume'])) ?></strong>
                    <input type="hidden" name="existing_resume" value="<?= htmlspecialchars($user_resume['resume']) ?>">
                </div>
                <small class="form-text text-muted">
                    To use a different resume, please <a href="/main/profile.php">update your profile</a> first.
                </small>
            <?php else: ?>
                <input type="file" name="resume_file" class="form-control" accept=".pdf,.doc,.docx" required>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="save_resume_to_profile" id="save_resume_to_profile" checked>
                    <label class="form-check-label" for="save_resume_to_profile">
                        Save this resume to my profile for future applications.
                    </label>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($questions)): ?>
            <hr>
            <h5>Additional Questions</h5>
            <?php foreach ($questions as $idx => $q): ?>
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars($q) ?></label>
                    <input type="text" name="question_answers[<?= $idx ?>]" class="form-control" required>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Submit Application</button>
        <a href="job-details.php?jobid=<?= $jobid ?>" class="btn btn-secondary ms-2">Back</a>
    </form>
    <?php endif; ?>
</div>
</body>
</html>