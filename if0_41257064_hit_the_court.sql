-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql213.infinityfree.com
-- Generation Time: Mar 10, 2026 at 04:40 PM
-- Server version: 11.4.10-MariaDB
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
-- Database: `if0_41257064_hit_the_court`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','staff') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `full_name`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hitthecourt.com', 'System Admin', 'super_admin', 'active', '2026-03-10 11:27:36', '2026-02-19 17:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `court_price` decimal(10,2) NOT NULL,
  `equipment_total` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `expires_at` datetime DEFAULT NULL,
  `booking_status` enum('active','cancelled','completed') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_code`, `user_id`, `court_id`, `slot_id`, `booking_date`, `duration_minutes`, `court_price`, `equipment_total`, `discount_amount`, `total_price`, `payment_status`, `expires_at`, `booking_status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'BK202602199C7651', 2, 12, 57, '2026-02-21', 60, '130.00', '150.00', '0.00', '280.00', 'failed', NULL, 'cancelled', NULL, '2026-02-19 18:54:17', '2026-02-19 20:20:21'),
(4, 'BK20260219D3EA4D', 2, 1, 27, '2026-02-19', 60, '130.00', '30.00', '0.00', '160.00', 'paid', NULL, 'active', NULL, '2026-02-19 20:18:53', '2026-02-19 20:19:29'),
(5, 'BK20260219EE881B', 3, 2, 38, '2026-02-19', 100, '500.00', '0.00', '0.00', '500.00', 'failed', NULL, 'active', NULL, '2026-02-19 20:43:10', '2026-02-19 20:46:21'),
(7, 'BK202602204BE9E6', 3, 15, 34, '2026-02-20', 60, '130.00', '100.00', '0.00', '230.00', 'paid', NULL, 'active', NULL, '2026-02-20 05:49:40', '2026-02-20 05:51:24'),
(8, 'BK20260220770D93', 3, 13, 68, '2026-02-20', 60, '40.00', '15.00', '0.00', '55.00', 'paid', NULL, 'active', NULL, '2026-02-20 05:54:31', '2026-02-20 05:55:46'),
(9, 'BK20260220F6BA89', 4, 8, 34, '2026-02-20', 60, '130.00', '30.00', '0.00', '160.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:04:31', '2026-02-20 06:05:14'),
(10, 'BK202602206037C4', 5, 5, 65, '2026-02-20', 60, '130.00', '50.00', '0.00', '180.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:08:22', '2026-02-20 06:08:54'),
(11, 'BK20260220C27F5B', 5, 12, 65, '2026-02-20', 60, '130.00', '0.00', '0.00', '130.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:11:56', '2026-02-20 06:12:19'),
(12, 'BK20260220FE0F79', 5, 16, 46, '2026-02-20', 120, '200.00', '0.00', '0.00', '200.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:13:19', '2026-02-20 06:14:28'),
(13, 'BK20260220AF30A6', 5, 19, 69, '2026-02-20', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:18:18', '2026-02-20 06:20:34'),
(14, 'BK20260220F686BB', 5, 7, 79, '2026-02-20', 60, '130.00', '0.00', '0.00', '130.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:29:19', '2026-02-20 06:30:09'),
(15, 'BK2026022080930E', 5, 21, 46, '2026-02-20', 120, '200.00', '0.00', '0.00', '200.00', 'paid', NULL, 'active', NULL, '2026-02-20 06:31:20', '2026-02-20 06:32:10'),
(19, 'BK202602233B5899', 5, 6, 69, '2026-02-24', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-23 14:49:55', '2026-02-23 14:51:30'),
(23, 'BK20260223466892', 5, 26, 71, '2026-02-23', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-23 15:26:28', '2026-02-23 15:55:08'),
(24, 'BK20260223793ABE', 5, 26, 72, '2026-02-23', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-23 16:01:59', '2026-02-23 16:10:57'),
(31, 'BK20260224E3F530', 4, 19, 78, '2026-02-25', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-24 16:04:46', '2026-02-24 16:35:11'),
(32, 'BK20260224C75BCD', 4, 9, 39, '2026-02-25', 100, '450.00', '600.00', '50.00', '1050.00', '', '2026-02-24 18:05:04', 'cancelled', NULL, '2026-02-24 16:50:04', '2026-02-24 17:59:06'),
(33, 'BK202602242BA749', 4, 11, 51, '2026-02-25', 120, '135.00', '0.00', '15.00', '135.00', '', '2026-02-24 19:14:30', 'cancelled', NULL, '2026-02-24 17:59:30', '2026-02-24 17:59:38'),
(34, 'BK20260224F8B01F', 4, 17, 51, '2026-02-25', 120, '135.00', '0.00', '15.00', '135.00', '', '2026-02-24 19:16:19', 'cancelled', NULL, '2026-02-24 18:01:19', '2026-02-24 18:01:32'),
(35, 'BK20260224F63012', 4, 16, 45, '2026-02-24', 120, '180.00', '0.00', '20.00', '180.00', '', '2026-02-24 19:17:07', 'cancelled', NULL, '2026-02-24 18:02:07', '2026-02-24 18:02:12'),
(36, 'BK202602250DBC4C', 2, 9, 40, '2026-02-25', 100, '450.00', '300.00', '50.00', '750.00', '', '2026-02-25 07:31:00', 'cancelled', NULL, '2026-02-25 06:16:00', '2026-02-25 06:16:22'),
(37, 'BK2026022591A302', 6, 24, 69, '2026-02-26', 60, '36.00', '0.00', '4.00', '36.00', 'paid', NULL, 'active', NULL, '2026-02-25 06:21:29', '2026-02-25 06:24:52'),
(38, 'BK20260225630CB2', 6, 8, 28, '2026-02-25', 60, '117.00', '0.00', '13.00', '117.00', '', '2026-02-25 08:06:18', 'cancelled', NULL, '2026-02-25 06:51:18', '2026-02-25 07:07:13'),
(39, 'BK20260226549531', 7, 24, 75, '2026-02-26', 60, '40.00', '10.00', '0.00', '50.00', 'paid', NULL, 'active', NULL, '2026-02-26 19:01:57', '2026-02-26 19:03:39'),
(41, 'BK20260226696091', 5, 24, 76, '2026-02-27', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-27 01:51:49', '2026-02-27 01:52:43'),
(44, 'BK20260227FC5149', 8, 19, 68, '2026-02-27', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-02-27 10:27:27', '2026-02-27 10:28:17'),
(53, 'BK20260302326C74', 2, 25, 61, '2026-03-03', 60, '117.00', '0.00', '13.00', '117.00', '', '2026-03-02 13:41:59', 'cancelled', NULL, '2026-03-02 18:26:59', '2026-03-03 06:40:55'),
(54, 'BK20260302D1557C', 2, 27, 63, '2026-03-03', 60, '117.00', '0.00', '13.00', '117.00', 'pending', NULL, 'active', NULL, '2026-03-02 19:44:29', '2026-03-02 19:44:29'),
(55, 'BK20260302ADFF4C', 2, 5, 57, '2026-03-02', 60, '117.00', '0.00', '13.00', '117.00', 'pending', NULL, 'active', NULL, '2026-03-02 19:44:58', '2026-03-02 19:44:58'),
(56, 'BK202603044ABC53', 9, 20, 27, '2026-03-06', 60, '130.00', '10.00', '0.00', '140.00', 'pending', NULL, 'active', NULL, '2026-03-04 09:50:28', '2026-03-04 09:50:28'),
(58, 'BK20260305DF24B4', 9, 21, 46, '2026-03-06', 120, '200.00', '65.00', '0.00', '265.00', 'pending', NULL, 'active', NULL, '2026-03-05 09:57:49', '2026-03-05 09:57:49'),
(59, 'BK202603058A480B', 5, 9, 38, '2026-03-06', 100, '450.00', '300.00', '50.00', '750.00', 'pending', NULL, 'active', NULL, '2026-03-05 10:04:07', '2026-03-05 10:04:07'),
(60, 'BK2026030869CBA0', 7, 6, 68, '2026-03-08', 60, '40.00', '0.00', '0.00', '40.00', 'pending', NULL, 'active', NULL, '2026-03-07 21:22:14', '2026-03-07 21:22:14'),
(61, 'BK2026030910B44D', 2, 24, 77, '2026-03-09', 60, '36.00', '0.00', '4.00', '36.00', 'pending', NULL, 'active', NULL, '2026-03-09 04:34:09', '2026-03-09 04:34:09'),
(62, 'BK202603090E5465', 2, 24, 77, '2026-03-09', 60, '36.00', '60.00', '4.00', '96.00', 'pending', NULL, 'active', NULL, '2026-03-09 04:34:25', '2026-03-09 04:34:25'),
(63, 'BK20260309D246CC', 2, 19, 75, '2026-03-09', 60, '36.00', '0.00', '4.00', '36.00', 'paid', NULL, 'active', NULL, '2026-03-09 04:35:25', '2026-03-09 04:38:53'),
(64, 'BK2026030922900E', 2, 24, 75, '2026-03-09', 60, '40.00', '0.00', '0.00', '40.00', 'pending', NULL, 'active', NULL, '2026-03-09 04:39:30', '2026-03-09 04:39:30'),
(65, 'BK202603090344B6', 2, 26, 76, '2026-03-09', 60, '28.00', '0.00', '12.00', '28.00', 'pending', NULL, 'active', NULL, '2026-03-09 04:45:04', '2026-03-09 04:45:04'),
(66, 'BK202603096C8772', 7, 24, 75, '2026-03-09', 60, '40.00', '0.00', '0.00', '40.00', 'pending', NULL, 'active', NULL, '2026-03-09 06:17:26', '2026-03-09 06:17:26'),
(67, 'BK20260309FDCCA8', 7, 24, 75, '2026-03-09', 60, '40.00', '10.00', '0.00', '50.00', 'paid', NULL, 'active', NULL, '2026-03-09 06:17:35', '2026-03-09 06:19:47'),
(68, 'BK20260309A4AFCE', 7, 26, 75, '2026-03-09', 60, '40.00', '10.00', '0.00', '50.00', 'paid', NULL, 'active', NULL, '2026-03-09 06:20:09', '2026-03-09 06:20:51'),
(69, 'BK20260309FD191A', 5, 21, 50, '2026-03-09', 120, '200.00', '0.00', '0.00', '200.00', 'pending', NULL, 'active', NULL, '2026-03-09 07:42:23', '2026-03-09 07:42:23'),
(70, 'BK20260309BBE6E0', 5, 16, 47, '2026-03-09', 120, '200.00', '0.00', '0.00', '200.00', 'pending', NULL, 'active', NULL, '2026-03-09 07:42:35', '2026-03-09 07:42:35'),
(71, 'BK202603095D3265', 10, 28, 76, '2026-03-09', 60, '36.00', '0.00', '4.00', '36.00', 'pending', NULL, 'active', NULL, '2026-03-09 08:53:09', '2026-03-09 08:53:09'),
(72, 'BK2026030933997C', 10, 28, 76, '2026-03-09', 60, '36.00', '0.00', '4.00', '36.00', 'pending', NULL, 'active', NULL, '2026-03-09 08:53:23', '2026-03-09 08:53:23'),
(73, 'BK20260309491C27', 10, 28, 76, '2026-03-09', 60, '36.00', '30.00', '4.00', '66.00', 'pending', NULL, 'active', NULL, '2026-03-09 08:54:28', '2026-03-09 08:54:28'),
(74, 'BK2026030978B9A5', 10, 28, 76, '2026-03-09', 60, '36.00', '10.00', '4.00', '46.00', 'paid', NULL, 'active', NULL, '2026-03-09 08:54:47', '2026-03-09 08:56:07'),
(75, 'BK20260309CB8E18', 10, 6, 77, '2026-03-09', 60, '40.00', '0.00', '0.00', '40.00', 'pending', NULL, 'active', NULL, '2026-03-09 08:59:56', '2026-03-09 08:59:56'),
(76, 'BK20260309EBAB31', 10, 6, 77, '2026-03-09', 60, '40.00', '0.00', '0.00', '40.00', 'paid', NULL, 'active', NULL, '2026-03-09 09:00:14', '2026-03-09 09:01:50'),
(77, 'BK202603104EC85E', 14, 27, 59, '2026-03-11', 60, '130.00', '100.00', '0.00', '230.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:12:20', '2026-03-09 18:12:20'),
(78, 'BK20260310A44A72', 14, 7, 79, '2026-03-10', 60, '130.00', '40.00', '0.00', '170.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:13:45', '2026-03-09 18:13:45'),
(79, 'BK202603106B43C3', 14, 9, 39, '2026-03-10', 100, '500.00', '360.00', '0.00', '860.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:17:42', '2026-03-09 18:17:42'),
(80, 'BK2026031051C754', 16, 21, 45, '2026-03-10', 120, '200.00', '175.00', '0.00', '375.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:20:52', '2026-03-09 18:20:52'),
(81, 'BK202603106AFE0B', 16, 21, 45, '2026-03-10', 120, '200.00', '175.00', '0.00', '375.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:20:54', '2026-03-09 18:20:54'),
(82, 'BK202603108A829B', 16, 21, 45, '2026-03-10', 120, '200.00', '175.00', '0.00', '375.00', 'pending', NULL, 'active', NULL, '2026-03-09 18:20:56', '2026-03-09 18:20:56'),
(84, 'BK20260310F71245', 7, 9, 44, '2026-03-10', 100, '500.00', '100.00', '0.00', '600.00', 'pending', NULL, 'active', NULL, '2026-03-10 08:34:23', '2026-03-10 08:34:23'),
(85, 'BK20260310FDE1A0', 7, 15, 33, '2026-03-11', 60, '130.00', '0.00', '0.00', '130.00', 'pending', NULL, 'active', NULL, '2026-03-10 13:05:52', '2026-03-10 13:05:52'),
(86, 'BK20260310B314FB', 7, 9, 39, '2026-03-10', 100, '500.00', '0.00', '0.00', '500.00', 'pending', NULL, 'active', NULL, '2026-03-10 13:06:19', '2026-03-10 13:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `booking_equipment`
--

CREATE TABLE `booking_equipment` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `eq_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `booking_equipment`
--

INSERT INTO `booking_equipment` (`id`, `booking_id`, `eq_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(3, 3, 9, 3, '50.00', '150.00'),
(4, 4, 1, 3, '10.00', '30.00'),
(6, 7, 1, 10, '10.00', '100.00'),
(7, 8, 10, 1, '10.00', '10.00'),
(8, 8, 11, 1, '5.00', '5.00'),
(9, 9, 1, 3, '10.00', '30.00'),
(10, 10, 9, 1, '50.00', '50.00'),
(19, 32, 5, 2, '300.00', '600.00'),
(20, 36, 2, 1, '50.00', '0.00'),
(21, 36, 3, 1, '30.00', '0.00'),
(22, 36, 4, 1, '20.00', '0.00'),
(23, 36, 5, 1, '300.00', '300.00'),
(24, 38, 1, 1, '10.00', '0.00'),
(25, 39, 10, 1, '10.00', '10.00'),
(27, 41, 10, 1, '10.00', '0.00'),
(33, 55, 9, 1, '50.00', '0.00'),
(34, 56, 1, 1, '10.00', '10.00'),
(37, 58, 6, 1, '50.00', '50.00'),
(38, 58, 7, 1, '15.00', '15.00'),
(39, 59, 2, 1, '50.00', '0.00'),
(40, 59, 3, 1, '30.00', '0.00'),
(41, 59, 4, 1, '20.00', '0.00'),
(42, 59, 5, 1, '300.00', '300.00'),
(43, 61, 10, 2, '10.00', '0.00'),
(44, 61, 11, 2, '5.00', '0.00'),
(45, 62, 10, 7, '10.00', '50.00'),
(46, 62, 11, 7, '5.00', '10.00'),
(47, 67, 10, 1, '10.00', '10.00'),
(48, 68, 10, 1, '10.00', '10.00'),
(49, 70, 6, 1, '50.00', '0.00'),
(50, 70, 7, 1, '15.00', '0.00'),
(51, 72, 10, 1, '10.00', '0.00'),
(52, 73, 10, 5, '10.00', '30.00'),
(53, 74, 10, 3, '10.00', '10.00'),
(54, 76, 10, 2, '10.00', '0.00'),
(55, 77, 9, 2, '50.00', '100.00'),
(56, 78, 12, 1, '40.00', '40.00'),
(57, 79, 3, 2, '30.00', '60.00'),
(58, 79, 5, 1, '300.00', '300.00'),
(59, 80, 6, 2, '50.00', '100.00'),
(60, 80, 7, 5, '15.00', '75.00'),
(61, 81, 6, 2, '50.00', '100.00'),
(62, 81, 7, 5, '15.00', '75.00'),
(63, 82, 6, 2, '50.00', '100.00'),
(64, 82, 7, 5, '15.00', '75.00'),
(66, 84, 2, 2, '50.00', '100.00');

-- --------------------------------------------------------

--
-- Table structure for table `courts`
--

CREATE TABLE `courts` (
  `court_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `court_number` int(11) NOT NULL,
  `court_name` varchar(50) DEFAULT NULL,
  `status` enum('available','maintenance','reserved') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`court_id`, `sport_id`, `court_number`, `court_name`, `status`, `created_at`) VALUES
(1, 1, 1, 'Badminton Court 1', 'available', '2026-02-19 17:15:05'),
(2, 2, 1, 'Football Court 1', 'available', '2026-02-19 17:15:05'),
(3, 3, 1, 'Tennis Court 1', 'available', '2026-02-19 17:15:05'),
(4, 4, 1, 'Volleyball Court 1', 'available', '2026-02-19 17:15:05'),
(5, 5, 1, 'Basketball Court 1', 'available', '2026-02-19 17:15:05'),
(6, 6, 1, 'Table Tennis Court 1', 'available', '2026-02-19 17:15:05'),
(7, 7, 1, 'Futsal Court 1', 'available', '2026-02-19 17:15:05'),
(8, 1, 2, 'Badminton Court 2', 'available', '2026-02-19 17:15:05'),
(9, 2, 2, 'Football Court 2', 'available', '2026-02-19 17:15:05'),
(10, 3, 2, 'Tennis Court 2', 'available', '2026-02-19 17:15:05'),
(11, 4, 2, 'Volleyball Court 2', 'available', '2026-02-19 17:15:05'),
(12, 5, 2, 'Basketball Court 2', 'available', '2026-02-19 17:15:05'),
(13, 6, 2, 'Table Tennis Court 2', 'available', '2026-02-19 17:15:05'),
(14, 7, 2, 'Futsal Court 2', 'available', '2026-02-19 17:15:05'),
(15, 1, 3, 'Badminton Court 3', 'available', '2026-02-19 17:15:05'),
(16, 3, 3, 'Tennis Court 3', 'available', '2026-02-19 17:15:05'),
(17, 4, 3, 'Volleyball Court 3', 'available', '2026-02-19 17:15:05'),
(18, 5, 3, 'Basketball Court 3', 'available', '2026-02-19 17:15:05'),
(19, 6, 3, 'Table Tennis Court 3', 'available', '2026-02-19 17:15:05'),
(20, 1, 4, 'Badminton Court 4', 'available', '2026-02-19 17:15:05'),
(21, 3, 4, 'Tennis Court 4', 'available', '2026-02-19 17:15:05'),
(22, 4, 4, 'Volleyball Court 4', 'available', '2026-02-19 17:15:05'),
(23, 5, 4, 'Basketball Court 4', 'available', '2026-02-19 17:15:05'),
(24, 6, 4, 'Table Tennis Court 4', 'available', '2026-02-19 17:15:05'),
(25, 5, 5, 'Basketball Court 5', 'available', '2026-02-19 17:15:05'),
(26, 6, 5, 'Table Tennis Court 5', 'available', '2026-02-19 17:15:05'),
(27, 5, 6, 'Basketball Court 6', 'available', '2026-02-19 17:15:05'),
(28, 6, 6, 'Table Tennis Court 6', 'available', '2026-02-19 17:15:05'),
(29, 5, 7, 'Basketball Court 7', 'available', '2026-02-19 17:15:05'),
(30, 5, 8, 'Basketball Court 8', 'available', '2026-02-19 17:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `eq_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `eq_name` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `fine_amount` int(11) DEFAULT 50,
  `max_per_court` int(11) DEFAULT 10,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`eq_id`, `sport_id`, `eq_name`, `price`, `stock`, `fine_amount`, `max_per_court`, `status`, `created_at`) VALUES
(1, 1, 'Badminton Racket', 10, 35, 50, 25, 'available', '2026-02-19 17:15:05'),
(2, 2, 'Football', 50, 41, 50, 20, 'available', '2026-02-19 17:15:05'),
(3, 2, 'Training Bib', 30, 11, 50, 5, 'available', '2026-02-19 17:15:05'),
(4, 2, 'Training Cone', 20, 11, 50, 5, 'available', '2026-02-19 17:15:05'),
(5, 2, 'Training Equipment Set', 300, 7, 50, 3, 'available', '2026-02-19 17:15:05'),
(6, 3, 'Tennis Racket', 50, 17, 50, 8, 'available', '2026-02-19 17:15:05'),
(7, 3, 'Tennis Ball', 15, 22, 50, 10, 'available', '2026-02-19 17:15:05'),
(8, 4, 'Volleyball', 50, 20, 50, 10, 'available', '2026-02-19 17:15:05'),
(9, 5, 'Basketball', 50, 33, 50, 15, 'available', '2026-02-19 17:15:05'),
(10, 6, 'Ping-Pong Racket', 10, 23, 50, 12, 'available', '2026-02-19 17:15:05'),
(11, 6, 'Ping-Pong Ball', 5, 23, 50, 12, 'available', '2026-02-19 17:15:05'),
(12, 7, 'Futsal Ball', 40, 40, 50, 20, 'available', '2026-02-19 17:15:05'),
(13, 1, 'Badminton Ball', 5, 0, 50, 10, 'available', '2026-02-20 19:24:13');

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_day1` int(11) DEFAULT 30,
  `discount_day16` int(11) DEFAULT 30,
  `discount_consecutive` int(11) DEFAULT 20,
  `discount_first_booking` int(11) DEFAULT 10,
  `free_equipment_limit` int(11) DEFAULT 4,
  `advance_booking_days` int(11) DEFAULT 7,
  `features` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`plan_id`, `plan_name`, `duration_months`, `price`, `discount_day1`, `discount_day16`, `discount_consecutive`, `discount_first_booking`, `free_equipment_limit`, `advance_booking_days`, `features`, `status`, `created_at`) VALUES
(1, 'Premium Plan', 3, '499.00', 30, 30, 20, 10, 4, 7, '7 Days Advance Booking;30% Discount on 1st & 16th;Free Equipment (4 items/month);Point Rewards System', 'active', '2026-02-19 17:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('bank_transfer','promptpay','cash') DEFAULT 'promptpay',
  `amount` decimal(10,2) NOT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_method`, `amount`, `slip_image`, `reference_code`, `payment_status`, `verified_by`, `verified_at`, `created_at`) VALUES
(3, 3, 'promptpay', '280.00', 'uploads/slips/69975c617a606_สกรีนช็อต 2026-02-19 012301.png', NULL, 'rejected', NULL, NULL, '2026-02-19 18:54:25'),
(4, 4, 'promptpay', '160.00', 'uploads/slips/6997703bc9d0c_สกรีนช็อต 2026-02-19 014836.png', NULL, 'verified', 1, '2026-02-20 03:19:29', '2026-02-19 20:19:07'),
(5, 5, 'promptpay', '500.00', 'uploads/slips/69977693462c0_สกรีนช็อต 2026-02-19 014836.png', NULL, 'rejected', NULL, NULL, '2026-02-19 20:46:11'),
(6, 7, 'promptpay', '230.00', 'uploads/slips/6997f6124eeed_สกรีนช็อต 2026-02-20 015813.png', NULL, 'verified', 1, '2026-02-20 12:51:24', '2026-02-20 05:50:10'),
(7, 8, 'promptpay', '55.00', 'uploads/slips/6997f756cf692_S__10158177.jpg', NULL, 'verified', 1, '2026-02-20 12:55:46', '2026-02-20 05:55:34'),
(8, 9, 'promptpay', '160.00', 'uploads/slips/6997f980b24e4_Home Page.png', NULL, 'verified', 1, '2026-02-20 13:05:14', '2026-02-20 06:04:48'),
(9, 10, 'promptpay', '180.00', 'uploads/slips/6997fa630588e_สกรีนช็อต 2026-02-19 005126.png', NULL, 'verified', 1, '2026-02-20 13:12:16', '2026-02-20 06:08:35'),
(10, 11, 'promptpay', '130.00', 'uploads/slips/6997fb3a7c16d_สกรีนช็อต 2026-02-19 014836.png', NULL, 'verified', 1, '2026-02-20 13:12:19', '2026-02-20 06:12:10'),
(11, 12, 'promptpay', '200.00', 'uploads/slips/6997fb8e83561_สกรีนช็อต 2026-02-19 014836.png', NULL, 'verified', 1, '2026-02-20 13:14:28', '2026-02-20 06:13:34'),
(12, 13, 'promptpay', '40.00', 'uploads/slips/6997fccf3814a_สกรีนช็อต 2026-02-19 014836.png', NULL, 'verified', 1, '2026-02-20 13:20:34', '2026-02-20 06:18:55'),
(13, 13, 'promptpay', '40.00', 'uploads/slips/6997fcd145ad4_สกรีนช็อต 2026-02-19 014836.png', NULL, 'verified', 1, '2026-02-20 13:20:34', '2026-02-20 06:18:57'),
(14, 14, 'promptpay', '130.00', 'uploads/slips/6997ff51e1c90_Home Page.png', NULL, 'verified', 1, '2026-02-20 13:32:08', '2026-02-20 06:29:37'),
(15, 15, 'promptpay', '200.00', 'uploads/slips/6997ffcfb110e_สกรีนช็อต 2026-02-19 011623.png', NULL, 'verified', 1, '2026-02-20 13:32:10', '2026-02-20 06:31:43'),
(17, 19, 'promptpay', '40.00', 'uploads/slips/bk_19_1771858289.jpg', NULL, 'verified', NULL, '2026-02-23 21:51:30', '2026-02-23 14:51:30'),
(18, 23, 'promptpay', '40.00', 'uploads/slips/bk_23_1771862107.jpg', NULL, 'verified', NULL, '2026-02-23 22:55:08', '2026-02-23 15:55:08'),
(19, 24, 'promptpay', '40.00', 'uploads/slips/bk_24_1771863056.jpg', NULL, 'verified', NULL, '2026-02-23 23:10:57', '2026-02-23 16:10:57'),
(20, 31, 'promptpay', '40.00', 'uploads/slips/bk_31_1771950910.jpg', NULL, 'verified', NULL, '2026-02-24 23:35:11', '2026-02-24 16:35:11'),
(21, 37, 'promptpay', '36.00', 'uploads/slips/bk_37_1772000691.jpg', NULL, 'verified', NULL, '2026-02-25 13:24:52', '2026-02-25 06:24:52'),
(22, 39, 'promptpay', '50.00', 'uploads/slips/bk_39_1772132617.jpg', NULL, 'verified', NULL, '2026-02-26 11:03:39', '2026-02-26 19:03:39'),
(23, 41, 'promptpay', '40.00', 'uploads/slips/bk_41_1772157162.jpg', NULL, 'verified', NULL, '2026-02-26 17:52:43', '2026-02-27 01:52:43'),
(24, 44, 'promptpay', '40.00', 'uploads/slips/bk_44_1772188096.jpg', NULL, 'verified', NULL, '2026-02-27 02:28:17', '2026-02-27 10:28:17'),
(25, 63, 'promptpay', '36.00', 'uploads/slips/bk_63_1773031131.jpg', NULL, 'verified', NULL, '2026-03-08 21:38:53', '2026-03-09 04:38:53'),
(26, 67, 'promptpay', '50.00', 'uploads/slips/bk_67_1773037185.jpg', NULL, 'verified', NULL, '2026-03-08 23:19:47', '2026-03-09 06:19:47'),
(27, 68, 'promptpay', '50.00', 'uploads/slips/bk_68_1773037249.jpg', NULL, 'verified', NULL, '2026-03-08 23:20:51', '2026-03-09 06:20:51'),
(28, 74, 'promptpay', '46.00', 'uploads/slips/bk_74_1773046565.jpg', NULL, 'verified', NULL, '2026-03-09 01:56:07', '2026-03-09 08:56:07'),
(29, 76, 'promptpay', '40.00', 'uploads/slips/bk_76_1773046902.jpg', NULL, 'verified', NULL, '2026-03-09 02:01:50', '2026-03-09 09:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('new','in_progress','resolved') DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_code`, `user_id`, `topic`, `description`, `image_path`, `status`, `admin_notes`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(20, 'RP20260220A0CFB6', 5, 'AI เป็นเอ๋อ', '7888', '', 'resolved', '', 1, '2026-02-21 01:28:01', '2026-02-20 18:26:50', '2026-02-20 18:28:01'),
(21, 'RP20260302174B31', 2, 'AI เป็นเอ๋อดดดดดด', 'ดดดดดดดดดด', '', 'in_progress', '', 1, NULL, '2026-03-02 17:40:49', '2026-03-02 17:41:21'),
(22, 'RP20260302BD04AB', 2, 'kkk', 'ััััััััั', '', 'in_progress', 'swfef', 1, NULL, '2026-03-02 17:40:59', '2026-03-03 18:18:35'),
(23, 'RP20260302A558B5', 2, 'ad', 'สาส', '', 'resolved', 'ครับ', 1, '2026-03-02 09:42:01', '2026-03-02 17:41:46', '2026-03-02 17:42:01'),
(24, 'RP202603044446B4', 9, 'test', '123', '', 'in_progress', 'ok', 1, NULL, '2026-03-04 07:38:11', '2026-03-04 07:41:09'),
(25, 'RP2026030435782D', 2, 'CSS มีปัญหาครับ', 'แก้ด้วยครับ', '', 'new', NULL, NULL, NULL, '2026-03-04 07:48:18', '2026-03-04 07:48:18'),
(26, 'RP202603041E82C9', 9, 'test2', 'check', '', 'in_progress', '', 1, NULL, '2026-03-04 07:55:29', '2026-03-04 09:24:26'),
(27, 'RP20260304E3CB30', 2, 'เวลา มีปัญหาครับบบบ', 'ทำไมครับ', '', 'new', NULL, NULL, NULL, '2026-03-04 07:56:13', '2026-03-04 07:56:13'),
(28, 'RP202603047C3740', 2, 'งง', 'งงเหมือนกัน', '', 'new', NULL, NULL, NULL, '2026-03-04 07:57:27', '2026-03-04 07:57:27'),
(29, 'RP20260304BC3CEB', 2, 'หหห', 'หหห', '', 'new', NULL, NULL, NULL, '2026-03-04 07:59:23', '2026-03-04 07:59:23'),
(30, 'RP2026030436A423', 2, 'คคค', 'คคค', '', 'new', NULL, NULL, NULL, '2026-03-04 08:01:06', '2026-03-04 08:01:06'),
(31, 'RP20260304C8744E', 9, 'testdate', 'sss', '', 'resolved', 'checked', 1, '2026-03-04 00:19:36', '2026-03-04 08:05:31', '2026-03-04 08:19:36'),
(32, 'RP20260306C77B36', 9, 'testwithpic', '123', 'uploads/reports/69aae3cc77a5e_สกรีนช็อต 2026-03-02 014856.png', 'new', NULL, NULL, NULL, '2026-03-06 14:25:16', '2026-03-06 14:25:16'),
(33, 'RP20260309CD054F', 10, 'Sawasdee', 'Krub', '', 'resolved', 'KRub nong', 1, '2026-03-09 01:52:37', '2026-03-09 08:51:08', '2026-03-09 08:52:37');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Hit The Court', 'Website name', '2026-02-19 17:15:05'),
(2, 'promptpay_number', '0951386174', 'PromptPay phone number', '2026-02-23 15:11:28'),
(3, 'bank_name', 'Kasikorn', 'Bank name', '2026-02-23 15:11:54'),
(4, 'bank_account', '1261900617', 'Bank account number', '2026-02-23 15:12:09'),
(5, 'company_name', 'น.ส. ภานิชา ศรีกระจ่าง', 'Company name for payment', '2026-02-23 15:56:57');

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

CREATE TABLE `sports` (
  `sport_id` int(11) NOT NULL,
  `sport_name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `max_courts` int(11) DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price_per_round` decimal(10,2) DEFAULT 0.00,
  `total_courts` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`sport_id`, `sport_name`, `slug`, `description`, `duration_minutes`, `price`, `max_courts`, `image`, `status`, `created_at`, `price_per_round`, `total_courts`) VALUES
(1, 'Badminton', 'badminton', NULL, 60, 130, 4, NULL, 'active', '2026-02-19 17:15:05', '130.00', 4),
(2, 'Football', 'football', NULL, 100, 500, 2, NULL, 'active', '2026-02-19 17:15:05', '500.00', 2),
(3, 'Tennis', 'tennis', NULL, 120, 200, 4, NULL, 'active', '2026-02-19 17:15:05', '200.00', 4),
(4, 'Volleyball', 'volleyball', NULL, 120, 150, 4, NULL, 'active', '2026-02-19 17:15:05', '150.00', 4),
(5, 'Basketball', 'basketball', NULL, 60, 130, 8, NULL, 'active', '2026-02-19 17:15:05', '130.00', 8),
(6, 'Table Tennis', 'table-tennis', NULL, 60, 40, 6, NULL, 'active', '2026-02-19 17:15:05', '40.00', 6),
(7, 'Futsal', 'futsal', NULL, 60, 130, 2, NULL, 'active', '2026-02-19 17:15:05', '130.00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `slot_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`slot_id`, `sport_id`, `start_time`, `end_time`, `status`) VALUES
(27, 1, '09:00:00', '10:00:00', 'active'),
(28, 1, '10:10:00', '11:10:00', 'active'),
(29, 1, '11:20:00', '12:20:00', 'active'),
(30, 1, '12:30:00', '13:30:00', 'active'),
(31, 1, '13:40:00', '14:40:00', 'active'),
(32, 1, '14:50:00', '15:50:00', 'active'),
(33, 1, '16:00:00', '17:00:00', 'active'),
(34, 1, '17:10:00', '18:10:00', 'active'),
(35, 1, '18:20:00', '19:20:00', 'active'),
(36, 1, '19:30:00', '20:30:00', 'active'),
(37, 1, '20:40:00', '21:40:00', 'active'),
(38, 2, '09:00:00', '10:40:00', 'active'),
(39, 2, '10:50:00', '12:30:00', 'active'),
(40, 2, '12:40:00', '14:20:00', 'active'),
(41, 2, '14:30:00', '16:10:00', 'active'),
(42, 2, '16:20:00', '18:00:00', 'active'),
(43, 2, '18:10:00', '19:50:00', 'active'),
(44, 2, '20:00:00', '21:40:00', 'active'),
(45, 3, '09:00:00', '11:00:00', 'active'),
(46, 3, '11:10:00', '13:10:00', 'active'),
(47, 3, '13:20:00', '15:20:00', 'active'),
(48, 3, '15:30:00', '17:30:00', 'active'),
(49, 3, '17:40:00', '19:40:00', 'active'),
(50, 3, '19:50:00', '21:50:00', 'active'),
(51, 4, '09:00:00', '11:00:00', 'active'),
(52, 4, '11:10:00', '13:10:00', 'active'),
(53, 4, '13:20:00', '15:20:00', 'active'),
(54, 4, '15:30:00', '17:30:00', 'active'),
(55, 4, '17:40:00', '19:40:00', 'active'),
(56, 4, '19:50:00', '21:50:00', 'active'),
(57, 5, '09:00:00', '10:00:00', 'active'),
(58, 5, '10:10:00', '11:10:00', 'active'),
(59, 5, '11:20:00', '12:20:00', 'active'),
(60, 5, '12:30:00', '13:30:00', 'active'),
(61, 5, '13:40:00', '14:40:00', 'active'),
(62, 5, '14:50:00', '15:50:00', 'active'),
(63, 5, '16:00:00', '17:00:00', 'active'),
(64, 5, '17:10:00', '18:10:00', 'active'),
(65, 5, '18:20:00', '19:20:00', 'active'),
(66, 5, '19:30:00', '20:30:00', 'active'),
(67, 5, '20:40:00', '21:40:00', 'active'),
(68, 6, '09:00:00', '10:00:00', 'active'),
(69, 6, '10:10:00', '11:10:00', 'active'),
(70, 6, '11:20:00', '12:20:00', 'active'),
(71, 6, '12:30:00', '13:30:00', 'active'),
(72, 6, '13:40:00', '14:40:00', 'active'),
(73, 6, '14:50:00', '15:50:00', 'active'),
(74, 6, '16:00:00', '17:00:00', 'active'),
(75, 6, '17:10:00', '18:10:00', 'active'),
(76, 6, '18:20:00', '19:20:00', 'active'),
(77, 6, '19:30:00', '20:30:00', 'active'),
(78, 6, '20:40:00', '21:40:00', 'active'),
(79, 7, '09:00:00', '10:00:00', 'active'),
(80, 7, '10:10:00', '11:10:00', 'active'),
(81, 7, '11:20:00', '12:20:00', 'active'),
(82, 7, '12:30:00', '13:30:00', 'active'),
(83, 7, '13:40:00', '14:40:00', 'active'),
(84, 7, '14:50:00', '15:50:00', 'active'),
(85, 7, '16:00:00', '17:00:00', 'active'),
(86, 7, '17:10:00', '18:10:00', 'active'),
(87, 7, '18:20:00', '19:20:00', 'active'),
(88, 7, '19:30:00', '20:30:00', 'active'),
(89, 7, '20:40:00', '21:40:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_member` tinyint(1) DEFAULT 0,
  `member_expire` date DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `total_bookings` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `phone`, `is_member`, `member_expire`, `points`, `total_bookings`, `created_at`, `updated_at`) VALUES
(2, 'user1', '$2y$10$21.BGxhQ422NIRUC281OxeQcYwJQk7Y7U489uAZhbheW.N2M.HL5G', 'user1@hitthecourt.com', '0999999999', 1, '2026-05-19', 20, 4, '2026-02-19 18:05:44', '2026-03-09 04:38:53'),
(3, 'user2', '$2y$10$DsuSRYMbRw3Nkk1obSeCbu0QuPzGDHnM73BxQPxwkfJkIDYH8kUnK', 'napxswww@gmail.com', '111', 0, NULL, 20, 5, '2026-02-19 20:37:54', '2026-02-20 05:55:46'),
(4, 'user3', '$2y$10$35r6vL236.dBAMPEq0I6BuSPHQdTYNESVWJ.ANHV9LZnR3Y8fdKZC', 'Darkviolet1819@gmail.com', '054584855', 1, '2026-05-24', 20, 3, '2026-02-20 05:20:31', '2026-02-24 17:47:01'),
(5, 'user4', '$2y$10$JIQJ6S8gvbGt6EZDE2SGb.2elD.n.KtHfdKcUJOo4YkHi/LqZlpKO', 'haha@example.com', 'roqer', 1, '2026-05-23', 58, 18, '2026-02-20 06:07:44', '2026-02-27 01:52:43'),
(6, 'user6', '$2y$10$.XpNmXwodifnH5syxuKxJuetm9cuL9JLtjSqiQ1/Aef8Z77dbsZJu', 'n3xzerf@gmail.com', '00000000000', 1, '2026-05-25', 10, 1, '2026-02-25 06:18:31', '2026-02-25 06:24:52'),
(7, 'user9', '$2y$10$AAWRN7aWuC3c71aOTOZr/.fpghVnnOtWnYaNtzNcGYZ5H94TA45aa', 'rr@rr.com', 'rr', 0, NULL, 30, 3, '2026-02-26 17:50:46', '2026-03-09 06:20:51'),
(8, 'player', '$2y$10$UGBWWsC7B7qU1EAOz5r2/usnWfoZ4aOT31V3Eo/zM8sNEpEQWBoL6', 'roadmay@gmail.com', '0874838383', 0, NULL, 10, 1, '2026-02-27 10:25:48', '2026-02-27 10:28:17'),
(9, 'addy', '$2y$10$y7AlEydEIY6sGcTRO4ibOeGih1YsJYhc6Cd8cfhtPUwHpiAZOLXwO', 'addy.hic@gmail.com', '0888888888', 0, NULL, 0, 0, '2026-03-01 17:07:42', '2026-03-01 17:07:42'),
(10, 'user10', '$2y$10$AqZVhOlS6Qc7FFaAkzKtQ.6/FWUi76vmDWWY4QBZxxMYFmNgEWGB.', 'ntrexpz@gh81ty.com', '0951386174', 1, '2026-06-09', 11, 2, '2026-03-09 07:47:13', '2026-03-09 09:15:55'),
(11, 'user11', '$2y$10$uDIC91qZqIQZ0bHnrO50kujyEQuTGH/sew9HoJgFyNqtsIZKVkuFG', 'shotraro@gmail.com', '095935825', 0, NULL, 0, 0, '2026-03-09 09:44:48', '2026-03-09 09:44:48'),
(12, 'user12', '$2y$10$R8b2kyd6hELXUNdwsYfkmuOq6Ut2jtNLqcxjrsLpX2aZkTEitKkhe', 'shiki@gmami.com', '0999999999', 0, NULL, 0, 0, '2026-03-09 09:45:39', '2026-03-09 09:45:39'),
(13, 'user13', '$2y$10$51EzBdquAJK2Cgn.cWLoleY6Z/BysTZAFTYdvji.BS6ghQrCHFKAS', 'bibi@bibi.com', '0123456789', 0, NULL, 0, 0, '2026-03-09 09:47:06', '2026-03-09 09:47:06'),
(14, 't7may', '$2y$10$EOWBXZc8wXf99TU99rk1cOK0UXMnCaPpSz41EP0aPPdpw3v4MgD2u', 't7may@gmail.com', '1111111111', 0, NULL, 0, 0, '2026-03-09 18:11:08', '2026-03-09 18:11:08'),
(15, 'jeered', '$2y$10$8cOqGA/SyB3MNLm9HXQ3mOBJLTlQR.5UdK83Vq8pKsuEmayHbqOiq', 'jer@gmai.com', '1234', 0, NULL, 0, 0, '2026-03-09 18:19:05', '2026-03-09 18:19:05'),
(16, 'turtle', '$2y$10$O5csfPjxUzF6LL.4AHcANuyB48lziYkHHEBSUI2c2IKr1uM81nlUC', 'turtle@gmail.com', '8888888888', 0, NULL, 0, 0, '2026-03-09 18:20:24', '2026-03-09 18:20:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_membership`
--

CREATE TABLE `user_membership` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','verified') DEFAULT 'pending',
  `slip_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `user_membership`
--

INSERT INTO `user_membership` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `total_price`, `payment_status`, `slip_image`, `created_at`) VALUES
(1, 3, 1, '2026-02-19', '2026-05-19', NULL, 'pending', NULL, '2026-02-19 20:38:55'),
(2, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 20:42:26'),
(3, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 20:49:38'),
(4, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 20:50:21'),
(5, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 20:58:33'),
(6, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 21:01:03'),
(7, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 21:05:11'),
(8, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 21:07:05'),
(9, 3, 1, '2026-02-19', '2026-05-19', '499.00', 'pending', NULL, '2026-02-19 21:07:31'),
(10, 3, 1, '2026-02-20', '2026-05-20', '499.00', '', 'uploads/slips/mem_10_1771566176.png', '2026-02-20 05:41:34'),
(11, 5, 1, '2026-02-20', '2026-05-20', '499.00', '', 'uploads/slips/mem_11_1771607893.png', '2026-02-20 17:16:38'),
(12, 5, 1, '2026-02-20', '2026-05-20', '499.00', 'pending', NULL, '2026-02-20 19:27:48'),
(13, 5, 1, '2026-02-23', '2026-05-23', '499.00', 'pending', NULL, '2026-02-23 15:02:24'),
(14, 5, 1, '2026-02-23', '2026-05-23', '499.00', '', 'uploads/slips/mem_14_1771863701.jpg', '2026-02-23 16:17:22'),
(15, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'pending', NULL, '2026-02-24 14:41:24'),
(16, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'pending', NULL, '2026-02-24 15:58:10'),
(17, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'pending', NULL, '2026-02-24 16:04:20'),
(18, 4, 1, '2026-02-24', '2026-05-24', '499.00', '', 'uploads/slips/mem_18_1771950512.jpg', '2026-02-24 16:28:07'),
(19, 4, 1, '2026-02-24', '2026-05-24', '499.00', '', 'uploads/slips/mem_19_1771951764.jpg', '2026-02-24 16:36:33'),
(20, 4, 1, '2026-02-24', '2026-05-24', '499.00', '', 'uploads/slips/mem_20_1771952242.jpg', '2026-02-24 16:56:42'),
(21, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'verified', 'uploads/slips/mem_21_1771953440.jpg', '2026-02-24 17:16:27'),
(22, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'verified', 'uploads/slips/mem_22_1771954591.jpg', '2026-02-24 17:35:54'),
(23, 4, 1, '2026-02-24', '2026-05-24', '499.00', 'verified', 'uploads/slips/mem_23_1771955221.jpg', '2026-02-24 17:46:29'),
(24, 6, 1, '2026-02-25', '2026-05-25', '499.00', 'verified', 'uploads/slips/mem_24_1772000455.jpg', '2026-02-25 06:18:56'),
(25, 7, 1, '2026-02-26', '2026-05-26', '499.00', 'pending', NULL, '2026-02-26 19:02:12'),
(26, 9, 1, '2026-03-04', '2026-06-04', '499.00', 'pending', NULL, '2026-03-04 09:54:20'),
(27, 9, 1, '2026-03-05', '2026-06-05', '499.00', 'pending', NULL, '2026-03-05 16:47:09'),
(28, 10, 1, '2026-03-09', '2026-06-09', '499.00', 'verified', 'uploads/slips/mem_28_1773042580.jpg', '2026-03-09 07:48:58'),
(29, 14, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-09 18:13:58'),
(30, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 12:40:09'),
(31, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 12:46:53'),
(32, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 12:47:40'),
(33, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 12:47:59'),
(34, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 13:01:28'),
(35, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 13:01:42'),
(36, 7, 1, '2026-03-10', '2026-06-10', '499.00', 'pending', NULL, '2026-03-10 13:11:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `court_id` (`court_id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `eq_id` (`eq_id`);

--
-- Indexes for table `courts`
--
ALTER TABLE `courts`
  ADD PRIMARY KEY (`court_id`),
  ADD UNIQUE KEY `unique_court` (`sport_id`,`court_number`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`eq_id`),
  ADD KEY `sport_id` (`sport_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`sport_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`slot_id`),
  ADD UNIQUE KEY `unique_slot` (`sport_id`,`start_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_membership`
--
ALTER TABLE `user_membership`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `courts`
--
ALTER TABLE `courts`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `eq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `sport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_membership`
--
ALTER TABLE `user_membership`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`court_id`) REFERENCES `courts` (`court_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`slot_id`);

--
-- Constraints for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  ADD CONSTRAINT `booking_equipment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_equipment_ibfk_2` FOREIGN KEY (`eq_id`) REFERENCES `equipment` (`eq_id`);

--
-- Constraints for table `courts`
--
ALTER TABLE `courts`
  ADD CONSTRAINT `courts_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`sport_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_membership`
--
ALTER TABLE `user_membership`
  ADD CONSTRAINT `user_membership_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_membership_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
