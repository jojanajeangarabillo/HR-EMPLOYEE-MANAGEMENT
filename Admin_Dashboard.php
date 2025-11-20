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
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Employee'");
$employees = ($employeeQuery && $row = $employeeQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Applicants
$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
$applicants = ($applicantQuery && $row = $applicantQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Pending Applicants
$pendingQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE status = 'Pending'");
$pendingApplicants = ($pendingQuery && $row = $pendingQuery->fetch_assoc()) ? $row['count'] : 0;

// Count Requests
$requestQuery = $conn->query("SELECT COUNT(*) AS count FROM employee_request");
$requests = ($requestQuery && $row = $requestQuery->fetch_assoc()) ? $row['count'] : 0;

//fetch pending applicants
$pending_applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE id = '0'");
if ($pending_applicantQuery && $row = $pending_applicantQuery->fetch_assoc()) {
    $pending_applicantQuery = $row['count'];
}

// Fetch total number of positions for Hirings (status = 'On-Going' or 'To Post')
$hiringsQuery = $conn->query("
    SELECT SUM(vacancy_count) AS count 
    FROM vacancies 
    WHERE status IN ('On-Going', 'To Post')
");
if ($hiringsQuery && $row = $hiringsQuery->fetch_assoc()) {
    $hirings = $row['count'] ?? 0; 
}

// Pagination Setup
$limit = 5; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Count total rows
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM vacancies WHERE status = 'On-Going'");
$total = ($totalQuery && $row = $totalQuery->fetch_assoc()) ? $row['total'] : 0;

$pages = ceil($total / $limit);

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

    <style>
        body { font-family: 'Poppins', sans-serif; margin:0; display:flex; background-color:#f1f5fc; color:#111827; }
        .main-content { padding:40px 30px; margin-left:220px; display:flex; flex-direction:column; }
        .main-content-header h1 { padding:25px 0; margin-bottom:40px; color:#1E3A8A; }
        .stats { display:flex; gap:40px; flex-wrap:wrap; margin-left:0; }
        .section { padding:25px 30px; border-radius:15px; border-top:4px solid #1E3A8A; width:350px; height:120px; background:white; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
        .section label { font-size:20px; }
        .section h3 { color:#1E3A8A; margin-top:15px; font-size:25px; }
        .job-posts h2 { margin-top:60px; margin-bottom:20px; color:#1E3A8A; }
        .hired-section h2 {
    margin-top: 40px;
    margin-bottom: 20px;
    color: #1E3A8A;
}

.hired-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.hired-card {
    width: 250px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    text-align: center;
    transition: transform .2s;
}

.hired-card:hover {
    transform: translateY(-5px);
}

.hired-card img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 10px;
    border: 3px solid #1E3A8A;
}

.hired-card h4 {
    margin-bottom: 5px;
    font-size: 20px;
    color: #1E3A8A;
}

.hired-card p {
    margin: 0;
    font-size: 14px;
    color: #374151;
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
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i> Vacancies</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
            <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Dashboard Overview</h1>
        </div>

        <div class="stats">
            <div class="section">
                <label>Employees</label>
                <h3><?php echo $employees; ?></h3>
            </div>
            <div class="section">
                <label>Applicants</label>
                <h3><?php echo $applicants; ?></h3>
            </div>
            <div class="section">
                <label>Pending Applicants</label>
                <h3><?php echo $pendingApplicants; ?></h3>
            </div>
            <div class="section">
                <label>Requests</label>
                <h3><?php echo $requests; ?></h3>
            </div>
            <div class="section">
                <label>Hirings</label>
                <h3><?php echo $hirings; ?></h3>
            </div>
        </div>

        <div class="hired-section">
    <h2>Newly Hired Employees</h2>

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
                    <p><strong>Hired Date:</strong> 
                        <?php echo date("F d, Y", strtotime($hired['hired_at'])); ?>
                    </p>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p>No newly hired employees yet.</p>
        <?php endif; ?>
    </div>
</div>


     <div class="job-posts">
    <h2>Recent Job Posts</h2>

    <table class="table table-striped table-bordered" style="background:white; border-radius:10px; overflow:hidden;">
        <thead class="table-primary">
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
                    <td colspan="6" class="text-center">No recent job posts available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <nav>
        <ul class="pagination justify-content-center">

            <!-- Previous Button -->
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
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
                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
            </li>

        </ul>
    </nav>

</div>


    </main>
</body>
</html>
