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

        if ($user_type == 'A') { // Recruiter registration
            // Start transaction to ensure data consistency
            $conn->begin_transaction();
            
            try {
                // First, create a default company record
                $default_company_name = $username . "'s Company";
                $default_location = "Location to be updated";
                $default_contact = "0000000000";
                
                $company_query = "INSERT INTO company (name, location, contact) VALUES (?, ?, ?)";
                $company_stmt = $conn->prepare($company_query);
                $company_stmt->bind_param("sss", $default_company_name, $default_location, $default_contact);
                $company_stmt->execute();
                $compid = $conn->insert_id;
                $company_stmt->close();
                
                // Then, create the recruiter record with the company ID
                $recruiter_query = "INSERT INTO recruiter (username, email, password, compid) VALUES (?, ?, ?, ?)";
                $recruiter_stmt = $conn->prepare($recruiter_query);
                $recruiter_stmt->bind_param("sssi", $username, $email, $hashed_password, $compid);
                $recruiter_stmt->execute();
                $recid = $conn->insert_id;
                $recruiter_stmt->close();
                
                // Commit the transaction
                $conn->commit();
                
                // On successful registration, log the user in automatically
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['userid'] = $recid;
                $_SESSION['compid'] = $compid;
                $_SESSION['recid'] = $recid;
                
                // Redirect to edit company page to complete setup
                header("Location: /main/edit-company.php");
                exit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo "[DEBUG] Error: " . $e->getMessage() . "\n";
                header("Location: /main/login.php?error=registration_failed");
                exit();
            }
            
        } else { // Job Seeker registration
            // Prepare SQL query for job seeker
            $query = "INSERT INTO user (username, email, password) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                // Execute the query and check for success
                if ($stmt->execute()) {
                    // On successful registration, log the user in automatically
                    session_start();
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['userid'] = $stmt->insert_id;
                    
                    header("Location: /main/job-list.php");
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
        }
    } else {
        header("Location: /main/login.php?error=password_mismatch");
        exit();
    }
}
?>

