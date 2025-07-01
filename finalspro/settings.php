<?php
session_start();
include '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$profile_image = null;

// Get user's name and profile image
if ($user_type === 'Admin_Secretary') {
    $stmt = $conn->prepare("SELECT lastname, firstname, image FROM Admin_Secretary WHERE admin_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profile_image = $row['image'];
        // Set the session variables if they're not already set
        if (!isset($_SESSION['first_name'])) {
            $_SESSION['first_name'] = $row['firstname'];
        }
        if (!isset($_SESSION['last_name'])) {
            $_SESSION['last_name'] = $row['lastname'];
        }
    }
} elseif ($user_type === 'partTime_Professor') {
    $stmt = $conn->prepare("SELECT first_name, last_name, image FROM PartTime_Professor WHERE professor_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profile_image = $row['image'];
        // Set the session variables if they're not already set
        if (!isset($_SESSION['first_name'])) {
            $_SESSION['first_name'] = $row['first_name'];
        }
        if (!isset($_SESSION['last_name'])) {
            $_SESSION['last_name'] = $row['last_name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        #nav-top {
            background: linear-gradient(to right, #2c5e1a, #1d3d11);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 69px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-left {
            display: flex;
            align-items: center;
        }

        #logo {
            width: 43px;
            margin-right: 15px;
        }

        .university-name {
            display: flex;
            flex-direction: column;
        }

        #top-text {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.7px;
        }

        #top-text2 {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.7px;
            color: #ffff99;
        }

        .nav-right {
            display: flex;
            align-items: center;
        }

        #profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #eee;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dropdown-nav {
            position: relative;
            display: inline-block;
        }

        .dropdown-button-nav {
            background-color: #f5f5f5;
            color: #1d3d11;
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }

        .dropdown-button-nav::after {
            content: "▼";
            margin-left: 8px;
            font-size: 10px;
        }

        .dropdown-content-nav {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }

        .dropdown-content-nav a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }

        .dropdown-content-nav a:hover {
            background-color: #f1f1f1;
        }

        .dropdown-nav:hover .dropdown-content-nav {
            display: block;
        }

        /* Sidebar styling */
        .wrapper {
            display: flex;
            height: calc(100vh - 69px);
            margin-top: 69px;
        }

        .sidebar {
            background: #f0f0f0;
            width: 250px;
            height: 100%;
            transition: all 0.3s ease;
            overflow-y: hidden;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 0;
            padding: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 15px;
            background: #e8e8e8;
            border-bottom: 1px solid #ddd;
        }

        .sidebar-header h3 {
            color: #333;
            font-size: 18px;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: #333;
            font-size: 16px;
            cursor: pointer;
        }

        .sidebar-menu {
            padding: 10px 0;
        }

        .sidebar-menu ul {
            list-style-type: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            padding: 12px 20px;
            display: block;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            background: #e0e0e0;
            border-left: 3px solid #2c5e1a;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content styling */
        .main-content {
            flex: 1;
            padding: 20px;
            transition: all 0.3s ease;
            overflow-x: auto;
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .breadcrumb {
            display: flex;
            list-style: none;
            font-size: 12px;
            color: #777;
        }

        .breadcrumb li:not(:last-child)::after {
            content: ">";
            margin: 0 8px;
        }

        .controls-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .toggle-sidebar-btn {
            background: #155724;
            border: 1px solid #ddd;
            padding: 20px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-left: -20px;
            border-top-right-radius: 100px;
            border-bottom-right-radius: 100px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            margin-top: -14px;
        }

        .toggle-sidebar-btn:hover {
            background: #e8e8e8;
            color: #1d3d11;
            box-shadow: 0px 0px 10px 2px black;
        }

        .controls {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-right: 6px;
        }

        .btn-primary {
            background-color: #2c5e1a;
            color: white;
        }

        .btn-primary:hover {
            background-color: #234a15;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: #333;
        }

        .btn-outline:hover {
            background-color: #f0f0f0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
            font-size: 13px;
            padding: 6px 12px;
            margin: 0 2px;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        .btn-danger {
            background-color: #f44336;
            color: white;
            font-size: 13px;
            padding: 6px 12px;
            margin: 0 2px;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        /* Settings Specific Styles */
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            font-weight: bold;
        }

        .tab.active {
            background-color: #fff;
            border-bottom: 2px solid #fff;
            margin-bottom: -1px;
            color: #2c5e1a;
        }

        .tab-content {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 0 4px 4px 4px;
            padding: 20px;
        }

        .form-section {
            margin-bottom: 20px;
        }

        .form-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c5e1a;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2c5e1a;
            box-shadow: 0 0 0 2px rgba(44, 94, 26, 0.2);
        }

        .btn-action {
            background-color: #2c5e1a;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background-color: #234a15;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Table styling */
        .table-container {
            margin-top: 20px;
            overflow-x: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f9f9f9;
        }

        th {
            text-align: left;
            padding: 15px 20px;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #eee;
        }

        th:last-child {
            text-align: center;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        /* Modal styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1010;
        }

        .modal-content {
            background: #fff;
            color: #000;
            padding: 25px;
            width: 500px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            color: #333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            color: #777;
            cursor: pointer;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-row label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-row input,
        .form-row select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-row input:focus,
        .form-row select:focus {
            border-color: #2c5e1a;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 94, 26, 0.2);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn-cancel {
            background-color: #f1f1f1;
            color: #333;
        }

        .btn-submit {
            background-color: #2c5e1a;
            color: white;
        }

        #bot-sidebar {
            position: relative;
            top: 420px;
        }
    </style>
</head>

<body>
    <nav id="nav-top">
        <div class="nav-left">
            <img id="logo" src="Logo.ico" alt="University Logo" />
            <div class="university-name">
                <h1 id="top-text">Pamantasan ng Lungsod ng Pasig</h1>
                <h1 id="top-text2">University of Pasig</h1>
            </div>
        </div>
        <div class="nav-right">
            <div id="profile">
                <img src="<?php
                            echo $profile_image ? 'data:image/jpeg;base64,' . base64_encode($profile_image) : '/api/placeholder/40/40';
                            ?>" alt="User Profile" />
            </div>
            <div class="dropdown-nav">
                <button class="dropdown-button-nav">
                    <?php
                    // Check if name is available in session
                    if (isset($_SESSION['last_name'], $_SESSION['first_name'])) {
                        echo htmlspecialchars($_SESSION['last_name'] . ', ' . $_SESSION['first_name']);
                    } else {
                        // Fallback to fetching from database if not in session
                        include '../Backend/db_connect.php';
                        $user_id = $_SESSION['user_id'];
                        $user_type = $_SESSION['user_type'];

                        if ($user_type === 'Admin_Secretary') {
                            $stmt = $conn->prepare("SELECT lastname, firstname FROM Admin_Secretary WHERE admin_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname']);
                                $_SESSION['first_name'] = $row['firstname'];
                                $_SESSION['last_name'] = $row['lastname'];
                            } else {
                                echo 'Username';
                            }
                        } elseif ($user_type === 'partTime_Professor') {
                            $stmt = $conn->prepare("SELECT last_name, first_name FROM PartTime_Professor WHERE professor_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']);
                                $_SESSION['first_name'] = $row['first_name'];
                                $_SESSION['last_name'] = $row['last_name'];
                            } else {
                                echo 'Username';
                            }
                        }
                        $conn->close();
                    }
                    ?>
                </button>
                <div class="dropdown-content-nav">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="wrapper">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Dashboard</h3>
                <button class="toggle-btn" id="close-sidebar"></button>
            </div>

            <div class="sidebar-menu">
                <ul>
                    <li><a href="management.php"><i class="fas fa-users"></i> Employee Management</a></li>
                    <li><a href="emp-formload.php"><i class="fas fa-calendar-alt"></i> Form Loading</a></li>
                    <li><a href="emp-dtr.php"><i class="fas fa-file-alt"></i> Daily Time Record</a></li>
                    <li><a href="emp-ar.php"><i class="fas fa-clock"></i> Accomplishment Report</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Class Management</a></li>
                    <li id="bot-sidebar"><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>


                </ul>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <div class="content-header">
                <h1>Class Management</h1>
                <ul class="breadcrumb">
                    <li>Dashboard</li>
                    <li>Class Management</li>
                </ul>
            </div>

            <div class="controls-wrapper">
                <button class="toggle-sidebar-btn" id="toggle-sidebar-btn">
                    <span id="sidebar-icon">☰</span> <span id="sidebar-text"></span>
                </button>
                <div class="controls">
                    <button class="btn btn-outline" id="sync-btn"><i class="fas fa-sync"></i> Sync</button>
                </div>
            </div>

            <div class="settings-tabs">
                <div class="tab active" data-tab="subjects">Subjects</div>
                <div class="tab" data-tab="courses">Courses</div>
                <div class="tab" data-tab="sections">Sections</div>
                <div class="tab" data-tab="semester">Semester</div>

            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="subjects-panel">
                    <div class="form-section">
                        <h3>Add New Subject</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="subject-code">Subject Code</label>
                                <input type="text" id="subject-code" class="form-control" placeholder="e.g. IT105">
                            </div>
                            <div class="form-group">
                                <label for="subject-name">Subject Name</label>
                                <input type="text" id="subject-name" class="form-control"
                                    placeholder="e.g. Networking I">
                            </div>
                            <div class="form-group">
                                <label for="subject-units">Units</label>
                                <input type="number" id="subject-units" class="form-control" placeholder="e.g. 3">
                            </div>
                            <div class="form-group">
                                <label for="subject-department">Department</label>
                                <select id="subject-department" class="form-control">
                                    <option value="" disabled selected hidden>Select Department</option>
                                    <option value="nursing">College of Nursing</option>
                                    <option value="engineering">College of Engineering</option>
                                    <option value="education">College of Education</option>
                                    <option value="computer-studies">College of Computer Studies</option>
                                    <option value="arts-science">College of Arts and Science</option>
                                    <option value="business-accountancy">College of Business and Accountancy</option>
                                    <option value="hospitality-management">College of Hospitality Management</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-action">Add Subject</button>
                            <button class="btn-action btn-secondary">Clear</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <h3>Existing Subjects</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- This will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sections Panel -->
                <div class="tab-panel" id="sections-panel">
                    <div class="form-section">
                        <h3>Add New Section</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="section-code">Section Code</label>
                                <input type="text" id="section-code" class="form-control" placeholder="e.g. 2A">
                            </div>
                            <div class="form-group">
                                <label for="section-name">Section Name</label>
                                <input type="text" id="section-name" class="form-control" placeholder="e.g. BSCS 1A">
                            </div>
                            <div class="form-group">
                                <label for="section-course">Course</label>
                                <select id="section-course" class="form-control">
                                    <option value="" disabled selected hidden>Select Course</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-action">Add Section</button>
                            <button class="btn-action btn-secondary">Clear</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <h3>Existing Sections</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Section Code</th>
                                    <th>Section Name</th>
                                    <th>Course</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- This will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-panel" id="semester-panel">
                    <div class="form-section">
                        <h3>Add New Semester</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="academic-year">Academic Year</label>
                                <input type="text" id="academic-year" class="form-control" placeholder="e.g. 2023-2024">
                            </div>
                            <div class="form-group">
                                <label for="semester">Semester</label>
                                <select id="semester" class="form-control">
                                    <option value="" disabled selected hidden>Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date-from">Date From</label>
                                <input type="date" id="date-from" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="date-to">Date To</label>
                                <input type="date" id="date-to" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-action">Add Semester</button>
                            <button class="btn-action btn-secondary">Clear</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <h3>Existing Semesters</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Date From</th>
                                    <th>Date To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- This will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-panel" id="courses-panel">
                    <div class="form-section">
                        <h3>Add New Course</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" class="form-control">
                                    <option value="" disabled selected hidden>Select Department</option>
                                    <option value="nursing">College of Nursing</option>
                                    <option value="engineering">College of Engineering</option>
                                    <option value="education">College of Education</option>
                                    <option value="computer-studies">College of Computer Studies</option>
                                    <option value="arts-science">College of Arts and Science</option>
                                    <option value="business-accountancy">College of Business and Accountancy</option>
                                    <option value="hospitality-management">College of Hospitality Management</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="program-name">Program Name</label>
                                <input type="text" id="program-name" class="form-control"
                                    placeholder="e.g. Bachelor of Science in Computer Science">
                            </div>
                            <div class="form-group">
                                <label for="department-head">Department Dean/Head</label>
                                <input type="text" id="department-head" class="form-control"
                                    placeholder="e.g. Dr. Maria Santos">
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-action">Add Course</button>
                            <button class="btn-action btn-secondary">Clear</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <h3>Existing Courses</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Program Name</th>
                                    <th>Department Dean/Head</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- This will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing records -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Record</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body" id="modal-form-content">
                <!-- Content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-submit" id="saveChangesBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebarIcon = document.getElementById('sidebar-icon');
        const tabs = document.querySelectorAll('.tab');
        const tabPanels = document.querySelectorAll('.tab-panel');
        const editModal = document.getElementById("editModal");
        const saveChangesBtn = document.getElementById("saveChangesBtn");

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                sidebarIcon.textContent = '☰';
            } else {
                sidebarIcon.textContent = '☰';
            }
        }

        toggleSidebarBtn.addEventListener('click', toggleSidebar);
        closeSidebarBtn.addEventListener('click', toggleSidebar);

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                tabPanels.forEach(panel => panel.classList.remove('active'));

                const tabId = this.getAttribute('data-tab');
                document.getElementById(`${tabId}-panel`).classList.add('active');

                // When switching to sections tab, ensure course dropdown is populated
                if (tabId === 'sections') {
                    populateCourseDropdown();
                }
            });
        });

        // Global variables
        let currentTab = 'subjects';
        let subjectsData = [];
        let sectionsData = [];
        let coursesData = [];
        let professorsData = [];
        let semestersData = [];
        let currentEditingId = null;
        let currentEditingType = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadInitialData();
            setupEventListeners();
        });

        function loadInitialData() {
            fetchCourses()
                .then(fetchSections)
                .then(fetchSubjects)
                .then(fetchProfessors)
                .then(fetchSemesters)
                .catch(error => console.error('Error loading initial data:', error));
        }


        function setupEventListeners() {
            // Add button event listeners
            document.querySelector('#subjects-panel .btn-action').addEventListener('click', addSubject);
            document.querySelector('#subjects-panel .btn-secondary').addEventListener('click', clearSubjectForm);

            document.querySelector('#sections-panel .btn-action').addEventListener('click', addSection);
            document.querySelector('#sections-panel .btn-secondary').addEventListener('click', clearSectionForm);

            document.querySelector('#courses-panel .btn-action').addEventListener('click', addCourse);
            document.querySelector('#courses-panel .btn-secondary').addEventListener('click', clearCourseForm);

            document.querySelector('#semester-panel .btn-action').addEventListener('click', addSemester);
            document.querySelector('#semester-panel .btn-secondary').addEventListener('click', clearSemesterForm);
        }

        // Fetch data functions
        function fetchSubjects() {
            return fetch('../Backend/ClassManagement/subjects.php')
                .then(response => response.json())
                .then(data => {
                    subjectsData = data;
                    renderSubjectsTable();
                    return data; // Return data for chaining
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    throw error;
                });
        }

        function fetchSections() {
            return fetch('../Backend/ClassManagement/sections.php')
                .then(response => response.json())
                .then(data => {
                    sectionsData = data;
                    renderSectionsTable();
                    return data; // Return data for chaining
                })
                .catch(error => {
                    console.error('Error fetching sections:', error);
                    throw error;
                });
        }

        function fetchCourses() {
            return fetch('../Backend/ClassManagement/courses.php')
                .then(response => response.json())
                .then(data => {
                    coursesData = data;
                    renderCoursesTable();
                    return data; // Return data for chaining
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                    throw error;
                });
        }

        function fetchProfessors() {
            fetch('../Backend/ClassManagement/professor.php')
                .then(response => response.json())
                .then(data => {
                    professorsData = data;
                })
                .catch(error => console.error('Error fetching professors:', error));
        }

        function fetchSemesters() {
            return fetch('../Backend/ClassManagement/semester.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    semestersData = data;
                    renderSemestersTable();
                    return data;
                })
                .catch(error => {
                    console.error('Error fetching semesters:', error);
                    throw error;
                });
        }

        function addSemester() {
            const academicYear = document.getElementById('academic-year').value.trim();
            const semester = document.getElementById('semester').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            if (!academicYear || !semester || !dateFrom || !dateTo) {
                alert('Please fill all required fields');
                return;
            }

            const newSemester = {
                academic_year: academicYear,
                semester: semester,
                date_from: dateFrom,
                date_to: dateTo
            };

            // Optimistically add to local data
            semestersData.push(newSemester);
            renderSemestersTable();

            fetch('../Backend/ClassManagement/semester.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(newSemester)
                })
                .then(response => {
                    if (!response.ok) {
                        semestersData.pop();
                        renderSemestersTable();
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    alert('Semester added successfully');
                    clearSemesterForm();
                    return fetchSemesters();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding semester: ' + error.message);
                });
        }

        function clearSemesterForm() {
            document.getElementById('academic-year').value = '';
            document.getElementById('semester').selectedIndex = 0;
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
        }

        function renderSemestersTable() {
            const tbody = document.querySelector('#semester-panel table tbody');
            tbody.innerHTML = '';

            semestersData.forEach(semester => {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${semester.academic_year}</td>
            <td>${semester.semester}</td>
            <td>${semester.date_from}</td>
            <td>${semester.date_to}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="openEditModal(${semester.semester_id}, 'semester')">
                        <i class="fas fa-edit"></i> Update
                    </button>
                    <button class="btn btn-danger" onclick="deleteSemester(${semester.semester_id})">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </td>
        `;
                tbody.appendChild(row);
            });
        }

        function deleteSemester(id) {
            if (confirm('Are you sure you want to delete this semester?')) {
                fetch(`../Backend/ClassManagement/semester.php?id=${id}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert('Semester deleted successfully');
                            return fetchSemesters();
                        }
                    })
                    .catch(error => console.error('Error deleting semester:', error));
            }
        }

        // Render tables
        function renderSubjectsTable() {
            const tbody = document.querySelector('#subjects-panel table tbody');
            tbody.innerHTML = '';

            subjectsData.forEach(subject => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subject.subject_code}</td>
                    <td>${subject.subject_name}</td>
                    <td>${subject.units}</td>
                    <td>${subject.department}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-secondary" onclick="openEditModal(${subject.subject_id}, 'subject')">
                                <i class="fas fa-edit"></i> Update
                            </button>
                            <button class="btn btn-danger" onclick="deleteSubject(${subject.subject_id})">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function renderSectionsTable() {
            const tbody = document.querySelector('#sections-panel table tbody');
            tbody.innerHTML = '';

            sectionsData.forEach(section => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${section.section_code}</td>
                    <td>${section.section_name}</td>
                    <td>${section.program_name || 'N/A'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-secondary" onclick="openEditModal(${section.section_id}, 'section')">
                                <i class="fas fa-edit"></i> Update
                            </button>
                            <button class="btn btn-danger" onclick="deleteSection(${section.section_id})">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function renderCoursesTable() {
            const tbody = document.querySelector('#courses-panel table tbody');
            tbody.innerHTML = '';

            coursesData.forEach(course => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${course.department}</td>
                    <td>${course.program_name}</td>
                    <td>${course.department_head}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-secondary" onclick="openEditModal(${course.course_id}, 'course')">
                                <i class="fas fa-edit"></i> Update
                            </button>
                            <button class="btn btn-danger" onclick="deleteCourse(${course.course_id})">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Populate dropdowns
        function populateCourseDropdown() {
            const dropdown = document.getElementById('section-course');
            dropdown.innerHTML = '<option value="" disabled selected hidden>Select Course</option>';

            coursesData.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.program_name;
                dropdown.appendChild(option);
            });
        }

        // Add functions
        function addSubject() {
            const subjectCode = document.getElementById('subject-code').value.trim();
            const subjectName = document.getElementById('subject-name').value.trim();
            const units = document.getElementById('subject-units').value.trim();
            const department = document.getElementById('subject-department').value;

            if (!subjectCode || !subjectName || !units || !department) {
                alert('Please fill all required fields');
                return;
            }

            const newSubject = {
                subject_code: subjectCode,
                subject_name: subjectName,
                units: parseInt(units),
                department: department
            };

            // Optimistically add to local data
            subjectsData.push(newSubject);
            renderSubjectsTable();

            fetch('../Backend/ClassManagement/subjects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(newSubject)
                })
                .then(response => {
                    if (!response.ok) {
                        // If failed, remove from local data and refresh
                        subjectsData.pop();
                        renderSubjectsTable();
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    alert('Subject added successfully');
                    clearSubjectForm();
                    // Refresh from server to get the actual ID
                    return fetchSubjects();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding subject: ' + error.message);
                });
        }


        function addSection() {
            const sectionCode = document.getElementById('section-code').value.trim();
            const sectionName = document.getElementById('section-name').value.trim();
            const courseId = document.getElementById('section-course').value;

            if (!sectionCode || !sectionName || !courseId) {
                alert('Please fill all required fields');
                return;
            }

            const newSection = {
                section_code: sectionCode,
                section_name: sectionName,
                course_id: courseId,
                program_name: getCourseNameById(courseId)
            };

            // Optimistically add to local data
            sectionsData.push(newSection);
            renderSectionsTable();

            fetch('../Backend/ClassManagement/sections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        section_code: sectionCode,
                        section_name: sectionName,
                        course_id: courseId,
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        // If failed, remove from local data and refresh
                        sectionsData.pop();
                        renderSectionsTable();
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    alert('Section added successfully');
                    clearSectionForm();
                    return fetchSections();
                })
                .catch(error => {
                    console.error('Error adding section:', error);
                    alert('Error adding section: ' + error.message);
                });
        }

        function getCourseNameById(courseId) {
            const course = coursesData.find(c => c.course_id == courseId);
            return course ? course.program_name : 'N/A';
        }

        function addCourse() {
            const department = document.getElementById('department').value;
            const programName = document.getElementById('program-name').value.trim();
            const departmentHead = document.getElementById('department-head').value.trim();

            if (!department || !programName || !departmentHead) {
                alert('Please fill all required fields');
                return;
            }

            const newCourse = {
                department: department,
                program_name: programName,
                department_head: departmentHead
            };

            // Optimistically add to local data
            coursesData.push(newCourse);
            renderCoursesTable();
            populateCourseDropdown();

            fetch('../Backend/ClassManagement/courses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(newCourse)
                })
                .then(response => {
                    if (!response.ok) {
                        // If failed, remove from local data and refresh
                        coursesData.pop();
                        renderCoursesTable();
                        populateCourseDropdown();
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    alert('Course added successfully');
                    clearCourseForm();
                    return fetchCourses().then(() => populateCourseDropdown());
                })
                .catch(error => {
                    console.error('Error adding course:', error);
                    alert('Error adding course: ' + error.message);
                });
        }

        // Clear form functions
        function clearSubjectForm() {
            document.getElementById('subject-code').value = '';
            document.getElementById('subject-name').value = '';
            document.getElementById('subject-units').value = '';
            document.getElementById('subject-department').selectedIndex = 0;
        }

        function clearSectionForm() {
            document.getElementById('section-code').value = '';
            document.getElementById('section-name').value = '';
            document.getElementById('section-course').selectedIndex = 0;
        }

        function clearCourseForm() {
            document.getElementById('department').selectedIndex = 0;
            document.getElementById('program-name').value = '';
            document.getElementById('department-head').value = '';
        }

        // Delete functions
        function deleteSubject(id) {
            if (confirm('Are you sure you want to delete this subject?')) {
                fetch(`../Backend/ClassManagement/subjects.php?id=${id}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert('Subject deleted successfully');
                            return fetchSubjects().then(() => {
                                renderSubjectsTable();
                            });
                        }
                    })
                    .catch(error => console.error('Error deleting subject:', error));
            }
        }

        function deleteSection(id) {
            if (confirm('Are you sure you want to delete this section?')) {
                fetch(`../Backend/ClassManagement/sections.php?id=${id}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert('Section deleted successfully');
                            return fetchSections().then(() => {
                                renderSectionsTable();
                            });
                        }
                    })
                    .catch(error => console.error('Error deleting section:', error));
            }
        }


        function deleteCourse(id) {
            if (confirm('Are you sure you want to delete this course?')) {
                fetch(`../Backend/ClassManagement/courses.php`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(text)
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert('Course deleted successfully');
                            return fetchCourses().then(() => {
                                renderCoursesTable();
                                populateCourseDropdown();
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting course:', error);
                        alert('Error deleting course: ' + error.message);
                    });
            }
        }

        // Modal functions
        function openEditModal(id, type) {
            currentEditingId = id;
            currentEditingType = type;

            const modalContent = document.getElementById('modal-form-content');
            modalContent.innerHTML = '';

            // Set the modal title based on type
            const modalTitle = document.querySelector('#editModal .modal-header h2');

            if (type === 'subject') {
                modalTitle.innerHTML = '<i class="fas fa-book"></i> Update Subject';
                const subject = subjectsData.find(s => s.subject_id == id);

                if (!subject) {
                    alert('Subject not found');
                    return;
                }

                modalContent.innerHTML = `
            <div class="form-row">
                <label for="edit-subject-code">Subject Code</label>
                <input type="text" id="edit-subject-code" value="${subject.subject_code}" required>
            </div>
            <div class="form-row">
                <label for="edit-subject-name">Subject Name</label>
                <input type="text" id="edit-subject-name" value="${subject.subject_name}" required>
            </div>
            <div class="form-row">
                <label for="edit-subject-units">Units</label>
                <input type="number" id="edit-subject-units" value="${subject.units}" required>
            </div>
            <div class="form-row">
                <label for="edit-subject-department">Department</label>
                <select id="edit-subject-department">
                    <option value="nursing" ${subject.department === 'nursing' ? 'selected' : ''}>College of Nursing</option>
                    <option value="engineering" ${subject.department === 'engineering' ? 'selected' : ''}>College of Engineering</option>
                    <option value="education" ${subject.department === 'education' ? 'selected' : ''}>College of Education</option>
                    <option value="computer-studies" ${subject.department === 'computer-studies' ? 'selected' : ''}>College of Computer Studies</option>
                    <option value="arts-science" ${subject.department === 'arts-science' ? 'selected' : ''}>College of Arts and Science</option>
                    <option value="business-accountancy" ${subject.department === 'business-accountancy' ? 'selected' : ''}>College of Business and Accountancy</option>
                    <option value="hospitality-management" ${subject.department === 'hospitality-management' ? 'selected' : ''}>College of Hospitality Management</option>
                </select>
            </div>
        `;

            } else if (type === 'section') {
                modalTitle.innerHTML = '<i class="fas fa-users"></i> Update Section';
                const section = sectionsData.find(s => s.section_id == id);

                if (!section) {
                    alert('Section not found');
                    return;
                }

                // Generate options for course dropdown
                let courseOptions = '';
                coursesData.forEach(course => {
                    courseOptions += `<option value="${course.course_id}" ${section.course_id == course.course_id ? 'selected' : ''}>${course.program_name}</option>`;
                });

                modalContent.innerHTML = `
            <div class="form-row">
                <label for="edit-section-code">Section Code</label>
                <input type="text" id="edit-section-code" value="${section.section_code}" required>
            </div>
            <div class="form-row">
                <label for="edit-section-name">Section Name</label>
                <input type="text" id="edit-section-name" value="${section.section_name}" required>
            </div>
            <div class="form-row">
                <label for="edit-section-course">Course</label>
                <select id="edit-section-course">
                    <option value="" disabled>Select Course</option>
                    ${courseOptions}
                </select>
            </div>
        `;

            } else if (type === 'course') {
                modalTitle.innerHTML = '<i class="fas fa-graduation-cap"></i> Update Course';
                const course = coursesData.find(c => c.course_id == id);

                if (!course) {
                    alert('Course not found');
                    return;
                }

                modalContent.innerHTML = `
            <div class="form-row">
                <label for="edit-department">Department</label>
                <select id="edit-department">
                    <option value="nursing" ${course.department === 'nursing' ? 'selected' : ''}>College of Nursing</option>
                    <option value="engineering" ${course.department === 'engineering' ? 'selected' : ''}>College of Engineering</option>
                    <option value="education" ${course.department === 'education' ? 'selected' : ''}>College of Education</option>
                    <option value="computer-studies" ${course.department === 'computer-studies' ? 'selected' : ''}>College of Computer Studies</option>
                    <option value="arts-science" ${course.department === 'arts-science' ? 'selected' : ''}>College of Arts and Science</option>
                    <option value="business-accountancy" ${course.department === 'business-accountancy' ? 'selected' : ''}>College of Business and Accountancy</option>
                    <option value="hospitality-management" ${course.department === 'hospitality-management' ? 'selected' : ''}>College of Hospitality Management</option>
                </select>
            </div>
            <div class="form-row">
                <label for="edit-program-name">Program Name</label>
                <input type="text" id="edit-program-name" value="${course.program_name}" required>
            </div>
            <div class="form-row">
                <label for="edit-department-head">Department Dean/Head</label>
                <input type="text" id="edit-department-head" value="${course.department_head}" required>
            </div>
        `;
            } else if (type === 'semester') {
                modalTitle.innerHTML = '<i class="fas fa-calendar-alt"></i> Update Semester';
                const semester = semestersData.find(s => s.semester_id == id);

                if (!semester) {
                    alert('Semester not found');
                    return;
                }

                modalContent.innerHTML = `
                    <div class="form-row">
                        <label for="edit-academic-year">Academic Year</label>
                        <input type="text" id="edit-academic-year" value="${semester.academic_year}" required>
                    </div>
                    <div class="form-row">
                        <label for="edit-semester">Semester</label>
                        <select id="edit-semester">
                            <option value="1st Semester" ${semester.semester === '1st Semester' ? 'selected' : ''}>1st Semester</option>
                            <option value="2nd Semester" ${semester.semester === '2nd Semester' ? 'selected' : ''}>2nd Semester</option>
                            <option value="Summer" ${semester.semester === 'Summer' ? 'selected' : ''}>Summer</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="edit-date-from">Date From</label>
                        <input type="date" id="edit-date-from" value="${semester.date_from}" required>
                    </div>
                    <div class="form-row">
                        <label for="edit-date-to">Date To</label>
                        <input type="date" id="edit-date-to" value="${semester.date_to}" required>
                    </div>
                `;
            }

            // Set up the save button handler
            saveChangesBtn.onclick = saveChanges;

            // Show the modal
            editModal.style.display = "flex";
        }


        function saveChanges() {
            if (!currentEditingId || !currentEditingType) return;

            let endpoint = '';
            let payload = {};
            let updatedItem = {};

            if (currentEditingType === 'subject') {
                endpoint = '../Backend/ClassManagement/subjects.php';
                updatedItem = {
                    subject_id: currentEditingId,
                    subject_code: document.getElementById('edit-subject-code').value,
                    subject_name: document.getElementById('edit-subject-name').value,
                    units: document.getElementById('edit-subject-units').value,
                    department: document.getElementById('edit-subject-department').value
                };
                payload = updatedItem;
            } else if (currentEditingType === 'section') {
                endpoint = '../Backend/ClassManagement/sections.php';
                updatedItem = {
                    section_id: currentEditingId,
                    section_code: document.getElementById('edit-section-code').value,
                    section_name: document.getElementById('edit-section-name').value,
                    course_id: document.getElementById('edit-section-course').value,
                    program_name: getCourseNameById(document.getElementById('edit-section-course').value)
                };
                payload = {
                    section_id: currentEditingId,
                    section_code: document.getElementById('edit-section-code').value,
                    section_name: document.getElementById('edit-section-name').value,
                    course_id: document.getElementById('edit-section-course').value
                };
            } else if (currentEditingType === 'course') {
                endpoint = '../Backend/ClassManagement/courses.php';
                updatedItem = {
                    course_id: currentEditingId,
                    department: document.getElementById('edit-department').value,
                    program_name: document.getElementById('edit-program-name').value,
                    department_head: document.getElementById('edit-department-head').value
                };
                payload = updatedItem;
            } else if (currentEditingType === 'semester') {
                endpoint = '../Backend/ClassManagement/semester.php';
                updatedItem = {
                    semester_id: currentEditingId,
                    academic_year: document.getElementById('edit-academic-year').value,
                    semester: document.getElementById('edit-semester').value,
                    date_from: document.getElementById('edit-date-from').value,
                    date_to: document.getElementById('edit-date-to').value
                };
                payload = updatedItem;
            }

            // Show loading state
            saveChangesBtn.disabled = true;
            saveChangesBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            // Update the local data immediately (optimistic update)
            if (currentEditingType === 'subject') {
                const index = subjectsData.findIndex(s => s.subject_id == currentEditingId);
                if (index !== -1) {
                    subjectsData[index] = updatedItem;
                    renderSubjectsTable();
                }
            } else if (currentEditingType === 'section') {
                const index = sectionsData.findIndex(s => s.section_id == currentEditingId);
                if (index !== -1) {
                    sectionsData[index] = updatedItem;
                    renderSectionsTable();
                }
            } else if (currentEditingType === 'course') {
                const index = coursesData.findIndex(c => c.course_id == currentEditingId);
                if (index !== -1) {
                    coursesData[index] = updatedItem;
                    renderCoursesTable();
                    populateCourseDropdown();
                }
            } else if (currentEditingType === 'semester') {
                const index = semestersData.findIndex(s => s.semester_id == currentEditingId);
                if (index !== -1) {
                    semestersData[index] = updatedItem;
                    renderSemestersTable();
                }
            }

            // Then make the API call
            fetch(endpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) {
                        // If the API call fails, revert the local changes
                        if (currentEditingType === 'subject') {
                            fetchSubjects().then(() => renderSubjectsTable());
                        } else if (currentEditingType === 'section') {
                            fetchSections().then(() => renderSectionsTable());
                        } else if (currentEditingType === 'course') {
                            fetchCourses().then(() => {
                                renderCoursesTable();
                                populateCourseDropdown();
                            });
                        } else if (currentEditingType === 'semester') {
                            fetchSemesters().then(() => renderSemestersTable());
                        }
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    alert('Changes saved successfully');
                    closeModal();

                    if (currentEditingType === 'subject') {
                        return fetchSubjects();
                    } else if (currentEditingType === 'section') {
                        return fetchSections();
                    } else if (currentEditingType === 'course') {
                        return fetchCourses().then(() => populateCourseDropdown());
                    } else if (currentEditingType === 'semester') {
                        return fetchSemesters();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving changes: ' + error.message);
                })
                .finally(() => {
                    saveChangesBtn.disabled = false;
                    saveChangesBtn.innerHTML = 'Save Changes';
                });
        }

        function closeModal() {
            editModal.style.display = "none";
            currentEditingId = null;
            currentEditingType = null;
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editModal) {
                closeModal();
            }
        });

        function handleSync() {
            // Show loading state on the button
            const syncBtn = document.getElementById('sync-btn');
            const originalText = syncBtn.innerHTML;
            syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            syncBtn.disabled = true;

            // Determine which tab is currently active
            const activeTab = document.querySelector('.tab.active').getAttribute('data-tab');

            // Array to hold all the fetch promises
            const fetchPromises = [];

            // Always fetch professors as they might be needed for dropdowns
            fetchPromises.push(fetchProfessors());

            // Fetch data based on the active tab
            if (activeTab === 'subjects') {
                fetchPromises.push(fetchSubjects());
            } else if (activeTab === 'sections') {
                fetchPromises.push(fetchSections());
                fetchPromises.push(fetchCourses()); // Need courses for the dropdown
            } else if (activeTab === 'courses') {
                fetchPromises.push(fetchCourses());
            }

            // Execute all fetches
            Promise.all(fetchPromises)
                .then(() => {
                    // Show success message
                    alert('Data synchronized successfully');
                })
                .catch(error => {
                    console.error('Sync error:', error);
                    alert('Error synchronizing data: ' + error.message);
                })
                .finally(() => {
                    // Restore button state
                    syncBtn.innerHTML = originalText;
                    syncBtn.disabled = false;
                });
        }
        document.getElementById('sync-btn').addEventListener('click', handleSync);
    </script>
</body>

</html>