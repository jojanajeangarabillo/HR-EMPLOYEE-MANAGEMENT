<?php
session_start();
require 'admin/db.connect.php';

// PHPMailer includes
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load mailer config
$config = require 'mailer-config.php';

// Initialize counts
$employees = $applicants = 0;

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";


// MENUS
$menus = [
    "HR Director" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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

    "HR Assistant" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Logout" => "Login.php"
    ],

    "Training and Development Coordinator" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Employees" => "Manager_Employees.php",
        "Calendar" => "Manager_Calendar.php",
        "Requests" => "Manager_Request.php",
        "Logout" => "Login.php"
    ]
];

$role = $_SESSION['sub_role'] ?? "HR Manager";

// Count employees
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Employee'");
if ($employeeQuery && $row = $employeeQuery->fetch_assoc()) {
    $employees = $row['count'];
}

// Count applicants
$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc()) {
    $applicants = $row['count'];
}

// Fetch newly hired applicants
$newlyHiredQuery = $conn->query("SELECT * FROM applicant WHERE status='Hired'");
$newlyHired = $newlyHiredQuery ? $newlyHiredQuery->fetch_all(MYSQLI_ASSOC) : [];

// Handle promotion to Employee
if (isset($_POST['add_employee_id'])) {

    $applicantID = $_POST['add_employee_id'];

    // Fetch applicant info
    $appStmt = $conn->prepare("SELECT * FROM applicant WHERE applicantID=? LIMIT 1");
    $appStmt->bind_param("s", $applicantID);
    $appStmt->execute();
    $appResult = $appStmt->get_result();

    if ($app = $appResult->fetch_assoc()) {

        $email = $app['email_address'];

        // BEGIN TRANSACTION (safe execution)
        $conn->begin_transaction();

        try {

            // 1. DELETE old applicant account from user table (NEW)
            $deleteUser = $conn->prepare("DELETE FROM user WHERE email=? AND role='Applicant'");
            $deleteUser->bind_param("s", $email);
            $deleteUser->execute();

            // 2. Generate unique EMP ID
            $empLastID = 'EMP-000';
            $empLastQuery = $conn->query(
                "SELECT applicant_employee_id FROM user 
                 WHERE role='Employee' AND applicant_employee_id IS NOT NULL 
                 ORDER BY applicant_employee_id DESC LIMIT 1"
            );
            if ($empLastQuery && $row = $empLastQuery->fetch_assoc()) {
                $empLastID = $row['applicant_employee_id'];
            }
            $num = intval(substr($empLastID, 4)) + 1;
            $newEmpID = 'EMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);

            // 3. Generate password and reset token
            $passwordPlain = bin2hex(random_bytes(4));
            $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);
            $resetToken = bin2hex(random_bytes(16));
            $expiry = date('Y-m-d H:i:s', strtotime('+3 days'));

            // 4. Create NEW employee record in user table (NEW)
            $insertUser = $conn->prepare("INSERT INTO user 
                (fullname, email, role, status, applicant_employee_id, password, reset_token, token_expiry)
                VALUES (?, ?, 'Employee', 'Active', ?, ?, ?, ?)");
            $insertUser->bind_param(
                "ssssss",
                $app['fullName'],
                $email,
                $newEmpID,
                $passwordHash,
                $resetToken,
                $expiry
            );
            $insertUser->execute();

            // 5. Insert into employee table (NEW)
// Insert into employee table
            $insertEmployee = $conn->prepare("
    INSERT INTO employee (
        empID, fullname, department, position, type_name, 
        email_address, home_address, contact_number, date_of_birth, gender,
        emergency_contact, TIN_number, phil_health_number, SSS_number, 
        pagibig_number, profile_pic, hired_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

            $insertEmployee->bind_param(
                "sssssssssssssssss",
                $newEmpID,
                $app['fullName'],
                $app['department'],
                $app['position_applied'],
                $app['type_name'],       // <-- just the string
                $app['email_address'],
                $app['home_address'],
                $app['contact_number'],
                $app['date_of_birth'],
                $app['gender'],
                $app['emergency_contact'],
                $app['TIN_number'],
                $app['phil_health_number'],
                $app['SSS_number'],
                $app['pagibig_number'],
                $app['profile_pic'],
                $app['hired_at']
            );

            $insertEmployee->execute();



            // 5. Archive applicant in applicant table (NEW)
            $archiveStmt = $conn->prepare("UPDATE applicant SET status='Archived' WHERE applicantID=?");
            $archiveStmt->bind_param("s", $applicantID);
            $archiveStmt->execute();

            // COMMIT changes
            $conn->commit();

            // 6. Send employment confirmation email (NEW)
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port = $config['port'];

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email, $app['fullName']);

                $mail->isHTML(true);
                $mail->Subject = 'Official Employment Confirmation';

                $resetLink = "https://localhost/HR-EMPLOYEE-MANAGEMENT/Change-Password.php?token=$resetToken";

                $mail->Body = "
                    <p>Dear <strong>{$app['fullName']}</strong>,</p>
                    <p>Congratulations! You are now officially employed.</p>
                    
                    <p><b>Your Employee Credentials:</b></p>
                    <ul>
                        <li><b>Employee ID:</b> $newEmpID</li>
                        <li><b>Temporary Password:</b> $passwordPlain</li>
                    </ul>

                    <p>To secure your account, please reset your password using the link below:</p>
                    <p><a href='$resetLink'>Reset Your Password</a></p>
                    <p><b>⚠ Note:</b> This password-reset link expires in <b>3 days</b>.</p>

                    <p>Your applicant account is now closed and cannot be accessed anymore.</p>

                    <p>Welcome to the team!<br>HR Department</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

            echo "<script>alert('Employee promoted, old account deleted, email sent successfully.'); 
                  window.location='Newly-Hired.php';</script>";
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Failed to promote employee. Transaction rolled back.');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newly Hired List</title>
    <link rel="stylesheet" href="manager-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background: #f1f5fc;
            color: #111827;
        }


        .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column;
        }

        .main-content-header h1 {
            padding: 25px 30px;
            margin: 0;
            font-size: 2rem;
            margin-bottom: 40px;
            color: #1E3A8A;
        }

        .table-container {
            width: 90%;
            padding: 0 30px;
            margin-top: 20px;
            box-sizing: border-box;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            min-width: 150px;
            padding: 16px 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        thead {
            background: #1E3A8A;
            font-weight: 600;
            color: white;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .action-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .action-icons button {
            border: none;
            background: none;
            color: green;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $managername"; ?></p>
        </div>

        <ul class="nav">
            <?php foreach ($menus[$role] as $label => $link): ?>
                <li><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="main-content">
        <div class="main-content-header">
            <h1>Newly Hired List</h1>
        </div>
        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Applicant ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th>Email Address</th>
                            <th>Hired Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($newlyHired)): ?>
                            <?php foreach ($newlyHired as $hire): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hire['applicantID']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['fullName']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['department']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['position_applied']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['email_address']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['hired_at']); ?></td>
                                    <td class="action-icons">
                                        <form method="POST">
                                            <input type="hidden" name="add_employee_id"
                                                value="<?php echo $hire['applicantID']; ?>">
                                            <button type="submit"><i class="fa-solid fa-user-plus"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No newly hired applicants found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>