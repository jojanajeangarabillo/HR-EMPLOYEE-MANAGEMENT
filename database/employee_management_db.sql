-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 12:09 PM
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
('HOS-003', 'Joepat Lacerna', 'Radiology Assistant', 'Breast Screening Department', 'Contractual', '2025-11-22', '0909', 'opat09252005@gmail.com', 'Taguig', 'Ceo', 'Microsoft', '0000-00-00', 15, '', 'PLP', 'BSN', '2010', 'sample, sample, sample, sample, sample', 'samplesamplesamplesample\r\n', 'Archived', '2025-11-22', NULL),
('HOS-004', 'Jean Baglan', '', '', NULL, '2025-11-22', '', 'garabillo_jojanajean@plpasig.edu.ph', '', NULL, '', '0000-00-00', NULL, '', '', '', NULL, '', '', 'Pending', NULL, NULL);

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
(16, 'Utility Department');

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
('EMP-001', 'Rhoanne Nicole Antonio', 'Human Resources (HR) Department', 'HR Manager', 'Full Time', 'antonio_rhoannenicole@plpasig.edu.ph', 'Pasig\r\n', '0909', '2005-12-25', 'Female', '085', '123-1234-123', '123-1234-123', '123-1234-123', '123-1234-123', 'employee_EMP-001.jpg', '2025-11-18'),
('EMP-004', 'Jhanna Jaroda', 'Human Resources (HR) Department', 'Recruitment Manager', 'Full Time', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-005', 'Shane Ella Cacho', 'Human Resources (HR) Department', 'Training and Development Coordinator', 'Full Time', 'cacho_shaneellamae@plpasig.edu.ph', '', '', NULL, NULL, '', '', '', '', '', NULL, '2025-11-18'),
('EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', 'Pasig\r\n', '0303', '2001-11-24', '', '015', '', '', '', '', 'employee_EMP-006.jpg', '2025-11-18'),
('EMP-007', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', 'Contractual', 'opat09252005@gmail.com', 'Taguig', '0909', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22'),
('EMP-009', 'Carlos Mendoza', 'Cardiology Department', 'Cardiac Technologist', 'Full Time', 'carlos_mendoza@plpasig.edu.ph', 'Pasig City', '09171234567', '1990-03-15', 'Male', '09981234567', '111-2222-333', '444-5555-666', '777-8888-999', '1234-5678-9012', 'employee_EMP-009.jpg', '2025-11-20'),
('EMP-011', 'Miguel Santos', 'Utility Department', 'Utility Head', 'Contractual', 'miguel_santos@plpasig.edu.ph', 'Taguig', '09081239876', '1985-01-20', 'Male', '09221239876', '333-4444-555', '666-7777-888', '999-0000-111', '6789-1234-5678', 'employee_EMP-011.jpg', '2025-11-21'),
('EMP-012', 'Patricia Gomez', 'Gastroenterology Department', 'Endoscopy Nurse', 'Regular', 'patricia_gomez@plpasig.edu.ph', 'Quezon City', '09273451234', '1996-05-02', 'Female', '09573451234', '444-5555-666', '777-8888-999', '000-1111-222', '9876-5432-1011', 'employee_EMP-012.jpg', '2025-11-21'),
('EMP-013', 'John Francis Velasquez', 'General Surgery Department', 'Surgical Technician', 'Internship', 'johnf_velasquez@plpasig.edu.ph', 'Manila', '09384562378', '2001-10-11', 'Male', '09454562378', '555-6666-777', '888-9999-000', '111-2222-333', '1928-3746-2910', 'employee_EMP-013.jpg', '2025-11-22'),
('EMP-014', 'Cheska Ramirez', 'Breast Screening Department', 'Mammography Technologist', 'Full Time', 'cheska_ramirez@plpasig.edu.ph', 'Cainta', '09171239888', '1994-12-01', 'Female', '09391239888', '999-8888-777', '666-5555-444', '333-2222-111', '3141-5926-5358', 'employee_EMP-014.jpg', '2025-11-22'),
('EMP-015', 'Hannah Nicole Villanueva', 'Hematology Department', 'Phlebotomist', 'Regular', 'hannah_villanueva@plpasig.edu.ph', 'Antipolo', '09092349877', '1997-09-22', 'Female', '09452349877', '121-2121-212', '343-4343-434', '565-6565-656', '787-8787-878', 'employee_EMP-015.jpg', '2025-11-22'),
('EMP-016', 'Jerome Alcantara', 'ENT Department', 'ENT Resident', 'Full Time', 'jerome_alcantara@plpasig.edu.ph', 'Marikina', '09481239876', '1992-04-16', 'Male', '09231239876', '213-5465-879', '745-6321-987', '123-6547-852', '951-3578-246', 'employee_EMP-016.jpg', '2025-11-22'),
('EMP-017', 'Danica Joy Flores', 'Gynecology Department', 'Midwife', 'Contractual', 'danica_flores@plpasig.edu.ph', 'Pasig City', '09174566789', '1995-11-05', 'Female', '09384566789', '987-6543-210', '345-6789-012', '456-7890-123', '1122-3344-5566', 'employee_EMP-017.jpg', '2025-11-22'),
('EMP-018', 'Ricardo Manalo', 'Elderly Services (Geriatrics)', 'Geriatric Nurse', 'Full Time', 'ricardo_manalo@plpasig.edu.ph', 'San Mateo', '09391235678', '1989-02-14', 'Male', '09991235678', '741-8529-963', '159-3579-951', '258-4567-789', '2233-4455-6677', 'employee_EMP-018.jpg', '2025-11-22'),
('EMP-019', 'Alice Mae Santos', 'Cardiology Department', 'ECG Technician', 'Full Time', 'alice_santos@plpasig.edu.ph', 'Pasig', '09181234567', '1993-06-12', 'Female', '09181239876', '111-222-333', '444-555-666', '777-888-999', '123-456-789', 'employee_EMP-019.jpg', '2025-11-22'),
('EMP-021', 'Elena Cruz', 'General Surgery Department', 'Scrub Nurse', 'Regular', 'elena_cruz@plpasig.edu.ph', 'Makati', '09331234567', '1992-03-14', 'Female', '09331239876', '333-444-555', '666-777-888', '999-000-111', '345-678-901', 'employee_EMP-021.jpg', '2025-11-22'),
('EMP-022', 'Kevin Paul Tan', 'Gynecology Department', 'OB-GYN Resident', 'Contractual', 'kevin_tan@plpasig.edu.ph', 'Mandaluyong', '09441234567', '1990-12-05', 'Male', '09441239876', '444-555-666', '777-888-999', '000-111-222', '456-789-012', 'employee_EMP-022.jpg', '2025-11-22'),
('EMP-023', 'Sophia Mae Lim', 'Hematology Department', 'Phlebotomist', 'Full Time', 'sophia_lim@plpasig.edu.ph', 'Pasig', '09551234567', '1996-09-30', 'Female', '09551239876', '555-666-777', '888-999-000', '111-222-333', '567-890-123', 'employee_EMP-023.jpg', '2025-11-22'),
('EMP-024', 'Rafael De Guzman', 'Breast Screening Department', 'Radiology Assistant', 'Regular', 'rafael_deguzman@plpasig.edu.ph', 'Taguig', '09661234567', '1994-07-19', 'Male', '09661239876', '666-777-888', '999-000-111', '222-333-444', '678-901-234', 'employee_EMP-024.jpg', '2025-11-22'),
('EMP-025', 'Isabella Flores', 'Anesthetics Department', 'Anesthetic Technician', 'Internship', 'isabella_flores@plpasig.edu.ph', 'Mandaluyong', '09771234567', '2000-02-28', 'Female', '09771239876', '777-888-999', '000-111-222', '333-444-555', '789-012-345', 'employee_EMP-025.jpg', '2025-11-22'),
('EMP-026', 'Daniel Reyes', 'Elderly Services (Geriatrics)', 'Geriatric Nurse', 'Full Time', 'daniel_reyes@plpasig.edu.ph', 'Pasig', '09881234567', '1988-11-11', 'Male', '09881239876', '888-999-000', '111-222-333', '444-555-666', '890-123-456', 'employee_EMP-026.jpg', '2025-11-22'),
('EMP-027', 'Angela Santos', 'ENT Department', 'Audiologist', 'Contractual', 'angela_santos@plpasig.edu.ph', 'Quezon City', '09991234567', '1991-05-20', 'Female', '09991239876', '999-000-111', '222-333-444', '555-666-777', '901-234-567', 'employee_EMP-027.jpg', '2025-11-22'),
('EMP-028', 'Luis Fernando Cruz', 'Utility Department', 'Utility Head', 'Part Time', 'luis_cruz@plpasig.edu.ph', 'Taguig', '09011234567', '1985-01-15', 'Male', '09011239876', '111-222-333', '444-555-666', '777-888-999', '012-345-678', 'employee_EMP-028.jpg', '2025-11-22'),
('EMP-029', 'Alice Mae Santos', 'Cardiology Department', 'ECG Technician', 'Full Time', 'alice_santos@plpasig.edu.ph', 'Pasig', '09181234567', '1993-06-12', 'Female', '09181239876', '111-222-333', '444-555-666', '777-888-999', '123-456-789', 'employee_EMP-029.jpg', '2025-11-22'),
('EMP-030', 'Mark Joseph Reyes', 'IT Department', 'Utility Head', 'Part Time', 'mark_reyes@plpasig.edu.ph', 'Quezon City', '09221234567', '1995-08-23', 'Male', '09221239876', '222-333-444', '555-666-777', '888-999-000', '234-567-890', 'employee_EMP-030.jpg', '2025-11-22'),
('EMP-031', 'Elena Cruz', 'General Surgery Department', 'Scrub Nurse', 'Regular', 'elena_cruz@plpasig.edu.ph', 'Makati', '09331234567', '1992-03-14', 'Female', '09331239876', '333-444-555', '666-777-888', '999-000-111', '345-678-901', 'employee_EMP-031.jpg', '2025-11-22'),
('EMP-032', 'Kevin Paul Tan', 'Gynecology Department', 'OB-GYN Resident', 'Contractual', 'kevin_tan@plpasig.edu.ph', 'Mandaluyong', '09441234567', '1990-12-05', 'Male', '09441239876', '444-555-666', '777-888-999', '000-111-222', '456-789-012', 'employee_EMP-032.jpg', '2025-11-22'),
('EMP-033', 'Sophia Mae Lim', 'Hematology Department', 'Phlebotomist', 'Full Time', 'sophia_lim@plpasig.edu.ph', 'Pasig', '09551234567', '1996-09-30', 'Female', '09551239876', '555-666-777', '888-999-000', '111-222-333', '567-890-123', 'employee_EMP-033.jpg', '2025-11-22'),
('EMP-034', 'Rafael De Guzman', 'Breast Screening Department', 'Radiology Assistant', 'Regular', 'rafael_deguzman@plpasig.edu.ph', 'Taguig', '09661234567', '1994-07-19', 'Male', '09661239876', '666-777-888', '999-000-111', '222-333-444', '678-901-234', 'employee_EMP-034.jpg', '2025-11-22'),
('EMP-035', 'Isabella Flores', 'Anesthetics Department', 'Anesthetic Technician', 'Internship', 'isabella_flores@plpasig.edu.ph', 'Mandaluyong', '09771234567', '2000-02-28', 'Female', '09771239876', '777-888-999', '000-111-222', '333-444-555', '789-012-345', 'employee_EMP-035.jpg', '2025-11-22'),
('EMP-036', 'Daniel Reyes', 'Elderly Services (Geriatrics)', 'Geriatric Nurse', 'Full Time', 'daniel_reyes@plpasig.edu.ph', 'Pasig', '09881234567', '1988-11-11', 'Male', '09881239876', '888-999-000', '111-222-333', '444-555-666', '890-123-456', 'employee_EMP-036.jpg', '2025-11-22'),
('EMP-037', 'Angela Santos', 'ENT Department', 'Audiologist', 'Contractual', 'angela_santos@plpasig.edu.ph', 'Quezon City', '09991234567', '1991-05-20', 'Female', '09991239876', '999-000-111', '222-333-444', '555-666-777', '901-234-567', 'employee_EMP-037.jpg', '2025-11-22'),
('EMP-038', 'Luis Fernando Cruz', 'Utility Department', 'Utility Head', 'Part Time', 'luis_cruz@plpasig.edu.ph', 'Taguig', '09011234567', '1985-01-15', 'Male', '09011239876', '111-222-333', '444-555-666', '777-888-999', '012-345-678', 'employee_EMP-038.jpg', '2025-11-22'),
('EMP-039', 'Jean Garabillo', 'Human Resources (HR) Department', 'HR Director', 'Full Time', 'jojanajeangarabillo@gmail.com', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'employee_EMP-039.jpg', '2025-11-23');

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
(2, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'gutierrez_jodielynn@plpasig.edu.ph', 2, 'awadrgbk', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 15:03:02', NULL),
(5, 'EMP-007', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', 'opat09252005@gmail.com', 2, 'asd', 'Pending', NULL, '2025-11-23 18:48:09', NULL);

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
(36, 'Hematology Lab Manager', 'SDSFG', 9, NULL, 'BSA', 'Asdfg', '1234', 5, 5, NULL, 5, '2025-11-21', '2025-11-26');

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
(3, 'Partially Paid');

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
(35, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', 1, 'Leave', 'asd', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 16:31:45', 3, 1, 'Maternity Leave', '2025-11-24', '2025-11-27', 4),
(38, 'EMP-007', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', 'Contractual', 'opat09252005@gmail.com', '', 1, 'Leave', 'ASDF', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 17:20:19', 4, 1, 'Paternity Leave', '2025-11-29', '2025-12-08', 10),
(39, 'EMP-007', 'Joepat Lacerna', 'Breast Screening Department', 'Radiology Assistant', 'Contractual', 'opat09252005@gmail.com', '', 1, 'Leave', 'xxsdcfv', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 18:17:01', 2, 1, 'Vacation Leave', '2025-11-25', '2025-11-28', 4),
(40, 'EMP-006', 'Jodie Lyn Gutierrez', 'Human Resources (HR) Department', 'HR Officer', 'Full Time', 'gutierrez_jodielynn@plpasig.edu.ph', '', 1, 'Leave', 'asdf', 'Approved', 'Rhoanne Nicole Antonio', '2025-11-23 18:18:58', 1, 1, 'Sick Leave', '2025-11-28', '2025-11-30', 3);

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
(18, '2025-11-23', '2025-11-24', NULL, 0, 'Rhoanne Nicole Antonio', '2025-11-23 15:59:38', 1),
(19, '2025-11-23', '2025-11-25', 11, 0, 'Rhoanne Nicole Antonio', '2025-11-23 18:16:04', 1);

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
(7, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'Leave Availability for November', 'Only 2 employees are allowed to have a leave this month. This is a first come first serve basis. Thank You', '2025-11-23 16:00:33', 1, 18),
(8, 'antonio_rhoannenicole@plpasig.edu.ph', 'Rhoanne Nicole Antonio', 'November Leave Slots', 'jiruaknsc', '2025-11-23 18:16:21', 1, 19);

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
(100, 16, NULL, 'Utility Head');

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
(95, 'HOS-003', 35, 'Qualification mismatch', '2025-11-22 08:45:18'),
(96, 'HOS-003', 36, 'Qualification mismatch', '2025-11-22 08:45:21'),
(97, 'HOS-003', 34, 'Qualification mismatch', '2025-11-22 08:45:24');

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
(1, 'Employee Management', 'employeemanagement@gmail.com', '09214235', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum', 'uploads/1763771757_6921056d38950.jpg', 'uploads/1763744701_69209bbd21d7e.png', '[{\"title\":\"Meaningful Work\",\"icon\":\"fa-heart-pulse\",\"description\":\"asdfghj\"},{\"title\":\"Collaboration\",\"icon\":\"fa-clock\",\"description\":\"cxvbn\"},{\"title\":\"Innovation and Creativity\",\"icon\":\"fa-arrow-up-right-dots\",\"description\":\"gcxthjytk\"}]');

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
('USR-006', 'EMP-005', 'cacho_shaneellamae@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Shane Ella Cacho', 'Active', '2025-11-18 16:53:26', NULL, '', '', NULL),
('', 'HOS-004', 'garabillo_jojanajean@plpasig.edu.ph', '$2y$10$hG1.dmRaChqcFACEuNI32OE/3OKb3xRfN3PQ5c6chqz.KEUSeKBvu', 'Applicant', 'Jean Baglan', 'Pending', '0000-00-00 00:00:00', NULL, '', '', NULL),
('USR-007', 'EMP-006', 'gutierrez_jodielynn@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jodie Lyn Gutierrez', 'Active', '2025-11-18 16:53:26', NULL, '', '', NULL),
('USR-005', 'EMP-004', 'jaroda_jhanna_rhaynne@plpasig.edu.ph', '$2y$10$RJtHsBgGOE3/PVHBCH5FdOJoYXj04MmyajHi2zQYyYtjNU0r6rm5.', 'Employee', 'Jhanna Jaroda', 'Active', '2025-11-18 16:53:26', NULL, '', '', 'Recruitment Manager'),
('', 'EMP-039', 'jojanajeangarabillo@gmail.com', '$2y$10$dpjXpB09v2hA.mid64gViusF9uTn6jf.xS4QfnPCFYTPgMCvH4cNy', 'Employee', 'Jean Garabillo', 'Active', '2025-11-23 19:06:50', NULL, '', '', 'HR Director'),
('USR-002', 'HOS-002', 'n0305933@gmail.com', '$2y$10$uZauCbxJX84e0TSrqZ6Wp.92LcWgE5dZBSa/Se9uKgFPknRigl1ZK', 'Applicant', 'Nelly Bousted', 'Active', '2025-11-22 06:13:45', NULL, '', '', NULL),
('', 'EMP-007', 'opat09252005@gmail.com', '$2y$10$VnVYwJ4uMtJzIx3LYUdxHuX3mQDCCQS.4cONLO1hs466gWGItDc9G', 'Employee', 'Joepat Lacerna', 'Active', '0000-00-00 00:00:00', NULL, '', '', NULL);

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
(52, 5, 34, 5, 3, 'To Post', 'Rhoanne Nicole Antonio', '2025-11-21 21:04:40');

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
  ADD KEY `fk_employee_emtype` (`type_name`);

--
-- Indexes for table `employment_type`
--
ALTER TABLE `employment_type`
  ADD PRIMARY KEY (`emtypeID`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
-- AUTO_INCREMENT for table `employment_type`
--
ALTER TABLE `employment_type`
  MODIFY `emtypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `general_request`
--
ALTER TABLE `general_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `job_posting`
--
ALTER TABLE `job_posting`
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `leave_pay_categories`
--
ALTER TABLE `leave_pay_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_request`
--
ALTER TABLE `leave_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `leave_request_archive`
--
ALTER TABLE `leave_request_archive`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `manager_announcement`
--
ALTER TABLE `manager_announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `positionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `rejected_applications`
--
ALTER TABLE `rejected_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

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
