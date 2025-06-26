-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2025 at 07:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jobportal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminid`, `username`, `password`, `email`, `created_at`) VALUES
(2, 'admin', '$2y$12$CqIt.Df974fDqXXNnUgIl.rGFjA/qU1hW3nQXkoVCFf8eiCAtXquu', 'admin@jobportal.com', '2025-06-24 22:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `applied`
--

CREATE TABLE `applied` (
  `S. No` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `cover_letter_file` varchar(255) DEFAULT NULL,
  `resume_file` varchar(255) DEFAULT NULL,
  `answers` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applied`
--

INSERT INTO `applied` (`S. No`, `userid`, `jobid`, `status`, `applied_at`, `cover_letter_file`, `resume_file`, `answers`) VALUES
(1, 5, 11, 'Pending', '2025-06-17 04:24:54', NULL, NULL, NULL),
(13, 8, 11, 'Pending', '2025-06-24 15:31:54', 'cover_685b275aa2eb0.pdf', 'resume_8_1750742913.pdf', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `compid` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact` bigint(10) NOT NULL,
  `about` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `suspended` tinyint(1) DEFAULT 0,
  `industry` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`compid`, `name`, `location`, `contact`, `about`, `logo`, `website`, `suspended`, `industry`, `phone`) VALUES
(1, 'hello', 'Vancouver, Washington, United States of America', 5644440700, 'ssss', NULL, 'www.example.com', 0, NULL, NULL),
(101, 'Newton School', 'Bangalore', 7004395760, NULL, NULL, NULL, 0, NULL, NULL),
(102, 'Union FBLA Team', 'Hello, World', 5644440700, 'www', NULL, 'www.example.com', 0, NULL, NULL),
(103, 'Union FBla Team', 'Vancouver, Washington, United States of America', 5644440700, 'ge', NULL, 'www.example.com', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job-post`
--

CREATE TABLE `job-post` (
  `jobid` int(11) NOT NULL,
  `recid` int(11) NOT NULL,
  `compid` int(11) NOT NULL,
  `designation` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `salary` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `spots` int(11) NOT NULL DEFAULT 1,
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`questions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job-post`
--

INSERT INTO `job-post` (`jobid`, `recid`, `compid`, `designation`, `location`, `salary`, `description`, `status`, `created_at`, `spots`, `questions`) VALUES
(11, 9, 102, 'Software Engineer', 'union', 2, '2', 'Active', '2025-06-15 22:44:51', 1, NULL),
(13, 9, 102, 'Software Engineer', 'union', 50000, 'Develop and maintain software.', 'Rejected', '2025-06-24 22:43:47', 1, NULL),
(14, 9, 102, 'Software Engineer', 'union', 50000, 'Develop and maintain software.', 'Pending', '2025-06-24 22:43:54', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruiter`
--

CREATE TABLE `recruiter` (
  `recid` int(4) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(20) NOT NULL,
  `compid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruiter`
--

INSERT INTO `recruiter` (`recid`, `username`, `password`, `email`, `compid`) VALUES
(9, 'helloworld', '$2y$12$qWYDkOMBaTPsq/m6rIMObOHkCJueDoekQGOMzhk1sxNakWb.w0pAW', 'helloworld@gmail.com', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userid` int(4) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `user_type` varchar(10) NOT NULL DEFAULT 'J',
  `suspended` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userid`, `username`, `password`, `email`, `profile_picture`, `about`, `education`, `experience`, `website`, `phone`, `location`, `resume`, `user_type`, `suspended`, `last_login`) VALUES
(5, 'goodbyeworld', '$2y$12$3KexXSQYhmrr4URIH4.Ocu4fRQ1OERys3diyB5NmcTAzukn7Y8Rni', 'goodbyeworld@gmail.com', '../uploads/profile_pictures/chart.png', 'hello we are seeking jobs', 'union high school', NULL, NULL, NULL, NULL, NULL, 'J', 0, NULL),
(8, 'seeyouagain', '$2y$12$7YzokUtZAD6kZTnw9Pp9AO5xchkHPGbRAM50apt8EznN5KPxzaUpm', 'xoxomct@gmail.com', NULL, '', '', '', '', '1234567890', 'Vancouver, Washington, USA', 'resume_8_1750660641.pdf', 'J', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_resumes`
--

CREATE TABLE `user_resumes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_resumes`
--

INSERT INTO `user_resumes` (`id`, `user_id`, `filename`, `original_filename`, `uploaded_at`) VALUES
(2, 8, 'resume_8_1750742913.pdf', 'resume 1', '2025-06-24 05:28:33'),
(3, 8, 'resume_8_1750742924.pdf', 'oeeieeoeeiee', '2025-06-24 05:28:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminid`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `applied`
--
ALTER TABLE `applied`
  ADD PRIMARY KEY (`S. No`),
  ADD KEY `new3` (`userid`),
  ADD KEY `new4` (`jobid`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`compid`);

--
-- Indexes for table `job-post`
--
ALTER TABLE `job-post`
  ADD PRIMARY KEY (`jobid`),
  ADD KEY `new` (`compid`),
  ADD KEY `new2` (`recid`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `recruiter`
--
ALTER TABLE `recruiter`
  ADD PRIMARY KEY (`recid`),
  ADD KEY `fk_recruiter_company` (`compid`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userid`,`username`);

--
-- Indexes for table `user_resumes`
--
ALTER TABLE `user_resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `applied`
--
ALTER TABLE `applied`
  MODIFY `S. No` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `compid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `job-post`
--
ALTER TABLE `job-post`
  MODIFY `jobid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruiter`
--
ALTER TABLE `recruiter`
  MODIFY `recid` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userid` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_resumes`
--
ALTER TABLE `user_resumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applied`
--
ALTER TABLE `applied`
  ADD CONSTRAINT `new3` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`),
  ADD CONSTRAINT `new4` FOREIGN KEY (`jobid`) REFERENCES `job-post` (`jobid`);

--
-- Constraints for table `job-post`
--
ALTER TABLE `job-post`
  ADD CONSTRAINT `new` FOREIGN KEY (`compid`) REFERENCES `company` (`compid`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `recruiter`
--
ALTER TABLE `recruiter`
  ADD CONSTRAINT `fk_recruiter_company` FOREIGN KEY (`compid`) REFERENCES `company` (`compid`);

--
-- Constraints for table `user_resumes`
--
ALTER TABLE `user_resumes`
  ADD CONSTRAINT `user_resumes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`userid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
