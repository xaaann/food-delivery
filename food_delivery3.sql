-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 20, 2026 at 07:18 PM
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
-- Database: `food_delivery`
--

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `owner_id`, `restaurant_id`, `name`, `description`, `price`, `stock`, `image`, `available`, `created_at`) VALUES
(1, 2, 1, 'Caesar Salad', 'Fresh romaine lettuce tossed with creamy Caesar dressing, crunchy croutons, and grated parmesan cheese.', 6.50, 37, NULL, 1, '2026-01-05 16:41:17'),
(2, 2, 1, 'Grilled Chicken Sandwich', 'Juicy grilled chicken breast served with lettuce, tomato, and mayo on a toasted bun.', 8.99, 13, NULL, 1, '2026-01-05 16:42:06'),
(3, 2, 1, 'Spaghetti Carbonara', 'Classic Italian pasta with creamy egg sauce, crispy bacon, and parmesan cheese.', 10.50, 50, '../assets/default-food.jpg', 1, '2026-01-05 16:42:30'),
(4, 2, 1, 'Chocolate Milkshake', 'Rich and creamy chocolate milkshake topped with whipped cream.', 4.25, 28, NULL, 1, '2026-01-05 16:42:52'),
(5, 2, 1, 'Vegetable Spring Rolls', 'Crispy spring rolls filled with fresh vegetables, served with sweet chili sauce.', 5.00, 30, NULL, 1, '2026-01-05 16:43:11'),
(6, 7, NULL, 'Macaroni Salad', 'A delicious food', 25.00, 97, NULL, 1, '2026-01-14 08:58:43'),
(8, 2, NULL, 'roxanne', '3rd year student', 1.00, 1, '../assets/default-food.jpg', 1, '2026-01-14 09:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `order_id`, `sender_id`, `receiver_id`, `message`, `created_at`) VALUES
(1, 1, 3, 1, 'qwe', '2026-01-07 14:11:02'),
(2, 1, 3, 1, 'qwe', '2026-01-07 14:11:05'),
(3, 1, 3, 1, 'qwe', '2026-01-07 14:11:08'),
(4, 1, 3, 1, 'qwe', '2026-01-07 14:11:12');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) DEFAULT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','preparing','on_the_way','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `payment_status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `rider_id`, `restaurant_id`, `total_amount`, `status`, `delivery_address`, `payment_status`, `created_at`, `order_date`) VALUES
(1, 1, 3, 1, 22.00, 'delivered', 'jaro, iloilo', 'completed', '2026-01-05 16:45:43', '2026-01-14 16:39:21'),
(2, 1, 3, NULL, 39.24, 'delivered', 'lapaz', 'completed', '2026-01-07 14:34:22', '2026-01-14 16:39:21'),
(3, 1, 3, NULL, 35.24, 'delivered', 'jaro, iloilo', 'completed', '2026-01-14 07:50:46', '2026-01-14 16:39:21'),
(4, 1, 8, NULL, 65.00, 'delivered', 'lapaz, iloilo', 'completed', '2026-01-14 08:26:06', '2026-01-14 16:39:21'),
(5, 1, 8, NULL, 25.00, 'delivered', 'lapaz iloilo', 'completed', '2026-01-14 08:29:48', '2026-01-14 16:39:21'),
(7, 1, 8, NULL, 20.00, 'delivered', 'lapaz, iloilo', 'pending', '2026-01-14 08:41:30', '2026-01-14 16:41:30'),
(8, 1, 8, NULL, 31.50, 'delivered', 'lapaz, iloilo', 'pending', '2026-01-14 08:41:49', '2026-01-14 16:41:49'),
(9, 1, 8, NULL, 6.50, 'delivered', 'jaro, iloilo', 'pending', '2026-01-14 08:45:00', '2026-01-14 16:45:00'),
(10, 9, NULL, NULL, 25.00, 'pending', 'jaro,iloilo', 'pending', '2026-01-14 09:57:27', '2026-01-14 17:57:27'),
(11, 9, NULL, NULL, 25.00, 'pending', 'jaro, iloilo', 'pending', '2026-01-14 09:57:43', '2026-01-14 17:57:43'),
(12, 9, NULL, NULL, 25.00, 'pending', 'jaro, iloilo', 'pending', '2026-01-14 10:02:39', '2026-01-14 18:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` int(11) DEFAULT NULL,
  `food_id` int(11) NOT NULL,
  `food_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `food_id`, `food_item_id`, `quantity`, `price`) VALUES
(1, 1, 0, 1, 1, 6.50),
(2, 1, 0, 3, 1, 10.50),
(3, 1, 0, 5, 1, 5.00),
(4, 2, 0, 2, 1, 8.99),
(5, 2, 0, 3, 2, 10.50),
(6, 2, 0, 4, 1, 4.25),
(7, 2, 0, 5, 1, 5.00),
(8, 3, 0, 1, 1, 6.50),
(9, 3, 0, 2, 1, 8.99),
(10, 3, 0, 3, 1, 10.50),
(11, 3, 0, 4, 1, 4.25),
(12, 3, 0, 5, 1, 5.00),
(13, 4, 0, 1, 10, 6.50),
(14, 5, 0, 5, 5, 5.00),
(15, 7, 5, NULL, 4, 5.00),
(16, 8, 3, NULL, 3, 10.50),
(17, 9, 1, NULL, 1, 6.50),
(18, 10, 6, NULL, 1, 25.00),
(19, 11, 6, NULL, 1, 25.00),
(20, 12, 6, NULL, 1, 25.00);

-- --------------------------------------------------------

-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `restaurant_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `restaurant_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`restaurant_id`, `owner_id`, `restaurant_name`, `address`, `phone`, `email`, `description`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'roxanne fama\'s Restaurant', 'jaro', '09109638518', 'roxanne@gmail.com', NULL, NULL, 'active', '2026-01-14 07:31:52', '2026-01-14 07:31:52'),
(2, 4, 'John\'s Grill House', '123 Main Street, Roxas City, Western Visayas', '+63 912 345 6789', 'johnsgrillhouse@test.com', 'Best grilled food in town! Fresh ingredients daily.', NULL, 'active', '2026-01-14 07:31:52', '2026-01-14 07:31:52');

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_events`
--

