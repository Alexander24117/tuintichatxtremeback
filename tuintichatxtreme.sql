-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2024 at 05:13 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tuintichatxtreme`
--

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `email` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `status`, `creation_date`, `token`) VALUES
(20, 'alexanderpulido01@gmail.com', '202cb962ac59075b964b07152d234b70', 1, '2024-05-06 21:20:03', '864a59cd71bc2c879fa97b2946ec173d');

-- --------------------------------------------------------

--
-- Table structure for table `user_additional_info`
--

CREATE TABLE `user_additional_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `id_type` varchar(20) DEFAULT NULL,
  `id_number` int(11) DEFAULT NULL,
  `social_security_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_additional_info`
--

INSERT INTO `user_additional_info` (`id`, `user_id`, `first_name`, `last_name`, `id_type`, `id_number`, `social_security_number`) VALUES
(9, 20, 'Alexander ', 'Pulido', 'dni', 10000001, '891110000');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_additional_info`
--

CREATE TABLE `vehicle_additional_info` (
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vehicle_make` varchar(50) DEFAULT NULL,
  `vehicle_color` varchar(20) DEFAULT NULL,
  `vehicle_id_number` varchar(20) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_insurance` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_additional_info`
--

INSERT INTO `vehicle_additional_info` (`vehicle_id`, `user_id`, `vehicle_make`, `vehicle_color`, `vehicle_id_number`, `vehicle_plate`, `vehicle_insurance`) VALUES
(3, 9, 'Yamaha ', 'azul', '98900011', 'KRC89T', '8766119900');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vehicle_additional_info`
--
ALTER TABLE `vehicle_additional_info`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `vehicle_additional_info`
--
ALTER TABLE `vehicle_additional_info`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  ADD CONSTRAINT `user_additional_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `vehicle_additional_info`
--
ALTER TABLE `vehicle_additional_info`
  ADD CONSTRAINT `vehicle_additional_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_additional_info` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
