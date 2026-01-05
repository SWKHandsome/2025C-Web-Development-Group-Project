-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 07:50 AM
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
-- Database: `web-development`
--

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `found_location` varchar(120) NOT NULL,
  `found_at` datetime NOT NULL,
  `expiry_at` datetime NOT NULL,
  `status` enum('pending','collected') DEFAULT 'pending',
  `photo_path` varchar(255) DEFAULT NULL,
  `storage_location` varchar(80) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `claimed_by` int(11) DEFAULT NULL,
  `claimed_by_name` varchar(120) DEFAULT NULL,
  `claimed_by_student_id` varchar(30) DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`id`, `item_name`, `description`, `found_location`, `found_at`, `expiry_at`, `status`, `photo_path`, `storage_location`, `recorded_by`, `claimed_by`, `claimed_by_name`, `claimed_by_student_id`, `claimed_at`, `created_at`, `updated_at`) VALUES
(1, 'Black Backpack', 'Contains lecture notes and a USB drive.', 'Library Level 2', '2025-12-24 19:50:27', '2026-01-21 19:50:27', 'pending', NULL, 'Cabinet L1', 1, NULL, NULL, NULL, NULL, '2025-12-26 11:50:27', '2025-12-26 11:50:27'),
(2, 'Sports Bottle', 'Blue bottle with sticker.', 'Sports Complex foyer', '2025-12-16 19:50:27', '2026-01-15 19:50:27', 'collected', NULL, 'Shelf S3', 1, NULL, 'Soo Wei Kang', 'B240150B', '2025-12-26 19:53:08', '2025-12-26 11:50:27', '2025-12-26 11:53:08'),
(3, 'Air Bottle', 'Black Colour', 'Carteen', '2026-01-05 13:03:00', '2026-07-05 13:03:00', 'pending', 'uploads/lost-and-found/lost_1767589465_9b56e8.webp', '', 1, NULL, NULL, NULL, NULL, '2026-01-05 05:04:26', '2026-01-05 05:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `recipient_name` varchar(120) NOT NULL,
  `tracking_number` varchar(80) NOT NULL,
  `parcel_code` varchar(40) DEFAULT NULL,
  `courier` enum('Lalamove','Lazada','Shopee','Pos Laju','Other') DEFAULT 'Other',
  `arrival_at` datetime NOT NULL,
  `deadline_at` datetime NOT NULL,
  `status` enum('pending','collected') DEFAULT 'pending',
  `collected_at` datetime DEFAULT NULL,
  `collected_by_name` varchar(120) DEFAULT NULL,
  `collected_by_student_id` varchar(30) DEFAULT NULL,
  `shelf_code` varchar(40) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `recipient_name`, `tracking_number`, `parcel_code`, `courier`, `arrival_at`, `deadline_at`, `status`, `collected_at`, `collected_by_name`, `collected_by_student_id`, `shelf_code`, `notes`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 'Jason Lim', 'LM123MY908', 'PKG-221', 'Lalamove', '2025-12-25 19:50:27', '2026-01-24 19:50:27', 'collected', '2025-12-27 00:30:12', 'Soo Wei Kang', 'B240150B', 'Rack B-10', 'Requires student card verification.', 1, '2025-12-26 11:50:27', '2025-12-26 16:30:12'),
(2, 'Emily Tan', 'SHOPEE77881', 'PKG-310', 'Shopee', '2025-12-22 19:50:27', '2026-01-19 19:50:27', 'pending', NULL, NULL, NULL, 'Locker C-04', 'Fragile electronics.', 1, '2025-12-26 11:50:27', '2025-12-26 11:50:27'),
(3, 'Jason Lim', 'LAZ221144', 'PKG-098', 'Lazada', '2025-11-06 19:50:27', '2025-12-16 19:50:27', 'collected', NULL, NULL, NULL, 'Rack A-02', 'Collected during counter session.', 1, '2025-12-26 11:50:27', '2025-12-26 11:50:27'),
(4, 'Soo Wei Kang', 'LXST182489516MY', 'DGAKUL00-01', 'Lazada', '2025-12-26 23:58:00', '2026-06-26 23:58:00', 'collected', '2025-12-26 23:59:25', 'Soo Wei Kang', 'B240150B', '', '', 1, '2025-12-26 15:59:05', '2025-12-26 15:59:25'),
(5, 'Soo Wei Kang', 'LXST182489587MY', 'DGAKUL00-02', 'Lazada', '2025-12-27 00:03:00', '2026-06-27 00:03:00', 'collected', '2025-12-27 00:03:41', 'Soo Wei Kang', 'B240150B', '', '', 1, '2025-12-26 16:03:12', '2025-12-26 16:03:41'),
(6, 'Soo Wei Kang', 'LXST182489520MY', 'DGAKUL00-03', 'Lazada', '2025-12-27 00:30:00', '2026-06-27 00:30:00', 'collected', '2025-12-27 00:31:01', 'Soo Wei Kang', 'B240150B', '', '', 1, '2025-12-26 16:30:51', '2025-12-26 16:31:01'),
(7, 'Calvin Teo Yi Jie', 'LXST182489550MY', 'DGAKUL00-04', 'Lazada', '2025-12-30 08:07:00', '2026-06-30 08:07:00', 'pending', NULL, NULL, NULL, '', '', 1, '2025-12-30 00:07:56', '2025-12-30 00:07:56'),
(8, 'abc', '123', '', 'Lazada', '2026-01-05 12:09:00', '2026-07-05 12:09:00', 'pending', NULL, NULL, NULL, '', '', 1, '2026-01-05 04:17:55', '2026-01-05 04:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('student','admin') NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `staff_id` varchar(30) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `faculty` varchar(80) DEFAULT NULL,
  `office` varchar(80) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `student_id`, `staff_id`, `full_name`, `email`, `phone`, `faculty`, `office`, `password_hash`, `last_login`, `avatar_path`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, 'ADM001', 'Qing Chong Yang', 'admin001@sc.edu.my', '03-88775566', 'Logistics Office', 'Parcel Hub', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-05 13:02:38', NULL, '2025-12-26 11:50:27', '2026-01-05 05:02:38'),
(2, 'student', 'B240150B', NULL, 'Soo Wei Kang', 'b240150b@sc.edu.my', '012-5558888', 'School of Business', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-05 13:01:55', NULL, '2025-12-26 11:50:27', '2026-01-05 05:01:55'),
(3, 'student', 'S7654321', NULL, 'Emily Tan', 'b240111b@sc.edu.my', '011-2233445', 'Faculty of Computing', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-01-05 13:02:20', NULL, '2025-12-26 11:50:27', '2026-01-05 05:02:20'),
(4, 'student', NULL, NULL, 'Soo Wei Kang', 'ooeiang@recipients.local', NULL, NULL, NULL, '$2y$10$kMAOoG/VlXgnQjpGWMfAau//8r0xWE2FgDv7lXD3yiKrvMslMpClq', NULL, NULL, '2025-12-26 15:59:05', '2025-12-26 15:59:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lost_recorded` (`recorded_by`),
  ADD KEY `fk_lost_claimed` (`claimed_by`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_packages_recorder` (`recorded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD CONSTRAINT `fk_lost_claimed` FOREIGN KEY (`claimed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lost_recorded` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `fk_packages_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
