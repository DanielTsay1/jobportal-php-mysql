<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\update-profile.php -->
<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'] ?? 'Guest';

    $about = $_POST['about'] ?? null;
    $education = $_POST['education'] ?? null;
    $experience = $_POST['experience'] ?? null;

    $stmt = $conn->prepare("UPDATE user SET about = ?, education = ?, experience = ? WHERE username = ?");
    $stmt->bind_param("ssss", $about, $education, $experience, $username);
    $stmt->execute();

    header("Location: /main/profile.php");
    exit;
}
?>