<?php
session_start();
require 'admin/db.connect.php';

// Count Employees
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Employee'");
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
if ($plRes && $r = $plRes->fetch_assoc()) $pendingLeaves = (int)$r['c'];
$pendingGeneral = 0;
$pgRes = $conn->query("SELECT COUNT(*) AS c FROM general_request WHERE status = 'Pending'");
if ($pgRes && $r2 = $pgRes->fetch_assoc()) $pendingGeneral = (int)$r2['c'];
$pendingRequests = $pendingLeaves + $pendingGeneral;

$onLeaveCount = 0;
$olRes = $conn->query("SELECT COUNT(DISTINCT empID) AS count FROM leave_request WHERE status = 'Approved'");
if ($olRes && $row = $olRes->fetch_assoc()) $onLeaveCount = (int)$row['count'];



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
        "Vacancies" => "Manager_Vacancies.php",
        "Job Post" => "Manager-JobPosting.php",
        "Calendar" => "Manager_Calendar.php",
        "Approvals" => "Manager_Approvals.php",
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

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

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
        <li><a href="<?php echo $link; ?>"><i class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
    <!-- MAIN CONTENT -->
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
                <label>Pending Requests</label>
                <h3><?php echo $pendingRequests; ?></h3>
            </div>

            <div class="section">
                <label>Hirings</label>
                <h3><?php echo $hirings; ?></h3>
            </div>

            <div class="section">
                <label>Pending Applicants</label>
               <h3><?php echo $pendingApplicants; ?></h3>
            </div>
            <div class="section">
                <label>Employees On Leave</label>
                <h3><?php echo $onLeaveCount; ?></h3>
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