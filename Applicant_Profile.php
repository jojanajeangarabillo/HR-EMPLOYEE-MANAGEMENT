<?php
session_start();
require 'admin/db.connect.php';

// -------------------------------
// 1. Get Applicant ID from Session
// -------------------------------
$applicantID = $_SESSION['applicant_employee_id'] ?? null;

if (!$applicantID) {
  die("Applicant ID not found in session.");
}

// -------------------------------
// 2. Fetch Applicant Basic Info (Full Name + Picture)
// -------------------------------
$stmt = $conn->prepare("SELECT fullName, profile_pic FROM applicant WHERE applicantID = ?");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $applicantname = $row['fullName'];
  $profile_picture = !empty($row['profile_pic'])
    ? "uploads/applicants/" . $row['profile_pic']
    : "uploads/employees/default.png";
} else {
  $applicantname = "Applicant";
  $profile_picture = "uploads/employees/default.png";
}

// -------------------------------
// 3. Fetch ALL Applicant Details
// -------------------------------
$stmt = $conn->prepare("
    SELECT applicantID, fullName, position_applied, department, type_name, date_applied,
           contact_number, email_address, home_address,
           university, course, year_graduated,
           previous_job, company_name, years_experience, in_role,
           summary, skills, status, hired_at, profile_pic
    FROM applicant
    WHERE applicantID = ?
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

$applicant = $result->fetch_assoc();

// Helper for displaying empty fields
function displayOrEmpty($value)
{
  return !empty($value) ? htmlspecialchars($value) : "<span style='color:#888;'>Not set</span>";
}
// Handle applicant profile picture upload
if (isset($_POST['upload_pic'])) {

  if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

    $file_name = $_FILES['profile_pic']['name'];
    $file_tmp = $_FILES['profile_pic']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png'];

    if (in_array($file_ext, $allowed_extensions)) {

      // Directory for applicants
      $upload_dir = "uploads/applicants/";
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
      }

      // Rename the file
      $new_filename = "applicant_" . $applicantID . "." . $file_ext;
      $upload_path = $upload_dir . $new_filename;

      // Move uploaded file
      if (move_uploaded_file($file_tmp, $upload_path)) {

        // Update database
        $update = $conn->prepare("UPDATE applicant SET profile_pic = ? WHERE applicantID = ?");
        $update->bind_param("ss", $new_filename, $applicantID);

        if ($update->execute()) {
          $_SESSION['flash_success'] = 'Profile picture updated successfully.';
        } else {
          $_SESSION['flash_error'] = 'Failed to update profile picture in the database.';
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

// -------------------------------
// 4. UPDATE: PERSONAL INFORMATION
// -------------------------------
if (isset($_POST['save_personal'])) {
  $stmt = $conn->prepare("
        UPDATE applicant 
        SET fullName=?, contact_number=?, email_address=?, home_address=? 
        WHERE applicantID=?
    ");
  $stmt->bind_param(
    "sssss",
    $_POST['fullName'],
    $_POST['contact_number'],
    $_POST['email_address'],
    $_POST['home_address'],
    $applicantID
  );
  $stmt->execute();
  header("Location: Applicant_Profile.php");
  exit();
}


// -------------------------------
// 5. UPDATE: EDUCATION
// -------------------------------
if (isset($_POST['save_education'])) {
  $stmt = $conn->prepare("
        UPDATE applicant 
        SET university=?, course=?, year_graduated=?
        WHERE applicantID=?
    ");
  $stmt->bind_param(
    "ssss",
    $_POST['university'],
    $_POST['course'],
    $_POST['year_graduated'],
    $applicantID
  );
  $stmt->execute();
  header("Location: Applicant_Profile.php");
  exit();
}


// -------------------------------
// 6. UPDATE: WORK EXPERIENCE
// -------------------------------
if (isset($_POST['save_job'])) {
  $stmt = $conn->prepare("
        UPDATE applicant 
        SET previous_job=?, company_name=?, years_experience=?
        WHERE applicantID=?
    ");
  $stmt->bind_param(
    "ssss",
    $_POST['previous_job'],
    $_POST['company_name'],
    $_POST['years_experience'],
    $applicantID
  );
  $stmt->execute();
  header("Location: Applicant_Profile.php");
  exit();
}


// -------------------------------
// 7. UPDATE: SKILLS & SUMMARY
// -------------------------------
if (isset($_POST['save_skills'])) {
  // Use 'skill[]' from the form
  $skills = isset($_POST['skill']) ? implode(", ", array_map('trim', $_POST['skill'])) : '';
  $summary = $_POST['summary'] ?? '';

  $stmt = $conn->prepare("
        UPDATE applicant 
        SET skills=?, summary=?
        WHERE applicantID=?
    ");
  $stmt->bind_param("sss", $skills, $summary, $applicantID);
  $stmt->execute();

  header("Location: Applicant_Profile.php");
  exit();
}




?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Applicant Profile</title>
  <link rel="stylesheet" href="applicant.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f4f6f9;
      color: #111827;
    }

    .main-content {
      flex: 1;
      padding: 40px 60px;
      display: flex;
      flex-direction: column;
      gap: 40px;
    }

    h1 {
      color: #1f2937;
      font-weight: 600;
      font-size: 2rem;
    }

    section {
      background: #ffffff;
      border-radius: 15px;
      padding: 30px 40px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      position: relative;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    section:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    section h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #1e3a8a;
      margin-bottom: 20px;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 5px;
    }

    section div {
      font-size: 1rem;
      margin-bottom: 10px;
    }

    .edit-btn {
      position: absolute;
      bottom: 20px;
      right: 20px;
      border: none;
      background: #1e3a8a;
      color: #fff;
      padding: 6px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.2s ease;
    }

    .edit-btn:hover {
      background: #3b82f6;
    }

    .text-center img {
      border: 3px solid #1e3a8a;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    /* Cleaner, modern modal */
    .modal-dialog {
      max-width: 600px !important;
    }

    .modal-content {
      border-radius: 16px;
      border: none;
      padding: 10px 5px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .modal-header {
      background: #1E3A8A;
      color: white;
      border-bottom: none;
      padding: 18px 24px;
      border-top-left-radius: 16px;
      border-top-right-radius: 16px;
    }

    .modal-title {
      font-weight: 600;
      font-size: 1.2rem;
    }

    .btn-close {
      filter: brightness(0) invert(1);
    }

    .modal-body {
      padding: 20px 25px;
    }

    .form-label {
      font-weight: 600;
      color: #1f2937;
    }

    .form-control {
      padding: 10px 14px;
      border-radius: 10px;
      border: 1px solid #d1d5db;
      font-size: 0.95rem;
      transition: all .2s;
    }

    .form-control:focus {
      border-color: #1E3A8A;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.15);
    }

    .modal-footer {
      padding: 15px 25px;
      border-top: 1px solid #e5e7eb;
    }

    /* Buttons */
    .btn-primary {
      background-color: #1E3A8A;
      border-radius: 10px;
      border: none;
      padding: 10px 18px;
      font-weight: 500;
    }

    .btn-primary:hover {
      background-color: #2d4fbf;
    }

    .btn-secondary {
      border-radius: 10px;
      padding: 10px 18px;
    }

    /* Slight spacing fix between fields */
    .mb-3 {
      margin-bottom: 18px !important;
    }

    /* Prevent global SECTION styling from affecting modals */
    .modal section {
      width: auto !important;
      max-width: none !important;
      margin-left: 0 !important;
      padding: 0 !important;
      box-shadow: none !important;
    }




    /* Section width */
    section {
      width: 90%;
      /* fit main-content width */
      max-width: 100%;
      /* prevent narrow sections */
      background: #fff;
      padding: 25px 30px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      position: relative;
      margin-left: 220px;
    }

    .edit-btn {
      position: absolute;
      bottom: 15px;
      right: 15px;
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 1.2em;
      color: #224288;
    }

    .edit-btn:hover {
      color: #274ea0;
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

    .main-content h1 {
      color: #1E3A8A;
      font-weight: 700;
      font-size: 2.2rem;
      margin-bottom: 20px;
      margin-left: -690px;
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
      <img
        src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>"
        alt="Profile" class="sidebar-profile-img">

    </a>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $applicantname"; ?></p>
    </div>

    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <main class="main-content">
    <h1 class="text-center mb-4">Applicant Profile</h1>

    <!-- Personal Info Section -->
    <section>
      <h2>Personal Information</h2>
      <div class="text-center mb-3">
        <img id="profile-preview" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile"
          class="rounded-circle" style="width:130px; height:130px; border:2px solid #224288; object-fit:cover;">
        <form action="" method="post" enctype="multipart/form-data">
          <input type="file" name="profile_pic" id="profile-upload" style="display:none;" accept="image/*">
          <button type="button" class="btn btn-primary btn-sm mt-2" id="upload-btn">Upload Photo</button>
          <button type="submit" name="upload_pic" id="submit-btn" style="display:none;"></button>
        </form>
      </div>
      <div><strong>Full Name:</strong> <?php echo displayOrEmpty($applicant['fullName']); ?></div>
      <div><strong>Applicant ID:</strong> <?php echo htmlspecialchars($applicantID); ?></div>
      <div><strong>Contact:</strong> <?php echo displayOrEmpty($applicant['contact_number']); ?></div>
      <div><strong>Email:</strong> <?php echo displayOrEmpty($applicant['email_address']); ?></div>
      <div><strong>Home Address:</strong> <?php echo displayOrEmpty($applicant['home_address']); ?></div>
      <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#personalModal"><i
          class="fa-solid fa-pen-to-square"></i></button>
    </section>

    <!-- Job / Experience Section -->
    <section>
      <h2>Job / Experience</h2>
      <div><strong>Previous Job:</strong> <?php echo displayOrEmpty($applicant['previous_job']); ?></div>
      <div><strong>Company:</strong> <?php echo displayOrEmpty($applicant['company_name']); ?></div>
      <div><strong>Years Experience:</strong> <?php echo displayOrEmpty($applicant['years_experience']); ?></div>
      <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#jobModal"><i
          class="fa-solid fa-pen-to-square"></i></button>
    </section>

    <!-- Education Section -->
    <section>
      <h2>Education</h2>
      <div><strong>University:</strong> <?php echo displayOrEmpty($applicant['university']); ?></div>
      <div><strong>Course:</strong> <?php echo displayOrEmpty($applicant['course']); ?></div>
      <div><strong>Year Graduated:</strong> <?php echo displayOrEmpty($applicant['year_graduated']); ?></div>
      <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#educationModal"><i
          class="fa-solid fa-pen-to-square"></i></button>
    </section>

    <!-- Skills & Summary Section -->
    <section>
      <h2>Skills & Summary</h2>
      <div><strong>Skills:</strong>
        <?php
        $skills_list = array_filter(array_map('trim', explode(',', $applicant['skills'] ?? '')));
        if (!empty($skills_list)) {
          foreach ($skills_list as $skill) {
            echo "<span class='badge bg-primary me-1'>" . htmlspecialchars($skill) . "</span>";
          }
        } else {
          echo "<span style='color:#888;'>Not set</span>";
        }
        ?>
      </div>
      <div><strong>Summary:</strong> <?php echo displayOrEmpty($applicant['summary']); ?></div>
      <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#skillsModal"><i
          class="fa-solid fa-pen-to-square"></i></button>
    </section>

    <!-- Personal Info Modal -->
    <div class="modal fade" id="personalModal" tabindex="-1" aria-labelledby="personalModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="personalModalLabel">Edit Personal Info</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="fullName"
                  value="<?php echo htmlspecialchars($applicant['fullName']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" class="form-control" name="contact_number"
                  value="<?php echo htmlspecialchars($applicant['contact_number']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email_address"
                  value="<?php echo htmlspecialchars($applicant['email_address']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Home Address</label>
                <input type="text" class="form-control" name="home_address"
                  value="<?php echo htmlspecialchars($applicant['home_address']); ?>">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="save_personal" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Job / Experience Modal -->
    <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="jobModalLabel">Edit Job / Experience</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Job Title</label>
                <input type="text" class="form-control" name="previous_job"
                  value="<?php echo htmlspecialchars($applicant['previous_job']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Company</label>
                <input type="text" class="form-control" name="company_name"
                  value="<?php echo htmlspecialchars($applicant['company_name']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Years Experience</label>
                <input type="number" class="form-control" name="years_experience"
                  value="<?php echo htmlspecialchars($applicant['years_experience']); ?>">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="save_job" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Education Modal -->
    <div class="modal fade" id="educationModal" tabindex="-1" aria-labelledby="educationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="educationModalLabel">Edit Education</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">University</label>
                <input type="text" class="form-control" name="university"
                  value="<?php echo htmlspecialchars($applicant['university']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Course</label>
                <input type="text" class="form-control" name="course"
                  value="<?php echo htmlspecialchars($applicant['course']); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Year Graduated</label>
                <input type="number" class="form-control" name="year_graduated"
                  value="<?php echo htmlspecialchars($applicant['year_graduated']); ?>">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="save_education" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Skills & Summary Modal -->
    <div class="modal fade" id="skillsModal" tabindex="-1" aria-labelledby="skillsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="skillsModalLabel">Edit Skills & Summary</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <?php
                $existing_skills = array_filter(array_map('trim', explode(',', $applicant['skills'] ?? '')));
                // Show up to 5 skill inputs
                for ($i = 0; $i < 5; $i++) {
                  $value = $existing_skills[$i] ?? '';
                  echo '<label>Skill ' . ($i + 1) . ':</label>';
                  echo '<input type="text" name="skill[]" class="form-control mb-2" value="' . htmlspecialchars($value) . '">';
                }
                ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Summary</label>
                <textarea class="form-control" name="summary"
                  rows="3"><?php echo htmlspecialchars($applicant['summary']); ?></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="save_skills" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Profile Upload
      const fileInput = document.getElementById('profile-upload');
      const uploadBtn = document.getElementById('upload-btn');
      const submitBtn = document.getElementById('submit-btn');
      const imgPreview = document.getElementById('profile-preview');

      uploadBtn.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = e => imgPreview.src = e.target.result;
          reader.readAsDataURL(file);
          submitBtn.click();
        }
      });
    </script>
</body>

</html>