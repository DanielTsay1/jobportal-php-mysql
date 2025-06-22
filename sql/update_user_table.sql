-- Add missing columns to user table for profile functionality
-- Run this script to update your database

-- Add location column if it doesn't exist
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `location` VARCHAR(255) DEFAULT NULL AFTER `phone`;

-- Add status column to applied table if it doesn't exist (for application status tracking)
ALTER TABLE `applied` ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'Applied' AFTER `answers`;

-- Add notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing applications to have 'Applied' status if they don't have one
UPDATE `applied` SET `status` = 'Applied' WHERE `status` IS NULL; 