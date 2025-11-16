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
$managername = "";

// Fetch HR Manager name
$managerQuery = $conn->query("SELECT fullname FROM user WHERE role='Employee' AND sub_role='HR Manager' LIMIT 1");
if ($managerQuery && $row = $managerQuery->fetch_assoc()) {
    $managername = $row['fullname'];
}

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

        // Generate unique EMP ID
        $empLastID = 'EMP-000';
        $empLastQuery = $conn->query("SELECT applicant_employee_id FROM user WHERE role='Employee' AND applicant_employee_id IS NOT NULL ORDER BY applicant_employee_id DESC LIMIT 1");
        if ($empLastQuery && $row = $empLastQuery->fetch_assoc()) {
            $empLastID = $row['applicant_employee_id'];
        }
        $num = intval(substr($empLastID, 4)) + 1;
        $newEmpID = 'EMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        // Generate password and reset token
        $passwordPlain = bin2hex(random_bytes(4));
        $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);
        $resetToken = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+3 days'));

        // Promote applicant by updating existing user record
        $updateStmt = $conn->prepare("UPDATE user SET 
    applicant_employee_id=?, role='Employee', status='Active', password=?, reset_token=?, token_expiry=? 
    WHERE email=? AND role='Applicant'
");
$updateStmt->bind_param("sssss", $newEmpID, $passwordHash, $resetToken, $expiry, $app['email_address']);
        
        $updateStmt->execute();

        if ($updateStmt->affected_rows > 0) {
            // Archive applicant record
            $archiveStmt = $conn->prepare("UPDATE applicant SET status='Archived' WHERE applicantID=?");
            $archiveStmt->bind_param("s", $applicantID);
            $archiveStmt->execute();

            // Send email notification
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
                $mail->addAddress($app['email_address'], $app['fullName']);

                $mail->isHTML(true);
                $mail->Subject = 'Your Employee Account Created';
                $resetLink = "https://yourdomain.com/reset_password.php?token=$resetToken";
                $mail->Body = "
                    <p>Dear {$app['fullName']},</p>
                    <p>Your employee account has been created.</p>
                    <p><b>Employee ID:</b> $newEmpID</p>
                    <p><b>Password:</b> $passwordPlain</p>
                    <p>Please <a href='$resetLink'>click here</a> to reset your password. This link expires in 3 days.</p>
                    <p>Regards,<br>HR Department</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

            echo "<script>alert('Employee promoted and email sent successfully.'); window.location='Newly-Hired.php';</script>";
            exit;
        } else {
            echo "<script>alert('Failed to promote employee.');</script>";
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>
<style>
body { font-family: 'Poppins','Roboto',sans-serif; margin:0; display:flex; background:#f1f5fc; color:#111827; }
.sidebar-logo{display:flex; justify-content:center; margin-bottom:25px;}
.sidebar-logo img{height:110px;width:110px;border-radius:50%;object-fit:cover;border:3px solid #fff;}
.sidebar-name{display:flex;justify-content:center;align-items:center;text-align:center;color:white;padding:10px;margin-bottom:30px;font-size:18px;flex-direction:column;}
.main-content{padding:40px 30px;margin-left:220px;display:flex;flex-direction:column;}
.main-content-header h1{padding:25px 30px;margin:0;font-size:2rem;margin-bottom:40px;color:#1E3A8A;}
.table-container{width:90%;padding:0 30px;margin-top:20px;box-sizing:border-box;}
.table-responsive{width:100%;overflow-x:auto;}
table{border-collapse:collapse;width:100%;background:white;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden;}
th, td{min-width:150px;padding:16px 12px;text-align:center;border:1px solid #e0e0e0;}
thead{background:#1E3A8A;font-weight:600;color:white;}
tbody tr:hover{background:#f8f9fa;}
tbody tr:nth-child(even){background:#fafafa;}
.action-icons{display:flex;justify-content:center;gap:15px;}
.action-icons button{border:none;background:none;color:green;font-size:18px;cursor:pointer;}
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome, $managername"; ?></p></div>
    <ul class="nav">
        <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
        <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
        <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
        <li class="active"><a href="Newly-Hired.php"><i class="fa-solid fa-user-plus"></i>Newly Hired</a></li>
        <li><a href="Manager_Employees.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="main-content-header"><h1>Newly Hired List</h1></div>
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
                    <?php if(!empty($newlyHired)): ?>
                        <?php foreach($newlyHired as $hire): ?>
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
                                        <input type="hidden" name="add_employee_id" value="<?php echo $hire['applicantID']; ?>">
                                        <button type="submit"><i class="fa-solid fa-user-plus"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No newly hired applicants found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
