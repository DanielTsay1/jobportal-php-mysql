<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['userid'])) {
    header("Location: /main/login.html");
    exit;
}

$userid = $_SESSION['userid'];
$jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : 0;

// DEBUG: Log jobid for troubleshooting
file_put_contents(__DIR__ . '/debug_jobid.txt', "jobid: $jobid\n", FILE_APPEND);

// 1. Check that the job exists
$stmt = $conn->prepare("SELECT jobid FROM `job-post` WHERE jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die("Invalid job. Please go back and try again.");
}
$stmt->close();

// 2. Handle file uploads
$coverLetterPath = '';
$resumePath = '';
$uploadDir = __DIR__ . '/../uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['cover_letter_file']) && $_FILES['cover_letter_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['cover_letter_file']['name'], PATHINFO_EXTENSION);
    $coverLetterPath = 'uploads/cover_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['cover_letter_file']['tmp_name'], __DIR__ . '/../' . $coverLetterPath);
}

if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION);
    $resumePath = 'uploads/resume_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['resume_file']['tmp_name'], __DIR__ . '/../' . $resumePath);
}

// 3. Handle recruiter questions
$question_answers = $_POST['question_answers'] ?? [];
$answers_json = json_encode($question_answers);

// 4. Insert application
$stmt = $conn->prepare("INSERT INTO applied (userid, jobid, applied_at, cover_letter_file, resume_file, answers) VALUES (?, ?, NOW(), ?, ?, ?)");
$stmt->bind_param("iisss", $userid, $jobid, $coverLetterPath, $resumePath, $answers_json);

if ($stmt->execute()) {
    header("Location: /main/job-details.php?jobid=$jobid&applied=1");
    exit;
} else {
    echo "Error submitting application: " . $stmt->error;
}
$stmt->close();
?>