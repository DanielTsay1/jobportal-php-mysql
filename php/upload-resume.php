<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\upload-resume.php -->
<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $username = $_SESSION['username'] ?? 'Guest';
    $resume = $_FILES['resume'];

    // Validate file type and size
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($resume['type'], $allowedTypes) || $resume['size'] > 5 * 1024 * 1024) {
        die("Invalid file type or size. Please upload a PDF or Word document under 5MB.");
    }

    // Save the file
    $uploadDir = '../uploads/resumes/';
    $uploadPath = $uploadDir . basename($resume['name']);
    if (!move_uploaded_file($resume['tmp_name'], $uploadPath)) {
        die("Failed to upload resume.");
    }

    echo "Resume uploaded successfully!";
}
?>