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

// Fetch announcements
$managerResult = mysqli_query($conn, "SELECT * FROM manager_announcement ORDER BY date_posted DESC");
$adminResult = mysqli_query($conn, "SELECT * FROM admin_announcement ORDER BY date_posted DESC");

//total announcements
$countManager = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM manager_announcement WHERE is_active = 1"))['total'];
$countAdmin   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_announcement WHERE is_active = 1"))['total'];
$totalAnnouncements = $countManager + $countAdmin;

// For modal view
$openModal = false;
$announcement = null;

if (isset($_GET['view']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $type = $_GET['view'];

    if ($type == "manager") {
        $query = "
            SELECT ma.title, ma.message, ma.date_posted, ma.posted_by,
                   ls.leave_type, ls.duration, ls.employee_limit, ls.time_limit
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
/* YOUR ORIGINAL CSS UNCHANGED */
body { font-family:'Poppins','Roboto',sans-serif; margin:0; display:flex; background-color:#f1f5fc; color:#111827; }
h1 { font-family:'Roboto',sans-serif; font-size:35px; color:white; text-align:center; }
.menu-board-title { font-size:18px; font-weight:bold; margin:15px 0 5px 15px; text-transform:uppercase; color:white; }
.sidebar-logo { display:flex; flex-direction:column; align-items:center; gap:30px; margin-right:10px; }
.sidebar-logo img { width:100px; height:100px; border-radius:50%; border:3px solid white; }
.sidebar-name { display:flex; justify-content:center; align-items:center; text-align:center; color:white; padding:10px; margin-bottom:30px; font-size:18px; flex-direction:column; }
.main-content { margin-left:220px; padding:40px 30px; background-color:#f1f5fc; flex-grow:1; box-sizing:border-box; }
.welcome-card { background-color:#6674cc; color:white; padding:20px; border-radius:10px; margin-bottom:30px; }
.card-container { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:40px; }
.card { display:flex; align-items:center; justify-content:flex-start; background-color:#f3f3f9; border-radius:10px; padding:20px; width:500px; height:150px; box-shadow:0 2px 5px rgba(0,0,0,0.1); transition:transform 0.3s; }
.card:hover { transform:translateY(-5px); }
.card i { font-size:35px; margin-right:20px; color:#1E3A8A; }
.info h2 { font-size:18px; font-weight:bold; margin:0; }
.info p { font-size:20px; font-weight:bold; margin:0; }
.salary { border-left:5px solid #3b82f6; }
.attendance { border-left:5px solid #ec4899; }
.requests { border-left:5px solid #dc2626; }
.announcements { border-left:5px solid #3b82f6; }
.announcement-section { margin-top:30px; }
.announcement-section h1 { font-size:24px; font-weight:bold; margin-bottom:15px; display:flex; align-items:center; color:black; }
.announcement-section h1 i { color:#1E3A8A; margin-right:10px; }
.announcement-table { width:100%; border-collapse:collapse; background-color:#6674cc; color:white; border-radius:10px; overflow:hidden; }
.announcement-table th, .announcement-table td { padding:15px; text-align:left; }
.announcement-table th { background-color:#4c5ecf; font-weight:bold; }
.announcement-table td { background-color:#6674cc; }
.announcement-table button { background-color:#1E3A8A; border:none; color:white; padding:8px 15px; border-radius:5px; cursor:pointer; }
.announcement-table button:hover { background-color:#142b66; }
</style>
</head>

<body>

<div class="sidebar">
<div class="sidebar-logo">
<a href="Employee_Profile.php"><img src="Images/profile.png" alt="Profile"></a>
<div class="sidebar-name"><p><?php echo "Welcome, $employeename"; ?></p></div>
</div>

<ul class="nav">
<h4 class="menu-board-title">Menu Board</h4>
<li class="active"><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
<li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> Salary Slip</a></li>
<li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
<li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
</ul>
</div>

<main class="main-content">

<div class="welcome-card">
<h3>Welcome to Employee Dashboard</h3>
<p>Here's a quick summary of your payslip, requests, and announcements.</p>
</div>

<div class="card-container">
<div class="card salary"><i class="fa-solid fa-folder"></i><div class="info"><h2>Salary Slip</h2><p>20</p></div></div>
<div class="card attendance"><i class="fa-solid fa-chart-column"></i><div class="info"><h2>Attendance</h2><p>20</p></div></div>
<div class="card requests"><i class="fa-solid fa-code-branch"></i><div class="info"><h2>Requests</h2><p>5</p></div></div>
<div class="card announcements">
<i class="fa-solid fa-comment"></i>
<div class="info"><h2>Announcements</h2><p><?php echo $totalAnnouncements; ?></p></div>
</div>
</div>

<div class="announcement-section">
<h1><i class="fa-solid fa-bullhorn"></i>Announcements</h1>
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
</main>

<!-- ✅ MODAL -->
<div class="modal fade" id="announcementModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header bg-primary text-white">
<h5 class="modal-title"><?php echo $announcement['title'] ?? ''; ?></h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">

<?php if ($announcement) { ?>

<p><strong>Date:</strong> <?php echo date("F d, Y", strtotime($announcement['date_posted'])); ?></p>

<p><?php echo nl2br($announcement['message']); ?></p>

<?php if (!empty($announcement['leave_type'])) { ?>
<hr>
<h6 class="fw-bold text-success">Related Leave Setting</h6>

<p><strong>Purpose:</strong> <?php echo $announcement['leave_type']; ?></p>

<?php 
$range = explode(" to ", $announcement['duration']);
$start = date("m/d/Y", strtotime($range[0]));
$end   = date("m/d/Y", strtotime($range[1]));
?>
<p><strong>Filing Duration:</strong> <?php echo "$start to $end"; ?></p>

<p><strong>Slots:</strong> <?php echo $announcement['employee_limit']; ?></p>

<p><strong>Leave duration:</strong> <?php echo $announcement['time_limit']; ?></p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($openModal) { ?>
<script>
var modal = new bootstrap.Modal(document.getElementById('announcementModal'));
modal.show();
</script>
<?php } ?>
</body>
</html>
