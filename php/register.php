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
                // On successful registration, log the user in automatically
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = $user_type;

                if ($user_type == 'A') { // Recruiter
                    // We need to get the new recruiter's ID and compid
                    $recid = $stmt->insert_id;
                    $_SESSION['userid'] = $recid;
                    // Note: This registration flow doesn't create a company. 
                    // This needs to be handled separately. For now, we'll redirect to a settings page.
                    header("Location: /main/edit-company.php");
                } else { // Job Seeker
                    $_SESSION['userid'] = $stmt->insert_id;
                    header("Location: /main/job-list.php");
                }
                exit();
            } else {
                // Redirect back to login with an error
                header("Location: /main/login.php?error=registration_failed");
                exit();
            }
            $stmt->close();
        } else {
            header("Location: /main/login.php?error=server_error");
            exit();
        }
    } else {
        header("Location: /main/login.php?error=password_mismatch");
        exit();
    }
}
?>

