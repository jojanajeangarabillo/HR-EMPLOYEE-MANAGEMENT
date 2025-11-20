<?php
session_start();
require 'admin/db.connect.php';

// -------------------------------
// 1. Get Applicant ID from Session
// -------------------------------
$applicantID = $_SESSION['applicant_employee_id'] ?? null;

if (!$applicantID) {
    die("Applicant ID not found in session.");
}

// -------------------------------
// 2. Fetch Applicant Basic Info (Full Name + Picture)
// -------------------------------
$stmt = $conn->prepare("SELECT fullName, profile_pic FROM applicant WHERE applicantID = ?");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $applicantname = $row['fullName'];
    $profile_picture = !empty($row['profile_pic'])
        ? "uploads/applicants/" . $row['profile_pic']
        : "uploads/employees/default.png";
} else {
    $applicantname = "Applicant";
    $profile_picture = "uploads/employees/default.png";
}


// Fetch application counts by status
$status_counts = [
    'Pending' => 0,
    'Initial Interview' => 0,
    'Final Interview' => 0,
    'Rejected' => 0
];

// Get counts from applications table
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM applications 
    WHERE applicantID = ? 
    GROUP BY status
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']] = $row['count'];
    }
}


// Rejected count from rejected_applications table
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM rejected_applications 
    WHERE applicantID = ?
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $status_counts['Rejected'] = $row['count'];
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
?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="applicant.css">

    <!-- Internal CSS for dashboard contents -->
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .main-content {
            flex: 1;
            padding: 30px 80px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .sidebar-profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .sidebar-profile-img:hover {
            transform: scale(1.05);
        }
        /* Welcome Box */
        .welcome-box {
            background-color: #1E3A8A;
            color: white;
            padding: 33px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1200px;
            height: 100px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            font-size: 20px;
            font-weight: 500;
        }

        /* Application Status */
        .status-section {
            display: flex;
            margin-left: 200px;
            flex-direction: column;
            gap: 20px;
        }

        .status-section h3 {
            margin-left: 200px;
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }

        .status-cards {
            display: flex;
            gap: 40px;
        }

        .status-card {

            background-color: #2563EB;
            color: #fff;
            width: 140px;
            height: 110px;
            border-radius: 18px;
            text-align: center;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .status-card:hover {
            transform: translateY(-3px);
        }

        .status-card p {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
        }

        .status-card h2 {
            margin: 6px 0 0 0;
            font-size: 24px;
            font-weight: 600;
        }

        /* Notifications */
        .notifications-section {
            height: 200px;
            width: 1200px;
            margin-left: 200px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .notifications-section h3 {
            margin-left: 200px;
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }

        .notification-box {
            background-color: #dbe2f0;
            border-radius: 15px;
            height: 100px;
            width: 1200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar-name {
        display: flex;
        justify-content: center; 
        align-items: center;      
        text-align: center;       
        color: white;
        padding: 10px;
        margin-bottom: 30px;
        font-size: 18px; 
        flex-direction: column; 
        }

       
        .stats { display:flex; gap:40px; flex-wrap:wrap; margin-left:0; }
        .section { padding:25px 30px; border-radius:15px; border-top:4px solid #1E3A8A; width:350px; height:120px; background:white; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
        .section label { font-size:20px; }
        .section h3 { color:#1E3A8A; margin-top:15px; font-size:25px; }
        .job-posts h2 { margin-top:60px; margin-bottom:20px; color:#1E3A8A; }

    </style>


</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
   <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
     <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>" 
     alt="Profile" class="sidebar-profile-img">
    </a>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $applicantname"; ?></p>
    </div>

        <ul class="nav">
            <li class="active"><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
            <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
        </ul>
    </div>

    </div>


    <!-- Main Content -->
    <main class="main-content">
        <div class="welcome-box">
            <p><?php echo "Welcome back, $applicantname"; ?></p>
        </div>

        <div class="status-section">
            <h3>Application status</h3>
            <div class="status-cards">
                <div class="status-card">
                    <p>Pending</p>
                     <h2><?php echo $status_counts['Pending']; ?></h2>
                </div>
                <div class="status-card">
                    <p>Initial Interview</p>
                    <h2><?php echo $status_counts['Initial Interview']; ?></h2>
                </div>
                <div class="status-card">
                    <p>Final Interview</p>
                    <h2><?php echo $status_counts['Final Interview']; ?></h2>
                </div>
                <div class="status-card">
                    <p>Rejected</p>
                     <h2><?php echo $status_counts['Rejected']; ?></h2>
                </div>
            </div>
        </div>

        <div class="notifications-section">
            <h3>Notifications</h3>
            <div class="notification-box"></div>
        </div>
    </div>  

    

    </main>   
</body>

</html>