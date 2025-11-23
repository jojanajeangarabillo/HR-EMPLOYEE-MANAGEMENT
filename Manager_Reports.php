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

// --- AJAX: Return types for selected department ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['dept'])) {
    $deptID = $_GET['dept'];
    $types = [];

    if ($deptID != 'all') {
        $deptRes = $conn->query("SELECT deptName FROM department WHERE deptID = '" . $conn->real_escape_string($deptID) . "'");
        $deptName = ($deptRes && $row = $deptRes->fetch_assoc()) ? $row['deptName'] : '';

        if ($deptName) {
            $typeQuery = $conn->query("
                SELECT DISTINCT type_name 
                FROM employee 
                WHERE department = '" . $conn->real_escape_string($deptName) . "'
                ORDER BY type_name
            ");
            while ($t = $typeQuery->fetch_assoc())
                $types[] = $t['type_name'];
        }
    } else {
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
        while ($t = $typeQuery->fetch_assoc())
            $types[] = $t['type_name'];
    }

    echo json_encode($types);
    exit; // important: stop further execution for AJAX
}

// --- AJAX: Return types for selected department ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['dept'])) {
    $deptID = $_GET['dept'];
    $types = [];

    if ($deptID != 'all') {
        // Get department name
        $deptRes = $conn->query("SELECT deptName FROM department WHERE deptID = '" . $conn->real_escape_string($deptID) . "'");
        $deptName = ($deptRes && $row = $deptRes->fetch_assoc()) ? $row['deptName'] : '';

        if ($deptName) {
            $typeQuery = $conn->query("
                SELECT DISTINCT type_name 
                FROM employee 
                WHERE department = '" . $conn->real_escape_string($deptName) . "'
                ORDER BY type_name
            ");
            while ($t = $typeQuery->fetch_assoc())
                $types[] = $t['type_name'];
        }
    } else {
        // All departments → all types
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
        while ($t = $typeQuery->fetch_assoc())
            $types[] = $t['type_name'];
    }

    echo json_encode($types);
    exit; // stop execution for AJAX
}


