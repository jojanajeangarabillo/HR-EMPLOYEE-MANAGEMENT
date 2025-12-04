<?php
session_start();
require 'admin/db.connect.php';

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null;
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

// Statistics
$totalLeaves = count($displayLeaves);
$totalArchived = count($displayArchives);
$effectiveLeaves = 0;
$upcomingLeaves = 0;
$completedLeaves = 0;

foreach ($displayLeaves as $lv) {
  $from = $lv['from_date'];
  $to = $lv['to_date'];
  if ($from && $to && $today >= $from && $today <= $to) {
    $effectiveLeaves++;
  } elseif ($from && $today < $from) {
    $upcomingLeaves++;
  } else {
    $completedLeaves++;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Calendar</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="manager-sidebar.css">

  <style>
    /* MAIN PAGE LAYOUT */
    body {
      background-color: #F4F6F8;
      display: flex;
      font-family: "Poppins", sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Fix sidebar positioning */
    .sidebar {
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      z-index: 1000;
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
      flex: 1;
      padding: 30px;
      margin-left: 220px;
      min-height: 100vh;
      width: calc(100% - 220px);
    }

    /* Header Styling */
    .page-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      padding: 25px 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
    }

    .page-header h1 {
      color: white;
      margin: 0;
      font-size: 28px;
      font-weight: 700;
    }

    .page-header .subtitle {
      opacity: 0.9;
      font-size: 14px;
      margin-top: 5px;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border-left: 4px solid #1E3A8A;
      transition: transform 0.2s ease;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .stat-card h6 {
      color: #666;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }

    .stat-card .stat-number {
      font-size: 28px;
      font-weight: 700;
      color: #1E3A8A;
    }

    /* Calendar Container */
    .calendar-container {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      margin-bottom: 30px;
    }

    /* Calendar Table */
    .calendar-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 8px;
    }

    .calendar-table th {
      background: #F8FAFC;
      color: #4B5563;
      font-weight: 600;
      padding: 15px;
      border-radius: 8px;
      font-size: 14px;
      text-align: center;
    }

    .calendar-table td {
      background: white;
      border: 2px solid #E5E7EB;
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      vertical-align: top;
      min-height: 120px;
      transition: all 0.2s ease;
      position: relative;
    }

    .calendar-table td:hover {
      border-color: #3B82F6;
      transform: translateY(-1px);
    }

    .calendar-table td.has-leave {
      background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
      border-color: #F59E0B;
    }

    .calendar-table td.has-leave:hover {
      background: linear-gradient(135deg, #FDE68A 0%, #FCD34D 100%);
    }

    .calendar-day {
      font-size: 16px;
      font-weight: 600;
      color: #1F2937;
      margin-bottom: 8px;
    }

    .leave-indicator {
      background: #DC2626;
      color: white;
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 12px;
      display: inline-block;
      margin-top: 5px;
    }

    /* View Details Button */
    .view-day-btn {
      background: #1E3A8A;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      margin-top: 8px;
      width: 100%;
    }

    .view-day-btn:hover {
      background: #3B82F6;
      transform: scale(1.02);
    }

    /* Filter Section */
    .filter-section {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    /* Month Navigation */
    .month-nav {
      background: white;
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .month-nav-btn {
      background: #F3F4F6;
      border: none;
      color: #4B5563;
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .month-nav-btn:hover {
      background: #E5E7EB;
      color: #1E3A8A;
    }

    .month-title {
      font-size: 20px;
      font-weight: 600;
      color: #1E3A8A;
      margin: 0;
    }

    /* Data Tables */
    .data-table {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      margin-bottom: 30px;
    }

    .data-table-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      padding: 20px 25px;
      border-bottom: 1px solid #E5E7EB;
    }

    .data-table-header h5 {
      margin: 0;
      font-weight: 600;
    }

    .data-table-content {
      padding: 0;
    }

    /* Table Styling */
    .table-custom {
      margin: 0;
    }

    .table-custom thead th {
      background: #F8FAFC;
      color: #4B5563;
      font-weight: 600;
      padding: 15px;
      border: none;
      border-bottom: 2px solid #E5E7EB;
    }

    .table-custom tbody td {
      padding: 12px 15px;
      vertical-align: middle;
      border-color: #E5E7EB;
    }

    .table-custom tbody tr:hover {
      background-color: #F9FAFB;
    }

    /* Badge Styling */
    .badge-status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-effective { background: #10B981; color: white; }
    .badge-upcoming { background: #3B82F6; color: white; }
    .badge-completed { background: #6B7280; color: white; }
    .badge-archived { background: #8B5CF6; color: white; }

    /* Modal Styling */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .modal-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      border-radius: 12px 12px 0 0;
      padding: 20px 25px;
      border: none;
    }

    .modal-header .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.8;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
      }
      
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .calendar-table td {
        padding: 10px 5px;
        font-size: 12px;
      }
      
      .view-day-btn {
        font-size: 10px;
        padding: 4px 8px;
      }
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #F1F5F9;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: #CBD5E1;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #94A3B8;
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
    <!-- Page Header -->
    <div class="page-header">
      <h1><i class="fa-solid fa-calendar-days me-3"></i>Leave Calendar</h1>
      <div class="subtitle">Manage and view approved leave requests</div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <h6>Total Active Leaves</h6>
        <div class="stat-number"><?= $totalLeaves ?></div>
      </div>
      <div class="stat-card">
        <h6>Currently Effective</h6>
        <div class="stat-number"><?= $effectiveLeaves ?></div>
      </div>
      <div class="stat-card">
        <h6>Upcoming Leaves</h6>
        <div class="stat-number"><?= $upcomingLeaves ?></div>
      </div>
      <div class="stat-card">
        <h6>Archived Records</h6>
        <div class="stat-number"><?= $totalArchived ?></div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Department</label>
          <select name="dept" class="form-select">
            <option value="">All Departments</option>
            <?php foreach ($allDept as $d): ?>
              <option value="<?= htmlspecialchars($d) ?>" <?= ($filterDept === $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Leave Type</label>
          <select name="ltype" class="form-select">
            <option value="">All Types</option>
            <?php foreach ($allTypes as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= ($filterType === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Employee Name</label>
          <input type="text" name="q" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($filterName) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="d-flex gap-2 w-100">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <i class="fa-solid fa-filter me-2"></i>Apply Filters
            </button>
            <a href="Manager_Calendar.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-rotate-right"></i>
            </a>
          </div>
        </div>
      </form>
    </div>

    <!-- Month Navigation -->
    <div class="month-nav">
      <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="month-nav-btn">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <h2 class="month-title"><?= date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $month))) ?></h2>
      <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="month-nav-btn">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>

    <!-- Calendar -->
    <div class="calendar-container">
      <table class="calendar-table">
        <thead>
          <tr>
            <th>Sunday</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Saturday</th>
          </tr>
        </thead>
        <tbody>
          <?php $day = 1; for ($row = 0; $row < 6; $row++): ?>
            <tr>
              <?php for ($col = 0; $col < 7; $col++): ?>
                <?php if ($row === 0 && $col < $startWeekday): ?>
                  <td class="bg-light"></td>
                <?php elseif ($day > $daysInMonth): ?>
                  <td class="bg-light"></td>
                <?php else: ?>
                  <?php 
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day); 
                    $items = $eventsByDate[$dateStr] ?? []; 
                    $hasLeave = !empty($items);
                    $isToday = ($dateStr === $today);
                  ?>
                  <td class="<?= $hasLeave ? 'has-leave' : '' ?>" style="background: <?= $isToday ? '#EFF6FF' : 'white' ?>;">
                    <div class="calendar-day <?= $isToday ? 'text-primary fw-bold' : '' ?>">
                      <?= $day ?>
                      <?php if ($isToday): ?>
                        <span class="badge bg-primary ms-1">Today</span>
                      <?php endif; ?>
                    </div>
                    <?php if ($hasLeave): ?>
                      <div class="leave-indicator">
                        <i class="fa-solid fa-user-clock me-1"></i>
                        <?= count($items) ?> on leave
                      </div>
                      <button type="button" class="view-day-btn view-day" 
                              data-date="<?= $dateStr ?>" 
                              data-items='<?= json_encode($items) ?>'>
                        View Details
                      </button>
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

    <!-- Approved Leaves Table -->
    <div class="data-table">
      <div class="data-table-header">
        <h5><i class="fa-solid fa-clipboard-check me-2"></i>Approved Leaves</h5>
      </div>
      <div class="data-table-content">
        <div class="table-responsive">
          <table class="table table-custom">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Leave Type</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Action By</th>
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
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($lv['fullname']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($lv['position']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($lv['department']) ?></td>
                  <td>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($lv['leave_type_name'] ?? 'N/A') ?></span>
                  </td>
                  <td><strong><?= htmlspecialchars($from ?? 'N/A') ?></strong></td>
                  <td><strong><?= htmlspecialchars($to ?? 'N/A') ?></strong></td>
                  <td><?= htmlspecialchars($lv['duration'] ?? 'N/A') ?> days</td>
                  <td>
                    <?php if ($st === 'Effective'): ?>
                      <span class="badge badge-status badge-effective">Effective</span>
                    <?php elseif ($st === 'Upcoming'): ?>
                      <span class="badge badge-status badge-upcoming">Upcoming</span>
                    <?php else: ?>
                      <span class="badge badge-status badge-completed">Completed</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($lv['action_by'] ?? 'N/A') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($displayLeaves)): ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fa-solid fa-calendar-check fa-2x mb-3"></i>
                    <div>No approved leaves found</div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Archived Leaves Table -->
    <?php if ($hasArchive): ?>
      <div class="data-table">
        <div class="data-table-header">
          <h5><i class="fa-solid fa-box-archive me-2"></i>Recently Archived Leaves</h5>
        </div>
        <div class="data-table-content">
          <div class="table-responsive">
            <table class="table table-custom">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Leave Type</th>
                  <th>From Date</th>
                  <th>To Date</th>
                  <th>Duration</th>
                  <th>Status</th>
                  <th>Action By</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($displayArchives)): foreach ($displayArchives as $lv): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($lv['fullname']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($lv['position']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($lv['department']) ?></td>
                    <td>
                      <span class="badge bg-secondary"><?= htmlspecialchars($lv['leave_type_name'] ?? 'N/A') ?></span>
                    </td>
                    <td><?= htmlspecialchars($lv['from_date'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($lv['to_date'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($lv['duration'] ?? 'N/A') ?> days</td>
                    <td><span class="badge badge-status badge-archived">Archived</span></td>
                    <td><?= htmlspecialchars($lv['action_by'] ?? 'N/A') ?></td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                      <i class="fa-solid fa-box-open fa-2x mb-3"></i>
                      <div>No archived leaves found</div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Day Details Modal -->
  <div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
      // Day modal functionality
      document.querySelectorAll('.view-day').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var dateStr = this.getAttribute('data-date');
          var items = JSON.parse(this.getAttribute('data-items') || '[]');
          
          // Format date for display
          var date = new Date(dateStr);
          var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
          var formattedDate = date.toLocaleDateString('en-US', options);
          
          document.getElementById('modalTitle').textContent = 'Leave Details - ' + formattedDate;
          
          var list = document.getElementById('dayList');
          if (items.length > 0) {
            var html = '<div class="row g-3">';
            items.forEach(function(item) {
              html += `
                <div class="col-md-6">
                  <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                      <h6 class="card-title text-primary mb-2">${item.fullname || 'Unknown'}</h6>
                      <div class="mb-2">
                        <small class="text-muted d-block">Employee ID: ${item.empID || 'N/A'}</small>
                        <small class="text-muted d-block">${item.department || 'N/A'} • ${item.position || 'N/A'}</small>
                      </div>
                      <div class="d-flex align-items-center">
                        <span class="badge bg-warning text-dark">
                          <i class="fa-solid fa-umbrella-beach me-1"></i>
                          ${item.leave_type_name || 'N/A'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              `;
            });
            html += '</div>';
          } else {
            html = '<div class="text-center py-5"><i class="fa-solid fa-calendar-check fa-3x text-muted mb-3"></i><p class="text-muted">No leave records for this date</p></div>';
          }
          list.innerHTML = html;
          
          var modal = new bootstrap.Modal(document.getElementById('dayModal'));
          modal.show();
        });
      });

      // Mobile sidebar toggle (optional)
      function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
      }
    });
  </script>
</body>
</html>