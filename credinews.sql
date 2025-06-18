-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 03:33 PM
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
-- Database: `credinews`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','verified','fake','rejected') DEFAULT 'pending',
  `encrypted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `user_id`, `title`, `content`, `source`, `publication_date`, `submission_date`, `status`, `encrypted`) VALUES
(1, 3, 'news', 'N3Vvb210aEZMS0tYT24xQ0t6dWQvcFpDS3JJZzlaM3VTRGQ5eko0VFhLOD06Om0yWlVvRml5NGcvVVlyZmxUTFhxQVE9PQ==::1750242276', 'abs-cbn', '2025-06-18', '2025-06-18 10:24:36', 'pending', 1);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `timestamp`, `ip_address`) VALUES
(3, NULL, 'User registered', NULL, NULL, '2025-06-18 10:09:24', '::1'),
(4, 3, 'User registered', NULL, NULL, '2025-06-18 10:12:36', '::1'),
(5, 3, 'Email verified', NULL, NULL, '2025-06-18 10:12:52', '::1'),
(6, 3, 'User logged in', NULL, NULL, '2025-06-18 10:13:34', '::1'),
(7, 3, 'News verification', 'verification', NULL, '2025-06-18 10:14:55', '::1'),
(8, 3, 'News verification', 'verification', NULL, '2025-06-18 10:17:08', '::1'),
(9, 3, 'User logged out', NULL, NULL, '2025-06-18 10:21:44', '::1'),
(10, 3, 'User logged in', NULL, NULL, '2025-06-18 10:23:20', '::1'),
(11, 3, 'News verification', 'verification', NULL, '2025-06-18 10:23:32', '::1'),
(12, 3, 'Article submitted', 'article', 1, '2025-06-18 10:24:36', '::1'),
(13, 3, 'Voted agree', 'report', 1, '2025-06-18 10:24:44', '::1'),
(14, 3, 'Vote updated to disagree', 'report', 1, '2025-06-18 10:24:45', '::1'),
(15, 3, 'Vote updated to agree', 'report', 1, '2025-06-18 10:24:46', '::1'),
(16, 3, 'Vote removed', 'report', 1, '2025-06-18 10:24:47', '::1'),
(17, 3, 'Voted agree', 'report', 1, '2025-06-18 10:24:47', '::1'),
(18, 3, 'Vote removed', 'report', 1, '2025-06-18 10:24:47', '::1'),
(19, 3, 'Voted agree', 'report', 1, '2025-06-18 10:24:47', '::1'),
(20, 3, 'Vote updated to disagree', 'report', 1, '2025-06-18 10:24:48', '::1'),
(21, 3, 'Vote updated to agree', 'report', 1, '2025-06-18 10:24:49', '::1'),
(22, 3, 'Vote removed', 'report', 1, '2025-06-18 10:24:51', '::1'),
(23, 3, 'Voted agree', 'report', 1, '2025-06-18 10:24:51', '::1'),
(24, 3, 'Vote removed', 'report', 1, '2025-06-18 10:24:52', '::1'),
(25, 3, 'Voted agree', 'report', 1, '2025-06-18 10:25:19', '::1'),
(26, 3, 'Vote removed', 'report', 1, '2025-06-18 10:25:19', '::1'),
(27, 3, 'Voted agree', 'report', 1, '2025-06-18 10:25:19', '::1'),
(28, 3, 'User logged out', NULL, NULL, '2025-06-18 10:29:04', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `count` int(11) DEFAULT 1,
  `last_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `credibility_score` decimal(5,2) DEFAULT NULL,
  `analysis_text` text DEFAULT NULL,
  `digital_signature` varchar(255) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewer_id` int(11) DEFAULT NULL,
  `review_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `review_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `article_id`, `credibility_score`, `analysis_text`, `digital_signature`, `generated_at`, `reviewer_id`, `review_status`, `review_date`) VALUES
(1, 1, 50.00, 'Automated AI Analysis:\n\nThis article has mixed credibility indicators. While some information appears factual, there are elements that raise concerns. Readers should verify key claims from additional trusted sources.', '6ba1b04510864b0a1de0d5ecab23bf1d95a98218ff9b1755731f44fc7b03cd7a', '2025-06-18 10:24:36', NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_votes`
--

CREATE TABLE `report_votes` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('agree','disagree') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_votes`
--

INSERT INTO `report_votes` (`id`, `report_id`, `user_id`, `vote_type`, `created_at`) VALUES
(6, 1, 3, 'agree', '2025-06-18 10:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `submission_activity`
--

CREATE TABLE `submission_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_url` varchar(255) NOT NULL,
  `submission_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_activity`
--

INSERT INTO `submission_activity` (`id`, `user_id`, `submission_url`, `submission_timestamp`) VALUES
(1, 3, 'https://abs-cbn', '2025-06-18 10:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','reviewer') NOT NULL DEFAULT 'user',
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `submission_count` int(11) DEFAULT 0,
  `last_submission` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `verification_token`, `is_verified`, `created_at`, `last_login`, `submission_count`, `last_submission`) VALUES
(1, 'admin', 'admin@credinews.com', '$2y$10$8KOO.VVOXWE8iBXBCRZLz.0ZQCiCGCnw1.w0Tgq5kDaQQxtWnHUXO', 'admin', NULL, 1, '2025-06-18 10:08:48', NULL, 0, NULL),
(3, 'mhacemojica', 'mhacemojica04@gmail.com', '$2y$10$WsqXuy1wUn6WwMvXA/72EeqAG5QdHzhG7JVtGSr93.Ma4HaCLVNgu', 'user', NULL, 1, '2025-06-18 10:12:30', '2025-06-18 10:23:20', 1, '2025-06-18 10:24:36'),
(4, 'Monitor Admin', 'monitor@credinews.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'admin', NULL, 1, '2025-06-18 12:48:53', NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `report_votes`
--
ALTER TABLE `report_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `submission_activity`
--
ALTER TABLE `submission_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_votes`
--
ALTER TABLE `report_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `submission_activity`
--
ALTER TABLE `submission_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD CONSTRAINT `rate_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `report_votes`
--
ALTER TABLE `report_votes`
  ADD CONSTRAINT `report_votes_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_activity`
--
ALTER TABLE `submission_activity`
  ADD CONSTRAINT `submission_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
