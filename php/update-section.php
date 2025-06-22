<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\update-section.php -->
<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    echo "unauthorized";
    exit;
}

$userid = $_SESSION['userid'];
$section = $_POST['section'] ?? '';
$entries = $_POST['entries'] ?? [];

// Validate section
$allowedSections = ['about', 'education', 'experience'];
if (!in_array($section, $allowedSections)) {
    echo "invalid_section";
    exit;
}

// Process entries
if (is_array($entries)) {
    $content = implode('<br>', array_filter($entries)); // Filter out empty entries
} else {
    $content = trim($entries);
}

// Update the section
$sql = "UPDATE user SET $section = ? WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $content, $userid);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "failed";
}

$stmt->close();
$conn->close();
?>