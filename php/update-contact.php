<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    echo "unauthorized";
    exit;
}

$userid = $_SESSION['userid'];
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

// Validate field
$allowedFields = ['email', 'phone', 'location', 'website'];
if (!in_array($field, $allowedFields)) {
    echo "invalid_field";
    exit;
}

// Validate email if field is email
if ($field === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
    echo "invalid_email";
    exit;
}

// Validate website if field is website
if ($field === 'website' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
    echo "invalid_website";
    exit;
}

// Update the field
$sql = "UPDATE user SET $field = ? WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $value, $userid);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "failed";
}

$stmt->close();
$conn->close();
?>