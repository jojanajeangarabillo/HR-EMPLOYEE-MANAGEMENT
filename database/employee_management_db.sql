-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2025 at 02:40 PM
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
('HOS-001', 'Jeopat Lacerna', '', 0, '2025-10-31', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', NULL);

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

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`deptID`, `deptName`, `vacancies`) VALUES
(1, 'Anesthetics Department', NULL),
(2, 'Breast Screening Department', NULL),
(3, 'Cardiology Department', NULL),
(4, 'Ear, Nose and Throat (ENT) Department', NULL),
(5, 'Elderly Services (Geriatrics)', NULL),
(6, 'Gastroenterology Department', NULL),
(7, 'General Surgery Department', NULL),
(8, 'Gynecology Department', NULL),
(9, 'Hematology Department', NULL),
(10, 'Human Resources (HR) Department', NULL);

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

--
-- Dumping data for table `employment_type`
--

INSERT INTO `employment_type` (`emtypeID`, `typeName`) VALUES
(1, 'Full Time'),
(2, 'Part Time'),
(3, 'Regular'),
(4, 'Contractual'),
(5, 'Internship');

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

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`positionID`, `departmentID`, `position_title`, `vacancies`) VALUES
(1, 1, 'Anesthetic Technician', NULL),
(2, 1, 'Nurse Anesthetist', NULL),
(3, 1, 'Anesthesiology Resident', NULL),
(4, 1, 'Consultant Anesthesiologist', NULL),
(5, 1, 'Recovery Room Nurse', NULL),
(6, 1, 'Senior PACU Nurse', NULL),
(7, 1, 'Operating Room Nurse', NULL),
(8, 1, 'OR Nurse Supervisor', NULL),
(9, 2, 'Radiology Assistant', NULL),
(10, 2, 'Mammography Technologist', NULL),
(11, 2, 'Senior Technologist', NULL),
(12, 2, 'Screening Coordinator', NULL),
(13, 2, 'Breast Care Nurse', NULL),
(14, 2, 'Senior Breast Nurse', NULL),
(15, 2, 'Breast Clinic Manager', NULL),
(16, 3, 'ECG Technician', NULL),
(17, 3, 'ECHO Technician', NULL),
(18, 3, 'Cardiac Technologist', NULL),
(19, 3, 'Cardiac Lab Supervisor', NULL),
(20, 3, 'Cardiac Nurse', NULL),
(21, 3, 'Senior Cardiac Nurse', NULL),
(22, 3, 'Cardiac Rehabilitation Specialist', NULL),
(23, 3, 'Cardiology Unit Manager', NULL),
(24, 3, 'Cardiology Resident', NULL),
(25, 3, 'Fellow', NULL),
(26, 3, 'Consultant Cardiologist', NULL),
(27, 4, 'ENT Clinic Assistant', NULL),
(28, 4, 'ENT Nurse', NULL),
(29, 4, 'ENT Resident', NULL),
(30, 4, 'ENT Consultant', NULL),
(31, 4, 'Audiologist', NULL),
(32, 4, 'Senior Audiologist', NULL),
(33, 4, 'Head of Audiology Services', NULL),
(34, 5, 'Healthcare Assistant', NULL),
(35, 5, 'Geriatric Nurse', NULL),
(36, 5, 'Nurse Practitioner', NULL),
(37, 5, 'Unit Head', NULL),
(38, 5, 'Physiotherapist', NULL),
(39, 5, 'Occupational Therapist', NULL),
(40, 5, 'Senior Therapist', NULL),
(41, 5, 'Rehabilitation Coordinator', NULL),
(42, 5, 'Geriatric Resident', NULL),
(43, 5, 'Consultant in Elderly Medicine', NULL),
(44, 6, 'Endoscopy Technician', NULL),
(45, 6, 'Endoscopy Nurse', NULL),
(46, 6, 'Senior Endoscopy Nurse', NULL),
(47, 6, 'Unit Supervisor', NULL),
(48, 6, 'Gastroenterology Resident', NULL),
(49, 6, 'Fellow', NULL),
(50, 6, 'Consultant Gastroenterologist', NULL),
(51, 6, 'Nutritionist', NULL),
(52, 6, 'Dietitian', NULL),
(53, 6, 'Senior Dietitian', NULL),
(54, 6, 'Department Head (Nutrition)', NULL),
(55, 7, 'Surgical Technician', NULL),
(56, 7, 'Scrub Nurse', NULL),
(57, 7, 'Operating Room Nurse', NULL),
(58, 7, 'Surgical Charge Nurse', NULL),
(59, 7, 'Surgical Resident', NULL),
(60, 7, 'Senior Resident', NULL),
(61, 7, 'Consultant Surgeon', NULL),
(62, 7, 'Ward Nurse', NULL),
(63, 7, 'Senior Nurse', NULL),
(64, 7, 'Nurse Unit Manager', NULL),
(65, 8, 'OB-GYN Resident', NULL),
(66, 8, 'Consultant Gynecologist', NULL),
(67, 8, 'Midwife', NULL),
(68, 8, 'Senior Midwife', NULL),
(69, 8, 'Labor and Delivery Supervisor', NULL),
(70, 8, 'Gynecology Nurse', NULL),
(71, 8, 'Nurse Coordinator', NULL),
(72, 8, 'Nurse Manager', NULL),
(73, 9, 'Phlebotomist', NULL),
(74, 9, 'Medical Laboratory Scientist (Hematology)', NULL),
(75, 9, 'Senior Lab Scientist', NULL),
(76, 9, 'Lab Supervisor', NULL),
(77, 9, 'Hematology Lab Manager', NULL),
(78, 9, 'Hematology Resident', NULL),
(79, 9, 'Consultant Hematologist', NULL),
(80, 9, 'Oncology Nurse (Hematology Unit)', NULL),
(81, 9, 'Senior Hematology Nurse', NULL),
(82, 9, 'Nurse Unit Head', NULL),
(83, 10, 'HR Clerk', NULL),
(84, 10, 'HR Assistant', NULL),
(85, 10, 'HR Officer', NULL),
(86, 10, 'HR Supervisor', NULL),
(87, 10, 'HR Manager', NULL),
(88, 10, 'HR Director', NULL),
(89, 10, 'Recruitment Specialist', NULL),
(90, 10, 'Senior Recruitment Officer', NULL),
(91, 10, 'Recruitment Manager', NULL),
(92, 10, 'Training and Development Coordinator', NULL),
(93, 10, 'HR Manager (Training and Organizational Development)', NULL);

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
  `token_expiry` varchar(255) NOT NULL,
  `sub_role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `applicant_employee_id`, `email`, `password`, `role`, `fullname`, `status`, `created_at`, `profile_pic`, `reset_token`, `token_expiry`, `sub_role`) VALUES
('', 'EMP-001', 'antonio_rhoannenicole@plpasig.edu.ph', '$2y$10$pYQTWm/o1QeNzw6xVOJpY.1k8kzsweDLjFJuZY1xC4ck6LWzU17NS', 'Employee', 'Rhoanne Nicole Antonio', 'Active', '2025-10-25 10:38:47', NULL, '', '', 'HR Manager'),
('1', 'admin', 'jojanajeangarabillo@gmail.com', '$2y$10$b/O8vCRZmkYlAI8xinFlYu4nvQ6Xqp4sH3xyfQKR1ONIT.qV02JVS', 'Admin', 'Jojana Garabillo', 'Active', '2025-11-10 00:00:00', NULL, '', '', NULL),
('', 'HOS-001', 'opat09252005@gmail.com', '$2y$10$yV0hT3DpZIY9WlaM2YPpS..whgUiV5Zjrhj2HF0Ekkb9wYhh9SgA2', 'Applicant', 'Jeopat Lacerna', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vacancies`
--

