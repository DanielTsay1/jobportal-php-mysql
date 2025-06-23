<?php
require_once 'db.php';

echo "<h2>Resume Data Migration Script</h2>";
echo "<p>This script will create a new table for managing multiple resumes and migrate any existing single resumes into it.</p>";

try {
    // Step 1: Create the user_resumes table if it doesn't exist
    $sql_create = "CREATE TABLE IF NOT EXISTS `user_resumes` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `userid` INT(11) NOT NULL,
      `file_name` VARCHAR(255) NOT NULL,
      `display_name` VARCHAR(255) NOT NULL,
      `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `userid` (`userid`),
      CONSTRAINT `fk_resumes_user` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($sql_create)) {
        echo "<p style='color:green;'>✓ `user_resumes` table created successfully or already exists.</p>";
    } else {
        throw new Exception("Error creating `user_resumes` table: " . $conn->error);
    }

    // Step 2: Check if the old 'resume' column exists in the 'user' table
    $result = $conn->query("SHOW COLUMNS FROM `user` LIKE 'resume'");
    $column_exists = ($result && $result->num_rows > 0);

    if ($column_exists) {
        echo "<p>Found 'resume' column in 'user' table. Attempting to migrate data...</p>";

        // Step 3: Migrate data from user.resume to user_resumes
        // We select only those users who have a resume and are not already in the user_resumes table
        // to prevent duplicate entries if the script is run multiple times.
        $sql_migrate = "INSERT INTO user_resumes (userid, file_name, display_name) 
                        SELECT u.userid, u.resume, 'My Default Resume' 
                        FROM user u
                        LEFT JOIN user_resumes ur ON u.userid = ur.userid AND u.resume = ur.file_name
                        WHERE u.resume IS NOT NULL AND u.resume != '' AND ur.id IS NULL";
                        
        if ($conn->query($sql_migrate)) {
            $count = $conn->affected_rows;
            echo "<p style='color:green;'>✓ Successfully migrated {$count} new resume(s).</p>";
            
            // Step 4: Advise on dropping the old column
            echo "<p style='color:orange;'><strong>Action Recommended:</strong> After confirming the migration, you can manually drop the old 'resume' column by running this SQL command in phpMyAdmin:</p>";
            echo "<pre style='background:#eee;padding:10px;border-radius:5px;'>ALTER TABLE `user` DROP COLUMN `resume`;</pre>";
            
        } else {
            echo "<p style='color:red;'>✗ Warning: Could not migrate data. Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✓ 'resume' column not found in 'user' table. No data migration needed.</p>";
    }

    echo "<h3 style='color:blue;'>Migration script finished successfully.</h3>";

} catch (Exception $e) {
    echo "<p style='color:red;'>An error occurred during migration: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 