INSERT INTO `security_events` (`event_id`, `user_id`, `event_type`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(1, 7, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 07:43:17'),
(2, 7, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 07:50:07'),
(3, 1, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 07:58:15'),
(4, 3, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:03:20'),
(5, 3, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:19:25'),
(6, 3, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:20:44'),
(7, 1, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:21:17'),
(8, 1, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:45:46'),
(9, 7, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:46:14'),
(10, 2, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 08:57:55'),
(11, 7, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 09:04:11'),
(12, 2, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 09:50:07'),
(13, 8, 'registration', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'New rider registration', '2026-01-14 09:53:39'),
(14, 8, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 09:55:10'),
(15, 7, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 09:55:44'),
(16, 9, 'registration', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'New customer registration', '2026-01-14 09:56:20'),
(17, 9, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-14 10:42:07'),
(18, 1, 'logout', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'User logged out', '2026-01-18 16:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','rider','owner') DEFAULT 'customer',
  `status` enum('active','inactive','pending','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`) VALUES
(1, 'shella mae', 'shella@gmail.com', '$2y$10$x1LO0Rge1t.Muujx3WRrb.Jiw29wotUcztTm.S4cDQ0Zddv0S91s.', '09123456789', 'lapaz', 'customer', 'active', '2026-01-05 16:36:54'),
(2, 'roxanne fama', 'roxanne@gmail.com', '$2y$10$fP54eC/Y7jQn09KE4ugP3ecuLBNzc1GJzrmkk05jyaY54UUJMVVhi', '09109638518', 'jaro', 'owner', 'active', '2026-01-05 16:37:27'),
(3, 'jessa marie', 'jessa@gmail.com', '$2y$10$HJcANNxA0ApbBcp05VUOoeK5BP2wSN5ARTGu/a9htyKi6dmvM1aJu', '09123467123', 'leon', 'rider', 'active', '2026-01-05 16:44:43'),
(4, 'John Restaurant Owner', 'owner@test.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMesJVYcPNgUj1ZLxdM4.v7FzC', '+63 912 345 6789', '123 Main Street, Roxas City', 'owner', 'active', '2026-01-14 07:31:52'),
(5, 'Jane Customer', 'customer@test.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMesJVYcPNgUj1ZLxdM4.v7FzC', '+63 917 123 4567', '456 Oak Avenue, Roxas City', 'customer', 'active', '2026-01-14 07:31:52'),
(6, 'Mike Rider', 'rider@test.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMesJVYcPNgUj1ZLxdM4.v7FzC', '+63 919 876 5432', '789 Pine Road, Roxas City', 'rider', 'active', '2026-01-14 07:31:52'),
(7, 'Meka Cantero', 'mekac@gmail.com', '$2y$10$Z24xw2W.R/eCxfesf56Al.gx1Zte2uhXDOtOYU339jWh8zeWzqTbu', '09109638518', 'iloilo', 'owner', 'active', '2026-01-14 07:33:45'),
(8, 'eunel angelo', 'eunel@gmail.com', '$2y$12$PFAD/35NG3mOEZzbEew6bOUawU/sGkJ6ODbQYn7PvWkFk3KqHmynm', '09674523123', 'iloilo jaro', 'rider', 'active', '2026-01-14 09:53:39'),
(9, 'jheann kate robles', 'jheann@gmail.com', '$2y$12$/ydNUnoTGV7xAycI2RA0DuQL77WFMmW2HSf1tNtKNsHalfptwt716', '09123456789', 'jaro, iloilo', 'customer', 'active', '2026-01-14 09:56:20');

-- --------------------------------------------------------

--
-- Structure for view `order_stats`
--
DROP TABLE IF EXISTS `order_stats`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `order_stats`  AS SELECT `o`.`id` AS `order_id`, `o`.`user_id` AS `user_id`, `o`.`rider_id` AS `rider_id`, `o`.`restaurant_id` AS `restaurant_id`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`payment_status` AS `payment_status`, `o`.`created_at` AS `created_at`, `u`.`name` AS `customer_name`, `u`.`email` AS `customer_email`, `r`.`name` AS `rider_name`, `res`.`restaurant_name` AS `restaurant_name` FROM (((`orders` `o` left join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `users` `r` on(`o`.`rider_id` = `r`.`id`)) left join `restaurants` `res` on(`o`.`restaurant_id` = `res`.`restaurant_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `idx_restaurant_id` (`restaurant_id`),
  ADD KEY `idx_food_items_available` (`available`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rider_id` (`rider_id`),
  ADD KEY `idx_restaurant_id` (`restaurant_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `food_item_id` (`food_item_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`restaurant_id`),
  ADD UNIQUE KEY `owner_id` (`owner_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email_role` (`email`,`role`),
  ADD KEY `idx_users_role_status` (`role`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `restaurant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_items`
--
ALTER TABLE `food_items`
  ADD CONSTRAINT `food_items_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`);

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `security_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
