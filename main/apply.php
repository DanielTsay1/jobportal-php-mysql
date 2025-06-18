<?php
session_start();
require_once '../php/db.php';

$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;

$stmt = $conn->prepare("SELECT jp.*, c.name AS company_name FROM `job-post` jp JOIN company c ON jp.compid = c.compid WHERE jp.jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

$questions = [];
if ($job && !empty($job['questions'])) {
    $questions = json_decode($job['questions'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Apply for <?= htmlspecialchars($job['designation'] ?? 'Job') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <input type="file" name="resume_file" class="form-control" accept=".pdf,.doc,.docx" required>
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