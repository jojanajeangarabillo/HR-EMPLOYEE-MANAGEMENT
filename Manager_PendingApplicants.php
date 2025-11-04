<?php
session_start();
require 'admin/db.connect.php';

$hirings = 0;
$applicants = 0;
$managername = 0;

$managernameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Employee' AND  sub_role ='HR Manager' LIMIT 1");
if ($managernameQuery && $row = $managernameQuery->fetch_assoc()) {
  $managername = $row['fullname'];
}

$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc()) {
  $applicants = $row['count'];
}

// Fetch pending applicants from the applicant table
// Server-side filtering: read GET params
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'Pending';

$pendingApplicants = [];

// Build SQL dynamically based on filters
$sql = "SELECT applicantID, fullName, status FROM applicant";
$clauses = [];
$types = '';
$params = [];

// status filter (default to Pending to match the page intent)
if ($statusFilter && strtolower($statusFilter) !== 'all') {
  $clauses[] = "status = ?";
  $types .= 's';
  $params[] = $statusFilter;
}

// search across applicantID, fullName, email_address (if present)
if ($search !== '') {
  $clauses[] = "(applicantID LIKE ? OR fullName LIKE ? OR email_address LIKE ?)";
  $like = "%" . $search . "%";
  $types .= 'sss';
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

if (!empty($clauses)) {
  $sql .= ' WHERE ' . implode(' AND ', $clauses);
}

$sql .= ' ORDER BY date_applied DESC';

if ($stmt = $conn->prepare($sql)) {
  if (!empty($params)) {
    // bind params dynamically
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
      $bind_name = 'bind' . $i;
      $$bind_name = $params[$i];
      $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
  }
  $stmt->execute();
  $pres = $stmt->get_result();
  if ($pres) {
    while ($prow = $pres->fetch_assoc()) {
      $pendingApplicants[] = $prow;
    }
  }
  $stmt->close();
}

// Handle status update POST from the dropdowns
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && isset($_POST['applicantID']) && isset($_POST['new_status'])) {
  $allowed = [
    'Initial Interview',
    'Assessment',
    'Final Interview',
    'Requirements',
    'Hired',
    'Rejected',
  ];
  $aid = $_POST['applicantID'];
  $new = $_POST['new_status'];
  $isAjax = (isset($_POST['ajax']) && $_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

  $response = ['success' => false, 'message' => 'Invalid request'];
  if (in_array($new, $allowed, true) && !empty($aid)) {
    if ($u = $conn->prepare("UPDATE applicant SET status = ? WHERE applicantID = ?")) {
      $u->bind_param('ss', $new, $aid);
      $exec = $u->execute();
      $u->close();
      if ($exec) {
        $response = ['success' => true, 'message' => 'Status updated'];
      } else {
        $response = ['success' => false, 'message' => 'Database update failed'];
      }
    } else {
      $response = ['success' => false, 'message' => 'Failed to prepare update'];
    }
  } else {
    $response = ['success' => false, 'message' => 'Invalid status or applicant ID'];
  }

  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  } else {
    // Non-AJAX fallback: redirect back (preserve query string)
    $qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
    // Optionally set a flash message in session
    if ($response['success']) {
      $_SESSION['flash_success'] = $response['message'];
    } else {
      $_SESSION['flash_error'] = $response['message'];
    }
    header('Location: Manager_PendingApplicants.php' . $qs);
    exit;
  }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }

    .sidebar-logo {
      display: flex;
      justify-content: center;
      margin-bottom: 25px;
    }

    .sidebar-logo img {
      height: 110px;
      width: 110px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #ffffff;
    }

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      margin: 0 0 25px 0;
      font-size: 2rem;
      color: #1E3A8A;
    }

    /* Header for Search + Filter */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-filter {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .search-box {
      position: relative;
      flex: 1;
      max-width: 350px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 40px;
      border: 1px solid #d1d5db;
      border-radius: 25px;
      font-size: 14px;
      background-color: white;
      outline: none;
      transition: all 0.3s;
    }

    .search-box input:focus {
      border-color: #1e3a8a;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
      font-size: 14px;
    }

    select {
      border-radius: 25px;
      padding: 10px 18px;
      border: 1px solid #d1d5db;
      background-color: #fff;
      font-size: 14px;
      color: #333;
      outline: none;
      cursor: pointer;
      transition: all 0.3s;
    }

    select:hover {
      border-color: #1E3A8A;
    }

    /* Table Styling */
    table {
      width: 90%;
      border-collapse: collapse;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
      margin-left: 200px;
    }

    th,
    td {
      padding: 18px 30px;
      text-align: center;
      border-bottom: 1px solid #e0e0e0;
    }

    th {
      font-size: 15px;
      letter-spacing: 0.3px;
    }

    td {
      font-size: 14px;
    }

    thead {
      background-color: #1E3A8A;
      color: white;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    /* ✅ Wider Column Proportions */
    th:nth-child(1),
    td:nth-child(1) {
      width: 25%;
    }

    th:nth-child(2),
    td:nth-child(2) {
      width: 30%;
    }

    th:nth-child(3),
    td:nth-child(3) {
      width: 20%;
    }

    th:nth-child(4),
    td:nth-child(4) {
      width: 25%;
    }

    /* View Button - support both id and class to preserve compatibility */
    #view-btn,
    .view-btn {
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 25px;
      padding: 8px 16px;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    #view-btn:hover,
    .view-btn:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
    }

    #view-btn i,
    .view-btn i {
      font-size: 13px;
    }

    /* Status Color Coding */
    .status {
      font-weight: 600;
      border-radius: 30px;
      padding: 6px 14px;
      display: inline-block;
    }

    .status.pending {
      background-color: #fef3c7;
      color: #a16207;
    }

    .status.interviewed {
      background-color: #dcfce7;
      color: #166534;
    }

    .status.rejected {
      background-color: #fee2e2;
      color: #991b1b;
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
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $managername"; ?></p>
    </div>

    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li class="active"><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending
          Applicants</a></li>
      <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1>Pending Applicants</h1>
    </div>

    <div class="header">
      <div class="search-filter">
        <form method="get" id="filterForm" style="display:flex;align-items:center;gap:15px;">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" name="q" placeholder="Search applicants..."
              value="<?php echo htmlspecialchars($search ?? ''); ?>">
          </div>

          <select id="statusFilter" name="status">
            <option value="all" <?php echo (isset($statusFilter) && strtolower($statusFilter) === 'all') ? 'selected' : ''; ?>>All Status</option>
            <option value="Pending" <?php echo (isset($statusFilter) && $statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="Interviewed" <?php echo (isset($statusFilter) && $statusFilter === 'Interviewed') ? 'selected' : ''; ?>>Interviewed</option>
            <option value="Rejected" <?php echo (isset($statusFilter) && $statusFilter === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
          </select>

          <button type="submit"
            style="background:#1E3A8A;color:#fff;border:none;padding:8px 14px;border-radius:8px;">Search</button>
        </form>
      </div>
    </div>

    <table id="applicantTable">
      <thead>
        <tr>
          <th>Applicant ID</th>
          <th>Full Name</th>
          <th>Action</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingApplicants)): ?>
          <tr>
            <td colspan="4">No pending applicants found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pendingApplicants as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['applicantID'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($p['fullName'] ?? ''); ?></td>
              <td><button class="view-btn" data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"><i
                    class="fa-solid fa-eye"></i> View</button></td>
              <td>
                <?php $current = $p['status'] ?? ''; ?>
                <?php $opts = ['Initial Interview', 'Assessment', 'Final Interview', 'Requirements', 'Hired', 'Rejected']; ?>
                <select data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>" onchange="updateStatusAjax(this)"
                  style="padding:6px 10px;border-radius:8px;min-width:160px;">
                  <?php foreach ($opts as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($current === $opt) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($opt); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Auto-submit the form when status changes for convenience
    document.addEventListener('DOMContentLoaded', function () {
      const status = document.getElementById('statusFilter');
      const form = document.getElementById('filterForm');
      if (status && form) {
        status.addEventListener('change', function () { form.submit(); });
      }
    });

    // AJAX status updater
    function updateStatusAjax(selectEl) {
      const appid = selectEl.dataset.appid;
      const newStatus = selectEl.value;
      if (!appid) return;
      selectEl.disabled = true;

      const body = new URLSearchParams();
      body.append('ajax', '1');
      body.append('update_status', '1');
      body.append('applicantID', appid);
      body.append('new_status', newStatus);

      fetch('Manager_PendingApplicants.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: body
      }).then(res => res.json()).then(json => {
        if (json && json.success) {
          showToast(json.message || 'Status updated');
        } else {
          showToast('Error: ' + (json && json.message ? json.message : 'Update failed'));
        }
      }).catch(err => {
        showToast('Server error');
      }).finally(() => { selectEl.disabled = false; });
    }

    // Simple toast
    function showToast(msg) {
      let t = document.getElementById('mp-toast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'mp-toast';
        t.style.position = 'fixed';
        t.style.right = '20px';
        t.style.top = '20px';
        t.style.padding = '10px 14px';
        t.style.background = 'rgba(16,24,40,0.9)';
        t.style.color = '#fff';
        t.style.borderRadius = '8px';
        t.style.zIndex = 2000;
        t.style.boxShadow = '0 6px 18px rgba(2,6,23,0.2)';
        document.body.appendChild(t);
      }
      t.textContent = msg;
      t.style.opacity = '1';
      setTimeout(() => { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; }, 2500);
    }
  </script>
</body>

</html>