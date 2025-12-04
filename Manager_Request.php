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
    $employeename = $_SESSION['fullname'] ?? "Employee";
    $profile_picture = "uploads/employees/default.png";
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

// Fetch full employee info
$employeeData = [
  'fullname' => '',
  'empID' => '',
  'department' => '',
  'position' => '',
  'type_name' => '',
  'email_address' => ''
];

if ($employeeID) {
  $stmt = $conn->prepare("SELECT fullname, empID, department, position, type_name, email_address, profile_pic FROM employee WHERE empID = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $employeeData = $row;
    $employeename = $row['fullname'];
    $profile_picture = !empty($row['profile_pic'])
      ? "uploads/employees/" . $row['profile_pic']
      : "uploads/employees/default.png";
  }
}

$slotsByMonth = [];
$leaveTypeIdRow = null;
$ltidStmt = $conn->prepare("SELECT id FROM types_of_requests WHERE request_type_name = 'Leave' LIMIT 1");
$ltidStmt->execute();
$leaveTypeIdRow = $ltidStmt->get_result()->fetch_assoc();
$ltidStmt->close();
$leaveReqTypeId = $leaveTypeIdRow['id'] ?? null;
if ($leaveReqTypeId) {
  $lsStmt = $conn->prepare("SELECT month, employee_limit, settingID FROM leave_settings WHERE request_type_id = ? ORDER BY settingID DESC");
  $lsStmt->bind_param("i", $leaveReqTypeId);
  $lsStmt->execute();
  $lsRes = $lsStmt->get_result();
  while ($row = $lsRes->fetch_assoc()) {
    $m = (int)$row['month'];
    if (!isset($slotsByMonth[$m])) $slotsByMonth[$m] = (int)$row['employee_limit'];
  }
  $lsStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $employeeID = $_SESSION['applicant_employee_id'] ?? null;
  if (!$employeeID) {
    $_SESSION['request_error'] = "Employee not logged in.";
    header("Location: Manager_Request.php");
    exit;
  }

  $pendingCount = 0;
  $ps1 = $conn->prepare("SELECT COUNT(*) AS c FROM leave_request WHERE empID = ? AND status = 'Pending'");
  $ps1->bind_param("s", $employeeID);
  $ps1->execute();
  $r1 = $ps1->get_result()->fetch_assoc();
  $pendingCount += intval($r1['c'] ?? 0);
  $ps2 = $conn->prepare("SELECT COUNT(*) AS c FROM general_request WHERE empID = ? AND status = 'Pending'");
  $ps2->bind_param("s", $employeeID);
  $ps2->execute();
  $r2 = $ps2->get_result()->fetch_assoc();
  $pendingCount += intval($r2['c'] ?? 0);
  if ($pendingCount > 0) {
    $_SESSION['request_error'] = "You still have a pending request. Wait for action before requesting again.";
    header("Location: Manager_Request.php");
    exit;
  }

  $requestTypeID = $_POST['request_type_id'] ?? null;
  $leaveTypeID = $_POST['leave_type_id'] ?? null;
  $reason = trim($_POST['reason'] ?? '');
  $from_date = $_POST['from_date'] ?? null;
  $to_date = $_POST['to_date'] ?? null;
  $duration = $_POST['duration'] ?? null;

  $signaturePath = '';
  if (!empty($_FILES['e_signature']['name'])) {
    $fileName = time() . '_' . basename($_FILES['e_signature']['name']);
    $targetDir = 'uploads/signatures/';
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0777, true);
    }
    move_uploaded_file($_FILES['e_signature']['tmp_name'], $targetDir . $fileName);
    $signaturePath = $targetDir . $fileName;
  }

  $empStmt = $conn->prepare("SELECT fullname, department, position, type_name, email_address FROM employee WHERE empID = ?");
  $empStmt->bind_param("s", $employeeID);
  $empStmt->execute();
  $empRes = $empStmt->get_result()->fetch_assoc();
  $fullname = $empRes['fullname'] ?? '';
  $department = $empRes['department'] ?? '';
  $position = $empRes['position'] ?? '';
  $type_name = $empRes['type_name'] ?? '';
  $email_address = $empRes['email_address'] ?? '';

  $requestTypeName = null;
  if ($requestTypeID) {
    $typeStmt = $conn->prepare("SELECT request_type_name FROM types_of_requests WHERE id = ?");
    $typeStmt->bind_param("i", $requestTypeID);
    $typeStmt->execute();
    $typeRow = $typeStmt->get_result()->fetch_assoc();
    $requestTypeName = $typeRow['request_type_name'] ?? null;
  }

  if ($requestTypeName === 'Leave') {
    if (!$leaveTypeID || !$from_date || !$to_date) {
      $_SESSION['request_error'] = "Provide leave type and date range.";
      header("Location: Manager_Request.php");
      exit;
    }
    $d1 = date_create($from_date);
    $d2 = date_create($to_date);
    if (!$d1 || !$d2 || $d1 > $d2) {
      $_SESSION['request_error'] = "Invalid date range.";
      header("Location: Manager_Request.php");
      exit;
    }
    $monthNum = intval(date('n', strtotime($from_date)));
    $slotStmt = $conn->prepare("SELECT employee_limit FROM leave_settings WHERE request_type_id = ? AND month = ? ORDER BY settingID DESC LIMIT 1");
    $slotStmt->bind_param("ii", $requestTypeID, $monthNum);
    $slotStmt->execute();
    $slotRow = $slotStmt->get_result()->fetch_assoc();
    $slotsLeft = isset($slotRow['employee_limit']) ? (int)$slotRow['employee_limit'] : null;
    $slotStmt->close();
    if ($slotsLeft !== null && $slotsLeft <= 0) {
      $_SESSION['request_error'] = "No slots available for this month.";
      header("Location: Manager_Request.php");
      exit;
    }
    if (!$duration) {
      $diff = $d2->diff($d1)->days + 1;
      $duration = $diff;
    }
    $leaveTypeName = null;
    $ltStmt = $conn->prepare("SELECT leave_type_name, pay_category_id FROM leave_types WHERE id = ?");
    $ltStmt->bind_param("i", $leaveTypeID);
    $ltStmt->execute();
    $ltRow = $ltStmt->get_result()->fetch_assoc();
    $leaveTypeName = $ltRow['leave_type_name'] ?? '';
    $payCategoryID = isset($ltRow['pay_category_id']) ? (int)$ltRow['pay_category_id'] : 1;
    $stmt = $conn->prepare("INSERT INTO leave_request (
      empID, fullname, department, position, type_name, email_address, e_signature,
      request_type_id, request_type_name, reason, status, requested_at,
      leave_type_id, pay_category_id, leave_type_name, from_date, to_date, duration
    ) VALUES (
      ?, ?, ?, ?, ?, ?, ?,
      ?, ?, ?, 'Pending', NOW(),
      ?, ?, ?, ?, ?, ?
    )");
    $stmt->bind_param(
      "sssssssississssi",
      $employeeID,
      $fullname,
      $department,
      $position,
      $type_name,
      $email_address,
      $signaturePath,
      $requestTypeID,
      $requestTypeName,
      $reason,
      $leaveTypeID,
      $payCategoryID,
      $leaveTypeName,
      $from_date,
      $to_date,
      $duration
    );
  } else {
    if (!$requestTypeID || !$requestTypeName) {
      $_SESSION['request_error'] = "Select a valid request type.";
      header("Location: Manager_Request.php");
      exit;
    }
    $stmt = $conn->prepare("INSERT INTO general_request (empID, fullname, department, position, email, request_type_id, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
      "sssssis",
      $employeeID,
      $fullname,
      $department,
      $position,
      $email_address,
      $requestTypeID,
      $reason
    );
  }

  if ($stmt->execute()) {
    $_SESSION['request_success'] = "Requested Successfully!";
  } else {
    $_SESSION['request_error'] = "Failed to submit request.";
  }
  header("Location: Manager_Request.php");
  exit;
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
  <title>Manager Requests</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
  <?php
  $modalType = null;
  $modalText = null;
  if (isset($_SESSION['request_success'])) {
    $modalType = 'success';
    $modalText = $_SESSION['request_success'];
    unset($_SESSION['request_success']);
  } elseif (isset($_SESSION['request_error'])) {
    $modalType = 'danger';
    $modalText = $_SESSION['request_error'];
    unset($_SESSION['request_error']);
  }
  ?>
  <?php if ($modalType && $modalText): ?>
    <div class="modal fade" id="flashModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-<?php echo $modalType; ?>">
          <div class="modal-header bg-<?php echo $modalType; ?> text-white">
            <h5 class="modal-title">Notification</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <?php if ($modalType === 'success'): ?>
              <i class="fa-solid fa-check-circle fa-2x text-success mb-3"></i>
            <?php else: ?>
              <i class="fa-solid fa-triangle-exclamation fa-2x text-danger mb-3"></i>
            <?php endif; ?>
            <p><?php echo htmlspecialchars($modalText); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-<?php echo $modalType; ?>" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('flashModal');
        if (m) new bootstrap.Modal(m).show();
      });
    </script>
  <?php endif; ?>

  <main class="main-content">
    <div class="main-box" id="blur-content">
      <div class="main-header">
        <div class="request-title">
          <h2><i class="fa-solid fa-code-branch"></i> Employee Requests</h2>
        </div>
        <button class="file-request-btn" id="open-modal"><i class="fa-solid fa-plus-circle"></i> File a Request</button>
      </div>

      <div class="request-table-container">
        <table class="request-table">
          <thead>
            <tr>
              <th>Request Type</th>
              <th>Reason</th>
              <th>Date and Time Requested</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($employeeID) {
              $reqStmt = $conn->prepare("
                  SELECT 'Leave' AS request_type_name, lr.leave_type_name, lr.reason, lr.status, lr.requested_at, NULL AS action_by
                  FROM leave_request lr WHERE lr.empID = ?
                  UNION ALL
                  SELECT tor.request_type_name, NULL AS leave_type_name, gr.reason, gr.status, gr.requested_at, gr.action_by
                  FROM general_request gr
                  JOIN types_of_requests tor ON tor.id = gr.request_type_id
                  WHERE gr.empID = ?
                  ORDER BY requested_at DESC
              ");

              $reqStmt->bind_param("ss", $employeeID, $employeeID);
              $reqStmt->execute();
              $reqResult = $reqStmt->get_result();

              if ($reqResult->num_rows > 0) {
                while ($req = $reqResult->fetch_assoc()) {
                  $displayType = $req['leave_type_name'] ? $req['leave_type_name'] : $req['request_type_name'];
                  $date = date("F d, Y h:i A", strtotime($req['requested_at']));
                  $statusClass = strtolower($req['status']) === 'approved' ? 'approved' : (strtolower($req['status']) === 'pending' ? 'pending' : 'rejected');
                  $actionBy = $req['status'] === 'Pending' ? 'Pending' : htmlspecialchars($req['action_by'] ?? 'N/A');

                  echo "<tr>
            <td>" . htmlspecialchars($displayType) . "</td>
            <td>" . htmlspecialchars($req['reason']) . "</td>
            <td>$date</td>
            <td class='$statusClass'>" . htmlspecialchars($req['status']) . "</td>
            <td>
              <button class='view-btn' 
                data-type='" . htmlspecialchars($displayType) . "'
                data-reason='" . htmlspecialchars($req['reason']) . "'
                data-date='$date'
                data-status='" . htmlspecialchars($req['status']) . "'
                data-action='$actionBy'
              >View</button>
            </td>
          </tr>";
                }
              } else {
                echo "<tr><td colspan='5'>No requests found.</td></tr>";
              }
            } else {
              echo "<tr><td colspan='5'>Employee not logged in.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Request Form -->
    <div id="request-modal" class="modal-overlay">
      <div class="modal-form">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h2><i class="fa-solid fa-code-branch"></i> Employee Request</h2>
          </div>

          <div class="modal-content">
            <!-- Employee Info -->
            <div class="modal-row">
              <div class="form-group">
                <label>Full Name:</label>
                <input type="text" value="<?= htmlspecialchars($employeeData['fullname']); ?>" readonly>
              </div>
              <div class="form-group">
                <label>Employee ID:</label>
                <input type="text" name="empID" value="<?= htmlspecialchars($employeeData['empID']); ?>" readonly>
              </div>
              <div class="form-group">
                <label>Department:</label>
                <input type="text" value="<?= htmlspecialchars($employeeData['department']); ?>" readonly>
              </div>
              <div class="form-group">
                <label>Position:</label>
                <input type="text" value="<?= htmlspecialchars($employeeData['position']); ?>" readonly>
              </div>
              <div class="form-group wide">
                <label>Type of Employment:</label>
                <input type="text" value="<?= htmlspecialchars($employeeData['type_name']); ?>" readonly>
              </div>
              <div class="form-group wide">
                <label>Email Address:</label>
                <input type="text" value="<?= htmlspecialchars($employeeData['email_address']); ?>" readonly>
              </div>
            </div>

            <!-- Request Type -->
            <div class="modal-row">
              <div class="form-group wide">
                <label>Type of Request</label>
                <select id="request-type" name="request_type_id" required>
                  <option value="">-- Select Request Type --</option>
                  <?php
                  $requestTypes = $conn->query("SELECT * FROM types_of_requests");
                  while ($type = $requestTypes->fetch_assoc()):
                    ?>
                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['request_type_name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Leave Type -->
              <div class="form-group wide" id="leave-type-container" style="display:none;">
                <label>Select Leave Type</label>
                <select id="leave-type" name="leave_type_id">
                  <option value="">-- Select Leave Type --</option>
                  <?php
                  $leaveTypes = $conn->query("SELECT * FROM leave_types");
                  while ($leave = $leaveTypes->fetch_assoc()):
                    ?>
                    <option value="<?= $leave['id'] ?>" data-request="<?= $leave['request_type_id'] ?>">
                      <?= htmlspecialchars($leave['leave_type_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>

            <div class="modal-row">
              <div class="form-group wide">
                <label>Reason:</label>
                <textarea name="reason" placeholder="Enter your reason" rows="5" required></textarea>
              </div>
            </div>

            <div class="modal-row" id="leave-dates-container" style="display:none;">
              <div class="form-group wide">
                <label>From Date:</label>
                <input type="date" id="from-date" name="from_date">
              </div>
              <div class="form-group wide">
                <label>To Date:</label>
                <input type="date" id="to-date" name="to_date">
              </div>
              <div class="form-group wide">
                <label>Duration (Days):</label>
                <input type="number" id="duration" name="duration" readonly>
              </div>
              <div class="form-group wide">
                <div id="slot-warning" style="display:none;color:#E63946;font-weight:600;"></div>
              </div>
            </div>

            <div class="form-group wide">
              <label>Upload E-Signature:</label>
              <input type="file" name="e_signature" accept="image/*">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="cancel-btn" id="close-modal">Cancel</button>
            <button type="submit" class="send-btn">Send</button>
          </div>
        </form>
      </div>
    </div>

    <div id="view-request-modal" class="modal-overlay">
      <div class="modal-form">
        <div class="modal-header">
          <h2><i class="fa-solid fa-eye"></i> Request Details</h2>
        </div>
        <div class="modal-content">
          <div class="form-group">
            <label>Request Type:</label>
            <input type="text" id="view-type" readonly>
          </div>
          <div class="form-group">
            <label>Reason:</label>
            <textarea id="view-reason" readonly></textarea>
          </div>
          <div class="form-group">
            <label>Date Requested:</label>
            <input type="text" id="view-date" readonly>
          </div>
          <div class="form-group">
            <label>Status:</label>
            <input type="text" id="view-status" readonly>
          </div>
          <div class="form-group">
            <label>Action By:</label>
            <input type="text" id="view-action" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cancel-btn" id="close-view-modal">Close</button>
        </div>
      </div>
    </div>

    <style>
      /* Enhanced Blue Color Scheme */
      :root {
        --primary-blue: #1e40af;
        --primary-blue-light: #3b82f6;
        --primary-blue-lighter: #60a5fa;
        --primary-blue-lightest: #dbeafe;
        --secondary-blue: #1d4ed8;
        --secondary-blue-light: #2563eb;
        --accent-blue: #0284c7;
        --dark-blue: #1e3a8a;
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
      }

      .main-content {
        margin-left: 250px;
        padding: 40px 30px;
        background-color: var(--bg-light);
        flex-grow: 1;
        box-sizing: border-box;
        min-height: 100vh;
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
  color: white;
}



      /* Main Box Styling */
      .main-box {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 32px 24px 24px 24px;
        margin-top: 20px;
        margin-bottom: 32px;
        transition: all 0.3s ease;
      }

      .main-box:hover {
        box-shadow: var(--shadow-hover);
      }

      .main-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        border-bottom: 2px solid var(--light-blue-dark);
        padding-bottom: 16px;
      }

      .main-header h2 {
        font-size: 1.7rem;
        color: var(--dark-blue);
        margin: 0;
        font-weight: bold;
        letter-spacing: 0.03em;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .file-request-btn {
        background: var(--primary-blue);
        border: 2px solid var(--primary-blue);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .file-request-btn:hover {
        background: var(--secondary-blue);
        border-color: var(--secondary-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 64, 175, 0.3);
      }

      /* Table Styling */
      .request-table-container {
        margin-top: 18px;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(82, 100, 180, 0.06);
      }

      .request-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        overflow: hidden;
      }

      .request-table thead th {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
        color: white;
        font-weight: 600;
        text-align: center;
        padding: 16px 12px;
        font-size: 1rem;
        letter-spacing: 0.02em;
      }

      .request-table tbody tr {
        transition: background 0.2s, box-shadow 0.2s, transform .07s;
        cursor: pointer;
      }

      .request-table tbody tr:hover {
        background: var(--light-blue);
        box-shadow: 0 2px 12px rgba(82, 120, 220, 0.09);
        transform: scale(1.012);
        z-index: 1;
        position: relative;
      }

      .request-table td {
        padding: 14px 12px;
        font-size: 15px;
        color: var(--text-dark);
        border-bottom: 1px solid var(--light-blue-dark);
        background: none;
        text-align: center;
      }

      .request-table tr:last-child td {
        border-bottom: none;
      }

      /* Status Styling */
      .approved {
        color: #18a140;
        font-weight: bold;
        background-color: rgba(24, 161, 64, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
      }

      .pending {
        color: #f59e0b;
        font-weight: bold;
        background-color: rgba(245, 158, 11, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
      }

      .rejected {
        color: #dc2626;
        font-weight: bold;
        background-color: rgba(220, 38, 38, 0.1);
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
      }

      /* Button Styling */
      .view-btn {
        background: var(--primary-blue);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        box-shadow: 0 1.5px 6px rgba(30, 64, 175, 0.08);
      }

      .view-btn:hover {
        background: var(--secondary-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 64, 175, 0.2);
      }

      /* Modal Styling */
      .blurred {
        filter: blur(5px);
        pointer-events: none;
        user-select: none;
        transition: filter 0.2s;
      }

      .modal-overlay {
        display: none;
        position: fixed;
        z-index: 200;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        justify-content: center;
        align-items: center;
      }

      .modal-overlay.active {
        display: flex;
      }

      .modal-form {
        background: var(--card-bg);
        color: var(--text-dark);
        border-radius: var(--border-radius);
        padding: 30px 40px;
        box-shadow: var(--shadow-hover);
        width: 650px;
        max-width: 90%;
        margin: auto;
        max-height: 90vh;
        overflow-y: auto;
      }

      .modal-header h2 {
        font-size: 1.8rem;
        font-weight: bold;
        text-align: center;
        margin-bottom: 25px;
        color: var(--dark-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }

      .modal-content {
        display: flex;
        flex-direction: column;
        gap: 18px;
      }

      .modal-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
      }

      .form-group {
        flex: 1 1 calc(50% - 10px);
        display: flex;
        flex-direction: column;
        min-width: 250px;
      }

      .form-group.wide {
        flex: 1 1 100%;
      }

      .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 0.95rem;
        color: var(--text-dark);
      }

      .form-group input,
      .form-group select,
      .form-group textarea {
        background: var(--light-blue);
        border: 1px solid var(--light-blue-dark);
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 0.95rem;
        color: var(--text-dark);
        outline: none;
        transition: all 0.2s ease;
      }

      .form-group input:focus,
      .form-group select:focus,
      .form-group textarea:focus {
        border-color: var(--primary-blue-light);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      }

      .form-group textarea {
        resize: none;
        height: 100px;
      }

      .modal-footer {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
      }

      .cancel-btn,
      .send-btn {
        padding: 10px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s ease;
      }

      .cancel-btn {
        background: #E63946;
        color: white;
      }

      .cancel-btn:hover {
        background: #c9303c;
        transform: translateY(-2px);
      }

      .send-btn {
        background: var(--primary-blue);
        color: white;
      }

      .send-btn:hover {
        background: var(--secondary-blue);
        transform: translateY(-2px);
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .main-content {
          padding: 20px;
          margin-left: 0;
        }
        
        .main-header {
          flex-direction: column;
          gap: 15px;
          align-items: flex-start;
        }
        
        .file-request-btn {
          align-self: flex-start;
        }
        
        .modal-row {
          flex-direction: column;
        }
        
        .form-group {
          min-width: 100%;
        }
      }
    </style>
  </main>

  <script>
    const openModalBtn = document.getElementById('open-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const modal = document.getElementById('request-modal');
    const blurBox = document.getElementById('blur-content');
    const requestType = document.getElementById('request-type');
    const otherTypeInput = document.getElementById('other-type');

    openModalBtn.addEventListener('click', function () {
      modal.classList.add('active');
      blurBox.classList.add('blurred');
    });
    closeModalBtn.addEventListener('click', function () {
      modal.classList.remove('active');
      blurBox.classList.remove('blurred');
    });

    window.addEventListener('keydown', function (e) {
      if (e.key === "Escape" && modal.classList.contains('active')) {
        modal.classList.remove('active');
        blurBox.classList.remove('blurred');
      }
    });

    // Highlight active sidebar link
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar .nav li a").forEach(link => {
      if (link.getAttribute("href") === currentPage) {
        link.parentElement.classList.add("active");
      }
    });

    const requestTypeSelect = document.getElementById('request-type');
    const leaveTypeContainer = document.getElementById('leave-type-container');
    const leaveTypeSelect = document.getElementById('leave-type');

    requestTypeSelect.addEventListener('change', () => {
      const selected = requestTypeSelect.selectedOptions[0].text;

      // Show leave types if "Leave" selected
      if (selected === 'Leave') {
        leaveTypeContainer.style.display = 'block';
        Array.from(leaveTypeSelect.options).forEach(opt => {
          opt.style.display = (opt.dataset.request == requestTypeSelect.value || opt.value === "") ? 'block' : 'none';
        });
      } else {
        leaveTypeContainer.style.display = 'none';
        leaveTypeSelect.value = "";
      }
    });

    const viewButtons = document.querySelectorAll('.view-btn');
    const viewModal = document.getElementById('view-request-modal');
    const closeViewBtn = document.getElementById('close-view-modal');

    viewButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('view-type').value = btn.dataset.type;
        document.getElementById('view-reason').value = btn.dataset.reason;
        document.getElementById('view-date').value = btn.dataset.date;
        document.getElementById('view-status').value = btn.dataset.status;
        document.getElementById('view-action').value = btn.dataset.action;

        viewModal.classList.add('active');
        blurBox.classList.add('blurred');
      });
    });

    closeViewBtn.addEventListener('click', () => {
      viewModal.classList.remove('active');
      blurBox.classList.remove('blurred');
    });

    window.addEventListener('keydown', function (e) {
      if (e.key === "Escape" && viewModal.classList.contains('active')) {
        viewModal.classList.remove('active');
        blurBox.classList.remove('blurred');
      }
    });

    const leaveDatesContainer = document.getElementById('leave-dates-container');
    const fromDateInput = document.getElementById('from-date');
    const toDateInput = document.getElementById('to-date');
    const durationInput = document.getElementById('duration');
    const sendBtn = document.querySelector('.send-btn');
    const slotWarning = document.getElementById('slot-warning');
    const slotsByMonth = <?php echo json_encode($slotsByMonth); ?>;

    requestTypeSelect.addEventListener('change', () => {
      const selected = requestTypeSelect.selectedOptions[0].text;

      if (selected === 'Leave') {
        leaveTypeContainer.style.display = 'block';
        leaveDatesContainer.style.display = 'flex';
        Array.from(leaveTypeSelect.options).forEach(opt => {
          opt.style.display = (opt.dataset.request == requestTypeSelect.value || opt.value === "") ? 'block' : 'none';
        });
        checkSlots();
      } else {
        leaveTypeContainer.style.display = 'none';
        leaveDatesContainer.style.display = 'none';
        leaveTypeSelect.value = "";
        fromDateInput.value = "";
        toDateInput.value = "";
        durationInput.value = "";
        sendBtn.disabled = false;
        slotWarning.style.display = 'none';
      }
    });

    // Auto calculate duration
    function calculateDuration() {
      if (fromDateInput.value && toDateInput.value) {
        const from = new Date(fromDateInput.value);
        const to = new Date(toDateInput.value);
        const diff = (to - from) / (1000 * 60 * 60 * 24) + 1; // include both start/end
        durationInput.value = diff > 0 ? diff : 0;
      } else {
        durationInput.value = '';
      }
    }

    fromDateInput.addEventListener('change', calculateDuration);
    toDateInput.addEventListener('change', calculateDuration);

    function checkSlots() {
      const selected = requestTypeSelect.selectedOptions[0]?.text;
      if (selected === 'Leave' && fromDateInput.value) {
        const m = new Date(fromDateInput.value).getMonth() + 1;
        const slots = slotsByMonth && slotsByMonth[m] !== undefined ? parseInt(slotsByMonth[m], 10) : null;
        if (slots !== null && slots <= 0) {
          sendBtn.disabled = true;
          leaveTypeSelect.disabled = true;
          fromDateInput.disabled = true;
          toDateInput.disabled = true;
          slotWarning.textContent = 'No slots available for this month.';
          slotWarning.style.display = 'block';
        } else {
          sendBtn.disabled = false;
          leaveTypeSelect.disabled = false;
          fromDateInput.disabled = false;
          toDateInput.disabled = false;
          slotWarning.style.display = 'none';
        }
      }
    }

    fromDateInput.addEventListener('change', () => { calculateDuration(); checkSlots(); });
  </script>
</body>
</html>