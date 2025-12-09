-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Dec 09, 2025 at 03:21 AM
-- Server version: 10.6.22-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40633514_mms`
--

-- --------------------------------------------------------

--
-- Table structure for table `accomplishment_entries`
--

CREATE TABLE `accomplishment_entries` (
  `id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `accomplished` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `user_id`, `request_id`, `details`, `created_at`) VALUES
(1, 'Status Updated', 3, 1, 'New status: Completed', '2025-10-12 14:35:42'),
(2, 'Status Updated', 3, 4, 'New status: Completed', '2025-10-13 06:35:58'),
(3, 'Status Updated', 3, 1, 'New status: In Progress', '2025-10-13 07:13:55'),
(4, 'Status Updated', 3, 7, 'New status: Rejected', '2025-10-17 04:52:46'),
(5, 'Status Updated', 3, 7, 'New status: In Progress', '2025-10-17 23:30:56'),
(6, 'Status Updated', 3, 8, 'New status: Completed', '2025-10-19 06:11:27'),
(7, 'Status Updated', 3, 9, 'New status: Submitted', '2025-12-09 06:31:52'),
(8, 'Status Updated', 3, 9, 'New status: Rejected', '2025-12-09 06:56:30'),
(9, 'Emergency Flag Updated', 3, 9, 'Emergency set to 1', '2025-12-09 07:05:31'),
(10, 'Cost Updated', 3, 9, 'Labor: ₱100,000.00, Total: ₱10,000.00', '2025-12-09 07:07:23'),
(11, 'Status Updated', 3, 7, 'New status: Completed', '2025-12-09 07:10:18'),
(12, 'Cost Updated', 3, 7, 'Labor: ₱1,000.00, Total: ₱100,000.00', '2025-12-09 07:10:18'),
(13, 'Emergency Flag Updated', 3, 7, 'Emergency set to 1', '2025-12-09 07:10:18'),
(14, 'Cost Updated', 3, 1, 'Labor: ₱0.00, Total: ₱0.00', '2025-12-09 07:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(50) NOT NULL,
  `floors` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `request_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 2, 3, 'ksfjlksfj', '2025-10-13 07:21:27'),
(2, 7, 3, 'Mapurot', '2025-10-17 04:52:46'),
(3, 7, 3, 'dfsdfsdfs', '2025-12-09 07:10:18');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `system_type` enum('Electrical','Plumbing','Sound') NOT NULL,
  `status` varchar(30) DEFAULT NULL,
  `department` varchar(128) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `electrical_utilities` varchar(255) DEFAULT NULL,
  `electrical_other` varchar(128) DEFAULT NULL,
  `plumbing_utilities` varchar(255) DEFAULT NULL,
  `plumbing_other` varchar(128) DEFAULT NULL,
  `emergency_flag` tinyint(1) NOT NULL DEFAULT 0,
  `labor_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `user_id`, `title`, `description`, `system_type`, `status`, `department`, `created_at`, `updated_at`, `electrical_utilities`, `electrical_other`, `plumbing_utilities`, `plumbing_other`, `emergency_flag`, `labor_amount`, `total_cost`) VALUES
