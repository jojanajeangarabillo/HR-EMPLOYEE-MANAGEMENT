<?php
session_start();
require 'admin/db.connect.php';

$employees = 0;
$applicants = 0;

// Flash messages (pull into local variables and clear session keys)
$flash_success = '';
$flash_error = '';
if (isset($_SESSION['flash_success'])) {
  $flash_success = $_SESSION['flash_success'];
  unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
  $flash_error = $_SESSION['flash_error'];
  unset($_SESSION['flash_error']);
}

// Applied-success modal data (job title) pulled from session and cleared
$apply_success_job = '';
if (isset($_SESSION['apply_success']) && !empty($_SESSION['apply_success'])) {
  $apply_success_job = $_SESSION['apply_success'];
  unset($_SESSION['apply_success']);
}

$applicantnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Applicant'");
if ($applicantnameQuery && $row = $applicantnameQuery->fetch_assoc()) {
  $applicantname = $row['fullname'];
}

// Search query (GET param 'q') - used to filter job postings
$search = trim($_GET['q'] ?? '');

// Fetch job postings with department and employment type names (optionally filtered by search)
$jobs = [];

// base SQL (no ORDER BY yet) — removed qualification field
$job_sql_base = "SELECT jp.jobID, jp.job_title, jp.job_description, COALESCE(d.deptName,'') AS department_name, jp.educational_level, jp.skills, jp.expected_salary, jp.experience_years, COALESCE(et.typeName,'') AS employment_type, jp.location, jp.vacancies, jp.date_posted, jp.closing_date FROM job_posting jp LEFT JOIN department d ON jp.department = d.deptID LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID";

if ($search !== '') {
  // filter by title, description, department name or skills
  $job_sql = $job_sql_base . " WHERE (jp.job_title LIKE ? OR jp.job_description LIKE ? OR d.deptName LIKE ? OR jp.skills LIKE ?) ORDER BY jp.date_posted DESC";
  $job_stmt = $conn->prepare($job_sql);
  if ($job_stmt) {
    $like = "%" . $search . "%";
    // bind as strings (title, description, department, skills)
    $job_stmt->bind_param('ssss', $like, $like, $like, $like);
    $job_stmt->execute();
    $jres = $job_stmt->get_result();
    while ($j = $jres->fetch_assoc()) {
      $jobs[] = $j;
    }
    $job_stmt->close();
  }
} else {
  $job_sql = $job_sql_base . " ORDER BY jp.date_posted DESC";
  $job_stmt = $conn->prepare($job_sql);
  if ($job_stmt) {
    $job_stmt->execute();
    $jres = $job_stmt->get_result();
    while ($j = $jres->fetch_assoc()) {
      $jobs[] = $j;
    }
    $job_stmt->close();
  }
}

// Fetch applied jobIDs for current applicant (if any)
$applied_job_ids = [];
if (isset($_SESSION['applicantID']) && !empty($_SESSION['applicantID'])) {
  $aid = $_SESSION['applicantID'];
  $app_q = $conn->prepare("SELECT jobID FROM applications WHERE applicantID = ?");
  if ($app_q) {
    $app_q->bind_param('s', $aid);
    $app_q->execute();
    $ares = $app_q->get_result();
    while ($ar = $ares->fetch_assoc()) {
      $applied_job_ids[] = (int) $ar['jobID'];
    }
    $app_q->close();
  }
}

