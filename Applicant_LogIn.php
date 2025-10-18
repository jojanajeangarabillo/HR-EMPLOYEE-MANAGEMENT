<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Login</title>
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

    <!-- Login Section -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input">
                 <h1 style ="font-size: 50px;"><b>Log In</b></h1>
                <!-- Email -->
                <div class="login-email">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your Email" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="login-password">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your Password" required>
                    </div>
                </div>

                <!-- Show Password -->
                <div class="show-password">
                    <input type="checkbox" id="show">
                    <label for="show">Show Password</label>
                </div>

                <!-- Login Button -->
                <div class="login-button">
                    <button type="submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In
                    </button>
                </div>

                <!-- Forgot Password -->
                <div class="forgot-password">
                    <a href="Applicant-Forgot-Password.html" class="forgot-password-link">Forgot password?</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
