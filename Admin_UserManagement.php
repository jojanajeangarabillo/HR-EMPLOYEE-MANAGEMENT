<?php
session_start();
require 'admin/db.connect.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require 'mailer-config.php'; 

$error_msg = "";
$success_msg = "";

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// Handle Add User Form Submission
if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $sub_role = $_POST['sub_role'] ?? null;
    $department = $_POST['department'] ?? null;
    $status = $_POST['status'] ?? 'Active';
   $type_id = $_POST['employment_type'] ?? null; // selected emtypeID
        $type_name = null;

        if ($type_id) {
            $etypeQuery = $conn->prepare("SELECT typeName FROM employment_type WHERE emtypeID = ?");
            $etypeQuery->bind_param("i", $type_id);
            $etypeQuery->execute();
            $etypeResult = $etypeQuery->get_result();
            if ($etypeResult->num_rows > 0) {
                $etypeRow = $etypeResult->fetch_assoc();
                $type_name = $etypeRow['typeName'];
            }
        }


    // Generate temp password and hash
    $tempPass = bin2hex(random_bytes(4));
    $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);

    // Generate reset token
    $token = bin2hex(random_bytes(16));
    $token_expiry = date("Y-m-d H:i:s", strtotime("+1 day"));

    // Fetch last EMP ID
$result = $conn->query("SELECT applicant_employee_id FROM user WHERE applicant_employee_id LIKE 'EMP-%' ORDER BY applicant_employee_id DESC LIMIT 1");
$row = $result->fetch_assoc();

if ($row) {
    $lastID = $row['applicant_employee_id']; // e.g. EMP-007
    $num = (int) str_replace('EMP-', '', $lastID); // 7
    $num++;
} else {
    $num = 1; // first EMP ID
}

$empID = "EMP-" . str_pad($num, 3, '0', STR_PAD_LEFT);

   // Generate EMP ID
$result = $conn->query("SELECT applicant_employee_id FROM user WHERE applicant_employee_id LIKE 'EMP-%' ORDER BY applicant_employee_id DESC LIMIT 1");
$row = $result->fetch_assoc();

if ($row) {
    $lastID = $row['applicant_employee_id']; 
    $num = (int) str_replace('EMP-', '', $lastID); 
    $num++;
} else {
    $num = 1; 
}

