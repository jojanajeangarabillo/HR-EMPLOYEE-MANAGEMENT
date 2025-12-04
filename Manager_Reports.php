<?php
session_start();
require 'admin/db.connect.php';
require('fpdf/fpdf.php');

$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null;

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
        "Shift Scheduling"  => "Manager_Scheduling.php",
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
        "Shift Scheduling"  => "Manager_Scheduling.php",
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
    "Shift Scheduling" => "fa-clock-rotate-left",
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
$search_query = $_GET['search'] ?? '';

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
$allEmployees = [];

$deptNameSel = null;
if ($filter_dept != 'all') {
    foreach ($departments as $d) {
        if ($d['deptID'] == $filter_dept) {
            $deptNameSel = $d['deptName'];
            break;
        }
    }
}

// Add new report type: All Employees
if ($report_type === 'all-employees' && ($role === 'HR Director' || $role === 'HR Manager' || $role === 'HR Officer')) {
    $whereClauses = [];
    $params = [];
    $types = '';
    
    if ($deptNameSel) {
        $whereClauses[] = "e.department = ?";
        $params[] = $deptNameSel;
        $types .= 's';
    }
    
    if (!empty($search_query)) {
        $whereClauses[] = "(e.fullname LIKE ? OR e.empID LIKE ? OR e.email_address LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $types .= 'sss';
    }
    
    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
    
    // FIXED: Removed status column from SELECT
    $sql = "SELECT e.empID, e.fullname, e.department, e.position, e.email_address, e.type_name, 
                   e.hired_at, e.date_of_birth, e.contact_number, e.gender
            FROM employee e 
            $whereSQL 
            ORDER BY e.fullname ASC";
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $allEmployees[] = $row;
    $stmt->close();
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

// --- Enhanced PDF Export ---
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    class PDF extends FPDF {
        // Header
        function Header() {
            // Hospital header with gradient effect
            $this->SetFillColor(30, 58, 138); // Dark blue
            $this->Rect(0, 0, 297, 25, 'F');
            
            // Logo
            $this->Image('C:/xampp/htdocs/HR-EMPLOYEE-MANAGEMENT/images/hospitallogo.png', 10, 3, 20);
            
            // Title
            $this->SetFont('Arial', 'B', 20);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 10, 'BLUE PULSE EMPLOYEE MANAGEMENT SYSTEM', 0, 1, 'C');
            
            // Subtitle
            $this->SetFont('Arial', 'I', 12);
            $this->Cell(0, 5, 'Human Resources Department', 0, 1, 'C');
            
            // Light blue separator
            $this->SetFillColor(59, 130, 246); // Light blue
            $this->Rect(0, 25, 297, 3, 'F');
            
            $this->Ln(10);
        }
        
        // Footer
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated on ' . date('F d, Y H:i:s'), 0, 0, 'C');
        }
        
        // Chapter title
        function ChapterTitle($label) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(30, 58, 138);
            $this->Cell(0, 10, $label, 0, 1, 'L');
            $this->Ln(2);
        }
        
        // Report info
        function ReportInfo($info) {
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(64, 64, 64);
            foreach ($info as $key => $value) {
                $this->Cell(40, 6, $key . ':', 0, 0);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(0, 6, $value, 0, 1);
                $this->SetFont('Arial', '', 10);
            }
            $this->Ln(5);
        }
        
        // Enhanced table header
        function EnhancedTableHeader($header, $widths) {
            $this->SetFillColor(30, 58, 138);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 10);
            $this->SetDrawColor(200, 200, 200);
            $this->SetLineWidth(.3);
            
            for ($i = 0; $i < count($header); $i++) {
                $this->Cell($widths[$i], 8, $header[$i], 1, 0, 'C', true);
            }
            $this->Ln();
            
            $this->SetFillColor(224, 235, 255);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', '', 9);
        }
    }

    $pdf = new PDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    // Report title based on type
    $reportTitles = [
        'leaves' => 'LEAVE REQUESTS REPORT',
        'general-requests' => 'GENERAL REQUESTS REPORT',
        'pending-applicants' => 'PENDING APPLICANTS REPORT',
        'archived-applicants' => 'ARCHIVED APPLICANTS REPORT',
        'all-employees' => 'EMPLOYEE DIRECTORY REPORT'
    ];
    
    $pdf->ChapterTitle($reportTitles[$report_type] ?? 'REPORT');
    
    // Report information
    $info = [
        'Generated By' => $managername,
        'Position' => $role,
        'Date Generated' => date('F d, Y H:i:s'),
        'Period' => date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year))
    ];
    
    if ($filter_dept != 'all' && $deptNameSel) {
        $info['Department'] = $deptNameSel;
    }
    
    $pdf->ReportInfo($info);
    
    // Data tables
    if ($report_type === 'leaves') {
        $header = ['No.', 'Employee ID', 'Full Name', 'Department', 'Leave Type', 'From Date', 'To Date', 'Days', 'Status'];
        $widths = [10, 25, 50, 40, 35, 30, 30, 15, 25];
        $pdf->EnhancedTableHeader($header, $widths);
        
        $fill = false;
        $counter = 1;
        foreach ($leaves as $row) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->EnhancedTableHeader($header, $widths);
            }
            
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($widths[0], 7, $counter, 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, $row['empID'], 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, $row['fullname'], 1, 0, 'L', true);
            $pdf->Cell($widths[3], 7, $row['department'], 1, 0, 'L', true);
            $pdf->Cell($widths[4], 7, $row['leave_type_name'], 1, 0, 'L', true);
            $pdf->Cell($widths[5], 7, $row['from_date'], 1, 0, 'C', true);
            $pdf->Cell($widths[6], 7, $row['to_date'], 1, 0, 'C', true);
            $pdf->Cell($widths[7], 7, $row['duration'], 1, 0, 'C', true);
            
            // Color-coded status
            $pdf->SetTextColor(0, 0, 0);
            if ($row['status'] === 'Approved') {
                $pdf->SetTextColor(0, 128, 0);
            } elseif ($row['status'] === 'Rejected') {
                $pdf->SetTextColor(255, 0, 0);
            } elseif ($row['status'] === 'Pending') {
                $pdf->SetTextColor(255, 165, 0);
            }
            $pdf->Cell($widths[8], 7, $row['status'], 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            
            $fill = !$fill;
            $counter++;
        }
        
    } elseif ($report_type === 'general-requests') {
        $header = ['No.', 'Request ID', 'Employee ID', 'Full Name', 'Department', 'Request Type', 'Status'];
        $widths = [10, 25, 25, 60, 45, 50, 25];
        $pdf->EnhancedTableHeader($header, $widths);
        
        $fill = false;
        $counter = 1;
        foreach ($generals as $row) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->EnhancedTableHeader($header, $widths);
            }
            
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($widths[0], 7, $counter, 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, $row['request_id'], 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, $row['empID'], 1, 0, 'C', true);
            $pdf->Cell($widths[3], 7, $row['fullname'], 1, 0, 'L', true);
            $pdf->Cell($widths[4], 7, $row['department'], 1, 0, 'L', true);
            $pdf->Cell($widths[5], 7, $row['request_type_name'], 1, 0, 'L', true);
            
            // Color-coded status
            $pdf->SetTextColor(0, 0, 0);
            if ($row['status'] === 'Approved') {
                $pdf->SetTextColor(0, 128, 0);
            } elseif ($row['status'] === 'Rejected') {
                $pdf->SetTextColor(255, 0, 0);
            } elseif ($row['status'] === 'Pending') {
                $pdf->SetTextColor(255, 165, 0);
            }
            $pdf->Cell($widths[6], 7, $row['status'], 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            
            $fill = !$fill;
            $counter++;
        }
        
    } elseif ($report_type === 'all-employees') {
        // MODIFIED: Removed status column
        $header = ['No.', 'Employee ID', 'Full Name', 'Department', 'Position', 'Email', 'Employment Type'];
        $widths = [10, 25, 60, 40, 45, 60, 30];
        $pdf->EnhancedTableHeader($header, $widths);
        
        $fill = false;
        $counter = 1;
        foreach ($allEmployees as $row) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->EnhancedTableHeader($header, $widths);
            }
            
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($widths[0], 7, $counter, 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, $row['empID'], 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, $row['fullname'], 1, 0, 'L', true);
            $pdf->Cell($widths[3], 7, $row['department'], 1, 0, 'L', true);
            $pdf->Cell($widths[4], 7, $row['position'], 1, 0, 'L', true);
            $pdf->Cell($widths[5], 7, $row['email_address'], 1, 0, 'L', true);
            $pdf->Cell($widths[6], 7, $row['type_name'], 1, 1, 'L', true);
            
            $fill = !$fill;
            $counter++;
        }
        
    } elseif ($report_type === 'pending-applicants') {
        $header = ['No.', 'Applicant ID', 'Full Name', 'Department', 'Job Title', 'Applied Date'];
        $widths = [10, 30, 70, 50, 50, 40];
        $pdf->EnhancedTableHeader($header, $widths);
        
        $fill = false;
        $counter = 1;
        foreach ($pendingApplicants as $row) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->EnhancedTableHeader($header, $widths);
            }
            
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($widths[0], 7, $counter, 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, $row['applicantID'], 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, $row['fullName'], 1, 0, 'L', true);
            $pdf->Cell($widths[3], 7, $row['department_name'], 1, 0, 'L', true);
            $pdf->Cell($widths[4], 7, $row['job_title'], 1, 0, 'L', true);
            $pdf->Cell($widths[5], 7, date('Y-m-d', strtotime($row['applied_at'])), 1, 1, 'C', true);
            
            $fill = !$fill;
            $counter++;
        }
        
    } elseif ($report_type === 'archived-applicants') {
        $header = ['No.', 'Applicant ID', 'Full Name', 'Department', 'Position', 'Date Applied', 'Hired Date'];
        $widths = [10, 30, 60, 45, 50, 40, 40];
        $pdf->EnhancedTableHeader($header, $widths);
        
        $fill = false;
        $counter = 1;
        foreach ($archivedApplicants as $row) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->EnhancedTableHeader($header, $widths);
            }
            
            $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $pdf->Cell($widths[0], 7, $counter, 1, 0, 'C', true);
            $pdf->Cell($widths[1], 7, $row['applicantID'], 1, 0, 'C', true);
            $pdf->Cell($widths[2], 7, $row['fullName'], 1, 0, 'L', true);
            $pdf->Cell($widths[3], 7, $row['department'], 1, 0, 'L', true);
            $pdf->Cell($widths[4], 7, $row['position_applied'], 1, 0, 'L', true);
            $pdf->Cell($widths[5], 7, date('Y-m-d', strtotime($row['date_applied'])), 1, 0, 'C', true);
            $pdf->Cell($widths[6], 7, $row['hired_at'] ? date('Y-m-d', strtotime($row['hired_at'])) : 'N/A', 1, 1, 'C', true);
            
            $fill = !$fill;
            $counter++;
        }
    }

    // Summary statistics
    $pdf->AddPage();
    $pdf->ChapterTitle('REPORT SUMMARY');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(30, 58, 138);
    $pdf->Cell(0, 10, 'Statistics:', 0, 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(64, 64, 64);
    
    $totalRecords = 0;
    $statusCounts = [];
    
    switch($report_type) {
        case 'leaves':
            $totalRecords = count($leaves);
            foreach ($leaves as $r) {
                $statusCounts[$r['status']] = ($statusCounts[$r['status']] ?? 0) + 1;
            }
            break;
        case 'general-requests':
            $totalRecords = count($generals);
            foreach ($generals as $r) {
                $statusCounts[$r['status']] = ($statusCounts[$r['status']] ?? 0) + 1;
            }
            break;
        case 'all-employees':
            $totalRecords = count($allEmployees);
            // No status counts for all-employees report
            break;
        case 'pending-applicants':
            $totalRecords = count($pendingApplicants);
            break;
        case 'archived-applicants':
            $totalRecords = count($archivedApplicants);
            break;
    }
    
    $pdf->Cell(40, 6, 'Total Records:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, number_format($totalRecords), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    foreach ($statusCounts as $status => $count) {
        $pdf->Cell(40, 6, "$status:", 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, number_format($count) . " (" . round(($count/$totalRecords)*100, 1) . "%)", 0, 1);
        $pdf->SetFont('Arial', '', 10);
    }
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 6, '*** End of Report ***', 0, 1, 'C');

    $pdf->Output('D', str_replace('-', '_', $report_type) . '_report_' . date('Y_m_d') . '.pdf');
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
        :root {
            --primary-blue: #1E3A8A;
            --secondary-blue: #3B82F6;
            --light-blue: #EFF6FF;
            --dark-blue: #1E40AF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar fixes */
        .sidebar {
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border: 4px solid var(--primary-blue);
        }

        .sidebar-profile-img:hover {
            transform: scale(1.05);
        }

        .sidebar-name {
            text-align: center;
            color: white;
            padding: 10px;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 220px;
            width: calc(100% - 220px);
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        .page-header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header .subtitle {
            opacity: 0.9;
            font-size: 14px;
            margin-top: 8px;
            margin-left: 5px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border-left: 5px solid var(--primary-blue);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .stat-card h6 {
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }

        .stat-card .stat-sub {
            color: #9ca3af;
            font-size: 13px;
            font-weight: 500;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        /* Report Table */
        .report-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 40px;
        }

        .report-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-header h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Table Styling */
        .table-custom {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            background: #f8fafc;
            color: #4b5563;
            font-weight: 600;
            padding: 16px 20px;
            border: none;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
        }

        .table-custom tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .table-custom tbody tr {
            transition: all 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f9fafb;
        }

        /* Status Badges */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .badge-approved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .badge-rejected {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .badge-active {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .badge-inactive {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #9ca3af;
        }

        /* Export Button */
        .btn-export {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: #6b7280;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            color: #9ca3af;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .report-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
                <li><a href="<?php echo $link; ?>"><i class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa-solid fa-chart-column"></i> Reports Dashboard</h1>
            <div class="subtitle">Generate and analyze reports for better decision making</div>
        </div>

        <!-- Statistics -->
        <?php 
            $totalRecords = 0;
            switch($report_type) {
                case 'leaves': $totalRecords = count($leaves); break;
                case 'general-requests': $totalRecords = count($generals); break;
                case 'all-employees': $totalRecords = count($allEmployees); break;
                case 'pending-applicants': $totalRecords = count($pendingApplicants); break;
                case 'archived-applicants': $totalRecords = count($archivedApplicants); break;
            }
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h6>Total Records</h6>
                <div class="stat-number"><?= number_format($totalRecords) ?></div>
                <div class="stat-sub">Current filter results</div>
            </div>
            <div class="stat-card">
                <h6>Report Type</h6>
                <div class="stat-number">
                    <?= ucwords(str_replace('-', ' ', $report_type)) ?>
                </div>
                <div class="stat-sub">Active report</div>
            </div>
            <div class="stat-card">
                <h6>Period</h6>
                <div class="stat-number">
                    <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)) ?>
                </div>
                <div class="stat-sub">Selected month & year</div>
            </div>
            <div class="stat-card">
                <h6>Department</h6>
                <div class="stat-number">
                    <?= $filter_dept == 'all' ? 'All' : ($deptNameSel ?? 'All') ?>
                </div>
                <div class="stat-sub">Filtered department</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Report Type</label>
                    <select class="form-select" name="report" onchange="this.form.submit()">
                        <?php if ($role === 'Recruitment Manager'): ?>
                            <option value="pending-applicants" <?= $report_type == 'pending-applicants' ? 'selected' : '' ?>>Pending Applicants</option>
                            <option value="archived-applicants" <?= $report_type == 'archived-applicants' ? 'selected' : '' ?>>Archived Applicants</option>
                        <?php else: ?>
                            <option value="leaves" <?= $report_type == 'leaves' ? 'selected' : '' ?>>Leaves Report</option>
                            <option value="general-requests" <?= $report_type == 'general-requests' ? 'selected' : '' ?>>General Requests</option>
                            <option value="all-employees" <?= $report_type == 'all-employees' ? 'selected' : '' ?>>All Employees</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Department</label>
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
                    <label class="form-label fw-semibold">Month</label>
                    <select class="form-select" name="month" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Year</label>
                    <select class="form-select" name="year" onchange="this.form.submit()">
                        <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-2"></i>Apply
                    </button>
                </div>
            </form>

            <?php if ($report_type === 'all-employees'): ?>
                <form method="GET" class="mt-3">
                    <input type="hidden" name="report" value="all-employees">
                    <input type="hidden" name="dept" value="<?= $filter_dept ?>">
                    <input type="hidden" name="month" value="<?= $filter_month ?>">
                    <input type="hidden" name="year" value="<?= $filter_year ?>">
                    
                    <div class="search-box">
                        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                               placeholder="Search employees by name, ID, or email...">
                        <i class="fa-solid fa-search"></i>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa-solid fa-search me-2"></i>Search
                        </button>
                        <?php if ($search_query): ?>
                            <a href="?report=all-employees&dept=<?= $filter_dept ?>&month=<?= $filter_month ?>&year=<?= $filter_year ?>" 
                               class="btn btn-outline-secondary">
                                Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Report Table -->
        <div class="report-container">
            <div class="report-header">
                <h5>
                    <i class="fa-solid fa-table"></i>
                    <?= ucwords(str_replace('-', ' ', $report_type)) ?> Report
                </h5>
                <div class="report-controls">
                    <span class="text-white opacity-75">
                        Showing <?= $totalRecords ?> records
                    </span>
                    <a href="?<?= http_build_query($_GET) ?>&export=pdf" class="btn-export">
                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <?php if ($report_type === 'leaves'): ?>
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($leaves)): ?>
                                <?php foreach ($leaves as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($r['empID']) ?></td>
                                        <td><?= htmlspecialchars($r['fullname']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($r['department']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($r['leave_type_name']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($r['from_date']) ?></td>
                                        <td><?= htmlspecialchars($r['to_date']) ?></td>
                                        <td class="text-center fw-bold"><?= htmlspecialchars($r['duration']) ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                if ($r['status'] === 'Pending') $statusClass = 'badge-pending';
                                                elseif ($r['status'] === 'Approved') $statusClass = 'badge-approved';
                                                elseif ($r['status'] === 'Rejected') $statusClass = 'badge-rejected';
                                            ?>
                                            <span class="badge-status <?= $statusClass ?>">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($r['requested_at']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-calendar-times"></i>
                                            <h4>No Leave Records</h4>
                                            <p>No leave requests found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type === 'general-requests'): ?>
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Request Type</th>
                                <th>Status</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($generals)): ?>
                                <?php foreach ($generals as $r): ?>
                                    <tr>
                                        <td class="fw-semibold">#<?= htmlspecialchars($r['request_id']) ?></td>
                                        <td><?= htmlspecialchars($r['empID']) ?></td>
                                        <td><?= htmlspecialchars($r['fullname']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($r['department']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-white"><?= htmlspecialchars($r['request_type_name']) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                if ($r['status'] === 'Pending') $statusClass = 'badge-pending';
                                                elseif ($r['status'] === 'Approved') $statusClass = 'badge-approved';
                                                elseif ($r['status'] === 'Rejected') $statusClass = 'badge-rejected';
                                            ?>
                                            <span class="badge-status <?= $statusClass ?>">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($r['requested_at']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-inbox"></i>
                                            <h4>No General Requests</h4>
                                            <p>No general requests found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type === 'all-employees'): ?>
                    <!-- MODIFIED: Removed status column -->
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Email</th>
                                <th>Employment Type</th>
                                <th>Date Hired</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($allEmployees)): ?>
                                <?php foreach ($allEmployees as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($r['empID']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($r['fullname']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($r['gender'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($r['department']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($r['position']) ?></td>
                                        <td>
                                            <small><?= htmlspecialchars($r['email_address']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white"><?= htmlspecialchars($r['type_name']) ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($r['hired_at'] ?? 'Not Hired') ?></small>
                                        </td>
                                        
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-users-slash"></i>
                                            <h4>No Employees Found</h4>
                                            <p>No employees found matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type === 'pending-applicants'): ?>
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Applicant ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Applied At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendingApplicants)): ?>
                                <?php foreach ($pendingApplicants as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($r['applicantID']) ?></td>
                                        <td><?= htmlspecialchars($r['fullName']) ?></td>
                                        <td><?= htmlspecialchars($r['department_name']) ?></td>
                                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                                        <td>
                                            <span class="badge-status badge-pending">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($r['applied_at']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-user-clock"></i>
                                            <h4>No Pending Applicants</h4>
                                            <p>No pending applicants found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type === 'archived-applicants'): ?>
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Applicant ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Position Applied</th>
                                <th>Status</th>
                                <th>Hired At</th>
                                <th>Date Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($archivedApplicants)): ?>
                                <?php foreach ($archivedApplicants as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($r['applicantID']) ?></td>
                                        <td><?= htmlspecialchars($r['fullName']) ?></td>
                                        <td><?= htmlspecialchars($r['department']) ?></td>
                                        <td><?= htmlspecialchars($r['position_applied']) ?></td>
                                        <td>
                                            <span class="badge-status badge-inactive">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($r['hired_at'] ?? 'N/A') ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($r['date_applied']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-box-archive"></i>
                                            <h4>No Archived Applicants</h4>
                                            <p>No archived applicants found for the selected period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>