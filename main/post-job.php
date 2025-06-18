<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['recid']) || !isset($_SESSION['compid'])) {
    header("Location: ../main/login.html");
    exit;
}

$compid = $_SESSION['compid'];

// Fetch company name for display and saving
$company = '';
$stmt = $conn->prepare("SELECT name FROM company WHERE compid = ?");
$stmt->bind_param("i", $compid);
$stmt->execute();
$stmt->bind_result($company);
$stmt->fetch();
$stmt->close();

$jobPosted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = trim($_POST['designation'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $status = 'Active';
    $questions = $_POST['questions'] ?? [];
    $questions_json = json_encode(array_filter(array_map('trim', $questions)));

    $stmt = $conn->prepare("INSERT INTO `job-post` (company, compid, designation, description, location, salary, status, created_at, questions) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("sisssiss", $company, $compid, $designation, $description, $location, $salary, $status, $questions_json);
    if ($stmt->execute()) {
        $jobPosted = true;
    } else {
        $error = "Error posting job.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Post a Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Post a Job</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($jobPosted): ?>
        <div class="alert alert-success">Job posted successfully!</div>
        <a href="recruiter-dashboard.php" class="btn btn-secondary">Back</a>
    <?php else: ?>
        <form method="post" class="card p-4 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Company</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($company) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Company ID</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($compid) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Salary</label>
                <input type="text" name="salary" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Custom Questions for Applicants (optional)</label>
                <div id="questions-list">
                    <input type="text" name="questions[]" class="form-control mb-2" placeholder="e.g. Why do you want this job?">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addQuestion()">Add Another Question</button>
            </div>
            <button type="submit" class="btn btn-primary">Post Job</button>
            <a href="recruiter-dashboard.php" class="btn btn-secondary ms-2">Back</a>
        </form>
    <?php endif; ?>
</div>
<script>
function addQuestion() {
    const div = document.createElement('div');
    div.innerHTML = '<input type="text" name="questions[]" class="form-control mb-2" placeholder="Another question">';
    document.getElementById('questions-list').appendChild(div);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>