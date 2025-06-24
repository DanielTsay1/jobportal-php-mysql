<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['recid']) || !isset($_SESSION['compid'])) {
    header("Location: ../main/login.php");
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
        <form action="/php/postjob.php" method="POST" id="postJobForm">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="designation" name="designation" placeholder="Job Title" required>
                        <label for="designation">Job Title / Designation</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="location" name="location" placeholder="Location" required>
                        <label for="location">Location (e.g., City, State)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="salary" name="salary" placeholder="Annual Salary" required>
                        <label for="salary">Annual Salary (USD)</label>
                    </div>
                </div>
                 <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="spots" name="spots" placeholder="Available Spots" value="1" min="1" required>
                        <label for="spots">Number of Available Spots</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Leave a message here" id="description" name="description" style="height: 150px" required></textarea>
                        <label for="description">Job Description</label>
                    </div>
                </div>
                <div class="col-12">
                    <h5>Screening Questions (Optional)</h5>
                    <div id="questions-container">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="questions[]" placeholder="e.g., How many years of experience do you have?">
                            <button class="btn btn-outline-danger" type="button" onclick="removeQuestion(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addQuestion()">+ Add another question</button>
                </div>
                <div class="col-12 text-center">
                    <button class="btn btn-primary py-3 px-5" type="submit">Post Job</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
<script>
function addQuestion() {
    const div = document.createElement('div');
    div.innerHTML = '<div class="input-group mb-3"><input type="text" class="form-control" name="questions[]" placeholder="Another question"><button class="btn btn-outline-danger" type="button" onclick="removeQuestion(this)">Remove</button></div>';
    document.getElementById('questions-container').appendChild(div);
}

function removeQuestion(button) {
    const div = button.parentElement;
    div.remove();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>