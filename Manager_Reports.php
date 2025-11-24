<?php
session_start();
require 'admin/db.connect.php';
require('fpdf/fpdf.php');

$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null; // Make sure empID is stored in session
if ($employeeID) {
    $stmt = $conn->prepare("SELECT profile_pic FROM employee WHERE empID = ?");
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profile_picture = !empty($row['profile_pic'])
            ? "uploads/employees/" . $row['profile_pic']
            : "uploads/employees/default.png";
    } else {

        $profile_picture = "uploads/employees/default.png";
    }
} else {
    $employeename = $_SESSION['fullname'] ?? "Employee";
    $profile_picture = "uploads/employees/default.png";
}

// MENUS
$menus = [
    "HR Director" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Job Post" => "Manager-JobPosting.php",
        "Calendar" => "Manager_Calendar.php",
        "Approvals" => "Manager_Approvals.php",
        "Reports" => "Manager_Reports.php",
        "Settings" => "Manager_LeaveSettings.php",
        "Logout" => "Login.php"
    ],

    "HR Manager" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Job Post" => "Manager-JobPosting.php",
        "Calendar" => "Manager_Calendar.php",
        "Approvals" => "Manager_Approvals.php",
        "Reports" => "Manager_Reports.php",
        "Settings" => "Manager_LeaveSettings.php",
        "Logout" => "Login.php"
    ],

    "Recruitment Manager" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Requests" => "Manager_Request.php",
    "Reports" => "Manager_Reports.php",
        "Logout" => "Login.php"
    ],

    "HR Officer" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Logout" => "Login.php"
    ],


];

$role = $_SESSION['sub_role'] ?? "HR Manager";
$icons = [
    "Dashboard" => "fa-table-columns",
    "Applicants" => "fa-user",
    "Pending Applicants" => "fa-clock",
    "Newly Hired" => "fa-user-check",
    "Employees" => "fa-users",
    "Requests" => "fa-file-lines",
    "Vacancies" => "fa-briefcase",
    "Job Post" => "fa-bullhorn",
    "Calendar" => "fa-calendar-days",
    "Approvals" => "fa-square-check",
    "Reports" => "fa-chart-column",
    "Settings" => "fa-gear",
    "Logout" => "fa-right-from-bracket"
];

 


// --- Fetch admin name ---
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// --- Get filters ---
$report_type = $_GET['report'] ?? (($role === 'Recruitment Manager') ? 'pending-applicants' : 'leaves');
$filter_dept = $_GET['dept'] ?? 'all';
$filter_from = $_GET['from'] ?? null;
$filter_to = $_GET['to'] ?? null;
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// --- Fetch departments ---
$deptQuery = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName");
$departments = [];
while ($d = $deptQuery->fetch_assoc())
    $departments[] = $d;

 

$summary = [];
$leaves = [];
$generals = [];
$pendingApplicants = [];
$archivedApplicants = [];

$deptNameSel = null;
if ($filter_dept != 'all') {
    foreach ($departments as $d) {
        if ($d['deptID'] == $filter_dept) {
            $deptNameSel = $d['deptName'];
            break;
        }
    }
}

