<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['userid']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B') {
    header('Location: /main/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main/job-list.php');
    exit;
}

$userid = $_SESSION['userid'];
$jobid = intval($_POST['jobid'] ?? 0);

if ($jobid <= 0) {
    header('Location: /main/job-list.php');
    exit;
}

// Check if company is suspended
$suspension_check = $conn->prepare("SELECT c.suspended, c.suspension_reason FROM `job-post` j JOIN company c ON j.compid = c.compid WHERE j.jobid = ?");
$suspension_check->bind_param("i", $jobid);
$suspension_check->execute();
$suspension_result = $suspension_check->get_result();
$company_status = $suspension_result->fetch_assoc();
$suspension_check->close();

if (!empty($company_status['suspended']) && $company_status['suspended'] == 1) {
    // Company is suspended, redirect with error
    header("Location: /main/job-details.php?jobid=" . $jobid . "&error=suspended&reason=" . urlencode($company_status['suspension_reason'] ?? 'No reason provided.'));
    exit();
}

// Check if user has already applied
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM applied WHERE userid = ? AND jobid = ?");
$check_stmt->bind_param("ii", $userid, $jobid);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if ($check_result['count'] > 0) {
    header('Location: /main/job-details.php?jobid=' . $jobid . '&error=already_applied');
    exit;
}

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

// Determine which resume to use based on user selection
$resume_type = $_POST['resume_type'] ?? '';

if ($resume_type === 'existing') {
    // User selected an existing resume from their profile
    $selected_resume_id = $_POST['selected_resume_id'] ?? null;
    
    if (!$selected_resume_id) {
        // Try to get the first resume if none specifically selected
        $stmt = $conn->prepare("SELECT filename FROM user_resumes WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            $resume_filename = $result['filename'];
        } else {
            die("No resume found in your profile. Please upload a resume first.");
        }
    } else {
        // Get the specific selected resume
        $stmt = $conn->prepare("SELECT filename FROM user_resumes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $selected_resume_id, $userid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            $resume_filename = $result['filename'];
        } else {
            die("Selected resume not found. Please try again.");
        }
    }
    
    // Security check: ensure the file actually exists
    if (!file_exists($upload_dir . $resume_filename)) {
        error_log("Selected resume file not found at path: " . $upload_dir . $resume_filename);
        die("Error: Your selected resume file could not be found. Please re-upload it on your profile.");
    }
    
} elseif ($resume_type === 'new') {
    // User is uploading a new resume
    $resume_filename = handle_upload('resume_file', 'resume', $upload_dir);
    if (!$resume_filename) {
        die("A resume is required to apply. Please upload one.");
    }

    // Check if the user wants to save this resume to their profile
    if (isset($_POST['save_resume_to_profile']) && $_POST['save_resume_to_profile'] === 'on') {
        $original_filename = $_FILES['resume_file']['name'];
        $stmt = $conn->prepare("INSERT INTO user_resumes (user_id, filename, original_filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userid, $resume_filename, $original_filename);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Fallback: check if user has any resumes and use the first one
    $stmt = $conn->prepare("SELECT filename FROM user_resumes WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        $resume_filename = $result['filename'];
    } else {
        // No resumes found, try to handle a new upload
        $resume_filename = handle_upload('resume_file', 'resume', $upload_dir);
        if (!$resume_filename) {
            die("A resume is required to apply. Please upload one.");
        }
        
        // Save to profile by default
        $original_filename = $_FILES['resume_file']['name'];
        $stmt = $conn->prepare("INSERT INTO user_resumes (user_id, filename, original_filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userid, $resume_filename, $original_filename);
        $stmt->execute();
        $stmt->close();
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