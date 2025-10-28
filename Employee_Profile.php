<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Profile</title>
  <link rel="stylesheet" href="employee-sidebar.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <style>


  

/* Heading with Icon */
.heading-container {
  display: flex;
  align-items: center;
  gap: 12px;
}
.main-heading {
  font-size: 2rem;
  font-weight: 700;
  color: #25306d;
  margin: 0;
}
.heading-icon {
  width: 28px;
  height: 28px;
}
.main-heading-line {
  border: 0;
  height: 2px;
  background: #224288;
  width: 100%;
  margin: 15px 0 30px 0;
}

/* Profile Header */
.profile-header {
  display: flex;
  align-items: flex-start;
  gap: 32px;
  margin-bottom: 25px;
}
.profile-photo-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.profile-header img {
  border-radius: 50%;
  border: 2px solid #224288;
  width: 110px;
  height: 110px;
  object-fit: cover;
  background: #fff;
  margin: 0;
}
.upload-btn {
  padding: 6px 18px;
  background: #274ea0;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1em;
  font-weight: 500;
  transition: background 0.2s;
}
.upload-btn:hover {
  background: #193568;
}
input[type="file"] { display: none; }

/* Profile Info */
.profile-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.employee-name {
  font-size: 1.9em;
  font-weight: 700;
  color: #25306d;
}
.profile-info span {
  font-size: 1em;
  color: #2c2c2c;
  line-height: 1.5;
}
.profile-info .label { font-weight: 600; color: #25306d; }

/* Sections */
section {
  background: #ffffff;
  padding: 25px 30px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
section h2 {
  text-align: center; 
  font-size: 30px;
  font-weight: 700;
  color:black; 
  margin-bottom: 20px;
}

/* Info Flex */
.info-flex {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
}
.info-block {
  min-width: 240px;
  font-size: 0.95em;
  color: #2c2c2c;
}
.info-block strong { font-weight: 600; color: #25306d; }

/* Government IDs */
.gov-ids-flex {
  display: flex;
  flex-wrap: wrap;
  gap: 50px;
}
.gov-id-block {
  min-width: 150px;
  font-size: 0.95em;
  color: #2c2c2c;
}
.gov-id-block strong { font-weight: 700; color: #25306d; }

/* Responsive */
@media (max-width: 900px) {
  .main-content { margin-left: 0; padding: 20px; width: 100%; }
  .profile-header { flex-direction: column; gap: 14px; align-items: center; text-align: center; }
  .profile-info span { text-align: center; }
  .info-flex, .gov-ids-flex { flex-direction: column; gap: 15px; }
  section { padding: 15px 12px; }
}

  </style>
</head>

<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h1>Welcome</h1>
      <img src="images/profile.png" alt="Profile" width="80" height="80">

    <ul class="sidebar-menu">
        <li><a href="Employee_Profile.php" class="active" style="display: block; text-align: center; padding-right: 75px;">My Profile</a></li>
        <li class="menu-title">Menu Board</li>
        <li><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
        <li><a href="Employee_SalarySlip.php"><i class="fa-solid fa-user-group"></i> Salary Slip</a></li>
        <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
        <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
    </aside>

<!-- Main Content -->
 <main class="main-content">
  <div class="heading-container">
    <h1 class="main-heading">Profile<i class="fa-solid fa-circle-user"></i></h1>
  </div>
  <hr class="main-heading-line">

  <!-- Profile Header -->
  <div class="profile-header">
    <div class="profile-photo-upload">
      <img id="profile-preview" src="image.jpg" alt="Profile">
      <label for="profile-upload" class="upload-btn">Upload Profile</label>
      <input type="file" id="profile-upload" accept="image/*">
    </div>

    <div class="profile-info">
      <div class="employee-name">RIVER FUENTABELLA</div>
      <span>Position: <span class="label">Cardiologist Doctor</span></span>
      <span>Department: <span class="label">Medical</span></span>
      <span>Employment Status: <span class="label">Regular</span></span>
      <span>Employee ID: <span class="label">25-0001</span></span>
    </div>
  </div>

  <!-- Personal Information Section -->
  <section>
    <h2>Personal Information</h2>
    <div class="info-flex">
      <div class="info-block">
        <strong>FULL NAME</strong><br>RIVER FUENTABELLA<br>
        <strong>CONTACT NUMBER</strong><br>09981654387<br>
        <strong>EMERGENCY CONTACT NUMBER</strong><br>0994856718
      </div>
      <div class="info-block">
        <strong>DATE OF BIRTH</strong><br>MARCH 5, 1990<br>
        <strong>GENDER</strong><br>MALE<br>
        <strong>EMAIL ADDRESS</strong><br>funtabella_river@gmail.com
      </div>
      <div class="info-block" style="flex:2;">
        <strong>HOME ADDRESS</strong><br>
        123 Mabini Street, Barangay Kapitolyo, Pasig City, Metro Manila, 1603
      </div>
    </div>
  </section>

  <!-- Government IDs Section -->
  <section>
    <h2>Government Identification Numbers</h2>
    <div class="gov-ids-flex">
      <div class="gov-id-block">
        <strong>PAG-IBIG</strong><br>1234-5678-9012
      </div>
      <div class="gov-id-block">
        <strong>PHILHEALTH</strong><br>12-345678901-2
      </div>
      <div class="gov-id-block">
        <strong>SSS</strong><br>09-1234567-8
      </div>
      <div class="gov-id-block">
        <strong>TIN</strong><br>123-456-789-000
      </div>
    </div>
  </section>
</main>

<script>
  // Profile Upload
  const fileInput = document.getElementById('profile-upload');
  const imgPreview = document.getElementById('profile-preview');
  fileInput.addEventListener('change', function(e){
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(evt) {
        imgPreview.src = evt.target.result;
      }
      reader.readAsDataURL(file);
    }
  });
</script>


</body>
</html>
