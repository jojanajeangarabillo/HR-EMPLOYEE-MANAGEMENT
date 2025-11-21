<?php
// Start session and connect to database
session_start();
require 'admin/db.connect.php';

// Fetch system settings
$sql = "SELECT * FROM system_settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

// Fallback in case no record is found
$logo = $settings['logo'] ?? 'images/default-logo.png';
$system_name = $settings['system_name'] ?? 'Hospital';
$cover_image = $settings['cover_image'] ?? 'images/default-cover.jpg';
$about_text = $settings['about'] ?? 'Welcome to our organization!';
$email = $settings['email'] ?? '';
$contact = $settings['contact'] ?? '';


// Fetch Work With Us features
$workWithUsJson = $settings['work_with_us'] ?? '[]';
$workWithUsFeatures = json_decode($workWithUsJson, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($system_name); ?></title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
  :root {
    --primary:#1E3A8A;
    --primary-dark:#163273;
    --white:#fff;
    --font:'Poppins', sans-serif;
    --radius:8px;
  }
  body {font-family:'Poppins','Roboto',sans-serif; background:#f9fafc; margin:0; padding:0; color:#111827; }
  .top-bar { background-color: var(--primary); padding: 10px 20px; position: sticky; top: 0; z-index: 50; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
  .logo-left { display:flex; align-items:center; gap:15px; }
  .logo-left img { height:60px; width:60px; border-radius:50%; }
  .logo-left h1 { color:white; font-size:1.4rem; letter-spacing:2px; margin:0; }
  .menu-wrap { position:relative; }
  .hamburger-btn { background:none; border:none; color:white; font-size:1.4rem; cursor:pointer; padding:8px; }
  .dropdown { position:absolute; right:0; top:calc(100% + 10px); background:white; min-width:160px; border-radius:var(--radius); box-shadow:0 6px 18px rgba(15,23,42,0.12); transform: scale(0.95); opacity: 0; visibility: hidden; transition: 150ms ease; overflow:hidden; }
  .dropdown.open { transform: scale(1); opacity: 1; visibility: visible; }
  .dropdown a { display:block; padding:12px 14px; text-decoration:none; color:#0f172a; font-weight:600; transition:0.2s; }
  .dropdown a:hover { background:#f1f5f9; color:var(--primary); }
  .learn-section { text-align:center; padding:40px 20px; }
  .learn-section h2 { color: var(--primary-dark); margin-bottom:16px; }
  .learn-section p { font-size:1.1rem; margin-bottom:20px; line-height:1.6; }
  .btn-primary { background: var(--primary); border:none; padding:12px 24px; border-radius:8px; }
  .btn-primary:hover { background: var(--primary-dark); }
  .accordion { max-width:700px; margin:24px auto; }
  @media (max-width:768px) { .logo-left h1 { font-size:1rem; } }
  .cover-wrapper { position: relative; width: 100%; height: 420px; overflow: hidden; }
  .cover-blur { background-image: url('<?php echo $cover_image; ?>'); background-size: cover; background-position: center; width: 100%; height: 100%; position: absolute; top: 0; left: 0; transform: scale(1.2); }
  .cover-clear { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 2; }
  .footer {
  background-color: #1E3A8A; /* Same as --primary but slightly transparent */
  backdrop-filter: blur(8px); /* Adds the blur effect */
  -webkit-backdrop-filter: blur(8px); /* Safari support */
  color: white;
}
.footer a:hover {
  color: #d1d5db;
}



/* Overlay with primary shade */
.cover-overlay {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  background-color: rgba(0, 47, 108, 0.6); /* color overlay to make text readable */
  z-index: 2;
}

/* Cover Text Container - impactful welcome text */
.cover-text-container {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3;
  padding: 30px 40px;
  border-radius: 16px;
  text-align: center;
  color: #fff;
  max-width: 750px;
  
  box-shadow: 0 8px 20px rgba(0,0,0,0.4);
  animation: fadeInUp 1s ease-out;
}

.cover-text-container h2 {
  font-size: 2.8rem;
  font-weight: 800;
  font-family: 'Poppins', sans-serif;
  color: #fff;
  text-shadow: 0 4px 12px rgba(0, 0, 0, 0.7);
  margin-bottom: 15px;
}

.cover-text-container p {
  font-size: 1.2rem;
  font-weight: 400;
  line-height: 1.6;
  margin-bottom: 20px;
}

/* About Us Container */
/* About / Learn Section */
.learn-section {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.08);
  padding: 50px 30px;
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}
.learn-section:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.15);
}
.learn-section h2 {
  font-size: 2.2rem;
  font-weight: 800;
  color: var(--primary-dark);
}
.learn-section p.lead {
  font-size: 1.15rem;
  line-height: 1.8;
  color: #374151;
}

/* Why Work With Us Features */
.why-work .feature-card {
  border-radius: 16px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  background: #f9fafc;
}
.why-work .feature-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}
.why-work .icon-wrap i {
  transition: transform 0.3s ease, color 0.3s ease;
}
.why-work .feature-card:hover .icon-wrap i {
  transform: scale(1.2);
  color: #2563eb; /* hover accent color */
}
.why-work .card-title {
  font-size: 1.25rem;
  margin-bottom: 0.75rem;
}
.why-work .card-text {
  font-size: 1rem;
  color: #4b5563;
}


/* Animations */
@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translate(-50%, 20%);
  }
  100% {
    opacity: 1;
    transform: translate(-50%, -50%);
  }
}

 .why-work .card-body {
    padding: 2rem 1.5rem;
  }
  .why-work .card-title {
    margin-bottom: 0.75rem;
    font-family: 'Poppins', sans-serif;
  }
  .why-work .card-text {
    font-size: 0.95rem;
    color: #4b5563; /* Gray text */
  }

  /* Hidden content for Learn More */
