<?php
include("db.php"); // Corrected include for db.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    
    // Debugging the POST data
    echo "[DEBUG] Login attempt: username=$username, userType=$user_type\n";

    // Choose the table based on user type
    $table = ($user_type == 'A') ? 'recruiter' : 'user';

    // Debugging table choice
    echo "[DEBUG] Checking credentials in table: $table\n";

    // Prepare SQL query to fetch user data
    $query = "SELECT password FROM $table WHERE username = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username);
        
        // Execute the query and check for result
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($hashed_password);
                $stmt->fetch();

                // Debugging the fetched hashed password
                echo "[DEBUG] Fetched hashed password: $hashed_password\n";

                // Verify password
                if (password_verify($password, $hashed_password)) {
                    echo "[DEBUG] Password matched\n";
                    // Start session or other login success actions
                    header("Location: /main/dashboard.php"); // Redirect to dashboard on success
                    exit();
                } else {
                    echo "[DEBUG] Password does not match for user: $username\n";
                    header("Location: /main/login.html?error=Bad+credentials");
                    exit();
                }
            } else {
                echo "[DEBUG] No user found with username: $username\n";
                header("Location: /main/login.html?error=Bad+credentials");
                exit();
            }
        } else {
            echo "[DEBUG] Error executing query: " . $stmt->error . "\n";
            header("Location: /main/login.html?error=Bad+credentials");
            exit();
        }
        $stmt->close();
    } else {
        echo "[DEBUG] Prepare statement failed: " . $conn->error . "\n";
        header("Location: /main/login.html?error=Bad+credentials");
        exit();
    }
}
?>