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
$countAdmin   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_announcement WHERE is_active = 1"))['total'];
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
  --primary: #6674cc;
  --primary-dark: #4c5ecf;
  --primary-light: #f0f2ff;
  --secondary: #3b82f6;
  --accent-pink: #ec4899;
  --accent-red: #dc2626;
  --accent-green: #10b981;
  --text-dark: #111827;
  --text-light: #6b7280;
  --bg-light: #f8fafc;
  --card-bg: #ffffff;
  --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
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






/* Main Content */
.main-content {
  margin-left: 280px;
  padding: 30px;
  flex-grow: 1;
  box-sizing: border-box;
  min-height: 100vh;
}

/* Welcome Card */
.welcome-card {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
  background: rgba(0, 0, 0, 0.05);
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

/* Card Color Variants */
.card.salary { 
  border-left-color: var(--secondary); 
}
.card.salary i { color: var(--secondary); }

.card.attendance { 
  border-left-color: var(--accent-pink); 
}
.card.attendance i { color: var(--accent-pink); }

.card.requests { 
  border-left-color: var(--accent-red); 
}
.card.requests i { color: var(--accent-red); }

.card.announcements { 
  border-left-color: var(--accent-green); 
}
.card.announcements i { color: var(--accent-green); }

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
  color: var(--primary);
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
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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
  background-color: var(--primary-light);
}

.announcement-table td {
  padding: 16px 20px;
  color: var(--text-dark);
}

.announcement-table button {
  background-color: var(--primary);
  border: none;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.announcement-table button:hover {
  background-color: var(--primary-dark);
  transform: translateY(-2px);
}

/* Modal Styling */
.modal-content {
  border-radius: var(--border-radius);
  overflow: hidden;
  border: none;
  box-shadow: var(--shadow-hover);
}

.modal-header {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  border-bottom: none;
  padding: 20px 25px;
}

.modal-title {
  font-weight: 600;
  font-size: 20px;
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
  
  .main-content {
    margin-left: 0;
  }
  
  .announcement-table {
    font-size: 14px;
  }
  
  .announcement-table th, 
  .announcement-table td {
    padding: 12px 10px;
  }
}



.sidebar-logo img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 3px solid white;
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
        font-size: 18px;
        font-weight: bold;
        margin: 15px 0 5px 15px;
        text-transform: uppercase;
        color: white;
      }
</style>
</head>

<body>

<div class="sidebar">
  <div class="sidebar-logo">
    <a href="Employee_Profile.php" class="profile">
      <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
           alt="Profile" class="sidebar-profile-img">
    </a>
    <div class="sidebar-name"><p><?php echo "Welcome, $employeename"; ?></p></div>
  </div>

  <ul class="nav">
    <h4 class="menu-board-title">Menu Board</h4>
    <li class="active"><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> <span>Dashboard</span></a></li>
    <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Salary Slip</span></a></li>
    <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> <span>Requests</span></a></li>
    <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
  </ul>
</div>

<main class="main-content">
  <div class="welcome-card">
    <h3>Welcome to Employee Dashboard</h3>
    <p>Here's a quick summary of your payslip, requests, and announcements.</p>
  </div>

  <div class="card-container">
    <div class="card salary">
      <i class="fa-solid fa-folder"></i>
      <div class="info">
        <h2>Salary Slip</h2>
        <p>0</p>
      </div>
    </div>
    <div class="card attendance">
      <i class="fa-solid fa-chart-column"></i>
      <div class="info">
        <h2>Attendance</h2>
        <p>0</p>
      </div>
    </div>
    <div class="card requests">
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

            <?php while ($row = mysqli_fetch_assoc($adminResult)) { ?>
            <tr>
              <td><?php echo $row['title']; ?></td>
              <td>Admin</td>
              <td><?php echo date("M d, Y", strtotime($row['date_posted'])); ?></td>
              <td><a href="?view=admin&id=<?php echo $row['id']; ?>"><button>View</button></a></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $announcement['title'] ?? ''; ?></h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($announcement) { ?>
          <p><strong>Date:</strong> <?php echo date("F d, Y", strtotime($announcement['date_posted'])); ?></p>
          <p><?php echo nl2br($announcement['message']); ?></p>

          <?php if (!empty($announcement['start_date']) || !empty($announcement['end_date'])) { ?>
          <hr>
          <h6 class="fw-bold text-success">Related Leave Setting</h6>

          <?php $start = !empty($announcement['start_date']) ? date("m/d/Y", strtotime($announcement['start_date'])) : null; ?>
          <?php $end   = !empty($announcement['end_date']) ? date("m/d/Y", strtotime($announcement['end_date'])) : null; ?>
          <p><strong>Filing Duration:</strong> <?php echo ($start && $end) ? "$start to $end" : 'N/A'; ?></p>

          <p><strong>Slots:</strong> <?php echo isset($announcement['employee_limit']) ? $announcement['employee_limit'] : 'N/A'; ?></p>

          <p><strong>Expires:</strong> <?php echo $end ? $end : 'N/A'; ?></p>
          <?php } ?>

          <br>
          <p><strong>Posted By:</strong><br>
          HR Manager, <br>
          <?php echo $announcement['posted_by']; ?><br>
          </p>
        <?php } ?>
      </div>
      <div class="modal-footer">
        <a href="Employee_Dashboard.php" class="btn btn-secondary">Close</a>
      </div>
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
</body>
</html>