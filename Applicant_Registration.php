<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Registration</title>
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

    <!-- Registration Section -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input">
                <div class="login-email">
                    <h1 style ="font-size: 50px;"><b>Register</b></h1>
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                    </div>
                </div>

                <div class="register-button">
                    <button type="submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Register
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