(1, 4, 'sdfsd', 'sdfsf', 'Electrical', 'In Progress', NULL, '2025-10-11 15:41:04', '2025-10-13 07:13:55', NULL, NULL, NULL, NULL, 0, '0.00', '0.00'),
(2, 4, 'dfsdf', 'sdfdsf', 'Sound', 'Submitted', NULL, '2025-10-13 05:19:31', '2025-10-13 05:19:31', NULL, NULL, NULL, NULL, 0, '0.00', '0.00'),
(3, 5, 'dfsdfsf', 'jhj', 'Plumbing', 'Rejected', NULL, '2025-10-13 06:05:49', '2025-10-13 07:10:29', NULL, NULL, NULL, NULL, 0, '0.00', '0.00'),
(7, 4, 'h', 'hhjhj', 'Electrical', 'Completed', NULL, '2025-10-15 15:14:51', '2025-12-09 07:10:18', NULL, NULL, NULL, NULL, 1, '1000.00', '100000.00'),
(9, 4, 'df', 'fsdf', 'Electrical', 'Rejected', NULL, '2025-10-22 08:31:16', '2025-12-09 07:07:23', NULL, NULL, NULL, NULL, 1, '100000.00', '10000.00');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_types`
--

CREATE TABLE `maintenance_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_files`
--

CREATE TABLE `request_files` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_path` varchar(256) NOT NULL,
  `original_name` varchar(128) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_files`
--

INSERT INTO `request_files` (`id`, `request_id`, `file_path`, `original_name`, `uploaded_at`) VALUES
(1, 1, 'uploads/68ea182043012.png', 'Screenshot (69).png', '2025-10-11 15:41:04'),
(2, 2, 'uploads/68ec297305167.jpg', 'qwer.jpg', '2025-10-13 05:19:31'),
(3, 3, 'uploads/68ec344d02d22.jpg', 'singapore.jpg', '2025-10-13 06:05:49'),
(5, 7, 'uploads/68f271880722b.png', 'bbb.png', '2025-10-17 23:40:40'),
(6, 1, 'uploads/6937cb7c673b6.png', 'bbb.png', '2025-12-09 07:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `request_workers`
--

CREATE TABLE `request_workers` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_workers`
--

INSERT INTO `request_workers` (`id`, `request_id`, `worker_id`) VALUES
(3, 3, 5),
(4, 2, 4),
(10, 9, 1),
(11, 9, 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(128) NOT NULL,
  `role` enum('admin','user') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`) VALUES
(2, 'brix', '$2y$10$eA5kYq7/4y4NX13b05V0hOZvx7sElcWkf9HbvrrgH6dyRqVnSPkUG', 'brix@gmail.com', 'user'),
(3, 'admin', '$2y$12$jaB74.hJ1eus/DV7n3AVPO3KaGnelpWLl2FongRxK2ZE24I1FSJwO', 'admin@gmail.com', 'admin'),
(4, 'user', '$2y$10$SuB9m5k/9AyCIEk.yQIwdOJI5.h0OQLLZNk50MX.xBn2h2WDcCvT2', 'user@gmail.com', 'user'),
(5, 'lorenz', '$2y$10$Yess8QyQfpmNXkTts3wIzuNK9qho0oq3aRWjGmGW6uJAyXZyF.A86', 'lorenzjoshuaensong@gmail.com', 'user'),
(7, 'ping', '$2y$10$BcT9Yykj.p8/ojoCCWX3H.X0oPijfa47O74eoQGUlfYjxiIxSG2i2', 'abenalespingxiao@gmail.com', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `name`, `position`) VALUES
(1, 'Alice Johnson', 'Electrician'),
(2, 'Bob Smith', 'Plumber'),
(3, 'Carlos Diaz', 'HVAC Technician'),
(4, 'Dana Lee', 'Carpenter'),
(5, 'Ella Brown', 'Cleaning Staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accomplishment_entries`
--
ALTER TABLE `accomplishment_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `building_id` (`building_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `maintenance_types`
--
ALTER TABLE `maintenance_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_files`
--
ALTER TABLE `request_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `request_workers`
--
ALTER TABLE `request_workers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accomplishment_entries`
--
ALTER TABLE `accomplishment_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `maintenance_types`
--
ALTER TABLE `maintenance_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_files`
--
ALTER TABLE `request_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `request_workers`
--
ALTER TABLE `request_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accomplishment_entries`
--
ALTER TABLE `accomplishment_entries`
  ADD CONSTRAINT `accomplishment_entries_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accomplishment_entries_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `maintenance_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_files`
--
ALTER TABLE `request_files`
  ADD CONSTRAINT `request_files_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`);

--
-- Constraints for table `request_workers`
--
ALTER TABLE `request_workers`
  ADD CONSTRAINT `request_workers_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_workers_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
