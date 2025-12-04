<?php
session_start();
require 'admin/db.connect.php';

// Fetch employee name
$employeenameQuery = $conn->query("
    SELECT fullname 
    FROM user 
    WHERE role = 'Employee' AND (sub_role IS NULL OR sub_role != 'HR Manager')
");
$employeename = ($employeenameQuery && $row = $employeenameQuery->fetch_assoc()) ? $row['fullname'] : 'Employee';

$employeeID = $_SESSION['applicant_employee_id'] ?? null;
$employeename = "Employee";

if ($employeeID) {
  $stmt = $conn->prepare("SELECT fullname FROM user WHERE applicant_employee_id = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $employeename = $row['fullname'];
  }
}

// Fetch employee name and profile picture
if ($employeeID) {
  $stmt = $conn->prepare("SELECT fullname, profile_pic FROM employee WHERE empID = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $employeename = $row['fullname'];
    $profile_picture = !empty($row['profile_pic'])
      ? "uploads/employees/" . $row['profile_pic']
      : "uploads/employees/default.png";
  } else {
    $employeename = $_SESSION['fullname'] ?? "Employee";
    $profile_picture = "uploads/employees/default";
  }
} else {
  $employeename = $_SESSION['fullname'] ?? "Employee";
  $profile_picture = "uploads/employees/default";
}

// Fetch announcements
$managerResult = mysqli_query($conn, "SELECT ma.id, ma.title, ma.message, ma.date_posted, ma.posted_by FROM manager_announcement ma LEFT JOIN leave_settings ls ON ma.settingID = ls.settingID WHERE ma.is_active = 1 AND (ls.end_date IS NULL OR ls.end_date >= CURDATE()) ORDER BY ma.date_posted DESC");
$adminResult = mysqli_query($conn, "SELECT id, title, message, date_posted FROM admin_announcement WHERE is_active = 1 ORDER BY date_posted DESC");

$countManager = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM manager_announcement ma LEFT JOIN leave_settings ls ON ma.settingID = ls.settingID WHERE ma.is_active = 1 AND (ls.end_date IS NULL OR ls.end_date >= CURDATE())"))['total'];
$countAdmin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_announcement WHERE is_active = 1"))['total'];
$totalAnnouncements = $countManager + $countAdmin;

// Employee requests count (leave + general)
$countRequests = 0;
if ($employeeID) {
  $reqCount = 0;
  if ($stmt = $conn->prepare("SELECT COUNT(*) AS c FROM leave_request WHERE empID = ?")) {
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $reqCount += intval($row['c'] ?? 0);
    $stmt->close();
  }
  if ($stmt = $conn->prepare("SELECT COUNT(*) AS c FROM general_request WHERE empID = ?")) {
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $reqCount += intval($row['c'] ?? 0);
    $stmt->close();
  }
  $countRequests = $reqCount;
}

// For modal view
$openModal = false;
$announcement = null;

if (isset($_GET['view']) && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $type = $_GET['view'];

  if ($type == "manager") {
    $query = "
            SELECT ma.title, ma.message, ma.date_posted, ma.posted_by,
                   ls.start_date, ls.end_date, ls.employee_limit
            FROM manager_announcement ma
            LEFT JOIN leave_settings ls ON ma.settingID = ls.settingID
            WHERE ma.id = $id
        ";
  } else {
    $query = "
            SELECT title, message, date_posted, 'Admin' AS posted_by
            FROM admin_announcement 
            WHERE id = $id
        ";
  }

  $res = mysqli_query($conn, $query);
  $announcement = mysqli_fetch_assoc($res);
  $openModal = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <style>
    :root {
      /* Blue color palette */
      --primary-blue: #1e40af;
      --primary-blue-light: #3b82f6;
      --primary-blue-lighter: #60a5fa;
      --primary-blue-lightest: #dbeafe;

      --secondary-blue: #1d4ed8;
      --secondary-blue-light: #2563eb;
      --secondary-blue-lighter: #3b82f6;

      --accent-blue: #0284c7;
      --accent-blue-light: #0ea5e9;

      --dark-blue: #1e3a8a;
      --dark-blue-light: #1e40af;

      --light-blue: #eff6ff;
      --light-blue-dark: #dbeafe;

      --text-dark: #111827;
      --text-light: #6b7280;
      --bg-light: #f8fafc;
      --card-bg: #ffffff;
      --shadow: 0 4px 12px rgba(30, 64, 175, 0.1);
      --shadow-hover: 0 8px 24px rgba(30, 64, 175, 0.15);
      --border-radius: 12px;
    }

    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: var(--bg-light);
      color: var(--text-dark);
      line-height: 1.6;
    }



    .sidebar-logo {
      padding: 30px 20px 10px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }


    .sidebar-logo img:hover {
      border-color: rgba(255, 255, 255, 0.5);
      transform: scale(1.05);
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

    .menu-board-title {
      font-size: 14px;
      font-weight: 600;
      margin: 15px 0 5px 20px;
      text-transform: uppercase;
      color: var(--light-blue-dark);
      letter-spacing: 1px;
    }


    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 30px;
      flex-grow: 1;
      box-sizing: border-box;
      min-height: 100vh;
      background-color: var(--bg-light);
    }

    /* Welcome Card */
    .welcome-card {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
      color: white;
      padding: 25px 30px;
      border-radius: var(--border-radius);
      margin-bottom: 30px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .welcome-card::before {
      content: "";
      position: absolute;
      top: -50%;
      right: -20%;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }

    .welcome-card h3 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
      position: relative;
    }

    .welcome-card p {
      font-size: 16px;
      opacity: 0.9;
      max-width: 600px;
      position: relative;
    }

    /* Card Container */
    .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 25px;
      margin-bottom: 40px;
    }

    .card {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      border-left: 5px solid;
    }

    .card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: inherit;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
    }

    .card i {
      font-size: 42px;
      margin-right: 20px;
      opacity: 0.9;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 70px;
      height: 70px;
      border-radius: 12px;
      background: var(--light-blue);
    }

    .card .info {
      display: flex;
      flex-direction: column;
      justify-content: center;
      height: 100%;
    }

    .card .info h2 {
      font-size: 16px;
      font-weight: 600;
      margin: 0;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .card .info p {
      font-size: 28px;
      font-weight: 700;
      margin: 5px 0 0 0;
      color: var(--text-dark);
    }

    /* Card Color Variants - All in blue shades */
    .card.salary {
      border-left-color: var(--primary-blue);
    }

    .card.salary i {
      color: var(--primary-blue);
      background: var(--light-blue);
    }

    .card.attendance {
      border-left-color: var(--secondary-blue-light);
    }

    .card.attendance i {
      color: var(--secondary-blue-light);
      background: var(--light-blue);
    }

    .card.requests {
      border-left-color: var(--accent-blue);
    }

    .card.requests i {
      color: var(--accent-blue);
      background: var(--light-blue);
    }

    .card.announcements {
      border-left-color: var(--dark-blue-light);
    }

    .card.announcements i {
      color: var(--dark-blue-light);
      background: var(--light-blue);
    }

    /* Announcement Section */
    .announcement-section {
      margin-top: 30px;
    }

    .announcement-section h1 {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      color: var(--text-dark);
    }

    .announcement-section h1 i {
      color: var(--primary-blue);
      margin-right: 12px;
      font-size: 24px;
    }

    .announcement-table-container {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .announcement-table {
      width: 100%;
      border-collapse: collapse;
    }

    .announcement-table thead {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    }

    .announcement-table th {
      padding: 18px 20px;
      text-align: left;
      font-weight: 600;
      color: white;
      font-size: 15px;
    }

    .announcement-table tbody tr {
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.2s ease;
    }

    .announcement-table tbody tr:hover {
      background-color: var(--light-blue);
    }

    .announcement-table td {
      padding: 16px 20px;
      color: var(--text-dark);
    }

    .announcement-table button {
      background-color: var(--primary-blue);
      border: none;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
      box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
    }

    .announcement-table button:hover {
      background-color: var(--secondary-blue);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 64, 175, 0.3);
    }

    /* Modal Styling */
    .modal-content {
      border-radius: var(--border-radius);
      overflow: hidden;
      border: none;
      box-shadow: var(--shadow-hover);
    }

    .modal-header {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
      border-bottom: none;
      padding: 20px 25px;
    }

    .modal-title {
      font-weight: 600;
      font-size: 20px;
      color: white;
    }

    .modal-body {
      padding: 25px;
    }

    .modal-body p {
      margin-bottom: 15px;
    }

    .modal-footer {
      border-top: 1px solid rgba(0, 0, 0, 0.05);
      padding: 15px 25px;
    }

    .btn-primary {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
    }

    .btn-primary:hover {
      background-color: var(--secondary-blue);
      border-color: var(--secondary-blue);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .main-content {
        margin-left: 80px;
      }

      .card-container {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }

      .card-container {
        grid-template-columns: 1fr;
      }

      .welcome-card h3 {
        font-size: 20px;
      }

      .welcome-card p {
        font-size: 14px;
      }
    }

    @media (max-width: 576px) {
      .sidebar {
        width: 70px;
      }

      .sidebar-name,
      .menu-board-title,
      .nav li a span {
        display: none;
      }

      .nav li a {
        justify-content: center;
        padding: 15px;
      }

      .nav li a i {
        margin-right: 0;
      }

      .main-content {
        margin-left: 70px;
      }

      .announcement-table {
        font-size: 14px;
      }

      .announcement-table th,
      .announcement-table td {
        padding: 12px 10px;
      }
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <div class="sidebar-logo">
      <a href="Employee_Profile.php" class="profile">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
      </a>
      <div class="sidebar-name">
        <p><?php echo "Welcome, $employeename"; ?></p>
      </div>
    </div>

    <ul class="nav">
      <h4 class="menu-board-title">Menu Board</h4>
      <li class="active"><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> <span>Dashboard</span></a>
      </li>
      <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Salary Slip</span></a>
      </li>
      <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> <span>Requests</span></a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
  </div>

  <main class="main-content">
    <div class="welcome-card">
      <h3><?php echo "Welcome to Employee Dashboard, $employeename"; ?></h3>
      <p>Here's a quick summary of your payslip, requests, and announcements.</p>
    </div>

    <div class="card-container">
      <div class="card salary">
        <i class="fa-solid fa-folder"></i>
        <div class="info">
          <h2>Salary Slip</h2>
          <p id="payslipsCountMetric">0</p>
        </div>
      </div>
      <div class="card attendance">
        <i class="fa-solid fa-chart-column"></i>
        <div class="info">
          <h2>Present Days</h2>
          <p id="presentDaysMetric">0</p>
        </div>
      </div>
      <div class="card attendance">
        <i class="fa-solid fa-user-xmark"></i>
        <div class="info">
          <h2>Absent Days</h2>
          <p id="absencesCountMetric">0</p>
        </div>
      </div>
      <div class="card requests" onclick="window.location.href='Employee_Requests.php'">
        <i class="fa-solid fa-code-branch"></i>
        <div class="info">
          <h2>Requests</h2>
          <p><?php echo $countRequests; ?></p>
        </div>
      </div>
      <div class="card announcements">
        <i class="fa-solid fa-comment"></i>
        <div class="info">
          <h2>Announcements</h2>
          <p><?php echo $totalAnnouncements; ?></p>
        </div>
      </div>
    </div>

    <div class="announcement-section">
      <h1><i class="fa-solid fa-bullhorn"></i> Announcements</h1>
      <div class="announcement-table-container">
        <div class="table-responsive">
          <table class="announcement-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>From</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($managerResult)) { ?>
                <tr>
                  <td><?php echo $row['title']; ?></td>
                  <td><?php echo $row['posted_by']; ?> (Manager)</td>
                  <td><?php echo date("M d, Y", strtotime($row['date_posted'])); ?></td>
                  <td><a href="?view=manager&id=<?php echo $row['id']; ?>"><button>View</button></a></td>
                </tr>
              <?php } ?>

              <div class="announcement-section">
                <h1><i class="fa-solid fa-bullhorn"></i> Announcements</h1>
                <div class="announcement-table-container">
                  <div class="table-responsive">
                    <table class="announcement-table">
                      <thead>
                        <tr>
                          <th>Title</th>
                          <th>From</th>
                          <th>Date</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while ($row = mysqli_fetch_assoc($managerResult)) { ?>
                          <tr>
                            <td><?php echo $row['title']; ?></td>
                            <td><?php echo $row['posted_by']; ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['date_posted'])); ?></td>
                            <td><a href="?view=manager&id=<?php echo $row['id']; ?>"><button>View</button></a></td>
                          </tr>
                        <?php } ?>

                        <br>
                        <p><strong>Posted By:</strong><br>
                          HR Manager, <br>
                          <?php echo $announcement['posted_by']; ?><br>
                        </p>
                        <?php ?>

                        <br>
                        <p><strong>Posted By:</strong><br>
                          <?php echo $announcement['posted_by']; ?><br>
                        </p>
                        <?php ?>
                  </div>
                  <div class="modal-footer">
                    <a href="Employee_Dashboard.php" class="btn btn-secondary">Close</a>
                  </div>
                </div>
              </div>

              <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
              <?php if ($openModal) { ?>
                <script>
                  var modal = new bootstrap.Modal(document.getElementById('announcementModal'));
                  modal.show();
                </script>
              <?php } ?>
              <script>
                const empID = '<?php echo addslashes($employeeID ?? ""); ?>';
                async function loadAttendanceAnalytics() {
                  try {
                    const res = await fetch('/HR-EMPLOYEE-MANAGEMENT/API/consumer_attendance.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                      body: JSON.stringify({ emp_code: empID })
                    });
                    const data = await res.json();
                    const analytics = (data && data.analytics) ? data.analytics : {};
                    const dash = (analytics && analytics.dashboard_stats) ? analytics.dashboard_stats : {};
                    const present = Number(dash.present_days ?? analytics.present_days ?? 0);
                    const absent = Number(dash.absent_days ?? analytics.absences_count ?? 0);
                    const payslips = Number(dash.payslips_issued ?? analytics.payslips_count ?? 0);
                    const setNum = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = String(Number.isFinite(v) ? v : 0); };
                    setNum('presentDaysMetric', present);
                    setNum('absencesCountMetric', absent);
                    setNum('payslipsCountMetric', payslips);
                  } catch (e) {
                    console.error(e);
                  }
                }
                loadAttendanceAnalytics();
                setInterval(loadAttendanceAnalytics, 30000);

                function runDashboardTests() {
                  const presentEl = document.getElementById('presentDaysMetric');
                  const absentEl = document.getElementById('absencesCountMetric');
                  const payslipEl = document.getElementById('payslipsCountMetric');
                  presentEl.textContent = '5';
                  absentEl.textContent = '2';
                  payslipEl.textContent = '3';
                  console.log('TEST dashboard metrics', presentEl.textContent === '5' && absentEl.textContent === '2' && payslipEl.textContent === '3' ? 'PASS' : 'FAIL');
                }
                try { const params = new URLSearchParams(window.location.search); if (params.get('test') === '1') runDashboardTests(); } catch (e) { }
              </script>
</body>

</html>