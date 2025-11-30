<?php
session_start();
require 'admin/db.connect.php';
require('fpdf/fpdf.php');

// --- AJAX: Return types for selected department ---
if(isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['dept'])){
    $deptID = $_GET['dept'];
    $types = [];

    if($deptID != 'all'){
        $deptRes = $conn->query("SELECT deptName FROM department WHERE deptID = '".$conn->real_escape_string($deptID)."'");
        $deptName = ($deptRes && $row = $deptRes->fetch_assoc()) ? $row['deptName'] : '';
        
        if($deptName){
            $typeQuery = $conn->query("
                SELECT DISTINCT type_name 
                FROM employee 
                WHERE department = '".$conn->real_escape_string($deptName)."'
                ORDER BY type_name
            ");
            while($t = $typeQuery->fetch_assoc()) $types[] = $t['type_name'];
        }
    } else {
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
        while($t = $typeQuery->fetch_assoc()) $types[] = $t['type_name'];
    }

    echo json_encode($types);
    exit; // important: stop further execution for AJAX
}

// Admin name
$adminname = $_SESSION['fullname'] ?? "Human Resource (HR) Admin";
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
    $adminname = $_SESSION['fullname'] ?? "Employee";
    $profile_picture = "uploads/employees/default.png";
}

// --- Get filters ---
$report_type = $_GET['report'] ?? 'department-summary';
$filter_dept = $_GET['dept'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$filter_from = $_GET['from'] ?? null;
$filter_to = $_GET['to'] ?? null;

// --- Fetch departments ---
$deptQuery = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName");
$departments = [];
while($d = $deptQuery->fetch_assoc()) $departments[] = $d;

// --- Fetch types based on selected department ---
$types = [];
if($filter_dept != 'all'){
    $deptName = '';
    foreach($departments as $d){
        if($d['deptID'] == $filter_dept){
            $deptName = $d['deptName'];
            break;
        }
    }
    if($deptName != ''){
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee WHERE department = '".$conn->real_escape_string($deptName)."' ORDER BY type_name");
    } else {
        $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
    }
} else {
    $typeQuery = $conn->query("SELECT DISTINCT type_name FROM employee ORDER BY type_name");
}
while($t = $typeQuery->fetch_assoc()) $types[] = $t['type_name'];

// --- Fetch report summary ---
$summary = [];
$sql = "SELECT 
            COALESCE(e.department,'Unassigned') AS deptName,
            COALESCE(e.position,'Unassigned') AS position_title,
            COALESCE(e.type_name,'Unassigned') AS type_name,
            COUNT(e.empID) as total
        FROM employee e
        WHERE 1";

if($filter_dept != 'all') {
    $deptName = '';
    foreach($departments as $d){
        if($d['deptID'] == $filter_dept){
            $deptName = $d['deptName'];
            break;
        }
    }
    if($deptName != ''){
        $sql .= " AND COALESCE(e.department,'Unassigned') = '".$conn->real_escape_string($deptName)."'";
    }
}

if($filter_type != 'all') $sql .= " AND COALESCE(e.type_name,'Unassigned') = '".$conn->real_escape_string($filter_type)."'";
if($filter_from) $sql .= " AND e.hired_at >= '".$conn->real_escape_string($filter_from)."'";
if($filter_to)   $sql .= " AND e.hired_at <= '".$conn->real_escape_string($filter_to)."'";

$sql .= " GROUP BY deptName, position_title, type_name ORDER BY deptName, position_title";

$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $dept = $row['deptName'];
    $pos = $row['position_title'];
    $type = $row['type_name'];
    $total = $row['total'];
    if(!isset($summary[$dept])) $summary[$dept] = [];
    if(!isset($summary[$dept][$pos])) $summary[$dept][$pos] = [];
    $summary[$dept][$pos][$type] = $total;
}

