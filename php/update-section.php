<!-- filepath: c:\Users\mandy\jobportal-php-mysql\php\update-section.php -->
<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'] ?? 'Guest';
    $section = $_POST['section'] ?? null;
    $entries = implode('<br>', $_POST['entries'] ?? []);

    // Whitelist valid column names
    $validSections = ['about', 'education', 'experience'];
    if (in_array($section, $validSections) && $entries) {
        $stmt = $conn->prepare("UPDATE user SET $section = ? WHERE username = ?");
        $stmt->bind_param("ss", $entries, $username);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Section updated successfully!";
        } else {
            echo "No changes made.";
        }
    } else {
        echo "Invalid section or entries.";
    }
}
?>