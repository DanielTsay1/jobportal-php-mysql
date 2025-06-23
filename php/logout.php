<?php
session_start();

// Destroy the session only if the user intended to log out
if (isset($_SESSION['username'])) {
    session_destroy();
}

// Redirect to the login page regardless
header('Location: /main/login.php');
exit;
?>