// Handle apply action: when an applicant clicks Apply, insert an application row and set applicant.status = 'Pending'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
  // Ensure user is an applicant and session contains applicantID
  if (!isset($_SESSION['applicantID']) || empty($_SESSION['applicantID'])) {
    // Not logged in as applicant — redirect to login
    header('Location: Login.php');
    exit();
  }

  // applicantID is stored as a string in the DB (e.g. 'HOS-001') so keep it as string
  $applicantID = isset($_SESSION['applicantID']) ? $_SESSION['applicantID'] : '';
  $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

  if ($job_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid job selected.';
    header('Location: Applicant_Jobs.php');
    exit();
  }

  // Server-side duplicate check: has this applicant already applied for this job?
  $check = $conn->prepare("SELECT id FROM applications WHERE applicantID = ? AND jobID = ? LIMIT 1");
  if ($check) {
    // applicantID is a string in the DB, jobID is integer
    $check->bind_param('si', $applicantID, $job_id);
    $check->execute();
    $cres = $check->get_result();
    if ($cres && $cres->fetch_assoc()) {
      // Already applied
      $_SESSION['flash_error'] = 'You have already applied to this job.';
      $check->close();
      header('Location: Applicant_Jobs.php');
      exit();
    }
    $check->close();
  }

  // Insert application record
  $insert = $conn->prepare("INSERT INTO applications (applicantID, jobID, status) VALUES (?, ?, ?)");
  if ($insert) {
    $app_status = 'Pending';
    // applicantID = string, jobID = int, status = string
    $insert->bind_param('sis', $applicantID, $job_id, $app_status);
    if ($insert->execute()) {
      $insert->close();

      // Fetch job title for success modal message
      $jt = $conn->prepare("SELECT job_title FROM job_posting WHERE jobID = ? LIMIT 1");
      if ($jt) {
        $jt->bind_param('i', $job_id);
        $jt->execute();
        $jres = $jt->get_result();
        $job_title_for_modal = '';
        if ($jres && $rj = $jres->fetch_assoc()) {
          $job_title_for_modal = $rj['job_title'] ?? '';
        }
        $jt->close();
        if ($job_title_for_modal !== '') {
          $_SESSION['apply_success'] = $job_title_for_modal;
        }
      }

      // Update applicant.status to Pending as well
      $upd = $conn->prepare("UPDATE applicant SET status = ? WHERE applicantID = ?");
      if ($upd) {
        $status_val = 'Pending';
        // both are strings in this schema
        $upd->bind_param('ss', $status_val, $applicantID);
        if ($upd->execute()) {
          // Do not set a flash success message here per request; keep silent on success
        } else {
          $_SESSION['flash_error'] = 'Application created but failed to update applicant status.';
        }
        $upd->close();
      } else {
        $_SESSION['flash_error'] = 'Application created but server failed to update applicant status (prepare failed).';
      }
    } else {
      $_SESSION['flash_error'] = 'Failed to submit application.';
      $insert->close();
    }
  } else {
    $_SESSION['flash_error'] = 'Server error (could not prepare application insert).';
  }

  // Redirect to avoid form resubmission
  header('Location: Applicant_Jobs.php');
  exit();
}
?>







