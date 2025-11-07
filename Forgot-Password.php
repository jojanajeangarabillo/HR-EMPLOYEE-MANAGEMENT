<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
            </div>
        </div>
    </header>

    <!-- Forgot Password Section -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input">
                <h1 style ="font-size: 40px;"><b>Forgot Password</b></h1>

                <!-- Email Input -->
                <div class="login-email">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                    </div>
                </div>

                <!-- Send OTP Button -->
                <div class="login-button">
                    <button type="submit">
                        <i class="fa-solid fa-paper-plane"></i> Send OTP
                    </button>
                </div>

                <div class="back-link">
                    <a href="Login.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
