<?php
// Start output buffering to prevent any whitespace or output before JSON
ob_start();

session_start();
require_once 'db.php';

// Clear any output buffer
ob_clean();

header('Content-Type: application/json');

// Debug logging
error_log("update-profile.php called with action: " . ($_POST['action'] ?? 'none'));

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    error_log("Unauthorized access attempt - user_type: " . ($_SESSION['user_type'] ?? 'not set') . ", userid: " . ($_SESSION['userid'] ?? 'not set'));
    $response = ['success' => false, 'message' => 'Unauthorized'];
    error_log("Sending JSON response: " . json_encode($response));
    echo json_encode($response);
    exit;
}

$userid = $_SESSION['userid'];
error_log("Processing request for userid: " . $userid);

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update_personal':
        updatePersonalInfo($conn, $userid);
        break;
    case 'change_password':
        changePassword($conn, $userid);
        break;
    case 'upload_picture':
        uploadProfilePicture($conn, $userid);
        break;
    case 'upload_resume':
        uploadResume($conn, $userid);
        break;
    case 'delete_resume':
        $resumeId = $_POST['resume_id'] ?? 0;
        deleteResume($conn, $userid, $resumeId);
        break;
    case 'rename_resume':
        $resumeId = $_POST['resume_id'] ?? 0;
        $newName = $_POST['new_name'] ?? '';
        renameResume($conn, $userid, $resumeId, $newName);
        break;
    default:
        $response = ['success' => false, 'message' => 'Invalid action'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        break;
}

function updatePersonalInfo($conn, $userid) {
    error_log("updatePersonalInfo called for userid: " . $userid);
    
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    error_log("Received data - email: $email, phone: $phone, location: $location");
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: $email");
        $response = ['success' => false, 'message' => 'Invalid email format'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Validate website if provided
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        error_log("Invalid website URL: $website");
        $response = ['success' => false, 'message' => 'Invalid website URL'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    $sql = "UPDATE user SET email = ?, phone = ?, location = ?, website = ?, about = ?, education = ?, experience = ? WHERE userid = ?";
    error_log("Executing SQL: " . $sql);
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $response = ['success' => false, 'message' => 'Database prepare failed: ' . $conn->error];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    $stmt->bind_param("sssssssi", $email, $phone, $location, $website, $about, $education, $experience, $userid);
    
    if ($stmt->execute()) {
        error_log("Profile update successful for userid: " . $userid);
        $response = ['success' => true, 'message' => 'Profile updated successfully'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    } else {
        error_log("Profile update failed: " . $stmt->error);
        $response = ['success' => false, 'message' => 'Failed to update profile: ' . $stmt->error];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
    $stmt->close();
}

function changePassword($conn, $userid) {
    error_log("changePassword called for userid: " . $userid);
    
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        $response = ['success' => false, 'message' => 'All password fields are required'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM user WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result || !password_verify($currentPassword, $result['password'])) {
        $response = ['success' => false, 'message' => 'Current password is incorrect'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE userid = ?");
    $stmt->bind_param("si", $hashedPassword, $userid);
    
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Password changed successfully'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    } else {
        $response = ['success' => false, 'message' => 'Failed to change password: ' . $stmt->error];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
    $stmt->close();
}

function uploadProfilePicture($conn, $userid) {
    error_log("uploadProfilePicture called for userid: " . $userid);
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        error_log("Profile picture upload error: " . ($_FILES['profile_picture']['error'] ?? 'no file'));
        $response = ['success' => false, 'message' => 'No file uploaded or upload error'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    error_log("Profile picture file info - name: " . $file['name'] . ", type: " . $file['type'] . ", size: " . $file['size']);
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        $response = ['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $response = ['success' => false, 'message' => 'File size must be less than 5MB'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/profile_pictures/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userid . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    error_log("Saving profile picture to: $filepath");
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $relativePath = 'uploads/profile_pictures/' . $filename;
        $stmt = $conn->prepare("UPDATE user SET profile_picture = ? WHERE userid = ?");
        $stmt->bind_param("si", $relativePath, $userid);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Profile picture uploaded successfully'];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        } else {
            $response = ['success' => false, 'message' => 'Failed to update database: ' . $stmt->error];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'Failed to save file'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
}

function uploadResume($conn, $userid) {
    error_log("uploadResume called for userid: " . $userid);
    
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        error_log("Resume upload error: " . ($_FILES['resume']['error'] ?? 'no file'));
        $response = ['success' => false, 'message' => 'No file uploaded or upload error'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    $file = $_FILES['resume'];
    $originalFilename = $file['name'];
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    error_log("Resume file info - name: $originalFilename, type: " . $file['type'] . ", size: " . $file['size']);
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        $response = ['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $response = ['success' => false, 'message' => 'File size must be less than 5MB'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'resume_' . $userid . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    error_log("Saving resume to: $filepath");
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $stmt = $conn->prepare("INSERT INTO user_resumes (user_id, filename, original_filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userid, $filename, $originalFilename);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Resume uploaded successfully'];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        } else {
            $response = ['success' => false, 'message' => 'Failed to update database: ' . $stmt->error];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'Failed to save file'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
}

function deleteResume($conn, $userid, $resumeId) {
    error_log("deleteResume called for userid: $userid, resumeId: $resumeId");
    
    if ($resumeId <= 0) {
        $response = ['success' => false, 'message' => 'Invalid resume ID'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }

    // Get current resume filename to delete the file
    $stmt = $conn->prepare("SELECT filename FROM user_resumes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $resumeId, $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result && $result['filename']) {
        // Delete file from server
        $filepath = '../uploads/' . $result['filename'];
        error_log("Attempting to delete file: $filepath");
        
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                error_log("File deleted successfully: $filepath");
            } else {
                error_log("Failed to delete file: $filepath");
            }
        } else {
            error_log("File not found: $filepath");
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM user_resumes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resumeId, $userid);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Resume deleted successfully'];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        } else {
            $response = ['success' => false, 'message' => 'Failed to update database: ' . $stmt->error];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'No resume found to delete'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
}

function renameResume($conn, $userid, $resumeId, $newName) {
    error_log("renameResume called for userid: $userid, resumeId: $resumeId, newName: $newName");
    
    if ($resumeId <= 0 || empty($newName)) {
        $response = ['success' => false, 'message' => 'Invalid resume ID or new name'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }

    // Validate the new name (basic validation)
    $newName = trim($newName);
    if (strlen($newName) > 100) {
        $response = ['success' => false, 'message' => 'Resume name is too long (max 100 characters)'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
        return;
    }

    // Check if resume exists and belongs to user
    $stmt = $conn->prepare("SELECT original_filename FROM user_resumes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $resumeId, $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        // Update the display name in database
        $stmt = $conn->prepare("UPDATE user_resumes SET original_filename = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newName, $resumeId, $userid);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Resume renamed successfully'];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        } else {
            $response = ['success' => false, 'message' => 'Failed to update database: ' . $stmt->error];
            error_log("Sending JSON response: " . json_encode($response));
            echo json_encode($response);
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'No resume found to rename'];
        error_log("Sending JSON response: " . json_encode($response));
        echo json_encode($response);
    }
}

$conn->close();

// End output buffering and send the response
ob_end_flush();
?>