-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 02:16 PM
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
-- Table structure for table `admin_announcement`
--

CREATE TABLE `admin_announcement` (
  `id` int(11) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_posted` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` varchar(20) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`applicantID`, `fullName`, `position_applied`, `department`, `date_applied`, `contact_number`, `email_address`, `home_address`, `job_title`, `company_name`, `date_started`, `in_role`, `university`, `course`, `year_graduated`, `skills`, `summary`, `status`, `profile_pic`) VALUES
('HOS-001', 'Jeopat Lacerna', '', 0, '2025-10-31', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', '', NULL),
('HOS-002', 'Kristina Magnaye', '', 0, '2025-11-04', '09126872700', 'n0305933@gmail.com', 'Pasig City', '', '', '0000-00-00', '', '', '', '0000', 'Hardworking, Adaptable, Sincere, Caring, Dependable', 'I am a hard working person', 'Hired', NULL),
('HOS-001', 'Jeopat Lacerna', '', 0, '2025-10-31', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', '', NULL),
('HOS-001', 'Jeopat Lacerna', '', 0, '2025-10-31', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', '', NULL),
('HOS-002', 'Kristina Magnaye', '', 0, '2025-11-04', '09126872700', 'n0305933@gmail.com', 'Pasig City', '', '', '0000-00-00', '', '', '', '0000', 'Hardworking, Adaptable, Sincere, Caring, Dependable', 'I am a hard working person', 'Hired', NULL),
('HOS-001', 'Jeopat Lacerna', '', 0, '2025-10-31', '', 'opat09252005@gmail.com', '', '', '', '0000-00-00', '', '', '', '0000', '', '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applicant_education`
--

CREATE TABLE `applicant_education` (
  `id` int(11) NOT NULL,
  `applicantID` varchar(100) NOT NULL,
  `school` varchar(255) NOT NULL,
  `degree` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_education`
--

INSERT INTO `applicant_education` (`id`, `applicantID`, `school`, `degree`, `created_at`) VALUES
(0, 'HOS-002', 'Pamantasan ng Lungsod ng Pasig', 'Nursing', '2025-11-04 17:48:30');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_roles`
--

CREATE TABLE `applicant_roles` (
  `id` int(11) NOT NULL,
  `applicantID` varchar(100) NOT NULL,
  `job_title` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_roles`
--

INSERT INTO `applicant_roles` (`id`, `applicantID`, `job_title`, `company_name`, `description`, `created_at`) VALUES
(0, 'HOS-002', 'Anesthologist', 'General Hospital', 'blah blah blah', '2025-11-04 17:47:34');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `applicantID` varchar(100) NOT NULL,
  `jobID` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `applicantID`, `jobID`, `status`, `applied_at`) VALUES
(0, 'HOS-002', 14, 'Pending', '2025-11-04 19:43:12'),
(0, 'HOS-002', 15, 'Pending', '2025-11-04 19:47:37'),
(0, 'HOS-002', 16, 'Pending', '2025-11-04 19:59:53'),
(0, 'HOS-002', 17, 'Pending', '2025-11-04 20:05:02');

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

--
-- Dumping data for table `job_posting`
--

INSERT INTO `job_posting` (`jobID`, `job_title`, `job_description`, `department`, `qualification`, `educational_level`, `skills`, `expected_salary`, `experience_years`, `employment_type`, `location`, `vacancies`, `date_posted`, `closing_date`) VALUES
(14, 'Hematology Resident', 'blah blah', 9, 'Hardworking', NULL, NULL, '150000', 3, 4, NULL, 3, '2025-11-04', '2025-11-20'),
(15, 'Fellow', 'asdwasdwasd', 3, 'test', NULL, NULL, '100000', 1, 5, NULL, 10, '2025-11-04', '2025-11-20'),
(16, 'Rehabilitation Coordinator', 'asdwasdwasdwasd', 5, 'asd', NULL, NULL, '200000', 2, 5, NULL, 10, '2025-11-04', '2025-11-22'),
(17, 'OB-GYN Resident', 'asdkapsdlpalpsda', 8, 'test', NULL, NULL, '200000', 3, 1, NULL, 4, '2025-11-04', '2025-11-15'),
(18, 'HR Director', 'kaokdopqkwopksdkopa', 10, NULL, 'College Graduate', 'Teamwork, Organized, Proactive', '250000', 2, 3, NULL, 2, '2025-11-04', '2025-11-16');

-- --------------------------------------------------------

--
-- Table structure for table `leave_settings`
--

CREATE TABLE `leave_settings` (
  `settingID` int(11) NOT NULL,
  `leave_type` varchar(100) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `employee_limit` int(11) NOT NULL DEFAULT 0,
  `time_limit` varchar(20) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_settings`
--

INSERT INTO `leave_settings` (`settingID`, `leave_type`, `duration`, `employee_limit`, `time_limit`, `created_by`, `created_at`) VALUES
(12, 'Leave', '2025-11-05 to 2025-11-12', 1, '00:00:05', 'Rhoanne Nicole Antonio', '2025-11-03 18:52:00'),
(13, 'Leave', '2025-11-12 to 2025-11-30', 2, '1 day', 'Rhoanne Nicole Antonio', '2025-11-03 18:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `manager_announcement`
--

CREATE TABLE `manager_announcement` (
  `id` int(11) NOT NULL,
  `manager_email` varchar(100) NOT NULL,
  `posted_by` varchar(150) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_posted` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `settingID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager_announcement`
--

INSERT INTO `manager_announcement` (`id`, `manager_email`, `posted_by`, `title`, `message`, `date_posted`, `is_active`, `settingID`) VALUES
(1, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Updated Leave Settings', 'Grettings,\r\n\r\nHere\'s the updated leave, first come first serve will be the basos.', '2025-11-03 18:59:18', 1, 13);

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `positionID` int(11) NOT NULL,
  `departmentID` int(11) NOT NULL,
  `emtypeID` int(11) DEFAULT NULL,
  `position_title` varchar(150) NOT NULL,
  `vacancies` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`positionID`, `departmentID`, `emtypeID`, `position_title`, `vacancies`) VALUES
(1, 1, NULL, 'Anesthetic Technician', NULL),
(2, 1, NULL, 'Nurse Anesthetist', NULL),
(3, 1, NULL, 'Anesthesiology Resident', NULL),
(4, 1, NULL, 'Consultant Anesthesiologist', NULL),
(5, 1, NULL, 'Recovery Room Nurse', NULL),
(6, 1, NULL, 'Senior PACU Nurse', NULL),
(7, 1, NULL, 'Operating Room Nurse', NULL),
(8, 1, NULL, 'OR Nurse Supervisor', NULL),
(9, 2, NULL, 'Radiology Assistant', NULL),
(10, 2, NULL, 'Mammography Technologist', NULL),
(11, 2, NULL, 'Senior Technologist', NULL),
(12, 2, NULL, 'Screening Coordinator', NULL),
(13, 2, NULL, 'Breast Care Nurse', NULL),
(14, 2, NULL, 'Senior Breast Nurse', NULL),
(15, 2, NULL, 'Breast Clinic Manager', NULL),
(16, 3, NULL, 'ECG Technician', NULL),
(17, 3, NULL, 'ECHO Technician', NULL),
(18, 3, NULL, 'Cardiac Technologist', NULL),
(19, 3, NULL, 'Cardiac Lab Supervisor', NULL),
(20, 3, NULL, 'Cardiac Nurse', NULL),
(21, 3, NULL, 'Senior Cardiac Nurse', NULL),
(22, 3, NULL, 'Cardiac Rehabilitation Specialist', NULL),
(23, 3, NULL, 'Cardiology Unit Manager', NULL),
(24, 3, NULL, 'Cardiology Resident', NULL),
(25, 3, NULL, 'Fellow', NULL),
(26, 3, NULL, 'Consultant Cardiologist', NULL),
(27, 4, NULL, 'ENT Clinic Assistant', NULL),
(28, 4, NULL, 'ENT Nurse', NULL),
(29, 4, NULL, 'ENT Resident', NULL),
(30, 4, NULL, 'ENT Consultant', NULL),
(31, 4, NULL, 'Audiologist', NULL),
(32, 4, NULL, 'Senior Audiologist', NULL),
(33, 4, NULL, 'Head of Audiology Services', NULL),
(34, 5, NULL, 'Healthcare Assistant', NULL),
(35, 5, NULL, 'Geriatric Nurse', NULL),
(36, 5, NULL, 'Nurse Practitioner', NULL),
(37, 5, NULL, 'Unit Head', NULL),
(38, 5, NULL, 'Physiotherapist', NULL),
(39, 5, NULL, 'Occupational Therapist', NULL),
(40, 5, NULL, 'Senior Therapist', NULL),
(41, 5, NULL, 'Rehabilitation Coordinator', NULL),
(42, 5, NULL, 'Geriatric Resident', NULL),
(43, 5, NULL, 'Consultant in Elderly Medicine', NULL),
(44, 6, NULL, 'Endoscopy Technician', NULL),
(45, 6, NULL, 'Endoscopy Nurse', NULL),
(46, 6, NULL, 'Senior Endoscopy Nurse', NULL),
(47, 6, NULL, 'Unit Supervisor', NULL),
(48, 6, NULL, 'Gastroenterology Resident', NULL),
(49, 6, NULL, 'Fellow', NULL),
(50, 6, NULL, 'Consultant Gastroenterologist', NULL),
(51, 6, NULL, 'Nutritionist', NULL),
(52, 6, NULL, 'Dietitian', NULL),
(53, 6, NULL, 'Senior Dietitian', NULL),
(54, 6, NULL, 'Department Head (Nutrition)', NULL),
(55, 7, NULL, 'Surgical Technician', NULL),
(56, 7, NULL, 'Scrub Nurse', NULL),
(57, 7, NULL, 'Operating Room Nurse', NULL),
(58, 7, NULL, 'Surgical Charge Nurse', NULL),
(59, 7, NULL, 'Surgical Resident', NULL),
(60, 7, NULL, 'Senior Resident', NULL),
(61, 7, NULL, 'Consultant Surgeon', NULL),
(62, 7, NULL, 'Ward Nurse', NULL),
(63, 7, NULL, 'Senior Nurse', NULL),
(64, 7, NULL, 'Nurse Unit Manager', NULL),
(65, 8, NULL, 'OB-GYN Resident', NULL),
(66, 8, NULL, 'Consultant Gynecologist', NULL),
(67, 8, NULL, 'Midwife', NULL),
(68, 8, NULL, 'Senior Midwife', NULL),
(69, 8, NULL, 'Labor and Delivery Supervisor', NULL),
(70, 8, NULL, 'Gynecology Nurse', NULL),
(71, 8, NULL, 'Nurse Coordinator', NULL),
(72, 8, NULL, 'Nurse Manager', NULL),
(73, 9, NULL, 'Phlebotomist', NULL),
(74, 9, NULL, 'Medical Laboratory Scientist (Hematology)', NULL),
(75, 9, NULL, 'Senior Lab Scientist', NULL),
(76, 9, NULL, 'Lab Supervisor', NULL),
(77, 9, NULL, 'Hematology Lab Manager', NULL),
(78, 9, NULL, 'Hematology Resident', NULL),
(79, 9, NULL, 'Consultant Hematologist', NULL),
(80, 9, NULL, 'Oncology Nurse (Hematology Unit)', NULL),
(81, 9, NULL, 'Senior Hematology Nurse', NULL),
(82, 9, NULL, 'Nurse Unit Head', NULL),
(83, 10, NULL, 'HR Clerk', NULL),
(84, 10, NULL, 'HR Assistant', NULL),
(85, 10, NULL, 'HR Officer', NULL),
(86, 10, NULL, 'HR Supervisor', NULL),
(87, 10, NULL, 'HR Manager', NULL),
(88, 10, NULL, 'HR Director', NULL),
(89, 10, NULL, 'Recruitment Specialist', NULL),
(90, 10, NULL, 'Senior Recruitment Officer', NULL),
(91, 10, NULL, 'Recruitment Manager', NULL),
(92, 10, NULL, 'Training and Development Coordinator', NULL),
(93, 10, NULL, 'HR Manager (Training and Organizational Development)', NULL);

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
('EMP-002', 'EMP-002', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$FFCiNt8biEO542SyB35/T.S8Chdw7jySMzt0ZjH2TBFSHXHJEbCVC', 'Employee', 'Jackson Wang', 'Active', '2025-11-03 16:43:38', NULL, '', '', 'Staff'),
('1', 'admin', 'jojanajeangarabillo@gmail.com', '$2y$10$b/O8vCRZmkYlAI8xinFlYu4nvQ6Xqp4sH3xyfQKR1ONIT.qV02JVS', 'Admin', 'Jojana Garabillo', 'Active', '2025-11-10 00:00:00', NULL, '', '', NULL),
('', 'HOS-002', 'n0305933@gmail.com', '$2y$10$XQfSm9e2jfBacwj1uo7dvuStYoPgYvPuALnnVYd0ZXdK3jXs9F/mu', 'Applicant', 'Kristina Magnaye', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL),
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
(21, 1, 1, 2, 1, 'On-Going', '2025-10-31 12:53:36'),
(22, 9, 73, 1, 3, 'On-Going', '2025-11-03 13:14:22'),
(23, 3, 18, 1, 3, 'On-Going', '2025-11-04 09:04:22'),
(24, 1, 3, 5, 5, 'On-Going', '2025-11-04 09:08:44'),
(25, 4, 31, 2, 5, 'On-Going', '2025-11-04 09:10:20'),
(26, 5, 41, 5, 10, 'On-Going', '2025-11-04 09:13:04'),
(27, 9, 78, 4, 3, 'On-Going', '2025-11-04 09:14:10'),
(28, 3, 25, 5, 10, 'On-Going', '2025-11-04 11:46:40'),
(29, 10, 88, 3, 2, 'On-Going', '2025-11-04 11:50:46'),
(30, 8, 65, 1, 4, 'On-Going', '2025-11-04 12:03:58'),
(31, 9, 75, 5, 1, 'To Post', '2025-11-04 13:13:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_announcement`
--
ALTER TABLE `admin_announcement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_email` (`admin_email`);

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
-- Indexes for table `applicant_education`
--
ALTER TABLE `applicant_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_applicant_education_applicant` (`applicantID`);

--
-- Indexes for table `applicant_roles`
--
ALTER TABLE `applicant_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_applicant_roles_applicant` (`applicantID`);

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
-- Indexes for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_email` (`manager_email`),
  ADD KEY `fk_setting` (`settingID`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`positionID`),
  ADD KEY `departmentID` (`departmentID`),
  ADD KEY `fk_position_employment` (`emtypeID`);

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
-- AUTO_INCREMENT for table `admin_announcement`
--
ALTER TABLE `admin_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `announcementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_education`
--
ALTER TABLE `applicant_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_roles`
--
ALTER TABLE `applicant_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_announcement`
--
ALTER TABLE `admin_announcement`
  ADD CONSTRAINT `admin_announcement_ibfk_1` FOREIGN KEY (`admin_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

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
-- Constraints for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  ADD CONSTRAINT `fk_manager_leave` FOREIGN KEY (`settingID`) REFERENCES `leave_settings` (`settingID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_setting` FOREIGN KEY (`settingID`) REFERENCES `leave_settings` (`settingID`) ON DELETE CASCADE,
  ADD CONSTRAINT `manager_announcement_ibfk_1` FOREIGN KEY (`manager_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `position`
--
ALTER TABLE `position`
  ADD CONSTRAINT `fk_position_employment` FOREIGN KEY (`emtypeID`) REFERENCES `employment_type` (`emtypeID`),
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
