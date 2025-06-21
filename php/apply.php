<?php
session_start();
require_once '../php/db.php';

// Ensure the user is a logged-in jobseeker
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: /main/login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main/index.php'); // Redirect if not a POST request
    exit;
}

$userid = $_SESSION['userid'];
$jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : 0;
$answers = $_POST['question_answers'] ?? [];

// Validate jobid
if ($jobid <= 0) {
    die("Invalid job ID.");
}

// File upload configuration
$upload_dir = realpath(dirname(__FILE__) . '/../uploads') . '/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function to handle file uploads
function handle_upload($file_key, $user_id) {
    global $upload_dir;
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES[$file_key]['tmp_name'];
        $file_name = $_FILES[$file_key]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_ext = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            die("Error: Invalid file type for {$file_key}. Only PDF, DOC, and DOCX are allowed.");
        }

        // Create a unique filename
        $unique_name = $file_key . '_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $unique_name;

        if (move_uploaded_file($file_tmp_name, $destination)) {
            return $unique_name;
        }
    }
    return null;
}

$cover_letter_filename = handle_upload('cover_letter_file', $userid);
$resume_filename = handle_upload('resume_file', $userid);

if (!$resume_filename) {
    die("Error: Resume is required.");
}

// Prepare to insert into the database
$answers_json = !empty($answers) ? json_encode($answers) : null;

$sql = "INSERT INTO applied (userid, jobid, cover_letter_file, resume_file, answers, applied_at) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("DB Prepare Error: " . $conn->error);
}

$stmt->bind_param("iisss", $userid, $jobid, $cover_letter_filename, $resume_filename, $answers_json);

if ($stmt->execute()) {
    header("Location: /main/my-applications.php?success=1");
} else {
    die("DB Execute Error: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>