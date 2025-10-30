<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager - Job Posting</title>

  <!-- Manager Sidebar -->
  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', 'Roboto', sans-serif;
      background-color: #f1f5fc;
      display: flex;
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


    /* MAIN CONTENT */
    .job-postings-container {
      flex-grow: 1;
      margin-left: 220px;
      padding: 40px;
    }

    .job-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .job-header h2 {
      font-size: 26px;
      font-weight: bold;
      color: #1f3c88;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .add-job-btn {
      background-color: #1f3c88;
      color: white;
      padding: 10px 18px;
      border: none;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
      transition: 0.3s;
    }

    .add-job-btn:hover {
      background-color: #274ca7;
    }

    /* JOB TABLE */
    .job-table {
      width: 100%;
    }

    .job-table-header,
    .job-row {
      display: grid;
      grid-template-columns: 1.8fr 1.2fr 1.2fr 0.7fr 0.8fr 1fr;
      padding: 12px 15px;
      align-items: center;
    }

    .job-table-header {
      background-color: #1f3c88;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      margin-bottom: 10px;
    }

    .job-row {
      background-color: #2f5fca;
      color: white;
      margin-bottom: 8px;
      border-radius: 6px;
    }

    .job-row div {
      font-size: 14px;
    }

    .status.active {
      color: #bdfbbd;
      font-weight: bold;
    }

    .actions {
      display: flex;
      gap: 8px;
      justify-content: center;
    }

    .circle-btn {
      background: #40a6ff;
      border: none;
      padding: 6px;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      color: white;
      transition: 0.3s;
    }

    .circle-btn:hover {
      background: #1f8ae8;
    }

    .circle-btn.delete {
      background: #ff4b4b;
    }

    .circle-btn.delete:hover {
      background: #d93a3a;
    }

    .circle-btn i {
      font-size: 14px;
    }

    .job-icon {
      font-size: 28px;
      color: #1a3f9b;
    }

    /* MODAL */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      display: flex;
      justify-content: center;
      align-items: center;
      visibility: hidden;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal-overlay.show {
      visibility: visible;
      opacity: 1;
    }

    .modal-content {
      background: #1f57ff;
      padding: 30px;
      width: 70%;
      border-radius: 20px;
      color: white;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 5px;
    }

    .row {
      display: flex;
      gap: 20px;
    }

    .half {
      width: 50%;
    }

    .third {
      width: 33.33%;
    }

    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 20px;
    }

    .cancel-btn {
      background: #b30000;
      color: white;
      padding: 8px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .post-btn {
      background: #0b8f2e;
      color: white;
      padding: 8px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
      <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li class="active"><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <main class="job-postings-container">
    <div class="job-header">
      <h2><i class="fa-solid fa-briefcase job-icon"></i> Job Posting</h2>
      <button class="add-job-btn">+ Add New Job</button>
    </div>

    <div class="job-table">
      <div class="job-table-header">
        <div>Job Title</div>
        <div>Department</div>
        <div>Type</div>
        <div>Vacancies</div>
        <div>Status</div>
        <div>Actions</div>
      </div>

      <div class="job-row">
        <div>Staff Nurse</div>
        <div>Nursing</div>
        <div>Full-Time</div>
        <div>3</div>
        <div class="status active">Active</div>
        <div class="actions">
          <button class="circle-btn"><i class="fa-solid fa-eye"></i></button>
          <button class="circle-btn"><i class="fa-solid fa-pen"></i></button>
          <button class="circle-btn delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>

      <div class="job-row">
        <div>Radiologic Tech</div>
        <div>Radiology</div>
        <div>Full-Time</div>
        <div>3</div>
        <div class="status active">Active</div>
        <div class="actions">
          <button class="circle-btn"><i class="fa-solid fa-eye"></i></button>
          <button class="circle-btn"><i class="fa-solid fa-pen"></i></button>
          <button class="circle-btn delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>

      <div class="job-row">
        <div>Pharmacist</div>
        <div>Pharmacy</div>
        <div>Full-Time</div>
        <div>1</div>
        <div class="status active">Active</div>
        <div class="actions">
          <button class="circle-btn"><i class="fa-solid fa-eye"></i></button>
          <button class="circle-btn"><i class="fa-solid fa-pen"></i></button>
          <button class="circle-btn delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>
    </div>
  </main>

  <!-- ADD JOB MODAL -->
  <div id="jobModal" class="modal-overlay">
    <div class="modal-content">
      <form>
        <div class="form-group">
          <label>Job Title</label>
          <input type="text" />
        </div>

        <div class="row">
          <div class="form-group half">
            <label>Department</label>
            <input type="text" />
          </div>
          <div class="form-group half">
            <label>Job Type</label>
            <input type="text" />
          </div>
        </div>

        <div class="row">
          <div class="form-group half">
            <label>Qualifications</label>
            <input type="text" />
          </div>
          <div class="form-group half">
            <label>Vacancies</label>
            <input type="text" />
          </div>
        </div>

        <div class="row">
          <div class="form-group half">
            <label>Expected Salary</label>
            <input type="text" />
          </div>
          <div class="form-group half">
            <label>Experience in Years</label>
            <input type="text" />
          </div>
        </div>

        <div class="form-group">
          <label>Location</label>
          <input type="text" />
        </div>

        <div class="row">
          <div class="form-group third">
            <label>Posting Date</label>
            <input type="date" />
          </div>
          <div class="form-group third">
            <label>Closing Date</label>
            <input type="date" />
          </div>
          <div class="form-group third">
            <label>Employee Status</label>
            <input type="text" />
          </div>
        </div>

        <div class="form-group">
          <label>Job Description</label>
          <textarea rows="5"></textarea>
        </div>

        <div class="button-group">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" class="post-btn">Post</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function () {
      $(".add-job-btn").click(function () {
        $("#jobModal").addClass("show");
      });

      $(".cancel-btn").click(function () {
        $("#jobModal").removeClass("show");
      });
    });
  </script>
</body>

</html>
