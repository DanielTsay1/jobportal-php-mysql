<?php
include("db.php"); // Corrected include for db.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    
    // Debugging the POST data
    echo "[DEBUG] Username: $username, Email: $email, User Type: $user_type\n";

    // Password hashing for storage
    if ($password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Choose the table based on user type
        $table = ($user_type == 'A') ? 'recruiter' : 'user';

        // Debugging table choice
        echo "[DEBUG] Using table: $table\n";

        // Prepare SQL query based on user type
        $query = "INSERT INTO $table (username, email, password) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            // Execute the query and check for success
            if ($stmt->execute()) {
                echo "[DEBUG] Registration successful\n";
                header("Location: /main/login.html"); // Redirect to login page after success
                exit();
            } else {
                echo "[DEBUG] Error executing query: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "[DEBUG] Prepare statement failed: " . $conn->error . "\n";
        }
    } else {
        echo "[DEBUG] Passwords do not match\n";
    }
}
?>

