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
    
    echo "<h3>Database update completed!</h3>";
    echo "<p><a href='/main/profile.php'>Go to Profile Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 