// --- Fetch admin name ---
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// --- Get filters ---
$report_type = $_GET['report'] ?? 'department-summary';
$filter_dept = $_GET['dept'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$filter_from = $_GET['from'] ?? null;
$filter_to = $_GET['to'] ?? null;

// --- Fetch departments ---
$deptQuery = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName");
$departments = [];
while ($d = $deptQuery->fetch_assoc())
    $departments[] = $d;

// --- Fetch types based on selected department ---
$types = [];
if ($filter_dept != 'all') {
    $deptName = '';
    foreach ($departments as $d) {
        if ($d['deptID'] == $filter_dept) {
            $deptName = $d['deptName'];
            break;
        }
    }
    if ($deptName != '') {
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee WHERE department = '" . $conn->real_escape_string($deptName) . "' ORDER BY type_name");
    } else {
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
    }
} else {
    $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
}
while ($t = $typeQuery->fetch_assoc())
    $types[] = $t['type_name'];

// --- Fetch report summary ---
$summary = [];
$sql = "SELECT 
            COALESCE(e.department,'Unassigned') AS deptName,
            COALESCE(e.position,'Unassigned') AS position_title,
            COALESCE(e.type_name,'Unassigned') AS type_name,
            COUNT(e.empID) as total
        FROM employee e
        WHERE 1";

if ($filter_dept != 'all') {
    $deptName = '';
    foreach ($departments as $d) {
        if ($d['deptID'] == $filter_dept) {
            $deptName = $d['deptName'];
            break;
        }
    }
    if ($deptName != '') {
        $sql .= " AND COALESCE(e.department,'Unassigned') = '" . $conn->real_escape_string($deptName) . "'";
    }
}

if ($filter_type != 'all')
    $sql .= " AND COALESCE(e.type_name,'Unassigned') = '" . $conn->real_escape_string($filter_type) . "'";
if ($filter_from)
    $sql .= " AND e.hired_at >= '" . $conn->real_escape_string($filter_from) . "'";
if ($filter_to)
    $sql .= " AND e.hired_at <= '" . $conn->real_escape_string($filter_to) . "'";

$sql .= " GROUP BY deptName, position_title, type_name ORDER BY deptName, position_title";

$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $dept = $row['deptName'];
    $pos = $row['position_title'];
    $type = $row['type_name'];
    $total = $row['total'];
    if (!isset($summary[$dept]))
        $summary[$dept] = [];
    if (!isset($summary[$dept][$pos]))
        $summary[$dept][$pos] = [];
    $summary[$dept][$pos][$type] = $total;
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
    $pdf->Cell(0, 7, 'EMPLOYEE REPORT', 0, 1, 'C');

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


    // --- Table Header ---
    $colWidths = [10, 70, 100, 60, 30];
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(50, 120, 200); // Blue header
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
    $pdf->Cell($colWidths[1], 8, 'Department', 1, 0, 'C', true);
    $pdf->Cell($colWidths[2], 8, 'Position', 1, 0, 'C', true);
    $pdf->Cell($colWidths[3], 8, 'Employment Type', 1, 0, 'C', true);
    $pdf->Cell($colWidths[4], 8, 'Total', 1, 1, 'C', true);

    // --- Table Data ---
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false; // alternating row color
    $lineHeight = 6;
    $pageHeightLimit = 190;
    $counter = 1;

    foreach ($summary as $dept => $positions) {
        foreach ($positions as $pos => $types) {
            foreach ($types as $type => $total) {
                if ($pdf->GetY() + $lineHeight * 2 > $pageHeightLimit) {
                    $pdf->AddPage();
                    // repeat table header
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->SetFillColor(50, 120, 200);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->Cell($colWidths[0], 8, 'No.', 1, 0, 'C', true);
                    $pdf->Cell($colWidths[1], 8, 'Department', 1, 0, 'C', true);
                    $pdf->Cell($colWidths[2], 8, 'Position', 1, 0, 'C', true);
                    $pdf->Cell($colWidths[3], 8, 'Employment Type', 1, 0, 'C', true);
                    $pdf->Cell($colWidths[4], 8, 'Total', 1, 1, 'C', true);
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->SetTextColor(0, 0, 0);
                }
                // alternating row colors
                $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
                $pdf->Cell($colWidths[0], $lineHeight, $counter, 1, 0, 'C', true);
                $pdf->Cell($colWidths[1], $lineHeight, $dept, 1, 0, 'L', true);
                $pdf->Cell($colWidths[2], $lineHeight, $pos, 1, 0, 'L', true);
                $pdf->Cell($colWidths[3], $lineHeight, $type, 1, 0, 'L', true);
                $pdf->Cell($colWidths[4], $lineHeight, $total, 1, 1, 'C', true);
                $fill = !$fill;
                $counter++;
            }
        }
    }

    // --- Grand Total ---
    $totalSQL = "SELECT COUNT(empID) AS total_employees FROM employee WHERE 1";
    if ($filter_dept != 'all')
        $totalSQL .= " AND COALESCE(department,'Unassigned') = '" . $conn->real_escape_string($deptName) . "'";
    if ($filter_type != 'all')
        $totalSQL .= " AND COALESCE(type_name,'Unassigned') = '" . $conn->real_escape_string($filter_type) . "'";
    if ($filter_from)
        $totalSQL .= " AND hired_at >= '" . $conn->real_escape_string($filter_from) . "'";
    if ($filter_to)
        $totalSQL .= " AND hired_at <= '" . $conn->real_escape_string($filter_to) . "'";

    $totalRes = $conn->query($totalSQL);
    $grandTotal = ($totalRes && $row = $totalRes->fetch_assoc()) ? $row['total_employees'] : 0;

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(array_sum($colWidths) - $colWidths[4], 8, 'TOTAL EMPLOYEES', 1, 0, 'R', true);
    $pdf->Cell($colWidths[4], 8, $grandTotal, 1, 1, 'C', true);

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
                                <option value="department-summary" <?= $report_type == 'department-summary' ? 'selected' : '' ?>>Department Summary</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Department</label>
                            <select class="form-select" name="dept">
                                <option value="all">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['deptID'] ?>" <?= ($filter_dept == $d['deptID']) ? 'selected' : '' ?>>
                                        <?= $d['deptName'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Employment Type</label>
                            <select class="form-select" name="type" id="typeSelect">
                                <option value="all">All Types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t ?>" <?= ($filter_type == $t) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">From</label>
                            <input type="date" class="form-control" name="from" value="<?= $filter_from ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">To</label>
                            <input type="date" class="form-control" name="to" value="<?= $filter_to ?>">
                        </div>

                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i>
                                Filter</button>
                            <a href="?<?= http_build_query($_GET) ?>&export=pdf" class="btn btn-danger"><i
                                    class="fa-solid fa-file-pdf"></i> Export PDF</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Table Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle text-center mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Employment Type</th>
                                    <th>Total Employees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary as $dept => $positions): ?>
                                    <?php foreach ($positions as $pos => $types): ?>
                                        <?php foreach ($types as $type => $total): ?>
                                            <tr>
                                                <td><?= $dept ?></td>
                                                <td><?= $pos ?></td>
                                                <td><?= $type ?></td>
                                                <td><?= $total ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

<script>



    const filterForm = document.querySelector('form');
    const deptSelect = document.querySelector('select[name="dept"]');
    const typeSelect = document.querySelector('select[name="type"]');

    deptSelect.addEventListener('change', () => {
        const deptID = deptSelect.value;

        // Fetch types dynamically
        fetch(`?ajax=1&dept=${deptID}`)
            .then(res => res.json())
            .then(types => {
                // Reset type dropdown
                typeSelect.innerHTML = '<option value="all">All Types</option>';

                types.forEach(t => {
                    typeSelect.innerHTML += `<option value="${t}">${t}</option>`;
                });

                // Optional: reset type filter to "all" when department changes
                typeSelect.value = 'all';
            });
    });

</script>



</html>