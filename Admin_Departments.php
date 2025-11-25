<?php
session_start();
require 'admin/db.connect.php';

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// ---------------------------
// --- INITIALIZE VARIABLES ---
// ---------------------------

$editDeptID = null;
$editDeptName = "";

$editPositionID = null;
$editPositionTitle = "";
$editPositionDeptID = "";

// ---------------------------
// --- HANDLE GET PARAMETERS ---
// ---------------------------

// EDIT Department
if(isset($_GET['editDeptID'])){
    $editDeptID = $_GET['editDeptID'];
    $stmt = $conn->prepare("SELECT deptName FROM department WHERE deptID=?");
    $stmt->bind_param("i", $editDeptID);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $editDeptName = $row['deptName'];
    }
}

// EDIT Position
if(isset($_GET['editPositionID'])){
    $editPositionID = $_GET['editPositionID'];
    $stmt = $conn->prepare("SELECT departmentID, position_title FROM position WHERE positionID=?");
    $stmt->bind_param("i", $editPositionID);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $editPositionTitle = $row['position_title'];
        $editPositionDeptID = $row['departmentID'];
    }
}

// ---------------------------
// --- HANDLE POST REQUESTS ---
// ---------------------------

// ADD Department
if(isset($_POST['addDept'])){
    $deptName = $_POST['deptName'];
    $stmt = $conn->prepare("INSERT INTO department (deptName) VALUES (?)");
    $stmt->bind_param("s", $deptName);
    if($stmt->execute()){
        $_SESSION['success'] = "Department '$deptName' added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add department: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-1");
    exit;
}

// EDIT Department
if(isset($_POST['editDept'])){
    $deptID = $_POST['deptID'];
    $deptName = $_POST['deptName'];
    $stmt = $conn->prepare("UPDATE department SET deptName=? WHERE deptID=?");
    $stmt->bind_param("si", $deptName, $deptID);
    if($stmt->execute()){
        $_SESSION['success'] = "Department updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update department: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-1");
    exit;
}

// DELETE Department
if(isset($_GET['deleteDept'])){
    $deptID = $_GET['deleteDept'];
    $stmt = $conn->prepare("DELETE FROM department WHERE deptID=?");
    $stmt->bind_param("i", $deptID);
    if($stmt->execute()){
        $_SESSION['success'] = "Department deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete department: ".$stmt->error;
    }
    header("Location: #tabs-1");
    exit;
}

