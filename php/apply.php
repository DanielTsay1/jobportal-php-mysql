<?php
session_start();
require_once 'db.php';

// 1. Authentication and Authorization
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B') {
    header('Location: /main/login.html?error=unauthorized');
    exit;
}
if (!isset($_SESSION['userid'])) {
    header('Location: /main/login.html?error=nouserid');
    exit;
}
$userid = $_SESSION['userid'];

// 2. Validate Input
$jobid = filter_input(INPUT_POST, 'jobid', FILTER_VALIDATE_INT);
if (!$jobid) {
    die("Invalid Job ID provided.");
}

// Check if user has already applied
$stmt_check = $conn->prepare("SELECT `S. No` FROM applied WHERE userid = ? AND jobid = ?");
$stmt_check->bind_param("ii", $userid, $jobid);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    header('Location: /main/job-details.php?jobid=' . $jobid . '&error=alreadyapplied');
    exit;
}
$stmt_check->close();

// 3. Handle File Uploads
function handle_upload($file_key, $prefix) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = realpath(__DIR__ . '/../uploads') . '/';
        $file_extension = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            die("Error: Invalid file type for $prefix. Please upload a PDF, DOC, or DOCX.");
        }
        $file_name = $prefix . '_' . uniqid() . '.' . $file_extension;
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $destination)) {
            return $file_name;
        } else {
            // Log error, more robustly in a real app
            error_log("File upload failed for {$file_key}. Temp: {$_FILES[$file_key]['tmp_name']}, Dest: {$destination}");
            die("Error uploading file. Check permissions for 'uploads' directory.");
        }
    }
    return null; // Return null if file not present or upload error
}

$cover_letter_filename = handle_upload('cover_letter_file', 'cover');
$resume_filename = handle_upload('resume_file', 'resume');

if (!$resume_filename) {
    die("A resume is required to apply.");
}

// 4. Handle Question Answers
$answers_json = null;
if (isset($_POST['question_answers']) && is_array($_POST['question_answers'])) {
    // Basic sanitization
    $answers = array_map('htmlspecialchars', $_POST['question_answers']);
    $answers_json = json_encode($answers);
}

// 5. Insert into Database
$sql = "INSERT INTO applied (userid, jobid, cover_letter_file, resume_file, answers) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database prepare failed: " . $conn->error);
}

$stmt->bind_param("iisss", $userid, $jobid, $cover_letter_filename, $resume_filename, $answers_json);

if ($stmt->execute()) {
    header('Location: /main/job-details.php?jobid=' . $jobid);
    exit;
} else {
    // More specific error for debugging
    die("Database execute failed: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>