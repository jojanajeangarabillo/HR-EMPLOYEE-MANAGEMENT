<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Calendar</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <!-- External Sidebar CSS -->
  <link rel="stylesheet" href="manager-sidebar.css">

  <style>


/* SIDEBAR LOGO */
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


    /* MAIN PAGE LAYOUT */
    body {
      background-color: #F4F6F8;
      display: flex;
      font-family: "Poppins", sans-serif;
    }

    /* MAIN CONTENT AREA */
    .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column
        }

    .main-content h1 {
      color: #1E3A8A;
      font-weight: 700;
      margin-bottom: 25px;
    }

    /* TABLE DESIGN */
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    th, td {
      text-align: center;
      padding: 12px;
      border: 1px solid #ddd;
    }

    th {
      background-color: #1E3A8A;
      color: white;
      font-weight: 600;
    }

    td {
      color: #333;
    }

    tbody tr:hover {
      background-color: #F2F6FF;
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
      <li><a href="Manager-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Manager-Pending-Applicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
      <li><a href="Manager-Requests.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Manager-JobPost.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li class="active"><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager-Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <h1>Manager Calendar</h1>

    <table>
      <thead>
        <tr>
          <th>Employee ID</th>
          <th>Employee Name</th>
          <th>Department</th>
          <th>Request Type</th>
          <th>Reason</th>
          <th>Date</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>001</td>
          <td>Jojana Garabillo</td>
          <td>Gynecology</td>
          <td>Leave</td>
          <td>Vacation</td>
          <td>10/18/2025–10/25/2025</td>
          <td>Approved</td>
        </tr>
        <tr>
          <td>002</td>
          <td>Jhanna Jaroda</td>
          <td>Nursing</td>
          <td>Leave</td>
          <td>Sick</td>
          <td>11/18/2025–11/25/2025</td>
          <td>Approved</td>
        </tr>
        <tr>
          <td>003</td>
          <td>Angela Sison</td>
          <td>IT</td>
          <td>Leave</td>
          <td>Vacation</td>
          <td>09/02/2025–09/04/2025</td>
          <td>Rejected</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
