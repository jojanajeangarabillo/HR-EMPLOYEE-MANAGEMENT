<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Change Password</title>
    <link rel="stylesheet" href="applicant.css">
    <link rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
          integrity="sha512-papNMv5z+YdUj4m6rKcxQZZNhpCJ3+VzYDA6kYskk5wDZqB8bJz5K5C9mEeD2iHZG5tLx4yPcXy4A4p4rA7Rqw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="login-body">
    <!-- Topbar -->
    <header class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
            <div class="top-bar-text">
                <h1>H O S P I T A L</h1>
                <h4>Applicant</h4>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input">
                <h1 style ="font-size: 40px;"><b>Change Password</b></h1>

                <!-- Current Password -->
                <div class="input-group">
                    <label for="currentPass">Current Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="currentPass" name="currentPass" placeholder="Enter Current Password" required>
                    </div>
                </div>

                <!-- New Password -->
                <div class="input-group">
                    <label for="newPass">New Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="newPass" name="newPass" placeholder="Enter New Password" required>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="input-group">
                    <label for="confirmPass">Confirm Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirmPass" name="confirmPass" placeholder="Confirm New Password" required>
                    </div>
                </div>

                <!-- Confirm Button -->
                <div class="confirm-button">
                    <button type="submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Confirm
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
