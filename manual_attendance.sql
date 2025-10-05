-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 05, 2025 at 09:50 PM
-- Server version: 8.0.43
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mathipms`
--

-- --------------------------------------------------------

--
-- Table structure for table `manual_attendance`
--

CREATE TABLE `manual_attendance` (
  `id` int NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `id_no` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `pay_schedule` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `work_days_count` int DEFAULT NULL,
  `ot_hours` decimal(5,2) DEFAULT '0.00',
  `ut_hours` decimal(5,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `attendance_data` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `manual_attendance`
--

INSERT INTO `manual_attendance` (`id`, `archived`, `id_no`, `department`, `name`, `pay_schedule`, `start_date`, `end_date`, `work_days_count`, `ot_hours`, `ut_hours`, `created_at`, `attendance_data`) VALUES
(10, 0, '00202506', 'DESIGN', 'RAMIS,KENNARD JOHN', 'weekly', '2025-09-30', '2025-10-06', 4, 7.00, 0.00, '2025-10-03 08:06:17', '{\"days\": {\"2025-09-30\": {\"ot\": 0, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 0, \"undertime_deduction\": 0}, \"2025-10-01\": {\"ot\": 0, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 0, \"undertime_deduction\": 0}, \"2025-10-02\": {\"ot\": 5, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 625, \"undertime_deduction\": 0}, \"2025-10-03\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}}, \"id_no\": \"00202506\"}'),
(11, 0, '00202512', 'DESIGN', 'QUINONIZALA,CHRISTIAN B.', 'weekly', '2025-09-30', '2025-10-06', 4, 6.00, 1.00, '2025-10-03 16:21:39', '{\"days\": {\"2025-09-30\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}, \"2025-10-01\": {\"ot\": 0, \"ut\": 1, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 0, \"undertime_deduction\": 100}, \"2025-10-02\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}, \"2025-10-03\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}}, \"id_no\": \"00202512\"}'),
(12, 0, '00202513', 'DESIGN', 'CATIENZA,BLAKE ANDRIE', 'weekly', '2025-09-30', '2025-10-06', 4, 5.00, 0.00, '2025-10-03 16:39:10', '{\"days\": {\"2025-10-02\": {\"ot\": 1, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 125, \"undertime_deduction\": 0}, \"2025-10-03\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}, \"2025-10-04\": {\"ot\": 1, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"47\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 0, \"holiday_type\": \"Regular\", \"overtime_pay\": 0, \"undertime_deduction\": 0}, \"2025-10-05\": {\"ot\": 1, \"ut\": 0, \"is_sunday\": true, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 1040, \"holiday_type\": \"\", \"overtime_pay\": 169, \"undertime_deduction\": 0}}, \"id_no\": \"00202513\"}'),
(13, 1, '00202504', 'DESIGN', 'LIORERA,GEORGE MICHAEL R.', 'weekly', '2025-09-30', '2025-10-06', 6, 8.00, 2.00, '2025-10-05 18:55:30', '{\"days\": {\"2025-09-30\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}, \"2025-10-01\": {\"ot\": 2, \"ut\": 1, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 100}, \"2025-10-02\": {\"ot\": 2, \"ut\": 1, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 100}, \"2025-10-03\": {\"ot\": 2, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 800, \"holiday_type\": \"\", \"overtime_pay\": 250, \"undertime_deduction\": 0}, \"2025-10-04\": {\"ot\": 0, \"ut\": 0, \"is_sunday\": false, \"holiday_id\": \"47\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 0, \"holiday_type\": \"Regular\", \"overtime_pay\": 0, \"undertime_deduction\": 0}, \"2025-10-05\": {\"ot\": 0, \"ut\": 0, \"is_sunday\": true, \"holiday_id\": \"\", \"multipliers\": {\"regular_rate\": \"1\", \"overtime_rate\": \"1.25\", \"restdayholiday_regular\": \"0\", \"restdayholiday_special\": \"0\", \"restdayholiday_overtime\": \"0\", \"restdayspecialholiday_overtime\": \"0\"}, \"regular_pay\": 1040, \"holiday_type\": \"\", \"overtime_pay\": 0, \"undertime_deduction\": 0}}, \"id_no\": \"00202504\"}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `manual_attendance`
--
ALTER TABLE `manual_attendance`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `manual_attendance`
--
ALTER TABLE `manual_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
