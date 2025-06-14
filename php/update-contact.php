<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'] ?? '';
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    // Only allow these fields to be updated
    $allowed = ['email', 'phone', 'location', 'website'];
    if (!in_array($field, $allowed, true)) {
        echo "error: invalid field";
        exit;
    }
    if ($username === '') {
        echo "error: no username";
        exit;
    }

    $stmt = $conn->prepare("UPDATE user SET $field = ? WHERE username = ?");
    if (!$stmt) {
        echo "error: prepare failed - " . $conn->error;
        exit;
    }
    $stmt->bind_param("ss", $value, $username);
    if (!$stmt->execute()) {
        echo "error: execute failed - " . $stmt->error;
        exit;
    }
    echo $stmt->affected_rows > 0 ? "success" : "nochange";
}
?>