// --- Get total counts for statistics ---
$totalEmployees = $conn->query("SELECT COUNT(*) as total FROM employee")->fetch_assoc()['total'];
$totalDepartments = $conn->query("SELECT COUNT(*) as total FROM department")->fetch_assoc()['total'];
$totalPositions = $conn->query("SELECT COUNT(DISTINCT position) as total FROM employee")->fetch_assoc()['total'];

// --- PDF Export ---
if(isset($_GET['export']) && $_GET['export'] == 'pdf'){
    $pdf = new FPDF('L','mm','A4');
    $pdf->AddPage();

    // --- Header ---
    $bannerHeight = 45;
    $pdf->SetFillColor(50,120,200);
    $pdf->Rect(0,0,297,$bannerHeight,'F');

    $pdf->Image('C:/xampp/htdocs/HR-EMPLOYEE-MANAGEMENT/images/hospitallogo.png', 10, 5, 25);

    $pdf->SetFont('Arial','B',18);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetXY(0,8);
    $pdf->Cell(0,10,'HOSPITAL REPORT',0,1,'C');

    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,7,'EMPLOYEE REPORT',0,1,'C');

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,'Report Created: '.date('F d, Y H:i:s'),0,1,'C');

    // Filters / Date Range
    $dateRange = '';
    if($filter_from && $filter_to){
        $dateRange = date('F d, Y', strtotime($filter_from)) . ' to ' . date('F d, Y', strtotime($filter_to));
    } elseif($filter_from){
        $dateRange = 'From ' . date('F d, Y', strtotime($filter_from));
    } elseif($filter_to){
        $dateRange = 'Up to ' . date('F d, Y', strtotime($filter_to));
    } else {
        $dateRange = 'All Dates';
    }

    $pdf->Cell(0,6,"Report Type: " . strtoupper(str_replace('-', ' ', $report_type)),0,1,'C');
    $pdf->Cell(0,6,"Date Range: $dateRange",0,1,'C');

    // Draw a line to separate header from table
    $pdf->SetDrawColor(50,120,200);
    $pdf->SetLineWidth(0.7);
    $pdf->Line(10, $bannerHeight, 287, $bannerHeight);
    $pdf->Ln(4);

    // --- Table Header ---
    $colWidths = [10, 70, 100, 60, 30];
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(50, 120, 200);
    $pdf->SetTextColor(255,255,255);
    $pdf->Cell($colWidths[0],8,'No.',1,0,'C',true);
    $pdf->Cell($colWidths[1],8,'Department',1,0,'C',true);
    $pdf->Cell($colWidths[2],8,'Position',1,0,'C',true);
    $pdf->Cell($colWidths[3],8,'Employment Type',1,0,'C',true);
    $pdf->Cell($colWidths[4],8,'Total',1,1,'C',true);

    // --- Table Data ---
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $fill = false;
    $lineHeight = 6;
    $pageHeightLimit = 190;
    $counter = 1;

    foreach($summary as $dept => $positions){
        foreach($positions as $pos => $types){
            foreach($types as $type => $total){
                if($pdf->GetY() + $lineHeight*2 > $pageHeightLimit){
                    $pdf->AddPage();
                    $pdf->SetFont('Arial','B',10);
                    $pdf->SetFillColor(50,120,200);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->Cell($colWidths[0],8,'No.',1,0,'C',true);
                    $pdf->Cell($colWidths[1],8,'Department',1,0,'C',true);
                    $pdf->Cell($colWidths[2],8,'Position',1,0,'C',true);
                    $pdf->Cell($colWidths[3],8,'Employment Type',1,0,'C',true);
                    $pdf->Cell($colWidths[4],8,'Total',1,1,'C',true);
                    $pdf->SetFont('Arial','',10);
                    $pdf->SetTextColor(0,0,0);
                }
                $pdf->SetFillColor($fill ? 230 : 255, $fill ? 230 : 255, $fill ? 230 : 255);
                $pdf->Cell($colWidths[0],$lineHeight,$counter,1,0,'C',true);
                $pdf->Cell($colWidths[1],$lineHeight,$dept,1,0,'L',true);
                $pdf->Cell($colWidths[2],$lineHeight,$pos,1,0,'L',true);
                $pdf->Cell($colWidths[3],$lineHeight,$type,1,0,'L',true);
                $pdf->Cell($colWidths[4],$lineHeight,$total,1,1,'C',true);
                $fill = !$fill;
                $counter++;
            }
        }
    }

    // --- Grand Total ---
    $totalSQL = "SELECT COUNT(empID) AS total_employees FROM employee WHERE 1";
    if($filter_dept != 'all') $totalSQL .= " AND COALESCE(department,'Unassigned') = '".$conn->real_escape_string($deptName)."'";
    if($filter_type != 'all') $totalSQL .= " AND COALESCE(type_name,'Unassigned') = '".$conn->real_escape_string($filter_type)."'";
    if($filter_from) $totalSQL .= " AND hired_at >= '".$conn->real_escape_string($filter_from)."'";
    if($filter_to) $totalSQL .= " AND hired_at <= '".$conn->real_escape_string($filter_to)."'";

    $totalRes = $conn->query($totalSQL);
    $grandTotal = ($totalRes && $row = $totalRes->fetch_assoc()) ? $row['total_employees'] : 0;

    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(array_sum($colWidths)-$colWidths[4],8,'TOTAL EMPLOYEES',1,0,'R',true);
    $pdf->Cell($colWidths[4],8,$grandTotal,1,1,'C',true);

    // --- Footer ---
    $pdf->SetY(-15);
    $pdf->SetFont('Arial','I',8);
    $pdf->Cell(0,10,'Generated on: '.date('F d, Y H:i').' | HR Employee Management System | Page '.$pdf->PageNo(),0,0,'C');

    $pdf->Output('D', $report_type.'.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Reports</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #1E3A8A;
    --primary-light: #3B82F6;
    --primary-dark: #1E40AF;
    --secondary: #64748B;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --info: #06B6D4;
    --light: #F8FAFC;
    --dark: #1E293B;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --smooth-shadow: 0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
}

