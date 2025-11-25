<?php
session_start();
require 'admin/db.connect.php';

// Initialize counters
$employees = 0;
$requests = 0;
$hirings = 0;
$applicants = 0;
$pendingApplicants = 0;

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// Count Employees
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM employee");
$employees = ($employeeQuery && $row = $employeeQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Applicants
$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
$applicants = ($applicantQuery && $row = $applicantQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Pending Applicants
$pendingQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE status = 'Pending'");
$pendingApplicants = ($pendingQuery && $row = $pendingQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Requests from both leave_request and general_request tables
$leaveRequestsQuery = $conn->query("SELECT COUNT(*) AS count FROM leave_request WHERE status = 'Pending'");
$generalRequestsQuery = $conn->query("SELECT COUNT(*) AS count FROM general_request WHERE status = 'Pending'");

$leaveRequests = ($leaveRequestsQuery && $row = $leaveRequestsQuery->fetch_assoc()) ? $row['count'] : 0;
$generalRequests = ($generalRequestsQuery && $row = $generalRequestsQuery->fetch_assoc()) ? $row['count'] : 0;
$requests = $leaveRequests + $generalRequests;

// Fetch total number of positions for Hirings (status = 'On-Going' or 'To Post')
$hiringsQuery = $conn->query("
    SELECT COUNT(*) AS count
    FROM (
        SELECT DISTINCT department_id, position_id 
        FROM vacancies 
        WHERE status = 'On-Going'
    ) AS uniqueVacancies
");
$hirings = ($hiringsQuery && $row = $hiringsQuery->fetch_assoc()) ? $row['count'] : 0;

// Pagination Setup
$limit = 5; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Count total rows
$totalQuery = $conn->query("
    SELECT COUNT(*) AS count
    FROM (
        SELECT DISTINCT department_id, position_id 
        FROM vacancies 
        WHERE status = 'On-Going'
    ) AS uniqueVacancies
");
$count = ($totalQuery && $row = $totalQuery->fetch_assoc()) ? $row['count'] : 0;

$pages = ceil($count / $limit);

// Fetch paginated vacancies
$recentVacanciesQuery = $conn->query("
    SELECT v.id, v.vacancy_count, v.status, d.deptName, p.position_title, e.typeName AS employment_type
    FROM vacancies v
    JOIN department d ON v.department_id = d.deptID
    JOIN position p ON v.position_id = p.positionID
    JOIN employment_type e ON v.employment_type_id = e.emtypeID
    WHERE v.status = 'On-Going'
    ORDER BY v.id DESC
    LIMIT $start, $limit
");

// Fetch Newly Hired Applicants
$newHiredQuery = $conn->query("
    SELECT applicantID, fullName, position_applied, department, hired_at, profile_pic
    FROM applicant
    WHERE status = 'Hired'
    ORDER BY hired_at DESC
    LIMIT 6
");

// Get additional stats for charts
$departmentStats = $conn->query("
    SELECT d.deptName, COUNT(e.empID) as employee_count
    FROM department d
    LEFT JOIN employee e ON d.deptName = e.department
    GROUP BY d.deptID, d.deptName
    ORDER BY employee_count DESC
    LIMIT 5
");

$monthlyHires = $conn->query("
    SELECT 
        DATE_FORMAT(hired_at, '%Y-%m') as month,
        COUNT(*) as hires
    FROM applicant 
    WHERE status = 'Hired' 
    AND hired_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(hired_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        }
        
        .main-content { 
            padding: 30px; 
            margin-left: 220px; 
            display: flex; 
            flex-direction: column; 
            width: calc(100% - 220px);
            min-height: 100vh;
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
        }
        
        /* Stats Cards */
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
        .stat-card.applicants::before { background: linear-gradient(90deg, var(--info), #0EA5E9); }
        .stat-card.pending::before { background: linear-gradient(90deg, var(--warning), #F59E0B); }
        .stat-card.requests::before { background: linear-gradient(90deg, var(--success), #10B981); }
        .stat-card.hirings::before { background: linear-gradient(90deg, #8B5CF6, #A855F7); }
        
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
        .stat-card.applicants .stat-icon { background: linear-gradient(135deg, var(--info), #0EA5E9); }
        .stat-card.pending .stat-icon { background: linear-gradient(135deg, var(--warning), #F59E0B); }
        .stat-card.requests .stat-icon { background: linear-gradient(135deg, var(--success), #10B981); }
        .stat-card.hirings .stat-icon { background: linear-gradient(135deg, #8B5CF6, #A855F7); }
        
        /* Content Sections */
        .content-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--smooth-shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title i {
            color: var(--primary-light);
        }
        
        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            color: var(--primary-light);
            transform: translateX(3px);
        }
        
        /* Hired Cards */
        .hired-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .hired-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hired-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--success), #34D399);
        }
        
        .hired-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .hired-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid var(--success);
            padding: 2px;
        }
        
        .hired-card h4 {
            margin-bottom: 8px;
            font-size: 1.1rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .hired-card p {
            margin: 4px 0;
            font-size: 0.85rem;
            color: var(--secondary);
        }
        
        .hired-date {
            background: var(--success);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 10px;
            display: inline-block;
        }
        
        /* Table Styling */
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
        }
        
        .table {
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 16px 12px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            border-color: var(--gray-200);
            color: var(--dark);
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: var(--gray-100);
            transform: scale(1.002);
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--smooth-shadow);
            border: 1px solid var(--gray-200);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            font-size: 1.2rem;
        }
        
        /* Pagination */
        .pagination .page-link {
            border-radius: 10px;
            margin: 0 4px;
            border: 1px solid var(--gray-300);
            color: var(--primary);
            font-weight: 500;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-color: var(--primary);
        }
        
        .pagination .page-link:hover {
            background: var(--gray-100);
            border-color: var(--primary-light);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 15px;
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
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .hired-cards {
                grid-template-columns: 1fr;
            }
            
            .main-content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
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
            <li class="active"><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
            <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
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
                <h1>Dashboard Overview</h1>
                <p class="welcome-text">Welcome back, <?php echo $adminname; ?>! Here's what's happening today.</p>
            </div>
            <?php date_default_timezone_set('Asia/Manila'); ?>
            <div class="text-end">
    <p class="text-muted mb-1"><?php echo date('l, F j, Y'); ?></p>
    <p class="text-muted"><?php echo date('h:i A'); ?></p>
</div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card employees fade-in">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $employees; ?></h3>
                        <p>Total Employees</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card applicants fade-in">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $applicants; ?></h3>
                        <p>Applicants</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-file-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card pending fade-in">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $pendingApplicants; ?></h3>
                        <p>Pending Applications</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card requests fade-in">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $requests; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card hirings fade-in">
                <div class="stat-card-content">
                    <div class="stat-info">
                        <h3><?php echo $hirings; ?></h3>
                        <p>Active Hirings</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-container">
            <div class="chart-card fade-in">
                <div class="chart-header">
                    <h3 class="chart-title">Department Distribution</h3>
                </div>
                <canvas id="departmentChart" height="250"></canvas>
            </div>
            
            <div class="chart-card fade-in">
                <div class="chart-header">
                    <h3 class="chart-title">Monthly Hires</h3>
                </div>
                <canvas id="hiresChart" height="250"></canvas>
            </div>
        </div>

        <!-- Newly Hired Section -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-user-check"></i>
                    Newly Hired Employees
                </h2>
                <a href="Admin-Applicants.php" class="view-all">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="hired-cards">
                <?php if ($newHiredQuery && $newHiredQuery->num_rows > 0): ?>
                    <?php while ($hired = $newHiredQuery->fetch_assoc()): ?>
                        <div class="hired-card">
                            <?php
                            $pic = (!empty($hired['profile_pic']) && file_exists("Uploads/" . $hired['profile_pic']))
                                ? $hired['profile_pic']
                                : "default.png";
                            ?>
                            <img src="Uploads/<?php echo $pic; ?>" alt="Profile Picture">
                            <h4><?php echo $hired['fullName']; ?></h4>
                            <p><strong>Position:</strong> <?php echo $hired['position_applied']; ?></p>
                            <p><strong>Department:</strong> <?php echo $hired['department']; ?></p>
                            <span class="hired-date">
                                <?php echo date("M d, Y", strtotime($hired['hired_at'])); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state w-100">
                        <i class="fa-solid fa-user-plus"></i>
                        <p>No newly hired employees yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Job Posts -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-briefcase"></i>
                    Recent Job Posts
                </h2>
            </div>

            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Position Title</th>
                            <th>Department</th>
                            <th>Employment Type</th>
                            <th>Vacancy Count</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recentVacanciesQuery && $recentVacanciesQuery->num_rows > 0): ?>
                            <?php 
                            $i = $start + 1;
                            while($vacancy = $recentVacanciesQuery->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><strong><?php echo $vacancy['position_title']; ?></strong></td>
                                    <td><?php echo $vacancy['deptName']; ?></td>
                                    <td><?php echo $vacancy['employment_type']; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $vacancy['vacancy_count']; ?></span></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $vacancy['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-briefcase"></i>
                                        <p>No recent job posts available.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if($pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Button -->
                    <li class="page-item <?php echo ($page >= $pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Department Distribution Chart
            const deptCtx = document.getElementById('departmentChart').getContext('2d');
            const deptChart = new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php 
                        if ($departmentStats && $departmentStats->num_rows > 0) {
                            $labels = [];
                            while ($dept = $departmentStats->fetch_assoc()) {
                                $labels[] = "'" . $dept['deptName'] . "'";
                            }
                            echo implode(', ', $labels);
                        } else {
                            echo "'No Data'";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            if ($departmentStats && $departmentStats->num_rows > 0) {
                                $departmentStats->data_seek(0);
                                $data = [];
                                while ($dept = $departmentStats->fetch_assoc()) {
                                    $data[] = $dept['employee_count'];
                                }
                                echo implode(', ', $data);
                            } else {
                                echo "1";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw} employees`;
                                }
                            }
                        }
                    }
                }
            });

            // Monthly Hires Chart
            const hiresCtx = document.getElementById('hiresChart').getContext('2d');
            const hiresChart = new Chart(hiresCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        if ($monthlyHires && $monthlyHires->num_rows > 0) {
                            $months = [];
                            while ($hire = $monthlyHires->fetch_assoc()) {
                                $date = DateTime::createFromFormat('Y-m', $hire['month']);
                                $months[] = "'" . $date->format('M Y') . "'";
                            }
                            echo implode(', ', array_reverse($months));
                        } else {
                            echo "'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun','Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Hires',
                        data: [
                            <?php 
                            if ($monthlyHires && $monthlyHires->num_rows > 0) {
                                $hiresData = [];
                                while ($hire = $monthlyHires->fetch_assoc()) {
                                    $hiresData[] = $hire['hires'];
                                }
                                echo implode(', ', array_reverse($hiresData));
                            } else {
                                echo "0, 0, 0, 0, 0, 0";
                            }
                            ?>
                        ],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Add hover effects to all cards
            const cards = document.querySelectorAll('.stat-card, .hired-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>