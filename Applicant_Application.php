<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Applications</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="applicant.css">

  <!-- Internal CSS for main content -->
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f9fbff;
      color: #111827;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 230px;
      padding: 50px 70px;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h2 {
      font-weight: 600;
      font-size: 22px;
      color: #1E3A8A;
    }

    .search-box {
      display: flex;
      align-items: center;
      background-color: #f0e9f7;
      border-radius: 30px;
      padding: 8px 14px;
      width: 250px;
    }

    .search-box input {
      border: none;
      outline: none;
      background: none;
      width: 100%;
      padding-left: 8px;
      font-size: 14px;
      color: #333;
    }

    .search-box i {
      color: #5b5b5b;
      font-size: 15px;
    }

    /* Application Cards */
    .applications-container {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .application-card {
      background-color: #2563EB;
      color: white;
      border-radius: 15px;
      padding: 25px 30px;
      height: 200px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
    }

    .application-card:hover {
      transform: translateY(-3px);
    }

    .application-card .job-title {
      font-size: 16px;
      font-weight: 500;
    }

    .application-card .status {
      font-size: 15px;
      font-weight: 500;
    }

    hr {
      border: none;
      border-top: 1px solid #ccc;
      margin-top: -10px;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="profile">
      <i class="fa-solid fa-user"></i>
    </div>

    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li class="active"><a href="Applicant_Applications.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Applicant_Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h2>My Applications</h2>
      <div class="search-box">
        <i class="fa-solid fa-bars"></i>
        <input type="text" placeholder="Search Application">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
    </div>

    <hr>

    <div class="applications-container">
      <div class="application-card">
        <span class="job-title">Consultant Anesthesiologist</span>
        <span class="status">Status</span>
      </div>
      <div class="application-card">
        <span class="job-title">Consultant Anesthesiologist</span>
        <span class="status">Status</span>
      </div>
      <div class="application-card">
        <span class="job-title">Consultant Anesthesiologist</span>
        <span class="status">Status</span>
      </div>
    </div>
  </div>
</body>
</html>