.learn-more-details {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: max-height 0.6s ease, opacity 0.6s ease;
}

/* When active */
.learn-more-details.active {
  max-height: 500px; /* adjust based on content */
  opacity: 1;
}

/* Make sure image doesn't move */
.fixed-img {
  max-height: 320px;
  object-fit: cover;
}

/* Ensure row columns align at top */
.row.align-items-start {
  align-items: flex-start;
}

/* Hidden content for Learn More */
.learn-more-details {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: max-height 0.6s ease, opacity 0.6s ease, padding 0.6s ease;
  background: #f3f4f6; /* light gray */
  border-radius: 12px;
  padding: 0 20px; /* no padding until expanded */
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* Active state */
.learn-more-details.active {
  max-height: 1000px; /* adjust if needed */
  opacity: 1;
  padding: 20px;
}

/* List styling */
.learn-more-details ol {
  padding-left: 20px;
  margin: 15px 0;
  color: #374151;
  font-size: 1rem;
  line-height: 1.6;
}

/* Optional: fade+slide animation for each li */
.learn-more-details li {
  opacity: 0;
  transform: translateX(-20px);
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.learn-more-details.active li {
  opacity: 1;
  transform: translateX(0);
}

/* Ensure hidden list is left-aligned */
.about-text-wrapper .learn-more-details {
  text-align: left;       /* left-align text */
}

.about-text-wrapper .learn-more-details ol {
  list-style-position: inside;  /* numbers inside the container */
  padding-left: 20px;           /* spacing from the left */
  margin: 0;
  color: #374151;
  font-size: 1rem;
  line-height: 1.6;
}

.about-text-wrapper .learn-more-details li {
  opacity: 0;
  transform: translateX(-20px);
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.about-text-wrapper .learn-more-details.active li {
  opacity: 1;
  transform: translateX(0);
}


</style>
</head>
<body>

<!-- HEADER -->
<header class="top-bar">
  <div class="logo-left">
    <img src="<?php echo $logo; ?>" alt="Logo">
    <h1><?php echo htmlspecialchars($system_name); ?></h1>
  </div>

  <div class="menu-wrap">
    <button id="hamburger" class="hamburger-btn" aria-expanded="false">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav id="primary-menu" class="dropdown">
      <a href="Login.php">Log In</a>
      <a href="Applicant_Registration.php">Sign Up</a>
    </nav>
  </div>
</header>

<!-- COVER IMAGE WITH OVERLAY AND TEXT -->
<div class="cover-wrapper">
  <div class="cover-blur"></div>
  <div class="cover-overlay"></div>

  <!-- Text container -->
  <div class="cover-text-container">
    <h2>Welcome to <?php echo htmlspecialchars($system_name); ?></h2>
    
  </div>
</div>
<section class="learn-section container mt-5" id="learn-more">
  <div class="row align-items-start g-4">
    <!-- About Text + Hidden Details -->
    <div class="col-md-6">
      <div class="about-text-wrapper">
        <h2>About Us</h2>
        <p class="lead"><?php echo htmlspecialchars($about_text); ?></p>
        <button class="btn btn-primary mt-3" id="learnMoreBtn">How to create an Account?</button>

        <!-- Hidden details container -->
        <div id="learnMoreContent" class="learn-more-details mt-3">
          <ol>
            <li>Click <strong>Menu Button</strong> at the top-right corner.</li>
            <li>Click <strong>Sign Up</strong>.</li>
            <li>Register using your <strong>Email Address</strong>.</li>
            <li>Check your Email for a <strong>Temporary Password</strong>.</li>
            <li><strong>Log in </strong> using the Temporary Password.</li>
            <li>Set a <strong>New Password</strong>.</li>
            <li>Log in again using the <strong>New Password</strong> and <strong>Registered Email</strong>.</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Image Column -->
    <div class="col-md-6 text-center">
      <img src="<?php echo $cover_image; ?>" alt="About Image" class="img-fluid rounded shadow fixed-img">
    </div>
  </div>
</section>


<!-- WHY WORK WITH US -->
<section class="why-work container py-5">
  <div class="text-center mb-5">
    <h2 style="color:#1E3A8A; font-weight:800; font-size:2.2rem;">Why Work With Us</h2>
    <p class="lead" style="max-width:700px; margin:auto;">
      Join our team and experience a supportive, innovative, and rewarding work environment. At <?php echo htmlspecialchars($system_name); ?>, we value our employees and empower them to grow in their careers.
    </p>
  </div>
  
  <div class="row g-4">
    <?php foreach($workWithUsFeatures as $feature): ?>
      <div class="col-md-4">
        <div class="card h-100 shadow-lg border-0 feature-card text-center p-3">
          <div class="icon-wrap mb-3">
            <i class="fa-solid <?= htmlspecialchars($feature['icon'] ?? 'fa-circle-info') ?> fa-3x" style="color:#1E3A8A;"></i>
          </div>
          <div class="card-body">
            <h5 class="card-title fw-bold"><?= htmlspecialchars($feature['title'] ?? 'Feature') ?></h5>
            <p class="card-text"><?= htmlspecialchars($feature['description'] ?? '') ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>




<!-- FOOTER -->
<footer class="footer mt-5 py-4">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
    <div class="mb-2 mb-md-0">
      &copy; 2025 <?php echo htmlspecialchars($system_name); ?>. All rights reserved.
    </div>
    <div class="footer-links">
      <a href="#" class="text-white text-decoration-none">Contact: <?php echo htmlspecialchars($email); ?> | <?php echo htmlspecialchars($contact); ?></a>
    </div>
  </div>
</footer>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const hamburgerBtn = document.getElementById('hamburger');
  const menu = document.getElementById('primary-menu');

  hamburgerBtn.addEventListener('click', () => {
    menu.classList.toggle('open');
    const expanded = hamburgerBtn.getAttribute('aria-expanded') === 'true';
    hamburgerBtn.setAttribute('aria-expanded', !expanded);
  });

  document.addEventListener('click', (e) => {
    if(!menu.contains(e.target) && !hamburgerBtn.contains(e.target)){
      menu.classList.remove('open');
      hamburgerBtn.setAttribute('aria-expanded','false');
    }
  });

  const learnBtn = document.getElementById('learnMoreBtn');
const learnContent = document.getElementById('learnMoreContent');

learnBtn.addEventListener('click', () => {
  learnContent.classList.toggle('active');

  // Optional: Change button text
  if(learnContent.classList.contains('active')){
    learnBtn.textContent = "Show Less";
  } else {
    learnBtn.textContent = "Learn More";
  }
});
</script>

</body>
</html>
