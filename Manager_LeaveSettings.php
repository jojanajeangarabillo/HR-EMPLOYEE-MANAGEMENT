<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Settings</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <style>

    /* ========================= */
/* SIDEBAR LOGO */
/* ========================= */
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



    body {
      background-color: #f7f9fc;
      font-family: "Poppins", "Roboto", sans-serif;
    }

    .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column
        }

    .main-content h2 {
      font-size: 24px;
      color: #1E3A8A;
      font-weight: 700;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .leave-form-container {
      background-color: #E5E7EB;
      padding: 40px;
      border-radius: 10px;
      width: 500px;
      max-width: 90%;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .leave-form-container form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .leave-form-container label {
      font-weight: 600;
      color: #333;
    }

    .leave-form-container input {
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      outline: none;
      transition: 0.2s;
    }

    .leave-form-container input:focus {
      border-color: #1E3A8A;
    }

    .form-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
    }

    .cancel-btn {
      background-color: #b91c1c;
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.3s;
    }

    .cancel-btn:hover {
      background-color: #991b1b;
    }

    .post-btn {
      background-color: #15803d;
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.3s;
    }

    .post-btn:hover {
      background-color: #166534;
    }

    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      .leave-form-container {
        width: 100%;
      }
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
      <li><a href="#"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
      <li><a href="#"><i class="fa-solid fa-user"></i> Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-users"></i> Pending Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-clipboard-list"></i> Request</a></li>
      <li><a href="#"><i class="fa-solid fa-briefcase"></i> Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar-days"></i> Calendar</a></li>
      <li><a href="#"><i class="fa-solid fa-check-circle"></i> Approvals</a></li>
      <li class="active"><a href="#"><i class="fa-solid fa-gear"></i> Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i> Log out</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <h2><i class="fa-solid fa-gear"></i> Leave Settings</h2>

    <div class="leave-form-container">
      <form>
        <label for="purpose">Purpose:</label>
        <input type="text" id="purpose" name="purpose" required>

        <label for="duration">Duration:</label>
        <input type="text" id="duration" name="duration" required>

        <label for="employeeLimit">Employee Limit:</label>
        <input type="number" id="employeeLimit" name="employeeLimit" min="1" required>

        <label for="leaveDuration">Leave Duration:</label>
        <input type="text" id="leaveDuration" name="leaveDuration" required>

        <label for="createdBy">Created by:</label>
        <input type="text" id="createdBy" name="createdBy" required>

        <div class="form-buttons">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" class="post-btn">Post</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
