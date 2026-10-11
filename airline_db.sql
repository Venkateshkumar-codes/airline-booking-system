-- phpMyAdmin SQL Dump
-- Database: `airline_db`
-- Updated: May 17, 2026 — Added users table + FK fix

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'password123');

-- --------------------------------------------------------
-- Table: users  (NEW)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `dob`, `contact`) VALUES
(1, 'Test User', 'test@example.com', 'password123', '2000-01-01', '9876543210');

-- --------------------------------------------------------
-- Table: flights
-- --------------------------------------------------------
CREATE TABLE `flights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `available_seats` int(11) DEFAULT NULL,
  `status` enum('Scheduled','Delayed','Cancelled') DEFAULT 'Scheduled',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `flights` (`id`, `origin`, `destination`, `departure_date`, `price`, `available_seats`, `status`) VALUES
(1, 'New York', 'London', '2026-08-01', 550.00, 19, 'Scheduled'),
(2, 'London', 'Paris', '2026-08-05', 120.00, 15, 'Scheduled'),
(3, 'Tokyo', 'New York', '2026-08-10', 890.00, 10, 'Scheduled'),
(4, 'torangallu', 'bengaluru', '2026-07-08', 250.00, 37, 'Scheduled'),
(5, 'hospet', 'bengaluru', '2026-06-16', 3000.00, 70, 'Scheduled');

-- --------------------------------------------------------
-- Table: bookings
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flight_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `passenger_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Booked','Cancelled') DEFAULT 'Booked',
  `class` enum('Economy','Business','First') DEFAULT 'Economy',
  `passenger_type` enum('Adult','Child','Infant') DEFAULT 'Adult',
  `dob` date DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_id` (`flight_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bookings` (`id`, `flight_id`, `user_id`, `passenger_name`, `email`, `booking_date`, `status`, `class`, `passenger_type`, `dob`, `contact`) VALUES
(1, 1, NULL, 'vinay', 'vinay@example.com', '2026-02-07 11:37:30', 'Booked', 'Economy', 'Adult', NULL, NULL),
(2, 4, NULL, 'VINAY', 'VINAY@123', '2026-03-23 08:45:39', 'Booked', 'Economy', 'Adult', NULL, NULL),
(3, 4, NULL, 'error', 'error@123', '2026-05-02 03:48:25', 'Booked', 'Business', 'Adult', '2026-07-07', '1234567890'),
(4, 4, 1, 'error', 'error@123', '2026-05-02 04:14:16', 'Booked', 'Economy', 'Adult', '2003-07-07', '1234567890');

COMMIT;
