<?php
session_start();
require 'admin/db.connect.php';
// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE sub_role = 'Human Resource (HR) Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Human Resource (HR) Admin';

// FETCH ALL DATA FOR TABLES
$typesReq = $conn->query("SELECT * FROM types_of_requests ORDER BY id DESC");
$payCats  = $conn->query("SELECT * FROM leave_pay_categories ORDER BY id DESC");
$leaveTypes = $conn->query("
    SELECT lt.id, lt.leave_type_name, tr.request_type_name, pc.category_name, lt.request_type_id, lt.pay_category_id
    FROM leave_types lt
    JOIN types_of_requests tr ON lt.request_type_id = tr.id
    LEFT JOIN leave_pay_categories pc ON lt.pay_category_id = pc.id
    ORDER BY lt.id DESC
");

// Get counts for cards
$requestCount = $conn->query("SELECT COUNT(*) as count FROM types_of_requests")->fetch_assoc()['count'];
$categoryCount = $conn->query("SELECT COUNT(*) as count FROM leave_pay_categories")->fetch_assoc()['count'];
$leaveTypeCount = $conn->query("SELECT COUNT(*) as count FROM leave_types")->fetch_assoc()['count'];

/* ===================== CREATE ===================== */

// Add Request Type
if (isset($_POST['add_request_type'])) {
    $name = $_POST['new_request_type'];
    $conn->query("INSERT INTO types_of_requests (request_type_name) VALUES ('$name')");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Add Pay Category
if (isset($_POST['add_pay_category'])) {
    $name = $_POST['new_pay_category'];
    $conn->query("INSERT INTO leave_pay_categories (category_name) VALUES ('$name')");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Add Leave Type
if (isset($_POST['add_leave_type'])) {
    $leave = $_POST['leave_name'];
    $req   = $_POST['req_type'];
    $cat   = $_POST['pay_cat'];

    $conn->query("INSERT INTO leave_types (leave_type_name, request_type_id, pay_category_id)
                  VALUES ('$leave', '$req', '$cat')");
    header("Location: Admin_RequestSetting.php");
    exit;
}

/* ===================== UPDATE ===================== */

// Update Request Type
if (isset($_POST['update_request_type'])) {
    $id = $_POST['request_type_id'];
    $name = $_POST['request_type_name'];
    $conn->query("UPDATE types_of_requests SET request_type_name = '$name' WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Update Pay Category
if (isset($_POST['update_pay_category'])) {
    $id = $_POST['pay_category_id'];
    $name = $_POST['category_name'];
    $conn->query("UPDATE leave_pay_categories SET category_name = '$name' WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Update Leave Type
if (isset($_POST['update_leave_type'])) {
    $id = $_POST['leave_type_id'];
    $name = $_POST['leave_type_name'];
    $req_type = $_POST['request_type_id'];
    $pay_cat = $_POST['pay_category_id'];
    
    $conn->query("UPDATE leave_types SET leave_type_name = '$name', request_type_id = $req_type, pay_category_id = $pay_cat WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

/* ===================== DELETE ===================== */

// Delete request type
if (isset($_POST['delete_request_type'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM types_of_requests WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Delete pay category
if (isset($_POST['delete_pay_category'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM leave_pay_categories WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Delete leave type
if (isset($_POST['delete_leave_type'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM leave_types WHERE id = $id");
    header("Location: Admin_RequestSetting.php");
    exit;
}

// Fetch data for dropdowns
$requestTypesDropdown = $conn->query("SELECT * FROM types_of_requests ORDER BY request_type_name");
$payCategoriesDropdown = $conn->query("SELECT * FROM leave_pay_categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Request Settings</title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #1E3A8A;
            --primary-light: #3B82F6;
            --secondary: #64748B;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --light: #F8FAFC;
            --dark: #1E293B;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            margin:0; 
            display:flex; 
            background-color:#f1f5fc; 
            color:#111827; 
        }
        .main-content { 
            padding:30px; 
            margin-left:220px; 
            display:flex; 
            flex-direction:column; 
            width: calc(100% - 220px);
        }
        .main-content-header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .main-content-header h1 { 
            color: var(--primary);
            font-weight: 700;
            margin: 0;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-card.request-types {
            border-left-color: var(--primary);
        }
        
        .stat-card.pay-categories {
            border-left-color: var(--success);
        }
        
        .stat-card.leave-types {
            border-left-color: var(--warning);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-card.request-types i {
            color: var(--primary);
        }
        
        .stat-card.pay-categories i {
            color: var(--success);
        }
        
        .stat-card.leave-types i {
            color: var(--warning);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark);
        }
        
        .stat-card p {
            color: var(--secondary);
            margin: 5px 0 0 0;
            font-weight: 500;
        }
        
        /* Section Styling */
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }
        
        /* Form Styling */
        .add-form {
            background: var(--light);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: var(--warning);
            border: none;
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            border: none;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        /* Table Styling */
        .table-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e2e8f0;
        }
        
        .table tbody tr {
            transition: background 0.2s;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .table tbody tr:nth-child(even) {
            background: #fafbfc;
        }
        
        .table tbody tr:nth-child(even):hover {
            background: #f1f5f9;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modal-header {
            background: var(--primary);
            color: white;
            border-radius: 12px 12px 0 0;
            border-bottom: none;
            padding: 20px 24px;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        /* Delete confirmation modal */
        .delete-modal .modal-header {
            background: var(--danger);
        }
        
        .delete-icon {
            font-size: 3rem;
            color: var(--danger);
            margin-bottom: 1rem;
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
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
        
        /* Animation for new items */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
        </div>
        <div class="sidebar-name">
            <p><?php echo "Welcome Admin, $adminname"; ?></p>
        </div>
        <ul class="nav flex-column">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
            <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
            <li class="active"><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
            <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Request Settings</h1>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card request-types">
                <i class="fa-solid fa-list-check"></i>
                <h3><?php echo $requestCount; ?></h3>
                <p>Request Types</p>
            </div>
            
            <div class="stat-card pay-categories">
                <i class="fa-solid fa-money-bill-wave"></i>
                <h3><?php echo $categoryCount; ?></h3>
                <p>Pay Categories</p>
            </div>
            
            <div class="stat-card leave-types">
                <i class="fa-solid fa-calendar-day"></i>
                <h3><?php echo $leaveTypeCount; ?></h3>
                <p>Leave Types</p>
            </div>
        </div>

        <div class="container-fluid px-0">
            <!-- =================== TYPES OF REQUESTS ====================== -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">Types of Requests</h2>
                </div>
                
                <div class="add-form">
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="new_request_type" class="form-label">Add New Request Type</label>
                            <input type="text" name="new_request_type" id="new_request_type" required class="form-control" placeholder="Enter request type name">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" name="add_request_type">
                                <i class="fa-solid fa-plus me-2"></i> Add Request Type
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="10%">ID</th>
                                <th>Request Type</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $typesReq->data_seek(0); // Reset pointer
                            while($row = $typesReq->fetch_assoc()): 
                            ?>
                                <tr class="fade-in">
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td><?= $row['request_type_name'] ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="openEditRequestTypeModal(<?= $row['id'] ?>, '<?= $row['request_type_name'] ?>')">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="openDeleteModal('request_type', <?= $row['id'] ?>, '<?= $row['request_type_name'] ?>')">
                                                <i class="fa-solid fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================== LEAVE PAY CATEGORIES ====================== -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">Leave Pay Categories</h2>
                </div>
                
                <div class="add-form">
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="new_pay_category" class="form-label">Add New Pay Category</label>
                            <input type="text" name="new_pay_category" id="new_pay_category" required class="form-control" placeholder="Enter pay category name">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" name="add_pay_category">
                                <i class="fa-solid fa-plus me-2"></i> Add Pay Category
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="10%">ID</th>
                                <th>Pay Category</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $payCats->data_seek(0); // Reset pointer
                            while($row = $payCats->fetch_assoc()): 
                            ?>
                                <tr class="fade-in">
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td><?= $row['category_name'] ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="openEditPayCategoryModal(<?= $row['id'] ?>, '<?= $row['category_name'] ?>')">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="openDeleteModal('pay_category', <?= $row['id'] ?>, '<?= $row['category_name'] ?>')">
                                                <i class="fa-solid fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================== LEAVE TYPES ====================== -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">Leave Types</h2>
                </div>
                
                <div class="add-form">
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="leave_name" class="form-label">Leave Type Name</label>
                            <input type="text" name="leave_name" id="leave_name" class="form-control" placeholder="Leave type" required>
                        </div>

                        <div class="col-md-3">
                            <label for="req_type" class="form-label">Request Type</label>
                            <select name="req_type" id="req_type" class="form-select" required>
                                <option value="">Select Request Type</option>
                                <?php
                                    $res = $conn->query("SELECT * FROM types_of_requests");
                                    while($r = $res->fetch_assoc()):
                                ?>
                                    <option value="<?= $r['id'] ?>"><?= $r['request_type_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="pay_cat" class="form-label">Pay Category</label>
                            <select name="pay_cat" id="pay_cat" class="form-select" required>
                                <option value="">Select Pay Category</option>
                                <?php
                                    $pc = $conn->query("SELECT * FROM leave_pay_categories");
                                    while($p = $pc->fetch_assoc()):
                                ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['category_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" name="add_leave_type">
                                <i class="fa-solid fa-plus me-2"></i> Add Leave Type
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="8%">ID</th>
                                <th>Leave Type</th>
                                <th>Request Type</th>
                                <th>Pay Category</th>
                                <th width="18%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $leaveTypes->data_seek(0); // Reset pointer
                            while($row = $leaveTypes->fetch_assoc()): 
                            ?>
                                <tr class="fade-in">
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td><?= $row['leave_type_name'] ?></td>
                                    <td><?= $row['request_type_name'] ?></td>
                                    <td><?= $row['category_name'] ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="openEditLeaveTypeModal(<?= $row['id'] ?>, '<?= $row['leave_type_name'] ?>', <?= $row['request_type_id'] ?>, <?= $row['pay_category_id'] ?>)">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="openDeleteModal('leave_type', <?= $row['id'] ?>, '<?= $row['leave_type_name'] ?>')">
                                                <i class="fa-solid fa-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Request Type Modal -->
    <div class="modal fade" id="editRequestTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Request Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="request_type_id" id="edit_request_type_id">
                        <div class="mb-3">
                            <label for="edit_request_type_name" class="form-label">Request Type Name</label>
                            <input type="text" class="form-control" id="edit_request_type_name" name="request_type_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_request_type">Update Request Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Pay Category Modal -->
    <div class="modal fade" id="editPayCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pay Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="pay_category_id" id="edit_pay_category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_pay_category">Update Pay Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Leave Type Modal -->
    <div class="modal fade" id="editLeaveTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Leave Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="leave_type_id" id="edit_leave_type_id">
                        <div class="mb-3">
                            <label for="edit_leave_type_name" class="form-label">Leave Type Name</label>
                            <input type="text" class="form-control" id="edit_leave_type_name" name="leave_type_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_request_type_id" class="form-label">Request Type</label>
                            <select class="form-select" id="edit_request_type_id" name="request_type_id" required>
                                <option value="">Select Request Type</option>
                                <?php
                                    $requestTypesDropdown->data_seek(0);
                                    while($row = $requestTypesDropdown->fetch_assoc()):
                                ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['request_type_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pay_category_id" class="form-label">Pay Category</label>
                            <select class="form-select" id="edit_pay_category_id" name="pay_category_id" required>
                                <option value="">Select Pay Category</option>
                                <?php
                                    $payCategoriesDropdown->data_seek(0);
                                    while($row = $payCategoriesDropdown->fetch_assoc()):
                                ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['category_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_leave_type">Update Leave Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade delete-modal" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteForm">
                    <div class="modal-body text-center">
                        <div class="delete-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h4 class="text-danger mb-3">Are you sure?</h4>
                        <p>You are about to delete <strong id="deleteItemName"></strong>. This action cannot be undone.</p>
                        <input type="hidden" name="delete_id" id="delete_id">
                        <input type="hidden" name="delete_type" id="delete_type">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="deleteConfirmBtn">Yes, Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Modal Functions
        function openEditRequestTypeModal(id, name) {
            document.getElementById('edit_request_type_id').value = id;
            document.getElementById('edit_request_type_name').value = name;
            const modal = new bootstrap.Modal(document.getElementById('editRequestTypeModal'));
            modal.show();
        }

        function openEditPayCategoryModal(id, name) {
            document.getElementById('edit_pay_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            const modal = new bootstrap.Modal(document.getElementById('editPayCategoryModal'));
            modal.show();
        }

        function openEditLeaveTypeModal(id, name, requestTypeId, payCategoryId) {
            document.getElementById('edit_leave_type_id').value = id;
            document.getElementById('edit_leave_type_name').value = name;
            document.getElementById('edit_request_type_id').value = requestTypeId;
            document.getElementById('edit_pay_category_id').value = payCategoryId;
            const modal = new bootstrap.Modal(document.getElementById('editLeaveTypeModal'));
            modal.show();
        }

        // Delete Modal Function
        function openDeleteModal(type, id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_type').value = type;
            document.getElementById('deleteItemName').textContent = name;
            
            // Set the correct form action based on type
            const deleteForm = document.getElementById('deleteForm');
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            
            if (type === 'request_type') {
                deleteBtn.name = 'delete_request_type';
            } else if (type === 'pay_category') {
                deleteBtn.name = 'delete_pay_category';
            } else if (type === 'leave_type') {
                deleteBtn.name = 'delete_leave_type';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // Add confirmation for delete actions
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to new rows
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Add hover effects to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>