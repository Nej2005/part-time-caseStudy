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

$professors = [];
$sql = "SELECT p.*, u.employee_id 
        FROM PartTime_Professor p
        JOIN Users u ON p.email_address = u.email_address";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $professors[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee Management</title>
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

        /* Navigation bar styling */
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

        /* Table styling */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            width: 100%;
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

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            text-align: center;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* Form modal styling */
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

        #bot-sidebar {
            position: relative;
            top: 420px;
        }

        .form-row input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-row input:focus {
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

        /* Progress dropdown styling */
        .progress-dropdown {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
        }

        .progress-dropdown.completed {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .progress-dropdown.in-progress {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }

        .progress-dropdown.pending {
            background-color: #f8f9fa;
            color: #6c757d;
            border-color: #e9ecef;
        }

        .progress-dropdown option {
            background-color: white !important;
            color: black !important;
        }

        .form-row select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }

        .form-row select:focus {
            border-color: #2c5e1a;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 94, 26, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .controls {
                flex-wrap: wrap;
            }

            .btn {
                margin-bottom: 5px;
            }

            .table-container {
                overflow-x: auto;
            }
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
                <?php
                $profile_image = null;
                if (isset($_SESSION['user_id'])) {
                    include '../Backend/db_connect.php';
                    $user_id = $_SESSION['user_id'];
                    $user_type = $_SESSION['user_type'];

                    if ($user_type === 'Admin_Secretary') {
                        $stmt = $conn->prepare("SELECT image FROM Admin_Secretary WHERE admin_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $profile_image = $row['image'];
                            // Debug output
                            echo "<!-- Image found in database. Size: " . ($profile_image ? strlen($profile_image) : '0') . " bytes -->";
                        } else {
                            echo "<!-- No image found for admin_id: $user_id -->";
                        }
                    }
                    $conn->close();
                }
                ?>
                <img src="<?php
                            echo $profile_image ? 'data:image/jpeg;base64,' . base64_encode($profile_image) : '/api/placeholder/40/40';
                            ?>" alt="User Profile" />
            </div>
            <div class="dropdown-nav">
                <button class="dropdown-button-nav">
                    <?php
                    if (isset($_SESSION['last_name'], $_SESSION['first_name'])) {
                        echo htmlspecialchars($_SESSION['last_name'] . ', ' . $_SESSION['first_name']);
                    } else {
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
                    <li><a href="#" class="active"><i class="fas fa-users"></i> Employee Management</a></li>
                    <li><a href="emp-formload.php"><i class="fas fa-calendar-alt"></i> Form Loading</a></li>
                    <li><a href="emp-dtr.php"><i class="fas fa-file-alt"></i> Daily Time Record</a></li>
                    <li><a href="emp-ar.php"><i class="fas fa-clock"></i> Accomplishment Report</a></li>
                    <li><a href="settings.php"><i class="fas fa-chalkboard-teacher"></i> Class Management</a></li>
                    <li id="bot-sidebar"><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>

                </ul>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <div class="content-header">
                <h1>Employee Management</h1>
                <ul class="breadcrumb">
                    <li>Dashboard</li>
                    <li>Employee Management</li>
                </ul>
            </div>

            <div class="controls-wrapper">
                <button class="toggle-sidebar-btn" id="toggle-sidebar-btn">
                    <span id="sidebar-icon">☰</span></span>
                </button>
                <div class="controls">
                    <button id="filterBtn" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
                    <button id="exportBtn" class="btn btn-outline"><i class="fas fa-file-export"></i> Export</button>
                    <button class="btn btn-primary" onclick="openAddNewProfessorModal()"><i class="fas fa-plus"></i> Add
                        New</button>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center;">EMPLOYEE ID</th>
                            <th style="text-align: center;">PROFESSOR NAME</th>
                            <th style="text-align: center;">EMAIL ADDRESS</th>
                            <th style="text-align: center;">DEPARTMENT</th>
                            <th style="text-align: center;">PROGRESS NOTE</th>
                            <th style="text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="professor-table">
                        <?php foreach ($professors as $professor): ?>
                            <tr data-professor-id="<?php echo $professor['professor_id']; ?>"
                                data-first-name="<?php echo htmlspecialchars($professor['first_name']); ?>"
                                data-last-name="<?php echo htmlspecialchars($professor['last_name']); ?>"
                                data-middle-initial="<?php echo htmlspecialchars($professor['middle_initial'] ?? ''); ?>">
                                <td><?php echo $professor['employee_id']; ?></td>
                                <td><?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($professor['email_address']); ?></td>
                                <td><?php echo htmlspecialchars($professor['department'] ?? ''); ?></td>
                                <td>
                                    <select class="progress-dropdown <?php echo $professor['progress_status'] ?? 'pending'; ?>"
                                        data-professor-id="<?php echo $professor['professor_id']; ?>"
                                        onchange="updateProgressStatus(this)">
                                        <option value="completed" <?php echo ($professor['progress_status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="in-progress" <?php echo ($professor['progress_status'] ?? '') == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="pending" <?php echo empty($professor['progress_status']) || ($professor['progress_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-secondary" onclick="openUpdateModal(this)"><i class="fas fa-edit"></i> Update</button>
                                        <button class="btn btn-danger" onclick="deleteProfessor(this)"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="professorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Professor</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" placeholder="Enter email address" required>
                </div>
                <div class="form-row">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" placeholder="Enter first name" required>
                </div>
                <div class="form-row">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" placeholder="Enter last name" required>
                </div>
                <div class="form-row">
                    <label for="minit">Middle Initial</label>
                    <input type="text" id="minit" placeholder="Enter middle initial" maxlength="1">
                </div>
                <div class="form-row">
                    <label for="department">Department</label>
                    <select id="department" required>
                        <option value="">Select Department</option>
                        <option value="College of Nursing">College of Nursing</option>
                        <option value="College of Engineering">College of Engineering</option>
                        <option value="College of Education">College of Education</option>
                        <option value="College of Computer Studies">College of Computer Studies</option>
                        <option value="College of Arts and Science">College of Arts and Science</option>
                        <option value="College of Business and Accountancy">College of Business and Accountancy</option>
                        <option value="College of Hospitality Management">College of Hospitality Management</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-submit" id="saveProfessorBtn">Add Professor</button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebarIcon = document.getElementById('sidebar-icon');
        const modal = document.getElementById("professorModal");
        const saveProfessorBtn = document.getElementById("saveProfessorBtn");

        // Toggle sidebar functionality
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            sidebarIcon.textContent = sidebar.classList.contains('collapsed') ? '☰' : '☰';
        }

        // Initialize event listeners
        toggleSidebarBtn.addEventListener('click', toggleSidebar);
        closeSidebarBtn.addEventListener('click', toggleSidebar);

        // Modal functions
        function openAddNewProfessorModal() {
            document.querySelector(".modal-header h2").innerHTML = '<i class="fas fa-user-plus"></i> Add New Professor';
            document.getElementById('email').value = '';
            document.getElementById('fname').value = '';
            document.getElementById('lname').value = '';
            document.getElementById('minit').value = '';
            saveProfessorBtn.textContent = 'Add Professor';
            saveProfessorBtn.onclick = saveProfessor;
            modal.style.display = "flex";
        }

        function openUpdateModal(button) {
            const row = button.closest("tr");
            document.querySelector(".modal-header h2").innerHTML = '<i class="fas fa-user-edit"></i> Update Professor';

            // Get data from row attributes
            const email = row.cells[2].innerText.trim();
            const professorId = row.getAttribute('data-professor-id');
            const department = row.cells[3].innerText.trim();
            const middleInitial = row.dataset.middleInitial || '';

            // Populate form fields
            document.getElementById('email').value = email;
            document.getElementById('fname').value = row.dataset.firstName || '';
            document.getElementById('lname').value = row.dataset.lastName || '';
            document.getElementById('minit').value = middleInitial;
            document.getElementById('department').value = department;

            saveProfessorBtn.textContent = 'Update Professor';
            saveProfessorBtn.onclick = function() {
                updateProfessor(row);
            };
            row.classList.add("editing");
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";
            const editingRow = document.querySelector("#professor-table tr.editing");
            if (editingRow) editingRow.classList.remove("editing");
        }


        let currentSortOrder = localStorage.getItem('professorSortOrder') || 'desc';

        // Function to initialize the filter button appearance
        function initializeFilterButton() {
            const filterBtn = document.getElementById('filterBtn');
            if (currentSortOrder === 'desc') {
                filterBtn.innerHTML = '<i class="fas fa-sort-alpha-down-alt"></i> Filter (Z-A)';
            } else {
                filterBtn.innerHTML = '<i class="fas fa-sort-alpha-down"></i> Filter (A-Z)';
            }
        }

        // Function to sort the table by last name
        function sortTableByLastName() {
            const tbody = document.getElementById('professor-table');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            if (event && event.type === 'click') {
                currentSortOrder = currentSortOrder === 'desc' ? 'asc' : 'desc';
                localStorage.setItem('professorSortOrder', currentSortOrder);
            }

            // Sort the rows based on last name from data attribute
            rows.sort((rowA, rowB) => {
                const lastNameA = rowA.dataset.lastName.toLowerCase();
                const lastNameB = rowB.dataset.lastName.toLowerCase();

                if (currentSortOrder === 'desc') {
                    return lastNameB.localeCompare(lastNameA);
                } else {
                    return lastNameA.localeCompare(lastNameB);
                }
            });

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }

            // Add the sorted rows back
            rows.forEach(row => {
                tbody.appendChild(row);
            });

            // Update the filter button appearance
            initializeFilterButton();
        }

        function applyCurrentSortOrder() {
            const fakeEvent = {
                type: 'load'
            };
            sortTableByLastName.call({}, fakeEvent);
        }

        // Add event listener for the filter button
        document.getElementById('filterBtn').addEventListener('click', sortTableByLastName);

        // Professor CRUD functions
        function saveProfessor() {
            const email = document.getElementById('email').value;
            const fname = document.getElementById('fname').value;
            const lname = document.getElementById('lname').value;
            const middleInitial = document.getElementById('minit').value;
            const department = document.getElementById('department').value;

            // Validate required fields
            if (!email || !fname || !lname || !department) {
                alert("Please fill in all required fields");
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address");
                return;
            }

            // Validate names - allow letters, spaces, hyphens, and apostrophes
            const nameRegex = /^[a-zA-Z\s\-']+$/;

            if (!nameRegex.test(fname)) {
                alert("First name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.");
                return;
            }

            if (!nameRegex.test(lname)) {
                alert("Last name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.");
                return;
            }

            // Validate middle initial (optional)
            if (middleInitial && !/^[a-zA-Z]$/.test(middleInitial)) {
                alert("Middle initial must be a single letter");
                return;
            }

            // Create form data object
            const formData = new FormData();
            formData.append('email', email);
            formData.append('first_name', fname.trim());
            formData.append('last_name', lname.trim());
            formData.append('middle_initial', middleInitial.trim());
            formData.append('department', department);

            saveProfessorBtn.disabled = true;
            saveProfessorBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            fetch('../Backend/admin_EmpManagement.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Create new row in the table using employee_id from response
                    const tbody = document.getElementById("professor-table");
                    const newRow = document.createElement("tr");
                    newRow.setAttribute('data-professor-id', data.professor_id);
                    newRow.innerHTML = `
                        <td>${data.employee_id}</td>
                        <td>${data.employee_name}</td>
                        <td>${data.employee_email}</td>
                        <td>${data.department}</td>
                        <td>
                            <select class="progress-dropdown pending" data-professor-id="${data.professor_id}" onchange="updateProgressStatus(this)">
                                <option value="completed">Completed</option>
                                <option value="in-progress">In Progress</option>
                                <option value="pending" selected>Pending</option>
                            </select>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-secondary" onclick="openUpdateModal(this)"><i class="fas fa-edit"></i> Update</button>
                                <button class="btn btn-danger" onclick="deleteProfessor(this)"><i class="fas fa-trash-alt"></i> Delete</button>
                            </div>
                        </td>
                    `;

                    tbody.appendChild(newRow);
                    saveProgressStatus(data.professor_id, 'pending');
                    alert("Professor added successfully! Credentials sent in Email.");
                    closeModal();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error adding professor: " + error.message);
                })
                .finally(() => {
                    saveProfessorBtn.disabled = false;
                    saveProfessorBtn.innerHTML = 'Add Professor';
                });
        }

        function saveProgressStatus(professorId, status) {
            console.log(`Saved progress status for ${professorId}: ${status}`);
        }

        function updateProfessor(row) {
            const email = document.getElementById('email').value;
            const fname = document.getElementById('fname').value;
            const lname = document.getElementById('lname').value;
            const minit = document.getElementById('minit').value;
            const professorId = row.getAttribute('data-professor-id');
            const department = document.getElementById('department').value;

            // Validate required fields
            if (!email || !fname || !lname || !department) {
                alert("Please fill in all required fields");
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address");
                return;
            }

            const nameRegex = /^[a-zA-Z\s\-']+$/;

            if (!nameRegex.test(fname)) {
                alert("First name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.");
                return;
            }

            if (!nameRegex.test(lname)) {
                alert("Last name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.");
                return;
            }

            // Validate middle initial (optional)
            if (minit && !/^[a-zA-Z]$/.test(minit)) {
                alert("Middle initial must be a single letter");
                return;
            }

            saveProfessorBtn.disabled = true;
            saveProfessorBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            fetch('../Backend/update_PTemployees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        professor_id: professorId,
                        email: email.trim(),
                        first_name: fname.trim(),
                        last_name: lname.trim(),
                        middle_initial: minit.trim(),
                        department: department
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.changes) {
                            const displayName = minit ?
                                `${fname.trim()} ${minit.trim()}. ${lname.trim()}` :
                                `${fname.trim()} ${lname.trim()}`;

                            row.cells[1].textContent = displayName;
                            row.cells[2].textContent = email.trim();
                            row.cells[3].textContent = department;

                            // Update the data attributes
                            row.dataset.firstName = fname.trim();
                            row.dataset.lastName = lname.trim();

                            alert("Professor updated successfully!");
                        } else {
                            alert("No changes were made.");
                        }
                        row.classList.remove("editing");
                        closeModal();
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error updating professor: " + error.message);
                })
                .finally(() => {
                    saveProfessorBtn.disabled = false;
                    saveProfessorBtn.textContent = 'Update Professor';
                });
        }

        async function updateProgressStatus(dropdown) {
            const professorId = dropdown.getAttribute('data-professor-id');
            const newStatus = dropdown.value;
            const previousStatus = dropdown.dataset.previousStatus || dropdown.value;

            dropdown.dataset.previousStatus = previousStatus;

            try {
                dropdown.disabled = true;

                const response = await fetch('../Backend/save_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        professor_id: professorId,
                        status: newStatus
                    })
                });

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Server reported failure');

                // Remove previous classes
                dropdown.classList.remove('completed', 'in-progress', 'pending');

                // Add new class only to <select>, not <option>
                dropdown.classList.add(newStatus);
                dropdown.dataset.previousStatus = newStatus;

                if (typeof saveProgressStatus === 'function') {
                    saveProgressStatus(professorId, newStatus);
                }

            } catch (error) {
                console.error('Update failed:', error);
                dropdown.value = previousStatus;

                if (!error.message.includes('HTTP error') && !error.message.includes('Server reported failure')) {
                    alert('Failed to save progress: ' + error.message);
                }
            } finally {
                dropdown.disabled = false;
            }
        }


        function deleteProfessor(button) {
            const row = button.closest("tr");
            const professorId = row.getAttribute('data-professor-id');

            if (confirm("Are you sure you want to delete this professor?")) {
                // Show loading state on the button
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                button.disabled = true;

                // Make AJAX call to delete endpoint
                fetch('../Backend/delete_PTemployees.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            professor_id: professorId
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            alert("Professor deleted successfully!");
                        } else {
                            throw new Error(data.message || 'Failed to delete professor');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("Error deleting professor: " + error.message);
                        // Restore button state
                        button.innerHTML = originalText;
                        button.disabled = false;
                    });
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilterButton();
            applyCurrentSortOrder();

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) closeModal();
            });
        });

        async function exportToExcel() {
            const exportBtn = document.getElementById('exportBtn');
            const originalText = exportBtn.innerHTML;

            try {
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
                exportBtn.disabled = true;

                // Get the current month and year for the filename
                const now = new Date();
                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                const currentMonth = monthNames[now.getMonth()];
                const currentYear = now.getFullYear();
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
                const monthRange = `${currentMonth} 1-${lastDay}, ${currentYear}`;

                // Prepare export data
                const exportData = {
                    department: "College of Computer Studies",
                    monthRange: monthRange,
                    employees: []
                };

                // Get all professor rows from the table
                const rows = document.querySelectorAll('#professor-table tr');
                rows.forEach((row, index) => {
                    const cells = row.querySelectorAll('td');
                    exportData.employees.push({
                        employeeId: cells[0].textContent.trim(),
                        name: cells[1].textContent.trim(),
                        email: cells[2].textContent.trim()
                    });
                });

                // Send export request
                const response = await fetch('../Backend/Exporting/export_employees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(exportData)
                });

                // Check if response is an Excel file
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                    // Handle Excel file download
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `EmployeeManagement_${currentMonth}_${currentYear}.xlsx`;
                    document.body.appendChild(a);
                    a.click();

                    // Clean up
                    setTimeout(() => {
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    }, 100);
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Export failed');
                }
            } catch (error) {
                console.error('Export error:', error);
                alert('Export failed: ' + error.message);
            } finally {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }
        }

        document.getElementById('exportBtn').addEventListener('click', exportToExcel);
    </script>
</body>

</html>