$empID = "EMP-" . str_pad($num, 3, '0', STR_PAD_LEFT);

    // Insert into user table
    $userStmt = $conn->prepare("INSERT INTO user (applicant_employee_id, fullname, email, password, role, sub_role, status, reset_token, token_expiry, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $userStmt->bind_param("sssssssss", $empID, $fullname, $email, $hashedPass, $role, $sub_role, $status, $token, $token_expiry);

    if ($userStmt->execute()) {
        // Insert into employee table
        $empStmt = $conn->prepare("INSERT INTO employee (empID, fullname, email_address, department, position, type_name, hired_at) VALUES (?, ?, ?, ?, ?,?, NOW())");
        $empStmt->bind_param("ssssss", $empID, $fullname, $email, $department,$role, $type_name);
        $empStmt->execute();

        // Send email
        $mailConfig = [
            'host' => 'smtp.example.com',
            'username' => 'you@example.com',
            'password' => 'yourpassword',
            'port' => 587,
            'encryption' => 'tls',
            'from_email' => 'you@example.com',
            'from_name' => 'Employee Management System'
        ];

        try {
              $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port = $config['port'];

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email);

            $link = "http://localhost/HR-EMPLOYEE-MANAGEMENT/Change-Password.php?token=$token";

            $mail->isHTML(true);
            $mail->Subject = "Welcome to the Employee Management System";
            $mail->Body = "
                <h3>Welcome, $fullname!</h3>
                <p>Your temporary password is: <b>$tempPass</b></p>
                <p>Please change your password within 24 hours using this link:</p>
                <a href='$link'>$link</a>
            ";

            $mail->send();
            $_SESSION['success_msg'] = "User added successfully! Email sent.";
        } catch (Exception $e) {
            $_SESSION['error_msg'] = "Account created, but email failed. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error_msg'] = "Error creating account. Please try again.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch Employment Types
$employmentTypes = [];
$etypeQuery = $conn->query("SELECT * FROM employment_type ORDER BY emtypeID ASC");
while ($etype = $etypeQuery->fetch_assoc()) {
    $employmentTypes[] = $etype;
}


// Handle Edit User
if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = $_POST['user_id'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $sub_role = $_POST['sub_role'] ?? null;
    $department = $_POST['department'] ?? null;
    $status = $_POST['status'] ?? 'Active';

    $updateStmt = $conn->prepare("UPDATE user SET fullname=?, email=?, role=?, sub_role=?, status=? WHERE user_id=?");
    $updateStmt->bind_param("ssssss", $fullname, $email, $role, $sub_role, $status, $user_id);
    if ($updateStmt->execute()) {
        // Update employee table as well
        $empUpdateStmt = $conn->prepare("UPDATE employee SET fullname=?, email_address=?, department=?, position=? WHERE empID=(SELECT applicant_employee_id FROM user WHERE user_id=?)");
        $empUpdateStmt->bind_param("sssss", $fullname, $email, $department, $role, $user_id);
        $empUpdateStmt->execute();

        $_SESSION['success_msg'] = "User updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error updating user.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - User Management</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; margin:0; display:flex; background-color:#f1f5fc; color:#111827; }
.main-content { padding:40px 30px; margin-left:220px; display:flex; flex-direction:column; }
.main-content-header h1 { padding:25px 0; margin-bottom:40px; color:#1E3A8A; }
.card { border-radius:15px; }
.modal-body input[name="fullname"],
.modal-body input[name="email"],
.modal-body #edit_fullname,
.modal-body #edit_email { min-height: 45px; }
.table td, .table th { vertical-align: middle; }
.badge { font-size: 0.9em; }
@media (max-width: 576px) {
    .modal-body .form-control, .modal-body .form-select { font-size: 0.9rem; }
}
.btn-sm {
    padding: 5px 8px;
    font-size: 0.9rem;
    color: white;
}

.btn-sm:hover{
    color: blue;
}

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Hospital Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome, $adminname"; ?></p></div>
    <ul class="nav flex-column">
             <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li class="active"><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
             <li><a href="Admin_Departments.php"><i class="fa-building-columns"></i> Departments</a></li>
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i> Vacancies</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
            <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
<div class="main-content-header">
    <h1>User Management / Accounts</h1>
    <?php
    if (!empty($_SESSION['success_msg'])) {
        echo '<div class="alert alert-success">'.$_SESSION['success_msg'].'</div>';
        unset($_SESSION['success_msg']);
    } elseif (!empty($_SESSION['error_msg'])) {
        echo '<div class="alert alert-danger">'.$_SESSION['error_msg'].'</div>';
        unset($_SESSION['error_msg']);
    }
    ?>
</div>

<div class="d-flex justify-content-between mb-4 flex-wrap">
    <h3 style="color:#1E3A8A;">List of Users</h3>
    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addUserModal" 
        style="background:#1E3A8A; border:none; border-radius:8px; padding:10px 20px;">
        <i class="fa-solid fa-user-plus"></i> Add User
    </button>
</div>

<div class="card p-4 shadow-sm">
    <table class="table table-hover align-middle">
           <table class="table table-hover align-middle text-center"> 
        <thead style="color:#1E3A8A; font-size:15px;">
            <tr>
             
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Sub-role</th>
                <th>Status</th>
                <th>Date Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $query = $conn->query("SELECT * FROM user ORDER BY created_at DESC");
        while ($row = $query->fetch_assoc()) {
            $statusBadge = ($row['status'] == 'Active') ? 'bg-success' : 'bg-danger';
            $subRole = $row['sub_role'] ?: '-';
            echo "
            <tr>
              
                <td>{$row['fullname']}</td>
                <td>{$row['email']}</td>
                <td>{$row['role']}</td>
                <td>{$subRole}</td>
                <td><span class='badge $statusBadge'>{$row['status']}</span></td>
                <td>{$row['created_at']}</td>
                <td>
            <button class='btn btn-sm btn-secondary editBtn' 
                data-id='{$row['user_id']}' 
                data-fullname='{$row['fullname']}' 
                data-email='{$row['email']}' 
                data-role='{$row['role']}' 
                data-subrole='{$row['sub_role']}' 
                data-status='{$row['status']}' 
                title='Edit User'>
                <i class='fa-solid fa-pen'></i>
            </button>
            </td>

            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</main>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:15px;">
      <div class="modal-header" style="background:#1E3A8A; color:white;">
        <h5 class="modal-title">Add New User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_user">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label>Full Name</label>
              <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="col-12 col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <label>Role</label>
              <select name="role" class="form-select" required>
                <option value="Employee">Employee</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label>Sub-role</label>
              <select name="sub_role" class="form-select">
                <option value="">-- Select Sub-role --</option>
                <option value="HR Director">HR Director</option>
                <option value="HR Manager">HR Manager</option>
                <option value="HR Officer">HR Officer</option>
                <option value="HR Assistant">HR Assistant</option>
                <option value="Recruitment Manager">Recruitment Manager</option>
                <option value="Training Coordinator">Training Coordinator</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label>Department</label>
              <select name="department" class="form-select" required>
                <option value="">-- Select Department --</option>
                <option value="HR">HR</option>
                <option value="Finance">Finance</option>
                <option value="IT">IT</option>
                <option value="Operations">Operations</option>
              </select>
            </div>

            <div class="col-12 col-md-4">
  <label>Employment Type</label>
  <select name="employment_type" class="form-select" required>
    <option value="">-- Select Employment Type --</option>
    <?php foreach ($employmentTypes as $etype): ?>
        <option value="<?= $etype['emtypeID'] ?>"><?= $etype['typeName'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

            <div class="col-12 col-md-4">
              <label>Status</label>
              <select name="status" class="form-select">
                <option value="Active" selected>Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" style="background:#1E3A8A;">Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:15px;">
      <div class="modal-header" style="background:#1E3A8A; color:white;">
        <h5 class="modal-title">Edit User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label>Full Name</label>
              <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
            </div>
            <div class="col-12 col-md-6">
              <label>Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <label>Role</label>
              <select name="role" id="edit_role" class="form-select" required>
                <option value="Employee">Employee</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label>Sub-role</label>
              <select name="sub_role" id="edit_sub_role" class="form-select">
                <option value="">-- Select Sub-role --</option>
                <option value="HR Director">HR Director</option>
                <option value="HR Manager">HR Manager</option>
                <option value="HR Officer">HR Officer</option>
                <option value="HR Assistant">HR Assistant</option>
                <option value="Recruitment Manager">Recruitment Manager</option>
                <option value="Training Coordinator">Training Coordinator</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label>Department</label>
              <select name="department" id="edit_department" class="form-select" required>
                <option value="">-- Select Department --</option>
                <option value="HR">HR</option>
                <option value="Finance">Finance</option>
                <option value="IT">IT</option>
                <option value="Operations">Operations</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
  <label>Employment Type</label>
  <select name="employment_type" id="edit_employment_type" class="form-select" required>
    <option value="">-- Select Employment Type --</option>
    <?php foreach ($employmentTypes as $etype): ?>
        <option value="<?= $etype['emtypeID'] ?>"><?= $etype['typeName'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

            <div class="col-12 col-md-4">
              <label>Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" style="background:#1E3A8A;">Update User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const editButtons = document.querySelectorAll(".editBtn");
editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
        const modal = new bootstrap.Modal(document.getElementById("editUserModal"));
        document.getElementById("edit_fullname").value = btn.dataset.fullname;
        document.getElementById("edit_email").value = btn.dataset.email;
        document.getElementById("edit_role").value = btn.dataset.role;
        document.getElementById("edit_sub_role").value = btn.dataset.subrole;
        document.getElementById("edit_status").value = btn.dataset.status;
        document.getElementById("edit_department").value = btn.dataset.department;
        document.getElementById("edit_employment_type").value = btn.dataset.employmentType;
        document.getElementById("edit_user_id").value = btn.dataset.id;
        modal.show();
    });
});
</script>

</body>
</html>
