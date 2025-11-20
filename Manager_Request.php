<?php
session_start();
require 'admin/db.connect.php';


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
    "Vacancies" => "Manager_Vacancies.php",
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
    "Vacancies" => "Manager_Vacancies.php",
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Requests</title>

  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }


    .main-content {
      margin-left: 220px;
      padding: 40px 30px;
      background-color: #f1f5fc;
      flex-grow: 1;
      box-sizing: border-box;
    }

    .request-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .request-header h1 {
      font-size: 2rem;
      color: #1E3A8A;
      margin: 0;
    }

    .show-filter-btn {
      padding: 8px 14px;
      font-size: 14px;
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .show-filter-btn:hover {
      background-color: #1e40af;
    }

    /* FILTER BOX */
    .filter-box {
      display: none;
      margin-bottom: 20px;
      padding: 20px;
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: flex-start;
    }

    .filter-box label {
      font-weight: 500;
    }

    .filter-box select {
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid #e0e0e0;
      font-size: 14px;
    }

    .filter-buttons {
      width: 100%;
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .filter-buttons button {
      padding: 8px 14px;
      font-size: 14px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
    }

    .apply-btn {
      background-color: #1E3A8A;
      color: white;
    }

    .apply-btn:hover {
      background-color: #1e40af;
    }

    .reset-btn {
      background-color: #e5e7eb;
      color: #111827;
    }

    .reset-btn:hover {
      background-color: #d1d5db;
    }

    /* TABLE */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
      padding: 20px 24px;
      text-align: center;
      border: 1px solid #e0e0e0;
      min-width: 150px;
    }

    thead {
      background-color: #1E3A8A;
      color: white;
      font-weight: 600;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    /* STATUS COLORS */
    .status-approved {
      color: #10b981;
      font-weight: 600;
    }

    .status-pending {
      color: #f59e0b;
      font-weight: 600;
    }

    .status-rejected {
      color: #ef4444;
      font-weight: 600;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
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

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="main-box" id="blur-content">
      <div class="main-header">
        <div class="request-title">
          <h2>Employee Request <i class="fa-solid fa-code-branch"></i></h2>
        </div>
        <button class="file-request-btn" id="open-modal"><i class="fa-solid fa-plus-circle"></i> File a Request</button>
      </div>
      <div class="request-table-container">
        <table class="request-table">
          <thead>
            <tr>
              <th>Request Type</th>
              <th>Reason</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Certification of Employment</td>
              <td>For Credit Card</td>
              <td>October 15, 2025</td>
              <td class="approved">Approved</td>
              <td><button class="view-btn">View</button></td>
            </tr>
            <tr>
              <td>Certification of Employment</td>
              <td>For Credit Card</td>
              <td>October 15, 2025</td>
              <td class="approved">Approved</td>
              <td><button class="view-btn">View</button></td>
            </tr>
            <tr>
              <td>Certification of Employment</td>
              <td>For Credit Card</td>
              <td>October 15, 2025</td>
              <td class="approved">Approved</td>
              <td><button class="view-btn">View</button></td>
            </tr>
            <tr>
              <td>Certification of Employment</td>
              <td>For Credit Card</td>
              <td>October 15, 2025</td>
              <td class="approved">Approved</td>
              <td><button class="view-btn">View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Request Form -->
    <div id="request-modal" class="modal-overlay">
      <div class="modal-form">
        <form>
          <div class="modal-header">
            <h2><i class="fa-solid fa-code-branch"></i> Employee Request</h2>
          </div>
          <div class="modal-content">
            <div class="modal-row">
              <div class="form-group">
                <label>Full Name:</label>
                <input type="text" placeholder="">
              </div>
              <div class="form-group">
                <label>Employee ID:</label>
                <input type="text" placeholder="">
              </div>
            </div>
            <div class="modal-row">
              <div class="form-group">
                <label>Department:</label>
                <input type="text" placeholder="">
              </div>
              <div class="form-group">
                <label>Position:</label>
                <input type="text" placeholder="">
              </div>
            </div>

            <div class="modal-row">
              <div class="form-group wide">
                <label>Type of Request</label>
                <select id="request-type" name="request-type">
                  <option>Leave Request<Sick,Vacation, Emergency, etc>
                  </option>
                  <option>Attendance<Under Time, Over Time>
                  </option>
                  <option>Resignation</option>
                  <option>Certification</option>
                  <option>Others</option>
                </select>
                <input type="text" id="other-type" placeholder="Please specify" style="display:none; margin-top:7px;">
              </div>

              <div class="form-group wide">
                <label>Email Address:</label>
                <input type="text" placeholder="">
              </div>
            </div>
            <div class="modal-row">
              <div class="form-group wide">
                <label>Reason:</label>
                <textarea placeholder="Enter your reason" rows="10"></textarea>
              </div>
            </div>


          </div>
          <div class="modal-footer">
            <button type="button" class="cancel-btn" id="close-modal">Cancel</button>
            <button type="submit" class="send-btn">Send</button>
          </div>
        </form>
      </div>
    </div>

    <style>
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
        background: rgba(28, 36, 80, 0.19);
        justify-content: center;
        align-items: center;
        overflow: auto;
      }

      .modal-overlay.active {
        display: flex;
      }

      .modal-form {
        background: #23439e;
        color: #fff;
        border-radius: 10px;
        padding: 40px 50px 30px 50px;
        box-shadow: 0 10px 40px rgba(30, 40, 120, 0.18);
        min-width: 420px;
        max-width: 540px;
        width: 100%;
        margin: 36px auto;
        position: relative;
        display: flex;
        flex-direction: column;
      }

      .modal-header h2 {
        font-size: 2rem;
        margin-bottom: 24px;
        font-weight: bold;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 14px;
        color: #fff;
      }

      .modal-content {
        width: 100%;
        margin-bottom: 18px;
      }

      .modal-row {
        display: flex;
        gap: 25px;
        margin-bottom: 16px;
      }

      .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
      }

      .form-group.wide {
        flex: 2;
      }

      .form-group label {
        font-size: 1rem;
        margin-bottom: 7px;
        color: #fff;
        font-weight: 500;
      }

      .form-group input[type="text"],
      .form-group input[type="email"],
      .form-group select,
      .form-group input[type="file"] {
        padding: 8px 12px;
        border: none;
        border-radius: 7px;
        font-size: 1rem;
        outline: none;
        margin-bottom: 4px;
        background: #f3f5fb;
        color: black;
      }

      .form-group input[type="file"] {
        background: #f3f5fb;
        color: black;
        cursor: pointer;
      }

      .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 18px;
        margin-top: 10px;
      }

      .cancel-btn,
      .send-btn {
        padding: 8px 22px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 14px;
        box-shadow: 0 1.5px 6px rgba(28, 140, 64, 0.07);
      }

      .cancel-btn {
        background: #e63939;
        color: white;
        transition: background 0.18s;
      }

      .cancel-btn:hover {
        background: #c52626;
      }

      .send-btn {
        background: #19bb4e;
        color: white;
        transition: background 0.19s;
      }

      .send-btn:hover {
        background: #0e9133;
      }

      /* Responsive for modal */
      @media (max-width: 700px) {
        .modal-form {
          max-width: 98vw;
          min-width: unset;
          padding: 25px 3vw;
        }

        .modal-row {
          flex-direction: column;
          gap: 10px;
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
        box-shadow: 0 1px 3px rgba(30, 70, 140, 0.04);
        margin-left: 24px;
      }

      .file-request-btn:hover {
        background: #2540a8;
        color: #fff;
      }

      .request-table-container {
        margin-top: 18px;
      }

      .request-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #f3f7ff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(82, 100, 180, 0.06);
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
        box-shadow: 0 1.5px 6px rgba(24, 161, 64, 0.08);
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
    requestType.addEventListener('change', function () {
      if (requestType.value === 'Others') {
        otherTypeInput.style.display = 'block';
      } else {
        otherTypeInput.style.display = 'none';
      }
    });
  </script>

</body>

</html>