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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeID = $_SESSION['applicant_employee_id'] ?? null;
    if (!$employeeID) die("Employee not found or not logged in.");

    // Check for existing pending request
    $pendingStmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM employee_request WHERE empID = ? AND status = 'Pending'");
    $pendingStmt->bind_param("s", $employeeID);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result()->fetch_assoc();

    if ($pendingResult['pending_count'] > 0) {
        $_SESSION['request_error'] = "You still have a pending request. Wait for further action before requesting again. Thank you!";
        header("Location: Employee_Requests.php");
        exit;
    }

    // Get submitted values
    $requestTypeID = $_POST['request_type_id'] ?? null;
    $leaveTypeID   = $_POST['leave_type_id'] ?? null;
    $reason        = trim($_POST['reason'] ?? '');

    // Handle file upload for e-signature
    $signaturePath = '';
    if (!empty($_FILES['e_signature']['name'])) {
        $fileName = time() . '_' . basename($_FILES['e_signature']['name']);
        $targetDir = 'uploads/signatures/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        move_uploaded_file($_FILES['e_signature']['tmp_name'], $targetDir . $fileName);
        $signaturePath = $targetDir . $fileName;
    }

    // Fetch employee info dynamically
    $empStmt = $conn->prepare("SELECT fullname, department, position, type_name, email_address FROM employee WHERE empID = ?");
    $empStmt->bind_param("s", $employeeID);
    $empStmt->execute();
    $empResult = $empStmt->get_result()->fetch_assoc();

    $fullname      = $empResult['fullname'] ?? '';
    $department    = $empResult['department'] ?? '';
    $position      = $empResult['position'] ?? '';
    $type_name     = $empResult['type_name'] ?? '';
    $email_address = $empResult['email_address'] ?? '';

    // Get request type name
    $requestTypeName = null;
    if ($requestTypeID) {
        $typeStmt = $conn->prepare("SELECT request_type_name FROM types_of_requests WHERE id = ?");
        $typeStmt->bind_param("i", $requestTypeID);
        $typeStmt->execute();
        $typeResult = $typeStmt->get_result()->fetch_assoc();
        $requestTypeName = $typeResult['request_type_name'] ?? null;
    }

    // Get leave type name only if request is Leave
    $leaveTypeName = null;
    if ($requestTypeName === 'Leave' && $leaveTypeID) {
        $leaveStmt = $conn->prepare("SELECT leave_type_name FROM leave_types WHERE id = ?");
        $leaveStmt->bind_param("i", $leaveTypeID);
        $leaveStmt->execute();
        $leaveResult = $leaveStmt->get_result()->fetch_assoc();
        $leaveTypeName = $leaveResult['leave_type_name'] ?? null;
    } else {
        $leaveTypeID = null;
        $leaveTypeName = null;
    }

    // Insert employee request
    $stmt = $conn->prepare("INSERT INTO employee_request 
        (empID, fullname, department, position, type_name, email_address, e_signature, request_type_id, request_type_name, leave_type_id, leave_type_name, reason, status, requested_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");

    $stmt->bind_param(
        "sssssssisiss",
        $employeeID,
        $fullname,
        $department,
        $position,
        $type_name,
        $email_address,
        $signaturePath,
        $requestTypeID,
        $requestTypeName,
        $leaveTypeID,
        $leaveTypeName,
        $reason
    );

    if ($stmt->execute()) {
        $_SESSION['request_success'] = "Requested Successfully!";
        header("Location: Employee_Requests.php");
        exit;
    } else {
        $_SESSION['request_error'] = "Failed to submit request. Please try again.";
    }
}



//check pending request


?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Requests</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  
</head>

<body>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
        <li ><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
        <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> Salary Slip</a></li>
        <li class="active"><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>
  

<!-- Toast Container Centered -->
<div class="position-fixed p-3" style="z-index: 1100; top: 50%; left: 50%; transform: translate(-50%, -50%);">
  <div id="requestToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


<?php if(isset($_SESSION['request_success'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('requestToast');
    if (toastEl) {
        document.getElementById('toast-message').innerText = "<?php echo $_SESSION['request_success']; unset($_SESSION['request_success']); ?>";
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
});
</script>
<?php endif; ?>

<?php if(isset($_SESSION['request_error'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('requestToast');
    if (toastEl) {
        toastEl.classList.remove('bg-success');
        toastEl.classList.add('bg-danger');
        document.getElementById('toast-message').innerText = "<?php echo $_SESSION['request_error']; unset($_SESSION['request_error']); ?>";
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
});
</script>
<?php endif; ?>



<main class="main-content">
  <div class="main-box" id="blur-content">
    <div class="main-header">
      <div class="request-title">
        <h2 style="color:black;" >Employee Request <i class="fa-solid fa-code-branch"></i></h2>
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
                  SELECT request_type_name, leave_type_name, reason, status, requested_at, action_by
                  FROM employee_request 
                  WHERE empID = ? 
                  ORDER BY requested_at DESC
              ");

          $reqStmt->bind_param("s", $employeeID);
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
                while($type = $requestTypes->fetch_assoc()):
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
                while($leave = $leaveTypes->fetch_assoc()):
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

 h1 {
      font-family: 'Roboto', sans-serif;
      font-size: 35px;
      color: white;
      text-align: center;
    }
    .menu-board-title {
      font-size: 18px;
      font-weight: bold;
      margin: 15px 0 5px 15px;
      text-transform: uppercase;
      color: white;
    }

   /* --- Blur Effect --- */
    .blurred {
      filter: blur(5px);
      pointer-events: none;
      user-select: none;
      transition: filter 0.2s;
    }

    /* --- Modal Styles --- */
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
  background: #1E3A8A;
  color: #fff;
  border-radius: 12px;
  padding: 30px 40px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  width: 650px;
  max-width: 90%;
  margin: auto;
}

.modal-header h2 {
  font-size: 1.8rem;
  font-weight: bold;
  text-align: center;
  margin-bottom: 25px;
}

.modal-content {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.modal-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap; /* allow stacking on small screens */
}

.form-group {
  flex: 1 1 calc(50% - 10px); /* two columns, with a gap accounted for */
  display: flex;
  flex-direction: column;
  min-width: 250px; /* prevents fields from shrinking too much */
}


#leave-type-container,
#other-type {
  min-height: 60px; /* same height as a normal input */
  transition: all 0.2s ease;
}

.modal-form {
  max-height: 90vh;
  overflow-y: auto;
}

.form-group label {
  font-weight: 600;
  margin-bottom: 5px;
  font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  background: #fff;
  border: none;
  border-radius: 6px;
  padding: 10px 12px;
  font-size: 0.95rem;
  color: #000;
  outline: none;
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
}

.cancel-btn {
  background: #E63946;
  color: white;
}

.cancel-btn:hover {
  background: #c9303c;
}

.send-btn {
  background: #19BB4E;
  color: white;
}

.send-btn:hover {
  background: #128c3a;
}

/* Responsive */
@media (max-width: 700px) {
  .modal-row {
    flex-direction: column;
  }
}
    .main-box {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 6px 30px rgba(30, 70, 140, 0.10), 0 1.5px 4px rgba(30, 70, 140, 0.07);
      padding: 32px 24px 24px 24px;
      margin-top: 20px;
      margin-bottom: 32px;
    }

    .main-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      border-bottom: 2px solid #e2e7f1;
      padding-bottom: 16px;
    }

    .main-header h2 {
      font-size: 1.7rem;
      color: #222e50;
      margin: 0;
      font-weight: bold;
      letter-spacing: 0.03em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .file-request-btn {
      background: #fff;
      border: 2px solid #2540a8;
      color: #2540a8;
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 1rem;
      transition: background 0.2s, color 0.2s;
      cursor: pointer;
      box-shadow: 0 1px 3px rgba(30,70,140,0.04);
      margin-left: 24px;
    }
    .file-request-btn:hover {
      background: #2540a8;
      color: #fff;
    }

    .request-table-container {
      margin-top: 18px;
    }
    .request-title i {
      color: #1E3A8A;
    }
  
    .request-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #f3f7ff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(82,100,180, 0.06);
    }

    .request-table thead th {
      background: #2949d0;
      color: #fff;
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
      background: #e4edff;
      box-shadow: 0 2px 12px rgba(82, 120, 220, 0.09);
      transform: scale(1.012);
      z-index: 1;
      position: relative;
    }

    .request-table td {
      padding: 14px 12px;
      font-size: 15px;
      color: #213056;
      border-bottom: 1px solid #e2e7f1;
      background: none;
      text-align: center;
    }

    .request-table tr:last-child td {
      border-bottom: none;
    }

    .approved {
      color: #18a140;
      font-weight: bold;
    }

    .view-btn {
      background: #18a140;
      color: #fff;
      border: none;
      padding: 6px 18px;
      border-radius: 12px;
      font-size: 1rem;
      cursor: pointer;
      font-weight: 500;
      box-shadow: 0 1.5px 6px rgba(24,161,64,0.08);
      transition: background 0.18s;
    }
    .view-btn:hover {
      background: #17a13d;
    }

.form-group textarea {
  padding: 8px 12px;
  border: none;
  border-radius: 7px;
  font-size: 1rem;
  outline: none;
  margin-bottom: 4px;
  background: #f3f5fb;
  color: black;
  resize: vertical;
}

   .sidebar-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 30px;
      margin-right: 10px;
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
     body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }
     .main-content {
      margin-left: 250px;
      padding: 40px 30px;
      background-color: #f1f5fc;
      flex-grow: 1;
      box-sizing: border-box;
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

    openModalBtn.addEventListener('click', function() {
      modal.classList.add('active');
      blurBox.classList.add('blurred');
    });
    closeModalBtn.addEventListener('click', function() {
      modal.classList.remove('active');
      blurBox.classList.remove('blurred');
    });
    
    window.addEventListener('keydown', function(e) {
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

window.addEventListener('keydown', function(e) {
  if (e.key === "Escape" && viewModal.classList.contains('active')) {
    viewModal.classList.remove('active');
    blurBox.classList.remove('blurred');
  }
});



  </script>

</body>

</html>
