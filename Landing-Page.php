<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Landing Page</title>

  <!-- Font Awesome for hamburger icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root{
      --accent:#1E3A8A;
      --accent-dark:#163273;
      --gold:#FFD700;
      --white:#fff;
      --bg:#f8fafc;
      --radius:8px;
    }

    body, h1, p {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: #111827;
    }

    /* Header bar */
    .top-bar {
      background-color: var(--accent);
      padding: 10px 20px;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .logo-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }

    .logo-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo-left img {
      height: 75px;
      width: 75px;
      padding: 10px;
      border-radius: 50%;
    }

    .logo-left h1 {
      padding: 5px;
      color: white; 
      background: transparent;
    }

    /* Hamburger button */
    .menu-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .hamburger-btn {
      background: transparent;
      border: none;
      color: var(--white);
      font-size: 1.4rem;
      padding: 8px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 6px;
    }

    .hamburger-btn:focus {
      outline: 2px solid rgba(255,255,255,0.25);
      outline-offset: 2px;
    }

    /* Dropdown menu */
    .dropdown {
      position: absolute;
      right: 0;
      top: calc(100% + 10px);
      background: var(--white);
      min-width: 160px;
      border-radius: var(--radius);
      box-shadow: 0 6px 18px rgba(15,23,42,0.12);
      transform-origin: top right;
      overflow: hidden;
      transition: transform 150ms ease, opacity 150ms ease, visibility 150ms;
      transform: scale(0.95);
      opacity: 0;
      visibility: hidden;
    }

    .dropdown.open {
      transform: scale(1);
      opacity: 1;
      visibility: visible;
    }

    .dropdown a {
      display: block;
      padding: 12px 14px;
      text-decoration: none;
      color: #0f172a;
      font-weight: 600;
      background: transparent;
      transition: background 120ms ease, color 120ms ease;
    }

    .dropdown a:hover,
    .dropdown a:focus {
      background: #f1f5f9;
      color: var(--accent);
      outline: none;
    }

    .dropdown a + a {
      border-top: 1px solid #eef2f7;
    }

    /* Main content */
    .main-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 64px 24px;
      margin: 20px;
      border-radius: 12px;
      gap: 24px;
    }

    .hero-text {
      flex: 1 1 60%;
    }

    .hero-text h2 {
      font-size: 8rem;
      color: #0b1220;
      margin-bottom: 18px;
      line-height: 1.03;
    }

    .hero-text p {
      font-size: 1.05rem;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .hero-text .btn {
      background-color: var(--accent);
      color: var(--white);
      padding: 12px 22px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
    }

    .hero-text .btn:hover {
      background-color: var(--accent-dark);
    }

    .hero-image {
      flex: 1 1 35%;
      text-align: center;
    }

    .hero-image i {
      font-size: 8rem;
      color: var(--accent);
    }

    /* Modal styles */
    .modal {
      display: none; 
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal.show {
      display: flex;
      animation: fadeIn 0.8s ease forwards;

    }

    .modal-content {
      background-color: #fff;
      padding: 30px 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      position: relative;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      font-size: 1rem;
      line-height: 1.6;
      text-align: left;
      margin: auto;
    }

    .modal-content h3 {
      margin-top: 0;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }

    .modal-content ol {
      padding-left: 20px;
      margin: 0;
    }

    .modal-content li {
      margin-bottom: 12px;
    }

    .close {
      color: #aaa;
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.2s;
    }

    .close:hover {
      color: #000;
    }
        @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideUp {
      from { transform: translateY(40px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* Small screens */
    @media (max-width: 768px) {
      .main-content {
        flex-direction: column;
        text-align: center;
        padding: 36px 16px;
      }
      .hero-text h2 {
        font-size: 2rem;
      }
      .hero-image i {
        font-size: 5.5rem;
      }
      .logo-left h1 {
        font-size: 1rem;
        letter-spacing: 2px;
      }
    }
    
  </style>
</head>
<body class="login-body">

  <!-- Header -->
  <header class="top-bar">
    <div class="logo-header">
      <div class="logo-left">
        <img src="Images/hospitallogo.png" alt="Hospital Logo" />
        <h1>H O S P I T A L</h1>
      </div>

      <!-- Hamburger menu wrapper -->
      <div class="menu-wrap">
        <button
          id="hamburger"
          class="hamburger-btn"
          aria-expanded="false"
          aria-controls="primary-menu"
          aria-label="Open menu"
        >
          <i class="fa-solid fa-bars" aria-hidden="true"></i>
          <span style="display:none">Menu</span>
        </button>

        <nav id="primary-menu" class="dropdown" role="menu" aria-labelledby="hamburger">
          <a href="Login.php" role="menuitem" tabindex="-1">Log In</a>
          <a href="Applicant_Registration.php" role="menuitem" tabindex="-1">Sign Up</a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content">
    <div class="hero-text">
      <h2>Welcome</h2>
      <p>Start your career journey with us. We value passion, growth, and teamwork — and we're excited to meet dedicated individuals like you.</p>
      <button id="learnBtn" class="btn">Learn More</button>
    </div>
  </main>

  <!-- Modal -->
  <div id="myModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h3>How to Access Your Applicant Account</h3>
      <ol>
        <li>Click <strong>Menu Button</strong> at the top-right corner.</li>
        <li>Click <strong>Sign Up.</strong></li>
        <li>Register using your <strong>Email Address.</strong></li>
        <li>Check your Email for a <strong>Temporary Password</strong></li>
        <li>Log in using the Temporary Password.</li>
        <li>Set a <strong>New Password</strong>.</li>
        <li>Log in again using your <strong> New Password</strong> and <strong>Registered Email Address.</strong></li>
      </ol>
    </div>
  </div>

<script>
(function(){
  const hamburgerBtn = document.getElementById('hamburger');
  const menu = document.getElementById('primary-menu');

  // Toggle function
  function toggleMenu(openFromKeyboard = false){
    const isOpen = menu.classList.contains('open');
    if(isOpen){
      menu.classList.remove('open');
      hamburgerBtn.setAttribute('aria-expanded','false');
      hamburgerBtn.setAttribute('aria-label','Open menu');
    } else {
      menu.classList.add('open');
      hamburgerBtn.setAttribute('aria-expanded','true');
      hamburgerBtn.setAttribute('aria-label','Close menu');
      if(openFromKeyboard){
        const first = menu.querySelector('a');
        if(first) first.focus();
      }
    }
  }

  // Click toggles menu
  hamburgerBtn.addEventListener('click', function(e){
    toggleMenu();
  });

  // Close when clicking outside
  document.addEventListener('click', function(e){
    if(!menu.contains(e.target) && !hamburgerBtn.contains(e.target)){
      if(menu.classList.contains('open')){
        menu.classList.remove('open');
        hamburgerBtn.setAttribute('aria-expanded','false');
        hamburgerBtn.setAttribute('aria-label','Open menu');
      }
    }
  });

  // Keyboard support
  hamburgerBtn.addEventListener('keydown', function(e){
    if(e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      toggleMenu(true);
    } else if(e.key === 'Escape'){
      if(menu.classList.contains('open')){
        menu.classList.remove('open');
        hamburgerBtn.setAttribute('aria-expanded','false');
        hamburgerBtn.setAttribute('aria-label','Open menu');
        hamburgerBtn.focus();
      }
    }
  });

  menu.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
      menu.classList.remove('open');
      hamburgerBtn.setAttribute('aria-expanded','false');
      hamburgerBtn.setAttribute('aria-label','Open menu');
      hamburgerBtn.focus();
    }
  });

  const links = menu.querySelectorAll('a');
  function updateTabIndex(){
    const open = menu.classList.contains('open');
    links.forEach(a => a.tabIndex = open ? 0 : -1);
  }
  const obs = new MutationObserver(updateTabIndex);
  obs.observe(menu, { attributes: true, attributeFilter: ['class'] });
  updateTabIndex();
})();

// ---- Modal code ----
const learnBtn = document.getElementById("learnBtn");
const modal = document.getElementById("myModal");
const closeSpan = document.querySelector(".close");

// Open modal
learnBtn.onclick = () => modal.classList.add("show");

// Close modal
closeSpan.onclick = () => modal.classList.remove("show");

// Close modal if clicked outside content
window.onclick = (e) => {
  if(e.target === modal) modal.classList.remove("show");
};
</script>

</body>
</html>
