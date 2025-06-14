<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\upload-profile.php -->
<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $username = $_SESSION['username'] ?? 'Guest';
    $profile_picture = $_FILES['profile_picture'];

    // Validate file type and size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($profile_picture['type'], $allowedTypes) || $profile_picture['size'] > 2 * 1024 * 1024) {
        die("Invalid file type or size. Please upload an image under 2MB.");
    }

    // Save the file
    $uploadDir = '../uploads/profile_pictures/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $uploadPath = $uploadDir . basename($profile_picture['name']);
    if (!move_uploaded_file($profile_picture['tmp_name'], $uploadPath)) {
        die("Failed to upload profile picture.");
    }

    // Update database
    $stmt = $conn->prepare("UPDATE user SET profile_picture = ? WHERE username = ?");
    $stmt->bind_param("ss", $uploadPath, $username);
    $stmt->execute();

    echo "Profile picture updated successfully!";
}
?>