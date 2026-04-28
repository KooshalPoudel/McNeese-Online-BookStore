-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 06:31 AM
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
-- Database: `mcneese_bookstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` enum('textbook','office_supply','other') DEFAULT 'textbook',
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `price`, `stock`, `category`, `description`, `cover_image`, `course_code`, `created_at`) VALUES
(1, 'Software Engineering', 'Ian Sommerville', '978-0133943030', 89.99, 23, 'textbook', 'Classic software engineering textbook', 'images/softwareEngineeringIanSommerville.png', 'CSCI 413', '2026-04-16 10:22:54'),
(2, 'Database Systems', 'Ramez Elmasri', '978-0133970777', 79.99, 20, 'textbook', 'Fundamentals of Database Systems', 'images/databaseSystemsElmasri.png', 'CSCI 350', '2026-04-16 10:22:54'),
(3, 'Calculus: Early Transcendentals', 'James Stewart', '978-1285741550', 120.00, 30, 'textbook', 'Complete calculus textbook', 'images/calculusJamesStewart.png', 'MATH 201', '2026-04-16 10:22:54'),
(4, 'Physics for Scientists', 'Raymond Serway', '978-1337553292', 95.00, 13, 'textbook', 'University Physics', 'images/physicsRaymond.png', 'PHYS 201', '2026-04-16 10:22:54'),
(5, 'Notebook Set (5-Pack)', 'McNeese Store', NULL, 12.99, 100, 'office_supply', 'College-ruled notebooks', 'images/notebookSet.png', NULL, '2026-04-16 10:22:54'),
(6, 'Ballpoint Pens (12-Pack)', 'McNeese Store', NULL, 7.99, 200, 'office_supply', 'Black and blue pens', 'images/ballpointPen.png', NULL, '2026-04-16 10:22:54'),
(7, 'Highlighters Set', 'McNeese Store', NULL, 5.99, 150, 'office_supply', 'Assorted color highlighters', 'images/highlighterSet.png', NULL, '2026-04-16 10:22:54'),
(8, 'Backpack - McNeese Cowboys', 'McNeese Store', NULL, 45.00, 5, 'office_supply', 'Official McNeese Cowboys backpack', 'images/cowsboysBackpack.png', NULL, '2026-04-16 10:22:54');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `apt` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(2) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `full_name`, `phone`, `street`, `apt`, `city`, `state`, `zip`, `subtotal`, `shipping_cost`, `tax`, `total`, `status`, `created_at`, `updated_at`) VALUES
(3, NULL, 1, 'Kushal Poudel', '16823809788', '4510 Commons St Apt 29', '29', 'Lake Charles', 'LA', '70607', 17.97, 5.99, 1.53, 25.49, 'delivered', '2026-04-16 21:17:15', '2026-04-22 03:51:15'),
(4, 'MC-2026-6PN4UB', 1, 'Kushal Poudel', '16823809788', '4510 Commons St', 'Apt 29', 'Lake Charles', 'LA', '70607', 369.98, 0.00, 31.45, 401.43, 'shipped', '2026-04-22 02:15:53', '2026-04-22 03:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `book_id`, `title`, `author`, `cover_image`, `quantity`, `price`) VALUES
(5, 3, 7, 'Highlighters Set', 'McNeese Store', 'images/highlighterSet.png', 3, 5.99),
(6, 4, 4, 'Physics for Scientists', 'Raymond Serway', 'images/physicsRaymond.png', 2, 95.00),
(7, 4, 1, 'Software Engineering', 'Ian Sommerville', 'images/softwareEngineeringIanSommerville.png', 2, 89.99);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `student_id`, `password`, `phone`, `address`, `role`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 'Kushal', 'Poudel', 'kpoudel@mcneese.edu', '000370339', '$2y$10$KUzn.JCzrP14w/6rAT1mW.2Iki6NCaBBCvexqf.RFSwD8re8LNbsq', '16823809788', '4510 Commons St Apt 29', 'student', NULL, '2026-04-16 10:23:48', '2026-04-16 10:23:48'),
(2, 'Kushal', 'Poudel', 'admin@mcneese.edu', '000370340', '$2y$10$Ct8sMhEQ4NcqxJ2tEisoK.gfPUc2zk8BfoMF6dn78G5Xz/njrpTwG', '16823809788', '4510 Commons St Apt 29', 'admin', NULL, '2026-04-22 02:17:43', '2026-04-22 02:18:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
