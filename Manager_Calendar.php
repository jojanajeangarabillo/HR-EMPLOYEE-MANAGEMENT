<?php
session_start();
require 'admin/db.connect.php';

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

$today = date('Y-m-d');
$archTbl = $conn->query("SHOW TABLES LIKE 'leave_request_archive'");
$hasArchive = ($archTbl && $archTbl->num_rows > 0);
if ($hasArchive) {
  $conn->query("INSERT INTO leave_request_archive SELECT * FROM leave_request WHERE status = 'Approved' AND to_date < CURDATE()");
  $conn->query("DELETE FROM leave_request WHERE status = 'Approved' AND to_date < CURDATE()");
}

$leaves = [];
$stmt = $conn->prepare("SELECT empID, fullname, department, position, type_name, request_type_id, request_type_name, leave_type_name, action_by, from_date, to_date, duration FROM leave_request WHERE status = 'Approved' ORDER BY from_date ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $leaves[] = $row; }
$stmt->close();

$filterDept = isset($_GET['dept']) ? $_GET['dept'] : '';
$filterType = isset($_GET['ltype']) ? $_GET['ltype'] : '';
$filterName = isset($_GET['q']) ? $_GET['q'] : '';

$departments = [];
$types = [];
foreach ($leaves as $lv) {
  if (!empty($lv['department'])) $departments[$lv['department']] = true;
  if (!empty($lv['leave_type_name'])) $types[$lv['leave_type_name']] = true;
}

$archives = [];
if ($hasArchive) {
  $aq = $conn->query("SELECT empID, fullname, department, position, type_name, request_type_id, request_type_name, leave_type_name, action_by, from_date, to_date, duration FROM leave_request_archive ORDER BY to_date DESC LIMIT 200");
  if ($aq) { while ($r = $aq->fetch_assoc()) { $archives[] = $r; } }
  foreach ($archives as $av) {
    if (!empty($av['department'])) $departments[$av['department']] = true;
    if (!empty($av['leave_type_name'])) $types[$av['leave_type_name']] = true;
  }
}

$allDept = array_keys($departments);
sort($allDept);
$allTypes = array_keys($types);
sort($allTypes);

$displayLeaves = array_values(array_filter($leaves, function($lv) use ($filterDept, $filterType, $filterName) {
  if ($filterDept !== '' && $lv['department'] !== $filterDept) return false;
  if ($filterType !== '' && $lv['leave_type_name'] !== $filterType) return false;
  if ($filterName !== '' && stripos($lv['fullname'], $filterName) === false) return false;
  return true;
}));

$displayArchives = array_values(array_filter($archives, function($lv) use ($filterDept, $filterType, $filterName) {
  if ($filterDept !== '' && $lv['department'] !== $filterDept) return false;
  if ($filterType !== '' && $lv['leave_type_name'] !== $filterType) return false;
  if ($filterName !== '' && stripos($lv['fullname'], $filterName) === false) return false;
  return true;
}));

$eventsByDate = [];
foreach ($leaves as $lv) {
  $start = $lv['from_date'] ? new DateTime($lv['from_date']) : null;
  $end = $lv['to_date'] ? new DateTime($lv['to_date']) : null;
  if (!$start || !$end) continue;
  for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
    $key = $d->format('Y-m-d');
    if (!isset($eventsByDate[$key])) $eventsByDate[$key] = [];
    $eventsByDate[$key][] = [
      'empID' => $lv['empID'],
      'fullname' => $lv['fullname'],
      'department' => $lv['department'],
      'position' => $lv['position'],
      'leave_type_name' => $lv['leave_type_name']
    ];
  }
}

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = intval($firstDay->format('t'));
$startWeekday = intval($firstDay->format('w'));
$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear = $month === 12 ? $year + 1 : $year;


