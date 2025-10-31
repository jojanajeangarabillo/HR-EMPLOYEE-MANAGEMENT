<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
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
  
    /* Welcome Section */
    .welcome-card {
      background-color: #6674cc;
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
    }

    .welcome-card h3 {
      font-size: 22px;
      font-weight: bold;
    }

    .welcome-card p {
      font-size: 14px;
      opacity: 0.9;
    }

    /* Card Container */
    .card-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 40px;
    }

    /* Card Style */
    .card {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      background-color: #f3f3f9;
      border-radius: 10px;
      padding: 20px;
      width: 500px;
      height: 100px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card i {
      font-size: 35px;
      margin-right: 20px;
      color:#1E3A8A;
    }

    .info h2 {
      font-size: 18px;
      font-weight: bold;
      margin: 0;
    }

    .info p {
      font-size: 20px;
      font-weight: bold;
      margin: 0;
    }

    /* Border Colors */
    .salary { border-left: 5px solid #3b82f6; }
    .attendance { border-left: 5px solid #ec4899; }
    .requests { border-left: 5px solid #dc2626; }
    .announcements { border-left: 5px solid #3b82f6; }

    /* Announcements Section */
    .announcement-section {
      margin-top: 30px;
    }

    .announcement-section h1 {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }

    .announcement-section h1 i {
      color: #1E3A8A;
      margin-right: 10px;
    }

    .announcement-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #6674cc;
      color: white;
      border-radius: 10px;
      overflow: hidden;
    }

    .announcement-table th, .announcement-table td {
      padding: 15px;
      text-align: left;
    }

    .announcement-table th {
      background-color: #4c5ecf;
      font-weight: bold;
    }

    .announcement-table td {
      background-color: #6674cc;
    }

    .announcement-table button {
      background-color: #1E3A8A;
      border: none;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
    }

    .announcement-table button:hover {
      background-color: #142b66;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
   <div class="sidebar">
     <div class="sidebar-logo">
      <h1>Welcome</h1>
      <img src="Images/profile.png" alt="Hospital Logo">
    </div>

  <ul class="nav">
    <li style="display: flex; justify-content: center;">
  <a href="Employee_Profile.php" style="text-align: center; width: 100%; margin-right: 20px; display: block;">My Profile</a>
</li>
    <h4 class="menu-board-title">Menu Board </h4>
    <li class="active"><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
    <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> Salary Slip</a></li>
    <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
    <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
  </ul>
</div>
  <!-- Main Content -->
  <main class="main-content">

    <div class="welcome-card">
      <h3>Welcome to Employee Dashboard</h3>
      <p>Here's a quick summary of your payslip, requests, attendance record, and recent announcements.</p>
    </div>

    <div class="card-container">
      <div class="card salary">
        <i class="fa-solid fa-folder"></i>
        <div class="info">
          <h2>Salary Slip</h2>
          <p>20</p>
        </div>
      </div>

      <div class="card attendance">
        <i class="fa-solid fa-chart-column"></i>
        <div class="info">
          <h2>Attendance</h2>
          <p>20</p>
        </div>
      </div>

      <div class="card requests">
        <i class="fa-solid fa-code-branch"></i>
        <div class="info">
          <h2>Requests</h2>
          <p>5</p>
        </div>
      </div>

      <div class="card announcements">
        <i class="fa-solid fa-comment"></i>
        <div class="info">
          <h2>Announcements</h2>
          <p>5</p>
        </div>
      </div>
    </div>

    <!-- Announcements Section at Bottom -->
    <div class="announcement-section">
      <h1 style="color: black;"><i class="fa-solid fa-comment"></i> Announcements</h1>

      <table class="announcement-table">
        <tr>
          <th>Position</th>
          <th>Date Posted</th>
          <th>Action</th>
        </tr>
        <tr>
          <td>New Uniform Policy</td>
          <td>October 10, 2025</td>
          <td><button>View</button></td>
        </tr>
      </table>
    </div>
  </main>
  <script>
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
