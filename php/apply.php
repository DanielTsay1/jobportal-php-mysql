<?php
session_start();
require_once 'db.php';

// 1. Authentication and Authorization
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: /main/login.php?error=unauthorized');
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

// 3. Handle File Uploads and Resume Logic
$resume_filename = null;
$upload_dir = __DIR__ . '/../uploads';

// Ensure the upload directory exists and is writable
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        error_log("Failed to create uploads directory at: " . $upload_dir);
        die("A server configuration error occurred (Code: UPLOAD_DIR_CREATE). Please contact support.");
    }
}
if (!is_writable($upload_dir)) {
    error_log("Uploads directory is not writable: " . $upload_dir);
    die("A server configuration error occurred (Code: UPLOAD_DIR_WRITE). Please contact support.");
}
$upload_dir .= '/';

// Function to handle a new file upload
function handle_upload($file_key, $prefix, $upload_dir) {
    if (isset($_FILES[$file_key])) {
        
        // Check for upload errors
        if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
            // UPLOAD_ERR_NO_FILE means the user didn't select a file, which is handled later.
            if ($_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            // For all other errors, it's a server/configuration issue.
            error_log("File upload error for key '{$file_key}': error code {$_FILES[$file_key]['error']}");
            die("An unexpected error occurred during file upload. Please try again or contact support.");
        }

        $file_extension = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        if (!in_array($file_extension, $allowed_extensions)) {
            die("Error: Invalid file type for '{$file_key}'. Please upload a PDF, DOC, or DOCX.");
        }
        $file_name = $prefix . '_' . uniqid() . '.' . $file_extension;
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $destination)) {
            return $file_name;
        } else {
            error_log("File move failed for {$file_key}. Temp: {$_FILES[$file_key]['tmp_name']}, Dest: {$destination}");
            die("Error saving uploaded file. Please check server permissions.");
        }
    }
    return null;
}

// Determine which resume to use
if (!empty($_POST['existing_resume'])) {
    // Use the existing resume saved on the profile
    $resume_filename = basename($_POST['existing_resume']);
    // Security check: ensure the file actually exists
    if (!file_exists($upload_dir . $resume_filename)) {
        error_log("Saved resume file not found at path: " . $upload_dir . $resume_filename);
        die("Error: Your saved resume file could not be found. Please re-upload it on your profile.");
    }
} else {
    // Handle a new resume upload
    $resume_filename = handle_upload('resume_file', 'resume', $upload_dir);
    if (!$resume_filename) {
        die("A resume is required to apply. Please upload one.");
    }

    // Check if the user wants to save this resume to their profile
    if (isset($_POST['save_resume_to_profile'])) {
        $update_user_stmt = $conn->prepare("UPDATE user SET resume = ? WHERE userid = ?");
        $update_user_stmt->bind_param("si", $resume_filename, $userid);
        $update_user_stmt->execute();
        $update_user_stmt->close();
    }
}

$cover_letter_filename = handle_upload('cover_letter_file', 'cover', $upload_dir);
if (!$cover_letter_filename) {
    die("A cover letter is required to apply. Please upload one.");
}

// 4. Handle Question Answers
$answers_json = null;
if (isset($_POST['question_answers']) && is_array($_POST['question_answers'])) {
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
    header('Location: /main/my-applications.php?success=applied');
    exit;
} else {
    die("Database execute failed: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>