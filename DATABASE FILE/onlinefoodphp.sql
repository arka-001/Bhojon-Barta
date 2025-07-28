-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 04:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onlinefoodphp2`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_deletion_log`
--

CREATE TABLE `account_deletion_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adm_id` int(222) NOT NULL,
  `username` varchar(222) NOT NULL,
  `password` varchar(222) NOT NULL,
  `email` varchar(222) NOT NULL,
  `code` varchar(222) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adm_id`, `username`, `password`, `email`, `code`, `date`) VALUES
(1, 'admin', 'CAC29D7A34687EB14B37068EE4708E7B', 'admin@mail.com', '', '2022-05-27 07:51:52'),
(14, 'ytvillaina', '$2y$10$tefrkDw.8JpmAp1MSCYs2OwdmTwS2oOxRZtenilbOdMCNCZUZQti.', 'ytvillainar@gmail.com', '271513', '2025-04-22 21:41:31'),
(15, 'admin2', '$2y$10$dKMuulMrxeanwi1z3dHLjO4C8T1orxaq0HudW8rgZjCRXyCXM5tOq', 'arkamaitra001@gmail.com', '', '2025-03-29 18:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `u_id` int(222) NOT NULL,
  `d_id` int(222) NOT NULL,
  `res_id` int(222) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `u_id`, `d_id`, `res_id`, `quantity`, `price`, `added_at`) VALUES
(37, 41, 30, 26, 1, 30.00, '2025-04-19 18:58:23'),
(79, 44, 34, 44, 10, 169.00, '2025-06-24 08:42:38');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `cat_id` int(11) NOT NULL,
  `c_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `bot_response` text DEFAULT NULL,
  `intent` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `coupon_code`, `discount_type`, `discount_value`, `min_order_value`, `expiration_date`, `usage_limit`, `usage_limit_per_user`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'happy', 'fixed', 400.00, 2000.00, '2025-08-23', NULL, NULL, 1, '2025-03-23 09:33:13', '2025-03-23 09:33:13'),
