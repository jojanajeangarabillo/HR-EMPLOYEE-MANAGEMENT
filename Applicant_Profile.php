<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="applicant.css">

    <!-- Internal CSS for dashboard contents -->
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .main-content {
            flex: 1;
            padding: 30px 80px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .profile-header {
            padding: 5px 10px;
            margin-left: 200px;
            font-size: 40px;
        }

        /* Personal info box */
        .personal-info {
            background-color: #1E3A8A;
            color: white;
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;
            height: 340px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            font-size: 20px;
            font-weight: 500;
        }

        .info {
            display: flex;
            margin-left: 50px;
            gap: 15px;
            flex-direction: column;
        }

        .phone,
        .location,
        .email {
            display: flex;
        }

        p {
            margin-left: 20px;
        }

        .edit-btn {
            height: 42px;
            width: 93px;
            border-color: white;
            border-style: solid;
            background-color: #1E3A8A;
        }

        .edit-btn:hover {
            background-color: #e5edfb;
            color: #1E3A8A;
        }

        .section {
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;

        }

        .reminder {
            display: flex;
            align-items: center;
            background-color: #e5ebf7;
            height: 86px;
            color: #1E3A8A;
            border-radius: 20px;
        }

        .add-btn {
            height: 51px;
            width: 213px;
            border-color: #1E3A8A;
            border-style: solid;
            background-color: white;
            color: #1E3A8A;
        }

        .add-btn:hover {
            color: white;
        }

        .complete-box {
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;
            background-color: #e5ebf7;
            color: #1E3A8A;
        }

        .complete-btn {
            background-color: #1E3A8A;
            border-style: solid;
        }

        .complete-btn:hover {
            background-color: #e5edfb;
            color: #1E3A8A;
            border-color: #1E3A8A;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }

        .modal-content button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .save-btn {
            background-color: #1E3A8A;
            color: white;
        }

        .cancel-btn {
            background-color: #ccc;
            margin-left: 8px;
        }
    </style>


</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="Applicant_Profile.php" class="profile">
            <i class="fa-solid fa-user"></i>
        </a>

        <ul class="nav">
            <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
            <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
        </ul>
    </div>

    </div>


    <!-- Main Content -->
    <main class="main-content">

        <h1 class="profile-header">Profile</h1>

        <div class="personal-info">
            <div class="info">
                <h1 name="full-name">Name</h1>
                <div class="phone">
                    <i class="fa-solid fa-phone"></i>
                    <p name="phone">Phone number</p>
                </div>
                <div class="location">
                    <i class="fa-solid fa-location-dot"></i>
                    <p name="location">Home location</p>
                </div>
                <div class="email">
                    <i class="fa-solid fa-envelope"></i>
                    <p name="email">Email</p>
                </div>
                <button onclick="openModal('editModal')" class="edit-btn"><i class="fa-solid fa-pen"></i>
                    Edit</button>
            </div>
        </div>
        <div class="section">
            <div class="career">
                <h3>Career history</h3>
                <div class="reminder">
                    <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent roles</p>
                </div>
                <button onclick="openModal('roleModal')" class="add-btn">Add role</button>
            </div>
        </div>
        <div class="section">
            <div class="education">
                <h3>Education</h3>
                <div class="reminder">
                    <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent qualifications</p>
                </div>
                <button onclick="openModal('educationModal')" class="add-btn">Add education</button>
            </div>
        </div>
        <div class="section">
            <h3>Skills</h3>
            <div class="reminder">
                <p><i class="fa-solid fa-circle-exclamation"></i> Please add at least five skills</p>
            </div>
            <button onclick="openModal('skillsModal')" class="add-btn">Add skills</button>
        </div>
        <div class="section">
            <h3>Summary</h3>
            <div class="reminder">
                <p><i class="fa-solid fa-circle-exclamation"></i> Please add a summary</p>
            </div>
            <button onclick="openModal('summaryModal')" class="add-btn">Add summary</button>
        </div>

        <div class="complete-box">
            <p>Ensure all information are correct, your resume will be created</p>
            <button class="complete-btn">Complete</button>
        </div>

    </main>

    <!--Modals-->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Profile</h3>
            <input type="text" placeholder="Name">
            <input type="text" placeholder="Phone number">
            <input type="text" placeholder="Home location">
            <input type="email" placeholder="Email">
            <button class="save-btn" onclick="closeModal('editModal')">Save</button>
            <button class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
        </div>
    </div>

    <div id="roleModal" class="modal">
        <div class="modal-content">
            <h3>Add Role</h3>
            <input type="text" placeholder="Job Title">
            <input type="text" placeholder="Company Name">
            <textarea rows="3" placeholder="Description"></textarea>
            <button class="save-btn" onclick="closeModal('roleModal')">Save</button>
            <button class="cancel-btn" onclick="closeModal('roleModal')">Cancel</button>
        </div>
    </div>

    <div id="educationModal" class="modal">
        <div class="modal-content">
            <h3>Add Education</h3>
            <input type="text" placeholder="School / University">
            <input type="text" placeholder="Degree / Course">
            <button class="save-btn" onclick="closeModal('educationModal')">Save</button>
            <button class="cancel-btn" onclick="closeModal('educationModal')">Cancel</button>
        </div>
    </div>

    <div id="skillsModal" class="modal">
        <div class="modal-content">
            <h3>Add Skills</h3>
            <input type="text" placeholder="Skill 1">
            <input type="text" placeholder="Skill 2">
            <input type="text" placeholder="Skill 3">
            <input type="text" placeholder="Skill 4">
            <input type="text" placeholder="Skill 5">
            <button class="save-btn" onclick="closeModal('skillsModal')">Save</button>
            <button class="cancel-btn" onclick="closeModal('skillsModal')">Cancel</button>
        </div>
    </div>

    <div id="summaryModal" class="modal">
        <div class="modal-content">
            <h3>Add Summary</h3>
            <textarea rows="4" placeholder="Write a short professional summary..."></textarea>
            <button class="save-btn" onclick="closeModal('summaryModal')">Save</button>
            <button class="cancel-btn" onclick="closeModal('summaryModal')">Cancel</button>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>


</body>

</html>