CREATE TABLE `vacancies` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `employment_type_id` int(11) NOT NULL,
  `vacancy_count` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'To Post',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vacancies`
--

INSERT INTO `vacancies` (`id`, `department_id`, `position_id`, `employment_type_id`, `vacancy_count`, `status`, `created_at`) VALUES
(17, 1, 1, 1, 2, 'On-Going', '2025-10-31 11:18:15'),
(19, 1, 2, 1, 2, 'On-Going', '2025-10-31 12:02:06'),
(20, 1, 1, 5, 1, 'On-Going', '2025-10-31 12:02:23'),
(21, 1, 1, 2, 1, 'To Post', '2025-10-31 12:53:36');

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
  ADD PRIMARY KEY (`empID`),
  ADD KEY `fk_employee_emtype` (`employment_type`);

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
-- Indexes for table `vacancies`
--
ALTER TABLE `vacancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `fk_vacancies_employment_type` (`employment_type_id`);

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
-- AUTO_INCREMENT for table `employment_type`
--
ALTER TABLE `employment_type`
  MODIFY `emtypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vacancies`
--
ALTER TABLE `vacancies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_emtype` FOREIGN KEY (`employment_type`) REFERENCES `employment_type` (`emtypeID`) ON UPDATE CASCADE;

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

--
-- Constraints for table `vacancies`
--
ALTER TABLE `vacancies`
  ADD CONSTRAINT `fk_vacancies_employment_type` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_type` (`emtypeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `vacancies_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`deptID`),
  ADD CONSTRAINT `vacancies_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `position` (`positionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
