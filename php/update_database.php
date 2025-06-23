<?php
session_start();
require_once 'db.php';

// This script adds missing columns to the database
// Run this once to update your database structure

echo "<h2>Database Update Script</h2>";

try {
    // Add location column to user table
    $sql = "ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `location` VARCHAR(255) DEFAULT NULL AFTER `phone`";
    if ($conn->query($sql)) {
        echo "<p>✓ Location column added to user table (or already exists)</p>";
    } else {
        echo "<p>✗ Error adding location column: " . $conn->error . "</p>";
    }
    
    // Add status column to applied table
    $sql = "ALTER TABLE `applied` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'Applied' AFTER `answers`";
    if ($conn->query($sql)) {
        echo "<p>✓ Status column added to applied table (or already exists)</p>";
    } else {
        echo "<p>✗ Error adding status column: " . $conn->error . "</p>";
    }
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `userid` int(11) NOT NULL,
        `message` text NOT NULL,
        `link` varchar(255) DEFAULT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `userid` (`userid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql)) {
        echo "<p>✓ Notifications table created (or already exists)</p>";
    } else {
        echo "<p>✗ Error creating notifications table: " . $conn->error . "</p>";
    }
    
    // Update existing applications to have 'Applied' status
    $sql = "UPDATE `applied` SET `status` = 'Applied' WHERE `status` IS NULL";
    if ($conn->query($sql)) {
        echo "<p>✓ Updated existing applications with default status</p>";
    } else {
        echo "<p>✗ Error updating applications: " . $conn->error . "</p>";
    }

    // Add the resume column to the user table if it doesn't exist, for migration purposes
    $sql = "ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `resume` VARCHAR(255) DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p>✓ Resume column prepared for migration.</p>";
    } else {
        echo "<p>✗ Error preparing resume column for migration: " . $conn->error . "</p>";
    }

    // Create user_resumes table for multiple resume support
    $sql = "CREATE TABLE IF NOT EXISTS `user_resumes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_filename` VARCHAR(255) NOT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `user`(`userid`) ON DELETE CASCADE
    )";
    if ($conn->query($sql)) {
        echo "<p>✓ `user_resumes` table created successfully (or already exists).</p>";
    } else {
        echo "<p>✗ Error creating `user_resumes` table: " . $conn->error . "</p>";
    }

    // Migrate existing resumes from user table to user_resumes
    $sql = "SELECT userid, resume FROM user WHERE resume IS NOT NULL AND resume != ''";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo "<p>Migrating existing resumes...</p>";
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM user_resumes WHERE user_id = ? AND original_filename = ?");
        $insertStmt = $conn->prepare("INSERT INTO user_resumes (user_id, filename, original_filename) VALUES (?, ?, ?)");

        while ($row = $result->fetch_assoc()) {
            $userId = $row['userid'];
            $resumeFile = $row['resume'];

            // Check if this resume has already been migrated
            $checkStmt->bind_param("is", $userId, $resumeFile);
            $checkStmt->execute();
            $countResult = $checkStmt->get_result()->fetch_row();

            if ($countResult[0] == 0) {
                 // Insert into the new table
                $insertStmt->bind_param("iss", $userId, $resumeFile, $resumeFile);
                if ($insertStmt->execute()) {
                    echo "<p>✓ Migrated resume '$resumeFile' for user ID: $userId</p>";
                } else {
                    echo "<p>✗ Failed to migrate resume for user ID: $userId</p>";
                }
            } else {
                 echo "<p>✓ Resume '$resumeFile' for user ID: $userId already migrated.</p>";
            }
        }
        $checkStmt->close();
        $insertStmt->close();
    }

    // Drop the old resume column from the user table
    $sql = "ALTER TABLE `user` DROP COLUMN IF EXISTS `resume`";
    if ($conn->query($sql)) {
        echo "<p>✓ Old 'resume' column removed from 'user' table.</p>";
    } else {
        echo "<p>✗ Error removing 'resume' column: " . $conn->error . "</p>";
    }

    echo "<h3>Database update completed!</h3>";
    echo "<p><a href='/main/profile.php'>Go to Profile Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 