?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Calendar</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <!-- External Sidebar CSS -->
  <link rel="stylesheet" href="manager-sidebar.css">

  <style>
    /* MAIN PAGE LAYOUT */
    body {
      background-color: #F4F6F8;
      display: flex;
      font-family: "Poppins", sans-serif;
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

    /* MAIN CONTENT AREA */
    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column
    }

    .main-content h1 {
      color: #1E3A8A;
      font-weight: 700;
      margin-bottom: 25px;
    }

    /* TABLE DESIGN */
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
      text-align: center;
      padding: 12px;
      border: 1px solid #ddd;
    }

    th {
      background-color: #1E3A8A;
      color: white;
      font-weight: 600;
    }

    td {
      color: #333;
    }

    tbody tr:hover {
      background-color: #F2F6FF;
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
  <div class="main-content">
    <div class="main-content-header">
      <h1><i class="fa-solid fa-calendar-days"></i> Manager Calendar</h1>
    </div>

    <form method="GET" class="d-flex align-items-end mb-3" style="gap:12px;">
      <div>
        <label class="form-label">Department</label>
        <select name="dept" class="form-select" style="min-width:220px;">
          <option value="">All</option>
          <?php foreach ($allDept as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>" <?= ($filterDept === $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Leave Type</label>
        <select name="ltype" class="form-select" style="min-width:220px;">
          <option value="">All</option>
          <?php foreach ($allTypes as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= ($filterType === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Employee</label>
        <input type="text" name="q" class="form-control" placeholder="Search name" value="<?= htmlspecialchars($filterName) ?>" />
      </div>
      <div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="Manager_Calendar.php" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>

    <div class="d-flex align-items-center mb-3" style="gap:12px;">
      <a class="btn btn-outline-primary" href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"><i class="fa-solid fa-chevron-left"></i></a>
      <div class="fw-bold"><?= date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $month))) ?></div>
      <a class="btn btn-outline-primary" href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-primary">
          <tr>
            <th>Sun</th>
            <th>Mon</th>
            <th>Tue</th>
            <th>Wed</th>
            <th>Thu</th>
            <th>Fri</th>
            <th>Sat</th>
          </tr>
        </thead>
        <tbody>
          <?php $day = 1; $printed = false; for ($row = 0; $row < 6; $row++): ?>
            <tr>
              <?php for ($col = 0; $col < 7; $col++): ?>
                <?php if ($row === 0 && $col < $startWeekday): ?>
                  <td class="bg-light"></td>
                <?php elseif ($day > $daysInMonth): ?>
                  <td class="bg-light"></td>
                <?php else: ?>
                  <?php $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day); $items = $eventsByDate[$dateStr] ?? []; ?>
                  <td class="<?= !empty($items) ? 'bg-warning-subtle' : '' ?>" style="min-height:90px;">
                    <div class="fw-semibold"><?= $day ?></div>
                    <?php if (!empty($items)): ?>
                      <div class="badge bg-warning text-dark mb-2"><?= count($items) ?> on leave</div>
                      <button type="button" class="btn btn-sm btn-primary view-day" data-date="<?= $dateStr ?>" data-items='<?= json_encode($items) ?>'>View</button>
                    <?php endif; ?>
                  </td>
                  <?php $day++; ?>
                <?php endif; ?>
              <?php endfor; ?>
            </tr>
            <?php if ($day > $daysInMonth) break; ?>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <div class="card shadow-sm p-3 mt-4">
      <h5 class="mb-3" style="color:#1E3A8A;">Approved Leaves</h5>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Position</th>
              <th>Department</th>
              <th>Type Name</th>
              <th>Request Type</th>
              <th>Request Type Name</th>
              <th>Leave Type</th>
              <th>Action By</th>
              <th>From</th>
              <th>To</th>
              <th>Duration</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($displayLeaves as $lv): ?>
              <?php 
                $from = $lv['from_date'];
                $to = $lv['to_date'];
                $st = ($from && $to && $today >= $from && $today <= $to) ? 'Effective' : (($from && $today < $from) ? 'Upcoming' : 'Completed');
              ?>
              <tr>
                <td><?= htmlspecialchars($lv['fullname']) ?></td>
                <td><?= htmlspecialchars($lv['position']) ?></td>
                <td><?= htmlspecialchars($lv['department']) ?></td>
                <td><?= htmlspecialchars($lv['type_name'] ?? 'N/A') ?></td>
                <td><?= (int)($lv['request_type_id'] ?? 0) ?></td>
                <td><?= htmlspecialchars($lv['request_type_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lv['leave_type_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lv['action_by'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($from ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($to ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($lv['duration'] ?? 'N/A') ?></td>
                <td>
                  <?php if ($st === 'Effective'): ?>
                    <span class="badge bg-success">Effective</span>
                  <?php elseif ($st === 'Upcoming'): ?>
                    <span class="badge bg-info text-dark">Upcoming</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Completed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($hasArchive): ?>
      <div class="card shadow-sm p-3 mt-4">
        <h5 class="mb-3" style="color:#1E3A8A;">Recently Archived Leaves</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Department</th>
                <th>Type Name</th>
                <th>Request Type</th>
                <th>Request Type Name</th>
                <th>Leave Type</th>
                <th>Action By</th>
                <th>From</th>
                <th>To</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($displayArchives)): foreach ($displayArchives as $lv): ?>
                <tr>
                  <td><?= htmlspecialchars($lv['fullname']) ?></td>
                  <td><?= htmlspecialchars($lv['position']) ?></td>
                  <td><?= htmlspecialchars($lv['department']) ?></td>
                  <td><?= htmlspecialchars($lv['type_name'] ?? 'N/A') ?></td>
                  <td><?= (int)($lv['request_type_id'] ?? 0) ?></td>
                  <td><?= htmlspecialchars($lv['request_type_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lv['leave_type_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lv['action_by'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lv['from_date'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lv['to_date'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($lv['duration'] ?? 'N/A') ?></td>
                  <td><span class="badge bg-secondary">Archived</span></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="12" class="text-muted">No archived leaves.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
<div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Employees on Leave</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="dayList"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.view-day').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var items = [];
      try { items = JSON.parse(this.getAttribute('data-items')); } catch(e) {}
      var list = document.getElementById('dayList');
      var html = '';
      items.forEach(function(it) {
        html += '<div class="border rounded p-2 mb-2">' +
                '<div><strong>' + (it.fullname || '') + '</strong> (' + (it.empID || '') + ')</div>' +
                '<div>' + (it.department || 'N/A') + ' • ' + (it.position || 'N/A') + '</div>' +
                '<div>Leave Type: ' + (it.leave_type_name || 'N/A') + '</div>' +
                '</div>';
      });
      list.innerHTML = html || '<p class="text-muted">No data</p>';
      var m = new bootstrap.Modal(document.getElementById('dayModal'));
      m.show();
    });
  });
});
</script>
</body>

</html>
