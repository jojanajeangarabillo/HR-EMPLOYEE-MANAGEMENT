-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 10:25 AM
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
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `announcementID` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `target` enum('All','Department','Employee') DEFAULT 'All',
  `target_id` varchar(100) DEFAULT NULL,
  `type` enum('General','Leave Notice','System','Urgent') DEFAULT 'General',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `summary` text NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`applicantID`, `fullName`, `position_applied`, `department`, `date_applied`, `contact_number`, `email_address`, `home_address`, `job_title`, `company_name`, `date_started`, `in_role`, `university`, `course`, `year_graduated`, `skills`, `summary`, `profile_pic`) VALUES
('HOS-001', 'Jojana Jean B. Garabillo', '', 0, '2025-10-25', '', 'garabillo_jojanajean@plpasig.edu.ph', '', '', '', '0000-00-00', '', '', '', '0000', '', '', NULL),
('HOS-002', 'Shane Cacho', '', 0, '2025-10-25', '', 'cacho_shaneellamae@plpasig.edu.ph', '', '', '', '0000-00-00', '', '', '', '0000', '', '', NULL),
('HOS-003', 'Joepat Lacerna', '', 0, '2025-10-25', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `calendar`
--

CREATE TABLE `calendar` (
  `calendarID` int(11) NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `time_limit` time DEFAULT NULL,
  `allotted_time` time DEFAULT NULL,
  `empID` varchar(100) DEFAULT NULL,
  `leave_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Approved','Ongoing','Completed','Cancelled') DEFAULT 'Approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `deptID` int(11) NOT NULL,
  `deptName` varchar(100) NOT NULL,
  `vacancies` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `empID` varchar(100) NOT NULL,
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
  `pagibig_number` varchar(20) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_request`
--

CREATE TABLE `employee_request` (
  `requestID` int(11) NOT NULL,
  `empID` varchar(100) NOT NULL,
  `settingID` int(11) NOT NULL,
  `calendarID` int(11) DEFAULT NULL,
  `request_type` enum('COE','Resignation','Leave') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_days` int(11) GENERATED ALWAYS AS (case when `start_date` is not null and `end_date` is not null then to_days(`end_date`) - to_days(`start_date`) + 1 else NULL end) STORED,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `requested_at` datetime DEFAULT current_timestamp(),
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_type`
--

CREATE TABLE `employment_type` (
  `emtypeID` int(11) NOT NULL,
  `typeName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_posting`
--

CREATE TABLE `job_posting` (
  `jobID` int(11) NOT NULL,
  `job_title` varchar(150) NOT NULL,
  `job_description` text NOT NULL,
  `department` int(11) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `educational_level` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `expected_salary` varchar(50) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `employment_type` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `vacancies` int(11) DEFAULT NULL,
  `date_posted` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_settings`
--

CREATE TABLE `leave_settings` (
  `settingID` int(11) NOT NULL,
  `leave_type` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL,
  `time_limit` time DEFAULT NULL,
  `allotted_time` time DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `positionID` int(11) NOT NULL,
  `departmentID` int(11) NOT NULL,
  `position_title` varchar(150) NOT NULL,
  `vacancies` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `system_id` int(11) NOT NULL,
  `system_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL
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
  `profile_pic` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) NOT NULL,
  `token_expiry` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `applicant_employee_id`, `email`, `password`, `role`, `fullname`, `status`, `created_at`, `profile_pic`, `reset_token`, `token_expiry`) VALUES
('', 'EMP-001', 'antonio_rhoannenicole@plpasig.edu.ph', '$2y$10$pYQTWm/o1QeNzw6xVOJpY.1k8kzsweDLjFJuZY1xC4ck6LWzU17NS', 'Employee', 'Rhoanne Nicole Antonio', 'Active', '2025-10-25 10:38:47', NULL, '', ''),
('', 'HOS-002', 'cacho_shaneellamae@plpasig.edu.ph', '$2y$10$2p1gWPRQKuTHk6LKlOuYQOArQkrzuKX0sUKjOhmN/DaS00zugv7L2', 'Applicant', 'Shane Cacho', 'Pending', '0000-00-00 00:00:00', NULL, '2f42dd7d13366d8ab4c7c78490f08ed32e5b9f16a9284e811116acad2aaf366b', '2025-10-26 06:44:36'),
('', 'HOS-001', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$cRImp5xBpPyvK98UkrAMJORzby1WCcgad5ESGSGcJQDSie0/8BpR.', 'Applicant', 'Jojana Jean B. Garabillo', 'Pending', '0000-00-00 00:00:00', NULL, '', ''),
('1', 'admin', 'jojanajeangarabillo@gmail.com', '$2y$10$b/O8vCRZmkYlAI8xinFlYu4nvQ6Xqp4sH3xyfQKR1ONIT.qV02JVS', 'Admin', 'Jojana Garabillo', 'Active', '2025-11-10 00:00:00', NULL, '', ''),
('', 'HOS-003', 'opat09252005@gmail.com', '$2y$10$HMC/RTH50.je3pdq0B1WsusdNd.nnjx0d9TGA93rnL2JeTjix3DrS', 'Applicant', 'Joepat Lacerna', 'Pending', '0000-00-00 00:00:00', NULL, '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`announcementID`);

--
-- Indexes for table `applicant`
--
ALTER TABLE `applicant`
  ADD KEY `fk_applicant_user` (`applicantID`);

--
-- Indexes for table `calendar`
--
ALTER TABLE `calendar`
  ADD PRIMARY KEY (`calendarID`),
  ADD KEY `empID` (`empID`);

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
-- Indexes for table `employee_request`
--
ALTER TABLE `employee_request`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `empID` (`empID`),
  ADD KEY `settingID` (`settingID`),
  ADD KEY `calendarID` (`calendarID`);

--
-- Indexes for table `employment_type`
--
ALTER TABLE `employment_type`
  ADD PRIMARY KEY (`emtypeID`);

--
-- Indexes for table `job_posting`
--
ALTER TABLE `job_posting`
  ADD PRIMARY KEY (`jobID`),
  ADD KEY `department` (`department`),
  ADD KEY `employment_type` (`employment_type`);

--
-- Indexes for table `leave_settings`
--
ALTER TABLE `leave_settings`
  ADD PRIMARY KEY (`settingID`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`positionID`),
  ADD KEY `departmentID` (`departmentID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`system_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `uk_applicant_employee_id` (`applicant_employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `announcementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `calendarID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_request`
--
ALTER TABLE `employee_request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_posting`
--
ALTER TABLE `job_posting`
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant`
--
ALTER TABLE `applicant`
  ADD CONSTRAINT `fk_applicant_user` FOREIGN KEY (`applicantID`) REFERENCES `user` (`applicant_employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `calendar`
--
ALTER TABLE `calendar`
  ADD CONSTRAINT `calendar_ibfk_1` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`);

--
-- Constraints for table `employee_request`
--
ALTER TABLE `employee_request`
  ADD CONSTRAINT `employee_request_ibfk_1` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`),
  ADD CONSTRAINT `employee_request_ibfk_2` FOREIGN KEY (`settingID`) REFERENCES `leave_settings` (`settingID`),
  ADD CONSTRAINT `employee_request_ibfk_3` FOREIGN KEY (`calendarID`) REFERENCES `calendar` (`calendarID`);

--
-- Constraints for table `job_posting`
--
ALTER TABLE `job_posting`
  ADD CONSTRAINT `job_posting_ibfk_1` FOREIGN KEY (`department`) REFERENCES `department` (`deptID`),
  ADD CONSTRAINT `job_posting_ibfk_2` FOREIGN KEY (`employment_type`) REFERENCES `employment_type` (`emtypeID`);

--
-- Constraints for table `position`
--
ALTER TABLE `position`
  ADD CONSTRAINT `position_ibfk_1` FOREIGN KEY (`departmentID`) REFERENCES `department` (`deptID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