// ADD Position
if(isset($_POST['addPosition'])){
    $departmentID = $_POST['departmentID'];
    $position_title = $_POST['position_title'];
    $stmt = $conn->prepare("INSERT INTO position (departmentID, position_title) VALUES (?, ?)");
    $stmt->bind_param("is", $departmentID, $position_title);
    if($stmt->execute()){
        $_SESSION['success'] = "Position '$position_title' added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// EDIT Position
if(isset($_POST['editPosition'])){
    $positionID = $_POST['positionID'];
    $positionTitle = $_POST['position_title'];
    $departmentID = $_POST['departmentID'];
    $stmt = $conn->prepare("UPDATE position SET position_title=?, departmentID=? WHERE positionID=?");
    $stmt->bind_param("sii", $positionTitle, $departmentID, $positionID);
    if($stmt->execute()){
        $_SESSION['success'] = "Position updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// DELETE Position
if(isset($_GET['deletePosition'])){
    $positionID = $_GET['deletePosition'];
    $stmt = $conn->prepare("DELETE FROM position WHERE positionID=?");
    $stmt->bind_param("i", $positionID);
    if($stmt->execute()){
        $_SESSION['success'] = "Position deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// ---------------------------
// --- FETCH DATA FOR TABLES ---
// ---------------------------

// Pagination settings
$records_per_page = 10;

// Departments pagination
$dept_page = isset($_GET['dept_page']) ? max(1, intval($_GET['dept_page'])) : 1;
$dept_offset = ($dept_page - 1) * $records_per_page;
$departments_total = $conn->query("SELECT COUNT(*) AS total FROM department")->fetch_assoc()['total'];
$departments = $conn->query("SELECT * FROM department ORDER BY deptID ASC LIMIT $records_per_page OFFSET $dept_offset");
$departments_total_pages = ceil($departments_total / $records_per_page);

// Positions pagination
$pos_page = isset($_GET['pos_page']) ? max(1, intval($_GET['pos_page'])) : 1;
$pos_offset = ($pos_page - 1) * $records_per_page;
$positions_total = $conn->query("SELECT COUNT(*) AS total FROM position")->fetch_assoc()['total'];
$positions = $conn->query("SELECT p.positionID, p.position_title, d.deptName
                           FROM position p
                           JOIN department d ON p.departmentID = d.deptID
                           ORDER BY p.positionID ASC
                           LIMIT $records_per_page OFFSET $pos_offset");
$positions_total_pages = ceil($positions_total / $records_per_page);

// Fetch all departments for dropdown
$allDepartments = $conn->query("SELECT * FROM department ORDER BY deptName ASC");

// Get statistics for cards
$totalDepartments = $conn->query("SELECT COUNT(*) as count FROM department")->fetch_assoc()['count'];
$totalPositions = $conn->query("SELECT COUNT(*) as count FROM position")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments & Positions Management</title>
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

.stat-card.departments::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.stat-card.positions::before { background: linear-gradient(90deg, var(--success), #10B981); }

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

.stat-card.departments .stat-icon { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
.stat-card.positions .stat-icon { background: linear-gradient(135deg, var(--success), #10B981); }

/* Modern Tabs Container */
.modern-tabs {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    font-family: 'Poppins', sans-serif;
}

/* Tabs Navigation */
.modern-tabs-nav {
    display: flex;
    gap: 8px;
    padding: 0;
    margin-bottom: 0;
    border-bottom: 1px solid var(--gray-200);
    background: transparent;
    position: relative;
}

.modern-tabs-nav::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: var(--gray-200);
    z-index: 1;
}

/* Tab Items */
.modern-tab-item {
    list-style: none;
    position: relative;
    z-index: 2;
}

.modern-tab-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 28px;
    background-color: transparent;
    color: var(--secondary);
    font-weight: 500;
    text-decoration: none;
    border-radius: 12px 12px 0 0;
    transition: all 0.3s ease;
    position: relative;
    border: none;
    outline: none;
    font-size: 1rem;
}

.modern-tab-link i {
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.modern-tab-link:hover {
    color: var(--primary);
    background-color: var(--gray-100);
    transform: translateY(-2px);
}

.modern-tab-link:hover i {
    transform: scale(1.1);
}

/* Active Tab */
.modern-tab-item.active .modern-tab-link {
    color: var(--primary);
    background-color: white;
    font-weight: 600;
    box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
}

.modern-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    z-index: 3;
}

/* Tab Panels */
.modern-tab-panels {
    background: white;
    border-radius: 0 0 20px 20px;
    box-shadow: var(--smooth-shadow);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    margin-top: -1px;
}

.modern-tab-panel {
    padding: 40px;
    display: none;
}

.modern-tab-panel.active {
    display: block;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Panel Headers */
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.panel-title {
    color: var(--primary);
    font-weight: 600;
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.panel-title i {
    color: var(--primary-light);
    font-size: 1.3rem;
}

/* Forms inside tabs */
.form-card {
    background: var(--light);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--card-shadow);
}

.modern-tab-panel form .form-control, 
.modern-tab-panel form .form-select {
    border-radius: 12px;
    border: 1px solid var(--gray-300);
    padding: 14px 18px;
    font-size: 1rem;
    font-weight: 400;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
}

.modern-tab-panel form .form-control:focus, 
.modern-tab-panel form .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.form-label {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 10px;
    font-size: 1rem;
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

.btn-secondary {
    background: var(--gray-200);
    color: var(--dark);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-secondary:hover {
    background: var(--gray-300);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning), #F59E0B);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover {
    background: linear-gradient(135deg, #EAB308, #F59E0B);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger), #EF4444);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #DC2626, #EF4444);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-sm {
    padding: 10px 16px;
    font-size: 0.9rem;
    border-radius: 10px;
}

/* Table styling */
.table-container {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-200);
    margin-top: 20px;
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

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

/* Pagination styling */
.pagination {
    margin-top: 30px;
}

.pagination .page-link {
    border-radius: 10px;
    margin: 0 4px;
    border: 1px solid var(--gray-300);
    color: var(--primary);
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    padding: 10px 16px;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-color: var(--primary);
    color: white;
}

.pagination .page-link:hover {
    background: var(--gray-100);
    border-color: var(--primary-light);
    transform: translateY(-1px);
}

/* Alert styling */
.alert {
    border-radius: 12px;
    border: none;
    padding: 16px 20px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    margin-bottom: 24px;
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #ECFDF5);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-danger {
    background: linear-gradient(135deg, #FEE2E2, #FEF2F2);
    color: var(--danger);
    border-left: 4px solid var(--danger);
}

/* Delete Modal */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
}

.modal-header {
    background: linear-gradient(135deg, var(--danger), #EF4444);
    color: white;
    border-bottom: none;
    padding: 25px 30px;
}

.modal-title {
    font-weight: 600;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-body {
    padding: 30px;
    font-size: 1.1rem;
    color: var(--dark);
    text-align: center;
}

.modal-footer {
    border-top: 1px solid var(--gray-200);
    padding: 20px 30px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .modern-tabs-nav {
        flex-direction: column;
        gap: 0;
    }
    
    .modern-tab-item {
        width: 100%;
    }
    
    .modern-tab-link {
        border-radius: 0;
        border-bottom: 1px solid var(--gray-200);
        justify-content: center;
    }
    
    .modern-tab-item.active::after {
        display: none;
    }
    
    .modern-tab-panel {
        padding: 25px 20px;
    }
    
    .panel-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .form-card {
        padding: 20px;
    }
}
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome Admin, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li class="active"><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
    <div class="main-content-header">
        <div>
            <h1>Department & Position Management</h1>
            <p class="welcome-text">Manage departments and positions</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card departments fade-in">
            <div class="stat-card-content">
                <div class="stat-info">
                    <h3><?php echo $totalDepartments; ?></h3>
                    <p>Total Departments</p>
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
                    <p>Total Positions</p>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- SUCCESS / ERROR ALERTS -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Modern Tabs -->
    <div class="modern-tabs">
        <ul class="modern-tabs-nav">
            <li class="modern-tab-item active" data-tab="tab-1">
                <a href="#tab-1" class="modern-tab-link">
                    <i class="fa-solid fa-building"></i>
                    <span>Departments Management</span>
                </a>
            </li>
            <li class="modern-tab-item" data-tab="tab-2">
                <a href="#tab-2" class="modern-tab-link">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Positions Management</span>
                </a>
            </li>
        </ul>

        <div class="modern-tab-panels">
            <!-- Departments Tab -->
            <div id="tab-1" class="modern-tab-panel active">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="fa-solid fa-building"></i>
                        Departments Management
                    </h2>
                </div>

                <div class="form-card">
                    <form class="row g-3" method="POST">
                        <div class="col-md-6">
                            <label class="form-label">Department Name</label>
                            <input type="text" name="deptName" class="form-control" placeholder="Enter department name" 
                                   value="<?php echo htmlspecialchars($editDeptName); ?>" required>
                            <?php if($editDeptID): ?>
                                <input type="hidden" name="deptID" value="<?php echo $editDeptID; ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-auto d-flex align-items-end">
                            <button type="submit" name="<?php echo $editDeptID ? 'editDept' : 'addDept'; ?>" class="btn btn-primary">
                                <i class="fa-solid fa-<?php echo $editDeptID ? 'pen' : 'plus'; ?> me-2"></i>
                                <?php echo $editDeptID ? 'Update Department' : 'Add New Department'; ?>
                            </button>
                            <?php if($editDeptID): ?>
                                <a href="Admin_Departments.php#tab-1" class="btn btn-secondary ms-2">
                                    <i class="fa-solid fa-times me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Department Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $departments->data_seek(0);
                        $dept_count = $dept_offset + 1;
                        while($d = $departments->fetch_assoc()):
                        ?>
                            <tr class="fade-in">
                                <td><strong><?php echo $dept_count++; ?></strong></td>
                                <td><?php echo htmlspecialchars($d['deptName']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?editDeptID=<?php echo $d['deptID']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                        </a>
                                        <a href="?deleteDept=<?php echo $d['deptID']; ?>" 
                                           class="btn btn-danger btn-sm deleteBtn" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#deleteModal"
                                           data-href="?deleteDept=<?php echo $d['deptID']; ?>">
                                           <i class="fa-solid fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($departments_total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for($i=1; $i<=$departments_total_pages; $i++): ?>
                        <li class="page-item <?php if($i==$dept_page) echo 'active'; ?>">
                            <a class="page-link" href="?dept_page=<?php echo $i; ?>#tab-1"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>

            <!-- Positions Tab -->
            <div id="tab-2" class="modern-tab-panel">
                <div class="panel-header">
                    <h2 class="panel-title">
                        <i class="fa-solid fa-user-tie"></i>
                        Positions Management
                    </h2>
                </div>

                <div class="form-card">
                    <form class="row g-3" method="POST">
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="departmentID" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php
                                if($allDepartments){
                                    $allDepartments->data_seek(0);
                                    while($d = $allDepartments->fetch_assoc()){
                                        $selected = ($editPositionDeptID == $d['deptID']) ? "selected" : "";
                                        echo "<option value='{$d['deptID']}' $selected>{$d['deptName']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Position Title</label>
                            <input type="text" name="position_title" class="form-control" placeholder="Enter position title"
                                   value="<?php echo htmlspecialchars($editPositionTitle); ?>" required>
                            <?php if($editPositionID): ?>
                                <input type="hidden" name="positionID" value="<?php echo $editPositionID; ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-auto d-flex align-items-end">
                            <button type="submit" name="<?php echo $editPositionID ? 'editPosition' : 'addPosition'; ?>" class="btn btn-primary">
                                <i class="fa-solid fa-<?php echo $editPositionID ? 'pen' : 'plus'; ?> me-2"></i>
                                <?php echo $editPositionID ? 'Update Position' : 'Add New Position'; ?>
                            </button>
                            <?php if($editPositionID): ?>
                                <a href="Admin_Departments.php#tab-2" class="btn btn-secondary ms-2">
                                    <i class="fa-solid fa-times me-2"></i>Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Position Title</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $positions->data_seek(0);
                        $pos_count = $pos_offset + 1;
                        while($p = $positions->fetch_assoc()):
                        ?>
                            <tr class="fade-in">
                                <td><strong><?php echo $pos_count++; ?></strong></td>
                                <td><?php echo htmlspecialchars($p['position_title']); ?></td>
                                <td><?php echo htmlspecialchars($p['deptName']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?editPositionID=<?php echo $p['positionID']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                        </a>
                                        <a href="?deletePosition=<?php echo $p['positionID']; ?>" 
                                           class="btn btn-danger btn-sm deleteBtn" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#deleteModal"
                                           data-href="?deletePosition=<?php echo $p['positionID']; ?>">
                                           <i class="fa-solid fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($positions_total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for($i=1; $i<=$positions_total_pages; $i++): ?>
                        <li class="page-item <?php if($i==$pos_page) echo 'active'; ?>">
                            <a class="page-link" href="?pos_page=<?php echo $i; ?>#tab-2"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fa-solid fa-exclamation-triangle text-danger me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this item? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-2"></i>Cancel
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fa-solid fa-trash me-2"></i>Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Modern tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabItems = document.querySelectorAll('.modern-tab-item');
    const tabPanels = document.querySelectorAll('.modern-tab-panel');
    
    // Function to activate a tab
    function activateTab(tabId) {
        // Deactivate all tabs
        tabItems.forEach(item => item.classList.remove('active'));
        tabPanels.forEach(panel => panel.classList.remove('active'));
        
        // Activate the selected tab
        const selectedTab = document.querySelector(`.modern-tab-item[data-tab="${tabId}"]`);
        const selectedPanel = document.getElementById(tabId);
        
        if (selectedTab && selectedPanel) {
            selectedTab.classList.add('active');
            selectedPanel.classList.add('active');
            
            // Update URL hash
            window.location.hash = tabId;
        }
    }
    
    // Add click event listeners to tabs
    tabItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            activateTab(tabId);
        });
    });
    
    // Check URL hash on page load
    const urlHash = window.location.hash;
    if (urlHash) {
        activateTab(urlHash.substring(1));
    }
    
    // Check URL parameters for edit actions
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('editPositionID')){
        activateTab('tab-2');
    } else if(urlParams.has('editDeptID')){
        activateTab('tab-1');
    }

    // Delete modal functionality
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var href = button.getAttribute('data-href');
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.setAttribute('href', href);
    });

    // Add hover effects to cards and buttons
    const cards = document.querySelectorAll('.stat-card, .form-card');
    const buttons = document.querySelectorAll('.btn');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

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