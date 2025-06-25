<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/admin-login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Debug mode: set to true to see debug output
$DEBUG = true;

if ($DEBUG) {
    header('Content-Type: text/plain');
    echo "[DEBUG] Username: '$username'\n";
    echo "[DEBUG] Password: '$password'\n";
}

if (empty($username) || empty($password)) {
    if ($DEBUG) {
        echo "[DEBUG] Missing fields\n";
        exit;
    }
    header('Location: ../main/admin-login.php?error=missing_fields');
    exit;
}

// Check if admin exists
$stmt = $conn->prepare("SELECT adminid, username, password FROM admin WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if ($DEBUG) {
        echo "[DEBUG] No such user\n";
        exit;
    }
    header('Location: ../main/admin-login.php?error=no_user');
    exit;
}

$admin = $result->fetch_assoc();
$stmt->close();

if ($DEBUG) {
    echo "[DEBUG] DB Hash: '{$admin['password']}'\n";
    echo "[DEBUG] password_verify: ".(password_verify($password, $admin['password']) ? 'true' : 'false')."\n";
}

// Verify password
if (!password_verify($password, $admin['password'])) {
    if ($DEBUG) {
        echo "[DEBUG] Bad credentials\n";
        exit;
    }
    header('Location: ../main/admin-login.php?error=bad_credentials');
    exit;
}

// Regenerate session ID for security
session_regenerate_id(true);

// Set admin session variables
$_SESSION['is_admin'] = true;
$_SESSION['admin_id'] = $admin['adminid'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['user_type'] = 'admin';

// Redirect to admin dashboard
header('Location: ../main/admin-dashboard.php');
exit; 