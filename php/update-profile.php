<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\update-profile.php -->
<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userid = $_SESSION['userid'];

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
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function updatePersonalInfo($conn, $userid) {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Validate website if provided
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid website URL']);
        return;
    }
    
    $sql = "UPDATE user SET email = ?, phone = ?, location = ?, website = ?, about = ?, education = ?, experience = ? WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $email, $phone, $location, $website, $about, $education, $experience, $userid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    $stmt->close();
}

function changePassword($conn, $userid) {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        return;
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM user WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$result || !password_verify($currentPassword, $result['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE userid = ?");
    $stmt->bind_param("si", $hashedPassword, $userid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
    $stmt->close();
}

function uploadProfilePicture($conn, $userid) {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        return;
    }
    
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed']);
        return;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
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
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $relativePath = 'uploads/profile_pictures/' . $filename;
        $stmt = $conn->prepare("UPDATE user SET profile_picture = ? WHERE userid = ?");
        $stmt->bind_param("si", $relativePath, $userid);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile picture uploaded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
}

function uploadResume($conn, $userid) {
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        return;
    }
    
    $file = $_FILES['resume'];
    $originalFilename = $file['name'];
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed']);
        return;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
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
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $stmt = $conn->prepare("INSERT INTO user_resumes (user_id, filename, original_filename) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userid, $filename, $originalFilename);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Resume uploaded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
}

function deleteResume($conn, $userid, $resumeId) {
    if ($resumeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid resume ID']);
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
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM user_resumes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resumeId, $userid);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Resume deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No resume found to delete']);
    }
}

$conn->close();
?>