<?php
session_start();
require 'admin/db.connect.php';
// PHPMailer includes (adjust path if needed)
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// load mailer config
$config = require 'mailer-config.php';

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
  "Vacancies" => "fa-briefcase",
  "Job Post" => "fa-bullhorn",
  "Calendar" => "fa-calendar-days",
  "Approvals" => "fa-square-check",
  "Reports" => "fa-chart-column",
  "Settings" => "fa-gear",
  "Logout" => "fa-right-from-bracket"
];

// EMAIL NOTIFICATION FUNCTION WITH DEBUGGING
function sendShiftNotification($empID, $shiftDetails, $notificationType, $conn) {
    global $config;
    
    error_log("=== EMAIL DEBUG START ===");
    error_log("Employee ID: " . $empID);
    error_log("Notification Type: " . $notificationType);
    
    // Get employee email and details
    $stmt = $conn->prepare("SELECT email_address, fullname FROM employee WHERE empID = ?");
    $stmt->bind_param("s", $empID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("ERROR: Employee not found for email notification: " . $empID);
        return false;
    }
    
    $employee = $result->fetch_assoc();
    $to_email = $employee['email_address'];
    $employee_name = $employee['fullname'];
    
    error_log("Employee Name: " . $employee_name);
    error_log("Employee Email: " . $to_email);
    
    if (empty($to_email)) {
        error_log("ERROR: No email address found for employee: " . $empID);
        return false;
    }
    
    // Create email content based on notification type
    $subject = "";
    $message = "";
    
    switch($notificationType) {
        case 'single_shift_assigned':
            $subject = "Shift Assignment Notification - " . $shiftDetails['shift_name'];
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: #1E3A8A; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .shift-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .info-item { margin: 8px 0; }
                    .info-label { font-weight: bold; color: #1E3A8A; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Shift Assignment Notification</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>$employee_name</strong>,</p>
                    <p>Your shift schedule has been updated:</p>
                    <div class='shift-details'>
                        <h3 style='color: #1E3A8A; margin-top: 0;'>Shift Details</h3>
                        <div class='info-item'><span class='info-label'>Shift:</span> {$shiftDetails['shift_name']}</div>
                        <div class='info-item'><span class='info-label'>Date:</span> " . date('F j, Y', strtotime($shiftDetails['schedule_date'])) . "</div>
                        <div class='info-item'><span class='info-label'>Time:</span> {$shiftDetails['time_in']} - {$shiftDetails['time_out']}</div>
                        <div class='info-item'><span class='info-label'>Duration:</span> {$shiftDetails['shift_hours']} hours</div>
                        <div class='info-item'><span class='info-label'>Assigned By:</span> {$shiftDetails['assigned_by']}</div>
                    </div>
                    <p>Please ensure you are available for your scheduled shift.</p>
                    <p>If you have any concerns about this assignment, please contact your manager.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from HR Scheduling System. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
            ";
            break;
            
        case 'multiple_shifts_assigned':
            $days_text = !empty($shiftDetails['days_of_week']) ? implode(", ", $shiftDetails['days_of_week']) : "All days";
            $subject = "Multiple Shift Assignments - " . $shiftDetails['shift_name'];
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: #1E3A8A; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .shift-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
                    .date-range { background: #e8f4fd; padding: 12px; border-radius: 4px; margin: 10px 0; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .info-item { margin: 8px 0; }
                    .info-label { font-weight: bold; color: #1E3A8A; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Multiple Shift Assignments</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>$employee_name</strong>,</p>
                    <p>You have been assigned multiple shifts:</p>
                    <div class='shift-details'>
                        <h3 style='color: #1E3A8A; margin-top: 0;'>Shift Pattern</h3>
                        <div class='info-item'><span class='info-label'>Shift:</span> {$shiftDetails['shift_name']}</div>
                        <div class='info-item'><span class='info-label'>Time:</span> {$shiftDetails['time_in']} - {$shiftDetails['time_out']}</div>
                        <div class='date-range'>
                            <div class='info-item'><span class='info-label'>From:</span> " . date('F j, Y', strtotime($shiftDetails['start_date'])) . "</div>
                            <div class='info-item'><span class='info-label'>To:</span> " . date('F j, Y', strtotime($shiftDetails['end_date'])) . "</div>
                            <div class='info-item'><span class='info-label'>Days:</span> $days_text</div>
                        </div>
                        <div class='info-item'><span class='info-label'>Total Shifts:</span> {$shiftDetails['assigned_count']}</div>
                        <div class='info-item'><span class='info-label'>Assigned By:</span> {$shiftDetails['assigned_by']}</div>
                    </div>
                    <p>Please review your schedule and ensure availability for all assigned shifts.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from HR Scheduling System. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
            ";
            break;
            
        case 'default_shift_updated':
            $default_shift_text = $shiftDetails['default_shift_name'] ? 
                "{$shiftDetails['default_shift_name']} ({$shiftDetails['default_shift_time']})" : "Not Set";
            $subject = "Default Shift Settings Updated";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: #1E3A8A; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .settings-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .info-item { margin: 8px 0; }
                    .info-label { font-weight: bold; color: #1E3A8A; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Default Shift Settings Updated</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>$employee_name</strong>,</p>
                    <p>Your default shift preferences have been updated:</p>
                    <div class='settings-details'>
                        <h3 style='color: #1E3A8A; margin-top: 0;'>New Settings</h3>
                        <div class='info-item'><span class='info-label'>Shift Type:</span> {$shiftDetails['shift_type']}</div>
                        <div class='info-item'><span class='info-label'>Default Shift:</span> $default_shift_text</div>
                        <div class='info-item'><span class='info-label'>Work Hours Per Week:</span> {$shiftDetails['work_hours_per_week']} hours</div>
                        <div class='info-item'><span class='info-label'>Updated By:</span> {$shiftDetails['updated_by']}</div>
                    </div>
                    <p>These settings will be used for future automatic shift assignments.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from HR Scheduling System. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
            ";
            break;
            
        case 'shift_updated':
            $subject = "Shift Schedule Updated - " . $shiftDetails['shift_name'];
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: #1E3A8A; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .shift-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .info-item { margin: 8px 0; }
                    .info-label { font-weight: bold; color: #1E3A8A; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Shift Schedule Update</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>$employee_name</strong>,</p>
                    <p>Your shift schedule has been updated:</p>
                    <div class='shift-details'>
                        <h3 style='color: #1E3A8A; margin-top: 0;'>Updated Shift Details</h3>
                        <div class='info-item'><span class='info-label'>Date:</span> " . date('F j, Y', strtotime($shiftDetails['schedule_date'])) . "</div>
                        <div class='info-item'><span class='info-label'>New Shift:</span> {$shiftDetails['shift_name']}</div>
                        <div class='info-item'><span class='info-label'>Time:</span> {$shiftDetails['time_in']} - {$shiftDetails['time_out']}</div>
                        <div class='info-item'><span class='info-label'>Updated By:</span> {$shiftDetails['updated_by']}</div>
                    </div>
                    <p>Please note this change in your schedule.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from HR Scheduling System. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
            ";
            break;

        case 'shift_pattern_assigned':
            $subject = "Shift Pattern Assignment - " . $shiftDetails['pattern_name'];
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: #1E3A8A; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .pattern-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #1E3A8A; }
                    .date-range { background: #e8f4fd; padding: 12px; border-radius: 4px; margin: 10px 0; }
                    .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .info-item { margin: 8px 0; }
                    .info-label { font-weight: bold; color: #1E3A8A; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Shift Pattern Assignment</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>$employee_name</strong>,</p>
                    <p>You have been assigned a new shift pattern:</p>
                    <div class='pattern-details'>
                        <h3 style='color: #1E3A8A; margin-top: 0;'>Pattern Details</h3>
                        <div class='info-item'><span class='info-label'>Pattern:</span> {$shiftDetails['pattern_name']}</div>
                        <div class='info-item'><span class='info-label'>Description:</span> {$shiftDetails['pattern_description']}</div>
                        <div class='date-range'>
                            <div class='info-item'><span class='info-label'>From:</span> " . date('F j, Y', strtotime($shiftDetails['start_date'])) . "</div>
                            <div class='info-item'><span class='info-label'>To:</span> " . date('F j, Y', strtotime($shiftDetails['end_date'])) . "</div>
                        </div>
                        <div class='info-item'><span class='info-label'>Cycle Duration:</span> {$shiftDetails['cycle_days']} days</div>
                        <div class='info-item'><span class='info-label'>Assigned By:</span> {$shiftDetails['assigned_by']}</div>
                    </div>
                    <p>Your shifts will be automatically generated based on this pattern. Please review your schedule regularly.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from HR Scheduling System. Please do not reply to this email.</p>
                </div>
            </body>
            </html>
            ";
            break;
    }
    
    error_log("Email Subject: " . $subject);
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        // Enable verbose debug output
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP DEBUG (Level $level): $str");
        };
        
        error_log("SMTP Configuration:");
        error_log("Host: " . $config['host']);
        error_log("Username: " . $config['username']);
        error_log("Port: " . $config['port']);
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $employee_name);
        $mail->addReplyTo($config['reply_to'], $config['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        error_log("Attempting to send email...");
        
        if ($mail->send()) {
            error_log("SUCCESS: Email sent successfully to: " . $to_email);
            error_log("=== EMAIL DEBUG END ===");
            return true;
        } else {
            error_log("ERROR: Email sending failed for: " . $to_email);
            error_log("=== EMAIL DEBUG END ===");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("EXCEPTION: PHPMailer Error: " . $e->getMessage());
        error_log("EXCEPTION: PHPMailer Error Info: " . $mail->ErrorInfo);
        
        // Fallback to basic mail() function
        error_log("Attempting fallback to mail() function...");
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">" . "\r\n";
        $headers .= "Reply-To: " . $config['reply_to'] . "\r\n";
        
        if (mail($to_email, $subject, $message, $headers)) {
            error_log("SUCCESS: Fallback email sent to: " . $to_email);
            error_log("=== EMAIL DEBUG END ===");
            return true;
        } else {
            error_log("ERROR: Fallback email also failed for: " . $to_email);
            error_log("=== EMAIL DEBUG END ===");
            return false;
        }
    }
}

// NEW SHIFT PATTERN FUNCTIONS

// Function to assign shift pattern to employee
function assignShiftPattern($empID, $pattern_id, $start_date, $end_date, $assigned_by, $conn) {
    // Check if employee already has active pattern
    $check_stmt = $conn->prepare("
        SELECT emp_pattern_id FROM employee_shift_pattern 
        WHERE empID = ? AND end_date >= ? AND start_date <= ?
    ");
    $check_stmt->bind_param("sss", $empID, $start_date, $end_date);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        return ["success" => false, "message" => "Employee already has an active shift pattern for this period"];
    }
    
    // Assign new pattern - FIXED: Corrected parameter binding
    $insert_stmt = $conn->prepare("
        INSERT INTO employee_shift_pattern (empID, pattern_id, start_date, end_date, assigned_by) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("siss", $empID, $pattern_id, $start_date, $end_date, $assigned_by);
    
    if ($insert_stmt->execute()) {
        // Generate shifts based on pattern
        $shifts_created = generateShiftsFromPattern($empID, $pattern_id, $start_date, $end_date, $conn);
        return ["success" => true, "message" => "Shift pattern assigned successfully. Generated $shifts_created shifts.", "shifts_created" => $shifts_created];
    } else {
        return ["success" => false, "message" => "Error assigning shift pattern: " . $conn->error];
    }
}

// Function to generate shifts from pattern
function generateShiftsFromPattern($empID, $pattern_id, $start_date, $end_date, $conn) {
    // Get pattern details
    $pattern_stmt = $conn->prepare("
        SELECT sp.cycle_days, spd.day_number, spd.shift_id 
        FROM shift_patterns sp 
        JOIN shift_pattern_details spd ON sp.pattern_id = spd.pattern_id 
        WHERE sp.pattern_id = ?
    ");
    $pattern_stmt->bind_param("i", $pattern_id);
    $pattern_stmt->execute();
    $pattern_result = $pattern_stmt->get_result();
    
    $pattern_days = [];
    $cycle_days = 7; // Default
    
    if ($pattern_row = $pattern_result->fetch_assoc()) {
        $cycle_days = $pattern_row['cycle_days'];
        do {
            $pattern_days[] = [
                'day_number' => $pattern_row['day_number'],
                'shift_id' => $pattern_row['shift_id']
            ];
        } while ($pattern_row = $pattern_result->fetch_assoc());
    }
    
    // Generate shifts for each day in the date range
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $shifts_created = 0;
    
    while ($current_date <= $end_date_obj) {
        $days_from_start = $current_date->diff(new DateTime($start_date))->days;
        $day_of_cycle = ($days_from_start % $cycle_days) + 1;
        
        // Check if this day is in the pattern
        foreach ($pattern_days as $pattern_day) {
            if ($pattern_day['day_number'] == $day_of_cycle) {
                $date_str = $current_date->format('Y-m-d');
                
                // Check if shift already exists
                $check_stmt = $conn->prepare("
                    SELECT schedule_id FROM employee_shift_schedule 
                    WHERE empID = ? AND schedule_date = ?
                ");
                $check_stmt->bind_param("ss", $empID, $date_str);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows == 0) {
                    // Insert new shift
                    $insert_stmt = $conn->prepare("
                        INSERT INTO employee_shift_schedule (empID, shift_id, schedule_date, status) 
                        VALUES (?, ?, ?, 'Scheduled')
                    ");
                    $insert_stmt->bind_param("sis", $empID, $pattern_day['shift_id'], $date_str);
                    if ($insert_stmt->execute()) {
                        $shifts_created++;
                    }
                }
                break;
            }
        }
        
        $current_date->modify('+1 day');
    }
    
    return $shifts_created;
}

// Function to get employee's shift pattern
function getEmployeeShiftPattern($empID, $conn) {
    $stmt = $conn->prepare("
        SELECT esp.*, sp.pattern_name, sp.description, sp.cycle_days
        FROM employee_shift_pattern esp
        JOIN shift_patterns sp ON esp.pattern_id = sp.pattern_id
        WHERE esp.empID = ? AND esp.end_date >= CURDATE()
        ORDER BY esp.start_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $empID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Function to predict future shifts based on pattern
function predictFutureShifts($empID, $weeks_ahead = 4, $conn) {
    $pattern = getEmployeeShiftPattern($empID, $conn);
    
    if (!$pattern) {
        return ["success" => false, "message" => "No active shift pattern found"];
    }
    
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+$weeks_ahead weeks"));
    
    // Get pattern details
    $pattern_stmt = $conn->prepare("
        SELECT spd.day_number, spd.shift_id, st.shift_name, st.time_in, st.time_out
        FROM shift_pattern_details spd
        JOIN shift_templates st ON spd.shift_id = st.shift_id
        WHERE spd.pattern_id = ?
        ORDER BY spd.day_number
    ");
    $pattern_stmt->bind_param("i", $pattern['pattern_id']);
    $pattern_stmt->execute();
    $pattern_result = $pattern_stmt->get_result();
    
    $pattern_days = [];
    while ($row = $pattern_result->fetch_assoc()) {
        $pattern_days[$row['day_number']] = $row;
    }
    
    // Generate predicted shifts
    $predicted_shifts = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $pattern_start = new DateTime($pattern['start_date']);
    
    while ($current_date <= $end_date_obj) {
        $days_from_start = $current_date->diff($pattern_start)->days;
        $day_of_cycle = ($days_from_start % $pattern['cycle_days']) + 1;
        
        if (isset($pattern_days[$day_of_cycle])) {
            $shift_info = $pattern_days[$day_of_cycle];
            $predicted_shifts[] = [
                'date' => $current_date->format('Y-m-d'),
                'shift_id' => $shift_info['shift_id'],
                'shift_name' => $shift_info['shift_name'],
                'time_in' => $shift_info['time_in'],
                'time_out' => $shift_info['time_out'],
                'day_of_week' => $current_date->format('l'),
                'day_of_cycle' => $day_of_cycle
            ];
        }
        
        $current_date->modify('+1 day');
    }
    
    return [
        "success" => true,
        "pattern" => $pattern,
        "predicted_shifts" => $predicted_shifts
    ];
}

// Function to create new shift pattern
function createShiftPattern($pattern_name, $description, $cycle_days, $pattern_days, $conn) {
    // Insert pattern
    $stmt = $conn->prepare("INSERT INTO shift_patterns (pattern_name, description, cycle_days) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $pattern_name, $description, $cycle_days);
    
    if ($stmt->execute()) {
        $pattern_id = $conn->insert_id;
        
        // Insert pattern details
        $detail_stmt = $conn->prepare("INSERT INTO shift_pattern_details (pattern_id, day_number, shift_id) VALUES (?, ?, ?)");
        
        foreach ($pattern_days as $day) {
            $detail_stmt->bind_param("iii", $pattern_id, $day['day_number'], $day['shift_id']);
            $detail_stmt->execute();
        }
        
        return ["success" => true, "pattern_id" => $pattern_id, "message" => "Shift pattern created successfully"];
    } else {
        return ["success" => false, "message" => "Error creating shift pattern: " . $conn->error];
    }
}

// Function to update shift pattern
function updateShiftPattern($pattern_id, $pattern_name, $description, $cycle_days, $pattern_days, $conn) {
    // Update pattern
    $stmt = $conn->prepare("UPDATE shift_patterns SET pattern_name = ?, description = ?, cycle_days = ? WHERE pattern_id = ?");
    $stmt->bind_param("ssii", $pattern_name, $description, $cycle_days, $pattern_id);
    
    if ($stmt->execute()) {
        // Delete existing pattern details
        $delete_stmt = $conn->prepare("DELETE FROM shift_pattern_details WHERE pattern_id = ?");
        $delete_stmt->bind_param("i", $pattern_id);
        $delete_stmt->execute();
        
        // Insert updated pattern details
        $detail_stmt = $conn->prepare("INSERT INTO shift_pattern_details (pattern_id, day_number, shift_id) VALUES (?, ?, ?)");
        
        foreach ($pattern_days as $day) {
            $detail_stmt->bind_param("iii", $pattern_id, $day['day_number'], $day['shift_id']);
            $detail_stmt->execute();
        }
        
        return ["success" => true, "message" => "Shift pattern updated successfully"];
    } else {
        return ["success" => false, "message" => "Error updating shift pattern: " . $conn->error];
    }
}

// Function to delete shift pattern
function deleteShiftPattern($pattern_id, $conn) {
    // Check if pattern is assigned to any employee
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee_shift_pattern WHERE pattern_id = ?");
    $check_stmt->bind_param("i", $pattern_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ["success" => false, "message" => "Cannot delete pattern. It is currently assigned to employees."];
    }
    
    // Delete pattern details first
    $delete_details_stmt = $conn->prepare("DELETE FROM shift_pattern_details WHERE pattern_id = ?");
    $delete_details_stmt->bind_param("i", $pattern_id);
    $delete_details_stmt->execute();
    
    // Delete pattern
    $delete_stmt = $conn->prepare("DELETE FROM shift_patterns WHERE pattern_id = ?");
    $delete_stmt->bind_param("i", $pattern_id);
    
    if ($delete_stmt->execute()) {
        return ["success" => true, "message" => "Shift pattern deleted successfully"];
    } else {
        return ["success" => false, "message" => "Error deleting shift pattern: " . $conn->error];
    }
}

// Helper function for single date
function getExpectedEmployeesForDate($conn, $date) {
    $query = "
        SELECT 
            st.shift_name,
            e.department,
            COUNT(ess.empID) AS expected_employees,
            es.required_count AS staffing_requirement,
            (es.required_count - COUNT(ess.empID)) AS staffing_gap
        FROM employee_shift_schedule ess
        JOIN employee e ON ess.empID = e.empID
        JOIN shift_templates st ON ess.shift_id = st.shift_id
        LEFT JOIN expected_staffing es ON 
            e.department = es.department 
            AND st.shift_id = es.shift_id 
            AND DAYNAME(ess.schedule_date) = es.day_of_week
            AND (es.employment_status = 'Any' OR e.type_name = es.employment_status)
        WHERE ess.schedule_date = ?
        GROUP BY st.shift_name, e.department, es.required_count
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $staff = [];
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    
    return $staff;
}

// Function to get expected employees per day for a date range
function getExpectedEmployeesPerDay($conn, $startDate, $endDate) {
    $query = "
        SELECT 
            ess.schedule_date AS date,
            st.shift_name,
            e.department,
            COUNT(ess.empID) AS expected_employees,
            es.required_count AS staffing_requirement,
            (es.required_count - COUNT(ess.empID)) AS staffing_gap,
            CASE 
                WHEN es.required_count IS NULL THEN 'No Requirement'
                WHEN COUNT(ess.empID) >= es.required_count THEN 'Fully Staffed'
                ELSE 'Understaffed'
            END AS staffing_status
        FROM employee_shift_schedule ess
        JOIN employee e ON ess.empID = e.empID
        JOIN shift_templates st ON ess.shift_id = st.shift_id
        LEFT JOIN expected_staffing es ON 
            e.department = es.department 
            AND st.shift_id = es.shift_id 
            AND DAYNAME(ess.schedule_date) = es.day_of_week
            AND (es.employment_status = 'Any' OR e.type_name = es.employment_status)
        WHERE ess.schedule_date BETWEEN ? AND ?
        GROUP BY ess.schedule_date, st.shift_name, e.department, es.required_count
        ORDER BY ess.schedule_date, e.department, st.shift_name
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expectedStaff = [];
    while ($row = $result->fetch_assoc()) {
        $expectedStaff[] = $row;
    }
    
    return $expectedStaff;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Test email functionality
  if (isset($_POST['test_email'])) {
    $test_empID = $_POST['test_empID'] ?? '';
    if (!empty($test_empID)) {
        error_log("=== MANUAL EMAIL TEST ===");
        $testDetails = [
            'shift_name' => 'Test Shift',
            'schedule_date' => date('Y-m-d'),
            'time_in' => '09:00',
            'time_out' => '17:00',
            'shift_hours' => 8,
            'assigned_by' => 'Test Manager'
        ];
        
        $result = sendShiftNotification($test_empID, $testDetails, 'single_shift_assigned', $conn);
        if ($result) {
            $_SESSION['success'] = "Test email sent successfully! Check error logs for details.";
        } else {
            $_SESSION['error'] = "Test email failed. Check error logs for details.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
  }

  // Assign shift to employee
  if (isset($_POST['assign_shift'])) {
    $empID = $_POST['empID'];
    $shift_id = $_POST['shift_id'];
    $schedule_date = $_POST['schedule_date'];
    $assigned_by = $managername;
    
    // Get shift details for email
    $shift_stmt = $conn->prepare("SELECT shift_name, time_in, time_out, shift_hours FROM shift_templates WHERE shift_id = ?");
    $shift_stmt->bind_param("i", $shift_id);
    $shift_stmt->execute();
    $shift_result = $shift_stmt->get_result();
    $shift_data = $shift_result->fetch_assoc();
    
    // Check if shift already exists for this employee on this date
    $check_stmt = $conn->prepare("SELECT schedule_id FROM employee_shift_schedule WHERE empID = ? AND schedule_date = ?");
    $check_stmt->bind_param("ss", $empID, $schedule_date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    $is_update = $result->num_rows > 0;
    
    if ($is_update) {
      $update_stmt = $conn->prepare("UPDATE employee_shift_schedule SET shift_id = ? WHERE empID = ? AND schedule_date = ?");
      $update_stmt->bind_param("iss", $shift_id, $empID, $schedule_date);
      if ($update_stmt->execute()) {
        $_SESSION['success'] = "Shift updated successfully!";
        
        // Send update notification email
        $shiftDetails = array_merge($shift_data, [
            'schedule_date' => $schedule_date,
            'assigned_by' => $assigned_by,
            'updated_by' => $assigned_by
        ]);
        sendShiftNotification($empID, $shiftDetails, 'shift_updated', $conn);
      } else {
        $_SESSION['error'] = "Error updating shift: " . $conn->error;
      }
    } else {
      $insert_stmt = $conn->prepare("INSERT INTO employee_shift_schedule (empID, shift_id, schedule_date, status) VALUES (?, ?, ?, 'Scheduled')");
      $insert_stmt->bind_param("sis", $empID, $shift_id, $schedule_date);
      if ($insert_stmt->execute()) {
        $_SESSION['success'] = "Shift assigned successfully!";
        
        // Send assignment notification email
        $shiftDetails = array_merge($shift_data, [
            'schedule_date' => $schedule_date,
            'assigned_by' => $assigned_by
        ]);
        sendShiftNotification($empID, $shiftDetails, 'single_shift_assigned', $conn);
      } else {
        $_SESSION['error'] = "Error assigning shift: " . $conn->error;
      }
    }
  }
  
  // Assign multiple shifts
  if (isset($_POST['assign_multiple_shifts'])) {
    $empID = $_POST['empID'];
    $shift_id = $_POST['shift_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $days_of_week = isset($_POST['days_of_week']) ? $_POST['days_of_week'] : [];
    
    // Get shift details for email
    $shift_stmt = $conn->prepare("SELECT shift_name, time_in, time_out FROM shift_templates WHERE shift_id = ?");
    $shift_stmt->bind_param("i", $shift_id);
    $shift_stmt->execute();
    $shift_result = $shift_stmt->get_result();
    $shift_data = $shift_result->fetch_assoc();
    
    $assigned_count = 0;
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
      $date_str = $current_date->format('Y-m-d');
      $day_of_week = $current_date->format('l');
      
      // If specific days are selected, only assign on those days
      if (empty($days_of_week) || in_array($day_of_week, $days_of_week)) {
        // Check if shift already exists
        $check_stmt = $conn->prepare("SELECT schedule_id FROM employee_shift_schedule WHERE empID = ? AND schedule_date = ?");
        $check_stmt->bind_param("ss", $empID, $date_str);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
          // Update existing shift
          $update_stmt = $conn->prepare("UPDATE employee_shift_schedule SET shift_id = ? WHERE empID = ? AND schedule_date = ?");
          $update_stmt->bind_param("iss", $shift_id, $empID, $date_str);
          if ($update_stmt->execute()) {
            $assigned_count++;
          }
        } else {
          // Insert new shift
          $insert_stmt = $conn->prepare("INSERT INTO employee_shift_schedule (empID, shift_id, schedule_date, status) VALUES (?, ?, ?, 'Scheduled')");
          $insert_stmt->bind_param("sis", $empID, $shift_id, $date_str);
          if ($insert_stmt->execute()) {
            $assigned_count++;
          }
        }
      }
      
      $current_date->modify('+1 day');
    }
    
    if ($assigned_count > 0) {
      $_SESSION['success'] = "Successfully assigned $assigned_count shifts!";
      
      // Send multiple shifts notification email
      $shiftDetails = array_merge($shift_data, [
          'start_date' => $start_date,
          'end_date' => $end_date,
          'days_of_week' => $days_of_week,
          'assigned_count' => $assigned_count,
          'assigned_by' => $managername
      ]);
      sendShiftNotification($empID, $shiftDetails, 'multiple_shifts_assigned', $conn);
    } else {
      $_SESSION['error'] = "No shifts were assigned. Please check your date range and day selections.";
    }
  }
  
  // Update employee default shift
  if (isset($_POST['update_default_shift'])) {
    $empID = $_POST['empID'];
    $default_shift_id = $_POST['default_shift_id'];
    $shift_type = $_POST['shift_type'];
    $work_hours_per_week = $_POST['work_hours_per_week'];
    
    $stmt = $conn->prepare("UPDATE employee SET default_shift_id = ?, shift_type = ?, work_hours_per_week = ?, assigned_by = ? WHERE empID = ?");
    $stmt->bind_param("isiss", $default_shift_id, $shift_type, $work_hours_per_week, $managername, $empID);
    
    if ($stmt->execute()) {
      $_SESSION['success'] = "Employee shift settings updated successfully!";
      
      // Get default shift details for email
      $default_shift_name = "";
      $default_shift_time = "";
      
      if (!empty($default_shift_id)) {
        $shift_stmt = $conn->prepare("SELECT shift_name, time_in, time_out FROM shift_templates WHERE shift_id = ?");
        $shift_stmt->bind_param("i", $default_shift_id);
        $shift_stmt->execute();
        $shift_result = $shift_stmt->get_result();
        if ($shift_result->num_rows > 0) {
          $shift_data = $shift_result->fetch_assoc();
          $default_shift_name = $shift_data['shift_name'];
          $default_shift_time = $shift_data['time_in'] . " - " . $shift_data['time_out'];
        }
      }
      
      // Send default shift update notification email
      $shiftDetails = [
          'shift_type' => $shift_type,
          'default_shift_name' => $default_shift_name,
          'default_shift_time' => $default_shift_time,
          'work_hours_per_week' => $work_hours_per_week,
          'updated_by' => $managername
      ];
      sendShiftNotification($empID, $shiftDetails, 'default_shift_updated', $conn);
    } else {
      $_SESSION['error'] = "Error updating employee shift settings: " . $conn->error;
    }
  }
  
  // Add staffing requirement
  if (isset($_POST['add_staffing'])) {
    $department = $_POST['department'];
    $shift_id = $_POST['shift_id'];
    $day_of_week = $_POST['day_of_week'];
    $required_count = $_POST['required_count'];
    $employment_status = $_POST['employment_status'];
    
    $stmt = $conn->prepare("INSERT INTO expected_staffing (department, shift_id, day_of_week, required_count, employment_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisis", $department, $shift_id, $day_of_week, $required_count, $employment_status);
    
    if ($stmt->execute()) {
      $_SESSION['success'] = "Staffing requirement added successfully!";
    } else {
      $_SESSION['error'] = "Error adding staffing requirement: " . $conn->error;
    }
  }
  
  // Update staffing requirement
  if (isset($_POST['update_staffing'])) {
    $staffing_id = $_POST['staffing_id'];
    $department = $_POST['department'];
    $shift_id = $_POST['shift_id'];
    $day_of_week = $_POST['day_of_week'];
    $required_count = $_POST['required_count'];
    $employment_status = $_POST['employment_status'];
    
    $stmt = $conn->prepare("UPDATE expected_staffing SET department = ?, shift_id = ?, day_of_week = ?, required_count = ?, employment_status = ? WHERE staffing_id = ?");
    $stmt->bind_param("sisisi", $department, $shift_id, $day_of_week, $required_count, $employment_status, $staffing_id);
    
    if ($stmt->execute()) {
      $_SESSION['success'] = "Staffing requirement updated successfully!";
    } else {
      $_SESSION['error'] = "Error updating staffing requirement: " . $conn->error;
    }
  }
  
  // Delete staffing requirement
  if (isset($_POST['delete_staffing'])) {
    $staffing_id = $_POST['staffing_id'];
    
    $stmt = $conn->prepare("DELETE FROM expected_staffing WHERE staffing_id = ?");
    $stmt->bind_param("i", $staffing_id);
    
    if ($stmt->execute()) {
      $_SESSION['success'] = "Staffing requirement deleted successfully!";
    } else {
      $_SESSION['error'] = "Error deleting staffing requirement: " . $conn->error;
    }
  }

  // NEW: Assign shift pattern
  if (isset($_POST['assign_pattern'])) {
    $empID = $_POST['empID'];
    $pattern_id = $_POST['pattern_id'];
    $start_date = $_POST['pattern_start_date'];
    $end_date = $_POST['pattern_end_date'];
    
    $result = assignShiftPattern($empID, $pattern_id, $start_date, $end_date, $managername, $conn);
    
    if ($result['success']) {
      $_SESSION['success'] = $result['message'];
      
      // Get pattern details for email
      $pattern_stmt = $conn->prepare("SELECT pattern_name, description, cycle_days FROM shift_patterns WHERE pattern_id = ?");
      $pattern_stmt->bind_param("i", $pattern_id);
      $pattern_stmt->execute();
      $pattern_result = $pattern_stmt->get_result();
      $pattern_data = $pattern_result->fetch_assoc();
      
      // Send pattern assignment notification email
      $patternDetails = array_merge($pattern_data, [
          'start_date' => $start_date,
          'end_date' => $end_date,
          'assigned_by' => $managername
      ]);
      sendShiftNotification($empID, $patternDetails, 'shift_pattern_assigned', $conn);
    } else {
      $_SESSION['error'] = $result['message'];
    }
  }

  // NEW: Create shift pattern
  if (isset($_POST['create_pattern'])) {
    $pattern_name = $_POST['pattern_name'];
    $description = $_POST['pattern_description'];
    $cycle_days = $_POST['cycle_days'];
    
    // Process pattern days
    $pattern_days = [];
    for ($i = 1; $i <= $cycle_days; $i++) {
        if (isset($_POST["day_{$i}_shift"]) && !empty($_POST["day_{$i}_shift"])) {
            $pattern_days[] = [
                'day_number' => $i,
                'shift_id' => $_POST["day_{$i}_shift"]
            ];
        }
    }
    
    if (empty($pattern_days)) {
        $_SESSION['error'] = "Please assign at least one shift to the pattern.";
    } else {
        $result = createShiftPattern($pattern_name, $description, $cycle_days, $pattern_days, $conn);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
  }

  // NEW: Update shift pattern
  if (isset($_POST['update_pattern'])) {
    $pattern_id = $_POST['pattern_id'];
    $pattern_name = $_POST['pattern_name'];
    $description = $_POST['pattern_description'];
    $cycle_days = $_POST['cycle_days'];
    
    // Process pattern days
    $pattern_days = [];
    for ($i = 1; $i <= $cycle_days; $i++) {
        if (isset($_POST["day_{$i}_shift"]) && !empty($_POST["day_{$i}_shift"])) {
            $pattern_days[] = [
                'day_number' => $i,
                'shift_id' => $_POST["day_{$i}_shift"]
            ];
        }
    }
    
    if (empty($pattern_days)) {
        $_SESSION['error'] = "Please assign at least one shift to the pattern.";
    } else {
        $result = updateShiftPattern($pattern_id, $pattern_name, $description, $cycle_days, $pattern_days, $conn);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
  }

  // NEW: Delete shift pattern
  if (isset($_POST['delete_pattern'])) {
    $pattern_id = $_POST['pattern_id'];
    
    $result = deleteShiftPattern($pattern_id, $conn);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
  }
  
  // Redirect with tab anchor
  $active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : '';
  header("Location: " . $_SERVER['PHP_SELF'] . "#" . $active_tab);
  exit();
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
  // Set JSON header for all AJAX responses
  header('Content-Type: application/json');
  
  try {
    if ($_GET['ajax'] == 'get_employee_shifts') {
      $empID = $_GET['empID'] ?? '';
      $month = $_GET['month'] ?? date('n');
      $year = $_GET['year'] ?? date('Y');
      
      $start_date = "$year-$month-01";
      $end_date = date('Y-m-t', strtotime($start_date));
      
      $stmt = $conn->prepare("
        SELECT ess.*, st.shift_name, st.time_in, st.time_out 
        FROM employee_shift_schedule ess 
        JOIN shift_templates st ON ess.shift_id = st.shift_id 
        WHERE ess.empID = ? AND ess.schedule_date BETWEEN ? AND ?
        ORDER BY ess.schedule_date
      ");
      $stmt->bind_param("sss", $empID, $start_date, $end_date);
      $stmt->execute();
      $result = $stmt->get_result();
      
      $shifts = [];
      while ($row = $result->fetch_assoc()) {
        $shifts[] = $row;
      }
      
      echo json_encode($shifts);
      exit();
    }
    
    if ($_GET['ajax'] == 'get_calendar_events') {
      $month = $_GET['month'] ?? date('n');
      $year = $_GET['year'] ?? date('Y');
      
      $start_date = "$year-$month-01";
      $end_date = date('Y-m-t', strtotime($start_date));
      
      // Get shifts for the month
      $shift_stmt = $conn->prepare("
        SELECT ess.schedule_date, COUNT(*) as shift_count, st.shift_name
        FROM employee_shift_schedule ess
        JOIN shift_templates st ON ess.shift_id = st.shift_id
        WHERE ess.schedule_date BETWEEN ? AND ?
        GROUP BY ess.schedule_date, st.shift_name
      ");
      $shift_stmt->bind_param("ss", $start_date, $end_date);
      $shift_stmt->execute();
      $shift_result = $shift_stmt->get_result();
      
      $shift_events = [];
      while ($row = $shift_result->fetch_assoc()) {
        $shift_events[$row['schedule_date']][] = [
          'type' => 'shift',
          'shift_name' => $row['shift_name'],
          'count' => $row['shift_count']
        ];
      }
      
      // Get leaves for the month
      $leave_stmt = $conn->prepare("
        SELECT from_date, to_date, fullname, leave_type_name 
        FROM leave_request 
        WHERE status = 'Approved' 
        AND ((from_date BETWEEN ? AND ?) OR (to_date BETWEEN ? AND ?) OR (from_date <= ? AND to_date >= ?))
      ");
      $leave_stmt->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
      $leave_stmt->execute();
      $leave_result = $leave_stmt->get_result();
      
      $leave_events = [];
      while ($row = $leave_result->fetch_assoc()) {
        $start = new DateTime($row['from_date']);
        $end = new DateTime($row['to_date']);
        
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
          $date_str = $date->format('Y-m-d');
          if ($date_str >= $start_date && $date_str <= $end_date) {
            $leave_events[$date_str][] = [
              'type' => 'leave',
              'employee' => $row['fullname'],
              'leave_type' => $row['leave_type_name']
            ];
          }
        }
      }
      
      // Get expected staff for the month
      $expected_staff = getExpectedEmployeesPerDay($conn, $start_date, $end_date);
      $expected_staff_events = [];
      foreach ($expected_staff as $staff) {
        $date_str = $staff['date'];
        $expected_staff_events[$date_str][] = [
          'type' => 'expected_staff',
          'department' => $staff['department'],
          'shift_name' => $staff['shift_name'],
          'expected_employees' => $staff['expected_employees'],
          'staffing_requirement' => $staff['staffing_requirement'],
          'staffing_gap' => $staff['staffing_gap']
        ];
      }
      
      // Get staffing gaps
      $staffing_stmt = $conn->prepare("
        SELECT es.day_of_week, es.required_count, es.department, st.shift_name,
               COUNT(ess.schedule_id) as scheduled_count
        FROM expected_staffing es
        JOIN shift_templates st ON es.shift_id = st.shift_id
        LEFT JOIN employee_shift_schedule ess ON es.shift_id = ess.shift_id 
          AND DAYNAME(ess.schedule_date) = es.day_of_week 
          AND ess.schedule_date BETWEEN ? AND ?
        WHERE es.day_of_week IN ('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
        GROUP BY es.day_of_week, es.required_count, es.department, st.shift_name
        HAVING scheduled_count < es.required_count
      ");
      $staffing_stmt->bind_param("ss", $start_date, $end_date);
      $staffing_stmt->execute();
      $staffing_result = $staffing_stmt->get_result();
      
      $staffing_gaps = [];
      while ($row = $staffing_result->fetch_assoc()) {
        // For each day in the month that matches the day_of_week
        $current = new DateTime($start_date);
        $end_dt = new DateTime($end_date);
        
        while ($current <= $end_dt) {
          if ($current->format('l') == $row['day_of_week']) {
            $date_str = $current->format('Y-m-d');
            $gap = $row['required_count'] - $row['scheduled_count'];
            if ($gap > 0) {
              $staffing_gaps[$date_str][] = [
                'type' => 'staffing',
                'department' => $row['department'],
                'shift' => $row['shift_name'],
                'gap' => $gap
              ];
            }
          }
          $current->modify('+1 day');
        }
      }
      
      $events = [
        'shifts' => $shift_events,
        'leaves' => $leave_events,
        'staffing_gaps' => $staffing_gaps,
        'expected_staff' => $expected_staff_events
      ];
      
      echo json_encode($events);
      exit();
    }
    
    if ($_GET['ajax'] == 'get_employee_details') {
      $empID = $_GET['empID'] ?? '';
      
      $stmt = $conn->prepare("
        SELECT e.*, s.shift_name, s.shift_id 
        FROM employee e 
        LEFT JOIN shift_templates s ON e.default_shift_id = s.shift_id 
        WHERE e.empID = ?
      ");
      $stmt->bind_param("s", $empID);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        echo json_encode($employee);
      } else {
        echo json_encode(['error' => 'Employee not found']);
      }
      exit();
    }
    
    if ($_GET['ajax'] == 'get_staffing_overview') {
      $startDate = $_GET['start_date'] ?? date('Y-m-01');
      $endDate = $_GET['end_date'] ?? date('Y-m-t');
      
      $expectedStaff = getExpectedEmployeesPerDay($conn, $startDate, $endDate);
      echo json_encode($expectedStaff);
      exit();
    }

    if ($_GET['ajax'] == 'get_expected_employees') {
      $date = $_GET['date'] ?? date('Y-m-d');
      $expectedStaff = getExpectedEmployeesForDate($conn, $date);
      echo json_encode($expectedStaff);
      exit();
    }

    if ($_GET['ajax'] == 'get_staffing_status') {
      $date = $_GET['date'] ?? date('Y-m-d');
      $dayOfWeek = date('l', strtotime($date));
      
      $query = "
          SELECT 
              es.staffing_id,
              es.department,
              st.shift_name,
              es.day_of_week,
              es.required_count,
              COUNT(ess.empID) as expected_count,
              (es.required_count - COUNT(ess.empID)) as staffing_gap
          FROM expected_staffing es
          JOIN shift_templates st ON es.shift_id = st.shift_id
          LEFT JOIN employee_shift_schedule ess ON 
              es.shift_id = ess.shift_id 
              AND ess.schedule_date = ?
              AND EXISTS (
                  SELECT 1 FROM employee e 
                  WHERE e.empID = ess.empID 
                  AND e.department = es.department
                  AND (e.type_name = es.employment_status OR es.employment_status = 'Any')
              )
          WHERE es.day_of_week = ?
          GROUP BY es.staffing_id, es.department, st.shift_name, es.day_of_week, es.required_count
          ORDER BY es.department, st.shift_name
      ";
      
      $stmt = $conn->prepare($query);
      $stmt->bind_param("ss", $date, $dayOfWeek);
      $stmt->execute();
      $result = $stmt->get_result();
      
      $staffingStatus = [];
      while ($row = $result->fetch_assoc()) {
          $staffingStatus[] = $row;
      }
      
      echo json_encode($staffingStatus);
      exit();
    }

    // NEW AJAX HANDLERS FOR SHIFT PATTERNS
    if ($_GET['ajax'] == 'get_shift_patterns') {
      $stmt = $conn->query("SELECT * FROM shift_patterns WHERE is_active = 1 ORDER BY pattern_name");
      $patterns = [];
      while ($row = $stmt->fetch_assoc()) {
          $patterns[] = $row;
      }
      echo json_encode($patterns);
      exit();
    }

    if ($_GET['ajax'] == 'get_employee_patterns') {
      $stmt = $conn->query("
          SELECT esp.*, e.fullname, e.department, e.empID, sp.pattern_name 
          FROM employee_shift_pattern esp
          JOIN employee e ON esp.empID = e.empID
          JOIN shift_patterns sp ON esp.pattern_id = sp.pattern_id
          ORDER BY esp.start_date DESC
      ");
      $employee_patterns = [];
      while ($row = $stmt->fetch_assoc()) {
          $employee_patterns[] = $row;
      }
      echo json_encode($employee_patterns);
      exit();
    }

    if ($_GET['ajax'] == 'predict_shifts') {
      $empID = $_GET['empID'] ?? '';
      $weeks = $_GET['weeks'] ?? 4;
      
      if (empty($empID)) {
          echo json_encode(["success" => false, "message" => "Employee ID required"]);
          exit();
      }
      
      $result = predictFutureShifts($empID, $weeks, $conn);
      echo json_encode($result);
      exit();
    }

    if ($_GET['ajax'] == 'get_pattern_details') {
      $pattern_id = $_GET['pattern_id'] ?? '';
      
      $stmt = $conn->prepare("
          SELECT spd.*, st.shift_name, st.time_in, st.time_out
          FROM shift_pattern_details spd
          JOIN shift_templates st ON spd.shift_id = st.shift_id
          WHERE spd.pattern_id = ?
          ORDER BY spd.day_number
      ");
      $stmt->bind_param("i", $pattern_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      $pattern_details = [];
      while ($row = $result->fetch_assoc()) {
          $pattern_details[] = $row;
      }
      
      echo json_encode($pattern_details);
      exit();
    }

    if ($_GET['ajax'] == 'get_pattern_info') {
      $pattern_id = $_GET['pattern_id'] ?? '';
      
      $stmt = $conn->prepare("SELECT * FROM shift_patterns WHERE pattern_id = ?");
      $stmt->bind_param("i", $pattern_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
          $pattern = $result->fetch_assoc();
          echo json_encode($pattern);
      } else {
          echo json_encode(['error' => 'Pattern not found']);
      }
      exit();
    }

    // NEW: All Schedules AJAX Handlers
    if ($_GET['ajax'] == 'get_all_schedules') {
        $date = $_GET['date'] ?? date('Y-m-d');
        $department = $_GET['department'] ?? '';
        $shift = $_GET['shift'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        // Build query
        $query = "
            SELECT ess.*, e.fullname, e.department, e.position, 
                   st.shift_name, st.time_in, st.time_out
            FROM employee_shift_schedule ess
            JOIN employee e ON ess.empID = e.empID
            JOIN shift_templates st ON ess.shift_id = st.shift_id
            WHERE 1=1
        ";
        
        $countQuery = "
            SELECT COUNT(*) as total
            FROM employee_shift_schedule ess
            JOIN employee e ON ess.empID = e.empID
            JOIN shift_templates st ON ess.shift_id = st.shift_id
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';
        
        if (!empty($date)) {
            $query .= " AND ess.schedule_date = ?";
            $countQuery .= " AND ess.schedule_date = ?";
            $params[] = $date;
            $types .= 's';
        }
        
        if (!empty($department)) {
            $query .= " AND e.department = ?";
            $countQuery .= " AND e.department = ?";
            $params[] = $department;
            $types .= 's';
        }
        
        if (!empty($shift)) {
            $query .= " AND ess.shift_id = ?";
            $countQuery .= " AND ess.shift_id = ?";
            $params[] = $shift;
            $types .= 'i';
        }
        
        $query .= " ORDER BY ess.schedule_date DESC, e.department, e.fullname LIMIT ? OFFSET ?";
        
        // Get total count
        if (!empty($params)) {
            $countStmt = $conn->prepare($countQuery);
            $countParams = $params;
            $countTypes = $types;
            $countStmt->bind_param($countTypes, ...$countParams);
            $countStmt->execute();
        } else {
            $countStmt = $conn->query($countQuery);
        }
        
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        
        // Get schedules
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $allParams = array_merge($params, [$limit, $offset]);
            $allTypes = $types . 'ii';
            $stmt->bind_param($allTypes, ...$allParams);
        } else {
            // Handle case with no filters
            $stmt = $conn->prepare($query . " LIMIT ? OFFSET ?");
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        echo json_encode([
            'schedules' => $schedules,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit();
    }

    if ($_GET['ajax'] == 'update_schedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $schedule_id = $_POST['schedule_id'];
      $shift_id = $_POST['shift_id'];
      $status = $_POST['status'];
      
      $stmt = $conn->prepare("UPDATE employee_shift_schedule SET shift_id = ?, status = ? WHERE schedule_id = ?");
      $stmt->bind_param("isi", $shift_id, $status, $schedule_id);
      
      if ($stmt->execute()) {
          echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
      } else {
          echo json_encode(['success' => false, 'message' => 'Error updating schedule: ' . $conn->error]);
      }
      exit();
    }

    if ($_GET['ajax'] == 'delete_schedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $schedule_id = $_POST['schedule_id'];
      
      $stmt = $conn->prepare("DELETE FROM employee_shift_schedule WHERE schedule_id = ?");
      $stmt->bind_param("i", $schedule_id);
      
      if ($stmt->execute()) {
          echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
      } else {
          echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error]);
      }
      exit();
    }

  } catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
  }
}

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = intval($firstDay->format('t'));
$startWeekday = intval($firstDay->format('w'));

// Get shift templates
$shift_templates = [];
$shift_result = $conn->query("SELECT * FROM shift_templates ORDER BY time_in");
while ($row = $shift_result->fetch_assoc()) {
  $shift_templates[] = $row;
}

// Get departments
$departments = [];
$dept_result = $conn->query("SELECT DISTINCT department FROM employee ORDER BY department");
while ($row = $dept_result->fetch_assoc()) {
  $departments[] = $row['department'];
}

// Get employees for dropdowns with pagination - MODIFIED TO SORT BY EMPLOYEE NUMBER
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(10, intval($_GET['per_page'])) : 20;
$offset = ($page - 1) * $per_page;

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM employee");
$total_row = $count_result->fetch_assoc();
$total_employees = $total_row['total'];
$total_pages = ceil($total_employees / $per_page);

// Get employees with pagination - MODIFIED TO SORT BY EMPLOYEE NUMBER
$employees = [];
$emp_result = $conn->query("
    SELECT empID, fullname, department, position, 
           shift_type, work_hours_per_week, 
           default_shift_id
    FROM employee 
    ORDER BY 
        CASE 
            WHEN empID REGEXP '^emp-[0-9]+$' 
            THEN CAST(SUBSTRING(empID, 5) AS UNSIGNED)
            ELSE 999999
        END,
        empID
    LIMIT $per_page OFFSET $offset
");
while ($row = $emp_result->fetch_assoc()) {
    $employees[] = $row;
}

// Get staffing requirements
$staffing_requirements = [];
$staff_result = $conn->query("
  SELECT es.*, st.shift_name 
  FROM expected_staffing es 
  JOIN shift_templates st ON es.shift_id = st.shift_id
  ORDER BY es.department, es.day_of_week
");
while ($row = $staff_result->fetch_assoc()) {
  $staffing_requirements[] = $row;
}

// Get shift patterns
$shift_patterns = [];
$pattern_result = $conn->query("SELECT * FROM shift_patterns WHERE is_active = 1 ORDER BY pattern_name");
while ($row = $pattern_result->fetch_assoc()) {
  $shift_patterns[] = $row;
}

// Get leave data for filtering
$leaves = [];
$leave_stmt = $conn->prepare("SELECT empID, fullname, department, position, type_name, request_type_id, request_type_name, leave_type_name, action_by, from_date, to_date, duration FROM leave_request WHERE status = 'Approved' ORDER BY from_date ASC");
$leave_stmt->execute();
$leave_res = $leave_stmt->get_result();
while ($row = $leave_res->fetch_assoc()) { 
  $leaves[] = $row; 
}

$filterDept = isset($_GET['dept']) ? $_GET['dept'] : '';
$filterType = isset($_GET['ltype']) ? $_GET['ltype'] : '';
$filterName = isset($_GET['q']) ? $_GET['q'] : '';

$allDept = [];
$allTypes = [];
foreach ($leaves as $lv) {
  if (!empty($lv['department'])) $allDept[$lv['department']] = true;
  if (!empty($lv['leave_type_name'])) $allTypes[$lv['leave_type_name']] = true;
}
$allDept = array_keys($allDept);
sort($allDept);
$allTypes = array_keys($allTypes);
sort($allTypes);

$displayLeaves = array_values(array_filter($leaves, function($lv) use ($filterDept, $filterType, $filterName) {
  if ($filterDept !== '' && $lv['department'] !== $filterDept) return false;
  if ($filterType !== '' && $lv['leave_type_name'] !== $filterType) return false;
  if ($filterName !== '' && stripos($lv['fullname'], $filterName) === false) return false;
  return true;
}));

// Get employee shift schedules for the current month
$employee_schedules = [];
$schedule_stmt = $conn->prepare("
  SELECT ess.*, e.fullname, e.department, st.shift_name, st.time_in, st.time_out
  FROM employee_shift_schedule ess
  JOIN employee e ON ess.empID = e.empID
  JOIN shift_templates st ON ess.shift_id = st.shift_id
  WHERE MONTH(ess.schedule_date) = ? AND YEAR(ess.schedule_date) = ?
  ORDER BY ess.schedule_date, e.department
");
$schedule_stmt->bind_param("ii", $month, $year);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
while ($row = $schedule_result->fetch_assoc()) {
  $employee_schedules[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Calendar</title>
  
  <!-- Bootstrap CSS -->
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
  margin: 0;
  padding: 0;
  min-height: 100vh;
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
  padding: 20px 30px;
  margin-left: 220px;
  width: calc(100% - 220px);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content h1 {
  color: #1E3A8A;
  font-weight: 700;
  margin-bottom: 20px;
  font-size: 1.8rem;
}

.sidebar-name {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: white;
  padding: 10px;
  margin-bottom: 30px;
  font-size: 16px;
  flex-direction: column;
  line-height: 1.4;
}

/* TABS CONTAINER - FIXED POSITIONING */
.tabs-container {
  background: white;
  border-radius: 10px 10px 0 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-tabs {
  border-bottom: 1px solid #dee2e6;
  padding: 0 15px;
  margin-bottom: 0;
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
}

.nav-tabs .nav-link {
  color: #1E3A8A;
  font-weight: 500;
  border: none;
  border-radius: 0;
  padding: 12px 20px;
  white-space: nowrap;
  transition: all 0.3s ease;
  margin-bottom: -1px;
}

.nav-tabs .nav-link:hover {
  border: none;
  background-color: #f8f9fa;
  color: #1E3A8A;
}

.nav-tabs .nav-link.active {
  background-color: #1E3A8A;
  color: white;
  border: none;
  border-bottom: 3px solid #1E3A8A;
}

.nav-tabs .nav-link i {
  margin-right: 8px;
  font-size: 0.9rem;
}

/* TAB CONTENT AREA */
.tab-content {
  background: white;
  border-radius: 0 0 10px 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 25px;
  margin-top: 0;
  flex: 1;
}

.tab-pane {
  min-height: 500px;
}

/* Calendar Styles */
.calendar-container {
  background-color: white;
  border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 10px;
}

.calendar-nav-btn {
  background-color: #1E3A8A;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.calendar-nav-btn:hover {
  background-color: #152C6B;
}

.calendar-month-year {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1E3A8A;
  text-align: center;
  flex: 1;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  font-weight: 600;
  margin-bottom: 10px;
  color: #1E3A8A;
  background: #f8f9fa;
  padding: 10px 0;
  border-radius: 5px;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 5px;
}

.calendar-day {
  min-height: 120px;
  border: 1px solid #e0e0e0;
  padding: 8px;
  background-color: white;
  border-radius: 5px;
  overflow-y: auto;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.calendar-day:hover {
  background-color: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.calendar-day.other-month {
  background-color: #f8f9fa;
  color: #adb5bd;
}

.calendar-day-header {
  font-weight: 600;
  margin-bottom: 5px;
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.calendar-event {
  font-size: 0.7rem;
  padding: 3px 6px;
  margin-bottom: 3px;
  border-radius: 3px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: pointer;
}

.event-leave {
  background-color: #FFE8E8;
  color: #D32F2F;
  border-left: 3px solid #D32F2F;
}

.event-shift {
  background-color: #E8F4FD;
  color: #1976D2;
  border-left: 3px solid #1976D2;
}

.event-staffing {
  background-color: #E8F5E9;
  color: #388E3C;
  border-left: 3px solid #388E3C;
}

.event-expected-staff {
  background-color: #E3F2FD;
  color: #1565C0;
  border-left: 3px solid #1565C0;
}

.event-pattern {
  background-color: #FFF3E0;
  color: #EF6C00;
  border-left: 3px solid #EF6C00;
}

/* Table Styles */
.table-container {
  background-color: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 25px;
  margin-bottom: 20px;
  overflow-x: auto;
}

.table-container h3 {
  color: #1E3A8A;
  margin-bottom: 20px;
  font-size: 1.4rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 800px;
}

th, td {
  text-align: left;
  padding: 12px 15px;
  border: 1px solid #e0e0e0;
}

th {
  background-color: #1E3A8A;
  color: white;
  font-weight: 600;
  position: sticky;
  top: 0;
}

td {
  color: #333;
  vertical-align: middle;
}

tbody tr:hover {
  background-color: #F2F6FF;
}

/* Filter Controls */
.filter-controls {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  align-items: center;
}

.filter-controls select, .filter-controls input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 5px;
  min-width: 150px;
}

/* Button Styles */
.btn {
  border-radius: 5px;
  padding: 8px 16px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.875rem;
}

.btn-primary {
  background-color: #1E3A8A;
  border-color: #1E3A8A;
}

.btn-primary:hover {
  background-color: #152C6B;
  border-color: #152C6B;
}

/* Shift Badges */
.shift-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.shift-morning {
  background-color: #E3F2FD;
  color: #1565C0;
}

.shift-afternoon {
  background-color: #FFF3E0;
  color: #EF6C00;
}

.shift-night {
  background-color: #E8EAF6;
  color: #303F9F;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-scheduled {
  background-color: #E8F5E9;
  color: #2E7D32;
}

.status-completed {
  background-color: #E3F2FD;
  color: #1565C0;
}

.status-absent {
  background-color: #FFEBEE;
  color: #C62828;
}

.status-on-leave {
  background-color: #FFF3E0;
  color: #EF6C00;
}

.employee-info-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
}

.days-checkbox-container {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 10px 0;
}

.day-checkbox {
  flex: 0 0 calc(33.333% - 10px);
}

/* Pattern Builder Styles */
.pattern-builder {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin: 15px 0;
}

.pattern-day {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  padding: 10px;
  background: white;
  border-radius: 5px;
  border: 1px solid #dee2e6;
}

.pattern-day-label {
  min-width: 120px;
  font-weight: 500;
}

.pattern-day-select {
  flex: 1;
  max-width: 200px;
}

/* Enhanced Staffing Status Styles */
.staffing-status-good {
    background-color: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.staffing-status-warning {
    background-color: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.staffing-status-danger {
    background-color: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.expected-employees-modal .table th {
    background-color: #1E3A8A;
    color: white;
}

.employee-list-popup {
    max-height: 200px;
    overflow-y: auto;
}

.employee-list-item {
    padding: 5px 10px;
    border-bottom: 1px solid #eee;
    font-size: 0.85rem;
}

.employee-list-item:last-child {
    border-bottom: none;
}

/* Modal Improvements */
.modal-header {
  background-color: #1E3A8A;
  color: white;
  border-bottom: none;
}

.modal-header .btn-close {
  filter: invert(1);
}

/* Pagination Styles */
.pagination {
    margin-top: 20px;
}

.page-link {
    color: #1E3A8A;
    border: 1px solid #dee2e6;
    padding: 8px 16px;
}

.page-item.active .page-link {
    background-color: #1E3A8A;
    border-color: #1E3A8A;
    color: white;
}

.page-item.disabled .page-link {
    color: #6c757d;
    background-color: #f8f9fa;
}

/* Progress bar for work hours */
.progress {
    border-radius: 10px;
    background-color: #e9ecef;
    margin-top: 5px;
}

.progress-bar {
    background-color: #1E3A8A;
    border-radius: 10px;
    font-size: 0.75rem;
    line-height: 20px;
    text-align: center;
}

/* Badge styles */
.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

/* Employee ID display */
td:first-child {
    min-width: 100px;
}

/* Button group for actions */
.btn-group {
    flex-wrap: wrap;
    gap: 2px;
}

.btn-group .btn {
    padding: 4px 8px;
    font-size: 0.75rem;
}

/* Responsive table */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    
    .nav-tabs {
        padding: 0 10px;
    }
    
    .nav-tabs .nav-link {
        padding: 10px 15px;
        font-size: 0.9rem;
    }
    
    .calendar-day {
        min-height: 80px;
        font-size: 0.8rem;
    }
    
    .calendar-event {
        font-size: 0.6rem;
        padding: 2px 4px;
    }
    
    .filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-controls select,
    .filter-controls input {
        width: 100%;
        min-width: auto;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .btn-group .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Scrollbar Styling */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
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
      <h1><i class="fa-solid fa-calendar-days"></i> Manager Scheduling</h1>
    </div>

    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Tabs for different views -->
    <ul class="nav nav-tabs" id="schedulingTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab" aria-controls="calendar" aria-selected="true">
          <i class="fa-solid fa-calendar"></i> Calendar View
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts" type="button" role="tab" aria-controls="shifts" aria-selected="false">
          <i class="fa-solid fa-clock"></i> Shift Management
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="patterns-tab" data-bs-toggle="tab" data-bs-target="#patterns" type="button" role="tab" aria-controls="patterns" aria-selected="false">
          <i class="fa-solid fa-calendar-week"></i> Shift Patterns
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="all-schedules-tab" data-bs-toggle="tab" data-bs-target="#all-schedules" type="button" role="tab" aria-controls="all-schedules" aria-selected="false">
          <i class="fa-solid fa-list-check"></i> All Schedules
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="staffing-tab" data-bs-toggle="tab" data-bs-target="#staffing" type="button" role="tab" aria-controls="staffing" aria-selected="false">
          <i class="fa-solid fa-users"></i> Staffing Requirements
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="staffing-overview-tab" data-bs-toggle="tab" data-bs-target="#staffing-overview" type="button" role="tab" aria-controls="staffing-overview" aria-selected="false">
          <i class="fa-solid fa-chart-bar"></i> Staffing Overview
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab" aria-controls="leaves" aria-selected="false">
          <i class="fa-solid fa-umbrella-beach"></i> Leave Schedule
        </button>
      </li>
    </ul>

    <div class="tab-content" id="schedulingTabsContent">
      <!-- Calendar Tab -->
      <div class="tab-pane fade show active" id="calendar" role="tabpanel" aria-labelledby="calendar-tab">
        <div class="calendar-container">
          <div class="calendar-header">
            <button class="calendar-nav-btn" onclick="changeMonth(-1)">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </button>
            <div class="calendar-month-year" id="current-month-year">
              <?php echo $firstDay->format('F Y'); ?>
            </div>
            <button class="calendar-nav-btn" onclick="changeMonth(1)">
              Next <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>

          <div class="calendar-weekdays">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
          </div>

          <div class="calendar-days" id="calendar-days">
            <!-- Calendar days will be populated by JavaScript -->
          </div>
        </div>
      </div>

      <!-- Shifts Tab -->
      <div class="tab-pane fade" id="shifts" role="tabpanel" aria-labelledby="shifts-tab">
        <div class="table-container">
          <div class="filter-controls">
            <!-- Entries filter -->
            <div class="d-flex align-items-center">
              <span class="me-2">Show</span>
              <select id="entries-per-page" class="form-select" style="width: auto;" onchange="changePerPage(this.value)">
                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
              </select>
              <span class="ms-2">entries</span>
            </div>
            
            <!-- Search filter -->
            <input type="text" id="shift-search" class="form-control" placeholder="Search employee..." style="width: auto;">
            
            <!-- Department filter -->
            <select id="shift-dept-filter" class="form-select" style="width: auto;">
              <option value="">All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
              <?php endforeach; ?>
            </select>
            
            <!-- Shift type filter -->
            <select id="shift-type-filter" class="form-select" style="width: auto;">
              <option value="">All Shift Types</option>
              <option value="Fixed">Fixed</option>
              <option value="Rotational">Rotational</option>
            </select>
          </div>

          <table id="shifts-table" class="table table-striped">
            <thead>
              <tr>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Shift Type</th>
                <th>Default Shift</th>
                <th>Work Hours/Week</th>
                <th>Current Pattern</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              // Sort employees by employee number
              usort($employees, function($a, $b) {
                  $getEmpNumber = function($empID) {
                      $match = [];
                      if (preg_match('/emp-(\d+)/i', $empID, $match)) {
                          return intval($match[1]);
                      }
                      return 999999;
                  };
                  
                  $numA = $getEmpNumber($a['empID']);
                  $numB = $getEmpNumber($b['empID']);
                  
                  return $numA - $numB;
              });
              
              foreach ($employees as $emp): 
                // Get shift name if default shift exists
                $default_shift_name = '';
                if (!empty($emp['default_shift_id'])) {
                  $shift_stmt = $conn->prepare("SELECT shift_name FROM shift_templates WHERE shift_id = ?");
                  $shift_stmt->bind_param("i", $emp['default_shift_id']);
                  $shift_stmt->execute();
                  $shift_result = $shift_stmt->get_result();
                  if ($shift_row = $shift_result->fetch_assoc()) {
                    $default_shift_name = $shift_row['shift_name'];
                  }
                }
                
                // Get current pattern
                $pattern_stmt = $conn->prepare("
                  SELECT sp.pattern_name 
                  FROM employee_shift_pattern esp
                  JOIN shift_patterns sp ON esp.pattern_id = sp.pattern_id
                  WHERE esp.empID = ? AND esp.end_date >= CURDATE()
                  LIMIT 1
                ");
                $pattern_stmt->bind_param("s", $emp['empID']);
                $pattern_stmt->execute();
                $pattern_result = $pattern_stmt->get_result();
                $pattern_name = $pattern_result->num_rows > 0 ? $pattern_result->fetch_assoc()['pattern_name'] : 'No Pattern';
              ?>
              <tr>
                <td>
                  <span class="badge bg-primary"><?php echo htmlspecialchars($emp['empID']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($emp['fullname']); ?></td>
                <td><?php echo htmlspecialchars($emp['department']); ?></td>
                <td><?php echo htmlspecialchars($emp['position']); ?></td>
                <td>
                  <?php if ($emp['shift_type'] == 'Fixed'): ?>
                    <span class="badge bg-success">Fixed</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Rotational</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($default_shift_name): ?>
                    <span class="shift-badge shift-<?php echo strtolower($default_shift_name); ?>">
                      <?php echo htmlspecialchars($default_shift_name); ?>
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Not Set</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="progress" style="height: 20px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?php echo min(100, ($emp['work_hours_per_week'] / 40) * 100); ?>%">
                      <?php echo htmlspecialchars($emp['work_hours_per_week']); ?> hrs
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($pattern_name != 'No Pattern'): ?>
                    <span class="badge bg-info"><?php echo htmlspecialchars($pattern_name); ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($pattern_name); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group" role="group" aria-label="Employee Actions">
                    <button class='btn btn-sm btn-primary assign-shift-btn' 
                            data-emp-id="<?php echo htmlspecialchars($emp['empID']); ?>" 
                            data-bs-toggle='modal' 
                            data-bs-target='#assignShiftModal'
                            title="Assign Single Shift">
                      <i class='fa-solid fa-calendar-plus'></i>
                    </button>
                    <button class='btn btn-sm btn-info edit-default-shift-btn' 
                            data-emp-id="<?php echo htmlspecialchars($emp['empID']); ?>" 
                            data-bs-toggle='modal' 
                            data-bs-target='#editDefaultShiftModal'
                            title="Edit Settings">
                      <i class='fa-solid fa-gear'></i>
                    </button>
                    <button class='btn btn-sm btn-warning multiple-shift-btn' 
                            data-emp-id="<?php echo htmlspecialchars($emp['empID']); ?>" 
                            data-bs-toggle='modal' 
                            data-bs-target='#multipleShiftModal'
                            title="Assign Multiple Shifts">
                      <i class='fa-solid fa-calendar-week'></i>
                    </button>
                    <button class='btn btn-sm btn-success assign-pattern-btn' 
                            data-emp-id="<?php echo htmlspecialchars($emp['empID']); ?>" 
                            data-bs-toggle='modal' 
                            data-bs-target='#assignPatternModal'
                            title="Assign Pattern">
                      <i class='fa-solid fa-calendar-alt'></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <!-- Pagination -->
          <nav aria-label="Employee pagination">
            <ul class="pagination justify-content-center">
              <!-- Previous button -->
              <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>#shifts">
                  <i class="fa-solid fa-chevron-left"></i> Previous
                </a>
              </li>
              
              <!-- Page numbers -->
              <?php
              // Show page numbers
              $max_pages_to_show = 5;
              $start_page = max(1, $page - floor($max_pages_to_show / 2));
              $end_page = min($total_pages, $start_page + $max_pages_to_show - 1);
              
              if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&per_page=' . $per_page . '#shifts">1</a></li>';
                if ($start_page > 2) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
              }
              
              for ($i = $start_page; $i <= $end_page; $i++) {
                $active = $i == $page ? 'active' : '';
                echo '<li class="page-item ' . $active . '">';
                echo '<a class="page-link" href="?page=' . $i . '&per_page=' . $per_page . '#shifts">' . $i . '</a>';
                echo '</li>';
              }
              
              if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&per_page=' . $per_page . '#shifts">' . $total_pages . '</a></li>';
              }
              ?>
              
              <!-- Next button -->
              <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>#shifts">
                  Next <i class="fa-solid fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
          
          <!-- Showing X of Y entries -->
          <div class="text-center text-muted">
            Showing <?php echo min($per_page, count($employees)); ?> of <?php echo $total_employees; ?> employees
          </div>
        </div>
      </div>

      <!-- NEW: Shift Patterns Tab -->
      <div class="tab-pane fade" id="patterns" role="tabpanel" aria-labelledby="patterns-tab">
        <div class="table-container">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Shift Pattern Management</h3>
            <div>
              <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPatternModal">
                <i class="fa-solid fa-plus"></i> Create Pattern
              </button>
            </div>
          </div>

          <!-- Filter controls for entries -->
          <div class="filter-controls mb-3">
            <div class="d-flex align-items-center">
              <span class="me-2">Show</span>
              <select id="pattern-entries-per-page" class="form-select" style="width: auto;" onchange="changePatternPerPage(this.value)">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="ms-2">entries</span>
            </div>
            
            <!-- Search filter -->
            <input type="text" id="pattern-search" class="form-control" placeholder="Search pattern..." style="width: auto;">
          </div>

          <!-- Employee Patterns Table -->
          <div class="mb-4">
            <h4>Employee Shift Patterns</h4>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Pattern</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="employee-patterns-body">
                  <!-- Will be populated by JavaScript -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Available Patterns -->
          <div>
            <h4>Available Shift Patterns</h4>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Pattern ID</th>
                    <th>Pattern Name</th>
                    <th>Description</th>
                    <th>Cycle Days</th>
                    <th>Pattern Details</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="shift-patterns-body">
                  <!-- Will be populated by JavaScript -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- All Schedules Tab -->
      <div class="tab-pane fade" id="all-schedules" role="tabpanel" aria-labelledby="all-schedules-tab">
        <div class="table-container">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>All Employee Schedules</h3>
            <div class="filter-controls">
              <input type="date" id="schedule-date-filter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
              <select id="schedule-dept-filter" class="form-select" style="width: auto;">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
              </select>
              <select id="schedule-shift-filter" class="form-select" style="width: auto;">
                <option value="">All Shifts</option>
                <?php foreach ($shift_templates as $shift): ?>
                  <option value="<?php echo $shift['shift_id']; ?>"><?php echo htmlspecialchars($shift['shift_name']); ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary" onclick="loadAllSchedules()">
                <i class="fa-solid fa-filter"></i> Filter
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Full Name</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Date</th>
                  <th>Shift</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="all-schedules-body">
                <!-- Schedules will be loaded here by JavaScript -->
              </tbody>
            </table>
          </div>
          
          <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="schedule-pagination-info"></div>
            <nav>
              <ul class="pagination" id="schedule-pagination">
                <!-- Pagination will be generated by JavaScript -->
              </ul>
            </nav>
          </div>
        </div>
      </div>

      <!-- Staffing Tab -->
      <div class="tab-pane fade" id="staffing" role="tabpanel" aria-labelledby="staffing-tab">
        <div class="table-container">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Expected Staffing Requirements</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffingModal">
              <i class="fa-solid fa-plus"></i> Add Requirement
            </button>
          </div>

          <!-- Date Filter for Staffing Requirements -->
          <div class="filter-controls mb-3">
            <input type="date" id="staffing-date-filter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            <button class="btn btn-outline-primary" onclick="loadStaffingRequirements()">
              <i class="fa-solid fa-filter"></i> Filter by Date
            </button>
          </div>

          <table id="staffing-table">
            <thead>
              <tr>
                <th>Department</th>
                <th>Shift</th>
                <th>Day of Week</th>
                <th>Required Count</th>
                <th>Expected Employees</th>
                <th>Staffing Gap</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="staffing-table-body">
              <?php 
              // Get current date for initial display
              $currentDate = date('Y-m-d');
              $currentDayOfWeek = date('l');
              
              foreach ($staffing_requirements as $staff): 
                // Calculate expected employees for this staffing requirement on current date
                $expected_count = 0;
                
                // Check if this staffing requirement applies to current day of week
                if ($staff['day_of_week'] === $currentDayOfWeek) {
                  $expected_stmt = $conn->prepare("
                    SELECT COUNT(*) as expected_count 
                    FROM employee_shift_schedule ess 
                    JOIN employee e ON ess.empID = e.empID 
                    JOIN shift_templates st ON ess.shift_id = st.shift_id 
                    WHERE e.department = ? 
                    AND st.shift_id = ? 
                    AND ess.schedule_date = ?
                    AND (e.type_name = ? OR ? = 'Any')
                  ");
                  $employment_status = $staff['employment_status'];
                  $expected_stmt->bind_param("siss", 
                    $staff['department'], 
                    $staff['shift_id'], 
                    $currentDate,
                    $employment_status,
                    $employment_status
                  );
                  $expected_stmt->execute();
                  $expected_result = $expected_stmt->get_result();
                  
                  if ($expected_row = $expected_result->fetch_assoc()) {
                    $expected_count = $expected_row['expected_count'];
                  }
                }
                
                $staffing_gap = $staff['required_count'] - $expected_count;
                $status_class = $staffing_gap > 0 ? 'text-danger' : 'text-success';
                $status_icon = $staffing_gap > 0 ? '❌ Understaffed' : '✅ Fully Staffed';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($staff['department']) . "</td>";
                echo "<td>" . htmlspecialchars($staff['shift_name']) . "</td>";
                echo "<td>" . htmlspecialchars($staff['day_of_week']) . "</td>";
                echo "<td>" . htmlspecialchars($staff['required_count']) . "</td>";
                echo "<td>" . $expected_count . "</td>";
                echo "<td class='$status_class'>" . ($staffing_gap > 0 ? "-" . $staffing_gap : "0") . "</td>";
                echo "<td class='$status_class'>" . $status_icon . "</td>";
                echo "<td>
                        <button class='btn btn-sm btn-warning edit-staffing-btn' data-id='" . $staff['staffing_id'] . "' data-bs-toggle='modal' data-bs-target='#editStaffingModal'>
                          <i class='fa-solid fa-pen'></i>
                        </button>
                        <button class='btn btn-sm btn-danger delete-staffing-btn' data-id='" . $staff['staffing_id'] . "' data-bs-toggle='modal' data-bs-target='#deleteStaffingModal'>
                          <i class='fa-solid fa-trash'></i>
                        </button>
                      </td>";
                echo "</tr>";
              endforeach; 
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Staffing Overview Tab -->
      <div class="tab-pane fade" id="staffing-overview" role="tabpanel" aria-labelledby="staffing-overview-tab">
        <div class="table-container">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Staffing Overview</h3>
            <div class="filter-controls">
              <input type="date" id="overview-start-date" class="form-control" value="<?php echo date('Y-m-01'); ?>">
              <span>to</span>
              <input type="date" id="overview-end-date" class="form-control" value="<?php echo date('Y-m-t'); ?>">
              <button class="btn btn-primary" onclick="loadStaffingOverview()">
                <i class="fa-solid fa-filter"></i> Load Overview
              </button>
            </div>
          </div>
          
          <div id="staffing-overview-content">
            <!-- Staffing overview will be loaded here -->
            <p class="text-center text-muted">Select a date range and click "Load Overview" to see staffing status.</p>
          </div>
        </div>
      </div>

      <!-- Leaves Tab -->
      <div class="tab-pane fade" id="leaves" role="tabpanel" aria-labelledby="leaves-tab">
        <div class="table-container">
          <div class="filter-controls">
            <select id="leave-dept-filter" class="form-select" style="width: auto;">
              <option value="">All Departments</option>
              <?php foreach ($allDept as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="leave-type-filter" class="form-select" style="width: auto;">
              <option value="">All Leave Types</option>
              <?php foreach ($allTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="leave-search" class="form-control" placeholder="Search employee..." style="width: auto;">
          </div>

          <table id="leaves-table">
            <thead>
              <tr>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Leave Type</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($displayLeaves as $leave): ?>
                <tr>
                  <td><?php echo htmlspecialchars($leave['empID']); ?></td>
                  <td><?php echo htmlspecialchars($leave['fullname']); ?></td>
                  <td><?php echo htmlspecialchars($leave['department']); ?></td>
                  <td><?php echo htmlspecialchars($leave['position']); ?></td>
                  <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                  <td><?php echo htmlspecialchars($leave['from_date']); ?></td>
                  <td><?php echo htmlspecialchars($leave['to_date']); ?></td>
                  <td><?php echo htmlspecialchars($leave['duration']); ?> days</td>
                  <td><span class="status-badge status-on-leave">On Leave</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <!-- Modals -->
  <!-- Assign Single Shift Modal -->
  <div class="modal fade" id="assignShiftModal" tabindex="-1" aria-labelledby="assignShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="shifts">
          <div class="modal-header">
            <h5 class="modal-title" id="assignShiftModalLabel">Assign Single Shift</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="employee-info-card" id="singleShiftEmployeeInfo">
              <h6><i class="fa-solid fa-user"></i> Employee Information</h6>
              <p class="mb-1" id="singleEmpName">Loading...</p>
              <p class="mb-0" id="singleEmpDept">Loading...</p>
            </div>
            <input type="hidden" name="empID" id="assign_emp_id">
            <div class="mb-3">
              <label for="shift_id" class="form-label">Shift</label>
              <select class="form-select" id="shift_id" name="shift_id" required>
                <option value="">Select Shift</option>
                <?php foreach ($shift_templates as $shift): ?>
                  <option value="<?php echo $shift['shift_id']; ?>">
                    <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="schedule_date" class="form-label">Date</label>
              <input type="date" class="form-control" id="schedule_date" name="schedule_date" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="assign_shift" class="btn btn-primary">Assign Shift</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Assign Multiple Shifts Modal -->
  <div class="modal fade" id="multipleShiftModal" tabindex="-1" aria-labelledby="multipleShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="shifts">
          <div class="modal-header">
            <h5 class="modal-title" id="multipleShiftModalLabel">Assign Multiple Shifts</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="employee-info-card" id="multipleShiftEmployeeInfo">
              <h6><i class="fa-solid fa-user"></i> Employee Information</h6>
              <p class="mb-1" id="multipleEmpName">Loading...</p>
              <p class="mb-0" id="multipleEmpDept">Loading...</p>
            </div>
            <input type="hidden" name="empID" id="multiple_emp_id">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="multiple_shift_id" class="form-label">Shift</label>
                  <select class="form-select" id="multiple_shift_id" name="shift_id" required>
                    <option value="">Select Shift</option>
                    <?php foreach ($shift_templates as $shift): ?>
                      <option value="<?php echo $shift['shift_id']; ?>">
                        <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Days of Week</label>
                  <div class="days-checkbox-container">
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Monday" id="monday">
                      <label class="form-check-label" for="monday">Monday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Tuesday" id="tuesday">
                      <label class="form-check-label" for="tuesday">Tuesday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Wednesday" id="wednesday">
                      <label class="form-check-label" for="wednesday">Wednesday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Thursday" id="thursday">
                      <label class="form-check-label" for="thursday">Thursday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Friday" id="friday">
                      <label class="form-check-label" for="friday">Friday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Saturday" id="saturday">
                      <label class="form-check-label" for="saturday">Saturday</label>
                    </div>
                    <div class="form-check day-checkbox">
                      <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Sunday" id="sunday">
                      <label class="form-check-label" for="sunday">Sunday</label>
                    </div>
                  </div>
                  <small class="text-muted">Leave all unchecked to assign for all days</small>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="start_date" class="form-label">Start Date</label>
                  <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="end_date" class="form-label">End Date</label>
                  <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="assign_multiple_shifts" class="btn btn-primary">Assign Shifts</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Default Shift Modal -->
  <div class="modal fade" id="editDefaultShiftModal" tabindex="-1" aria-labelledby="editDefaultShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="shifts">
          <div class="modal-header">
            <h5 class="modal-title" id="editDefaultShiftModalLabel">Edit Default Shift Settings</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="employee-info-card" id="defaultShiftEmployeeInfo">
              <h6><i class="fa-solid fa-user"></i> Employee Information</h6>
              <p class="mb-1" id="defaultEmpName">Loading...</p>
              <p class="mb-0" id="defaultEmpDept">Loading...</p>
            </div>
            <input type="hidden" name="empID" id="default_emp_id">
            <div class="mb-3">
              <label for="default_shift_id" class="form-label">Default Shift</label>
              <select class="form-select" id="default_shift_id" name="default_shift_id">
                <option value="">No Default Shift</option>
                <?php foreach ($shift_templates as $shift): ?>
                  <option value="<?php echo $shift['shift_id']; ?>">
                    <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="shift_type" class="form-label">Shift Type</label>
              <select class="form-select" id="shift_type" name="shift_type" required>
                <option value="Fixed">Fixed</option>
                <option value="Rotational">Rotational</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="work_hours_per_week" class="form-label">Work Hours Per Week</label>
              <input type="number" class="form-control" id="work_hours_per_week" name="work_hours_per_week" min="1" max="168" value="40" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="update_default_shift" class="btn btn-primary">Update Settings</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- NEW: Assign Pattern Modal -->
  <div class="modal fade" id="assignPatternModal" tabindex="-1" aria-labelledby="assignPatternModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="patterns">
          <div class="modal-header">
            <h5 class="modal-title" id="assignPatternModalLabel">Assign Shift Pattern</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="employee-info-card" id="patternEmployeeInfo">
              <h6><i class="fa-solid fa-user"></i> Employee Information</h6>
              <p class="mb-1" id="patternEmpName">Loading...</p>
              <p class="mb-0" id="patternEmpDept">Loading...</p>
            </div>
            <input type="hidden" name="empID" id="pattern_emp_id">
            <div class="mb-3">
              <label for="pattern_id" class="form-label">Shift Pattern</label>
              <select class="form-select" id="pattern_id" name="pattern_id" required>
                <option value="">Select Pattern</option>
                <?php foreach ($shift_patterns as $pattern): ?>
                  <option value="<?php echo $pattern['pattern_id']; ?>" data-description="<?php echo htmlspecialchars($pattern['description']); ?>">
                    <?php echo htmlspecialchars($pattern['pattern_name']); ?> (<?php echo $pattern['cycle_days']; ?> days)
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text" id="pattern-description"></div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="pattern_start_date" class="form-label">Start Date</label>
                  <input type="date" class="form-control" id="pattern_start_date" name="pattern_start_date" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="pattern_end_date" class="form-label">End Date</label>
                  <input type="date" class="form-control" id="pattern_end_date" name="pattern_end_date" required>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="assign_pattern" class="btn btn-primary">Assign Pattern</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- NEW: Create Pattern Modal -->
  <div class="modal fade" id="createPatternModal" tabindex="-1" aria-labelledby="createPatternModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="patterns">
          <div class="modal-header">
            <h5 class="modal-title" id="createPatternModalLabel">Create Shift Pattern</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="pattern_name" class="form-label">Pattern Name</label>
                  <input type="text" class="form-control" id="pattern_name" name="pattern_name" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="cycle_days" class="form-label">Cycle Days</label>
                  <input type="number" class="form-control" id="cycle_days" name="cycle_days" min="1" max="30" value="7" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="pattern_description" class="form-label">Description</label>
              <textarea class="form-control" id="pattern_description" name="pattern_description" rows="2"></textarea>
            </div>
            
            <div class="pattern-builder">
              <h6>Pattern Days</h6>
              <div id="pattern-days-container">
                <!-- Pattern days will be generated by JavaScript -->
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="create_pattern" class="btn btn-primary">Create Pattern</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- NEW: Edit Pattern Modal -->
  <div class="modal fade" id="editPatternModal" tabindex="-1" aria-labelledby="editPatternModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="patterns">
          <div class="modal-header">
            <h5 class="modal-title" id="editPatternModalLabel">Edit Shift Pattern</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="pattern_id" id="edit_pattern_id">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="edit_pattern_name" class="form-label">Pattern Name</label>
                  <input type="text" class="form-control" id="edit_pattern_name" name="pattern_name" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="edit_cycle_days" class="form-label">Cycle Days</label>
                  <input type="number" class="form-control" id="edit_cycle_days" name="cycle_days" min="1" max="30" value="7" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="edit_pattern_description" class="form-label">Description</label>
              <textarea class="form-control" id="edit_pattern_description" name="pattern_description" rows="2"></textarea>
            </div>
            
            <div class="pattern-builder">
              <h6>Pattern Days</h6>
              <div id="edit-pattern-days-container">
                <!-- Pattern days will be generated by JavaScript -->
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="update_pattern" class="btn btn-primary">Update Pattern</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- NEW: Delete Pattern Modal -->
  <div class="modal fade" id="deletePatternModal" tabindex="-1" aria-labelledby="deletePatternModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="patterns">
          <div class="modal-header">
            <h5 class="modal-title" id="deletePatternModalLabel">Delete Shift Pattern</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="pattern_id" id="delete_pattern_id">
            <p>Are you sure you want to delete this shift pattern? This action cannot be undone.</p>
            <div class="alert alert-warning">
              <i class="fa-solid fa-exclamation-triangle"></i>
              <strong>Warning:</strong> This pattern may be assigned to employees. Deleting it will remove the pattern but not affect existing schedules.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_pattern" class="btn btn-danger">Delete Pattern</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Staffing Modal -->
  <div class="modal fade" id="addStaffingModal" tabindex="-1" aria-labelledby="addStaffingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="staffing">
          <div class="modal-header">
            <h5 class="modal-title" id="addStaffingModalLabel">Add Staffing Requirement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="department" class="form-label">Department</label>
              <select class="form-select" id="department" name="department" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="staffing_shift_id" class="form-label">Shift</label>
              <select class="form-select" id="staffing_shift_id" name="shift_id" required>
                <option value="">Select Shift</option>
                <?php foreach ($shift_templates as $shift): ?>
                  <option value="<?php echo $shift['shift_id']; ?>">
                    <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="day_of_week" class="form-label">Day of Week</label>
              <select class="form-select" id="day_of_week" name="day_of_week" required>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="required_count" class="form-label">Required Count</label>
              <input type="number" class="form-control" id="required_count" name="required_count" min="1" required>
            </div>
            <div class="mb-3">
              <label for="employment_status" class="form-label">Employment Status</label>
              <select class="form-select" id="employment_status" name="employment_status">
                <option value="Any">Any</option>
                <option value="Regular">Regular</option>
                <option value="Full-Time">Full-Time</option>
                <option value="Part-Time">Part-Time</option>
                <option value="Contractual">Contractual</option>
                <option value="Intern">Intern</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="add_staffing" class="btn btn-primary">Add Requirement</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Staffing Modal -->
  <div class="modal fade" id="editStaffingModal" tabindex="-1" aria-labelledby="editStaffingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="staffing">
          <div class="modal-header">
            <h5 class="modal-title" id="editStaffingModalLabel">Edit Staffing Requirement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="staffing_id" id="edit_staffing_id">
            <div class="mb-3">
              <label for="edit_department" class="form-label">Department</label>
              <select class="form-select" id="edit_department" name="department" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_shift_id" class="form-label">Shift</label>
              <select class="form-select" id="edit_shift_id" name="shift_id" required>
                <option value="">Select Shift</option>
                <?php foreach ($shift_templates as $shift): ?>
                  <option value="<?php echo $shift['shift_id']; ?>">
                    <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_day_of_week" class="form-label">Day of Week</label>
              <select class="form-select" id="edit_day_of_week" name="day_of_week" required>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_required_count" class="form-label">Required Count</label>
              <input type="number" class="form-control" id="edit_required_count" name="required_count" min="1" required>
            </div>
            <div class="mb-3">
              <label for="edit_employment_status" class="form-label">Employment Status</label>
              <select class="form-select" id="edit_employment_status" name="employment_status">
                <option value="Any">Any</option>
                <option value="Regular">Regular</option>
                <option value="Full-Time">Full-Time</option>
                <option value="Part-Time">Part-Time</option>
                <option value="Contractual">Contractual</option>
                <option value="Intern">Intern</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="update_staffing" class="btn btn-primary">Update Requirement</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Staffing Modal -->
  <div class="modal fade" id="deleteStaffingModal" tabindex="-1" aria-labelledby="deleteStaffingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="active_tab" value="staffing">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteStaffingModalLabel">Delete Staffing Requirement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="staffing_id" id="delete_staffing_id">
            <p>Are you sure you want to delete this staffing requirement? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_staffing" class="btn btn-danger">Delete Requirement</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Calendar functionality
    let currentMonth = <?php echo $month; ?>;
    let currentYear = <?php echo $year; ?>;
    
    function renderCalendar(month, year) {
      const monthYearElement = document.getElementById('current-month-year');
      const calendarDaysElement = document.getElementById('calendar-days');
      
      // Clear previous calendar
      calendarDaysElement.innerHTML = '';
      
      // Set month and year in header
      const date = new Date(year, month - 1, 1);
      monthYearElement.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });
      
      // Get first day of month and number of days
      const firstDay = new Date(year, month - 1, 1);
      const daysInMonth = new Date(year, month, 0).getDate();
      const startingDay = firstDay.getDay(); // 0 = Sunday
      
      // Get previous month's days to fill the first week
      const prevMonth = month === 1 ? 12 : month - 1;
      const prevYear = month === 1 ? year - 1 : year;
      const daysInPrevMonth = new Date(prevYear, prevMonth, 0).getDate();
      
      // Create calendar days
      let dayCount = 1;
      let nextMonthDayCount = 1;
      
      for (let i = 0; i < 6; i++) { // 6 rows to ensure we have enough space
        for (let j = 0; j < 7; j++) { // 7 days per week
          const dayElement = document.createElement('div');
          dayElement.className = 'calendar-day';
          
          if (i === 0 && j < startingDay) {
            // Previous month's days
            const dayNumber = daysInPrevMonth - startingDay + j + 1;
            dayElement.textContent = dayNumber;
            dayElement.classList.add('other-month');
          } else if (dayCount > daysInMonth) {
            // Next month's days
            dayElement.textContent = nextMonthDayCount;
            dayElement.classList.add('other-month');
            nextMonthDayCount++;
          } else {
            // Current month's days
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.textContent = dayCount;
            dayElement.appendChild(dayHeader);
            
            // Add events for this day
            const dateString = `${year}-${month.toString().padStart(2, '0')}-${dayCount.toString().padStart(2, '0')}`;
            addEventsToDay(dayElement, dateString);
            
            dayCount++;
          }
          
          calendarDaysElement.appendChild(dayElement);
        }
        
        // Stop creating rows if we've displayed all days
        if (dayCount > daysInMonth && nextMonthDayCount > 7) {
          break;
        }
      }
    }
    
    function addEventsToDay(dayElement, dateString) {
      // Fetch real events from server
      fetch(`?ajax=get_calendar_events&month=${currentMonth}&year=${currentYear}`)
        .then(response => response.json())
        .then(events => {
          const dateEvents = [];
          
          // Add shift events
          if (events.shifts && events.shifts[dateString]) {
            events.shifts[dateString].forEach(shift => {
              dateEvents.push({
                type: 'shift',
                text: `${shift.shift_name} - ${shift.count} staff`
              });
            });
          }
          
          // Add leave events
          if (events.leaves && events.leaves[dateString]) {
            events.leaves[dateString].forEach(leave => {
              dateEvents.push({
                type: 'leave',
                text: `${leave.employee} - ${leave.leave_type}`
              });
            });
          }
          
          // Add staffing gap events
          if (events.staffing_gaps && events.staffing_gaps[dateString]) {
            events.staffing_gaps[dateString].forEach(gap => {
              dateEvents.push({
                type: 'staffing',
                text: `Understaffed: ${gap.department} - ${gap.shift} (${gap.gap})`
              });
            });
          }
          
          // Add expected staff information
          if (events.expected_staff && events.expected_staff[dateString]) {
            events.expected_staff[dateString].forEach(staff => {
              if (staff.staffing_gap > 0) {
                dateEvents.push({
                  type: 'expected-staff',
                  text: `${staff.department} - ${staff.shift_name}: ${staff.expected_employees}/${staff.staffing_requirement} (-${staff.staffing_gap})`
                });
              } else {
                dateEvents.push({
                  type: 'expected-staff',
                  text: `${staff.department} - ${staff.shift_name}: ${staff.expected_employees}${staff.staffing_requirement ? '/' + staff.staffing_requirement : ''}`
                });
              }
            });
          }
          
          // Add events to day
          dateEvents.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = `calendar-event event-${event.type}`;
            eventElement.textContent = event.text;
            dayElement.appendChild(eventElement);
          });
        })
        .catch(error => {
          console.error('Error fetching calendar events:', error);
        });
    }
    
    function changeMonth(direction) {
      currentMonth += direction;
      
      if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
      } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
      }
      
      // Update URL parameters without page reload
      const url = new URL(window.location);
      url.searchParams.set('month', currentMonth);
      url.searchParams.set('year', currentYear);
      window.history.pushState({}, '', url);
      
      renderCalendar(currentMonth, currentYear);
    }
    
    // Enhanced function to get expected employees for a specific date
    function getExpectedEmployeesForDate(date) {
        fetch(`?ajax=get_expected_employees&date=${date}`)
            .then(response => response.json())
            .then(data => {
                displayExpectedEmployees(data, date);
            })
            .catch(error => {
                console.error('Error loading expected employees:', error);
            });
    }

    // Function to display expected employees in a modal
    function displayExpectedEmployees(data, date) {
        // Create and show modal with expected employees
        const modalHtml = `
            <div class="modal fade" id="expectedEmployeesModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Expected Employees - ${date}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${renderExpectedEmployeesTable(data, date)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('expectedEmployeesModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('expectedEmployeesModal'));
        modal.show();
    }

    // Function to render expected employees table
    function renderExpectedEmployeesTable(data, date) {
        if (data.length === 0) {
            return '<p class="text-center text-muted">No expected employees data found for this date.</p>';
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Department</th>
                            <th>Shift</th>
                            <th>Expected</th>
                            <th>Required</th>
                            <th>Gap</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.forEach(row => {
            const gap = row.staffing_gap || 0;
            let statusClass = 'staffing-status-good';
            let statusText = 'Fully Staffed';
            
            if (gap > 0) {
                statusClass = 'staffing-status-danger';
                statusText = `Understaffed by ${gap}`;
            } else if (gap < 0) {
                statusClass = 'staffing-status-warning';
                statusText = `Overstaffed by ${Math.abs(gap)}`;
            }
            
            html += `
                <tr>
                    <td><strong>${row.department}</strong></td>
                    <td>${row.shift_name}</td>
                    <td>${row.expected_employees}</td>
                    <td>${row.staffing_requirement || 'N/A'}</td>
                    <td class="${gap > 0 ? 'text-danger' : gap < 0 ? 'text-warning' : 'text-success'}">
                        ${gap > 0 ? `-${gap}` : gap < 0 ? `+${Math.abs(gap)}` : '0'}
                    </td>
                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        return html;
    }

    // Enhanced calendar day click handler
    function setupCalendarDayClicks() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.calendar-day')) {
                const dayElement = e.target.closest('.calendar-day');
                const dayHeader = dayElement.querySelector('.calendar-day-header');
                
                if (dayHeader) {
                    const day = parseInt(dayHeader.textContent);
                    const dateString = `${currentYear}-${currentMonth.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    
                    // Show expected employees for this date
                    getExpectedEmployeesForDate(dateString);
                }
            }
        });
    }

    // Enhanced function to update staffing status display
    function updateStaffingStatus() {
        const currentDate = new Date().toISOString().split('T')[0];
        
        fetch(`?ajax=get_staffing_status&date=${currentDate}`)
            .then(response => response.json())
            .then(data => {
                updateStaffingStatusDisplay(data);
            })
            .catch(error => {
                console.error('Error updating staffing status:', error);
            });
    }

    function updateStaffingStatusDisplay(data) {
        // Update staffing status in the staffing requirements table
        const tableBody = document.getElementById('staffing-table-body');
        
        if (tableBody) {
            let html = '';
            
            data.forEach(item => {
                const gap = item.staffing_gap;
                const statusClass = gap > 0 ? 'text-danger' : 'text-success';
                const statusIcon = gap > 0 ? '❌ Understaffed' : '✅ Fully Staffed';
                
                html += `
                    <tr>
                        <td>${item.department}</td>
                        <td>${item.shift_name}</td>
                        <td>${item.day_of_week}</td>
                        <td>${item.required_count}</td>
                        <td>${item.expected_count}</td>
                        <td class="${statusClass}">${gap > 0 ? `-${gap}` : '0'}</td>
                        <td class="${statusClass}">${statusIcon} ${item.staffing_status}</td>
                        <td>
                            <button class='btn btn-sm btn-warning edit-staffing-btn' data-id='${item.staffing_id}' data-bs-toggle='modal' data-bs-target='#editStaffingModal'>
                                <i class='fa-solid fa-pen'></i>
                            </button>
                            <button class='btn btn-sm btn-danger delete-staffing-btn' data-id='${item.staffing_id}' data-bs-toggle='modal' data-bs-target='#deleteStaffingModal'>
                                <i class='fa-solid fa-trash'></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
    }
    
    // Employee data management
    function loadEmployeeData(empID, modalType) {
      fetch(`?ajax=get_employee_details&empID=${empID}`)
        .then(response => response.json())
        .then(employee => {
          if (employee.error) {
            alert('Error loading employee data: ' . employee.error);
            return;
          }
          
          if (modalType === 'single') {
            document.getElementById('assign_emp_id').value = empID;
            document.getElementById('singleEmpName').textContent = employee.fullname;
            document.getElementById('singleEmpDept').textContent = employee.department;
          } else if (modalType === 'multiple') {
            document.getElementById('multiple_emp_id').value = empID;
            document.getElementById('multipleEmpName').textContent = employee.fullname;
            document.getElementById('multipleEmpDept').textContent = employee.department;
          } else if (modalType === 'default') {
            document.getElementById('default_emp_id').value = empID;
            document.getElementById('defaultEmpName').textContent = employee.fullname;
            document.getElementById('defaultEmpDept').textContent = employee.department;
            document.getElementById('default_shift_id').value = employee.default_shift_id || '';
            document.getElementById('shift_type').value = employee.shift_type || 'Rotational';
            document.getElementById('work_hours_per_week').value = employee.work_hours_per_week || 40;
          } else if (modalType === 'pattern') {
            document.getElementById('pattern_emp_id').value = empID;
            document.getElementById('patternEmpName').textContent = employee.fullname;
            document.getElementById('patternEmpDept').textContent = employee.department;
            // Set default dates for pattern
            const today = new Date();
            document.getElementById('pattern_start_date').valueAsDate = today;
            const nextMonth = new Date(today);
            nextMonth.setMonth(today.getMonth() + 1);
            document.getElementById('pattern_end_date').valueAsDate = nextMonth;
          }
        })
        .catch(error => {
          console.error('Error loading employee data:', error);
          alert('Error loading employee data');
        });
    }

    // Function to change entries per page
    function changePerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1'); // Reset to first page
        window.location.href = url.toString() + '#shifts';
    }

    // Function to change pattern entries per page
    function changePatternPerPage(value) {
        // This would typically reload patterns with new pagination
        loadShiftPatterns();
    }

    // Helper function to sort employee IDs
    function sortEmployeeIDs(employees) {
        return employees.sort((a, b) => {
            // Extract numbers from empID (e.g., emp-001 -> 1)
            const getEmpNumber = (empID) => {
                const match = empID.match(/emp-(\d+)/i);
                return match ? parseInt(match[1], 10) : Infinity;
            };
            
            const numA = getEmpNumber(a.empID);
            const numB = getEmpNumber(b.empID);
            
            return numA - numB;
        });
    }

    // NEW: Shift Pattern Functions
    function loadShiftPatterns() {
        fetch('?ajax=get_shift_patterns')
            .then(response => response.json())
            .then(data => {
                renderShiftPatterns(data);
            })
            .catch(error => {
                console.error('Error loading shift patterns:', error);
            });
    }

    function loadEmployeePatterns() {
        fetch('?ajax=get_employee_patterns')
            .then(response => response.json())
            .then(data => {
                renderEmployeePatterns(data);
            })
            .catch(error => {
                console.error('Error loading employee patterns:', error);
            });
    }

    function renderShiftPatterns(patterns) {
        const container = document.getElementById('shift-patterns-body');
        let html = '';
        
        patterns.forEach(pattern => {
            html += `
                <tr>
                    <td>${pattern.pattern_id}</td>
                    <td><strong>${pattern.pattern_name}</strong></td>
                    <td>${pattern.description}</td>
                    <td>${pattern.cycle_days} days</td>
                    <td>
                        <button class="btn btn-sm btn-info view-pattern-details" data-id="${pattern.pattern_id}">
                            <i class="fa-solid fa-eye"></i> View Details
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-pattern-btn" data-id="${pattern.pattern_id}">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-pattern-btn" data-id="${pattern.pattern_id}">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        container.innerHTML = html;
        
        // Attach event listeners to the new buttons
        attachPatternActionListeners();
    }

    function renderEmployeePatterns(employeePatterns) {
        const container = document.getElementById('employee-patterns-body');
        let html = '';
        
        employeePatterns.forEach(empPattern => {
            const status = new Date(empPattern.end_date) < new Date() ? 
                '<span class="badge bg-secondary">Expired</span>' : 
                '<span class="badge bg-success">Active</span>';
            
            html += `
                <tr>
                    <td>${empPattern.empID}</td>
                    <td>${empPattern.fullname}</td>
                    <td>${empPattern.department}</td>
                    <td>${empPattern.pattern_name}</td>
                    <td>${empPattern.start_date}</td>
                    <td>${empPattern.end_date}</td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-sm btn-primary predict-shifts-btn" data-emp-id="${empPattern.empID}">
                            <i class="fa-solid fa-crystal-ball"></i> Predict
                        </button>
                        <!-- Edit button removed as requested -->
                    </td>
                </tr>
            `;
        });
        
        container.innerHTML = html;
        
        // Attach event listeners
        attachEmployeePatternActionListeners();
    }

    function attachPatternActionListeners() {
        // Edit pattern buttons
        document.querySelectorAll('.edit-pattern-btn').forEach(button => {
            button.addEventListener('click', function() {
                const patternId = this.getAttribute('data-id');
                loadPatternForEditing(patternId);
            });
        });
        
        // Delete pattern buttons
        document.querySelectorAll('.delete-pattern-btn').forEach(button => {
            button.addEventListener('click', function() {
                const patternId = this.getAttribute('data-id');
                document.getElementById('delete_pattern_id').value = patternId;
                const deleteModal = new bootstrap.Modal(document.getElementById('deletePatternModal'));
                deleteModal.show();
            });
        });
        
        // View pattern details buttons
        document.querySelectorAll('.view-pattern-details').forEach(button => {
            button.addEventListener('click', function() {
                const patternId = this.getAttribute('data-id');
                viewPatternDetails(patternId);
            });
        });
    }

    function attachEmployeePatternActionListeners() {
        // Predict shifts buttons only (edit button removed)
        document.querySelectorAll('.predict-shifts-btn').forEach(button => {
            button.addEventListener('click', function() {
                const empId = this.getAttribute('data-emp-id');
                predictEmployeeShifts(empId);
            });
        });
    }

    // Predict shifts for employee
    function predictEmployeeShifts(empID) {
        fetch(`?ajax=predict_shifts&empID=${empID}&weeks=4`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPredictedShiftsModal(data);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error predicting shifts:', error);
            });
    }

    function showPredictedShiftsModal(data) {
        const modalHtml = `
            <div class="modal fade" id="predictedShiftsModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Predicted Shifts - ${data.pattern.pattern_name}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Pattern:</strong> ${data.pattern.pattern_name}<br>
                                <strong>Cycle:</strong> ${data.pattern.cycle_days} days<br>
                                <strong>Period:</strong> ${data.pattern.start_date} to ${data.pattern.end_date}
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Cycle Day</th>
                                            <th>Shift</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.predicted_shifts.map(shift => `
                                            <tr>
                                                <td>${shift.date}</td>
                                                <td>${shift.day_of_week}</td>
                                                <td>${shift.day_of_cycle}</td>
                                                <td><span class="shift-badge shift-${shift.shift_name.toLowerCase()}">${shift.shift_name}</span></td>
                                                <td>${shift.time_in} - ${shift.time_out}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('predictedShiftsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('predictedShiftsModal'));
        modal.show();
    }

    // Pattern builder functionality
    function generatePatternDays(cycleDays) {
        const container = document.getElementById('pattern-days-container');
        let html = '';
        
        for (let i = 1; i <= cycleDays; i++) {
            html += `
                <div class="pattern-day">
                    <div class="pattern-day-label">Day ${i}:</div>
                    <select class="form-select pattern-day-select" name="day_${i}_shift">
                        <option value="">No Shift</option>
                        <?php foreach ($shift_templates as $shift): ?>
                            <option value="<?php echo $shift['shift_id']; ?>">
                                <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            `;
        }
        
        container.innerHTML = html;
    }

    function loadPatternForEditing(patternId) {
        fetch(`?ajax=get_pattern_info&pattern_id=${patternId}`)
            .then(response => response.json())
            .then(pattern => {
                if (pattern.error) {
                    alert('Error loading pattern: ' + pattern.error);
                    return;
                }
                
                // Set pattern info in edit modal
                document.getElementById('edit_pattern_id').value = pattern.pattern_id;
                document.getElementById('edit_pattern_name').value = pattern.pattern_name;
                document.getElementById('edit_pattern_description').value = pattern.description;
                document.getElementById('edit_cycle_days').value = pattern.cycle_days;
                
                // Generate pattern days
                generateEditPatternDays(pattern.cycle_days, patternId);
                
                // Show edit modal
                const editModal = new bootstrap.Modal(document.getElementById('editPatternModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error loading pattern:', error);
            });
    }

    function generateEditPatternDays(cycleDays, patternId) {
        const container = document.getElementById('edit-pattern-days-container');
        let html = '';
        
        // First generate empty selects
        for (let i = 1; i <= cycleDays; i++) {
            html += `
                <div class="pattern-day">
                    <div class="pattern-day-label">Day ${i}:</div>
                    <select class="form-select pattern-day-select" name="day_${i}_shift" id="edit_day_${i}_shift">
                        <option value="">No Shift</option>
                        <?php foreach ($shift_templates as $shift): ?>
                            <option value="<?php echo $shift['shift_id']; ?>">
                                <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            `;
        }
        
        container.innerHTML = html;
        
        // Now load existing pattern details
        fetch(`?ajax=get_pattern_details&pattern_id=${patternId}`)
            .then(response => response.json())
            .then(patternDetails => {
                patternDetails.forEach(detail => {
                    const select = document.getElementById(`edit_day_${detail.day_number}_shift`);
                    if (select) {
                        select.value = detail.shift_id;
                    }
                });
            })
            .catch(error => {
                console.error('Error loading pattern details:', error);
            });
    }

    function loadStaffingOverview() {
      const startDate = document.getElementById('overview-start-date').value;
      const endDate = document.getElementById('overview-end-date').value;
      
      fetch(`?ajax=get_staffing_overview&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
          renderStaffingOverview(data);
        })
        .catch(error => {
          console.error('Error loading staffing overview:', error);
        });
    }

    function renderStaffingOverview(data) {
      const container = document.getElementById('staffing-overview-content');
      
      if (data.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No staffing data found for the selected date range.</p>';
        return;
      }
      
      let html = `
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Date</th>
              <th>Department</th>
              <th>Shift</th>
              <th>Scheduled</th>
              <th>Required</th>
              <th>Gap</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
      `;
      
      data.forEach(row => {
        const statusClass = row.staffing_gap > 0 ? 'text-danger' : 'text-success';
        const statusIcon = row.staffing_gap > 0 ? '❌' : '✅';
        
        html += `
          <tr>
            <td>${row.date}</td>
            <td>${row.department}</td>
            <td>${row.shift_name}</td>
            <td>${row.expected_employees}</td>
            <td>${row.staffing_requirement || 'N/A'}</td>
            <td class="${statusClass}">${row.staffing_gap > 0 ? `-${row.staffing_gap}` : '0'}</td>
            <td class="${statusClass}">${statusIcon} ${row.staffing_status}</td>
          </tr>
        `;
      });
      
      html += '</tbody></table>';
      container.innerHTML = html;
    }
    
    function loadStaffingRequirements() {
        const selectedDate = document.getElementById('staffing-date-filter').value;
        const dayOfWeek = new Date(selectedDate).toLocaleString('en-us', { weekday: 'long' });
        
        fetch(`?ajax=get_staffing_status&date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                renderStaffingRequirements(data, selectedDate, dayOfWeek);
            })
            .catch(error => {
                console.error('Error loading staffing requirements:', error);
            });
    }

    function renderStaffingRequirements(data, selectedDate, dayOfWeek) {
        const container = document.getElementById('staffing-table-body');
        
        if (data.length === 0) {
            container.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No staffing requirements found for ' + selectedDate + ' (' + dayOfWeek + ')</td></tr>';
            return;
        }
        
        let html = '';
        
        data.forEach(staff => {
            const staffing_gap = staff.required_count - staff.expected_count;
            const status_class = staffing_gap > 0 ? 'text-danger' : 'text-success';
            const status_icon = staffing_gap > 0 ? '❌ Understaffed' : '✅ Fully Staffed';
            
            html += `
                <tr>
                    <td>${staff.department}</td>
                    <td>${staff.shift_name}</td>
                    <td>${staff.day_of_week}</td>
                    <td>${staff.required_count}</td>
                    <td>${staff.expected_count}</td>
                    <td class="${status_class}">${staffing_gap > 0 ? '-' + staffing_gap : '0'}</td>
                    <td class="${status_class}">${status_icon}</td>
                    <td>
                        <button class='btn btn-sm btn-warning edit-staffing-btn' data-id='${staff.staffing_id}' data-bs-toggle='modal' data-bs-target='#editStaffingModal'>
                            <i class='fa-solid fa-pen'></i>
                        </button>
                        <button class='btn btn-sm btn-danger delete-staffing-btn' data-id='${staff.staffing_id}' data-bs-toggle='modal' data-bs-target='#deleteStaffingModal'>
                            <i class='fa-solid fa-trash'></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        container.innerHTML = html;
        
        // Re-attach event listeners to the new buttons
        document.querySelectorAll('.edit-staffing-btn').forEach(button => {
            button.addEventListener('click', function() {
                const staffingId = this.getAttribute('data-id');
                document.getElementById('edit_staffing_id').value = staffingId;
            });
        });
        
        document.querySelectorAll('.delete-staffing-btn').forEach(button => {
            button.addEventListener('click', function() {
                const staffingId = this.getAttribute('data-id');
                document.getElementById('delete_staffing_id').value = staffingId;
            });
        });
    }

    // Add these JavaScript functions for All Schedules tab

    let currentSchedulePage = 1;
    const schedulesPerPage = 20;

    function loadAllSchedules(page = 1) {
        const date = document.getElementById('schedule-date-filter').value;
        const department = document.getElementById('schedule-dept-filter').value;
        const shift = document.getElementById('schedule-shift-filter').value;
        
        currentSchedulePage = page;
        
        // Show loading state
        document.getElementById('all-schedules-body').innerHTML = `
            <tr>
                <td colspan="9" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading schedules...</p>
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams({
            ajax: 'get_all_schedules',
            date: date,
            department: department,
            shift: shift,
            page: page,
            limit: schedulesPerPage
        });
        
        console.log('Fetching schedules with params:', params.toString());
        
        fetch(`?${params}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                renderAllSchedules(data);
            })
            .catch(error => {
                console.error('Error loading schedules:', error);
                document.getElementById('all-schedules-body').innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-danger">
                            Error loading schedules: ${error.message}
                        </td>
                    </tr>
                `;
            });
    }

    function renderAllSchedules(data) {
        const container = document.getElementById('all-schedules-body');
        const paginationInfo = document.getElementById('schedule-pagination-info');
        const paginationContainer = document.getElementById('schedule-pagination');
        
        if (!data.schedules || data.schedules.length === 0) {
            container.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted">
                        No schedules found for the selected filters.
                    </td>
                </tr>
            `;
            paginationInfo.innerHTML = '';
            paginationContainer.innerHTML = '';
            return;
        }
        
        // Render schedules
        let html = '';
        data.schedules.forEach(schedule => {
            const statusClass = schedule.status === 'Scheduled' ? 'status-scheduled' : 
                               schedule.status === 'Completed' ? 'status-completed' :
                               schedule.status === 'Absent' ? 'status-absent' : 'status-on-leave';
            
            html += `
                <tr>
                    <td>${schedule.empID}</td>
                    <td><strong>${schedule.fullname}</strong></td>
                    <td>${schedule.department}</td>
                    <td>${schedule.position}</td>
                    <td>${schedule.schedule_date}</td>
                    <td><span class="shift-badge shift-${schedule.shift_name.toLowerCase()}">${schedule.shift_name}</span></td>
                    <td>${schedule.time_in} - ${schedule.time_out}</td>
                    <td><span class="status-badge ${statusClass}">${schedule.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-schedule-btn" 
                                data-schedule-id="${schedule.schedule_id}"
                                data-emp-id="${schedule.empID}"
                                data-emp-name="${schedule.fullname}"
                                data-date="${schedule.schedule_date}"
                                data-shift-id="${schedule.shift_id}">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-schedule-btn" 
                                data-schedule-id="${schedule.schedule_id}"
                                data-emp-name="${schedule.fullname}"
                                data-date="${schedule.schedule_date}">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        
        container.innerHTML = html;
        
        // Render pagination info
        const start = ((currentSchedulePage - 1) * schedulesPerPage) + 1;
        const end = Math.min(currentSchedulePage * schedulesPerPage, data.total);
        paginationInfo.innerHTML = `Showing ${start}-${end} of ${data.total} schedules`;
        
        // Render pagination
        const totalPages = Math.ceil(data.total / schedulesPerPage);
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${currentSchedulePage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadAllSchedules(${currentSchedulePage - 1})" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentSchedulePage - 2 && i <= currentSchedulePage + 2)) {
                paginationHtml += `
                    <li class="page-item ${i === currentSchedulePage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadAllSchedules(${i})">${i}</a>
                    </li>
                `;
            } else if (i === currentSchedulePage - 3 || i === currentSchedulePage + 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${currentSchedulePage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadAllSchedules(${currentSchedulePage + 1})" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHtml;
        
        // Attach event listeners to action buttons
        attachScheduleActionListeners();
    }

    function attachScheduleActionListeners() {
        // Edit schedule buttons
        document.querySelectorAll('.edit-schedule-btn').forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-schedule-id');
                const empId = this.getAttribute('data-emp-id');
                const empName = this.getAttribute('data-emp-name');
                const date = this.getAttribute('data-date');
                const shiftId = this.getAttribute('data-shift-id');
                
                showEditScheduleModal(scheduleId, empId, empName, date, shiftId);
            });
        });
        
        // Delete schedule buttons
        document.querySelectorAll('.delete-schedule-btn').forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-schedule-id');
                const empName = this.getAttribute('data-emp-name');
                const date = this.getAttribute('data-date');
                
                if (confirm(`Are you sure you want to delete the schedule for ${empName} on ${date}?`)) {
                    deleteSchedule(scheduleId);
                }
            });
        });
    }

    function showEditScheduleModal(scheduleId, empId, empName, date, currentShiftId) {
        const modalHtml = `
            <div class="modal fade" id="editScheduleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Schedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="employee-info-card">
                                <h6><i class="fa-solid fa-user"></i> Employee Information</h6>
                                <p class="mb-1"><strong>${empName}</strong></p>
                                <p class="mb-0">Employee ID: ${empId}</p>
                                <p class="mb-0">Date: ${date}</p>
                            </div>
                            <div class="mb-3">
                                <label for="edit_shift_id" class="form-label">Shift</label>
                                <select class="form-select" id="edit_shift_id">
                                    <option value="">Select Shift</option>
                                    <?php foreach ($shift_templates as $shift): ?>
                                        <option value="<?php echo $shift['shift_id']; ?>">
                                            <?php echo htmlspecialchars($shift['shift_name']); ?> (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_schedule_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_schedule_status">
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Absent">Absent</option>
                                    <option value="On Leave">On Leave</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="updateSchedule(${scheduleId})">Update Schedule</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('editScheduleModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Set current values
        const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
        document.getElementById('edit_shift_id').value = currentShiftId;
        
        // Show the modal
        modal.show();
    }

    function updateSchedule(scheduleId) {
        const shiftId = document.getElementById('edit_shift_id').value;
        const status = document.getElementById('edit_schedule_status').value;
        
        if (!shiftId) {
            alert('Please select a shift');
            return;
        }
        
        const formData = new FormData();
        formData.append('schedule_id', scheduleId);
        formData.append('shift_id', shiftId);
        formData.append('status', status);
        
        fetch('?ajax=update_schedule', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('editScheduleModal')).hide();
                
                // Show success message
                alert('Schedule updated successfully!');
                
                // Reload schedules
                loadAllSchedules(currentSchedulePage);
            } else {
                alert('Error updating schedule: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating schedule:', error);
            alert('Error updating schedule. Please try again.');
        });
    }

    function deleteSchedule(scheduleId) {
        const formData = new FormData();
        formData.append('schedule_id', scheduleId);
        
        fetch('?ajax=delete_schedule', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Schedule deleted successfully!');
                loadAllSchedules(currentSchedulePage);
            } else {
                alert('Error deleting schedule: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting schedule:', error);
            alert('Error deleting schedule. Please try again.');
        });
    }

    // Load schedules when the tab is shown
    document.getElementById('all-schedules-tab').addEventListener('shown.bs.tab', function() {
        loadAllSchedules();
    });

    // Initialize calendar and event listeners
    document.addEventListener('DOMContentLoaded', function() {
      renderCalendar(currentMonth, currentYear);
      setupCalendarDayClicks();
      updateStaffingStatus();
      loadShiftPatterns();
      loadEmployeePatterns();
      
      // Initialize pattern builder
      generatePatternDays(7);
      
      // Pattern cycle days change handlers
      document.getElementById('cycle_days').addEventListener('change', function() {
          generatePatternDays(this.value);
      });
      
      document.getElementById('edit_cycle_days').addEventListener('change', function() {
          const patternId = document.getElementById('edit_pattern_id').value;
          generateEditPatternDays(this.value, patternId);
      });
      
      // Pattern description display
      document.getElementById('pattern_id').addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          const description = selectedOption.getAttribute('data-description') || 'No description available.';
          document.getElementById('pattern-description').textContent = description;
      });
      
      // Filter functionality for shifts table
      document.getElementById('shift-dept-filter').addEventListener('change', filterShiftsTable);
      document.getElementById('shift-type-filter').addEventListener('change', filterShiftsTable);
      document.getElementById('shift-search').addEventListener('input', filterShiftsTable);
      
      // Filter functionality for patterns table
      document.getElementById('pattern-search').addEventListener('input', filterPatternsTable);
      
      // Filter functionality for leaves table
      document.getElementById('leave-dept-filter').addEventListener('change', filterLeavesTable);
      document.getElementById('leave-type-filter').addEventListener('change', filterLeavesTable);
      document.getElementById('leave-search').addEventListener('input', filterLeavesTable);
      
      // Assign shift buttons
      document.querySelectorAll('.assign-shift-btn').forEach(button => {
        button.addEventListener('click', function() {
          const empId = this.getAttribute('data-emp-id');
          loadEmployeeData(empId, 'single');
          // Set today's date as default
          document.getElementById('schedule_date').valueAsDate = new Date();
        });
      });
      
      // Multiple shift buttons
      document.querySelectorAll('.multiple-shift-btn').forEach(button => {
        button.addEventListener('click', function() {
          const empId = this.getAttribute('data-emp-id');
          loadEmployeeData(empId, 'multiple');
          // Set date range defaults
          const today = new Date();
          document.getElementById('start_date').valueAsDate = today;
          const nextWeek = new Date(today);
          nextWeek.setDate(today.getDate() + 7);
          document.getElementById('end_date').valueAsDate = nextWeek;
        });
      });
      
      // Edit default shift buttons
      document.querySelectorAll('.edit-default-shift-btn').forEach(button => {
        button.addEventListener('click', function() {
          const empId = this.getAttribute('data-emp-id');
          loadEmployeeData(empId, 'default');
        });
      });
      
      // Assign pattern buttons
      document.querySelectorAll('.assign-pattern-btn').forEach(button => {
        button.addEventListener('click', function() {
          const empId = this.getAttribute('data-emp-id');
          loadEmployeeData(empId, 'pattern');
        });
      });
      
      // Edit staffing buttons
      document.querySelectorAll('.edit-staffing-btn').forEach(button => {
        button.addEventListener('click', function() {
          const staffingId = this.getAttribute('data-id');
          // In a real implementation, you would fetch the staffing data here
          document.getElementById('edit_staffing_id').value = staffingId;
        });
      });
      
      // Delete staffing buttons
      document.querySelectorAll('.delete-staffing-btn').forEach(button => {
        button.addEventListener('click', function() {
          const staffingId = this.getAttribute('data-id');
          document.getElementById('delete_staffing_id').value = staffingId;
        });
      });

      // Load initial staffing overview
      loadStaffingOverview();
    });
    
    function filterShiftsTable() {
      const deptFilter = document.getElementById('shift-dept-filter').value.toLowerCase();
      const typeFilter = document.getElementById('shift-type-filter').value.toLowerCase();
      const searchFilter = document.getElementById('shift-search').value.toLowerCase();
      
      const rows = Array.from(document.querySelectorAll('#shifts-table tbody tr'));
      
      // Sort rows by employee ID
      rows.sort((a, b) => {
        const empIdA = a.cells[0].textContent;
        const empIdB = b.cells[0].textContent;
        
        const getEmpNumber = (empID) => {
          const match = empID.match(/emp-(\d+)/i);
          return match ? parseInt(match[1], 10) : Infinity;
        };
        
        return getEmpNumber(empIdA) - getEmpNumber(empIdB);
      });
      
      // Re-append sorted rows
      const tbody = document.querySelector('#shifts-table tbody');
      tbody.innerHTML = '';
      rows.forEach(row => tbody.appendChild(row));
      
      // Apply filters
      let visibleCount = 0;
      rows.forEach(row => {
        const dept = row.cells[2].textContent.toLowerCase();
        const type = row.cells[4].textContent.toLowerCase();
        const name = row.cells[1].textContent.toLowerCase();
        const empId = row.cells[0].textContent.toLowerCase();
        
        const showRow = 
          (deptFilter === '' || dept.includes(deptFilter)) &&
          (typeFilter === '' || type.includes(typeFilter)) &&
          (searchFilter === '' || name.includes(searchFilter) || empId.includes(searchFilter));
        
        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
      });
      
      // Update the "Showing X of Y entries" text
      const showingText = document.querySelector('.text-center.text-muted');
      if (showingText) {
        showingText.textContent = `Showing ${visibleCount} of ${visibleCount} filtered employees`;
      }
    }
    
    function filterPatternsTable() {
      const searchFilter = document.getElementById('pattern-search').value.toLowerCase();
      
      const rows = document.querySelectorAll('#shift-patterns-body tr');
      
      rows.forEach(row => {
        const patternName = row.cells[1].textContent.toLowerCase();
        const description = row.cells[2].textContent.toLowerCase();
        
        const showRow = 
          searchFilter === '' || 
          patternName.includes(searchFilter) || 
          description.includes(searchFilter);
        
        row.style.display = showRow ? '' : 'none';
      });
    }
    
    function filterLeavesTable() {
      const deptFilter = document.getElementById('leave-dept-filter').value.toLowerCase();
      const typeFilter = document.getElementById('leave-type-filter').value.toLowerCase();
      const searchFilter = document.getElementById('leave-search').value.toLowerCase();
      
      const rows = document.querySelectorAll('#leaves-table tbody tr');
      
      rows.forEach(row => {
        const dept = row.cells[2].textContent.toLowerCase();
        const type = row.cells[4].textContent.toLowerCase();
        const name = row.cells[1].textContent.toLowerCase();
        
        const showRow = 
          (deptFilter === '' || dept.includes(deptFilter)) &&
          (typeFilter === '' || type.includes(typeFilter)) &&
          (searchFilter === '' || name.includes(searchFilter));
        
        row.style.display = showRow ? '' : 'none';
      });
    }

    // View pattern details
    function viewPatternDetails(patternId) {
        fetch(`?ajax=get_pattern_details&pattern_id=${patternId}`)
            .then(response => response.json())
            .then(data => {
                showPatternDetailsModal(data, patternId);
            })
            .catch(error => {
                console.error('Error loading pattern details:', error);
            });
    }

    function showPatternDetailsModal(patternDetails, patternId) {
        let html = `
            <div class="modal fade" id="patternDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Pattern Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Day Number</th>
                                            <th>Shift</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
        `;
        
        patternDetails.forEach(detail => {
            html += `
                <tr>
                    <td>Day ${detail.day_number}</td>
                    <td><span class="shift-badge shift-${detail.shift_name.toLowerCase()}">${detail.shift_name}</span></td>
                    <td>${detail.time_in} - ${detail.time_out}</td>
                </tr>
            `;
        });
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('patternDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('patternDetailsModal'));
        modal.show();
    }
  </script>
</body>
</html>