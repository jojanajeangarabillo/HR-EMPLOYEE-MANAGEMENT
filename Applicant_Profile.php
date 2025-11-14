<?php
session_start();
include 'admin/db.connect.php';

// Ensure the session key we agreed on is present
if (!isset($_SESSION['applicant_employee_id']) || empty($_SESSION['applicant_employee_id'])) {
  // If your previous logic uses applicantID, you might want to fallback to that:
  if (isset($_SESSION['applicantID'])) {
    $_SESSION['applicant_employee_id'] = $_SESSION['applicantID'];
  } else {
    header("Location: Login.php");
    exit();
  }
}
$session_emp = $_SESSION['applicant_employee_id'];

// flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

/* ------------------------- Helper: ensure applicant row exists ------------------------- */
/*
  If there's no applicant row for the session_emp, try to seed one using the user table's fullname and email.
  Because the applicant table has many NOT NULL columns, we supply minimal defaults here.
*/
function ensure_applicant_exists($conn, $applicantID, &$flash_error, &$flash_success)
{
  $check = $conn->prepare("SELECT applicantID FROM applicant WHERE applicantID = ?");
  if (!$check) {
    $flash_error = "Server error (prepare failed).";
    return false;
  }
  $check->bind_param("s", $applicantID);
  $check->execute();
  $res = $check->get_result();
  if ($res && $res->num_rows > 0) {
    $check->close();
    return true;
  }
  $check->close();

  // Get user fullname and email (if available)
  $u = $conn->prepare("SELECT fullname, email, user_id FROM user WHERE applicant_employee_id = ? OR user_id = ?");
  if (!$u) {
    $flash_error = "Server error (prepare failed).";
    return false;
  }
  $u->bind_param("ss", $applicantID, $applicantID);
  $u->execute();
  $urow = $u->get_result()->fetch_assoc();
  $u->close();

  $fullname = $urow['fullname'] ?? 'Unknown Applicant';
  $email = $urow['email'] ?? '';

  // Provide safe defaults for required NOT NULL fields in applicant table
  $position_applied = '';
  $department = 0;
  $date_applied = date('Y-m-d');
  $contact_number = '';
  $email_address = $email;
  $home_address = '';
  $job_title = '';
  $company_name = '';
  $date_started = date('Y-m-d');
  $years_experience = 0;
  $in_role = 'no';
  $university = '';
  $course = '';
  $year_graduated = '0000';
  $skills = '';
  $summary = '';
  $status = 'Active';
  $profile_pic = null;

  $ins = $conn->prepare("INSERT INTO applicant (applicantID, fullName, position_applied, department, date_applied, contact_number, email_address, home_address, job_title, company_name, date_started, years_experience, in_role, university, course, year_graduated, skills, summary, status, profile_pic)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
  if (!$ins) {
    $flash_error = "Server error (prepare failed inserting).";
    return false;
  }
  $ins->bind_param(
    "sssissssssissssssss",
    $applicantID,
    $fullname,
    $position_applied,
    $department,
    $date_applied,
    $contact_number,
    $email_address,
    $home_address,
    $job_title,
    $company_name,
    $date_started,
    $years_experience,
    $in_role,
    $university,
    $course,
    $year_graduated,
    $skills,
    $summary,
    $status,
    $profile_pic
  );


  // Note: if your DB rejects '0000' year or null profile_pic, adjust accordingly.
  if ($ins->execute()) {
    $flash_success = "Applicant record created.";
    $ins->close();
    return true;
  } else {
    $flash_error = "Failed to create applicant record: " . $ins->error;
    $ins->close();
    return false;
  }
}

/* -------------------------- Handle form submissions (CRUD) -------------------------- */

/* Update profile (phone, home address) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $new_phone = trim($_POST['phoneNumber'] ?? '');
  $new_location = trim($_POST['homeLocation'] ?? '');

  if ($new_phone === '' || $new_location === '') {
    $_SESSION['flash_error'] = 'Please fill all profile fields.';
  } else {
    $update = $conn->prepare("UPDATE applicant SET contact_number = ?, home_address = ? WHERE applicantID = ?");
    if ($update) {
      $update->bind_param("sss", $new_phone, $new_location, $session_emp);
      if ($update->execute()) {
        $_SESSION['flash_success'] = 'Profile updated successfully.';
      } else {
        $_SESSION['flash_error'] = 'Failed to update profile.';
      }
      $update->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* Add role */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
  $job_title_input = trim($_POST['job_title'] ?? '');
  $company_input = trim($_POST['company_name'] ?? '');
  $years_experience = trim($_POST['years_experience'] ?? '0');

  if ($job_title_input === '' || $company_input === '') {
    $_SESSION['flash_error'] = 'Please fill all role fields.';
  } else {
    $ins = $conn->prepare("UPDATE applicant 
                               SET job_title = ?, company_name = ?, years_experience = ?
                               WHERE applicantID = ?");
    if ($ins) {
      $ins->bind_param("ssis", $job_title_input, $company_input, $years_experience, $session_emp);
      if ($ins->execute()) {
        $_SESSION['flash_success'] = 'Role saved successfully.';
      } else {
        $_SESSION['flash_error'] = 'Failed to save role: ' . $ins->error;
      }
      $ins->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }

  header("Location: Applicant_Profile.php");
  exit();
}


/* Edit role */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_role'])) {
  $job_title_input = trim($_POST['job_title_edit'] ?? '');
  $company_input = trim($_POST['company_name_edit'] ?? '');
  $years_experience = trim($_POST['years_experience_edit'] ?? '0');

  if ($job_title_input === '' || $company_input === '') {
    $_SESSION['flash_error'] = 'Please fill all role fields.';
  } else {
    $upd = $conn->prepare("UPDATE applicant 
                               SET job_title = ?, company_name = ?, years_experience = ?
                               WHERE applicantID = ?");
    if ($upd) {
      $upd->bind_param("ssis", $job_title_input, $company_input, $years_experience, $session_emp);
      if ($upd->execute()) {
        $_SESSION['flash_success'] = 'Role updated successfully.';
      } else {
        $_SESSION['flash_error'] = 'Failed to update role: ' . $upd->error;
      }
      $upd->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }

  header("Location: Applicant_Profile.php");
  exit();
}


/* Delete role */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_role'])) {

  $del = $conn->prepare("UPDATE applicant 
                           SET job_title = '', company_name = '', years_experience = 0 
                           WHERE applicantID = ?");
  if ($del) {
    $del->bind_param("s", $session_emp);
    if ($del->execute()) {
      $_SESSION['flash_success'] = 'Role deleted.';
    } else {
      $_SESSION['flash_error'] = 'Failed to delete role.';
    }
    $del->close();
  } else {
    $_SESSION['flash_error'] = 'Server error (prepare failed).';
  }

  header("Location: Applicant_Profile.php");
  exit();
}


/* Add education (single-entry fields on applicant table) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_education'])) {
  $school = trim($_POST['school'] ?? '');
  $degree = trim($_POST['degree'] ?? '');

  if ($school === '' || $degree === '') {
    $_SESSION['flash_error'] = 'Please fill all education fields.';
  } else {
    // year_graduated not collected in modal — keep default '0000' unless provided
    $year_graduated = $_POST['year_graduated'] ?? '0000';

    $ins = $conn->prepare("UPDATE applicant SET university = ?, course = ?, year_graduated = ? WHERE applicantID = ?");
    if ($ins) {
      $ins->bind_param("ssis", $school, $degree, $year_graduated, $session_emp);
      if ($ins->execute())
        $_SESSION['flash_success'] = 'Education saved.';
      else
        $_SESSION['flash_error'] = 'Failed to save education: ' . $ins->error;
      $ins->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* Edit education */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_education'])) {
  $school_edit = trim($_POST['school_edit'] ?? '');
  $degree_edit = trim($_POST['degree_edit'] ?? '');
  $year_graduated = trim($_POST['year_graduated_edit'] ?? '0000');

  if ($school_edit === '' || $degree_edit === '') {
    $_SESSION['flash_error'] = 'Please fill all education fields.';
  } else {
    $updEdu = $conn->prepare("UPDATE applicant SET university = ?, course = ?, year_graduated = ? WHERE applicantID = ?");
    if ($updEdu) {
      $updEdu->bind_param("ssis", $school_edit, $degree_edit, $year_graduated, $session_emp);
      if ($updEdu->execute())
        $_SESSION['flash_success'] = 'Education updated.';
      else
        $_SESSION['flash_error'] = 'Failed to update education.';
      $updEdu->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* Delete education (clear single-entry education fields) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_education'])) {
  $delEdu = $conn->prepare("UPDATE applicant SET university = '', course = '', year_graduated = '0000' WHERE applicantID = ?");
  if ($delEdu) {
    $delEdu->bind_param("s", $session_emp);
    if ($delEdu->execute())
      $_SESSION['flash_success'] = 'Education cleared.';
    else
      $_SESSION['flash_error'] = 'Failed to clear education.';
    $delEdu->close();
  } else {
    $_SESSION['flash_error'] = 'Server error (prepare failed).';
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* Add skills */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skills'])) {
  $skills = [];
  $missing = false;
  for ($i = 1; $i <= 5; $i++) {
    $k = trim($_POST['skill' . $i] ?? '');
    if ($k === '') {
      $missing = true;
      break;
    }
    $skills[] = $k;
  }
  if ($missing) {
    $_SESSION['flash_error'] = 'Please fill in all five skills before saving.';
  } else {
    $skills_str = implode(', ', $skills);
    $update_stmt = $conn->prepare("UPDATE applicant SET skills = ? WHERE applicantID = ?");
    if ($update_stmt) {
      $update_stmt->bind_param("ss", $skills_str, $session_emp);
      if ($update_stmt->execute())
        $_SESSION['flash_success'] = 'Skills updated.';
      else
        $_SESSION['flash_error'] = 'Failed to update skills.';
      $update_stmt->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* Add summary */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_summary'])) {
  $summary_text = trim($_POST['summary_text'] ?? '');
  if ($summary_text === '') {
    $_SESSION['flash_error'] = 'Please write a summary before saving.';
  } else {
    $update_stmt = $conn->prepare("UPDATE applicant SET summary = ? WHERE applicantID = ?");
    if ($update_stmt) {
      $update_stmt->bind_param("ss", $summary_text, $session_emp);
      if ($update_stmt->execute())
        $_SESSION['flash_success'] = 'Summary saved.';
      else
        $_SESSION['flash_error'] = 'Failed to save summary.';
      $update_stmt->close();
    } else {
      $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
  }
  header("Location: Applicant_Profile.php");
  exit();
}

/* -------------------------- Ensure applicant exists & load data -------------------------- */
if (!ensure_applicant_exists($conn, $session_emp, $flash_error, $flash_success)) {
  // If we failed to create or check, set a flash and continue (page will show "Unknown Applicant")
  if (!empty($flash_error))
    $_SESSION['flash_error'] = $flash_error;
  header("Location: Applicant_Profile.php");
  exit();
}

// Now load applicant data
$stmt = $conn->prepare("SELECT fullName, email_address, contact_number, home_address, skills, summary, 
                        job_title, company_name, date_started, years_experience, in_role,
                        university, course, year_graduated 
                        FROM applicant 
                        WHERE applicantID = ?");


$stmt->bind_param("s", $session_emp);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
  $name = htmlspecialchars($row['fullName'] ?? 'Unknown Applicant');
  $email = htmlspecialchars($row['email_address'] ?? '');
  $phone = htmlspecialchars($row['contact_number'] ?? '');
  $location = htmlspecialchars($row['home_address'] ?? '');
  $skills_str = $row['skills'] ?? '';
  $summary_text = $row['summary'] ?? '';
  $skills_array = array_filter(array_map('trim', preg_split('/\s*,\s*/', $skills_str)));
  // role / career (single)
  $role_job_title = htmlspecialchars($row['job_title'] ?? '');
  $role_company = htmlspecialchars($row['company_name'] ?? '');
  $role_date_started = htmlspecialchars($row['date_started'] ?? '');
  $years_experience = htmlspecialchars($row['years_experience'] ?? '0');
  $role_in_role = htmlspecialchars($row['in_role'] ?? 'no');
  // education (single)
  $edu_school = htmlspecialchars($row['university'] ?? '');
  $edu_degree = htmlspecialchars($row['course'] ?? '');
  $edu_year = htmlspecialchars($row['year_graduated'] ?? '');
} else {
  $name = "Unknown Applicant";
  $email = "";
  $phone = "";
  $location = "";
  $skills_array = [];
  $summary_text = '';
  $role_job_title = $role_company = $role_date_started = $role_in_role = '';
  $edu_school = $edu_degree = $edu_year = '';
}
$stmt->close();

/* -------------------------- For display we will create 'roles' as a single-item array if job_title exists -------------------------- */
$roles = [];
if (!empty($role_job_title) || !empty($role_company) || !empty($years_experience)) {
  $roles[] = [
    'job_title' => $role_job_title,
    'company_name' => $role_company,
    'years_experience' => $years_experience
  ];
}

/* education is single entry */
$edus = [];
if (!empty($edu_school) || !empty($edu_degree)) {
  $edus[] = ['id' => 0, 'school' => $edu_school, 'degree' => $edu_degree, 'created_at' => $edu_year];
}

// Pass any session flashes through to local variables to show in HTML
$flash_success = $_SESSION['flash_success'] ?? $flash_success;
$flash_error = $_SESSION['flash_error'] ?? $flash_error;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);


// Handle profile picture upload
if (isset($_POST['upload'])) {
  if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $file_name = $_FILES['profile_pic']['name'];
    $file_tmp = $_FILES['profile_pic']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png']; // Allow PNG too if needed
    if (in_array($file_ext, $allowed_extensions)) {
      $upload_dir = "uploads/applicants/";
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      // Use applicantID (or session_emp) for unique filename
      $new_filename = "applicant_" . $session_emp . "." . $file_ext;
      $upload_path = $upload_dir . $new_filename;

      // Move file and update database
      if (move_uploaded_file($file_tmp, $upload_path)) {
        $update = $conn->prepare("UPDATE applicant SET profile_pic = ? WHERE applicantID = ?");
        $update->bind_param("ss", $new_filename, $session_emp);
        if ($update->execute()) {
          $_SESSION['flash_success'] = 'Profile picture updated successfully.';
        } else {
          $_SESSION['flash_error'] = 'Failed to update profile picture in database.';
        }
        $update->close();
      } else {
        $_SESSION['flash_error'] = 'Error uploading the file.';
      }
    } else {
      $_SESSION['flash_error'] = 'Invalid file type. Only JPG, JPEG, or PNG allowed.';
    }
  } else {
    $_SESSION['flash_error'] = 'No file selected or upload error.';
  }
  header("Location: Applicant_Profile.php");
  exit();
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Applicant Profile</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Fonts / Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="applicant.css">
  <style>
    /* Preserve your inline styles from previous file so layout remains consistent */
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background: #f1f5fc;
      color: #111827;
    }

    .main-content {
      flex: 1;
      padding: 30px 80px;
      display: flex;
      flex-direction: column;
      gap: 40px;
    }

    .profile-header {
      padding: 5px 10px;
      margin-left: 200px;
      font-size: 40px;
    }

    .personal-info {
      background: #1E3A8A;
      color: #fff;
      padding: 30px;
      margin-left: 200px;
      border-radius: 15px;
      width: 1550px;
      height: 600px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
      font-size: 20px;
      font-weight: 500;
    }

    .info {
      display: flex;
      margin-left: 50px;
      gap: 15px;
      flex-direction: column;
    }

    .phone,
    .location,
    .email {
      display: flex;
    }

    p {
      margin-left: 20px;
    }

    .edit-btn {
      height: 42px;
      width: 93px;
      border-color: white;
      border-style: solid;
      background: #1E3A8A;
      color: #fff;
    }

    .edit-btn:hover {
      background: #e5edfb;
      color: #1E3A8A;
    }

    .section {
      padding: 30px;
      border-radius: 15px;
      width: 1550px;
      color: #1E3A8A;
    }

    .reminder {
      display: flex;
      align-items: center;
      background: #e5ebf7;
      height: 86px;
      color: #1E3A8A;
      border-radius: 20px;
      width: 1250px;
    }

    .add-btn {
      height: 51px;
      width: 213px;
      border-color: #1E3A8A;
      border-style: solid;
      background: #fff;
      color: #1E3A8A;
    }

    .complete-box {
      padding: 30px;
      border-radius: 15px;
      width: 1250px;
      background: #e5ebf7;
      color: #1E3A8A;
    }

    .complete-btn {
      background: #1E3A8A;
      border-style: solid;
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background: rgba(0, 0, 0, 0.4);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      width: 400px;
      max-width: 90%;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .modal-content input,
    .modal-content textarea {
      width: 100%;
      padding: 8px;
      margin-top: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-family: 'Poppins', sans-serif;
    }

    .save-btn {
      background: #1E3A8A;
      color: #fff;
      padding: 8px 12px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
    }

    .cancel-btn {
      background: #ccc;
      padding: 8px 12px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      margin-left: 8px;
    }

    .skill-chip {
      display: inline-block;
      background: linear-gradient(135deg, #eef2ff 0%, #e6f0ff 100%);
      color: #0f172a;
      padding: 6px 12px;
      border-radius: 999px;
      margin: 4px;
      font-size: 14px;
    }

    .flash-success {
      background: #d1fae5;
      color: #065f46;
      padding: 12px;
      border-radius: 8px;
      margin-left: 200px;
      max-width: 1200px;
      box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
    }

    .flash-error {
      background: #fee2e2;
      color: #991b1b;
      padding: 12px;
      border-radius: 8px;
      margin-left: 200px;
      max-width: 1200px;
      box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile"><i class="fa-solid fa-user"></i></a>
    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <main class="main-content">
    <div class="profile-header">
      <h2>Applicant Profile</h2>
    </div>
    <?php if (!empty($flash_success)): ?>
      <div class="flash-success"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="flash-error"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <div class="personal-info">
      <div class="info">
        <h1 name="full-name"><?php echo $name; ?></h1>

        <div class="phone"><i class="fa-solid fa-phone"></i>
          <p name="phone"><?php echo $phone ?: 'Not provided'; ?></p>
        </div>
        <div class="location"><i class="fa-solid fa-location-dot"></i>
          <p name="location"><?php echo $location ?: 'Not provided'; ?></p>
        </div>
        <div class="email"><i class="fa-solid fa-envelope"></i>
          <p name="email"><?php echo $email ?: 'Not provided'; ?></p>
        </div>




        <button onclick="openModal('editModal')" class="edit-btn"><i class="fa-solid fa-pen"></i> Edit</button>

        <div class="profile-pic-container">
          <?php
          // Fetch the current profile pic filename
          $stmtPic = $conn->prepare("SELECT profile_pic FROM applicant WHERE applicantID = ?");
          $stmtPic->bind_param("s", $session_emp);
          $stmtPic->execute();
          $picResult = $stmtPic->get_result()->fetch_assoc();
          $stmtPic->close();

          $profile_pic = $picResult['profile_pic'] ?? 'default.jpg';
          ?>
          <img src="uploads/applicants/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture"
            class="profile-pic" style="width:150px; height:150px; border-radius:50%; object-fit:cover;">

          <form method="POST" enctype="multipart/form-data" class="upload-form" style="margin-top:10px;">
            <input type="file" name="profile_pic" accept=".jpg, .jpeg, .png" required>
            <button type="submit" name="upload">Upload</button>

          </form>

        </div>

        <!-- Career / Roles -->
        <div class="section">
          <div class="career">
            <h3>Career history</h3>
            <div class="reminder">
              <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent roles</p>
            </div>
            <button onclick="openModal('roleModal')" class="add-btn">Add role</button>

            <?php if (!empty($roles)): ?>
              <div style="margin-top:12px;margin-left:10px;">
                <?php foreach ($roles as $r): ?>
                  <div
                    style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;position:relative;">
                    <strong><?php echo htmlspecialchars($r['job_title']); ?></strong>

                    <?php if (!empty($r['company_name'])): ?>
                      <div style="font-size:14px;color:#555;"><?php echo htmlspecialchars($r['company_name']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($r['years_experience'])): ?>
                      <div style="font-size:14px;color:#555;"><?php echo htmlspecialchars($r['years_experience']); ?> years
                      </div>
                    <?php endif; ?>

                    <div style="position:absolute;right:10px;top:10px;display:flex;gap:6px;">
                      <button type="button" class="save-btn"
                        onclick="openRoleEditModal(<?php echo json_encode($r['job_title']); ?>, <?php echo json_encode($r['company_name']); ?>)">Edit</button>

                      <form method="POST" style="display:inline;" onsubmit="return confirm('Clear role from profile?');">
                        <input type="hidden" name="delete_role" value="1">
                        <button type="submit" class="cancel-btn">Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Education -->
        <div class="section">
          <div class="education">
            <h3>Education</h3>
            <div class="reminder">
              <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent qualifications</p>
            </div>
            <button onclick="openModal('educationModal')" class="add-btn">Add education</button>

            <?php if (!empty($edus)): ?>
              <div style="margin-top:12px;margin-left:10px;">
                <?php foreach ($edus as $e): ?>
                  <div
                    style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;position:relative;">
                    <strong><?php echo htmlspecialchars($e['school']); ?></strong>
                    <?php if (!empty($e['degree'])): ?>
                      <div style="font-size:14px;color:#555;"><?php echo htmlspecialchars($e['degree']); ?></div>
                    <?php endif; ?>
                    <div style="position:absolute;right:10px;top:10px;display:flex;gap:6px;">
                      <button type="button" class="save-btn"
                        onclick="openEducationEditModal(<?php echo json_encode($e['school']); ?>, <?php echo json_encode($e['degree']); ?>)">Edit</button>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Clear education entry?');">
                        <input type="hidden" name="delete_education" value="1">
                        <button type="submit" class="cancel-btn">Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Skills -->
        <div class="section">
          <h3>Skills</h3>
          <div class="reminder">
            <p><i class="fa-solid fa-circle-exclamation"></i> Please add at least five skills</p>
          </div>
          <button onclick="prefillSkillsModal(); openModal('skillsModal');" class="add-btn">Add / Edit skills</button>

          <?php if (!empty($skills_array)): ?>
            <div style="margin-top:12px;margin-left:10px;">
              <div style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;">
                <?php foreach ($skills_array as $sk): ?>
                  <span class="skill-chip"><?php echo htmlspecialchars($sk); ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Summary -->
        <div class="section">
          <h3>Summary</h3>
          <div class="reminder">
            <p><i class="fa-solid fa-circle-exclamation"></i> Please add a summary</p>
          </div>
          <button onclick="prefillSummaryModal(); openModal('summaryModal');" class="add-btn">Add / Edit
            summary</button>

          <?php if (!empty($summary_text)): ?>
            <div
              style="margin-top:12px;margin-left:10px;max-width:1200px;background:#fff;padding:12px;border-radius:8px;">
              <?php echo nl2br(htmlspecialchars($summary_text)); ?>
            </div>
          <?php endif; ?>
        </div>
        <!--
        <div class="complete-box">
          <p>Ensure all information are correct, your resume will be created</p>
          <button class="complete-btn">Complete</button>
        </div>
          -->

  </main>

  <!-- EDIT Profile Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <h3>Edit Profile</h3>
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div style="text-align:left;margin-bottom:8px;">
          <label style="font-weight:600;">Name</label>
          <div style="background:#f3f4f6;padding:8px;border-radius:6px;margin-top:4px;">
            <?php echo htmlspecialchars($name); ?>
          </div>
        </div>
        <div style="text-align:left;margin-bottom:8px;">
          <label style="font-weight:600;">Email</label>
          <div style="background:#f3f4f6;padding:8px;border-radius:6px;margin-top:4px;">
            <?php echo htmlspecialchars($email); ?>
          </div>
        </div>

        <input type="text" name="phoneNumber" placeholder="Phone number" required
          value="<?php echo htmlspecialchars($phone); ?>">
        <input type="text" name="homeLocation" placeholder="Home location" required
          value="<?php echo htmlspecialchars($location); ?>">

        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ROLE Modal -->
  <div id="roleModal" class="modal">
    <div class="modal-content">
      <h3>Add Role</h3>
      <form method="POST">
        <input type="hidden" name="add_role" value="1">
        <input type="text" name="job_title" placeholder="Job Title" required>
        <input type="text" name="company_name" placeholder="Company Name" required>
        <input type="number" name="years_experience" placeholder="Years of Experience" min="0" required>
        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('roleModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT ROLE Modal -->
  <div id="editRoleModal" class="modal">
    <div class="modal-content">
      <h3>Edit Role</h3>
      <form method="POST">
        <input type="hidden" name="edit_role" value="1">
        <input type="text" name="job_title_edit" id="job_title_edit" placeholder="Job Title" required>
        <input type="text" name="company_name_edit" id="company_name_edit" placeholder="Company Name" required>

        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('editRoleModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDUCATION Modal -->
  <div id="educationModal" class="modal">
    <div class="modal-content">
      <h3>Add Education</h3>
      <form method="POST">
        <input type="hidden" name="add_education" value="1">
        <input type="text" name="school" placeholder="School / University" required>
        <input type="text" name="degree" placeholder="Degree / Course" required>
        <input type="text" name="year_graduated" placeholder="Year graduated (YYYY)">
        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('educationModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT EDUCATION Modal -->
  <div id="editEducationModal" class="modal">
    <div class="modal-content">
      <h3>Edit Education</h3>
      <form method="POST">
        <input type="hidden" name="edit_education" value="1">
        <input type="text" name="school_edit" id="school_edit" placeholder="School / University" required>
        <input type="text" name="degree_edit" id="degree_edit" placeholder="Degree / Course" required>
        <input type="text" name="year_graduated_edit" id="year_graduated_edit" placeholder="Year graduated (YYYY)">
        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('editEducationModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- SKILLS Modal -->
  <div id="skillsModal" class="modal">
    <div class="modal-content">
      <h3>Add Skills</h3>
      <form method="POST">
        <input type="hidden" name="add_skills" value="1">
        <input type="text" id="skill1" name="skill1" placeholder="Skill 1" required>
        <input type="text" id="skill2" name="skill2" placeholder="Skill 2" required>
        <input type="text" id="skill3" name="skill3" placeholder="Skill 3" required>
        <input type="text" id="skill4" name="skill4" placeholder="Skill 4" required>
        <input type="text" id="skill5" name="skill5" placeholder="Skill 5" required>
        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('skillsModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- SUMMARY Modal -->
  <div id="summaryModal" class="modal">
    <div class="modal-content">
      <h3>Add Summary</h3>
      <form method="POST">
        <input type="hidden" name="add_summary" value="1">
        <textarea id="summary_text" name="summary_text" rows="4" placeholder="Write a short professional summary..."
          required></textarea>
        <div style="display:flex;justify-content:center;gap:8px;">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" onclick="closeModal('summaryModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    window.onclick = function (e) { document.querySelectorAll('.modal').forEach(m => { if (e.target == m) m.style.display = 'none'; }); }

    function openRoleEditModal(title, company) {
      document.getElementById('job_title_edit').value = title || '';
      document.getElementById('company_name_edit').value = company || '';
      document.getElementById('role_description_edit').value = ''; // no per-role description stored separately; user can add a new description and it will be appended to summary
      openModal('editRoleModal');
    }

    function openEducationEditModal(school, degree) {
      document.getElementById('school_edit').value = school || '';
      document.getElementById('degree_edit').value = degree || '';
      document.getElementById('year_graduated_edit').value = '<?php echo addslashes($edu_year); ?>' || '';
      openModal('editEducationModal');
    }

    function prefillSkillsModal() {
      try {
        const skills = <?php echo json_encode(array_values($skills_array)); ?>;
        for (let i = 1; i <= 5; i++) {
          const el = document.getElementById('skill' + i);
          if (el) el.value = skills[i - 1] || '';
        }
      } catch (e) { }
    }

    function prefillSummaryModal() {
      try {
        const text = <?php echo json_encode($summary_text ?? ''); ?>;
        const el = document.getElementById('summary_text');
        if (el) el.value = text;
      } catch (e) { }
    }
  </script>

</body>

</html>