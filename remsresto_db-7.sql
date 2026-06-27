-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 08, 2026 at 06:02 AM
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
(5, 'rice', '2026-06-07 04:28:31');

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
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `category`, `category_id`, `price`, `description`, `stock`, `min_stock`, `created_at`, `updated_at`) VALUES
(1, 'Tapsilog', 'Mains', 2, 80.00, 'Tapa, sinangag, itlog', 50, 10, '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(2, 'Chicken Adobo', 'Mains', 2, 120.00, 'Manok na adobo', 30, 5, '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(3, 'Halo-Halo', 'Desserts', 3, 90.00, 'Mixed dessert', 20, 5, '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(4, 'Coke', 'Beverages', 4, 50.00, 'Softdrinks', 100, 20, '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(5, 'Fries', 'Appetizers', 1, 60.00, 'French fries', 40, 8, '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(6, 'fried', 'rice', 5, 15.00, 'hahaha', 30, 5, '2026-06-07 04:42:27', '2026-06-07 04:42:27'),
(7, 'Test Burger', 'mains', 2, 99.00, 'Delicious test burger', 10, 5, '2026-06-08 01:58:04', '2026-06-08 02:24:53'),
(8, 'Tapsilog', 'mains', NULL, 80.00, 'Tapa with sinangag at itlog', 50, 10, '2026-06-08 02:34:51', '2026-06-08 02:34:51');

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
  `status` enum('pending','completed','refunded') NOT NULL DEFAULT 'pending',
  `cashier_name` varchar(100) DEFAULT NULL,
  `order_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `item_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_carts`
--

INSERT INTO `saved_carts` (`id`, `customer_name`, `table_number`, `items`, `discount_senior`, `discount_pwd`, `subtotal`, `discount_amount`, `tax`, `total_amount`, `status`, `saved_at`) VALUES
(1, 'Andy', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 17.14, 160.00, 'active', '2026-06-08 03:36:07'),
(2, 'Andy', 2, '[{\"id\":3,\"name\":\"Halo-Halo\",\"price\":90,\"quantity\":2}]', 0, 0, 180.00, 0.00, 19.29, 180.00, 'active', '2026-06-08 03:36:31'),
(3, 'Andy', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":2}]', 0, 0, 30.00, 0.00, 3.21, 30.00, 'active', '2026-06-08 03:38:09'),
(4, 'Test Customer', 1, '[]', 0, 0, 100.00, 0.00, 12.00, 112.00, 'active', '2026-06-08 03:40:35'),
(5, 'Andy', 1, '[{\"id\":6,\"name\":\"fried\",\"price\":15,\"quantity\":3}]', 0, 0, 45.00, 0.00, 4.82, 45.00, 'active', '2026-06-08 03:44:12'),
(6, 'mommy d', 1, '[{\"id\":1,\"name\":\"Tapsilog\",\"price\":80,\"quantity\":2}]', 0, 0, 160.00, 0.00, 17.14, 160.00, 'active', '2026-06-08 04:01:10');

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
(2, 'senior_discount_rate', '0.20', 'Senior Citizen discount rate (20%)', '2026-06-07 04:41:39', '2026-06-07 04:41:39'),
(3, 'pwd_discount_rate', '0.20', 'PWD discount rate (20%)', '2026-06-07 04:41:39', '2026-06-07 04:41:39');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_carts`
--
ALTER TABLE `saved_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
