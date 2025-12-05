-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 03:13 PM
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
('HOS-002', 'Nelly Bousted', 'N/A', 'N/A', NULL, '2025-11-22', '0101', 'n0305933@gmail.com', 'Pasig', 'CEO', 'concentrix', '2025-11-22', 10, 'No', 'PLP', 'BSA', '2010', 'SAMPLE, SAMPLE, SAMPLE, SAMPLE, SAMPLE', 'SAMPLESAMPLESAMPLESAMPLE', 'Pending', NULL, 'applicant_HOS-002.jpg'),
('HOS-003', 'Joepat Lacerna', 'Radiology Assistant', 'Breast Screening Department', 'Contractual', '2025-11-25', '', 'opat09252005@gmail.com', '', NULL, '', '0000-00-00', NULL, '', 'Harvard', 'BSN', '2010', '', '', 'Archived', '2025-11-25', 'applicant_HOS-003.jpg'),
('HOS-004', 'Amihan Dimaguiba', 'Radiology Assistant', 'Breast Screening Department', 'Contractual', '2025-11-27', '', 'ruberducky032518@gmail.com', '', 'CEO', 'concentrix', '0000-00-00', 10, '', 'PLP', 'BSN', '2010', 'SAMPLE, SAMPLE, SAMPLE, SAMPLE, SAMPLE', 'asdfghj', 'Archived', '2025-11-28', 'applicant_HOS-004.jpg');

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
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `applicantID`, `jobID`, `job_title`, `department_name`, `type_name`, `status`, `applied_at`) VALUES
(33, 'HOS-002', 36, 'Hematology Lab Manager', 'Hematology Department', 'Internship', 'Pending', '2025-11-22 06:19:20');

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
(17, 'Finance Department'),
(18, 'Sales and Operation Department'),
(19, 'Warehouse and Supply Department'),
(20, 'Records Management Department'),
(21, 'Medical and Health Services Department'),
(22, 'Marketing Department');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `empID` varchar(100) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `salary_grade` int(11) DEFAULT NULL,
  `step` varchar(50) DEFAULT NULL,
  `type_name` varchar(50) NOT NULL,
  `shift_type` enum('Fixed','Rotational') DEFAULT 'Rotational',
  `default_shift_id` int(11) DEFAULT NULL,
  `work_hours_per_week` int(11) DEFAULT 40,
  `assigned_by` varchar(100) DEFAULT NULL,
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

