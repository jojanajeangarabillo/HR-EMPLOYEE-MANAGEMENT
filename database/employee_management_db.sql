-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 04:48 PM
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
  `job_title` varchar(100) DEFAULT NULL,
  `company_name` varchar(150) NOT NULL,
  `date_started` date NOT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `in_role` varchar(5) NOT NULL,
  `university` varchar(150) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_graduated` year(4) DEFAULT NULL,
  `skills` text NOT NULL,
  `summary` text NOT NULL,
  `status` varchar(20) NOT NULL,
  `hired_at` date DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`applicantID`, `fullName`, `position_applied`, `department`, `type_name`, `date_applied`, `contact_number`, `email_address`, `home_address`, `job_title`, `company_name`, `date_started`, `years_experience`, `in_role`, `university`, `course`, `year_graduated`, `skills`, `summary`, `status`, `hired_at`, `profile_pic`) VALUES
('HOS-002', 'Nelly Bousted', '', '0', '', '2025-11-04', '16754367', 'n0305933@gmail.com', 'Pasig', 'BDO', 'BDO', '0000-00-00', 10, '', 'PLP', 'BSA', '2027', '', '', 'Pending', NULL, 'applicant_HOS-002.jpg'),
('HOS-004', 'Jojana Baglan', 'Phlebotomist', 'Hematology Department', '', '2025-11-13', '01', 'garabillo_jojanajean@plpasig.edu.ph', 'Pasig', 'ax', 'xs', '0000-00-00', 5, 'no', 'HA', 'BSIT', '0000', 'sa, sa, sa, sa, sa', 'Role: CEO at APPLE\nSKJXAK\n\nRole: ax at xs\nxs', 'Archived', '2025-11-14', 'applicant_HOS-004.jpg'),
('HOS-005', 'Joepat Lacerna', 'Consultant Gynecologist', 'Gynecology Department', 'Full Time', '2025-11-19', '0909', 'opat09252005@gmail.com', 'Taguig', 'ceo', 'microsoft', '0000-00-00', 10, 'no', 'plp', 'BSA', '2027', 'as, SSA, sa, as, a', 'a,xjsxkab', 'Archived', '2025-11-20', 'applicant_HOS-005.png'),
('HOS-006', 'Joejana Jean', 'Consultant Gynecologist', 'Gynecology Department', 'Full Time', '2025-11-20', '090909', 'garabillo_jojanajean@plpasig.edu.ph', 'Taytay Rizal', 'CEO', 'MICROSOFT', '0000-00-00', 15, '', 'HARVARD', 'BSA', '2027', 'ganda, ganda, ganda, ganda, ganda', 'xsxmjslao;cubwapsuixaq[', 'Hired', '2025-11-20', 'applicant_HOS-006.jpg');

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
  `deptName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`deptID`, `deptName`) VALUES
