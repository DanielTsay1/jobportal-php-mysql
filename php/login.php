<?php
ob_start();
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../main/login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: ../main/login.php?error=missing_fields');
    exit;
}

// First, check if user exists in recruiter table
$recruiterStmt = $conn->prepare("SELECT recid, password, compid FROM recruiter WHERE username = ?");
$recruiterStmt->bind_param('s', $username);
$recruiterStmt->execute();
$recruiterResult = $recruiterStmt->get_result();

if ($recruiterResult->num_rows > 0) {
    // User found in recruiter table
    $user = $recruiterResult->fetch_assoc();
    $recruiterStmt->close();
    
    // Verify the password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session variables for recruiter
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = 'A';
        $_SESSION['userid'] = $user['recid'];
        $_SESSION['recid'] = $user['recid'];
        $_SESSION['compid'] = $user['compid'];
        
        header("Location: ../main/recruiter.php");
        exit;
    } else {
        header('Location: ../main/login.php?error=bad_credentials');
        exit;
    }
}

$recruiterStmt->close();

// If not found in recruiter table, check user table
$userStmt = $conn->prepare("SELECT userid, password FROM user WHERE username = ?");
$userStmt->bind_param('s', $username);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    // User found in user table
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    // Verify the password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session variables for job seeker
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = 'B';
        $_SESSION['userid'] = $user['userid'];
        // Update last_login
        $updateStmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE userid = ?");
        $updateStmt->bind_param('i', $user['userid']);
        $updateStmt->execute();
        $updateStmt->close();
        
        header("Location: ../main/job-list.php");
        exit;
    } else {
        header('Location: ../main/login.php?error=bad_credentials');
        exit;
    }
}

$userStmt->close();

// If we get here, no user was found
header('Location: ../main/login.php?error=no_user');
exit;