body { 
    font-family: 'Poppins', sans-serif; 
    margin: 0; 
    display: flex; 
    background: linear-gradient(135deg, #f1f5fc 0%, #e2e8f0 100%);
    color: var(--dark);
    min-height: 100vh;
    font-weight: 400;
    line-height: 1.6;
}

.main-content {
    padding: 30px;
    margin-left: 220px;
    flex: 1;
    display: flex;
    flex-direction: column;
    width: calc(100% - 220px);
}

.main-content-header { 
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.main-content-header h1 { 
    color: var(--primary);
    font-weight: 700;
    margin: 0;
    font-size: 2.2rem;
    position: relative;
}

.main-content-header h1::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 2px;
}

.welcome-text {
    color: var(--secondary);
    font-size: 1.1rem;
    margin-top: 10px;
    font-weight: 400;
}

/* Statistics Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    box-shadow: var(--card-shadow);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--hover-shadow);
}

.stat-card.employees::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.stat-card.departments::before { background: linear-gradient(90deg, var(--success), #10B981); }
.stat-card.positions::before { background: linear-gradient(90deg, var(--info), #06B6D4); }

.stat-card-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-info h3 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark);
    line-height: 1;
}

.stat-info p {
    color: var(--secondary);
    margin: 8px 0 0 0;
    font-weight: 500;
    font-size: 1rem;
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.stat-card.employees .stat-icon { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
.stat-card.departments .stat-icon { background: linear-gradient(135deg, var(--success), #10B981); }
.stat-card.positions .stat-icon { background: linear-gradient(135deg, var(--info), #06B6D4); }

/* Filters Card */
.filters-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--smooth-shadow);
    border: 1px solid var(--gray-200);
    margin-bottom: 30px;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.filters-title {
    color: var(--primary);
    font-weight: 600;
    margin: 0;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.filters-title i {
    color: var(--primary-light);
}

/* Form Elements */
.form-control, .form-select {
    border-radius: 12px;
    border: 1px solid var(--gray-300);
    padding: 12px 16px;
    font-size: 1rem;
    font-weight: 400;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.form-label {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 8px;
    font-size: 0.95rem;
}

/* Buttons */
.btn {
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 500;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger), #EF4444);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #DC2626, #EF4444);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Report Table */
.report-card {
    background: white;
    border-radius: 20px;
    box-shadow: var(--smooth-shadow);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.table-container {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-200);
}

.table {
    margin-bottom: 0;
    font-size: 0.95rem;
    font-family: 'Poppins', sans-serif;
}

.table thead {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
}

.table thead th {
    border: none;
    padding: 18px 16px;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'Poppins', sans-serif;
}

.table tbody td {
    padding: 16px;
    vertical-align: middle;
    border-color: var(--gray-200);
    color: var(--dark);
    font-weight: 400;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background: var(--gray-100);
    transform: scale(1.002);
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: var(--light);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--secondary);
}

.empty-state i {
    font-size: 4rem;
    color: var(--gray-300);
    margin-bottom: 20px;
}

.empty-state h4 {
    color: var(--secondary);
    font-weight: 500;
    margin-bottom: 10px;
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeInUp 0.6s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .main-content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .filters-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}
</style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
             <a href="Admin_Profile.php" class="sidebar_logo">
             <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
             </a>
        </div>
        <div class="sidebar-name">
            <p><?php echo "Welcome Admin, $adminname"; ?></p>
        </div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li class="active"><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
    <div class="main-content-header">
        <div>
            <h1>Reports</h1>
            <p class="welcome-text">Comprehensive employee reporting page</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card employees fade-in">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $totalEmployees; ?></h3>
                    <p>Total Employees</p>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card departments fade-in">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $totalDepartments; ?></h3>
                    <p>Departments</p>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card positions fade-in">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $totalPositions; ?></h3>
                    <p>Positions</p>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filters-card fade-in">
        <div class="filters-header">
            <h2 class="filters-title">
                <i class="fa-solid fa-filter"></i>
                Report Filters
            </h2>
        </div>

        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select class="form-select" name="report" onchange="this.form.submit()">
                    <option value="department-summary" <?= $report_type=='department-summary'?'selected':'' ?>>Department Summary</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" name="dept" id="deptSelect">
                    <option value="all">All Departments</option>
                    <?php foreach($departments as $d): ?>
                        <option value="<?= $d['deptID'] ?>" <?= ($filter_dept==$d['deptID'])?'selected':'' ?>><?= $d['deptName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Employment Type</label>
                <select class="form-select" name="type" id="typeSelect">
                    <option value="all">All Types</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?= $t ?>" <?= ($filter_type==$t)?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from" value="<?= $filter_from ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to" value="<?= $filter_to ?>">
            </div>

            <div class="col-12 d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter me-2"></i>Apply Filters
                </button>
                <a href="?<?= http_build_query($_GET) ?>&export=pdf" class="btn btn-danger">
                    <i class="fa-solid fa-file-pdf me-2"></i>Export PDF
                </a>
                <a href="Admin_Reports.php" class="btn btn-secondary">
                    <i class="fa-solid fa-refresh me-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    <div class="report-card fade-in">
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th class="text-center">Total Employees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($summary)): ?>
                            <?php foreach($summary as $dept => $positions): ?>
                                <?php foreach($positions as $pos => $types): ?>
                                    <?php foreach($types as $type => $total): ?>
                                        <tr class="fade-in">
                                            <td><strong><?= $dept ?></strong></td>
                                            <td><?= $pos ?></td>
                                            <td>
                                                <span class="badge bg-primary rounded-pill px-3 py-2"><?= $type ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold fs-5 text-primary"><?= $total ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-chart-bar"></i>
                                        <h4>No Data Available</h4>
                                        <p>Try adjusting your filters to see report data</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('deptSelect');
    const typeSelect = document.getElementById('typeSelect');

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

                // Reset type filter to "all" when department changes
                typeSelect.value = 'all';
            });
    });

    // Add hover effects to cards
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('disabled')) {
                this.style.transform = 'translateY(-2px)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
</body>
</html>