<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Listing</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap"
    rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="applicant.css">

  <!-- Internal CSS for Main Content -->
  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      background-color: #f9fafc;
      display: flex;
    }

    .main-content {
      margin-left: 220px;
      padding: 24px 32px;
      width: calc(100% - 240px);
    }

    h1 {
      font-size: 24px;
      font-weight: 600;
      color: #1E3A8A;
      gap: 25px;
      margin-bottom: 10px;
      white-space: nowrap;
      display: inline-block;
    }

    hr {
      border: none;
      height: 1px;
      background-color: #ccc;
      margin-bottom: 20px;
    }

    /* Search bar */
    .search-bar {
      position: absolute;
      top: 25px;
      right: 40px;
      background-color: #f3f0fa;
      border-radius: 20px;
      padding: 8px 15px;
      display: flex;
      align-items: center;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .search-bar input {
      border: none;
      background: transparent;
      outline: none;
      padding: 5px;
      font-family: 'Poppins';
    }

    .search-bar i {
      color: #4a4a4a;
      margin-left: 8px;
      cursor: pointer;
    }

    /* Job Listing Layout */
    .job-container {
      display: grid;
      grid-template-columns: 420px 1fr;
      /* wider left column to fit larger cards */
      margin-top: 90px;
      gap: 48px;
      /* larger gap between list and details */
      align-items: start;
      align-content: start;
      width: 100%;
      box-sizing: border-box;
    }

    /* Left: Job Titles */
    .job-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
      max-width: 420px;
      padding-right: 8px;
      width: 100%;
      align-items: center;
    }

    .job-card {
      background-color: #2563EB;
      color: #fff;
      padding: 22px 24px;
      border-radius: 14px;
      min-height: 110px;
      /* bigger card height */
      width: 92%;
      margin: 0 auto;
      /* center the card inside the list column */
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(16, 24, 40, 0.06);
      cursor: pointer;
      transition: transform 0.16s, box-shadow 0.16s;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
    }

    .job-card:hover {
      transform: translateY(-3px);
    }

    .job-card.active-card {
      outline: 3px solid rgba(37, 99, 235, 0.12);
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.12);
      transform: translateY(-4px);
    }

    /* Right: Job Details */
    .job-details {
      background-color: #eaf2ff;
      border-radius: 12px;
      padding: 28px 30px;
      /* slightly larger padding for spacing */
      color: #0f172a;
      font-family: 'Roboto';
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.06);
      line-height: 1.6;
      min-height: 220px;
    }

    .job-details h2 {
      font-size: 20px;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .department-info {
      background-color: #c7d8f7;
      border-radius: 10px;
      padding: 10px 15px;
      margin-bottom: 20px;
      font-size: 15px;
    }

    .department-info strong {
      color: #142c74;
    }

    .job-info {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 20px;
    }

    .job-info div {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 15px;
    }

    .job-info i {
      width: 18px;
      color: #1E3A8A;
    }

    .apply-btn {
      background-color: #1E3A8A;
      color: #fff;
      border: none;
      padding: 8px 22px;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      margin: 10px 0 20px 0;
      transition: background-color 0.2s ease;
    }

    .apply-btn:hover {
      background-color: #142c74;
    }

    .job-description h3 {
      font-size: 17px;
      margin-bottom: 8px;
    }

    .job-description p {
      margin: 0;
      color: #1f2937;
      font-size: 15px;
    }

    /* Simple modal for apply confirmation */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.45);
      justify-content: center;
      align-items: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: #fff;
      padding: 20px 22px;
      border-radius: 10px;
      width: 420px;
      max-width: 94%;
      box-shadow: 0 8px 30px rgba(2, 6, 23, 0.2);
      text-align: left;
    }

    .modal-content h3 {
      margin: 0 0 10px 0;
      font-size: 18px;
    }

    .modal-actions {
      display: flex;
      gap: 8px;
      /* cancel left, confirm right */
      justify-content: center;
      margin-top: 12px;
      align-items: center;
    }

    .modal-actions .left {
      display: flex;
      gap: 8px;
      margin-right: auto;
      align-items: center;
    }

    .modal-actions .right {
      display: flex;
      gap: 8px;
    }

    .cancel-btn {
      background: #efefef;
      color: #111827;
      padding: 8px 14px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
    }

    .apply-btn {
      padding: 8px 18px;
      min-width: 120px;
    }

    .flash-success {
      background: #d1fae5;
      color: #065f46;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 12px;
      /* match Applicant_Profile layout */
      margin-left: 200px;
      max-width: 1200px;
      box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
    }

    .flash-error {
      background: #fee2e2;
      color: #991b1b;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 12px;
      /* match Applicant_Profile layout */
      margin-left: 200px;
      max-width: 1200px;
      box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
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

    /* Responsive: stack columns on narrow screens */
    @media (max-width: 900px) {
      .job-container {
        grid-template-columns: 1fr;
        margin-top: 20px;
      }

      .job-list {
        max-width: 100%;
        order: 2;
      }

      .job-details {
        order: 1;
      }

      .main-content {
        padding: 16px;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
      <i class="fa-solid fa-user"></i>
    </a>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $applicantname"; ?></p>
    </div>

    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li class="active"><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Applicant_Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h1>Job Listing</h1>
    <hr>

    <?php if (!empty($flash_success)): ?>
      <div class="flash-success">
        <?php echo htmlspecialchars($flash_success); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="flash-error">
        <?php echo htmlspecialchars($flash_error); ?>
      </div>
    <?php endif; ?>

    <div class="search-bar">
      <input type="text" placeholder="Search Jobs">
      <i class="fa-solid fa-magnifying-glass"></i>
    </div>

    <div class="job-container">
      <!-- Left Section -->
      <div class="job-list">
        <?php if (!empty($jobs)): ?>
          <?php foreach ($jobs as $index => $job): ?>
            <div class="job-card" data-index="<?php echo (int) $index; ?>">
              <?php echo htmlspecialchars($job['job_title']); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:20px;background:#fff;border-radius:10px;">No job postings available at the moment.</div>
        <?php endif; ?>
      </div>

      <!-- Right Section -->
      <div class="job-details" id="jobDetails">
        <?php if (!empty($jobs)):
          $first = $jobs[0]; ?>
          <h2 id="detail_title"><?php echo htmlspecialchars($first['job_title']); ?></h2>

          <div class="job-info" id="detail_info">
            <div><i class="fa-solid fa-location-dot"></i><strong>Location:</strong> <span
                id="detail_location"><?php echo htmlspecialchars($first['location']); ?></span></div>
            <div><i class="fa-solid fa-building"></i><strong>Department:</strong> <span
                id="detail_department"><?php echo htmlspecialchars($first['department_name']); ?></span></div>
            <div><i class="fa-solid fa-money-bill-wave"></i><strong>Expected Salary:</strong> <span
                id="detail_salary"><?php echo htmlspecialchars($first['expected_salary']); ?></span></div>
            <div><i class="fa-solid fa-book"></i><strong>Educational Level:</strong> <span
                id="detail_education_level"><?php echo htmlspecialchars($first['educational_level']); ?></span></div>
            <div><i class="fa-solid fa-lightbulb"></i><strong>Skills:</strong> <span
                id="detail_skills"><?php echo htmlspecialchars($first['skills']); ?></span></div>
            <div><i class="fa-solid fa-clock"></i><strong>Experience in Years:</strong> <span
                id="detail_experience"><?php echo htmlspecialchars($first['experience_years']); ?></span></div>
            <div><i class="fa-solid fa-user-tie"></i><strong>Employment Type:</strong> <span
                id="detail_employment_type"><?php echo htmlspecialchars($first['employment_type']); ?></span></div>
            <div><i class="fa-solid fa-users"></i><strong>Vacancies:</strong> <span
                id="detail_vacancies"><?php echo htmlspecialchars($first['vacancies']); ?></span></div>
            <div><i class="fa-solid fa-calendar-day"></i><strong>Date Posted:</strong> <span
                id="detail_date_posted"><?php echo htmlspecialchars($first['date_posted']); ?></span></div>
            <div><i class="fa-solid fa-calendar-xmark"></i><strong>Closing Date:</strong> <span
                id="detail_closing_date"><?php echo htmlspecialchars($first['closing_date']); ?></span></div>
          </div>

          <!-- Open apply confirmation modal -->
          <button type="button" class="apply-btn" id="openApplyBtn" onclick="openApplyModal()">Apply</button>

          <div class="job-description">
            <h3>Job Description</h3>
            <p id="detail_description"><?php echo nl2br(htmlspecialchars($first['job_description'])); ?></p>
          </div>


        <?php else: ?>
          <h2>No jobs</h2>
          <div class="job-description">
            <p>There are no job postings right now.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Apply confirmation modal -->
  <div id="applyModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-content" role="document">
      <h3 id="apply_modal_title">Confirm application</h3>
      <p id="apply_modal_text" style="color:#374151;margin-top:8px;">Are you sure you want to apply for this job?
      </p>
      <form method="POST" id="applyForm" style="margin:0;padding:0;">
        <input type="hidden" name="apply_job" value="1">
        <input type="hidden" name="job_id" id="apply_job_id"
          value="<?php echo htmlspecialchars($jobs[0]['jobID'] ?? ''); ?>">
        <div class="modal-actions">
          <div class="left">
            <button type="button" class="cancel-btn" onclick="closeApplyModal()">Cancel</button>
          </div>
          <div class="right">
            <button type="submit" class="apply-btn" id="confirmApplyBtn">Confirm</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- Apply success modal (shown after successful application) -->
  <div id="applySuccessModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-content" role="document">
      <h3>Application submitted</h3>
      <p id="apply_success_text" style="color:#374151;margin-top:8px;"></p>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
        <button type="button" class="cancel-btn" onclick="closeApplySuccessModal()">Close</button>
        <a href="Applicant_Application.php" class="apply-btn"
          style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">View
          applications</a>
      </div>
    </div>
  </div>

  <script>
    // Make the PHP $jobs array available to JS
    const JOBS = <?php echo json_encode($jobs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    // Job IDs already applied by this applicant
    const APPLIED_JOB_IDS = <?php echo json_encode($applied_job_ids ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    // Add click handlers to job cards to update detail panel
    document.addEventListener('DOMContentLoaded', function () {
      const cards = document.querySelectorAll('.job-card');
      cards.forEach(card => {
        card.addEventListener('click', function () {
          const idx = parseInt(this.getAttribute('data-index'));
          const job = JOBS[idx];
          if (!job) return;

          // replace detail fields
          const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value ?? ''; };
          setText('detail_title', job.job_title);
          setText('detail_location', job.location);
          setText('detail_department', job.department_name);
          setText('detail_salary', job.expected_salary);
          setText('detail_education_level', job.educational_level);
          setText('detail_skills', job.skills);
          setText('detail_experience', job.experience_years);
          setText('detail_employment_type', job.employment_type);
          setText('detail_vacancies', job.vacancies);
          setText('detail_date_posted', job.date_posted);
          setText('detail_closing_date', job.closing_date);
          const desc = document.getElementById('detail_description');
          if (desc) desc.innerHTML = job.job_description ? job.job_description.replace(/\n/g, '<br>') : '';

          // update apply form hidden job id
          const applyInput = document.getElementById('apply_job_id');
          if (applyInput) applyInput.value = job.jobID || '';

          // active styling
          cards.forEach(c => c.classList.remove('active-card'));
          this.classList.add('active-card');
        });
      });
      // mark first card active
      const first = document.querySelector('.job-card[data-index="0"]');
      if (first) first.classList.add('active-card');
    });

    // Modal open/close helpers for apply modal
    function openApplyModal() {
      const modal = document.getElementById('applyModal');
      const applyInput = document.getElementById('apply_job_id');
      const titleEl = document.getElementById('apply_modal_title');
      const textEl = document.getElementById('apply_modal_text');
      // if no job selected, use first
      let job = null;
      try { job = JOBS[0] || null; } catch (e) { job = null; }
      // prefer currently selected job from hidden input
      if (applyInput && applyInput.value) {
        const id = applyInput.value;
        const found = JOBS.find(j => String(j.jobID) === String(id));
        if (found) job = found;
      }
      if (titleEl && job) titleEl.textContent = 'Apply for: ' + (job.job_title || '');
      if (textEl && job) textEl.textContent = 'You are about to apply for "' + (job.job_title || '') + '" in ' + (job.department_name || 'the selected department') + '. Click Confirm to submit your application.';

      // if user already applied to this job, show a different modal state
      const appliedIds = (typeof APPLIED_JOB_IDS !== 'undefined') ? APPLIED_JOB_IDS : [];
      const already = appliedIds.includes(Number(job.jobID));
      const confirmBtn = document.getElementById('confirmApplyBtn');
      if (already) {
        if (textEl) textEl.textContent = 'You have already applied to "' + (job.job_title || '') + '". You cannot apply again.';
        if (confirmBtn) confirmBtn.style.display = 'none';
      } else {
        if (confirmBtn) confirmBtn.style.display = '';
      }

      if (modal) modal.classList.add('active');
      if (modal) modal.setAttribute('aria-hidden', 'false');
    }

    function closeApplyModal() {
      const modal = document.getElementById('applyModal');
      if (modal) modal.classList.remove('active');
      if (modal) modal.setAttribute('aria-hidden', 'true');
    }

    // Success modal helpers
    function openApplySuccessModal(title) {
      const modal = document.getElementById('applySuccessModal');
      const textEl = document.getElementById('apply_success_text');
      if (textEl) textEl.textContent = 'You have successfully applied for "' + (title || '') + '".';
      if (modal) modal.classList.add('active');
      if (modal) modal.setAttribute('aria-hidden', 'false');
    }

    function closeApplySuccessModal() {
      const modal = document.getElementById('applySuccessModal');
      if (modal) modal.classList.remove('active');
      if (modal) modal.setAttribute('aria-hidden', 'true');
    }

    // Auto-open success modal if PHP set one
    document.addEventListener('DOMContentLoaded', function () {
      const successTitle = <?php echo json_encode($apply_success_job ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
      if (successTitle) {
        openApplySuccessModal(successTitle);
      }
    });
  </script>
</body>

</html>