INSERT INTO `employee` (`empID`, `fullname`, `department`, `position`, `salary_grade`, `step`, `type_name`, `shift_type`, `default_shift_id`, `work_hours_per_week`, `assigned_by`, `email_address`, `home_address`, `contact_number`, `date_of_birth`, `gender`, `emergency_contact`, `TIN_number`, `phil_health_number`, `SSS_number`, `pagibig_number`, `profile_pic`, `hired_at`) VALUES
('EMP-001', 'Rhoanne Nicole Antonio', 'Human Resources (HR) Department', 'HR Manager', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'antonio_rhoannenicole@plpasig.edu.ph', 'Pasig\r\n', '0909', '2005-12-25', 'Female', '085', '123-1234-123', '123-1234-123', '123-1234-123', '123-1234-123', 'employee_EMP-001.jpg', '2025-11-18'),
('EMP-004', 'Jhanna Jaroda', 'Human Resources (HR) Department', 'Recruitment Manager', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', 'Cainta\r\n', '74185', '2002-09-30', '', '875421', '12345-8754-087', '12345-8754-087', '12345-8754-087', '12345-8754-087', NULL, '2025-11-18'),
('EMP-005', 'Shane Ella Cacho', 'Human Resources (HR) Department', 'Training and Development Coordinator', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Jean Garabillo', 'cacho_shaneellamae@plpasig.edu.ph', 'Cainta', '123456', '2000-12-25', '', '123456', '1234-7654-2345', '1234-7654-2345', '1234-7654-2345', '1234-7654-2345', NULL, '2025-11-18'),
('EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Rhoanne Nicole Antonio', 'gutierrez_jodielynn@plpasig.edu.ph', 'Pasig\r\n', '0303', '2001-11-24', '', '015', '', '', '', '', 'employee_EMP-006.jpg', '2025-11-18'),
('EMP-009', 'Carlos Mendoza', 'Anesthetics Department', 'Nurse Anesthetist', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Jean Garabillo', 'carlos_mendoza@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-011', 'Miguel Santos', 'Cardiology Department', 'Cardiac Nurse', NULL, NULL, 'Regular', 'Rotational', 1, 40, 'Jean Garabillo', 'miguel_santos@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-012', 'Patricia Gomez', 'Gynecology Department', 'Midwife', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Rhoanne Nicole Antonio', 'patricia_gomez@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-013', 'John Francis Velasquez', 'IT Department', 'IT Support', NULL, NULL, 'Contractual', 'Rotational', 1, 40, 'Rhoanne Nicole Antonio', 'johnf_velasquez@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-014', 'Cheska Ramirez', 'Human Resources (HR) Department', 'HR Assistant', NULL, NULL, 'Part Time', 'Rotational', 2, 40, 'Jean Garabillo', 'cheska_ramirez@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-015', 'Hannah Nicole Villanueva', 'Elderly Services (Geriatrics)', 'Geriatric Nurse', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'hannah_villanueva@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-016', 'Jerome Alcantara', 'Cardiology Department', 'Cardiac Lab Supervisor', NULL, NULL, 'Regular', 'Rotational', 1, 40, 'Jean Garabillo', 'jerome_alcantara@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-017', 'Danica Joy Flores', 'Gastroenterology Department', 'Endoscopy Nurse', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Rhoanne Nicole Antonio', 'danica_flores@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-018', 'Ricardo Manalo', 'Breast Screening Department', 'Mammography Technologist', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'ricardo_manalo@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-019', 'Anna Mendoza', 'Anesthetics Department', 'Operating Room Nurse', NULL, NULL, 'Regular', 'Fixed', 2, 40, 'Jean Garabillo', 'anna_mendoza@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-021', 'George Cruz', 'IT Department', 'IT Manager', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'george_cruz@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-022', 'Kevin Tan', 'General Surgery Department', 'Surgical Technician', NULL, NULL, 'Contractual', 'Rotational', 1, 40, 'Jean Garabillo', 'kevin_tan@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-023', 'Olivia Lim', 'Gynecology Department', 'Senior Midwife', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'olivia_lim@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-024', 'Maria De Guzman', 'Cardiology Department', 'ECG Technician', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Jean Garabillo', 'maria_deguzman@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-025', 'Isabel Flores', 'Elderly Services (Geriatrics)', 'Healthcare Assistant', NULL, NULL, 'Part Time', 'Rotational', 1, 40, 'Jean Garabillo', 'isabel_flores@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-026', 'Edward Reyes', 'Anesthetics Department', 'Consultant Anesthesiologist', NULL, NULL, 'Full Time', 'Fixed', 3, 40, 'Jean Garabillo', 'edward_reyes@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-027', 'Carla Santos', 'Cardiology Department', 'Cardiology Unit Manager', NULL, NULL, 'Regular', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'carla_santos@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-028', 'Renato Villanueva', 'Gastroenterology Department', 'Consultant Gastroenterologist', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'renato.villanueva@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-029', 'Bella Ramirez', 'Breast Screening Department', 'Screening Coordinator', NULL, NULL, 'Contractual', 'Fixed', 1, 40, 'Jean Garabillo', 'bella_ramirez@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-030', 'Mark Joseph Reyes', 'General Surgery Department', 'Scrub Nurse', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Rhoanne Nicole Antonio', 'mark_reyes@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-031', 'Helena Cruz', 'IT Department', 'IT Head', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'helena_cruz@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-032', 'Lance Tan', 'Cardiology Department', 'Senior Cardiac Nurse', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Rhoanne Nicole Antonio', 'lance_tan@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-033', 'Paul Lim', 'Gynecology Department', 'Labor and Delivery Supervisor', NULL, NULL, 'Regular', 'Rotational', 3, 40, 'Rhoanne Nicole Antonio', 'paul_lim@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-034', 'Nathan De Guzman', 'Cardiology Department', 'Consultant Cardiologist', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Rhoanne Nicole Antonio', 'nathan_deguzman@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-035', 'Julian Flores', 'Elderly Services (Geriatrics)', 'Unit Head', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Rhoanne Nicole Antonio', 'julian_flores@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-036', 'Fiona Reyes', 'Anesthetics Department', 'Senior PACU Nurse', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Jean Garabillo', 'fiona_reyes@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-037', 'Diana Lopez', 'Cardiology Department', 'Cardiac Rehabilitation Specialist', NULL, NULL, 'Regular', 'Rotational', 2, 40, 'Jean Garabillo', 'diana_lopez@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Female', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-038', 'Marco Alcantara', 'Gastroenterology Department', 'Endoscopy Technician', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Jean Garabillo', 'marco.alcantara@plpasig.edu.ph', '123 Sample Street, Pasig City', '09123456789', '1990-01-01', 'Male', '09987654321', '123-456-789', 'PH123456789', 'SSS123456789', 'PB123456789', NULL, NULL),
('EMP-039', 'Jean Garabillo', 'Human Resources (HR) Department', 'HR Director', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'jojanajeangarabillo@gmail.com', 'Taytay Rizal', '1234', '2005-09-25', 'Female', '0984', '12345-87654-432', '12345-87654-432', '12345-87654-432', '12345-87654-432', 'employee_EMP-039.jpg', '2025-11-23'),
('EMP-041', 'Lark Bolotaolo', 'Sales and Operation Department', 'Point of Sales Admin', NULL, NULL, 'Full Time', 'Rotational', 2, 40, 'Rhoanne Nicole Antonio', 'bolotaolo_lark@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-24'),
('EMP-042', 'Marvin Gallardo', 'Records Management Department', 'Document Management Admin', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Jean Garabillo', 'gallardo_marvin@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25'),
('EMP-043', 'Ariuz Dean Guerrero', 'Medical and Health Services Department', 'Patient Management Admin', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'guerrero_ariuzdean@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25'),
('EMP-044', 'Klarenz Cobie O. Manrique', 'Finance Department', 'Payroll Admin', NULL, NULL, 'Full Time', 'Rotational', 3, 40, 'Jean Garabillo', 'manrique_klarenzcobie@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25'),
('EMP-045', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', NULL, NULL, 'Contractual', 'Rotational', 2, 40, 'Jean Garabillo', 'opat09252005@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'employee_EMP-045.jpeg', '2025-11-25'),
('EMP-047', 'Jojana Garabillo', 'Human Resources (HR) Department', 'Human Resource (HR) Admin', NULL, NULL, 'Regular', 'Rotational', 1, 40, 'Jean Garabillo', 'garabillo_jojanajean@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'employee_EMP-047.jpg', '2025-11-27'),
('EMP-048', 'Amihan Dimaguiba', 'Breast Screening Department', 'Radiology Assistant', NULL, NULL, 'Contractual', 'Rotational', 1, 40, 'Jean Garabillo', 'ruberducky032518@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'employee_EMP-048.png', '2025-11-28'),
('EMP-049', 'Leonor Rivera', 'Records Management Department', 'Document Management Admin', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'noonajeogyo@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
('EMP-050', 'Alexander Cajurao', 'Warehouse and Supply Department', 'Inventory Officer', NULL, NULL, 'Full Time', 'Rotational', 1, 40, 'Jean Garabillo', 'cajurao_alexanderjr@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-29'),
('EMP-051', 'Mico Bermudez', 'Marketing Department', 'Content Management Admin', NULL, NULL, 'Regular', 'Rotational', NULL, 40, NULL, 'bermudez_miguelcarlos@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-01'),
('EMP-052', 'Pepito Manaloto', 'Marketing Department', 'System Staff', NULL, NULL, 'Full Time', 'Rotational', NULL, 40, NULL, 'freeyt.zy@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-01'),
('EMP-053', 'Charles Jeramy De Padua', 'Finance Department', 'Payroll Manager', NULL, NULL, 'Regular', 'Rotational', NULL, 40, NULL, 'depadua_charlesjeramy@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-05'),
('EMP-054', 'Cj Castro', 'Finance Department', 'Payroll Officer', NULL, NULL, 'Full Time', 'Rotational', NULL, 40, NULL, 'castro_charljoven@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-05'),
('EMP-055', 'Daryll Alay', 'Warehouse and Supply Department', 'Inventory Admin', NULL, NULL, 'Regular', 'Rotational', NULL, 40, NULL, 'alay_darryljohn@plpasig.edu.ph', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-05');

-- --------------------------------------------------------

--
-- Table structure for table `employee_shift_pattern`
--

CREATE TABLE `employee_shift_pattern` (
  `emp_pattern_id` int(11) NOT NULL,
  `empID` varchar(100) DEFAULT NULL,
  `pattern_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_by` varchar(255) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_shift_pattern`
--

INSERT INTO `employee_shift_pattern` (`emp_pattern_id`, `empID`, `pattern_id`, `start_date`, `end_date`, `assigned_by`, `assigned_at`) VALUES
(3, 'EMP-004', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(4, 'EMP-006', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(5, 'EMP-011', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(6, 'EMP-013', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(7, 'EMP-016', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(8, 'EMP-017', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(9, 'EMP-022', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(10, 'EMP-025', 4, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(11, 'EMP-029', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(12, 'EMP-031', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(13, 'EMP-032', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(14, 'EMP-036', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(15, 'EMP-039', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(16, 'EMP-043', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(18, 'EMP-047', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(19, 'EMP-048', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(20, 'EMP-049', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(21, 'EMP-050', 1, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(22, 'EMP-009', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(23, 'EMP-012', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(24, 'EMP-014', 4, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(25, 'EMP-019', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(26, 'EMP-024', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(27, 'EMP-030', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(28, 'EMP-034', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(29, 'EMP-035', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(30, 'EMP-037', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(31, 'EMP-041', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(32, 'EMP-045', 2, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(33, 'EMP-001', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(34, 'EMP-005', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(35, 'EMP-015', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(36, 'EMP-018', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(37, 'EMP-021', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(38, 'EMP-023', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(39, 'EMP-026', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(40, 'EMP-027', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(41, 'EMP-028', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(42, 'EMP-033', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(43, 'EMP-038', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(44, 'EMP-042', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54'),
(45, 'EMP-044', 3, '2025-12-01', '2026-01-31', 'System', '2025-11-30 14:05:54');

-- --------------------------------------------------------

--
-- Table structure for table `employee_shift_schedule`
--

CREATE TABLE `employee_shift_schedule` (
  `schedule_id` int(11) NOT NULL,
  `empID` varchar(100) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `status` enum('Scheduled','Completed','Absent','On Leave') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_shift_schedule`
--

INSERT INTO `employee_shift_schedule` (`schedule_id`, `empID`, `shift_id`, `schedule_date`, `status`, `created_at`) VALUES
(1, 'EMP-019', 1, '2025-11-30', 'Scheduled', '2025-11-30 07:56:01'),
(55, 'EMP-009', 2, '2026-01-06', 'Scheduled', '2025-11-30 10:35:44'),
(56, 'EMP-009', 2, '2026-01-13', 'Scheduled', '2025-11-30 10:35:44'),
(57, 'EMP-009', 2, '2026-01-20', 'Scheduled', '2025-11-30 10:35:44'),
(58, 'EMP-009', 2, '2026-01-27', 'Scheduled', '2025-11-30 10:35:44'),
(78, 'EMP-006', 1, '2025-11-30', 'Scheduled', '2025-11-30 12:38:45'),
(394, 'EMP-004', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(395, 'EMP-006', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(396, 'EMP-011', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(397, 'EMP-013', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(398, 'EMP-016', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(399, 'EMP-017', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(400, 'EMP-022', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(401, 'EMP-029', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(402, 'EMP-031', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(403, 'EMP-032', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(404, 'EMP-036', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(405, 'EMP-039', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(406, 'EMP-043', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(408, 'EMP-047', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(409, 'EMP-048', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(410, 'EMP-049', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(411, 'EMP-050', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(412, 'EMP-004', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(413, 'EMP-006', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(414, 'EMP-011', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(415, 'EMP-013', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(416, 'EMP-016', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(417, 'EMP-017', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(418, 'EMP-022', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(419, 'EMP-029', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(420, 'EMP-031', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(421, 'EMP-032', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(422, 'EMP-036', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(423, 'EMP-039', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(424, 'EMP-043', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(426, 'EMP-047', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(427, 'EMP-048', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(428, 'EMP-049', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(429, 'EMP-050', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(430, 'EMP-004', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(431, 'EMP-006', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(432, 'EMP-011', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(433, 'EMP-013', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(434, 'EMP-016', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(435, 'EMP-017', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(436, 'EMP-022', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(437, 'EMP-029', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(438, 'EMP-031', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(439, 'EMP-032', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(440, 'EMP-036', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(441, 'EMP-039', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(442, 'EMP-043', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(444, 'EMP-047', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(445, 'EMP-048', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(446, 'EMP-049', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(447, 'EMP-050', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(448, 'EMP-004', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(449, 'EMP-006', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(450, 'EMP-011', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(451, 'EMP-013', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(452, 'EMP-016', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(453, 'EMP-017', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(454, 'EMP-022', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(455, 'EMP-029', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(456, 'EMP-031', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(457, 'EMP-032', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(458, 'EMP-036', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(459, 'EMP-039', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(460, 'EMP-043', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(462, 'EMP-047', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(463, 'EMP-048', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(464, 'EMP-049', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(465, 'EMP-050', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(466, 'EMP-004', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(467, 'EMP-006', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(468, 'EMP-011', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(469, 'EMP-013', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(470, 'EMP-016', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(471, 'EMP-017', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(472, 'EMP-022', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(473, 'EMP-029', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(474, 'EMP-031', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(475, 'EMP-032', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(476, 'EMP-036', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(477, 'EMP-039', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(478, 'EMP-043', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(480, 'EMP-047', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(481, 'EMP-048', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(482, 'EMP-049', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(483, 'EMP-050', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(484, 'EMP-001', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(485, 'EMP-005', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(486, 'EMP-015', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(487, 'EMP-018', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(488, 'EMP-021', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(489, 'EMP-023', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(490, 'EMP-026', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(491, 'EMP-027', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(492, 'EMP-028', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(493, 'EMP-033', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(494, 'EMP-038', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(495, 'EMP-042', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(496, 'EMP-044', 1, '2025-12-01', 'Scheduled', '2025-11-30 14:25:53'),
(497, 'EMP-001', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(498, 'EMP-005', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(499, 'EMP-015', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(500, 'EMP-018', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(501, 'EMP-021', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(502, 'EMP-023', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(503, 'EMP-026', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(504, 'EMP-027', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(505, 'EMP-028', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(506, 'EMP-033', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(507, 'EMP-038', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(508, 'EMP-042', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(509, 'EMP-044', 1, '2025-12-02', 'Scheduled', '2025-11-30 14:25:53'),
(510, 'EMP-001', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(511, 'EMP-005', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(512, 'EMP-015', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(513, 'EMP-018', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(514, 'EMP-021', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(515, 'EMP-023', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(516, 'EMP-026', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(517, 'EMP-027', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(518, 'EMP-028', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(519, 'EMP-033', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(520, 'EMP-038', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(521, 'EMP-042', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(522, 'EMP-044', 1, '2025-12-03', 'Scheduled', '2025-11-30 14:25:53'),
(523, 'EMP-001', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(524, 'EMP-005', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(525, 'EMP-015', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(526, 'EMP-018', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(527, 'EMP-021', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(528, 'EMP-023', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(529, 'EMP-026', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(530, 'EMP-027', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(531, 'EMP-028', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(532, 'EMP-033', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(533, 'EMP-038', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(534, 'EMP-042', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(535, 'EMP-044', 1, '2025-12-04', 'Scheduled', '2025-11-30 14:25:53'),
(536, 'EMP-001', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(537, 'EMP-005', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(538, 'EMP-015', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(539, 'EMP-018', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(540, 'EMP-021', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(541, 'EMP-023', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(542, 'EMP-026', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(543, 'EMP-027', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(544, 'EMP-028', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(545, 'EMP-033', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(546, 'EMP-038', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(547, 'EMP-042', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(548, 'EMP-044', 1, '2025-12-05', 'Scheduled', '2025-11-30 14:25:53'),
(549, 'EMP-001', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(550, 'EMP-005', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(551, 'EMP-015', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(552, 'EMP-018', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(553, 'EMP-021', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(554, 'EMP-023', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(555, 'EMP-026', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(556, 'EMP-027', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(557, 'EMP-028', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(558, 'EMP-033', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(559, 'EMP-038', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(560, 'EMP-042', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(561, 'EMP-044', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:25:53'),
(562, 'EMP-001', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(563, 'EMP-005', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(564, 'EMP-015', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(565, 'EMP-018', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(566, 'EMP-021', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(567, 'EMP-023', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(568, 'EMP-026', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(569, 'EMP-027', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(570, 'EMP-028', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(571, 'EMP-033', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(572, 'EMP-038', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(573, 'EMP-042', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(574, 'EMP-044', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:25:53'),
(575, 'EMP-001', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(576, 'EMP-005', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(577, 'EMP-015', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(578, 'EMP-018', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(579, 'EMP-021', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(580, 'EMP-023', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(581, 'EMP-026', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(582, 'EMP-027', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(583, 'EMP-028', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(584, 'EMP-033', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(585, 'EMP-038', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(586, 'EMP-042', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(587, 'EMP-044', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:25:53'),
(588, 'EMP-001', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(589, 'EMP-005', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(590, 'EMP-015', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(591, 'EMP-018', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(592, 'EMP-021', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(593, 'EMP-023', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(594, 'EMP-026', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(595, 'EMP-027', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(596, 'EMP-028', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(597, 'EMP-033', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(598, 'EMP-038', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(599, 'EMP-042', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(600, 'EMP-044', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:25:53'),
(601, 'EMP-001', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(602, 'EMP-005', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(603, 'EMP-015', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(604, 'EMP-018', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(605, 'EMP-021', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(606, 'EMP-023', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(607, 'EMP-026', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(608, 'EMP-027', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(609, 'EMP-028', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(610, 'EMP-033', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(611, 'EMP-038', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(612, 'EMP-042', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(613, 'EMP-044', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:25:53'),
(614, 'EMP-001', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(615, 'EMP-005', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(616, 'EMP-015', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(617, 'EMP-018', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(618, 'EMP-021', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(619, 'EMP-023', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(620, 'EMP-026', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(621, 'EMP-027', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(622, 'EMP-028', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(623, 'EMP-033', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(624, 'EMP-038', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(625, 'EMP-042', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(626, 'EMP-044', 3, '2025-12-15', 'Scheduled', '2025-11-30 14:25:53'),
(627, 'EMP-001', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(628, 'EMP-005', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(629, 'EMP-015', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(630, 'EMP-018', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(631, 'EMP-021', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(632, 'EMP-023', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(633, 'EMP-026', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(634, 'EMP-027', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(635, 'EMP-028', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(636, 'EMP-033', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(637, 'EMP-038', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(638, 'EMP-042', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(639, 'EMP-044', 3, '2025-12-16', 'Scheduled', '2025-11-30 14:25:53'),
(640, 'EMP-001', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(641, 'EMP-005', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(642, 'EMP-015', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(643, 'EMP-018', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(644, 'EMP-021', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(645, 'EMP-023', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(646, 'EMP-026', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(647, 'EMP-027', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(648, 'EMP-028', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(649, 'EMP-033', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(650, 'EMP-038', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(651, 'EMP-042', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(652, 'EMP-044', 3, '2025-12-17', 'Scheduled', '2025-11-30 14:25:53'),
(653, 'EMP-001', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(654, 'EMP-005', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(655, 'EMP-015', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(656, 'EMP-018', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(657, 'EMP-021', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(658, 'EMP-023', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(659, 'EMP-026', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(660, 'EMP-027', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(661, 'EMP-028', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(662, 'EMP-033', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(663, 'EMP-038', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(664, 'EMP-042', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(665, 'EMP-044', 3, '2025-12-18', 'Scheduled', '2025-11-30 14:25:53'),
(666, 'EMP-001', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(667, 'EMP-005', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(668, 'EMP-015', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(669, 'EMP-018', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(670, 'EMP-021', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(671, 'EMP-023', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(672, 'EMP-026', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(673, 'EMP-027', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(674, 'EMP-028', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(675, 'EMP-033', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(676, 'EMP-038', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(677, 'EMP-042', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(678, 'EMP-044', 3, '2025-12-19', 'Scheduled', '2025-11-30 14:25:53'),
(905, 'EMP-036', 1, '2025-11-30', 'Scheduled', '2025-11-30 14:32:23'),
(906, 'EMP-019', 2, '2025-12-01', 'Scheduled', '2025-11-30 14:52:31'),
(907, 'EMP-019', 2, '2025-12-02', 'Scheduled', '2025-11-30 14:52:31'),
(908, 'EMP-019', 2, '2025-12-03', 'Scheduled', '2025-11-30 14:52:31'),
(909, 'EMP-019', 2, '2025-12-04', 'Scheduled', '2025-11-30 14:52:31'),
(910, 'EMP-019', 2, '2025-12-05', 'Scheduled', '2025-11-30 14:52:31'),
(911, 'EMP-019', 2, '2025-12-06', 'Scheduled', '2025-11-30 14:52:31'),
(912, 'EMP-019', 2, '2025-12-08', 'Scheduled', '2025-11-30 14:52:31'),
(913, 'EMP-019', 2, '2025-12-09', 'Scheduled', '2025-11-30 14:52:31'),
(914, 'EMP-019', 2, '2025-12-10', 'Scheduled', '2025-11-30 14:52:31'),
(915, 'EMP-019', 2, '2025-12-11', 'Scheduled', '2025-11-30 14:52:31'),
(916, 'EMP-019', 2, '2025-12-12', 'Scheduled', '2025-11-30 14:52:31'),
(917, 'EMP-019', 2, '2025-12-13', 'Scheduled', '2025-11-30 14:52:31'),
(918, 'EMP-019', 2, '2025-12-15', 'Scheduled', '2025-11-30 14:52:31'),
(919, 'EMP-019', 2, '2025-12-16', 'Scheduled', '2025-11-30 14:52:31'),
(920, 'EMP-019', 2, '2025-12-17', 'Scheduled', '2025-11-30 14:52:31'),
(921, 'EMP-019', 2, '2025-12-18', 'Scheduled', '2025-11-30 14:52:31'),
(922, 'EMP-019', 2, '2025-12-19', 'Scheduled', '2025-11-30 14:52:31'),
(923, 'EMP-019', 2, '2025-12-20', 'Scheduled', '2025-11-30 14:52:31'),
(924, 'EMP-019', 2, '2025-12-22', 'Scheduled', '2025-11-30 14:52:31'),
(925, 'EMP-019', 2, '2025-12-23', 'Scheduled', '2025-11-30 14:52:31'),
(926, 'EMP-019', 2, '2025-12-24', 'Scheduled', '2025-11-30 14:52:31'),
(927, 'EMP-019', 2, '2025-12-25', 'Scheduled', '2025-11-30 14:52:31'),
(928, 'EMP-019', 2, '2025-12-26', 'Scheduled', '2025-11-30 14:52:31'),
(929, 'EMP-019', 2, '2025-12-27', 'Scheduled', '2025-11-30 14:52:31'),
(930, 'EMP-019', 2, '2025-12-29', 'Scheduled', '2025-11-30 14:52:31'),
(931, 'EMP-019', 2, '2025-12-30', 'Scheduled', '2025-11-30 14:52:31'),
(932, 'EMP-032', 1, '2025-12-06', 'Scheduled', '2025-11-30 14:55:54'),
(933, 'EMP-032', 1, '2025-12-08', 'Scheduled', '2025-11-30 14:55:54'),
(934, 'EMP-032', 1, '2025-12-09', 'Scheduled', '2025-11-30 14:55:54'),
(935, 'EMP-032', 1, '2025-12-10', 'Scheduled', '2025-11-30 14:55:54'),
(936, 'EMP-032', 1, '2025-12-11', 'Scheduled', '2025-11-30 14:55:54'),
(937, 'EMP-032', 1, '2025-12-12', 'Scheduled', '2025-11-30 14:55:54'),
(938, 'EMP-032', 1, '2025-12-13', 'Scheduled', '2025-11-30 14:55:54'),
(939, 'EMP-032', 1, '2025-12-15', 'Scheduled', '2025-11-30 14:55:54'),
(940, 'EMP-032', 1, '2025-12-16', 'Scheduled', '2025-11-30 14:55:54'),
(941, 'EMP-032', 1, '2025-12-17', 'Scheduled', '2025-11-30 14:55:54'),
(942, 'EMP-032', 1, '2025-12-18', 'Scheduled', '2025-11-30 14:55:54'),
(943, 'EMP-032', 1, '2025-12-19', 'Scheduled', '2025-11-30 14:55:54'),
(944, 'EMP-032', 1, '2025-12-20', 'Scheduled', '2025-11-30 14:55:54'),
(945, 'EMP-032', 1, '2025-12-22', 'Scheduled', '2025-11-30 14:55:54'),
(946, 'EMP-032', 1, '2025-12-23', 'Scheduled', '2025-11-30 14:55:54'),
(947, 'EMP-032', 1, '2025-12-24', 'Scheduled', '2025-11-30 14:55:54'),
(948, 'EMP-032', 1, '2025-12-25', 'Scheduled', '2025-11-30 14:55:54'),
(949, 'EMP-032', 1, '2025-12-26', 'Scheduled', '2025-11-30 14:55:54'),
(950, 'EMP-032', 1, '2025-12-27', 'Scheduled', '2025-11-30 14:55:54'),
(951, 'EMP-032', 1, '2025-12-29', 'Scheduled', '2025-11-30 14:55:54'),
(952, 'EMP-032', 1, '2025-12-30', 'Scheduled', '2025-11-30 14:55:54');

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
-- Table structure for table `expected_staffing`
--

CREATE TABLE `expected_staffing` (
  `staffing_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `required_count` int(11) NOT NULL,
  `employment_status` enum('Any','Regular','Full-Time','Part-Time','Contractual','Intern') DEFAULT 'Any'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_request`
--

CREATE TABLE `general_request` (
  `request_id` int(11) NOT NULL,
  `empID` varchar(100) DEFAULT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `request_type_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `action_by` varchar(100) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `pickup_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_request`
--

INSERT INTO `general_request` (`request_id`, `empID`, `fullname`, `department`, `position`, `email`, `request_type_id`, `reason`, `status`, `action_by`, `requested_at`, `pickup_date`) VALUES
(2, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'gutierrez_jodielynn@plpasig.edu.ph', 2, 'awadrgbk', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 15:03:02', NULL);

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
(29, 'Nurse Anesthetist', 'ASDCCDSC', 1, NULL, 'BSN', 'As', '1235', 12, 4, NULL, 1, '2025-11-10', '2025-11-28'),
(30, 'Radiology Assistant', 'SDXSAD', 2, NULL, 'BSN', 'Ajhbs', '123345', 12, 4, NULL, 1, '2025-11-10', '2025-11-24'),
(34, 'Phlebotomist', 'DCD', 9, NULL, 'BSIT', 'Hfc', '216512', 11, 1, NULL, 0, '2025-11-13', '2025-11-14'),
(35, 'Anesthetic Technician', 'KHXSKWIQGS', 1, NULL, 'BSA', 'Mxhsakx', '12232', 5, 1, NULL, 0, '2025-11-20', '2025-11-23'),
(36, 'Hematology Lab Manager', 'SDSFG', 9, NULL, 'BSA', 'Asdfg', '1234', 5, 5, NULL, 5, '2025-11-21', '2025-11-26'),
(37, 'Healthcare Assistant', 'SAMPLESAMPLESAMPSAMPLELESAMPLESAMPLESAMPLESAMPLESAMPLESAMPLESAMPSAMPLELESAMPLESAMPLESAMPLESAMPLE', 5, NULL, 'BSN', 'Samplesamplesamplesamplesamplesamplesample', '40,000', 5, 5, NULL, 3, '2025-12-02', '2025-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `leave_pay_categories`
--

CREATE TABLE `leave_pay_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_pay_categories`
--

INSERT INTO `leave_pay_categories` (`id`, `category_name`) VALUES
(1, 'Paid'),
(2, 'Unpaid'),
(3, 'Partially');

-- --------------------------------------------------------

--
-- Table structure for table `leave_request`
--

CREATE TABLE `leave_request` (
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
  `pay_category_id` int(11) DEFAULT NULL,
  `leave_type_name` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_request`
--

INSERT INTO `leave_request` (`request_id`, `empID`, `fullname`, `department`, `position`, `type_name`, `email_address`, `e_signature`, `request_type_id`, `request_type_name`, `reason`, `status`, `action_by`, `requested_at`, `leave_type_id`, `pay_category_id`, `leave_type_name`, `from_date`, `to_date`, `duration`) VALUES
(42, 'EMP-001', 'Rhoanne Nicole Antonio', 'Human Resources (HR) Department', 'HR Manager', 'Full Time', 'antonio_rhoannenicole@plpasig.edu.ph', '', 1, 'Leave', 'vacation', 'Pending', NULL, '2025-11-24 12:37:54', 2, 1, 'Vacation Leave', '2025-12-01', '2025-12-04', 4),
(44, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', 1, 'Leave', 'sample', 'Pending', NULL, '2025-11-25 20:18:10', 2, 1, 'Vacation Leave', '2025-12-01', '2025-12-04', 4),
(45, 'EMP-048', 'Amihan Dimaguiba', 'Breast Screening Department', 'Radiology Assistant', 'Contractual', 'ruberducky032518@gmail.com', 'uploads/signatures/1764303076_sample-esign.png', 1, 'Leave', 'sdfghju', 'Approved', 'HR Manager', '2025-11-28 12:11:16', 2, 1, 'Vacation Leave', '2025-12-04', '2025-12-08', 5),
(46, 'EMP-045', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', 'Contractual', 'opat09252005@gmail.com', '', 1, 'Leave', 'asdfg', 'Rejected', 'HR Director', '2025-12-01 17:41:59', 2, 1, 'Vacation Leave', '2026-01-02', '2026-01-10', 9),
(49, 'EMP-039', 'Jean Garabillo', 'Human Resources (HR) Department', 'HR Director', 'Full Time', 'jojanajeangarabillo@gmail.com', '', 1, 'Leave', 'samplereasonsamplereason', 'Pending', NULL, '2025-12-02 10:17:25', 2, 1, 'Vacation Leave', '2025-12-05', '2025-12-14', 10);

-- --------------------------------------------------------

--
-- Table structure for table `leave_request_archive`
--

CREATE TABLE `leave_request_archive` (
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
  `pay_category_id` int(11) DEFAULT NULL,
  `leave_type_name` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_request_archive`
--

INSERT INTO `leave_request_archive` (`request_id`, `empID`, `fullname`, `department`, `position`, `type_name`, `email_address`, `e_signature`, `request_type_id`, `request_type_name`, `reason`, `status`, `action_by`, `requested_at`, `leave_type_id`, `pay_category_id`, `leave_type_name`, `from_date`, `to_date`, `duration`) VALUES
(35, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', 1, 'Leave', 'asd', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 16:31:45', 3, 1, 'Maternity Leave', '2025-11-24', '2025-11-27', 4),
(40, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', 1, 'Leave', 'asdf', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 18:18:58', 1, 1, 'Sick Leave', '2025-11-28', '2025-11-30', 3);

-- --------------------------------------------------------

--
-- Table structure for table `leave_settings`
--

CREATE TABLE `leave_settings` (
  `settingID` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `month` tinyint(2) UNSIGNED DEFAULT NULL,
  `employee_limit` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `request_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_settings`
--

INSERT INTO `leave_settings` (`settingID`, `start_date`, `end_date`, `month`, `employee_limit`, `created_by`, `created_at`, `request_type_id`) VALUES
(26, '2025-12-01', '2025-12-31', 12, 20, 'Jean Garabillo', '2025-12-01 17:37:28', 1),
(27, '2026-01-01', '2026-01-31', 1, 15, 'Rhoanne Nicole Antonio', '2025-12-01 17:40:37', 1);

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL,
  `request_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(100) NOT NULL,
  `pay_category_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `request_type_id`, `leave_type_name`, `pay_category_id`) VALUES
(1, 1, 'Sick Leave', 1),
(2, 1, 'Vacation Leave', 1),
(3, 1, 'Maternity Leave', 1),
(4, 1, 'Paternity Leave', 1),
(5, 1, 'Bereavement Leave', 2),
(6, 1, 'Service Incentive Leave (SIL)', 1),
(7, 1, 'Rehabilitation Leave', 1),
(8, 1, 'Special Leave Benefit for Women', 1),
(9, 1, 'Leave for VAWC', 1),
(10, 1, 'Parental Leave for Solo Parents', 1),
(11, 1, 'Company-provided Sick Leave', 1),
(12, 1, 'Company-provided Vacation Leave', 1),
(13, 1, 'Leave Without Pay (LWOP)', 2),
(14, 1, 'Extended Maternity Leave', 2),
(15, 1, 'Extended Personal/Family Leave', 2);

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
(17, 'jojanajeangarabillo@gmail.com', 'HR Director', 'December Leave availability', 'First come First serve basis', '2025-12-01 17:37:54', 1, 26),
(18, 'antonio_rhoannenicole@plpasig.edu.ph', 'HR Manager', 'January Leave Availability', 'First come first serve Basis', '2025-12-01 17:40:59', 1, 27);

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
(92, 10, NULL, 'Training and Development Coordinator'),
(101, 17, NULL, 'Payroll Admin'),
(102, 17, NULL, 'Payroll Manager'),
(103, 17, NULL, 'Payroll Officer'),
(104, 18, NULL, 'Point of Sales Admin'),
(105, 19, NULL, 'Inventory Admin'),
(106, 20, NULL, 'Document Management Admin'),
(107, 21, NULL, 'Patient Management Admin'),
(108, 22, NULL, 'Content Management Admin'),
(109, 10, NULL, 'Human Resource (HR) Admin'),
(110, 15, NULL, 'IT Manager'),
(111, 15, NULL, 'IT Associate'),
(112, 15, NULL, 'IT Associate Jr'),
(113, 15, NULL, 'IT Head'),
(115, 22, NULL, 'System Staff'),
(116, 19, NULL, 'Inventory Officer');

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
(94, 'HOS-002', 35, 'Qualification mismatch', '2025-11-22 06:17:32'),
(98, 'HOS-003', 34, 'Qualification mismatch', '2025-11-25 14:53:34'),
(99, 'HOS-003', 36, 'Qualification mismatch', '2025-11-25 14:53:36'),
(100, 'HOS-003', 35, 'Qualification mismatch', '2025-11-25 14:53:38'),
(101, 'HOS-004', 36, 'Qualification mismatch', '2025-11-28 12:07:15'),
(102, 'HOS-004', 35, 'Qualification mismatch', '2025-11-28 12:07:29'),
(103, 'HOS-004', 34, 'Qualification mismatch', '2025-11-28 12:07:34');

-- --------------------------------------------------------

--
-- Table structure for table `shift_patterns`
--

CREATE TABLE `shift_patterns` (
  `pattern_id` int(11) NOT NULL,
  `pattern_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cycle_days` int(11) DEFAULT 7,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_patterns`
--

INSERT INTO `shift_patterns` (`pattern_id`, `pattern_name`, `description`, `cycle_days`, `is_active`) VALUES
(1, '5-Day Morning', 'Monday to Friday morning shifts', 7, 1),
(2, '5-Day Afternoon', 'Monday to Friday afternoon shifts', 7, 1),
(3, 'Rotating 3-Shift', 'Rotates through all three shifts weekly', 21, 1),
(4, 'Weekend Warrior', 'Works weekends with weekday off', 7, 1),
(5, '4x10 Schedule', 'Four 10-hour days', 7, 1),
(6, '5-Day Night', 'Monday to Friday night shifts', 7, 1),
(8, 'Weekend 2 Days Saturday and Sunday', 'Weekend Shift', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shift_pattern_details`
--

CREATE TABLE `shift_pattern_details` (
  `pattern_detail_id` int(11) NOT NULL,
  `pattern_id` int(11) DEFAULT NULL,
  `day_number` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_pattern_details`
--

INSERT INTO `shift_pattern_details` (`pattern_detail_id`, `pattern_id`, `day_number`, `shift_id`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 1),
(3, 1, 3, 1),
(4, 1, 4, 1),
(5, 1, 5, 1),
(6, 3, 1, 1),
(7, 3, 2, 1),
(8, 3, 3, 1),
(9, 3, 4, 1),
(10, 3, 5, 1),
(11, 3, 8, 2),
(12, 3, 9, 2),
(13, 3, 10, 2),
(14, 3, 11, 2),
(15, 3, 12, 2),
(16, 3, 15, 3),
(17, 3, 16, 3),
(18, 3, 17, 3),
(19, 3, 18, 3),
(20, 3, 19, 3),
(21, 5, 6, 1),
(22, 5, 7, 1),
(26, 8, 1, 1),
(27, 8, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shift_templates`
--

CREATE TABLE `shift_templates` (
  `shift_id` int(11) NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `shift_hours` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_templates`
--

INSERT INTO `shift_templates` (`shift_id`, `shift_name`, `time_in`, `time_out`, `shift_hours`, `description`) VALUES
(1, 'Morning', '06:00:00', '14:00:00', 8, 'Morning shift 6AM-2PM'),
(2, 'Afternoon', '14:00:00', '22:00:00', 8, 'Afternoon shift 2PM-10PM'),
(3, 'Night', '22:00:00', '06:00:00', 8, 'Night shift 10PM-6AM');

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
  `cover_image` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `work_with_us` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`system_id`, `system_name`, `email`, `contact`, `about`, `cover_image`, `logo`, `work_with_us`) VALUES
(1, 'Employee Management', 'employeemanagement@gmail.com', '09214235', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum', 'uploads/1764062036_692573549edfe.jpg', 'uploads/1764062036_69257354a4a91.png', '[{\"title\":\"Meaningful Work\",\"icon\":\"fa-heart-pulse\",\"description\":\"asdfghj\"},{\"title\":\"Collaboration\",\"icon\":\"fa-clock\",\"description\":\"cxvbn\"},{\"title\":\"Innovation and Creativity\",\"icon\":\"fa-arrow-up-right-dots\",\"description\":\"gcxthjytk\"}]');

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
(2, 'Certificate of Employment'),
(4, 'Training');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `applicant_employee_id` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `otp` varchar(6) NOT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `reset_required` tinyint(4) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) NOT NULL,
  `token_expiry` varchar(255) NOT NULL,
  `sub_role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`applicant_employee_id`, `email`, `password`, `role`, `fullname`, `status`, `created_at`, `profile_pic`, `otp`, `otp_expiry`, `reset_required`, `reset_token`, `token_expiry`, `sub_role`) VALUES
('EMP-055', 'alay_darryljohn@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Daryll Alay', 'Active', '2025-12-05 19:59:16', NULL, '', NULL, 0, '', '', 'Inventory Admin'),
('EMP-019', 'anna_mendoza@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Anna Mendoza', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-001', 'antonio_rhoannenicole@plpasig.edu.ph', '$2y$10$s.q6f3uiYy57y9570UQ5ve0Fj11IloW60lz.jrS61iyZBhao2TY6O', 'Employee', 'Rhoanne Nicole Antonio', 'Active', '2025-10-25 10:38:47', NULL, '', NULL, 0, '529313', '2025-12-05 15:18:50', 'HR Manager'),
('EMP-029', 'bella_ramirez@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Bella Ramirez', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-051', 'bermudez_miguelcarlos@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Mico Bermudez', 'Active', '2025-12-01 16:21:27', NULL, '', NULL, 0, '', '', 'Content Management Admin'),
('EMP-041', 'bolotaolo_lark@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Lark Bolotaolo', 'Active', '2025-11-24 14:59:39', NULL, '', NULL, 0, '', '', 'Point of Sales Admin'),
('EMP-005', 'cacho_shaneellamae@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Shane Ella Cacho', 'Active', '2025-11-18 16:53:26', NULL, '', NULL, 0, '', '', NULL),
('EMP-050', 'cajurao_alexanderjr@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Alexander Cajurao', 'Active', '2025-11-29 10:57:09', NULL, '', NULL, 0, '', '', 'Inventory Officer'),
('EMP-027', 'carla_santos@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Carla Santos', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-009', 'carlos_mendoza@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Carlos Mendoza', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-054', 'castro_charljoven@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Cj Castro', 'Active', '2025-12-05 17:50:56', NULL, '', NULL, 0, '', '', 'Payroll Officer'),
('EMP-014', 'cheska_ramirez@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Cheska Ramirez', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-017', 'danica_flores@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Danica Joy Flores', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-053', 'depadua_charlesjeramy@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Charles Jeramy De Padua', 'Active', '2025-12-05 17:49:55', NULL, '', NULL, 0, '', '', 'Payroll Manager'),
('EMP-037', 'diana_lopez@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Diana Lopez', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-026', 'edward_reyes@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Edward Reyes', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-036', 'fiona_reyes@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Fiona Reyes', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-052', 'freeyt.zy@gmail.com', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Pepito Manaloto', 'Active', '2025-12-01 16:22:26', NULL, '', NULL, 0, '', '', 'System Staff'),
('EMP-042', 'gallardo_marvin@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Marvin Gallardo', 'Active', '2025-11-25 14:05:28', NULL, '', NULL, 0, '', '', 'Document Management Admin'),
('EMP-047', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$Hd75TeKazwdE0p.OtI2D8Oz08Ox48DLUVq1OCrXSzrGdR9fBcu0em', 'Employee', 'Jojana Garabillo', 'Active', '2025-11-27 19:56:26', NULL, '', NULL, 0, '', '', 'Human Resource (HR) Admin'),
('EMP-021', 'george_cruz@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'George Cruz', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-043', 'guerrero_ariuzdean@plpasig.edu.ph', '$2y$10$fdfeow0o1qicwAfZ3MdKQeVleGQaYTWevimdV3nNg4O1HqouEmp6a', 'Employee', 'Ariuz Dean Guerrero', 'Active', '2025-11-25 14:06:50', NULL, '', NULL, 0, '', '', 'Patient Management Admin'),
('EMP-006', 'gutierrez_jodielynn@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jodie Lyn Gutierrez', 'Active', '2025-11-18 16:53:26', NULL, '', NULL, 0, '', '', NULL),
('EMP-015', 'hannah_villanueva@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Hannah Nicole Villanueva', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-031', 'helena_cruz@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Helena Cruz', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-025', 'isabel_flores@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Isabel Flores', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-004', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jhanna Jaroda', 'Active', '2025-11-18 16:53:26', NULL, '', NULL, 0, '', '', 'Recruitment Manager'),
('EMP-016', 'jerome_alcantara@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Jerome Alcantara', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-013', 'johnf_velasquez@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'John Francis Velasquez', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-039', 'jojanajeangarabillo@gmail.com', '$2y$10$ECd2.hwlGfvWTTP89npMUOkB8LmJ7Ers.s0uBLPKEwRzJnGLNKjT2', 'Employee', 'Jean Garabillo', 'Active', '2025-11-23 19:06:50', NULL, '', NULL, 0, '', '', 'HR Director'),
('EMP-035', 'julian_flores@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Julian Flores', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-022', 'kevin_tan@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Kevin Tan', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-032', 'lance_tan@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Lance Tan', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-044', 'manrique_klarenzcobie@plpasig.edu.ph', '$2y$10$T.sE.gaEzbtKCBdvemzdGeEmS8d8jUvhYnj3AUASQEwaOu2wIuv/y', 'Employee', 'Klarenz Cobie O. Manrique', 'Active', '2025-11-25 14:08:06', NULL, '', NULL, 0, '', '', 'Payroll Admin'),
('EMP-038', 'marco.alcantara@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Marco Alcantara', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-024', 'maria_deguzman@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Maria De Guzman', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-030', 'mark_reyes@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Mark Joseph Reyes', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-011', 'miguel_santos@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Miguel Santos', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('HOS-002', 'n0305933@gmail.com', '$2y$10$uZauCbxJX84e0TSrqZ6Wp.92LcWgE5dZBSa/Se9uKgFPknRigl1ZK', 'Applicant', 'Nelly Bousted', 'Active', '2025-11-22 06:13:45', NULL, '', NULL, 0, '', '', NULL),
('EMP-034', 'nathan_deguzman@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Nathan De Guzman', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-049', 'noonajeogyo@gmail.com', '$2y$10$O8.H2g5cW05BEOWcZJyFyumGEG43f8PtuQBMnYchV.PHFqCF8k5Z6', 'Employee', 'Leonor Rivera', 'Active', '2025-11-28 12:14:06', NULL, '', NULL, 0, '', '', 'Document Management Admin'),
('EMP-023', 'olivia_lim@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Olivia Lim', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-045', 'opat09252005@gmail.com', '$2y$10$Unfz75rCYF6S9R6eAnBhe.OJbSvDHHesakHf9EDfpiN9n/RMgRTie', 'Employee', 'Joepat Lacerna', 'Active', '0000-00-00 00:00:00', NULL, '', NULL, 0, '', '', NULL),
('EMP-012', 'patricia_gomez@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Patricia Gomez', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-033', 'paul_lim@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Paul Lim', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-028', 'renato.villanueva@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Renato Villanueva', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-018', 'ricardo_manalo@plpasig.edu.ph', '$2y$10$a60gRs.Sx9A0jUz098Urs.0g4QWFHWLJMBSbEkKUtSmVIyWq94dNq', 'employee', 'Ricardo Manalo', 'Active', '2025-11-28 21:07:47', NULL, '', NULL, 0, '', '', NULL),
('EMP-048', 'ruberducky032518@gmail.com', '$2y$10$83zATZWowUwclO8adNIGU.IXPGCVQQ.Dwn/NTTVstU0VoJogYsZAi', 'Employee', 'Amihan Dimaguiba', 'Active', '0000-00-00 00:00:00', NULL, '', NULL, 0, '', '', NULL);

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
(43, 1, 2, 4, 1, 'On-Going', '', '2025-11-10 12:32:38'),
(44, 2, 9, 4, 1, 'On-Going', '', '2025-11-10 12:34:52'),
(46, 2, 9, 4, 1, 'On-Going', '', '2025-11-10 13:55:14'),
(49, 1, 1, 1, 2, 'On-Going', 'Rhoanne Nicole Antonio', '2025-11-20 14:22:20'),
(50, 9, 77, 5, 5, 'On-Going', 'Jane Garabillo', '2025-11-21 15:32:32'),
(51, 2, 9, 1, 2, 'To Post', 'Rhoanne Nicole Antonio', '2025-11-21 21:04:19'),
(52, 5, 34, 5, 3, 'On-Going', 'Rhoanne Nicole Antonio', '2025-11-21 21:04:40');

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
(3, 1, 1, 4, 1, 'On-Going', '', '2025-11-21'),
(4, 8, 66, 1, 1, 'Positions Filled', '', '2025-11-22');

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
  ADD KEY `fk_employee_emtype` (`type_name`),
  ADD KEY `fk_default_shift` (`default_shift_id`);

--
-- Indexes for table `employee_shift_pattern`
--
ALTER TABLE `employee_shift_pattern`
  ADD PRIMARY KEY (`emp_pattern_id`),
  ADD KEY `empID` (`empID`),
  ADD KEY `pattern_id` (`pattern_id`);

--
-- Indexes for table `employee_shift_schedule`
--
ALTER TABLE `employee_shift_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_sched_employee` (`empID`),
  ADD KEY `fk_sched_shift` (`shift_id`);

--
-- Indexes for table `employment_type`
--
ALTER TABLE `employment_type`
  ADD PRIMARY KEY (`emtypeID`);

--
-- Indexes for table `expected_staffing`
--
ALTER TABLE `expected_staffing`
  ADD PRIMARY KEY (`staffing_id`),
  ADD KEY `fk_exp_shift` (`shift_id`);

--
-- Indexes for table `general_request`
--
ALTER TABLE `general_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `empID` (`empID`),
  ADD KEY `request_type_id` (`request_type_id`);

--
-- Indexes for table `job_posting`
--
ALTER TABLE `job_posting`
  ADD PRIMARY KEY (`jobID`),
  ADD KEY `department` (`department`),
  ADD KEY `employment_type` (`employment_type`);

--
-- Indexes for table `leave_pay_categories`
--
ALTER TABLE `leave_pay_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_leave_request_employee` (`empID`),
  ADD KEY `fk_leave_request_leave_type` (`leave_type_id`),
  ADD KEY `fk_leave_request_pay_category` (`pay_category_id`);

--
-- Indexes for table `leave_request_archive`
--
ALTER TABLE `leave_request_archive`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_leave_request_employee` (`empID`),
  ADD KEY `fk_leave_request_leave_type` (`leave_type_id`),
  ADD KEY `fk_leave_request_pay_category` (`pay_category_id`);

--
-- Indexes for table `leave_settings`
--
ALTER TABLE `leave_settings`
  ADD PRIMARY KEY (`settingID`),
  ADD KEY `fk_leave_settings_leave_type` (`request_type_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_type_id` (`request_type_id`),
  ADD KEY `fk_leave_types_pay_category` (`pay_category_id`);

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
-- Indexes for table `shift_patterns`
--
ALTER TABLE `shift_patterns`
  ADD PRIMARY KEY (`pattern_id`);

--
-- Indexes for table `shift_pattern_details`
--
ALTER TABLE `shift_pattern_details`
  ADD PRIMARY KEY (`pattern_detail_id`),
  ADD KEY `pattern_id` (`pattern_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `shift_templates`
--
ALTER TABLE `shift_templates`
  ADD PRIMARY KEY (`shift_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `calendarID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `deptID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `employee_shift_pattern`
--
ALTER TABLE `employee_shift_pattern`
  MODIFY `emp_pattern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `employee_shift_schedule`
--
ALTER TABLE `employee_shift_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=953;

--
-- AUTO_INCREMENT for table `employment_type`
--
ALTER TABLE `employment_type`
  MODIFY `emtypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expected_staffing`
--
ALTER TABLE `expected_staffing`
  MODIFY `staffing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `general_request`
--
ALTER TABLE `general_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `job_posting`
--
ALTER TABLE `job_posting`
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `leave_pay_categories`
--
ALTER TABLE `leave_pay_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_request`
--
ALTER TABLE `leave_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `leave_request_archive`
--
ALTER TABLE `leave_request_archive`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `shift_patterns`
--
ALTER TABLE `shift_patterns`
  MODIFY `pattern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shift_pattern_details`
--
ALTER TABLE `shift_pattern_details`
  MODIFY `pattern_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `shift_templates`
--
ALTER TABLE `shift_templates`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `system_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `types_of_requests`
--
ALTER TABLE `types_of_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vacancies`
--
ALTER TABLE `vacancies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `vacancies_archive`
--
ALTER TABLE `vacancies_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_default_shift` FOREIGN KEY (`default_shift_id`) REFERENCES `shift_templates` (`shift_id`);

--
-- Constraints for table `employee_shift_pattern`
--
ALTER TABLE `employee_shift_pattern`
  ADD CONSTRAINT `employee_shift_pattern_ibfk_1` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`),
  ADD CONSTRAINT `employee_shift_pattern_ibfk_2` FOREIGN KEY (`pattern_id`) REFERENCES `shift_patterns` (`pattern_id`);

--
-- Constraints for table `employee_shift_schedule`
--
ALTER TABLE `employee_shift_schedule`
  ADD CONSTRAINT `fk_sched_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`),
  ADD CONSTRAINT `fk_sched_shift` FOREIGN KEY (`shift_id`) REFERENCES `shift_templates` (`shift_id`);

--
-- Constraints for table `expected_staffing`
--
ALTER TABLE `expected_staffing`
  ADD CONSTRAINT `fk_exp_shift` FOREIGN KEY (`shift_id`) REFERENCES `shift_templates` (`shift_id`);

--
-- Constraints for table `general_request`
--
ALTER TABLE `general_request`
  ADD CONSTRAINT `general_request_ibfk_1` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`) ON DELETE CASCADE,
  ADD CONSTRAINT `general_request_ibfk_2` FOREIGN KEY (`request_type_id`) REFERENCES `types_of_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `job_posting`
--
ALTER TABLE `job_posting`
  ADD CONSTRAINT `job_posting_ibfk_1` FOREIGN KEY (`department`) REFERENCES `department` (`deptID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `job_posting_ibfk_2` FOREIGN KEY (`employment_type`) REFERENCES `employment_type` (`emtypeID`);

--
-- Constraints for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD CONSTRAINT `fk_leave_request_employee` FOREIGN KEY (`empID`) REFERENCES `employee` (`empID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_request_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_request_pay_category` FOREIGN KEY (`pay_category_id`) REFERENCES `leave_pay_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `leave_settings`
--
ALTER TABLE `leave_settings`
  ADD CONSTRAINT `fk_leave_settings_leave_type` FOREIGN KEY (`request_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD CONSTRAINT `fk_leave_types_pay_category` FOREIGN KEY (`pay_category_id`) REFERENCES `leave_pay_categories` (`id`) ON DELETE SET NULL,
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
-- Constraints for table `shift_pattern_details`
--
ALTER TABLE `shift_pattern_details`
  ADD CONSTRAINT `shift_pattern_details_ibfk_1` FOREIGN KEY (`pattern_id`) REFERENCES `shift_patterns` (`pattern_id`),
  ADD CONSTRAINT `shift_pattern_details_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shift_templates` (`shift_id`);

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