(1, 'Anesthetics Department'),
(2, 'Breast Screening Department'),
(3, 'Cardiology Department'),
(4, 'Ear, Nose and Throat (ENT) Department'),
(5, 'Elderly Services (Geriatrics)'),
(6, 'Gastroenterology Department'),
(7, 'General Surgery Department'),
(8, 'Gynecology Department'),
(9, 'Hematology Department'),
(10, 'Human Resources (HR) Department'),
(15, 'IT Department'),
(16, 'Unity Department');

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
('EMP-004', 'Jhanna Jaroda', 'Human Resources (HR) Department', 'Recruitment Manager', 'Full Time', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-005', 'Shane Ella Cacho', 'Human Resources (HR) Department', 'Training and Development Coordinator', 'Full Time', 'cacho_shaneellamae@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-007', 'Jane Garabillo', 'HR', 'Employee', 'Full Time', 'jojanajeangarabillo@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18'),
('EMP-008', 'Joepat Lacerna', 'Gynecology Department', 'Consultant Gynecologist', 'Full Time', 'opat09252005@gmail.com', 'Taguig', '0909', '2005-09-25', 'Male', '0101', '1324-10287893-1902', '11-000000-21', '12-7253179-121', '123-456-789', 'employee_EMP-008.jpg', '2025-11-20');

-- --------------------------------------------------------

--
-- Table structure for table `employee_request`
--

CREATE TABLE `employee_request` (
  `request_id` int(11) NOT NULL,
  `empID` varchar(100) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `type_name` varchar(50) DEFAULT NULL,
  `email_address` varchar(150) DEFAULT NULL,
  `e_signature` varchar(255) DEFAULT NULL,
  `request_type_id` int(11) NOT NULL,
  `request_type_name` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `action_by` varchar(100) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `leave_type_id` int(11) DEFAULT NULL,
  `leave_type_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_request`
--

INSERT INTO `employee_request` (`request_id`, `empID`, `fullname`, `department`, `position`, `type_name`, `email_address`, `e_signature`, `request_type_id`, `request_type_name`, `reason`, `status`, `action_by`, `requested_at`, `leave_type_id`, `leave_type_name`) VALUES
(26, 'EMP-008', 'Joepat Lacerna', 'Gynecology Department', 'Consultant Gynecologist', 'Full Time', 'opat09252005@gmail.com', '', 1, 'Leave', 'SASASA', 'Rejected', NULL, '2025-11-20 16:06:45', 2, 'Vacation Leave'),
(27, 'EMP-001', 'Rhoanne Nicole Antonio', 'Human Resources (HR) Department', 'HR Manager', 'Full Time', 'antonio_rhoannenicole@plpasig.edu.ph', 'uploads/signatures/1763735692_85a8de63-2a68-46e6-b899-2eeb29145031.jfif', 1, 'Leave', 'lasdmwasdwmasd', 'Pending', NULL, '2025-11-21 22:34:52', 1, 'Sick Leave');

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
(29, 'Nurse Anesthetist', 'ASDCCDSC', 1, NULL, 'BSN', 'As', '1235', 12, 4, NULL, 1, '2025-11-10', '2025-11-28'),
(30, 'Radiology Assistant', 'SDXSAD', 2, NULL, 'BSN', 'Ajhbs', '123345', 12, 4, NULL, 1, '2025-11-10', '2025-11-24'),
(31, 'Anesthetic Technician', 'ASD', 1, NULL, 'BSIT', 'Akjz', '1223345', 12, 4, NULL, 1, '2025-11-10', '2025-11-28'),
(32, 'Radiology Assistant', 'sax', 2, NULL, 'bft', 'As', '123', 12, 4, NULL, 1, '2025-11-10', '2025-11-20'),
(33, 'Screening Coordinator', 'zas', 2, NULL, 'as', 'Sa', '1123', 11, 5, NULL, 1, '2025-11-10', '2025-11-28'),
(34, 'Phlebotomist', 'DCD', 9, NULL, 'BSIT', 'Hfc', '216512', 11, 1, NULL, 0, '2025-11-13', '2025-11-14'),
(35, 'Anesthetic Technician', 'KHXSKWIQGS', 1, NULL, 'BSA', 'Mxhsakx', '12232', 5, 1, NULL, 0, '2025-11-20', '2025-11-23');

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
(14, 'Leave', '2025-11-12 to 2025-11-30', 2, '3 days', 'Rhoanne Nicole Antonio', '2025-11-08 10:04:42'),
(15, 'Leave', '2025-11-20 to 2025-11-21', 2, '3 days', 'Jojana Jean', '2025-11-19 22:15:56');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL,
  `request_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `request_type_id`, `leave_type_name`) VALUES
(1, 1, 'Sick Leave'),
(2, 1, 'Vacation Leave'),
(3, 1, 'Maternity Leave'),
(4, 1, 'Paternity Leave'),
(5, 1, 'Bereavement Leave');

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
(2, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Leave', 'shgcuksayc', '2025-11-08 10:04:56', 1, 14),
(3, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Attention', 'jxslaxobs', '2025-11-19 22:16:13', 1, 15);

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `positionID` int(11) NOT NULL,
  `departmentID` int(11) NOT NULL,
  `emtypeID` int(11) DEFAULT NULL,
  `position_title` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`positionID`, `departmentID`, `emtypeID`, `position_title`) VALUES
(1, 1, NULL, 'Anesthetic Technician'),
(2, 1, NULL, 'Nurse Anesthetist'),
(3, 1, NULL, 'Anesthesiology Resident'),
(4, 1, NULL, 'Consultant Anesthesiologist'),
(5, 1, NULL, 'Recovery Room Nurse'),
(6, 1, NULL, 'Senior PACU Nurse'),
(7, 1, NULL, 'Operating Room Nurse'),
(8, 1, NULL, 'OR Nurse Supervisor'),
(9, 2, NULL, 'Radiology Assistant'),
(10, 2, NULL, 'Mammography Technologist'),
(11, 2, NULL, 'Senior Technologist'),
(12, 2, NULL, 'Screening Coordinator'),
(13, 2, NULL, 'Breast Care Nurse'),
(14, 2, NULL, 'Senior Breast Nurse'),
(15, 2, NULL, 'Breast Clinic Manager'),
(16, 3, NULL, 'ECG Technician'),
(17, 3, NULL, 'ECHO Technician'),
(18, 3, NULL, 'Cardiac Technologist'),
(19, 3, NULL, 'Cardiac Lab Supervisor'),
(20, 3, NULL, 'Cardiac Nurse'),
(21, 3, NULL, 'Senior Cardiac Nurse'),
(22, 3, NULL, 'Cardiac Rehabilitation Specialist'),
(23, 3, NULL, 'Cardiology Unit Manager'),
(24, 3, NULL, 'Cardiology Resident'),
(25, 3, NULL, 'Fellow'),
(26, 3, NULL, 'Consultant Cardiologist'),
(27, 4, NULL, 'ENT Clinic Assistant'),
(28, 4, NULL, 'ENT Nurse'),
(29, 4, NULL, 'ENT Resident'),
(30, 4, NULL, 'ENT Consultant'),
(31, 4, NULL, 'Audiologist'),
(32, 4, NULL, 'Senior Audiologist'),
(33, 4, NULL, 'Head of Audiology Services'),
(34, 5, NULL, 'Healthcare Assistant'),
(35, 5, NULL, 'Geriatric Nurse'),
(36, 5, NULL, 'Nurse Practitioner'),
(37, 5, NULL, 'Unit Head'),
(38, 5, NULL, 'Physiotherapist'),
(39, 5, NULL, 'Occupational Therapist'),
(40, 5, NULL, 'Senior Therapist'),
(41, 5, NULL, 'Rehabilitation Coordinator'),
(42, 5, NULL, 'Geriatric Resident'),
(43, 5, NULL, 'Consultant in Elderly Medicine'),
(44, 6, NULL, 'Endoscopy Technician'),
(45, 6, NULL, 'Endoscopy Nurse'),
(46, 6, NULL, 'Senior Endoscopy Nurse'),
(47, 6, NULL, 'Unit Supervisor'),
(48, 6, NULL, 'Gastroenterology Resident'),
(49, 6, NULL, 'Fellow'),
(50, 6, NULL, 'Consultant Gastroenterologist'),
(51, 6, NULL, 'Nutritionist'),
(52, 6, NULL, 'Dietitian'),
(53, 6, NULL, 'Senior Dietitian'),
(54, 6, NULL, 'Department Head (Nutrition)'),
(55, 7, NULL, 'Surgical Technician'),
(56, 7, NULL, 'Scrub Nurse'),
(57, 7, NULL, 'Operating Room Nurse'),
(58, 7, NULL, 'Surgical Charge Nurse'),
(59, 7, NULL, 'Surgical Resident'),
(60, 7, NULL, 'Senior Resident'),
(61, 7, NULL, 'Consultant Surgeon'),
(62, 7, NULL, 'Ward Nurse'),
(63, 7, NULL, 'Senior Nurse'),
(64, 7, NULL, 'Nurse Unit Manager'),
(65, 8, NULL, 'OB-GYN Resident'),
(66, 8, NULL, 'Consultant Gynecologist'),
(67, 8, NULL, 'Midwife'),
(68, 8, NULL, 'Senior Midwife'),
(69, 8, NULL, 'Labor and Delivery Supervisor'),
(70, 8, NULL, 'Gynecology Nurse'),
(71, 8, NULL, 'Nurse Coordinator'),
(72, 8, NULL, 'Nurse Manager'),
(73, 9, NULL, 'Phlebotomist'),
(74, 9, NULL, 'Medical Laboratory Scientist (Hematology)'),
(75, 9, NULL, 'Senior Lab Scientist'),
(76, 9, NULL, 'Lab Supervisor'),
(77, 9, NULL, 'Hematology Lab Manager'),
(78, 9, NULL, 'Hematology Resident'),
(79, 9, NULL, 'Consultant Hematologist'),
(80, 9, NULL, 'Oncology Nurse (Hematology Unit)'),
(81, 9, NULL, 'Senior Hematology Nurse'),
(82, 9, NULL, 'Nurse Unit Head'),
(84, 10, NULL, 'HR Assistant'),
(85, 10, NULL, 'HR Officer'),
(87, 10, NULL, 'HR Manager'),
(88, 10, NULL, 'HR Director'),
(89, 10, NULL, 'Recruitment Manager'),
(92, 10, NULL, 'Training and Development Coordinator');

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
(55, 'HOS-004', 27, 'Course mismatch', '2025-11-13 21:01:07'),
(57, 'HOS-004', 29, 'Course mismatch', '2025-11-13 21:01:15'),
(58, 'HOS-004', 32, 'Course mismatch', '2025-11-13 21:01:17'),
(59, 'HOS-004', 33, 'Course mismatch', '2025-11-13 21:01:32'),
(60, 'HOS-004', 30, 'Course mismatch', '2025-11-13 21:01:35'),
(85, 'HOS-005', 34, 'Course mismatch', '2025-11-20 01:27:02'),
(86, 'HOS-006', 34, 'Course mismatch', '2025-11-20 18:57:06'),
(87, 'HOS-006', 27, 'Course mismatch', '2025-11-20 18:57:09'),
(88, 'HOS-006', 29, 'Course mismatch', '2025-11-20 22:24:13');

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

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`system_id`, `system_name`, `email`, `contact`, `about`, `cover_image`) VALUES
(1, 'Employee Management', 'employeemanagement@gmail.com', '09214235', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum', 'uploads/1763561153_52c3b2_a53aaba239a14451b891b04ae2f73977~mv2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `types_of_requests`
--

CREATE TABLE `types_of_requests` (
  `id` int(11) NOT NULL,
  `request_type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `types_of_requests`
--

INSERT INTO `types_of_requests` (`id`, `request_type_name`) VALUES
(1, 'Leave'),
(2, 'Certificate of Employment');

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
('', 'HOS-006', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$Q4BjlVeZil1GxvsS1iVMgOsyXHzyXTxc7dhe0N7DVYcPQYfiAnHU.', 'Applicant', 'Joejana Jean', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL),
('USR-007', 'EMP-006', 'gutierrez_jodielynn@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jodie Lyn Gutierrez', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'HR Officer'),
('USR-005', 'EMP-004', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jhanna Jaroda', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'Recruitment Manager'),
('', 'EMP-007', 'jojanajeangarabillo@gmail.com', '$2y$10$QqQJtieJacsNX9z8gE71xe30YsDoJ.E6g2UI/o5EaEXFxYRc2r3AK', 'Employee', 'Jane Garabillo', 'Active', '2025-11-18 20:55:54', NULL, '', '', 'HR Director'),
('', 'HOS-002', 'n0305933@gmail.com', '$2y$10$wXATHyunepSPHPGolMHnqe54maqVldT7WMxe3XbPB8vwvjPxehk/y', 'Applicant', 'Kristina Magnaye', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL),
('', 'EMP-008', 'opat09252005@gmail.com', '$2y$10$5oi3ArvuKePxhy88MLFkP.BAu0CV9qfqOCRZQSUUjlmCNDuScMVQa', 'Employee', 'Joepat Lacerna', 'Active', '0000-00-00 00:00:00', NULL, '', '', NULL);

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
  `posted_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vacancies`
--

INSERT INTO `vacancies` (`id`, `department_id`, `position_id`, `employment_type_id`, `vacancy_count`, `status`, `posted_by`, `created_at`) VALUES
(41, 1, 1, 4, 1, 'On-Going', '', '2025-11-10 11:59:15'),
(42, 8, 66, 1, 1, 'Positions Filled', '', '2025-11-10 12:04:51'),
(43, 1, 2, 4, 1, 'On-Going', '', '2025-11-10 12:32:38'),
(44, 2, 9, 4, 1, 'On-Going', '', '2025-11-10 12:34:52'),
(46, 2, 9, 4, 1, 'On-Going', '', '2025-11-10 13:55:14'),
(49, 1, 1, 1, 2, 'On-Going', 'Rhoanne Nicole Antonio', '2025-11-20 14:22:20'),
(50, 9, 77, 5, 5, 'To Post', 'Jane Garabillo', '2025-11-21 15:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `vacancies_archive`
--

CREATE TABLE `vacancies_archive` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `employement_type_id` int(11) NOT NULL,
  `vacancy_count` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `posted_by` varchar(255) NOT NULL,
  `archived_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vacancies_archive`
--

INSERT INTO `vacancies_archive` (`id`, `department_id`, `position_id`, `employement_type_id`, `vacancy_count`, `status`, `posted_by`, `archived_at`) VALUES
(1, 9, 73, 1, 1, 'On-Going', '', '2025-11-21'),
(2, 2, 12, 5, 1, 'On-Going', '', '2025-11-21'),
(3, 1, 1, 4, 1, 'On-Going', '', '2025-11-21');

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
  ADD PRIMARY KEY (`request_id`);

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
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_type_id` (`request_type_id`);

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
  ADD KEY `rejected_applications_ibfk_1` (`applicantID`),
  ADD KEY `rejected_applications_ibfk_2` (`jobID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`system_id`);

--
-- Indexes for table `types_of_requests`
--
ALTER TABLE `types_of_requests`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `vacancies_archive`
--
ALTER TABLE `vacancies_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deptID` (`department_id`),
  ADD KEY `positionID` (`position_id`),
  ADD KEY `emtypeID` (`employement_type_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `calendarID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `deptID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee_request`
--
ALTER TABLE `employee_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `employment_type`
--
ALTER TABLE `employment_type`
  MODIFY `emtypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `job_posting`
--
ALTER TABLE `job_posting`
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `types_of_requests`
--
ALTER TABLE `types_of_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vacancies`
--
ALTER TABLE `vacancies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `vacancies_archive`
--
ALTER TABLE `vacancies_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `job_posting`
--
ALTER TABLE `job_posting`
  ADD CONSTRAINT `job_posting_ibfk_1` FOREIGN KEY (`department`) REFERENCES `department` (`deptID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `job_posting_ibfk_2` FOREIGN KEY (`employment_type`) REFERENCES `employment_type` (`emtypeID`);

--
-- Constraints for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD CONSTRAINT `leave_types_ibfk_1` FOREIGN KEY (`request_type_id`) REFERENCES `types_of_requests` (`id`);

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
  ADD CONSTRAINT `position_ibfk_1` FOREIGN KEY (`departmentID`) REFERENCES `department` (`deptID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  ADD CONSTRAINT `rejected_applications_ibfk_1` FOREIGN KEY (`applicantID`) REFERENCES `applicant` (`applicantID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rejected_applications_ibfk_2` FOREIGN KEY (`jobID`) REFERENCES `job_posting` (`jobID`) ON DELETE CASCADE;

--
-- Constraints for table `vacancies`
--
ALTER TABLE `vacancies`
  ADD CONSTRAINT `fk_vacancies_employment_type` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_type` (`emtypeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `vacancies_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`deptID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `vacancies_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `position` (`positionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
