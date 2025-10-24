<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      height: 100vh;
      background-color: #1f3b83;
      color: white;
      position: fixed;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 20px;
    }

    h1 {
      font-family: 'Roboto', sans-serif;
      font-size: 35px;
    }

    /* Profile */
    .sidebar-menu .profile {
      display: flex;
      font-size: 20px;
      justify-content: center;
      font-family: 'Roboto', sans-serif;
      color: white;
      padding: 10px 0;
      margin: 10px 0;
      width: 85%;
      cursor: pointer;
      transition: background 0.1s, border-left 0.1s, color 0.1s;
    }

    .sidebar-menu .profile:hover {
      background-color: #142b66;
      border-left: 5px solid #ffffff;
      color: #ffffff;
    }

    .sidebar-menu .active,
    .sidebar-menu a.active:hover {
      background-color: #142b66;
      border-left: 5px solid #ffffff;
    }

    /* MAIN CONTENT */
    .main-content {
      margin-left: 250px; /* same as sidebar width */
      padding: 30px;
      width: calc(100% - 250px);
      background-color: #f7f9fc;
      min-height: 100vh;
    }

    .main-content h2 {
      font-size: 28px;
      color: #1f3b83;
    }

    .dashboard-header {
      background-color: #6d8ff0;
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .card {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      text-align: center;
      font-size: 18px;
    }

    .card i {
      font-size: 30px;
      margin-bottom: 10px;
      display: block;
    }

  </style>
</head>

<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <h1>Welcome</h1>
    <img src="images/hospital-logo.png" alt="Profile" width="80" height="80">

    <ul class="sidebar-menu">
      <li class="profile">My Profile</li>
      <li class="menu-title">Menu Board</li>
      <li><a href="#" class="active"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
      <li><a href="#"><i class="fa-solid fa-user-group"></i> Salary Slip</a></li>
      <li><a href="#"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <h2>Employee</h2>
    <div class="dashboard-header">
      <h3>Welcome to Employee Dashboard</h3>
      <p>Here’s a quick summary of your payslip, requests, attendance, and recent announcements.</p>
    </div>

    <div class="dashboard-cards">
      <div class="card"><i class="fa-solid fa-file-invoice-dollar"></i>Salary Slip<br><strong>20</strong></div>
      <div class="card"><i class="fa-solid fa-calendar-check"></i>Attendance<br><strong>20</strong></div>
      <div class="card"><i class="fa-solid fa-code-branch"></i>Requests<br><strong>5</strong></div>
      <div class="card"><i class="fa-solid fa-bullhorn"></i>Announcements<br><strong>5</strong></div>
    </div>
  </div>
</body>
</html>
