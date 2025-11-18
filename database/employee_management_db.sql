-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 10:23 AM
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
  `department` varchar(150) NOT NULL,
  `type_name` varchar(50) DEFAULT NULL,
  `date_applied` date NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `home_address` varchar(255) NOT NULL,
  `previous_job` varchar(100) DEFAULT NULL,
  `company_name` varchar(150) NOT NULL,
  `date_started` date NOT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `in_role` varchar(5) NOT NULL,
  `university` varchar(150) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `skills` text NOT NULL,
  `summary` text NOT NULL,
  `status` varchar(20) NOT NULL,
  `hired_at` date DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`applicantID`, `fullName`, `position_applied`, `department`, `type_name`, `date_applied`, `contact_number`, `email_address`, `home_address`, `previous_job`, `company_name`, `date_started`, `years_experience`, `in_role`, `university`, `course`, `year_graduated`, `skills`, `summary`, `status`, `hired_at`, `profile_pic`) VALUES
('HOS-002', 'Kristina Magnaye', '', '0', '', '2025-11-04', '09126872701', 'n0305933@gmail.com', 'Pasig City', 'saas', 'sas', '0000-00-00', 0, '', 'Aa', 'BSN', '2005', 'Hardworking, Adaptable, Sincere, Caring, Dependable', 'I am a hard working person\r\n\r\nRole: saas at sas\r\nsaas', 'Pending', NULL, 'applicant_HOS-002.jpg'),
('HOS-004', 'Jojana Baglan', 'Phlebotomist', 'Hematology Department', '', '2025-11-13', '01', 'garabillo_jojanajean@plpasig.edu.ph', 'Pasig', 'ax', 'xs', '0000-00-00', 5, 'no', 'HA', 'BSIT', '0000', 'sa, sa, sa, sa, sa', 'Role: CEO at APPLE\nSKJXAK\n\nRole: ax at xs\nxs', 'Archived', '2025-11-14', 'applicant_HOS-004.jpg'),
('HOS-007', 'Joepat Lacerna', 'Consultant Gynecologist', 'Gynecology Department', 'Full Time', '2025-11-18', '', 'opat09252005@gmail.com', '', NULL, '', '0000-00-00', NULL, '', 'PLP', 'BSA', '2027', '', '', 'Archived', '2025-11-18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `applicantID` varchar(100) NOT NULL,
  `jobID` int(11) NOT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `type_name` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `applications`
--
DELIMITER $$
CREATE TRIGGER `set_initial_application_status` BEFORE INSERT ON `applications` FOR EACH ROW BEGIN
    DECLARE applicant_status VARCHAR(20);

    SELECT status INTO applicant_status
    FROM applicant
    WHERE applicantID = NEW.applicantID
    LIMIT 1;

    SET NEW.status = applicant_status;
END
$$
DELIMITER ;

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
  `department` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `home_address` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `TIN_number` varchar(20) DEFAULT NULL,
  `phil_health_number` varchar(20) DEFAULT NULL,
  `SSS_number` varchar(20) DEFAULT NULL,
  `pagibig_number` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `hired_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`empID`, `fullname`, `department`, `position`, `type_name`, `email_address`, `home_address`, `contact_number`, `date_of_birth`, `gender`, `emergency_contact`, `TIN_number`, `phil_health_number`, `SSS_number`, `pagibig_number`, `profile_pic`, `hired_at`) VALUES
('EMP-001', 'Rhoanne Nicole Antonio', 'Human Resources (HR) Department', 'HR Manager', 'Full Time', 'antonio_rhoannenicole@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-002', 'Joepat Lacerna', 'Gynecology Department', 'Consultant Gynecologist', 'Full Time', 'opat09252005@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18'),
('EMP-003', 'Jane Garabillo', 'Human Resources (HR) Department', 'HR Director', 'Full Time', 'jojanajeangarabillo@gmail.com', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-004', 'Jhanna Jaroda', 'Human Resources (HR) Department', 'Recruitment Manager', 'Full Time', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-005', 'Shane Ella Cacho', 'Human Resources (HR) Department', 'Training and Development Coordinator', 'Full Time', 'cacho_shaneellamae@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-007', 'Sierra Madre', 'Human Resources (HR) Department', 'HR Assistant', 'Full Time', 'sheyn.cacho@gmail.com', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18');

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
(27, 'Anesthetic Technician', 'SASD', 1, NULL, 'BSN', 'As', '1234', 12, 4, NULL, 1, '2025-11-10', '2025-11-26'),
(28, 'Consultant Gynecologist', 'AKJSNA', 8, NULL, 'BSA', 'Ajshaujs', '1235', 10, 1, NULL, 1, '2025-11-10', '2025-11-29'),
(29, 'Nurse Anesthetist', 'ASDCCDSC', 1, NULL, 'BSN', 'As', '1235', 12, 4, NULL, 1, '2025-11-10', '2025-11-28'),
(30, 'Radiology Assistant', 'SDXSAD', 2, NULL, 'BSN', 'Ajhbs', '123345', 12, 4, NULL, 1, '2025-11-10', '2025-11-24'),
(31, 'Anesthetic Technician', 'ASD', 1, NULL, 'BSIT', 'Akjz', '1223345', 12, 4, NULL, 1, '2025-11-10', '2025-11-28'),
(32, 'Radiology Assistant', 'sax', 2, NULL, 'bft', 'As', '123', 12, 4, NULL, 1, '2025-11-10', '2025-11-20'),
(33, 'Screening Coordinator', 'zas', 2, NULL, 'as', 'Sa', '1123', 11, 5, NULL, 1, '2025-11-10', '2025-11-28'),
(34, 'Phlebotomist', 'DCD', 9, NULL, 'BSIT', 'Hfc', '216512', 11, 1, NULL, 0, '2025-11-13', '2025-11-14');

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
(13, 'Leave', '2025-11-12 to 2025-11-30', 2, '1 day', 'Rhoanne Nicole Antonio', '2025-11-03 18:58:39'),
(14, 'Leave', '2025-11-12 to 2025-11-30', 2, '3 days', 'Rhoanne Nicole Antonio', '2025-11-08 10:04:42');

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
(1, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Updated Leave Settings', 'Grettings,\r\n\r\nHere\'s the updated leave, first come first serve will be the basos.', '2025-11-03 18:59:18', 1, 13),
(2, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Leave', 'shgcuksayc', '2025-11-08 10:04:56', 1, 14);

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
(84, 10, NULL, 'HR Assistant', NULL),
(85, 10, NULL, 'HR Officer', NULL),
(87, 10, NULL, 'HR Manager', NULL),
(88, 10, NULL, 'HR Director', NULL),
(89, 10, NULL, 'Recruitment Manager', NULL),
(92, 10, NULL, 'Training and Development Coordinator', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rejected_applications`
--

CREATE TABLE `rejected_applications` (
  `id` int(11) NOT NULL,
  `applicantID` varchar(100) NOT NULL,
  `jobID` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `rejected_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rejected_applications`
--

INSERT INTO `rejected_applications` (`id`, `applicantID`, `jobID`, `reason`, `rejected_at`) VALUES
(3, 'HOS-002', 28, 'Course mismatch', '2025-11-10 20:31:34'),
(55, 'HOS-004', 27, 'Course mismatch', '2025-11-13 21:01:07'),
(56, 'HOS-004', 28, 'Course mismatch', '2025-11-13 21:01:08'),
(57, 'HOS-004', 29, 'Course mismatch', '2025-11-13 21:01:15'),
(58, 'HOS-004', 32, 'Course mismatch', '2025-11-13 21:01:17'),
(59, 'HOS-004', 33, 'Course mismatch', '2025-11-13 21:01:32'),
(60, 'HOS-004', 30, 'Course mismatch', '2025-11-13 21:01:35'),
(79, 'HOS-007', 34, 'Course mismatch', '2025-11-18 14:06:05'),
(80, 'HOS-007', 27, 'Course mismatch', '2025-11-18 14:10:25'),
(81, 'HOS-007', 29, 'Course mismatch', '2025-11-18 15:24:40'),
(82, 'HOS-007', 30, 'Course mismatch', '2025-11-18 16:03:03');

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
('', 'ADM-001', 'admin_jojanajean@plpasig.edu.ph', '$2y$10$wXATHyunepSPHPGolMHnqe54maqVldT7WMxe3XbPB8vwvjPxehk/y', 'Admin', 'Jojana Jean', 'Active', '2025-11-07 23:53:43', NULL, '', '', NULL),
('', 'EMP-001', 'antonio_rhoannenicole@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Rhoanne Nicole Antonio', 'Active', '2025-10-25 10:38:47', NULL, '', '', 'HR Manager'),
('USR-006', 'EMP-005', 'cacho_shaneellamae@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Shane Ella Cacho', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'Training and Development Coordinator'),
('USR-007', 'EMP-006', 'gutierrez_jodielynn@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jodie Lyn Gutierrez', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'HR Officer'),
('USR-005', 'EMP-004', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jhanna Jaroda', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'Recruitment Manager'),
('USR-003', 'EMP-003', 'jojanajeangarabillo@gmail.com', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jane Garabillo', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'HR Director'),
('', 'HOS-002', 'n0305933@gmail.com', '$2y$10$wXATHyunepSPHPGolMHnqe54maqVldT7WMxe3XbPB8vwvjPxehk/y', 'Applicant', 'Kristina Magnaye', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL),
('', 'EMP-002', 'opat09252005@gmail.com', '$2y$10$0Pg1Uu6LmxuAcfskgoLvz.CDYUrl5mM/aEh.Q85vKcuF2fTHRxG.a', 'Employee', 'Joepat Lacerna', 'Active', '0000-00-00 00:00:00', NULL, '0da2392f59a3d31bc9f2565fc8cd6bc8', '2025-11-21 09:40:51', NULL),
('USR-008', 'EMP-007', 'sheyn.cacho@gmail.com', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Sierra Madre', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'HR Assistant');

-- --------------------------------------------------------

--
-- Table structure for table `user_archive`
--

CREATE TABLE `user_archive` (
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
(41, 1, 1, 4, 1, 'On-Going', '2025-11-10 11:59:15'),
(42, 8, 66, 1, 1, 'On-Going', '2025-11-10 12:04:51'),
(43, 1, 2, 4, 1, 'On-Going', '2025-11-10 12:32:38'),
(44, 2, 9, 4, 1, 'On-Going', '2025-11-10 12:34:52'),
(45, 1, 1, 4, 1, 'On-Going', '2025-11-10 13:44:28'),
(46, 2, 9, 4, 1, 'On-Going', '2025-11-10 13:55:14'),
(47, 2, 12, 5, 1, 'On-Going', '2025-11-10 13:56:40'),
(48, 9, 73, 1, 1, 'On-Going', '2025-11-10 14:04:01');

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
  ADD UNIQUE KEY `applicantID_unique` (`applicantID`),
  ADD KEY `fk_applicant_user` (`applicantID`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_applications_job` (`jobID`),
  ADD KEY `fk_applicant` (`applicantID`);

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
  ADD KEY `fk_employee_emtype` (`type_name`);

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
-- Indexes for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobID` (`jobID`),
  ADD KEY `rejected_applications_ibfk_1` (`applicantID`);

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
-- Indexes for table `user_archive`
--
ALTER TABLE `user_archive`
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
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vacancies`
--
ALTER TABLE `vacancies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_announcement`
--
ALTER TABLE `admin_announcement`
  ADD CONSTRAINT `admin_announcement_ibfk_1` FOREIGN KEY (`admin_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applicant` FOREIGN KEY (`applicantID`) REFERENCES `user` (`applicant_employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_job` FOREIGN KEY (`jobID`) REFERENCES `job_posting` (`jobID`) ON DELETE CASCADE;

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
-- Constraints for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  ADD CONSTRAINT `rejected_applications_ibfk_1` FOREIGN KEY (`applicantID`) REFERENCES `applicant` (`applicantID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rejected_applications_ibfk_2` FOREIGN KEY (`jobID`) REFERENCES `job_posting` (`jobID`);

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
