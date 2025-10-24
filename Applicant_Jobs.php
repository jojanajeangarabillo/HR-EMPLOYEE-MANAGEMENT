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
      padding: 20px 40px;
      width: calc(100% - 230px);
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
      margin-top: 70px;
      grid-template-columns: 1fr 1.4fr;
      gap: 30px;
      align-items: start;
    }

    /* Left: Job Titles */
    .job-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .job-card {
      background-color: #2563EB;
      color: #fff;
      padding: 25px;
      border-radius: 15px;
      height: 200px;
      width: 400px;
      font-weight: 500;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: transform 0.2s;
    }

    .job-card:hover {
      transform: translateY(-3px);
    }

    /* Right: Job Details */
    .job-details {
      background-color: #e5edfb;
      border-radius: 15px;
      padding: 25px 30px;
      color: #1E3A8A;
      font-family: 'Roboto';
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      line-height: 1.6;
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
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
      <i class="fa-solid fa-user"></i>
    </a>

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

    <div class="search-bar">
      <input type="text" placeholder="Search Jobs">
      <i class="fa-solid fa-magnifying-glass"></i>
    </div>

    <div class="job-container">
      <!-- Left Section -->
      <div class="job-list">
        <div class="job-card">Consultant Anesthesiologist</div>
        <div class="job-card">Anesthesiology Resident / Registrar</div>
        <div class="job-card">Nurse Anesthetist</div>
      </div>

      <!-- Right Section -->
      <div class="job-details">
        <h2>Consultant Anesthesiologist</h2>

        <div class="job-info">
          <div><i class="fa-solid fa-location-dot"></i><strong>Location:</strong> Manila, Philippines</div>
          <div><i class="fa-solid fa-building"></i><strong>Department:</strong> Anesthetics Department</div>
          <div><i class="fa-solid fa-money-bill-wave"></i><strong>Expected Salary:</strong> ₱50,000 - ₱70,000/month
          </div>
          <div><i class="fa-solid fa-graduation-cap"></i><strong>Qualification:</strong> Doctor of Medicine / Nursing
            Degree</div>
          <div><i class="fa-solid fa-book"></i><strong>Educational Level:</strong> Graduate / Postgraduate</div>
          <div><i class="fa-solid fa-lightbulb"></i><strong>Skills:</strong> Anesthesia administration, critical care,
            teamwork</div>
          <div><i class="fa-solid fa-clock"></i><strong>Experience in Years:</strong> 2 - 5 years</div>
          <div><i class="fa-solid fa-user-tie"></i><strong>Employment Type:</strong> Full-Time</div>
          <div><i class="fa-solid fa-users"></i><strong>Vacancies:</strong> 4</div>
          <div><i class="fa-solid fa-calendar-day"></i><strong>Date Posted:</strong> October 20, 2025</div>
          <div><i class="fa-solid fa-calendar-xmark"></i><strong>Closing Date:</strong> November 15, 2025</div>
        </div>

        <button class="apply-btn">Apply</button>

        <div class="job-description">
          <h3>Job Description</h3>
          <p>The Anesthetics Department provides expert care in pain management and anesthesia during surgical
            procedures. We are looking for skilled medical professionals dedicated to patient safety and comfort.
            Successful candidates will collaborate closely with surgeons, nurses, and specialists to ensure optimal
            patient outcomes.</p>
        </div>
      </div>
    </div>
  </div>
</body>

</html>