if ($report_type === 'leaves' && ($role === 'HR Director' || $role === 'HR Manager' || $role === 'HR Officer')) {
    if ($deptNameSel) {
        $stmt = $conn->prepare("SELECT lr.empID, lr.fullname, e.department, lr.leave_type_name, lr.from_date, lr.to_date, lr.duration, lr.status, lr.requested_at FROM leave_request lr JOIN employee e ON lr.empID = e.empID WHERE YEAR(lr.requested_at) = ? AND MONTH(lr.requested_at) = ? AND e.department = ? ORDER BY lr.requested_at DESC");
        $stmt->bind_param('iis', $filter_year, $filter_month, $deptNameSel);
    } else {
        $stmt = $conn->prepare("SELECT lr.empID, lr.fullname, e.department, lr.leave_type_name, lr.from_date, lr.to_date, lr.duration, lr.status, lr.requested_at FROM leave_request lr JOIN employee e ON lr.empID = e.empID WHERE YEAR(lr.requested_at) = ? AND MONTH(lr.requested_at) = ? ORDER BY lr.requested_at DESC");
        $stmt->bind_param('ii', $filter_year, $filter_month);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $leaves[] = $row;
    $stmt->close();
} elseif ($report_type === 'general-requests' && ($role === 'HR Director' || $role === 'HR Manager' || $role === 'HR Officer')) {
    if ($deptNameSel) {
        $stmt = $conn->prepare("SELECT gr.request_id, gr.empID, gr.fullname, e.department, tor.request_type_name, gr.reason, gr.status, gr.requested_at FROM general_request gr JOIN employee e ON gr.empID = e.empID JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE YEAR(gr.requested_at) = ? AND MONTH(gr.requested_at) = ? AND e.department = ? ORDER BY gr.requested_at DESC");
        $stmt->bind_param('iis', $filter_year, $filter_month, $deptNameSel);
    } else {
        $stmt = $conn->prepare("SELECT gr.request_id, gr.empID, gr.fullname, e.department, tor.request_type_name, gr.reason, gr.status, gr.requested_at FROM general_request gr JOIN employee e ON gr.empID = e.empID JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE YEAR(gr.requested_at) = ? AND MONTH(gr.requested_at) = ? ORDER BY gr.requested_at DESC");
        $stmt->bind_param('ii', $filter_year, $filter_month);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $generals[] = $row;
    $stmt->close();
} elseif ($report_type === 'pending-applicants' && ($role === 'Recruitment Manager')) {
    $dateExpr = "COALESCE(app.applied_at, a.date_applied)";
    if ($deptNameSel) {
        $stmt = $conn->prepare("SELECT a.applicantID, a.fullName, app.department_name, app.job_title, COALESCE(app.status, a.status) AS status, $dateExpr AS applied_at FROM applicant a LEFT JOIN applications app ON a.applicantID = app.applicantID WHERE COALESCE(app.status, a.status) = 'Pending' AND app.department_name = ? AND YEAR($dateExpr) = ? AND MONTH($dateExpr) = ? ORDER BY $dateExpr DESC");
        $stmt->bind_param('sii', $deptNameSel, $filter_year, $filter_month);
    } else {
        $stmt = $conn->prepare("SELECT a.applicantID, a.fullName, app.department_name, app.job_title, COALESCE(app.status, a.status) AS status, $dateExpr AS applied_at FROM applicant a LEFT JOIN applications app ON a.applicantID = app.applicantID WHERE COALESCE(app.status, a.status) = 'Pending' AND YEAR($dateExpr) = ? AND MONTH($dateExpr) = ? ORDER BY $dateExpr DESC");
        $stmt->bind_param('ii', $filter_year, $filter_month);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $pendingApplicants[] = $row;
    $stmt->close();
} elseif ($report_type === 'archived-applicants' && ($role === 'Recruitment Manager')) {
    $dateExpr = "COALESCE(a.hired_at, a.date_applied)";
    if ($deptNameSel) {
        $stmt = $conn->prepare("SELECT a.applicantID, a.fullName, a.department, a.position_applied, a.status, a.hired_at, a.date_applied FROM applicant a WHERE a.status = 'Archived' AND a.department = ? AND (YEAR(a.hired_at) = ? OR a.hired_at IS NULL) AND MONTH(a.hired_at) = ? ORDER BY $dateExpr DESC");
        $stmt->bind_param('sii', $deptNameSel, $filter_year, $filter_month);
    } else {
        $stmt = $conn->prepare("SELECT a.applicantID, a.fullName, a.department, a.position_applied, a.status, a.hired_at, a.date_applied FROM applicant a WHERE a.status = 'Archived' AND (YEAR(a.hired_at) = ? OR a.hired_at IS NULL) AND MONTH(a.hired_at) = ? ORDER BY $dateExpr DESC");
        $stmt->bind_param('ii', $filter_year, $filter_month);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $archivedApplicants[] = $row;
    $stmt->close();
}


// --- PDF Export ---
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    // --- Header ---
    $bannerHeight = 45; // increase height to fit date range
    $pdf->SetFillColor(50, 120, 200);
    $pdf->Rect(0, 0, 297, $bannerHeight, 'F'); // full-width header

    $pdf->Image('C:/xampp/htdocs/HR-EMPLOYEE-MANAGEMENT/images/hospitallogo.png', 10, 5, 25); // logo on left

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(0, 8);
    $pdf->Cell(0, 10, 'HOSPITAL REPORT', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 7, strtoupper(($role === 'Recruitment Manager') ? 'APPLICANT REPORT' : 'EMPLOYEE REPORT'), 0, 1, 'C');

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'Report Created: ' . date('F d, Y H:i:s'), 0, 1, 'C');

    // Filters / Date Range
    $dateRange = '';
    if ($filter_from && $filter_to) {
        $dateRange = date('F d, Y', strtotime($filter_from)) . ' to ' . date('F d, Y', strtotime($filter_to));
    } elseif ($filter_from) {
        $dateRange = 'From ' . date('F d, Y', strtotime($filter_from));
    } elseif ($filter_to) {
        $dateRange = 'Up to ' . date('F d, Y', strtotime($filter_to));
    } else {
        $dateRange = 'All Dates';
    }

    $pdf->Cell(0, 6, "Report Type: " . strtoupper(str_replace('-', ' ', $report_type)), 0, 1, 'C');
    $pdf->Cell(0, 6, "Date Range: $dateRange", 0, 1, 'C');

    // Draw a line to separate header from table
    $pdf->SetDrawColor(50, 120, 200);
    $pdf->SetLineWidth(0.7);
    $pdf->Line(10, $bannerHeight, 287, $bannerHeight); // line at bottom of banner
    $pdf->Ln(4);


    if ($report_type === 'leaves') {
        $colWidths = [10, 35, 55, 55, 35, 35, 20, 40];
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(50, 120, 200);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 8, 'Emp ID', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 8, 'From', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 8, 'To', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 8, 'Days', 1, 0, 'C', true);
        $pdf->Cell($colWidths[7], 8, 'Leave Type', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;
        $lineHeight = 6;
        $pageHeightLimit = 190;
        $counter = 1;
        foreach ($leaves as $r) {
            if ($pdf->GetY() + $lineHeight * 2 > $pageHeightLimit) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 120, 200);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
                $pdf->Cell($colWidths[1], 8, 'Emp ID', 1, 0, 'C', true);
                $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
                $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
                $pdf->Cell($colWidths[4], 8, 'From', 1, 0, 'C', true);
                $pdf->Cell($colWidths[5], 8, 'To', 1, 0, 'C', true);
                $pdf->Cell($colWidths[6], 8, 'Days', 1, 0, 'C', true);
                $pdf->Cell($colWidths[7], 8, 'Leave Type', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
            $pdf->Cell($colWidths[0], $lineHeight, $counter, 1, 0, 'C', true);
            $pdf->Cell($colWidths[1], $lineHeight, $r['empID'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[2], $lineHeight, $r['fullname'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[3], $lineHeight, $r['department'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[4], $lineHeight, $r['from_date'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[5], $lineHeight, $r['to_date'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[6], $lineHeight, $r['duration'], 1, 0, 'C', true);
            $pdf->Cell($colWidths[7], $lineHeight, $r['leave_type_name'], 1, 1, 'L', true);
            $fill = !$fill;
            $counter++;
        }
    } elseif ($report_type === 'general-requests') {
        $colWidths = [10, 35, 55, 55, 50, 60, 35];
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(50, 120, 200);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 8, 'Emp ID', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 8, 'Request Type', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 8, 'Reason', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 8, 'Status', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;
        $lineHeight = 6;
        $pageHeightLimit = 190;
        $counter = 1;
        foreach ($generals as $r) {
            if ($pdf->GetY() + $lineHeight * 2 > $pageHeightLimit) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 120, 200);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
                $pdf->Cell($colWidths[1], 8, 'Emp ID', 1, 0, 'C', true);
                $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
                $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
                $pdf->Cell($colWidths[4], 8, 'Request Type', 1, 0, 'C', true);
                $pdf->Cell($colWidths[5], 8, 'Reason', 1, 0, 'C', true);
                $pdf->Cell($colWidths[6], 8, 'Status', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
            $pdf->Cell($colWidths[0], $lineHeight, $counter, 1, 0, 'C', true);
            $pdf->Cell($colWidths[1], $lineHeight, $r['empID'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[2], $lineHeight, $r['fullname'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[3], $lineHeight, $r['department'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[4], $lineHeight, $r['request_type_name'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[5], $lineHeight, $r['reason'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[6], $lineHeight, $r['status'], 1, 1, 'C', true);
            $fill = !$fill;
            $counter++;
        }
    } elseif ($report_type === 'pending-applicants') {
        $colWidths = [10, 30, 60, 50, 50, 35];
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(50, 120, 200);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 8, 'Applicant ID', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 8, 'Job Title', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 8, 'Applied At', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;
        $lineHeight = 6;
        $pageHeightLimit = 190;
        $counter = 1;
        foreach ($pendingApplicants as $r) {
            if ($pdf->GetY() + $lineHeight * 2 > $pageHeightLimit) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 120, 200);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
                $pdf->Cell($colWidths[1], 8, 'Applicant ID', 1, 0, 'C', true);
                $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
                $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
                $pdf->Cell($colWidths[4], 8, 'Job Title', 1, 0, 'C', true);
                $pdf->Cell($colWidths[5], 8, 'Applied At', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
            $pdf->Cell($colWidths[0], $lineHeight, $counter, 1, 0, 'C', true);
            $pdf->Cell($colWidths[1], $lineHeight, $r['applicantID'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[2], $lineHeight, $r['fullName'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[3], $lineHeight, $r['department_name'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[4], $lineHeight, $r['job_title'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[5], $lineHeight, $r['applied_at'], 1, 1, 'L', true);
            $fill = !$fill;
            $counter++;
        }
    } elseif ($report_type === 'archived-applicants') {
        $colWidths = [10, 30, 60, 50, 45, 35, 35];
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(50, 120, 200);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 8, 'Applicant ID', 1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
        $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
        $pdf->Cell($colWidths[4], 8, 'Position Applied', 1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 8, 'Hired At', 1, 0, 'C', true);
        $pdf->Cell($colWidths[6], 8, 'Date Applied', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $fill = false;
        $lineHeight = 6;
        $pageHeightLimit = 190;
        $counter = 1;
        foreach ($archivedApplicants as $r) {
            if ($pdf->GetY() + $lineHeight * 2 > $pageHeightLimit) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(50, 120, 200);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
                $pdf->Cell($colWidths[1], 8, 'Applicant ID', 1, 0, 'C', true);
                $pdf->Cell($colWidths[2], 8, 'Fullname', 1, 0, 'C', true);
                $pdf->Cell($colWidths[3], 8, 'Department', 1, 0, 'C', true);
                $pdf->Cell($colWidths[4], 8, 'Position Applied', 1, 0, 'C', true);
                $pdf->Cell($colWidths[5], 8, 'Hired At', 1, 0, 'C', true);
                $pdf->Cell($colWidths[6], 8, 'Date Applied', 1, 1, 'C', true);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
            $pdf->Cell($colWidths[0], $lineHeight, $counter, 1, 0, 'C', true);
            $pdf->Cell($colWidths[1], $lineHeight, $r['applicantID'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[2], $lineHeight, $r['fullName'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[3], $lineHeight, $r['department'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[4], $lineHeight, $r['position_applied'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[5], $lineHeight, $r['hired_at'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[6], $lineHeight, $r['date_applied'], 1, 1, 'L', true);
            $fill = !$fill;
            $counter++;
        }
    }

    // --- Footer ---
    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y H:i') . ' | HR Employee Management System | Page ' . $pdf->PageNo(), 0, 0, 'C');

    $pdf->Output('D', $report_type . '.pdf');
    exit;
}





?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Reports</title>
    <link rel="stylesheet" href="manager-sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column;
        }

        .main-content h1 {
            color: #1E3A8A;
            margin-bottom: 20px;
            margin-left: 10px;
        }
    </style>



</head>

<body>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <a href="Manager_Profile.php" class="profile">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
            </a>
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $managername"; ?></p>
        </div>

        <ul class="nav">
            <?php foreach ($menus[$role] as $label => $link): ?>
                        <li><a href="<?php echo $link; ?>"><i
                                    class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <main class="main-content">
        <div class="container-fluid p-4">
            <h1><i class="fa-solid fa-chart-column"></i> Manager Reports</h1>

            <!-- Filters Card -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Report Type</label>
                            <select class="form-select" name="report" onchange="this.form.submit()">
                                <?php if ($role === 'Recruitment Manager'): ?>
                                    <option value="pending-applicants" <?= $report_type == 'pending-applicants' ? 'selected' : '' ?>>Pending Applicants</option>
                                    <option value="archived-applicants" <?= $report_type == 'archived-applicants' ? 'selected' : '' ?>>Archived Applicants</option>
                                <?php else: ?>
                                    <option value="leaves" <?= $report_type == 'leaves' ? 'selected' : '' ?>>Leaves By Month</option>
                                    <option value="general-requests" <?= $report_type == 'general-requests' ? 'selected' : '' ?>>General Requests By Month</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Department</label>
                            <select class="form-select" name="dept" onchange="this.form.submit()">
                                <option value="all">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                            <option value="<?= $d['deptID'] ?>" <?= ($filter_dept == $d['deptID']) ? 'selected' : '' ?>>
                                                <?= $d['deptName'] ?>
                                            </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Month</label>
                            <select class="form-select" name="month" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Year</label>
                            <select class="form-select" name="year" onchange="this.form.submit()">
                                <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                                            <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2 mt-2">
                            <a href="?<?= http_build_query($_GET) ?>&export=pdf" class="btn btn-danger"><i class="fa-solid fa-file-pdf"></i> Export PDF</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if ($report_type === 'leaves'): ?>
                                    <table class="table table-striped table-hover align-middle text-center mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Emp ID</th>
                                                <th>Fullname</th>
                                                <th>Department</th>
                                                <th>Leave Type</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                                <th>Requested At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($leaves as $r): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($r['empID']) ?></td>
                                                            <td><?= htmlspecialchars($r['fullname']) ?></td>
                                                            <td><?= htmlspecialchars($r['department']) ?></td>
                                                            <td><?= htmlspecialchars($r['leave_type_name']) ?></td>
                                                            <td><?= htmlspecialchars($r['from_date']) ?></td>
                                                            <td><?= htmlspecialchars($r['to_date']) ?></td>
                                                            <td><?= htmlspecialchars($r['duration']) ?></td>
                                                            <td><?= htmlspecialchars($r['status']) ?></td>
                                                            <td><?= htmlspecialchars($r['requested_at']) ?></td>
                                                        </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                        <?php elseif ($report_type === 'general-requests'): ?>
                                    <table class="table table-striped table-hover align-middle text-center mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Emp ID</th>
                                                <th>Fullname</th>
                                                <th>Department</th>
                                                <th>Request Type</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Requested At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($generals as $r): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($r['empID']) ?></td>
                                                            <td><?= htmlspecialchars($r['fullname']) ?></td>
                                                            <td><?= htmlspecialchars($r['department']) ?></td>
                                                            <td><?= htmlspecialchars($r['request_type_name']) ?></td>
                                                            <td><?= htmlspecialchars($r['reason']) ?></td>
                                                            <td><?= htmlspecialchars($r['status']) ?></td>
                                                            <td><?= htmlspecialchars($r['requested_at']) ?></td>
                                                        </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                        <?php elseif ($report_type === 'pending-applicants'): ?>
                                    <table class="table table-striped table-hover align-middle text-center mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Applicant ID</th>
                                                <th>Fullname</th>
                                                <th>Department</th>
                                                <th>Job Title</th>
                                                <th>Status</th>
                                                <th>Applied At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingApplicants as $r): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($r['applicantID']) ?></td>
                                                            <td><?= htmlspecialchars($r['fullName']) ?></td>
                                                            <td><?= htmlspecialchars($r['department_name']) ?></td>
                                                            <td><?= htmlspecialchars($r['job_title']) ?></td>
                                                            <td><?= htmlspecialchars($r['status']) ?></td>
                                                            <td><?= htmlspecialchars($r['applied_at']) ?></td>
                                                        </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                        <?php elseif ($report_type === 'archived-applicants'): ?>
                                    <table class="table table-striped table-hover align-middle text-center mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Applicant ID</th>
                                                <th>Fullname</th>
                                                <th>Department</th>
                                                <th>Position Applied</th>
                                                <th>Status</th>
                                                <th>Hired At</th>
                                                <th>Date Applied</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archivedApplicants as $r): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($r['applicantID']) ?></td>
                                                            <td><?= htmlspecialchars($r['fullName']) ?></td>
                                                            <td><?= htmlspecialchars($r['department']) ?></td>
                                                            <td><?= htmlspecialchars($r['position_applied']) ?></td>
                                                            <td><?= htmlspecialchars($r['status']) ?></td>
                                                            <td><?= htmlspecialchars($r['hired_at']) ?></td>
                                                            <td><?= htmlspecialchars($r['date_applied']) ?></td>
                                                        </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

<script>

    const filterForm = document.querySelector('form');
    const deptSelect = document.querySelector('select[name="dept"]');

</script>



</html>
