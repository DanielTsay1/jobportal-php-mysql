<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\upload-resume.php -->
<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: /main/login.html?error=unauthorized');
    exit;
}

$userid = $_SESSION['userid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $file = $_FILES['resume'];
    
    // Validate file
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: /main/profile.php?error=upload_failed');
        exit;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        header('Location: /main/profile.php?error=invalid_type');
        exit;
    }
    
    if ($file['size'] > $maxSize) {
        header('Location: /main/profile.php?error=file_too_large');
        exit;
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
        $stmt = $conn->prepare("UPDATE user SET resume = ? WHERE userid = ?");
        $stmt->bind_param("si", $filename, $userid);
        
        if ($stmt->execute()) {
            header('Location: /main/profile.php?success=resume_uploaded');
        } else {
            header('Location: /main/profile.php?error=database_error');
        }
        $stmt->close();
    } else {
        header('Location: /main/profile.php?error=save_failed');
    }
} else {
    header('Location: /main/profile.php?error=no_file');
}

$conn->close();
?>