-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 04:40 PM
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
-- Database: `payroll_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `status`) VALUES
(1, 'IT', 'active'),
(2, 'Finance', 'active'),
(4, 'Human Resources', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `emp_name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `deleted_at` datetime DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `emp_name`, `position`, `department`, `salary`, `date_hired`, `status`, `deleted_at`, `employee_id`) VALUES
(1, 'Kenjiro Z. Takada', 'Auditor', 'Finance', 26000.00, '2025-10-20', 'active', NULL, '001'),
(2, 'John Lloyd Delmo', 'Accounting', 'Finance', 30000.00, '2025-10-20', 'active', NULL, '002'),
(3, 'Ron Hezykiel T. Arbois', 'Computer Engineer', 'IT', 50000.00, '2025-10-20', 'active', NULL, '003'),
(7, 'John Edward T. Solaybar', 'HR Manager', 'Human Resources', 28000.00, '2025-10-23', 'active', NULL, '004'),
(8, 'Arvie E. Caunca', 'Computer Science', 'IT', 40000.00, '2025-10-24', 'active', NULL, '005'),
(11, 'John Lewis Oliquiano', 'Data Analyst', 'IT', 35000.00, '2025-10-25', 'active', NULL, '006'),
(12, 'Glenn Casaguep', 'HR Assistant', 'Human Resources', 24000.00, '2025-10-26', 'active', NULL, '007');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('regular','special') DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `date`, `name`, `type`, `created_at`) VALUES
(1, '2025-01-01', 'New Year\'s Day', 'regular', '2025-11-05 12:35:13'),
(2, '2025-04-09', 'Araw ng Kagitingan', 'regular', '2025-11-05 12:35:13'),
(3, '2025-04-17', 'Maundy Thursday', 'regular', '2025-11-05 12:35:13'),
(4, '2025-04-18', 'Good Friday', 'regular', '2025-11-05 12:35:13'),
(5, '2025-05-01', 'Labor Day', 'regular', '2025-11-05 12:35:13'),
(6, '2025-06-12', 'Independence Day', 'regular', '2025-11-05 12:35:13'),
(7, '2025-08-21', 'Ninoy Aquino Day', 'special', '2025-11-05 12:35:13'),
(8, '2025-08-25', 'National Heroes Day', 'regular', '2025-11-05 12:35:13'),
(9, '2025-11-01', 'All Saints\' Day', 'special', '2025-11-05 12:35:13'),
(10, '2025-11-30', 'Bonifacio Day', 'regular', '2025-11-05 12:35:13'),
(11, '2025-12-25', 'Christmas Day', 'regular', '2025-11-05 12:35:13'),
(12, '2025-12-30', 'Rizal Day', 'regular', '2025-11-05 12:35:13'),
(13, '2025-12-31', 'New Year\'s Eve', 'special', '2025-11-05 12:35:13');

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(20) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `days_worked` int(11) DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `rate_type` enum('daily','hourly','monthly') DEFAULT 'monthly',
  `pay_date` date DEFAULT NULL,
  `basic_pay` decimal(10,2) DEFAULT NULL,
  `absent_hours` decimal(5,2) DEFAULT 0.00,
  `late_minutes` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `sss` decimal(10,2) DEFAULT NULL,
  `philhealth` decimal(10,2) DEFAULT NULL,
  `pagibig` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payrolls`
--

INSERT INTO `payrolls` (`id`, `ref_no`, `emp_id`, `days_worked`, `hours_worked`, `rate_type`, `pay_date`, `basic_pay`, `absent_hours`, `late_minutes`, `overtime_hours`, `gross_pay`, `tax`, `sss`, `philhealth`, `pagibig`, `deductions`, `net_pay`, `status`) VALUES
(2, '', 2, NULL, NULL, 'monthly', '2025-10-01', 28000.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1000.00, 27000.00, 'approved'),
(3, '', 3, NULL, NULL, 'monthly', '2025-10-01', 32000.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1500.00, 30500.00, 'approved'),
(5, '', 2, NULL, NULL, 'monthly', '2025-01-31', 40000.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 4000.00, 36000.00, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `days_worked` decimal(5,2) NOT NULL,
  `hours_per_day` int(11) DEFAULT 8,
  `total_hours` decimal(7,2) NOT NULL,
  `absent_hours` decimal(5,2) DEFAULT 0.00,
  `actual_hours` decimal(7,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `tardiness_deduction` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL,
  `tax_deduction` decimal(10,2) NOT NULL,
  `sss_deduction` decimal(10,2) NOT NULL,
  `philhealth_deduction` decimal(10,2) NOT NULL,
  `pagibig_deduction` decimal(10,2) NOT NULL,
  `total_deductions` decimal(12,2) NOT NULL,
  `net_pay` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `employee_id`, `pay_period_start`, `pay_period_end`, `days_worked`, `hours_per_day`, `total_hours`, `absent_hours`, `actual_hours`, `hourly_rate`, `late_minutes`, `tardiness_deduction`, `gross_pay`, `tax_deduction`, `sss_deduction`, `philhealth_deduction`, `pagibig_deduction`, `total_deductions`, `net_pay`, `created_at`, `status`, `updated_at`) VALUES
(1, 2, '2025-11-01', '2025-11-30', 1.00, 8, 8.00, 0.00, 8.00, 170.45, 0, 0.00, 1789.77, 178.98, 80.54, 53.69, 100.00, 413.21, 1376.56, '2025-11-05 15:23:23', 'pending', '2025-11-05 15:23:23');

--
-- Triggers `payroll_records`
--
DELIMITER $$
CREATE TRIGGER `payroll_records_before_update` BEFORE UPDATE ON `payroll_records` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_payroll_records_timestamp` BEFORE UPDATE ON `payroll_records` FOR EACH ROW BEGIN
    SET NEW.created_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `department_id`, `name`, `salary`, `base_salary`, `status`) VALUES
(1, 1, 'Computer Science', 40000.00, 0.00, 'active'),
(2, 2, 'Accounting', 30000.00, 0.00, 'active'),
(4, 1, 'Data Analyst', 35000.00, 0.00, 'active'),
(5, 1, 'Computer Engineer', 50000.00, 0.00, 'active'),
(6, 4, 'HR Manager', 28000.00, 0.00, 'active'),
(7, 4, 'HR Assistant', 24000.00, 0.00, 'active'),
(8, 2, 'Auditor', 26000.00, 0.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'admin123', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_pay_period` (`pay_period_start`,`pay_period_end`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `positions_ibfk_1` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD CONSTRAINT `payrolls_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
