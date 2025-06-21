<?php
ob_start();
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$userType = $_POST['user_type'] ?? '';

if (empty($username) || empty($password) || empty($userType)) {
    header('Location: ../main/login.html?error=missing_fields');
    exit;
}

// Determine table and columns based on user type
$table = ($userType === 'A') ? 'recruiter' : 'user';
$idColumn = ($userType === 'A') ? 'recid' : 'userid';
$columns = ($userType === 'A') ? "`$idColumn`, `password`, `compid`" : "`$idColumn`, `password`";

// Prepare and execute the query
$stmt = $conn->prepare("SELECT $columns FROM `$table` WHERE `username` = ?");
if (!$stmt) {
    // Log error: $conn->error
    header('Location: ../main/login.html?error=server_error');
    exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../main/login.html?error=no_user');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify the password
if (!password_verify($password, $user['password'])) {
    header('Location: ../main/login.html?error=bad_credentials');
    exit;
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Set session variables
$_SESSION['username'] = $username;
$_SESSION['user_type'] = $userType;
$_SESSION['userid'] = $user[$idColumn]; // Use the dynamic ID column name

// Redirect based on user type
if ($userType === 'A') {
    $_SESSION['recid'] = $user[$idColumn];
    $_SESSION['compid'] = $user['compid'];
    header("Location: ../main/recruiter.php");
} else {
    header("Location: ../main/job-list.php");
}
exit;