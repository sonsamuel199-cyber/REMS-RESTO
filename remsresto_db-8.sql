-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 19, 2026 at 04:49 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `remsresto_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `business_days`
--

CREATE TABLE `business_days` (
  `id` int(11) NOT NULL,
  `business_date` date NOT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `closing_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_days`
--

INSERT INTO `business_days` (`id`, `business_date`, `is_closed`, `opening_balance`, `closing_balance`, `created_at`, `closed_at`) VALUES
(1, '2026-06-19', 0, 0.00, 0.00, '2026-06-19 13:08:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Appetizers', '2025-01-01 00:00:00'),
(2, 'Mains', '2025-01-01 00:00:00'),
(3, 'Desserts', '2025-01-01 00:00:00'),
(4, 'Beverages', '2025-01-01 00:00:00'),
(45, 'Extra', '2026-06-10 01:50:15'),
(47, 'eggs', '2026-06-19 13:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `category_stock_defaults`
--

CREATE TABLE `category_stock_defaults` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `default_min_stock` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `cashier_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `threshold_percentage` int(11) DEFAULT 20,
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `category`, `category_id`, `price`, `cost_price`, `description`, `stock`, `min_stock`, `threshold_percentage`, `unit_of_measure`, `created_at`, `updated_at`) VALUES
(1, 'Tapsilog', 'Mains', 2, 80.00, NULL, 'Tapa, sinangag, itlog', 41, 10, 20, 'pcs', '2026-06-07 04:41:39', '2026-06-19 13:40:35'),
(2, 'Chicken Adobo', 'Mains', 2, 120.00, NULL, 'Manok na adobo', 30, 5, 20, 'pcs', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(3, 'Halo-Halo', 'Desserts', 3, 90.00, NULL, 'Mixed dessert', 20, 5, 20, 'pcs', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(4, 'Coke', 'Beverages', 4, 50.00, NULL, 'Softdrinks', 97, 20, 20, 'pcs', '2026-06-07 04:41:39', '2026-06-19 13:20:50'),
(5, 'Fries', 'Appetizers', 1, 60.00, NULL, 'French fries', 40, 8, 20, 'pcs', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(6, 'fried', 'rice', NULL, 15.00, NULL, 'hahaha', 23, 5, 20, 'pcs', '2026-06-07 04:42:27', '2026-06-19 13:15:30'),
(7, 'Test Burger', 'mains', 2, 99.00, NULL, 'Delicious test burger', 10, 5, 20, 'pcs', '2026-06-08 01:58:04', '2026-06-08 02:24:53'),
(8, 'Tapsilog', 'mains', NULL, 80.00, NULL, 'Tapa with sinangag at itlog', 50, 10, 20, 'pcs', '2026-06-08 02:34:51', '2026-06-08 02:34:51'),
(9, 'rice', 'Extra', NULL, 15.00, NULL, 'AHAHAHAHA', 8, 5, 20, 'pcs', '2026-06-10 01:50:44', '2026-06-10 01:51:35'),
(10, 'boiled', 'egg', NULL, 30.00, NULL, 'hahaha', 29, 5, 20, 'cup', '2026-06-19 13:22:37', '2026-06-19 13:25:20'),
(11, 'boiled', 'eggs', NULL, 15.00, NULL, 'boiled', 30, 5, 20, 'pcs', '2026-06-19 13:38:17', '2026-06-19 13:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `table_number` int(11) DEFAULT 1,
  `payment_method` enum('cash','gcash') DEFAULT 'cash',
  `amount_received` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `senior_discount` tinyint(1) DEFAULT 0,
  `pwd_discount` tinyint(1) DEFAULT 0,
  `order_note` text DEFAULT NULL,
  `status` enum('pending','completed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `refund_type` enum('restock','waste') DEFAULT 'restock',
  `cashier_name` varchar(100) DEFAULT NULL,
  `order_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `subtotal`, `discount`, `tax`, `total_amount`, `table_number`, `payment_method`, `amount_received`, `change_amount`, `senior_discount`, `pwd_discount`, `order_note`, `status`, `cancelled_at`, `cancellation_reason`, `cancelled_by`, `refund_type`, `cashier_name`, `order_date`, `created_at`) VALUES
(1, 160.00, 0.00, 17.14, 160.00, 1, 'gcash', 0.00, -160.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, 'restock', NULL, '2026-06-08', '2026-06-08 05:10:02'),
(2, 160.00, 0.00, 17.14, 160.00, 1, 'gcash', 0.00, -160.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, 'restock', NULL, '2026-06-08', '2026-06-08 05:10:16'),
(3, 240.00, 0.00, 25.71, 240.00, 1, 'cash', 300.00, 60.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, 'restock', NULL, '2026-06-10', '2026-06-10 01:49:29'),
(4, 30.00, 0.00, 3.21, 30.00, 1, 'cash', 50.00, 20.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, 'restock', NULL, '2026-06-10', '2026-06-10 01:51:35'),
(5, 45.00, 0.00, 4.82, 45.00, 1, 'cash', 50.00, 5.00, 0, 0, NULL, 'pending', NULL, NULL, NULL, 'restock', NULL, '2026-06-12', '2026-06-12 03:46:06'),
(6, 30.00, 0.00, 3.21, 30.00, 1, 'cash', 50.00, 20.00, 0, 0, NULL, 'cancelled', '2026-06-12 12:13:08', 'nainip', NULL, 'restock', NULL, '2026-06-12', '2026-06-12 04:12:38'),
(7, 30.00, 0.00, 3.60, 30.00, 1, 'cash', 40.00, 10.00, 0, 0, NULL, 'completed', NULL, NULL, NULL, 'restock', NULL, '2026-06-19', '2026-06-19 13:15:30'),
(8, 150.00, 0.00, 18.00, 150.00, 1, 'cash', 200.00, 50.00, 0, 0, NULL, 'cancelled', '2026-06-19 21:23:21', 'annuli', 'Administrator', 'restock', NULL, '2026-06-19', '2026-06-19 13:20:50'),
(9, 30.00, 0.00, 3.60, 30.00, 1, 'cash', 40.00, 10.00, 0, 0, NULL, 'completed', NULL, NULL, NULL, 'restock', NULL, '2026-06-19', '2026-06-19 13:25:20'),
(10, 160.00, 16.00, 19.20, 144.00, 1, 'cash', 150.00, 6.00, 0, 1, NULL, 'completed', NULL, NULL, NULL, 'restock', NULL, '2026-06-19', '2026-06-19 13:40:35');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `item_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `name`, `price`, `quantity`, `item_note`) VALUES
(1, 1, 1, 'Tapsilog', 80.00, 2, NULL),
(2, 2, 1, 'Tapsilog', 80.00, 2, NULL),
(3, 3, 1, 'Tapsilog', 80.00, 3, NULL),
(4, 4, 9, 'rice', 15.00, 2, NULL),
(5, 5, NULL, 'fried', 15.00, 3, NULL),
(6, 6, NULL, 'fried', 15.00, 2, NULL),
(7, 7, NULL, 'fried', 15.00, 2, NULL),
(8, 8, NULL, 'Coke', 50.00, 3, NULL),
(9, 9, NULL, 'boiled', 30.00, 1, NULL),
(10, 10, NULL, 'Tapsilog', 80.00, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receipt_settings`
--

CREATE TABLE `receipt_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_settings`
--

INSERT INTO `receipt_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'receipt_header', 'REM\'S RESTO', '2026-06-19 13:08:09', '2026-06-19 13:08:09'),
(2, 'receipt_footer', 'Thank you for dining with us!', '2026-06-19 13:08:09', '2026-06-19 13:08:09'),
(3, 'receipt_logo_path', 'images/logo.png', '2026-06-19 13:08:09', '2026-06-19 13:08:09'),
(4, 'receipt_tax_reg', 'TIN: 123-456-789', '2026-06-19 13:08:09', '2026-06-19 13:08:09'),
(5, 'receipt_contact', 'Phone: (043) 123-4567', '2026-06-19 13:08:09', '2026-06-19 13:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `saved_carts`
--

CREATE TABLE `saved_carts` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `table_number` int(11) NOT NULL,
  `items` longtext NOT NULL,
  `discount_senior` tinyint(1) DEFAULT 0,
  `discount_pwd` tinyint(1) DEFAULT 0,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeout_at` datetime DEFAULT NULL,
  `is_abandoned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_carts`
--

INSERT INTO `saved_carts` (`id`, `customer_name`, `table_number`, `items`, `discount_senior`, `discount_pwd`, `subtotal`, `discount_amount`, `tax`, `total_amount`, `status`, `saved_at`, `timeout_at`, `is_abandoned`) VALUES
(1, 'Andy', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 17.14, 160.00, 'active', '2026-06-08 03:36:07', NULL, 0),
(2, 'Andy', 2, '[{\"id\":3,\"name\":\"Halo-Halo\",\"price\":90,\"quantity\":2}]', 0, 0, 180.00, 0.00, 19.29, 180.00, 'active', '2026-06-08 03:36:31', NULL, 0),
(3, 'Andy', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":2}]', 0, 0, 30.00, 0.00, 3.21, 30.00, 'active', '2026-06-08 03:38:09', NULL, 0),
(4, 'Test Customer', 1, '[]', 0, 0, 100.00, 0.00, 12.00, 112.00, 'active', '2026-06-08 03:40:35', NULL, 0),
(5, 'Andy', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":3}]', 0, 0, 45.00, 0.00, 4.82, 45.00, 'active', '2026-06-08 03:44:12', NULL, 0),
(6, 'mommy d', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 17.14, 160.00, 'completed', '2026-06-08 04:01:10', NULL, 0),
(7, 'jsjdijh3rr3hljr4bf', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 17.14, 160.00, 'completed', '2026-06-08 04:13:45', NULL, 0),
(8, 'sir alni', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":3}]', 0, 0, 240.00, 0.00, 25.71, 240.00, 'completed', '2026-06-10 01:49:16', NULL, 0),
(9, 'ALNI', 1, '[{\"id\":9,\"name\":\"rice\",\"price\":15,\"quantity\":2}]', 0, 0, 30.00, 0.00, 3.21, 30.00, 'completed', '2026-06-10 01:51:17', NULL, 0),
(10, '', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":2}]', 0, 0, 30.00, 0.00, 3.21, 30.00, 'active', '2026-06-10 02:20:42', NULL, 0),
(11, 'sam', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":2}]', 0, 0, 30.00, 0.00, 3.60, 30.00, 'completed', '2026-06-19 13:15:15', NULL, 0),
(12, 'melody', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 19.20, 160.00, 'completed', '2026-06-19 13:40:17', NULL, 0),
(13, 'abf', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 19.20, 160.00, 'active', '2026-06-19 14:45:53', NULL, 0),
(14, 'add', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2},{\"id\":2,\"name\":\"Chicken Adobo\",\"price\":120,\"quantity\":1}]', 0, 0, 280.00, 0.00, 33.60, 280.00, 'active', '2026-06-19 14:46:20', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'tax_rate', '0.12', 'Value Added Tax rate (12%)', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(2, 'senior_discount_rate', '0.1', 'Senior Citizen discount rate (20%)', '2026-06-07 04:41:39', '2026-06-19 13:24:55'),
(3, 'pwd_discount_rate', '0.1', 'PWD discount rate (20%)', '2026-06-07 04:41:39', '2026-06-19 13:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `pin` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('admin','cashier') DEFAULT 'cashier',
  `user_type` enum('admin','cashier') DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_pins`
--

CREATE TABLE `user_pins` (
  `id` int(11) NOT NULL,
  `role` enum('menu','inventory') NOT NULL,
  `pin` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_pins`
--

INSERT INTO `user_pins` (`id`, `role`, `pin`, `created_at`, `updated_at`) VALUES
(1, 'menu', '1234', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(2, 'inventory', '4567', '2026-06-07 04:41:39', '2026-06-07 04:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `waste_log`
--

CREATE TABLE `waste_log` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `recorded_by` varchar(100) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business_days`
--
ALTER TABLE `business_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_date` (`business_date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `category_stock_defaults`
--
ALTER TABLE `category_stock_defaults`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `saved_carts`
--
ALTER TABLE `saved_carts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin` (`pin`);

--
-- Indexes for table `user_pins`
--
ALTER TABLE `user_pins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role` (`role`);

--
-- Indexes for table `waste_log`
--
ALTER TABLE `waste_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `business_days`
--
ALTER TABLE `business_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `category_stock_defaults`
--
ALTER TABLE `category_stock_defaults`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `saved_carts`
--
ALTER TABLE `saved_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_pins`
--
ALTER TABLE `user_pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `waste_log`
--
ALTER TABLE `waste_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `category_stock_defaults`
--
ALTER TABLE `category_stock_defaults`
  ADD CONSTRAINT `category_stock_defaults_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waste_log`
--
ALTER TABLE `waste_log`
  ADD CONSTRAINT `waste_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waste_log_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
