-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 02:35 PM
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
-- Database: `employee_management_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicant`
--

CREATE TABLE `applicant` (
  `applicantID` varchar(100) NOT NULL,
  `fullName` varchar(150) NOT NULL,
  `position_applied` varchar(100) NOT NULL,
  `department` int(11) NOT NULL,
  `date_applied` date NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `home_address` varchar(255) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `date_started` date NOT NULL,
  `in_role` varchar(5) NOT NULL,
  `university` varchar(150) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `skills` text NOT NULL,
  `summary` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`applicantID`, `fullName`, `position_applied`, `department`, `date_applied`, `contact_number`, `email_address`, `home_address`, `job_title`, `company_name`, `date_started`, `in_role`, `university`, `course`, `year_graduated`, `skills`, `summary`) VALUES
('HOS-001', 'Jojana Jean B. Garabillo', '', 0, '2025-10-24', '', 'garabillo_jojanajean@plpasig.edu.ph', '', '', '', '0000-00-00', '', '', '', '0000', '', ''),
('HOS-002', '', '', 0, '2025-10-24', '', 'antonio_rhoannenicole@plpasig.edu.ph', '', '', '', '0000-00-00', '', '', '', '0000', '', ''),
('HOS-003', 'Rhaonne', '', 0, '2025-10-25', '', 'lucasesterossa@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', ''),
('HOS-005', 'Rhoanne Nicole', '', 0, '2025-10-25', '', 'bruhemoment00@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', ''),
('HOS-006', 'lol', '', 0, '2025-10-25', '', 'n0305933@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `deptID` int(11) NOT NULL,
  `deptName` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `empID` int(11) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `department` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `employment_type` int(11) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `home_address` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `date_of_brith` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `emergency_contact` varchar(20) NOT NULL,
  `TIN_number` varchar(20) NOT NULL,
  `phil_health_number` varchar(20) NOT NULL,
  `SSS_number` varchar(20) NOT NULL,
  `pagibig_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employement_type`
--

CREATE TABLE `employement_type` (
  `emtypeID` int(11) NOT NULL,
  `typeName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `positionID` int(11) NOT NULL,
  `deptID` int(11) NOT NULL,
  `position_title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(100) NOT NULL,
  `applicant_employee_id` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `token_expiry` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `applicant_employee_id`, `email`, `password`, `role`, `fullname`, `status`, `created_at`, `reset_token`, `token_expiry`) VALUES
('', NULL, 'admin@gmail.com', '$2y$10$0AljaQmbIMBrTZ1R8rVDxOUp9JX4KVtMU1W3162A5vg0axILe9/va', 'Admin', '', '', '0000-00-00 00:00:00', '', ''),
('', 'HOS-002', 'antonio_rhoannenicole@plpasig.edu.ph', '$2y$10$i.5PvZYRP7kcfZ3TC9naUu7LSZPB.NBqzOKOEjTPY6G3vb2uLVJpy', 'Applicant', '', 'Pending', '0000-00-00 00:00:00', '37556e6a57ac99393a9ce313dfd15cd3d1ac7321d5d3898d6fe45ec5db2da500', '2025-10-25 13:46:17'),
('', 'HOS-005', 'bruhemoment00@gmail.com', '$2y$10$fOgKiEA6TwtzV9sC53ETXOeuPWYapJdm31DZVhDMPpNiz8kKCZiSi', 'Applicant', 'Rhoanne Nicole', 'Pending', '0000-00-00 00:00:00', '', ''),
('', 'HOS-001', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$9i4hR0aGKmS4Gir3.2xtK.0EOd7J2kDoq0OnxMn6f75EtsFZBuoAy', 'Applicant', 'Jojana Jean B. Garabillo', 'Pending', '0000-00-00 00:00:00', '', ''),
('', 'HOS-003', 'lucasesterossa@gmail.com', '$2y$10$Z6pR1nSJusgp8cNpVRvDQ.8a.rUSUbu0AiBYRKMLftakI33AX3ZYa', 'Applicant', 'Rhaonne', 'Pending', '0000-00-00 00:00:00', '', ''),
('', 'HOS-006', 'n0305933@gmail.com', '$2y$10$cNz9xKwONDhvG/efULOCJuYV/CCeGZ3eXv9A0kq8XHr/Qt5.irjnS', 'Applicant', 'lol', 'Pending', '0000-00-00 00:00:00', '', ''),
('', NULL, 'test2@gmail.com', '$2y$10$OcBqYLhncgM9OO7zknbPjONpm2rstuhbGvqGgeKI/x2CKpb3tyerW', 'Employee', '', '', '0000-00-00 00:00:00', '', ''),
('', NULL, 'test3@gmail.com', '$2y$10$OcBqYLhncgM9OO7zknbPjONpm2rstuhbGvqGgeKI/x2CKpb3tyerW', 'Applicant', '', '', '0000-00-00 00:00:00', '', ''),
('', NULL, 'test@gmail.com', '$2y$10$0AljaQmbIMBrTZ1R8rVDxOUp9JX4KVtMU1W3162A5vg0axILe9/va', 'Employee', '', '', '0000-00-00 00:00:00', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicant`
--
ALTER TABLE `applicant`
  ADD KEY `fk_applicant_user` (`applicantID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`deptID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`empID`);

--
-- Indexes for table `employement_type`
--
ALTER TABLE `employement_type`
  ADD PRIMARY KEY (`emtypeID`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`positionID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `uk_applicant_employee_id` (`applicant_employee_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant`
--
ALTER TABLE `applicant`
  ADD CONSTRAINT `fk_applicant_user` FOREIGN KEY (`applicantID`) REFERENCES `user` (`applicant_employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
