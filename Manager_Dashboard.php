<?php
session_start();
require 'admin/db.connect.php';

// Count Employees
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM employee");
$employees = ($employeeQuery && $row = $employeeQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Applicants
$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
$applicants = ($applicantQuery && $row = $applicantQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Pending Applicants
$pendingQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE status = 'Pending'");
$pendingApplicants = ($pendingQuery && $row = $pendingQuery->fetch_assoc()) ? $row['count'] : 0;



//fetch pending applicants
$pending_applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE id = '0'");
if ($pending_applicantQuery && $row = $pending_applicantQuery->fetch_assoc()) {
    $pending_applicantQuery = $row['count'];
}

$pendingLeaves = 0;
$plRes = $conn->query("SELECT COUNT(*) AS c FROM leave_request WHERE status = 'Pending'");
if ($plRes && $r = $plRes->fetch_assoc())
    $pendingLeaves = (int) $r['c'];
$pendingGeneral = 0;
$pgRes = $conn->query("SELECT COUNT(*) AS c FROM general_request WHERE status = 'Pending'");
if ($pgRes && $r2 = $pgRes->fetch_assoc())
    $pendingGeneral = (int) $r2['c'];
$pendingRequests = $pendingLeaves + $pendingGeneral;

$onLeaveCount = 0;
$olRes = $conn->query("SELECT COUNT(DISTINCT empID) AS count FROM leave_request WHERE status = 'Approved'");
if ($olRes && $row = $olRes->fetch_assoc())
    $onLeaveCount = (int) $row['count'];



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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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


// Manager name
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
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>

    <link rel="stylesheet" href="manager-sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --primary: #1E3A8A;
            --primary-light: #3B82F6;
            --secondary: #10B981;
            --accent: #F59E0B;
            --danger: #EF4444;
            --light: #F8FAFC;
            --dark: #111827;
            --gray: #6B7280;
            --gray-light: #E5E7EB;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: var(--dark);
            line-height: 1.6;
        }

        .main-content {
            padding: 30px;
            margin-left: 220px;
            width: calc(100% - 220px);
            display: flex;
            flex-direction: column;
        }

        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }

        .main-content-header h1 {
            color: var(--primary);
            font-weight: 700;
            margin: 0;
            font-size: 28px;
        }

        .date-display {
            color: var(--gray);
            font-size: 14px;
            background: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card:nth-child(1)::before { background-color: var(--primary); }
        .stat-card:nth-child(2)::before { background-color: var(--secondary); }
        .stat-card:nth-child(3)::before { background-color: var(--accent); }
        .stat-card:nth-child(4)::before { background-color: var(--danger); }
        .stat-card:nth-child(5)::before { background-color: #8B5CF6; }
        .stat-card:nth-child(6)::before { background-color: #06B6D4; }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-card-title {
            font-size: 16px;
            color: var(--gray);
            font-weight: 500;
            margin: 0;
        }

        .stat-card-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .stat-card:nth-child(1) .stat-card-icon { background-color: var(--primary); }
        .stat-card:nth-child(2) .stat-card-icon { background-color: var(--secondary); }
        .stat-card:nth-child(3) .stat-card-icon { background-color: var(--accent); }
        .stat-card:nth-child(4) .stat-card-icon { background-color: var(--danger); }
        .stat-card:nth-child(5) .stat-card-icon { background-color: #8B5CF6; }
        .stat-card:nth-child(6) .stat-card-icon { background-color: #06B6D4; }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-card-change {
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .positive { color: var(--secondary); }
        .negative { color: var(--danger); }

        .dashboard-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            font-size: 20px;
            margin: 0;
        }

        .view-all {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s;
        }

        .view-all:hover {
            color: var(--primary);
        }

        .hired-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .hired-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-light);
        }

        .hired-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .hired-card-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-light);
        }

        .hired-card-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
            font-size: 16px;
        }

        .hired-card-details {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .hired-card-date {
            font-size: 12px;
            color: var(--gray);
            margin-top: 10px;
            font-style: italic;
        }

        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            border: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--gray-light);
        }

        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        .pagination .page-link {
            color: var(--primary);
            border: 1px solid var(--gray-light);
            padding: 8px 15px;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .pagination .page-link:hover {
            background-color: var(--gray-light);
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .hired-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .hired-cards {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
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
    
    <!-- MAIN CONTENT -->
    <main class="main-content">
         <div class="main-content-header">
            <div>
                <h1>Dashboard Overview</h1>
                <p class="welcome-text">Welcome back, <?php echo $managername; ?>! Here's what's happening today.</p>
            </div>
            <?php date_default_timezone_set('Asia/Manila'); ?>
            <div class="text-end">
    <p class="text-muted mb-1"><?php echo date('l, F j, Y'); ?></p>
    <p class="text-muted"><?php echo date('h:i A'); ?></p>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Employees</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $employees; ?></div>
                <div class="stat-card-change positive">
                   
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Applicants</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $applicants; ?></div>
                <div class="stat-card-change positive">
                    
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Pending Requests</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-file-lines"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $pendingRequests; ?></div>
                <div class="stat-card-change negative">
                    
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Hirings</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $hirings; ?></div>
                <div class="stat-card-change positive">
                    
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Pending Applicants</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $pendingApplicants; ?></div>
                <div class="stat-card-change negative">
                   
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Employees On Leave</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $onLeaveCount; ?></div>
                <div class="stat-card-change positive">
                  
                </div>
            </div>
        </div>

        <!-- NEWLY HIRED SECTION -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Newly Hired Employees</h2>
                <a href="Newly-Hired.php" class="view-all">View All <i class="fas fa-chevron-right ms-1"></i></a>
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
                            <img src="Uploads/<?php echo $pic; ?>" alt="Profile Picture" class="hired-card-img">
                            <h4 class="hired-card-name"><?php echo $hired['fullName']; ?></h4>
                            <p class="hired-card-details"><strong>Position:</strong> <?php echo $hired['position_applied']; ?></p>
                            <p class="hired-card-details"><strong>Department:</strong> <?php echo $hired['department']; ?></p>
                            <p class="hired-card-date">Hired: <?php echo date("F d, Y", strtotime($hired['hired_at'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4 w-100">
                        <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No newly hired employees yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RECENT JOB POSTS SECTION -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Recent Job Posts</h2>
                <a href="Manager_Vacancies.php" class="view-all">View All <i class="fas fa-chevron-right ms-1"></i></a>
            </div>

            <div class="table-container">
                <table class="table table-hover mb-0">
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
                        <?php if ($recentVacanciesQuery && $recentVacanciesQuery->num_rows > 0): ?>
                            <?php
                            $i = $start + 1;
                            while ($vacancy = $recentVacanciesQuery->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $vacancy['position_title']; ?></td>
                                    <td><?php echo $vacancy['deptName']; ?></td>
                                    <td><?php echo $vacancy['employment_type']; ?></td>
                                    <td><?php echo $vacancy['vacancy_count']; ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $vacancy['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-briefcase fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No recent job posts available.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Button -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                <i class="fas fa-chevron-left me-1"></i> Previous
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <li class="page-item <?php echo ($page >= $pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                Next <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Simple animation for stat cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });
    </script>
</body>

</html>