(2, '001', 'fixed', 100.00, 6000.00, '2025-08-23', NULL, NULL, 1, '2025-03-23 09:34:32', '2025-03-23 09:34:32'),
(4, '002', 'percentage', 52.00, 500.00, '2025-08-28', NULL, NULL, 1, '2025-03-23 10:11:13', '2025-03-23 10:11:13'),
(5, '0000', 'fixed', 200.00, 500.00, '2025-08-28', NULL, NULL, 1, '2025-03-23 10:11:33', '2025-03-23 10:11:33'),
(6, '1111', 'percentage', 50.00, 100.00, '2025-08-26', NULL, NULL, 1, '2025-03-23 17:03:57', '2025-03-23 17:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users_log`
--

CREATE TABLE `deleted_users_log` (
  `log_id` int(11) NOT NULL,
  `original_user_id` int(11) NOT NULL,
  `username` varchar(222) DEFAULT NULL,
  `email` varchar(222) DEFAULT NULL,
  `phone` varchar(222) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `original_password_at_deletion` text DEFAULT NULL COMMENT 'Stores the password string from users table at deletion time',
  `reason_for_deletion` varchar(255) DEFAULT 'User requested deletion',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `deletion_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_users_log`
--

INSERT INTO `deleted_users_log` (`log_id`, `original_user_id`, `username`, `email`, `phone`, `address`, `city`, `original_password_at_deletion`, `reason_for_deletion`, `ip_address`, `user_agent`, `deletion_timestamp`) VALUES
(1, 43, 'animash001', 'animeshghosh1502@gmail.com', '9647185877', 'Jiaganj Azimganj, Murshidabad Jiaganj, West Bengal, 742123, India', 'Murshidabad Jiaganj', NULL, 'User requested deletion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `delivary_charges`
--

CREATE TABLE `delivary_charges` (
  `id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `min_order_value` decimal(10,2) DEFAULT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivary_charges`
--

INSERT INTO `delivary_charges` (`id`, `location_id`, `min_order_value`, `delivery_charge`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 3000.00, 50.00, 'Default Delivery Charge', '2025-03-17 22:09:11', '2025-06-02 18:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_boy`
--

CREATE TABLE `delivery_boy` (
  `db_id` int(11) NOT NULL,
  `db_name` varchar(255) NOT NULL,
  `db_phone` varchar(20) NOT NULL,
  `db_email` varchar(255) DEFAULT NULL,
  `db_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `db_photo` varchar(255) DEFAULT NULL,
  `db_password` varchar(255) NOT NULL,
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `db_status` tinyint(1) NOT NULL DEFAULT 1,
  `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `longitude` decimal(11,8) NOT NULL DEFAULT 0.00000000,
  `current_status` enum('available','busy','offline') DEFAULT 'available',
  `restaurant_id` int(11) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `driving_license_number` varchar(50) DEFAULT NULL,
  `driving_license_expiry` date DEFAULT NULL,
  `driving_license_photo` varchar(255) DEFAULT NULL,
  `aadhaar_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_boy`
--

INSERT INTO `delivery_boy` (`db_id`, `db_name`, `db_phone`, `db_email`, `db_address`, `city`, `created_at`, `updated_at`, `db_photo`, `db_password`, `reset_otp`, `reset_otp_expiry`, `reset_token`, `reset_token_expiry`, `db_status`, `latitude`, `longitude`, `current_status`, `restaurant_id`, `bank_account_number`, `ifsc_code`, `account_holder_name`, `driving_license_number`, `driving_license_expiry`, `driving_license_photo`, `aadhaar_pdf`) VALUES
(19, 'raj', '01111111111', '', 'madhupur berhampur murshidabad 742101', NULL, '2025-04-03 15:08:40', '2025-04-23 19:09:29', 'delivery_boy_images/67eea478387fc_badge-2.png', '$2y$10$zAlpQkrctUQrquQTdBHnwOwlkBKjiWaLYIo5KE7p2ru7gfZDVE5b2', NULL, NULL, NULL, NULL, 1, 23.52044430, 87.31192270, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'raja', '7896985896', NULL, 'sornomoi berhampur murshidabad', 'Berhampore', '2025-04-03 16:04:57', '2025-04-24 14:29:39', 'delivery_boy_images/67eeb1a903925_about-banner.jpg', '$2y$10$X7ouuiQjo9qzES64KgylG.ENIgngtuBK8nCtwTdWcEZDDqd2dJg8O', NULL, NULL, NULL, NULL, 0, 26.72710120, 88.39528610, 'offline', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'amio', '4569874589', 'mukutmaitra@gmail.com', 'madhupur berhampur murshidabad 742101', NULL, '2025-04-04 16:07:51', '2025-06-23 20:41:03', 'delivery_boy_images/67f003d78eb06_badge-2.png', '$2y$10$x.mI2UOkA4hAr8p.b1EVlO.3BLCr0gB5jTqsG3.HXf0zN2rZFreE2', NULL, NULL, NULL, NULL, 1, 26.72710120, 88.39528610, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'arka', '4569856985', 'ytvillainar@gmail.com', '1/1, Gariahat Road, Ballygunge, Kolkata, West Bengal 700019', 'Kolkata', '2025-04-19 16:16:10', '2025-06-23 20:43:38', '', '$2y$10$4Sp7oPVCZycU44LdHrDRjeFARpvNOzumfMfyFMRBKXsdTs7rAC0GC', NULL, NULL, NULL, NULL, 1, 22.34517230, 87.30470530, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'aaa', '7878965698', '', 'Berhampore, Murshidabad, West Bengal 742101', 'Berhampore', '2025-04-19 18:17:27', '2025-04-23 19:09:10', '', '$2y$10$76BSGcDTS3juZHAJJvFRreyAzkis3hf7JV05AlYZm6j2IVWpzuVXW', NULL, NULL, NULL, NULL, 1, 51.50735100, -0.12775800, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'Arka Maitra', '4569869856', '', 'Kolkata, Kolkata, West Bengal, India', 'kolkata', '2025-04-22 19:19:09', '2025-06-23 20:40:10', NULL, '$2y$10$qNK8mH0MEddKxGcYYIn7Au5cNnodwmk0P75AI/Dl2F87hChGjONBm', NULL, NULL, NULL, NULL, 0, 22.57264590, 88.36389530, 'busy', NULL, '', 'SBIN0000691', 'arka maitra', 'WB202022004567', '2026-12-10', NULL, ''),
(25, 'Arka Maitra', '7823636985', '', 'Kolkata, Kolkata, West Bengal, India', 'berhampore', '2025-04-22 19:33:12', '2025-04-23 19:09:17', 'admin/delivery_boy_images/profile_1745350350_6807eece1748e.jpg', '$2y$10$KLMoXY/04MhnDJSSxd4E2OeFetogwBvxwa..vvkvq6YqlteKJA1H2', NULL, NULL, NULL, NULL, 1, 19.30596500, 84.80118800, 'available', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745350350_6807eece17781.jpg', 'admin/delivery_boy_images/aadhaar_1745350350_6807eece1787f.pdf'),
(27, 'Arka Maitra', '8698569856', 'arkamaitra001@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', '2025-04-24 11:53:04', '2025-06-23 20:40:20', 'admin/delivery_boy_images/profile_1745492442_680a19da37212.jpg', '$2y$10$NDdV5aQ3/zfDSyhEtOUHpuEfOAFQcyzHXcf.5CmXaOkEKOymHhIc6', NULL, NULL, NULL, NULL, 0, 22.57435450, 88.36287340, 'busy', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745492442_680a19da373ea.jpg', 'admin/delivery_boy_images/aadhaar_1745492442_680a19da3763a.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_boy_history`
--

CREATE TABLE `delivery_boy_history` (
  `id` int(11) NOT NULL,
  `delivery_boy_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_price` decimal(10,2) NOT NULL,
  `order_title` varchar(255) NOT NULL,
  `order_quantity` int(11) NOT NULL,
  `customer_username` varchar(100) NOT NULL,
  `restaurant_title` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `completed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_boy_history`
--

INSERT INTO `delivery_boy_history` (`id`, `delivery_boy_id`, `order_id`, `delivery_charge`, `order_price`, `order_title`, `order_quantity`, `customer_username`, `restaurant_title`, `status`, `completed_at`) VALUES
(5, 19, 145, 50.00, 30.00, '', 0, '', '', 'in_transit', '2025-04-08 00:15:41'),
(6, 19, 144, 50.00, 90.00, '', 0, '', '', 'in_transit', '2025-04-08 00:18:41'),
(7, 19, 151, 50.00, 90.00, '', 0, '', '', 'in_transit', '2025-04-16 19:59:06'),
(8, 22, 158, 50.00, 180.00, '', 0, '', '', 'in_transit', '2025-04-22 19:55:03'),
(9, 22, 159, 50.00, 90.00, '', 0, '', '', 'in_transit', '2025-04-24 00:40:15'),
(10, 22, 163, 50.00, 200.00, '', 0, '', '', 'in_transit', '2025-05-07 01:29:20'),
(11, 22, 164, 50.00, 600.00, '', 0, '', '', 'in_transit', '2025-05-09 21:20:44'),
(12, 21, 169, 50.00, 60.00, '', 0, '', '', 'in_transit', '2025-06-02 20:52:57'),
(13, 27, 176, 50.00, 84.50, '', 0, '', '', 'in_transit', '2025-06-23 19:29:40'),
(14, 22, 165, 50.00, 450.00, '', 0, '', '', 'in_transit', '2025-06-24 02:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_boy_ratings`
--

CREATE TABLE `delivery_boy_ratings` (
  `rating_id` int(11) NOT NULL,
  `db_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `rating_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_boy_ratings`
--

INSERT INTO `delivery_boy_ratings` (`rating_id`, `db_id`, `u_id`, `order_id`, `rating`, `review`, `rating_date`) VALUES
(5, 19, 41, 151, 5, 'nice ', '2025-04-16 14:30:37'),
(6, 19, 40, 144, 5, 'ss', '2025-04-22 14:08:44'),
(7, 22, 44, 164, 5, 'sda', '2025-05-09 15:51:23');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_boy_requests`
--

CREATE TABLE `delivery_boy_requests` (
  `request_id` int(11) NOT NULL,
  `db_name` varchar(255) NOT NULL,
  `db_phone` varchar(20) NOT NULL,
  `db_email` varchar(255) DEFAULT NULL,
  `db_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `db_photo` varchar(255) DEFAULT NULL,
  `db_password` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT 0.00000000,
  `longitude` decimal(11,8) DEFAULT 0.00000000,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_comment` text DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `driving_license_number` varchar(50) DEFAULT NULL,
  `driving_license_expiry` date DEFAULT NULL,
  `driving_license_photo` varchar(255) DEFAULT NULL,
  `aadhaar_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_boy_requests`
--

INSERT INTO `delivery_boy_requests` (`request_id`, `db_name`, `db_phone`, `db_email`, `db_address`, `city`, `db_photo`, `db_password`, `latitude`, `longitude`, `status`, `request_date`, `admin_comment`, `bank_account_number`, `ifsc_code`, `account_holder_name`, `driving_license_number`, `driving_license_expiry`, `driving_license_photo`, `aadhaar_pdf`) VALUES
(1, 'Arka Maitra', '7896698569', 'ytvillainar@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'kolkata', '', '$2y$10$vXX45RvFl1mDutBodXkZlOHR/xStTVfY5ri9RDR.sA13OAFHwkbty', 22.57264590, 88.36389530, 'pending', '2025-04-22 19:12:57', NULL, '', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', '', ''),
(2, 'Arka Maitra', '4569869856', 'ytvillainar@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'kolkata', '', '$2y$10$qNK8mH0MEddKxGcYYIn7Au5cNnodwmk0P75AI/Dl2F87hChGjONBm', 22.57264590, 88.36389530, 'approved', '2025-04-22 19:18:25', NULL, '', 'SBIN0000691', 'arka maitra', 'WB202022004567', '2026-12-10', '', ''),
(3, 'Arka Maitra', '7823636985', 'ytvillainar@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'berhampore', 'admin/delivery_boy_images/profile_1745350350_6807eece1748e.jpg', '$2y$10$KLMoXY/04MhnDJSSxd4E2OeFetogwBvxwa..vvkvq6YqlteKJA1H2', 19.30596500, 84.80118800, 'approved', '2025-04-22 19:32:30', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745350350_6807eece17781.jpg', 'admin/delivery_boy_images/aadhaar_1745350350_6807eece1787f.pdf'),
(4, 'Amio Das', '7896985896', 'ytvillainar@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', 'admin/delivery_boy_images/profile_1745355969_680804c16866d.png', '$2y$10$uiEw4uPXu7yYyCX98BslCe84A.12eQa2DdkwGZK68rFxaP2oQ5Uiq', 22.57264590, 88.36389530, 'approved', '2025-04-22 21:06:09', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745355969_680804c168a11.png', 'admin/delivery_boy_images/aadhaar_1745355969_680804c168d78.pdf'),
(5, 'Arka Maitra', '8698569856', 'arkamaitra@gmail.come', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', 'admin/delivery_boy_images/profile_1745492442_680a19da37212.jpg', '$2y$10$aqhnSOZaPngk7XrB/hVNnue9ejnkmUf7UEnS6CXAUjmPZ/uJhe1IS', 22.57264590, 88.36389530, 'approved', '2025-04-24 11:00:42', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745492442_680a19da373ea.jpg', 'admin/delivery_boy_images/aadhaar_1745492442_680a19da3763a.pdf'),
(6, 'Arka Maitra', '7896985698', 'mukut@gmail.com', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', 'admin/delivery_boy_images/profile_1745733236_680dc674f17f7.jpg', '$2y$10$qR9stbPXzsgy67aRDAKbhuQxuRg4u7lD9Nt2r5NuqGfwv3bv/yidG', 22.57264590, 88.36389530, 'approved', '2025-04-27 05:53:56', NULL, '456985696', 'SBIN0000691', 'arka maitra', 'WB-20-2022-004567', '2026-12-10', 'admin/delivery_boy_images/license_1745733236_680dc674f1f8f.jpg', 'admin/delivery_boy_images/aadhaar_1745733236_680dc674f23c4.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_cities`
--

CREATE TABLE `delivery_cities` (
  `city_id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_cities`
--

INSERT INTO `delivery_cities` (`city_id`, `city_name`, `is_active`, `added_date`, `latitude`, `longitude`) VALUES
(3, 'kolkata', 1, '2025-04-04 19:32:56', 22.57264590, 88.36389530),
(5, 'berhampore', 1, '2025-04-04 20:44:02', 24.10449270, 88.25106350),
(6, 'nalhati', 1, '2025-04-04 20:45:05', 24.29660750, 87.83530600),
(7, 'murshidabad', 1, '2025-04-04 20:46:13', 24.17459930, 88.27213350),
(8, 'Berhampore, Berhampore, West Bengal, 742101, India', 1, '2025-04-05 17:00:05', 24.10449270, 88.25106350);

-- --------------------------------------------------------

--
-- Table structure for table `dishes`
--

CREATE TABLE `dishes` (
  `d_id` int(222) NOT NULL,
  `rs_id` int(222) NOT NULL,
  `title` varchar(222) NOT NULL,
  `slogan` varchar(222) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `offer_start_date` datetime DEFAULT NULL,
  `offer_end_date` datetime DEFAULT NULL,
  `img` varchar(222) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Available, 0 = Unavailable',
  `diet_type` enum('veg','nonveg','vegan') NOT NULL DEFAULT 'veg'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dishes`
--

INSERT INTO `dishes` (`d_id`, `rs_id`, `title`, `slogan`, `price`, `offer_price`, `offer_start_date`, `offer_end_date`, `img`, `is_available`, `diet_type`) VALUES
(22, 9, 'chiken Biriyani', 'a nonveg food item', 150.00, NULL, NULL, NULL, '67dabb2c8c6c7.jpg', 1, 'veg'),
(23, 11, 'fish', 'sss', 800.00, NULL, NULL, NULL, '67e03f4a65aff.png', 1, 'veg'),
(24, 12, 'chiken Biriyani', 'a nonveg food item', 120.00, NULL, NULL, NULL, '67e6801734aa5.png', 1, 'veg'),
(25, 11, 'chiken Biriyani', 'a nonveg food item', 150.00, NULL, NULL, NULL, '67e681b8b48b1.png', 1, 'veg'),
(26, 11, 'Fried Rice ', 'indian ', 50.00, NULL, NULL, NULL, '67e68204512a5.png', 1, 'veg'),
(27, 12, 'Fried Rice ', 'indian ', 50.00, NULL, NULL, NULL, '67e6821d2be92.png', 1, 'veg'),
(28, 17, 'chowmin', 'chow', 70.00, NULL, NULL, NULL, '67e9aeac9e53f_menu-2.png', 1, 'veg'),
(29, 24, 'chowmin', 'chow', 70.00, NULL, NULL, NULL, '67e9bc36613b5_menu-2.png', 1, 'veg'),
(30, 26, 'coca cola', 'drink', 30.00, NULL, NULL, NULL, 'dish_68596aaa1fada2.01542399.jpg', 1, 'vegan'),
(31, 32, 'fried rice', 'good food', 200.00, 160.00, '2025-06-02 23:32:00', '2025-06-04 23:32:00', '67eea18daf384_WhatsApp Image 2025-04-03 at 20.24.37_0f7ba807.jpg', 1, 'veg'),
(32, 35, 'Fish Kalia ', 'A rich Bengali fish curry made with fried fish cooked in a spicy, aromatic onion-tomato gravy. Often served on special occasions, it pairs perfectly with steamed rice or pulao.', 90.00, 60.00, '2025-06-18 21:17:00', '2025-06-26 21:55:00', 'dish_6807858c934967.22982466.jpg', 1, 'nonveg'),
(33, 26, 'chowmein', 'food', 180.00, 150.00, '2025-06-23 20:31:00', '2025-07-23 20:31:00', 'dish_68596c4531b843.85374975.jpg', 1, 'nonveg'),
(34, 44, 'Paneer Tikka Masala', 'Marinated paneer (Indian cheese) in a creamy tomato sauce.', 180.00, 169.00, '2025-06-20 23:56:00', '2025-06-28 23:57:00', 'dish_6855a85b7ea183.89372387.jpg', 1, 'veg'),
(35, 46, 'Basanti Pulao	', 'A fragrant Bengali yellow rice dish made with gobindobhog rice, ghee, cashews, raisins, and a touch of sugar and saffron — perfect for festive occasions.', 130.00, 100.00, '2025-06-23 01:49:00', '2025-07-23 01:49:00', 'dish_68586583144bc5.23647080.jpg', 1, 'veg'),
(36, 46, 'Alu Posto	', ' A classic Bengali vegetarian delicacy made with tender potatoes cooked in a creamy poppy seed (posto) paste, flavored with mustard oil and green chilies — simple yet comforting.', 89.00, 59.00, '2025-06-23 01:53:00', '2025-07-23 01:53:00', 'dish_6858665b282071.02826582.jpg', 1, 'veg'),
(37, 45, 'Thai Green Curry ', 'A rich and aromatic Thai curry made with green curry paste, coconut milk, seasonal vegetables, tofu, Thai basil, and kaffir lime leaves. Served hot with a side of jasmine rice. Mildly spicy and full of exotic flavor.', 220.00, 199.00, '2025-06-23 01:59:00', '2025-07-23 01:59:00', 'dish_685867fe8eaa40.78546208.jpg', 1, 'veg'),
(38, 30, 'rasgulla', 'Rasgulla is a syrupy dessert popular in the eastern part of south asia', 20.00, 15.00, '2025-06-23 20:11:00', '2025-08-23 20:12:00', 'dish_6859680a397d38.67875930.jpg', 1, 'veg'),
(39, 43, 'pasta', 'Pasta is a staple in Italian cuisine, made from durum wheat semolina.', 120.00, 100.00, '2025-06-23 20:22:00', '2025-07-23 20:22:00', 'dish_68596a51e38fd8.84604681.jpg', 1, 'veg'),
(42, 46, 'Mutton curry', 'A classic It\'s a flavorful and aromatic dish made with tender mutton pieces cooked in a rich, spicy gravy.', 200.00, 180.00, '2025-06-23 21:01:00', '2025-07-23 21:01:00', 'dish_6859737053fbf7.47107523.jpg', 1, 'nonveg'),
(43, 46, 'shorshe Hilish', 'A classic Bengali dish, Shorshe Hilish is a flavorful mustard-based curry made with Hilsa fish.', 150.00, 120.00, '2025-06-23 21:08:00', '2025-06-30 21:08:00', 'dish_685975366373e2.37736497.jpg', 1, 'nonveg');

-- --------------------------------------------------------

--
-- Table structure for table `footer_settings`
--

CREATE TABLE `footer_settings` (
  `id` int(11) NOT NULL,
  `payment_options` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_settings`
--

INSERT INTO `footer_settings` (`id`, `payment_options`, `address`, `phone`, `additional_info`) VALUES
(1, 'Cash on delivery ', 'Ward No. 11, Nalhati Municipality\r\nBlock: Nalhati-1\r\nDistrict: Birbhum\r\nWest Bengal, India\r\nPIN: 731243 \r\n', '7898569856', 'Welcome to Bhojon Barta!\r\n\r\nBhojon Barta is a food ordering platform proudly developed by Arka Maitra, a Diploma student of the Computer Science and Technology (CST) department. This platform makes it easy and convenient to order your favorite meals — from quick snacks to full-course dishes — all with just a few clicks.');

-- --------------------------------------------------------

--
-- Table structure for table `order_messages`
--

CREATE TABLE `order_messages` (
  `message_id` int(11) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `u_id` int(11) NOT NULL,
  `rs_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_messages`
--

INSERT INTO `order_messages` (`message_id`, `order_id`, `u_id`, `rs_id`, `message`, `created_at`) VALUES
(1, '68178bfa7637a', 40, 32, 'not show many spycy', '2025-05-04 15:47:06'),
(2, '68178bfa78654', 40, 35, 'not show many spycy', '2025-05-04 15:47:06'),
(3, '681e23a02b6f3', 44, 32, 'hiiiii', '2025-05-09 15:47:44'),
(4, '681e23a02d91e', 44, 35, 'hallo', '2025-05-09 15:47:44'),
(5, '68595c3b731bf', 42, 44, 'spicey', '2025-06-23 13:52:59'),
(6, '6859bcc4c6917', 44, 46, 'not show spycy', '2025-06-23 20:44:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_ratings`
--

CREATE TABLE `order_ratings` (
  `rating_id` int(11) NOT NULL,
  `o_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_ratings`
--

INSERT INTO `order_ratings` (`rating_id`, `o_id`, `u_id`, `rating`, `review`, `created_at`) VALUES
(8, 140, 40, 5, 'nice', '2025-04-07 18:59:44'),
(9, 151, 41, 3, 'nice ', '2025-04-16 14:30:37'),
(10, 144, 40, 5, 'ss', '2025-04-22 14:08:44'),
(11, 164, 44, 5, 'sda', '2025-05-09 15:51:23');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_requests`
--

CREATE TABLE `order_status_requests` (
  `request_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `delivery_boy_id` int(11) NOT NULL,
  `requested_status` varchar(50) NOT NULL,
  `request_time` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `db_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remark`
--

CREATE TABLE `remark` (
  `id` int(11) NOT NULL,
  `frm_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `remark` mediumtext NOT NULL,
  `remarkDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `remark`
--

INSERT INTO `remark` (`id`, `frm_id`, `status`, `remark`, `remarkDate`) VALUES
(1, 2, 'in process', 'none', '2022-05-01 05:17:49'),
(2, 3, 'in process', 'none', '2022-05-27 11:01:30'),
(3, 2, 'closed', 'thank you for your order!', '2022-05-27 11:11:41'),
(4, 3, 'closed', 'none', '2022-05-27 11:42:35'),
(5, 4, 'in process', 'none', '2022-05-27 11:42:55'),
(6, 1, 'rejected', 'none', '2022-05-27 11:43:26'),
(7, 7, 'in process', 'none', '2022-05-27 13:03:24'),
(8, 8, 'in process', 'none', '2022-05-27 13:03:38'),
(9, 9, 'rejected', 'thank you', '2022-05-27 13:03:53'),
(10, 7, 'closed', 'thank you for your ordering with us', '2022-05-27 13:04:33'),
(11, 8, 'closed', 'thanks ', '2022-05-27 13:05:24'),
(12, 5, 'closed', 'none', '2022-05-27 13:18:03'),
(13, 18, 'in process', 'wait 5 min', '2025-03-07 09:21:02'),
(14, 18, 'closed', 'done', '2025-03-07 09:31:05'),
(15, 19, 'closed', 'done', '2025-03-07 10:08:18'),
(16, 18, 'rejected', 'sbhbdc', '2025-03-07 10:38:35'),
(17, 44, 'closed', 'done', '2025-03-09 11:10:55'),
(18, 56, 'in process', 'wait', '2025-03-10 10:30:31'),
(19, 59, 'in process', 'done\r\n', '2025-03-12 18:45:18'),
(20, 59, 'closed', 'done ', '2025-03-12 19:17:19'),
(21, 64, 'closed', 'done', '2025-03-18 12:34:07'),
(22, 66, 'closed', 'done', '2025-03-18 12:54:22'),
(23, 67, 'in process', 'wait', '2025-03-19 08:22:05'),
(24, 67, 'closed', 'done', '2025-03-19 08:33:05'),
(25, 66, 'in process', 'wait', '2025-03-19 08:45:15'),
(26, 66, 'rejected', 'sory', '2025-03-19 08:46:28'),
(27, 66, 'in process', 'wait', '2025-03-19 08:47:05'),
(28, 70, 'in process', 'wait', '2025-03-19 09:07:59'),
(29, 70, 'closed', 'aaa', '2025-03-19 09:12:17'),
(30, 70, 'in process', 'sss', '2025-03-19 09:21:58'),
(31, 70, 'delivered', 'Updated by Delivary Boy', '2025-03-19 09:28:08'),
(32, 70, 'closed', 'hh', '2025-03-19 09:33:56'),
(33, 0, 'outfordelivery', 'aaaa', '2025-03-19 09:42:18'),
(34, 0, 'pickedup', 'aaa', '2025-03-19 09:42:39'),
(35, 70, 'pickedup', 'Updated by Delivary Boy', '2025-03-19 09:42:49'),
(36, 0, 'outfordelivery', 'aaa', '2025-03-19 09:43:16'),
(37, 0, 'cancelled', 'bb', '2025-03-19 10:03:40'),
(38, 0, 'cancelled', 'bb', '2025-03-19 10:03:40'),
(39, 0, 'outfordelivery', 'hhh', '2025-03-19 10:03:45'),
(40, 0, 'outfordelivery', 'hhh', '2025-03-19 10:03:45'),
(41, 71, 'in process', 'nn', '2025-03-22 17:07:11'),
(42, 71, 'in process', 'xx', '2025-03-22 17:14:27'),
(43, 71, 'in process', 'zz', '2025-03-22 17:40:40'),
(44, 74, 'in process', 'aa', '2025-03-22 18:41:17'),
(45, 74, 'closed', 'k', '2025-03-22 21:57:44'),
(46, 76, 'rejected', 'a', '2025-03-23 07:51:55'),
(47, 77, 'closed', 'b', '2025-03-23 08:04:40'),
(48, 89, 'closed', 'aa', '2025-03-23 16:48:18'),
(49, 93, 'closed', 'a', '2025-03-26 09:03:42'),
(50, 93, 'in process', 'aaa', '2025-03-26 10:02:24'),
(51, 89, 'closed', 'aa', '2025-03-26 10:22:17'),
(52, 94, 'closed', 'm', '2025-03-26 10:27:30'),
(53, 93, 'rejected', 'm', '2025-03-26 10:28:05'),
(54, 105, 'in process', 'z', '2025-04-01 12:23:50'),
(55, 105, 'closed', 'm', '2025-04-01 12:25:31'),
(56, 107, 'closed', 'a', '2025-04-01 13:12:57'),
(57, 140, 'closed', 'aa', '2025-04-07 18:36:47');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant`
--

CREATE TABLE `restaurant` (
  `rs_id` int(222) NOT NULL,
  `c_id` int(222) NOT NULL,
  `title` varchar(222) NOT NULL,
  `email` varchar(222) NOT NULL,
  `phone` varchar(222) NOT NULL,
  `o_hr` varchar(222) NOT NULL,
  `c_hr` varchar(222) NOT NULL,
  `o_days` varchar(222) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `diet_type` varchar(255) NOT NULL DEFAULT 'all',
  `image` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `owner_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `fssai_license` varchar(255) DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Open, 0 = Closed',
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `restaurant`
--

INSERT INTO `restaurant` (`rs_id`, `c_id`, `title`, `email`, `phone`, `o_hr`, `c_hr`, `o_days`, `address`, `city`, `diet_type`, `image`, `date`, `owner_id`, `url`, `latitude`, `longitude`, `fssai_license`, `is_open`, `bank_account_number`, `ifsc_code`, `account_holder_name`) VALUES
(26, 3, ' Golden Wok', 'ytvillainar@gmail.com', '01111111111', '9am', '8pm', 'Mon-Fri', 'kolkata', 'Kolkata', 'all', 'image_6858553bdc128.jpg', '2025-06-23 15:59:58', 17, 'https://www.att.com/', 22.57264590, 88.36389530, '', 1, NULL, NULL, NULL),
(30, 5, 'Grill Nation', 'ytvillainar@gmail.com', '07896587458', '9am', '9pm', 'Mon-Fri', 'kolkata', 'Kolkata', 'all', 'image_685859d05d03f.jpg', '2025-06-23 16:00:01', 17, '', 22.57264590, 88.36389530, '', 1, NULL, NULL, NULL),
(32, 1, ' EuroFlame', 'ytvillainar@gmail.com', '7898745896', '12pm', '11pm', '24hr-x7', 'kolkata', 'kolkata', 'all', 'image_68585a3d56eb8.jpg', '2025-06-23 16:00:04', 17, '', 22.57435450, 88.36287340, '', 1, NULL, NULL, NULL),
(35, 5, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '10am', '1am', 'Mon-Tue', 'kolkata', 'kolkata', 'all', 'image_6856a5bd09ea7.jpg', '2025-06-23 16:00:07', 17, '', 22.57264590, 88.36389530, 'fssai_6803befc9b1d6.pdf', 1, NULL, NULL, NULL),
(43, 2, 'The Olive Oven', 'ytvillainar@gmail.com', '01111111111', '8am', '9pm', 'Mon-Sat', 'kolkata', 'Kolkata', 'all', 'image_68585790956ed.jpg', '2025-06-23 16:00:10', 17, '', 22.57264590, 88.36389530, '0', 1, NULL, NULL, NULL),
(44, 5, 'pabna hindu hotel', 'ytvillainar@gmail.com', '01111111111', '7am', '12am', '24hr-x7', 'kolkata', 'Kolkata', 'veg', 'res_23_1750443508_6855a5f47c154.jpg', '2025-06-23 16:00:08', 23, '', 22.57264590, 88.36389530, 'fssai_23_1750443508_6855a5f47f398.pdf', 1, NULL, NULL, NULL),
(45, 8, 'Bangkok Essence', 'ytvillainar@gmail.com', '4569874589', '6am', '12am', '24hr-x7', 'kolkata', 'Kolkata', 'all', 'res_22_1750622833_685862710f828.jpg', '2025-06-23 16:01:03', 22, '', 22.57264590, 88.36389530, 'fssai_22_1750622833_6858627113ca4.pdf', 1, NULL, NULL, NULL),
(46, 9, 'Shonar Bangla Bhojanaloy', 'ytvillainar@gmail.com', '01111111111', '6am', '12am', '24hr-x7', 'kolkata salt lake', 'Kolkata', 'all', 'res_22_1750623243_6858640bc7727.jpg', '2025-06-23 16:01:06', 22, '', 22.57311150, 88.40308910, 'fssai_22_1750623243_6858640bcc380.pdf', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_owners`
--

CREATE TABLE `restaurant_owners` (
  `owner_id` int(11) NOT NULL,
  `email` varchar(222) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expiry` datetime DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_owners`
--

INSERT INTO `restaurant_owners` (`owner_id`, `email`, `password`, `reset_otp`, `reset_otp_expiry`, `bank_account_number`, `ifsc_code`, `account_holder_name`, `created_at`) VALUES
(1, 'test@gmail.com', 'temp_password', NULL, NULL, NULL, NULL, NULL, '2025-03-30 15:16:09'),
(2, 'abcd@gmail.com', 'temp_password', NULL, NULL, NULL, NULL, NULL, '2025-03-30 15:16:09'),
(7, 'owner1@example.com', 'test123', NULL, NULL, NULL, NULL, NULL, '2025-03-30 15:24:55'),
(8, 'owner2@example.com', 'test456', NULL, NULL, NULL, NULL, NULL, '2025-03-30 15:24:55'),
(17, 'ytvillainar@gmail.com', '$2y$10$9x1TFWfJ4t8WlTBuSeA36O54vqm8ujk74e.JXd6brVCBoPi4GiQru', '593163', '2025-04-24 05:17:41', NULL, NULL, NULL, '2025-03-30 22:42:26'),
(19, 'amiya2025@gmail.com', '$2y$10$fKpY2LpRg2/9RaZBcw.EzeKkTjuR4aOCdvxVbXAN93FEMdovi6fRm', NULL, NULL, NULL, NULL, NULL, '2025-04-03 08:15:21'),
(21, 'abc@gmail.com', '$2y$10$HLtIRJQzQFxh4eTBPw2dpeFRCkly.1kOC9keTeWwy1ZU2hJb6IdoK', NULL, NULL, '5263656523', 'SBIN0000691', 'arka maitra', '2025-04-23 20:37:06'),
(22, 'arkamaitra001@gmail.com', '$2y$10$aJUgE/BDqW4EUVuMSm4un.uZ5XS719m1xBFQJhLlZ0SnGN2SQSkgq', NULL, NULL, '5263656523', 'SBIN0001234', 'arka maitra', '2025-04-24 10:26:14'),
(23, 'popola6270@ancewa.com', '$2y$10$K1dMAvsFiQhghkNrRhhDBeWCcdkc26ZSIk3dIgmw42fKtJr2J9LgG', NULL, NULL, '5263656523', 'SBIN0000691', 'arka maitra', '2025-04-28 10:25:34');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_owner_requests`
--

CREATE TABLE `restaurant_owner_requests` (
  `request_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `restaurant_name` varchar(255) NOT NULL,
  `restaurant_photo` varchar(255) DEFAULT NULL,
  `fssai_license` varchar(255) DEFAULT NULL,
  `aadhar_card` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_owner_requests`
--

INSERT INTO `restaurant_owner_requests` (`request_id`, `name`, `email`, `phone`, `restaurant_name`, `restaurant_photo`, `fssai_license`, `aadhar_card`, `address`, `city`, `latitude`, `longitude`, `password`, `status`, `admin_comment`, `bank_account_number`, `ifsc_code`, `account_holder_name`, `request_date`, `category_id`) VALUES
(1, 'Arka Maitra', 'mukutmaitra@gmail.com', '01111111111', '', '../admin/Owner_docs/IMG-20250416-WA0004_6809188786b005.59050114.jpg', '../admin/Owner_docs/DOC-20250416-WA0001_6809188786d915.35189510.pdf', '../admin/Owner_docs/DOC-20250422-WA0007_68091887870078.92272500.pdf', '17, Park Street, Kolkata, West Bengal 700016', '0', 22.55413910, 88.35158750, '$2y$10$EOSBk9.WibQdAtckB0z9zePtaQKqgJbGdpN0fIIfmxVDCdZAwmBoe', 'approved', 'Approved by Admin ID: 15', NULL, NULL, NULL, '2025-04-23 16:42:47', 1),
(2, 'Arka Maitra', 'abc@gmail.com', '01111111111', '', 'admin/Owner_docs/IMG-20250422-WA0001_6809467ee27113.63284666.jpg', 'admin/Owner_docs/DOC-20250422-WA0001_6809467ee2d066.06566780.pdf', 'admin/Owner_docs/DOC-20250422-WA0001_6809467ee2ea97.91940096.pdf', 'kolkata', 'Kolkata', 22.57264590, 88.36389530, '$2y$10$HLtIRJQzQFxh4eTBPw2dpeFRCkly.1kOC9keTeWwy1ZU2hJb6IdoK', 'approved', '', '5263656523', 'SBIN0000691', 'arka maitra', '2025-04-23 19:58:54', 3),
(10, 'Arka Maitra', 'popola6270@ancewa.com', '7896985698', '', 'admin/Owner_docs/67cd740de3f21_Screenshot 2024-11-28 204142_680f578e44bbf5.56725874.png', 'admin/Owner_docs/DOC-20250426-WA0014_680f578e459508.54885377.pdf', 'admin/Owner_docs/DOC-20250426-WA0014_680f578e45dec2.96057757.pdf', 'kolkata', 'Kolkata', 22.57264590, 88.36389530, '$2y$10$9VaToS3WiDgcRuDKHA4y0OFCpzNQ0NyNeY/nl86Wx2m.A7DdyyN5K', 'approved', 'Approved via dashboard.', '5263656523', 'SBIN0000691', 'arka maitra', '2025-04-28 10:25:18', 5),
(16, 'Raj Roy', 'arkamaitra001@gmail.com', '7896985698', '', 'admin/Owner_docs/WhatsApp Image 2025-06-23 at 01.14.08_045ada23_685860f833b812.44288103.jpg', 'admin/Owner_docs/All Restaurant Owners - Admin_685860f8346392.38805356.pdf', 'admin/Owner_docs/All Restaurant Owners - Admin_685860f8349248.93278935.pdf', 'kolkata', 'Kolkata', 22.57264590, 88.36389530, '$2y$10$8zqJjWnK1su1vqqq1X5OQecbjr4C6g/toG5nJr3NfLuPmuv9/bTGO', 'pending', NULL, '5263656523', 'SBIN0001234', 'Arka maitra', '2025-06-22 20:00:56', 8);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_ratings`
--

CREATE TABLE `restaurant_ratings` (
  `rating_id` int(11) NOT NULL,
  `rs_id` int(222) NOT NULL,
  `u_id` int(222) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `rating_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_ratings`
--

INSERT INTO `restaurant_ratings` (`rating_id`, `rs_id`, `u_id`, `rating`, `review`, `rating_date`) VALUES
(1, 26, 40, 5, 'nice', '2025-04-07 18:59:44'),
(2, 32, 41, 5, 'nice ', '2025-04-16 14:30:37'),
(3, 32, 40, 5, 'ss', '2025-04-22 14:08:44'),
(4, 32, 44, 5, 'DDx', '2025-05-09 15:51:23');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_requests`
--

CREATE TABLE `restaurant_requests` (
  `request_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `o_hr` varchar(10) NOT NULL,
  `c_hr` varchar(10) NOT NULL,
  `o_days` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `url` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `fssai_license` varchar(255) DEFAULT NULL,
  `aadhar_card` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `diet_type` varchar(255) NOT NULL DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_requests`
--

INSERT INTO `restaurant_requests` (`request_id`, `owner_id`, `c_id`, `title`, `email`, `phone`, `o_hr`, `c_hr`, `o_days`, `address`, `image`, `status`, `request_date`, `url`, `latitude`, `longitude`, `fssai_license`, `aadhar_card`, `city`, `diet_type`) VALUES
(50, 17, 3, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '8am', '9pm', 'Mon-Sat', 'kolkata', 'image_6802a45b42a647.19756429.jpg', 'approved', '2025-04-18 19:13:31', '', 22.57264590, 88.36389530, 'fssai_6802a45b42a6f9.42258985.pdf', NULL, 'Kolkata', 'all'),
(51, 17, 3, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '8am', '9pm', 'Mon-Sat', 'kolkata', 'image_6802a8e90f285.jpg', 'approved', '2025-04-18 19:32:57', '', 22.57264590, 88.36389530, '0', NULL, 'Kolkata', 'all'),
(52, 17, 3, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '8am', '9pm', 'Mon-Sat', 'kolkata', 'image_6802a923cde2d.jpg', 'approved', '2025-04-18 19:33:55', '', 22.57264590, 88.36389530, '0', NULL, 'Kolkata', 'all'),
(53, 17, 1, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '12pm', '5pm', 'Mon-Thu', 'kolkata', 'image_6802aba07e3b9.jpg', 'approved', '2025-04-18 19:44:32', '', 22.57264590, 88.36389530, 'fssai_6802aba07e3c1.pdf', NULL, 'Kolkata', 'all'),
(54, 17, 2, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '10am', '1am', 'Mon-Tue', 'kolkata', 'image_6803befc9b1cd.jpg', 'approved', '2025-04-19 15:19:24', '', 22.57264590, 88.36389530, 'fssai_6803befc9b1d6.pdf', NULL, 'Kolkata', 'all'),
(55, 17, 1, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '8am', '1am', 'Mon-Sun', 'kolkata', 'res_17_6809550567e30.jpg', 'approved', '2025-04-23 21:00:53', '', 22.57264590, 88.36389530, 'fssai_17_68095505685e2.pdf', NULL, 'Kolkata', 'all'),
(56, 21, 5, 'ma tara hindu hotel', 'abc@gmail.com', '01111111111', '6am', '4pm', 'Mon-Sat', 'kolkata', 'res_21_6809557545c49.jpg', 'approved', '2025-04-23 21:02:45', '', 22.57264590, 88.36389530, 'fssai_21_6809557548460.pdf', NULL, 'Kolkata', 'all'),
(57, 17, 4, 'Bonedi hensel', 'ytvillainar@gmail.com', '01111111111', '9pm', '5am', 'Mon-Fri', 'kolkata', 'res_17_1745493894_680a1f8688c7a.jpg', 'approved', '2025-04-24 11:24:54', '', 22.57264590, 88.36389530, 'fssai_17_1745493894_680a1f868a6be.pdf', NULL, 'Kolkata', 'all'),
(58, 22, 5, 'sobar hensel', 'arkamaitra001@gmail.com', '7896985698', '8am', '12am', '24hr-x7', 'berhampore westbengal 742101', 'res_22_1745610504_680be7088cc78.png', 'approved', '2025-04-25 19:48:24', '', 24.10449270, 88.25106350, 'fssai_22_1745610504_680be7088e523.pdf', NULL, 'Berhampore', 'all'),
(59, 22, 5, 'soabr hensel', 'arkamaitra001@gmail.com', '7896985698', '8am', '11pm', '24hr-x7', 'berhampore westbengal 742101', 'res_22_1745611012_680be904976fb.jpg', 'approved', '2025-04-25 19:56:52', '', 24.10449270, 88.25106350, 'fssai_22_1745611012_680be90498925.pdf', NULL, 'Berhampore', 'veg'),
(60, 22, 5, 'pabna hindu hotel', 'arkamaitra001@gmail.com', '7896985698', '6am', '12pm', 'Mon-Fri', 'berhampur westbengal 742101', 'res_22_1745611927_680bec97e97fb.jpg', 'rejected', '2025-04-25 20:12:07', '', 24.10449270, 88.25106350, 'fssai_22_1745611927_680bec97eb44d.pdf', NULL, 'Berhampore', 'all'),
(61, 22, 4, 'Olive', 'arkamaitra001@gmail.com', '7859856985', '7am', '5pm', '24hr-x7', 'berhampur westbengal 742101', 'res_22_1745613559_680bf2f7c9919.jpg', 'approved', '2025-04-25 20:39:19', '', 24.10449270, 88.25106350, 'fssai_22_1745613559_680bf2f7cc96b.pdf', NULL, 'Berhampore', 'veg'),
(62, 23, 5, 'pabna hindu hotel', 'ytvillainar@gmail.com', '01111111111', '7am', '12am', '24hr-x7', 'kolkata', 'res_23_1750443508_6855a5f47c154.jpg', 'approved', '2025-06-20 18:18:28', '', 22.57264590, 88.36389530, 'fssai_23_1750443508_6855a5f47f398.pdf', NULL, 'Kolkata', 'veg'),
(63, 22, 8, 'Bangkok Essence', 'ytvillainar@gmail.com', '4569874589', '6am', '12am', '24hr-x7', 'kolkata', 'res_22_1750622833_685862710f828.jpg', 'approved', '2025-06-22 20:07:13', '', 22.57264590, 88.36389530, 'fssai_22_1750622833_6858627113ca4.pdf', NULL, 'Kolkata', 'all'),
(64, 22, 9, 'Shonar Bangla Bhojanaloy', 'ytvillainar@gmail.com', '01111111111', '6am', '12am', '24hr-x7', 'kolkata salt lake', 'res_22_1750623243_6858640bc7727.jpg', 'approved', '2025-06-22 20:14:03', '', 22.57311150, 88.40308910, 'fssai_22_1750623243_6858640bcc380.pdf', NULL, 'Kolkata', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `res_category`
--

CREATE TABLE `res_category` (
  `c_id` int(222) NOT NULL,
  `c_name` varchar(222) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `res_category`
--

INSERT INTO `res_category` (`c_id`, `c_name`, `date`, `image`) VALUES
(1, 'Continental', '2025-06-22 19:41:27', 'WhatsApp Image 2025-06-23 at 01.09.53_ec179c5d.jpg'),
(2, 'Italian', '2025-06-23 13:39:11', 'WhatsApp Image 2025-06-23 at 19.08.34_ed551ae4.jpg'),
(3, 'Chinese', '2025-03-27 19:25:57', 'menu-2.png'),
(4, 'American', '2025-03-27 19:25:35', 'menu-8.png'),
(5, 'indian', '2025-06-22 19:44:50', 'WhatsApp Image 2025-06-23 at 01.14.08_045ada23.jpg'),
(8, ' Thai', '2025-06-22 19:37:47', 'WhatsApp Image 2025-06-23 at 01.07.11_18b6fe46.jpg'),
(9, 'Bengali', '2025-06-22 19:39:11', 'WhatsApp Image 2025-06-23 at 01.08.53_b56f6317.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'free_delivery_threshold', '1000', '2025-03-17 22:12:40', '2025-03-17 22:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` int(222) NOT NULL,
  `username` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `f_name` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l_name` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `otp` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expiry` int(11) DEFAULT NULL,
  `status` int(222) NOT NULL DEFAULT 1,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `is_veg_mode` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Off, 1=On'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `username`, `name`, `f_name`, `l_name`, `email`, `phone`, `password`, `address`, `city`, `otp`, `otp_expiry`, `status`, `date`, `verification_token`, `email_verified`, `latitude`, `longitude`, `is_veg_mode`) VALUES
(34, 'user34', NULL, 'John', 'Doe', 'john.doe@example.com', '1234567890', 'Temp@123', 'Some Address', NULL, NULL, NULL, 1, '2025-04-26 19:38:19', NULL, 1, NULL, NULL, 0),
(36, 'user36', NULL, 'Jane', 'Smith', 'jane.smith@example.com', '0987654321', 'Temp@123', 'Another Address', NULL, NULL, NULL, 1, '2025-03-30 20:43:43', NULL, 1, NULL, NULL, 0),
(40, 'raj001', NULL, 'Arka', 'Maitra', 'mukutmaitra@gmail.com', '7896587485', 'Arka@123', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', '$2y$10$G65ue7Sp6/jdnlFM84Jqn.0CpsONpxCGos0kU858R8YoybERqdWku', 1745449166, 1, '2025-04-30 09:20:03', 'c9bef19137da83c443704088f8afa64599d8eb8750b73dbe06c11cd310165759', 1, 22.57264590, 88.36389530, 0),
(41, 'raj111', NULL, 'Arka', 'Maitra', '', '7896987458', 'Arka@123', 'Berhampore, Berhampore, West Bengal, 742101, India', 'Berhampore', '388593', NULL, 1, '2025-04-24 13:45:18', '0e31824cec9d5c3ee9e5c286a24996d8ef830e4c443bc73a578e9817bb47f57e', 1, 24.10449270, 88.25106350, 0),
(42, 'aaa111', NULL, 'aaa', 'aaa', 'bikkyb2020@gmail.com', '5469874589', 'Bikky@2025', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', NULL, NULL, 1, '2025-06-23 14:18:05', '01a9baec15b363b121349b21643642d4ff9e641c6eb5a1c55f3d32c4b90e3d1f', 1, 22.57264590, 88.36389530, 0),
(44, 'arka0001', NULL, 'Arka', 'Maitra', 'ytvillainar@gmail.com', '8670247168', '$2y$10$9d1xGH7Ckc6HYKmbKPG0TeLwPrl8eVeeRbiZtpKL.3TeRQMjnnrwq', 'Kolkata, Kolkata, West Bengal, India', 'Kolkata', NULL, NULL, 1, '2025-06-24 08:42:00', '009612206c9ad15513f9241d8119d819f97f09fa5681a825c19521d9df5d4fdd', 1, 22.57264590, 88.36389530, 1),
(45, 'raj222', NULL, 'Arka', 'Maitra', 'litopo6865@claspira.com', '7896985698', 'Arka@123', 'kolkata', 'Kolkata', NULL, NULL, 1, '2025-06-20 13:36:03', 'a04ca22a705990920e663f5ac1657dc995cd519fba265e0457855fbc3dd3952f', 1, 22.57264590, 88.36389530, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_orders`
--

CREATE TABLE `users_orders` (
  `o_id` int(222) NOT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `u_id` int(222) NOT NULL,
  `title` varchar(222) NOT NULL,
  `quantity` int(222) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','preparing','ready_for_pickup','assigned','in process','delivered','closed','rejected') DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_option` varchar(255) DEFAULT NULL,
  `delivery_boy_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT 0,
  `review` text DEFAULT NULL,
  `rs_id` int(11) DEFAULT NULL,
  `customer_lat` decimal(10,8) DEFAULT NULL,
  `customer_lon` decimal(11,8) DEFAULT NULL,
  `delivery_charge` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users_orders`
--

INSERT INTO `users_orders` (`o_id`, `order_id`, `u_id`, `title`, `quantity`, `price`, `status`, `rejection_reason`, `date`, `payment_option`, `delivery_boy_id`, `rating`, `review`, `rs_id`, `customer_lat`, `customer_lon`, `delivery_charge`, `total_amount`, `coupon_code`) VALUES
(140, '67f02ce8ab493', 40, 'coca cola', 1, 30.00, 'closed', NULL, '2025-04-07 18:36:47', NULL, NULL, 0, NULL, 26, 22.57264590, 88.36389530, 50.00, 80.00, NULL),
(141, '67f1873b80f8f', 37, 'fried rice', 31, 1395.00, 'rejected', NULL, '2025-04-07 18:51:00', NULL, NULL, 0, NULL, 32, 24.10449270, 88.25106350, 0.00, 1395.00, '1111'),
(142, '67f188be7125b', 37, 'coca cola', 1, 30.00, NULL, NULL, '2025-04-05 19:47:10', NULL, NULL, 0, NULL, 26, 24.10449270, 88.25106350, 50.00, 80.00, NULL),
(143, '67f18d43e5854', 37, 'fried rice', 2, 90.00, 'rejected', NULL, '2025-04-07 18:50:55', NULL, NULL, 0, NULL, 32, 24.10449270, 88.25106350, 50.00, 140.00, '1111'),
(144, '67f41cee628df', 40, 'fried rice', 1, 90.00, 'closed', NULL, '2025-04-07 18:49:00', NULL, 19, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 140.00, NULL),
(145, '67f41cee650e7', 40, 'coca cola', 1, 30.00, 'closed', NULL, '2025-04-07 18:45:53', NULL, 19, 0, NULL, 26, 22.57264590, 88.36389530, 0.00, 30.00, NULL),
(146, '67ffb43c80629', 37, 'coca cola', 1, 30.00, 'assigned', NULL, '2025-04-19 18:49:11', NULL, 23, 0, NULL, 26, 24.10449270, 88.25106350, 50.00, 80.00, NULL),
(147, '67ffb64899274', 41, 'fried rice', 1, 90.00, NULL, NULL, '2025-04-16 13:53:12', NULL, NULL, 0, NULL, 32, 24.10449270, 88.25106350, 50.00, 140.00, NULL),
(148, '67ffb87da6d6c', 41, 'fried rice', 1, 90.00, NULL, NULL, '2025-04-16 14:02:37', NULL, NULL, 0, NULL, 32, 24.10449270, 88.25106350, 50.00, 140.00, NULL),
(149, '67ffb8dbcf616', 41, 'coca cola', 1, 30.00, 'accepted', NULL, '2025-04-19 18:48:52', NULL, NULL, 0, NULL, 26, 24.10449270, 88.25106350, 50.00, 80.00, NULL),
(150, '67ffb8dbd00c7', 41, 'fried rice', 1, 90.00, NULL, NULL, '2025-04-16 14:04:11', NULL, NULL, 0, NULL, 32, 24.10449270, 88.25106350, 0.00, 90.00, NULL),
(151, '67ffbd8e5c58c', 41, 'fried rice', 1, 90.00, 'closed', NULL, '2025-04-16 14:29:59', NULL, 19, 0, NULL, 32, 24.08435360, 88.27209280, 50.00, 140.00, NULL),
(152, '67ffd4e52b191', 41, 'coca cola', 1, 30.00, 'rejected', NULL, '2025-04-19 18:29:26', NULL, 23, 0, NULL, 26, 24.08435360, 88.27209280, 50.00, 80.00, NULL),
(153, '67ffd4e52c84a', 41, 'fried rice', 3, 270.00, NULL, NULL, '2025-04-16 16:03:49', NULL, NULL, 0, NULL, 32, 24.08435360, 88.27209280, 0.00, 270.00, NULL),
(154, '6803d7b162888', 40, 'fried rice', 6, 540.00, 'rejected', NULL, '2025-04-19 17:10:22', NULL, 22, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 590.00, NULL),
(155, '6803d9792b7a3', 41, 'coca cola', 1, 30.00, 'rejected', NULL, '2025-04-19 18:29:13', NULL, 23, 0, NULL, 26, 24.10449270, 88.25106350, 50.00, 80.00, NULL),
(156, '6803eba7aa69e', 41, 'coca cola', 1, 30.00, 'rejected', NULL, '2025-04-19 18:48:36', NULL, 23, 0, NULL, 26, 24.10449270, 88.25106350, 50.00, 80.00, NULL),
(157, '6807a2d000fda', 40, 'fried rice', 1, 90.00, NULL, NULL, '2025-04-22 14:08:16', NULL, NULL, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 140.00, NULL),
(158, '6807a2d0017e2', 40, 'Fish Kalia ', 2, 180.00, 'closed', NULL, '2025-04-22 14:25:15', NULL, 22, 0, NULL, 35, 22.57264590, 88.36389530, 0.00, 180.00, NULL),
(159, '680937c1f1737', 40, 'fried rice', 1, 90.00, 'closed', NULL, '2025-04-24 14:39:43', NULL, 22, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 140.00, NULL),
(160, '680a4690e129d', 44, 'fried rice', 3, 270.00, 'assigned', NULL, '2025-04-24 14:14:19', NULL, 24, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 320.00, NULL),
(161, '68178bfa7637a', 40, 'fried rice', 1, 200.00, NULL, NULL, '2025-05-04 15:47:06', NULL, NULL, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 250.00, NULL),
(162, '68178bfa78654', 40, 'Fish Kalia ', 1, 90.00, NULL, NULL, '2025-05-04 15:47:06', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 0.00, 90.00, NULL),
(163, '681a5fb479977', 44, 'fried rice', 1, 200.00, 'closed', NULL, '2025-06-23 20:43:38', NULL, 22, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 250.00, NULL),
(164, '681e23a02b6f3', 44, 'fried rice', 3, 600.00, 'closed', NULL, '2025-05-09 15:51:01', NULL, 22, 0, NULL, 32, 22.57264590, 88.36389530, 0.00, 600.00, NULL),
(165, '681e23a02d91e', 44, 'Fish Kalia ', 5, 450.00, 'closed', NULL, '2025-06-23 20:43:46', NULL, 22, 0, NULL, 35, 22.57264590, 88.36389530, 0.00, 450.00, NULL),
(167, '683b6c08cb942', 44, 'fried rice', 3, 600.00, NULL, NULL, '2025-05-31 20:52:24', NULL, NULL, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 650.00, NULL),
(168, '683dbfaca6108', 44, 'Fish Kalia ', 1, 60.00, 'assigned', NULL, '2025-06-02 15:17:28', NULL, 1, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(169, '683dc131d25bd', 44, 'Fish Kalia ', 1, 60.00, 'closed', NULL, '2025-06-02 15:23:11', NULL, 21, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(170, '683de8cee5c51', 44, 'Fish Kalia ', 1, 60.00, 'accepted', NULL, '2025-06-02 18:10:11', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(171, '683f10d38b449', 44, 'Fish Kalia ', 2, 120.00, NULL, NULL, '2025-06-03 15:12:19', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 170.00, NULL),
(172, '683f1154b98b0', 44, 'Fish Kalia ', 1, 60.00, NULL, NULL, '2025-06-03 15:14:28', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(173, '683f1598b59c2', 44, 'Fish Kalia ', 1, 60.00, NULL, NULL, '2025-06-03 15:32:40', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(174, '6855625b803c5', 44, 'fried rice', 1, 160.00, NULL, NULL, '2025-06-20 13:30:03', NULL, NULL, 0, NULL, 32, 22.57264590, 88.36389530, 50.00, 210.00, NULL),
(175, '685563e78d13b', 45, 'Fish Kalia ', 1, 60.00, NULL, NULL, '2025-06-20 13:36:39', NULL, NULL, 0, NULL, 35, 22.57264590, 88.36389530, 50.00, 110.00, NULL),
(176, '68595c3b731bf', 42, 'Paneer Tikka Masala', 1, 84.50, '', NULL, '2025-06-23 13:59:40', NULL, 27, 0, NULL, 44, 22.57264590, 88.36389530, 50.00, 134.50, '1111'),
(177, '6859bcc4c6917', 44, 'Basanti Pulao	', 1, 18.33, NULL, NULL, '2025-06-23 20:44:52', NULL, NULL, 0, NULL, 46, 22.57264590, 88.36389530, 50.00, 166.67, '1111'),
(178, '6859bcc4c6917', 44, 'Mutton curry', 1, 98.33, NULL, NULL, '2025-06-23 20:44:52', NULL, NULL, 0, NULL, 46, 22.57264590, 88.36389530, 50.00, 166.67, '1111'),
(179, '6859bcc4c8b06', 44, 'rasgulla', 14, 128.33, NULL, NULL, '2025-06-23 20:44:52', NULL, NULL, 0, NULL, 30, 22.57264590, 88.36389530, 0.00, 128.33, '1111');

-- --------------------------------------------------------

--
-- Table structure for table `user_backups`
--

CREATE TABLE `user_backups` (
  `backup_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(222) DEFAULT NULL,
  `email` varchar(222) DEFAULT NULL,
  `phone` varchar(222) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `backed_up_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_favorite_dishes`
--

CREATE TABLE `user_favorite_dishes` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `d_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `user_favorite_dishes`
--

INSERT INTO `user_favorite_dishes` (`id`, `u_id`, `d_id`, `created_at`) VALUES
(5, 40, 32, '2025-04-22 12:03:39'),
(10, 44, 42, '2025-06-23 16:41:28'),
(12, 44, 32, '2025-06-23 18:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorite_restaurants`
--

CREATE TABLE `user_favorite_restaurants` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `rs_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `user_favorite_restaurants`
--

INSERT INTO `user_favorite_restaurants` (`id`, `u_id`, `rs_id`, `created_at`) VALUES
(36, 44, 32, '2025-05-31 16:45:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_deletion_log`
--
ALTER TABLE `account_deletion_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adm_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_cart_item` (`u_id`,`d_id`),
  ADD KEY `d_id` (`d_id`),
  ADD KEY `res_id` (`res_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`);

--
-- Indexes for table `deleted_users_log`
--
ALTER TABLE `deleted_users_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_original_user_id` (`original_user_id`);

--
-- Indexes for table `delivary_charges`
--
ALTER TABLE `delivary_charges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_boy`
--
ALTER TABLE `delivery_boy`
  ADD PRIMARY KEY (`db_id`),
  ADD KEY `idx_db_phone` (`db_phone`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `delivery_boy_history`
--
ALTER TABLE `delivery_boy_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_boy_id` (`delivery_boy_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `delivery_boy_ratings`
--
ALTER TABLE `delivery_boy_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `db_id` (`db_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `delivery_boy_requests`
--
ALTER TABLE `delivery_boy_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `delivery_cities`
--
ALTER TABLE `delivery_cities`
  ADD PRIMARY KEY (`city_id`),
  ADD UNIQUE KEY `city_name` (`city_name`);

--
-- Indexes for table `dishes`
--
ALTER TABLE `dishes`
  ADD PRIMARY KEY (`d_id`);

--
-- Indexes for table `footer_settings`
--
ALTER TABLE `footer_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_messages`
--
ALTER TABLE `order_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `rs_id` (`rs_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `o_id` (`o_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `order_status_requests`
--
ALTER TABLE `order_status_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `delivery_boy_id` (`delivery_boy_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `db_id` (`db_id`),
  ADD KEY `idx_token_pr` (`token`),
  ADD KEY `idx_email_pr` (`email`);

--
-- Indexes for table `remark`
--
ALTER TABLE `remark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD PRIMARY KEY (`rs_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `restaurant_owners`
--
ALTER TABLE `restaurant_owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `restaurant_owner_requests`
--
ALTER TABLE `restaurant_owner_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `restaurant_ratings`
--
ALTER TABLE `restaurant_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_user_restaurant` (`rs_id`,`u_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `restaurant_requests`
--
ALTER TABLE `restaurant_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `c_id` (`c_id`);

--
-- Indexes for table `res_category`
--
ALTER TABLE `res_category`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`);

--
-- Indexes for table `users_orders`
--
ALTER TABLE `users_orders`
  ADD PRIMARY KEY (`o_id`),
  ADD KEY `delivery_boy_id` (`delivery_boy_id`),
  ADD KEY `rs_id` (`rs_id`);

--
-- Indexes for table `user_backups`
--
ALTER TABLE `user_backups`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `user_backups_user_id_idx` (`user_id`);

--
-- Indexes for table `user_favorite_dishes`
--
ALTER TABLE `user_favorite_dishes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`u_id`,`d_id`),
  ADD KEY `idx_u_id` (`u_id`),
  ADD KEY `idx_d_id` (`d_id`);

--
-- Indexes for table `user_favorite_restaurants`
--
ALTER TABLE `user_favorite_restaurants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`u_id`,`rs_id`),
  ADD KEY `idx_u_id` (`u_id`),
  ADD KEY `idx_rs_id` (`rs_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_deletion_log`
--
ALTER TABLE `account_deletion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adm_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `deleted_users_log`
--
ALTER TABLE `deleted_users_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivary_charges`
--
ALTER TABLE `delivary_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery_boy`
--
ALTER TABLE `delivery_boy`
  MODIFY `db_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `delivery_boy_history`
--
ALTER TABLE `delivery_boy_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `delivery_boy_ratings`
--
ALTER TABLE `delivery_boy_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_boy_requests`
--
ALTER TABLE `delivery_boy_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `delivery_cities`
--
ALTER TABLE `delivery_cities`
  MODIFY `city_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `dishes`
--
ALTER TABLE `dishes`
  MODIFY `d_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `footer_settings`
--
ALTER TABLE `footer_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_messages`
--
ALTER TABLE `order_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_ratings`
--
ALTER TABLE `order_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_status_requests`
--
ALTER TABLE `order_status_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `remark`
--
ALTER TABLE `remark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `restaurant`
--
ALTER TABLE `restaurant`
  MODIFY `rs_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `restaurant_owners`
--
ALTER TABLE `restaurant_owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `restaurant_owner_requests`
--
ALTER TABLE `restaurant_owner_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `restaurant_ratings`
--
ALTER TABLE `restaurant_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `restaurant_requests`
--
ALTER TABLE `restaurant_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `res_category`
--
ALTER TABLE `res_category`
  MODIFY `c_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users_orders`
--
ALTER TABLE `users_orders`
  MODIFY `o_id` int(222) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `user_backups`
--
ALTER TABLE `user_backups`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_favorite_dishes`
--
ALTER TABLE `user_favorite_dishes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_favorite_restaurants`
--
ALTER TABLE `user_favorite_restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_deletion_log`
--
ALTER TABLE `account_deletion_log`
  ADD CONSTRAINT `account_deletion_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`d_id`) REFERENCES `dishes` (`d_id`),
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`res_id`) REFERENCES `restaurant` (`rs_id`);

--
-- Constraints for table `delivery_boy`
--
ALTER TABLE `delivery_boy`
  ADD CONSTRAINT `delivery_boy_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`rs_id`);

--
-- Constraints for table `delivery_boy_history`
--
ALTER TABLE `delivery_boy_history`
  ADD CONSTRAINT `delivery_boy_history_ibfk_1` FOREIGN KEY (`delivery_boy_id`) REFERENCES `delivery_boy` (`db_id`),
  ADD CONSTRAINT `delivery_boy_history_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `users_orders` (`o_id`);

--
-- Constraints for table `delivery_boy_ratings`
--
ALTER TABLE `delivery_boy_ratings`
  ADD CONSTRAINT `delivery_boy_ratings_ibfk_1` FOREIGN KEY (`db_id`) REFERENCES `delivery_boy` (`db_id`),
  ADD CONSTRAINT `delivery_boy_ratings_ibfk_2` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);

--
-- Constraints for table `order_messages`
--
ALTER TABLE `order_messages`
  ADD CONSTRAINT `order_messages_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`),
  ADD CONSTRAINT `order_messages_ibfk_2` FOREIGN KEY (`rs_id`) REFERENCES `restaurant` (`rs_id`);

--
-- Constraints for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD CONSTRAINT `order_ratings_ibfk_1` FOREIGN KEY (`o_id`) REFERENCES `users_orders` (`o_id`),
  ADD CONSTRAINT `order_ratings_ibfk_2` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);

--
-- Constraints for table `order_status_requests`
--
ALTER TABLE `order_status_requests`
  ADD CONSTRAINT `order_status_requests_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `users_orders` (`o_id`),
  ADD CONSTRAINT `order_status_requests_ibfk_2` FOREIGN KEY (`delivery_boy_id`) REFERENCES `delivery_boy` (`db_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`db_id`) REFERENCES `delivery_boy` (`db_id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD CONSTRAINT `restaurant_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `restaurant_owners` (`owner_id`);

--
-- Constraints for table `restaurant_owner_requests`
--
ALTER TABLE `restaurant_owner_requests`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `res_category` (`c_id`);

--
-- Constraints for table `restaurant_ratings`
--
ALTER TABLE `restaurant_ratings`
  ADD CONSTRAINT `restaurant_ratings_ibfk_1` FOREIGN KEY (`rs_id`) REFERENCES `restaurant` (`rs_id`),
  ADD CONSTRAINT `restaurant_ratings_ibfk_2` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`);

--
-- Constraints for table `restaurant_requests`
--
ALTER TABLE `restaurant_requests`
  ADD CONSTRAINT `restaurant_requests_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `restaurant_owners` (`owner_id`),
  ADD CONSTRAINT `restaurant_requests_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `res_category` (`c_id`);

--
-- Constraints for table `users_orders`
--
ALTER TABLE `users_orders`
  ADD CONSTRAINT `users_orders_ibfk_1` FOREIGN KEY (`rs_id`) REFERENCES `restaurant` (`rs_id`);

--
-- Constraints for table `user_favorite_dishes`
--
ALTER TABLE `user_favorite_dishes`
  ADD CONSTRAINT `user_favorite_dishes_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorite_dishes_ibfk_2` FOREIGN KEY (`d_id`) REFERENCES `dishes` (`d_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorite_restaurants`
--
ALTER TABLE `user_favorite_restaurants`
  ADD CONSTRAINT `user_favorite_restaurants_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorite_restaurants_ibfk_2` FOREIGN KEY (`rs_id`) REFERENCES `restaurant` (`rs_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
