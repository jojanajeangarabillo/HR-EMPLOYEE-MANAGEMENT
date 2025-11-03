<?php
session_start();
require 'admin/db.connect.php';

$employees = 0;
$applicants = 0;


$employeenameQuery = $conn->query("
    SELECT fullname 
    FROM user 
    WHERE role = 'Employee' AND (sub_role IS NULL OR sub_role != 'HR Manager')
");

if ($employeenameQuery && $row = $employeenameQuery->fetch_assoc()) {
    $employeename = $row['fullname'];
    echo $employeename; 
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Requests</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  
</head>

<body>

  <div class="sidebar">
    <div class="sidebar-logo">
       
        <a href="Employee_Profile.php">
            <img src="Images/profile.png" alt="Hospital Logo">
        </a>
        <div class="sidebar-name">
            <p><?php echo "Welcome, $employeename"; ?></p>
        </div>
    </div>

    <ul class="nav">
        <h4 class="menu-board-title">Menu Board</h4>
        <li ><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
        <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> Salary Slip</a></li>
        <li class="active"><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>
  
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
                <option>Leave Request<Sick,Vacation, Emergency, etc></option>
                <option>Attendance<Under Time, Over Time></option>
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
  flex-wrap: wrap;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
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
    requestType.addEventListener('change', function() {
  if (requestType.value === 'Others') {
    otherTypeInput.style.display = 'block';
  } else {
    otherTypeInput.style.display = 'none';
  }
});

// Highlight active sidebar link
const currentPage = window.location.pathname.split("/").pop();
document.querySelectorAll(".sidebar .nav li a").forEach(link => {
  if (link.getAttribute("href") === currentPage) {
    link.parentElement.classList.add("active");
  }
});

  </script>

</body>
</html>
