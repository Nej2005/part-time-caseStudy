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
    <title>Daily Time Record</title>
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

        /* added style */
        .action-buttons {
            display: flex;
            gap: 5px;
            /* Adjust the gap as needed */
            justify-content: center;
            /* Add this line to center the buttons */
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
            </div>

            <div class="sidebar-menu">
                <ul>
                    <li><a href="management.php"><i class="fas fa-users"></i> Employee Management</a></li>
                    <li><a href="emp-formload.php"><i class="fas fa-calendar-alt"></i> Form Loading</a></li>
                    <li><a href="#" class="active"><i class="fas fa-file-alt"></i> Daily Time Record</a></li>
                    <li><a href="emp-ar.php"><i class="fas fa-clock"></i> Accomplishment Report</a></li>
                    <li><a href="settings.php"><i class="fas fa-chalkboard-teacher"></i> Class Management</a></li>
                    <li id="bot-sidebar"><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>

                </ul>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <div class="content-header">
                <h1>Daily Time Record</h1>
                <ul class="breadcrumb">
                    <li>Dashboard</li>
                    <li>Daily Time Record</li>
                </ul>
            </div>

            <div class="controls-wrapper">
                <button class="toggle-sidebar-btn" id="toggle-sidebar-btn">
                    <span id="sidebar-icon">☰</span>
                    <button class="toggle-btn" id="close-sidebar"></button>

                </button>
                <div class="controls">
                    <button id="filterBtn" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center;">EMPLOYEE ID</th>
                            <th style="text-align: center;">EMPLOYEE NAME</th>
                            <th style="text-align: center;">PROGRESS NOTE</th>
                            <th style="text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="employee-table">
                        <?php foreach ($professors as $professor): ?>
                            <tr data-professor-id="<?php echo $professor['professor_id']; ?>"
                                data-first-name="<?php echo htmlspecialchars($professor['first_name']); ?>"
                                data-last-name="<?php echo htmlspecialchars($professor['last_name']); ?>">
                                <td><?php echo $professor['employee_id']; ?></td>
                                <td><?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?></td>
                                <td>
                                    <select class="progress-dropdown <?php echo $professor['progress_status'] ?? 'pending'; ?>">
                                        <option value="completed" <?php echo ($professor['progress_status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="in-progress" <?php echo ($professor['progress_status'] ?? '') == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="pending" <?php echo empty($professor['progress_status']) || ($professor['progress_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary" onclick="uploadDTR(<?php echo $professor['professor_id']; ?>)">
                                            <i class="fas fa-upload"></i> Upload DTR
                                        </button>
                                        <button class="btn btn-secondary" onclick="viewPreviousRecords(<?php echo $professor['professor_id']; ?>)">
                                            <i class="fas fa-file-alt"></i> View Previous Records
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="employeeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Create New Daily Time Record</h2>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <label for="email">Employee Email</label>
                    <input type="email" id="email" placeholder="Enter email address">
                </div>
                <div class="form-row">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" placeholder="Enter first name">
                </div>
                <div class="form-row">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" placeholder="Enter last name">
                </div>
                <div class="form-row">
                    <label for="minit">Middle Initial</label>
                    <input type="text" id="minit" placeholder="Enter middle initial">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn btn-submit" id="saveEmployeeBtn">Create New</button>
            </div>
        </div>
    </div>

    <script>
        function viewPreviousRecords(professorIdOrButton) {
            let professorId;

            // Check if we got a button element or professor ID
            if (typeof professorIdOrButton === 'object' && professorIdOrButton.closest) {
                // It's a button element
                const row = professorIdOrButton.closest("tr");
                const employeeId = row.cells[0].textContent;
                professorId = employeeId;
            } else {
                // It's a professor ID
                professorId = professorIdOrButton;
            }

            if (!professorId || professorId <= 0) {
                console.error("Invalid professor ID:", professorId);
                alert("Invalid professor ID");
                return;
            }

            console.log("Redirecting to DTR for professor ID:", professorId);
            window.location.href = "dtr.php?professor_id=" + encodeURIComponent(professorId);
        }

        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        const sidebarIcon = document.getElementById('sidebar-icon');
        const modal = document.getElementById("employeeModal");
        const saveEmployeeBtn = document.getElementById("saveEmployeeBtn");

        // Toggle sidebar functionality
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                sidebarIcon.textContent = '☰'; // Show menu icon when sidebar is closed
            } else {
                sidebarIcon.textContent = '☰'; // Keep menu icon when sidebar is open
            }
        }

        toggleSidebarBtn.addEventListener('click', toggleSidebar);
        closeSidebarBtn.addEventListener('click', toggleSidebar);


        function uploadDTR(professorId) {
            window.location.href = `createnew-dtr.php?professor_id=${professorId}`;
        }

        // Modal functions
        function openCreateNewModal() {
            const modalTitle = document.querySelector(".modal-header h2");
            modalTitle.innerHTML = '<i class="fas fa-plus"></i> Create New Daily Time Record';

            // Clear form inputs
            document.getElementById('email').value = '';
            document.getElementById('fname').value = '';
            document.getElementById('lname').value = '';
            document.getElementById('minit').value = '';

            // Change button text
            saveEmployeeBtn.textContent = 'Create New';

            // Display the modal
            modal.style.display = "flex";
        }

        function closeModal() {
            modal.style.display = "none";

            // Remove the editing class from any row that might have it
            const editingRow = document.querySelector("#employee-table tr.editing");
            if (editingRow) {
                editingRow.classList.remove("editing");
            }
        }

        let currentSortOrder = localStorage.getItem('employeeSortOrder') || 'desc';

        function initializeFilterButton() {
            const filterBtn = document.getElementById('filterBtn');
            if (currentSortOrder === 'desc') {
                filterBtn.innerHTML = '<i class="fas fa-sort-alpha-down-alt"></i> Filter (Z-A)';
            } else {
                filterBtn.innerHTML = '<i class="fas fa-sort-alpha-down"></i> Filter (A-Z)';
            }
        }

        function sortTableByLastName(event) {
            const tbody = document.getElementById('employee-table');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            if (event && event.type === 'click') {
                currentSortOrder = currentSortOrder === 'desc' ? 'asc' : 'desc';
                localStorage.setItem('employeeSortOrder', currentSortOrder);
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

            rows.forEach(row => {
                tbody.appendChild(row);
            });

            initializeFilterButton();
        }

        function applyCurrentSortOrder() {
            const fakeEvent = {
                type: 'load'
            };
            sortTableByLastName.call({}, fakeEvent);
        }

        document.getElementById('filterBtn').addEventListener('click', sortTableByLastName);

        function fetchEmployees() {
            // Store the original table content in case we need to restore it
            const tbody = document.getElementById('employee-table');
            const originalContent = tbody.innerHTML;

            fetch('../Backend/fetch_PTemployees.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!Array.isArray(data)) {
                        console.error('Invalid data format:', data);
                        throw new Error('Expected an array of employees');
                    }

                    // Only clear the table if we got valid data
                    tbody.innerHTML = '';

                    data.forEach(employee => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td>${employee.employee_id}</td>
                    <td>${employee.first_name} ${employee.last_name}</td>
                    <td>
                        <select class="progress-dropdown ${employee.progress_status || 'pending'}">
                            <option value="completed" ${employee.progress_status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="in-progress" ${employee.progress_status === 'in-progress' ? 'selected' : ''}>In Progress</option>
                            <option value="pending" ${!employee.progress_status || employee.progress_status === 'pending' ? 'selected' : ''}>Pending</option>
                        </select>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="uploadDTR(${employee.professor_id})">
                                <i class="fas fa-upload"></i> Upload DTR
                            </button>
                            <button class="btn btn-secondary" onclick="viewPreviousRecords(${employee.professor_id})">
                                <i class="fas fa-file-alt"></i> View Previous Records
                            </button>
                        </div>
                    </td>
                `;
                        tbody.appendChild(row);
                        attachRowEvents(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching employees:', error);
                    // Restore original content if fetch fails
                    tbody.innerHTML = originalContent;
                    // Re-attach event listeners to restored elements
                    document.querySelectorAll('.progress-dropdown').forEach(dropdown => {
                        updateDropdownStyle(dropdown);
                        dropdown.addEventListener('change', function() {
                            updateDropdownStyle(this);
                        });
                    });
                });
        }

        // Call this when page loads
        document.addEventListener('DOMContentLoaded', fetchEmployees);

        // Handle save button clicks (both add and update)
        saveEmployeeBtn.addEventListener("click", function() {
            const email = document.getElementById('email').value;
            const fname = document.getElementById('fname').value;
            const lname = document.getElementById('lname').value;
            const middleInitial = document.getElementById('minit').value;
            const fullName = fname + " " + (middleInitial ? middleInitial + " " : "") + lname;

            const isUpdate = this.textContent === "Update Employee";

            if (isUpdate) {
                // Handle update logic
                const row = document.querySelector("#employee-table tr.editing");
                if (row) {
                    row.cells[1].innerText = fullName;
                    row.cells[2].innerText = email;
                    row.classList.remove("editing");

                    // Here you would normally send this to the server
                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('first_name', fname);
                    formData.append('last_name', lname);
                    formData.append('middle_initial', middleInitial);

                    // Simulate successful update
                    alert("Employee updated successfully!");
                }
            } else {
                // Handle adding a new employee
                const tbody = document.getElementById("employee-table");
                const newRow = document.createElement("tr");

                // Generate a simple incremental ID (in production this would come from the server)
                const lastRow = tbody.lastElementChild;
                const lastId = lastRow ? parseInt(lastRow.cells[0].innerText) : 0;
                const newId = String(lastId + 1).padStart(3, '0');

                newRow.innerHTML = `
               <td>${newId}</td>
               <td>${fullName}</td>
                <td>        
                  <select class="progress-dropdown pending">
                     <option value="completed">Completed</option>  
                     <option value="in-progress">In Progress</option>
                     <option value="pending" selected>Pending</option>
                  </select>
               </td>
               <td>
                  <div class="action-buttons">
                    <button class="btn btn-primary" onclick="uploadDTR(<?php echo $professor['professor_id']; ?>)">
                                <i class="fas fa-upload"></i> Upload DTR
                            </button>
                    <button class="btn btn-secondary" onclick="viewPreviousRecords(<?php echo $professor['professor_id']; ?>)">
                            <i class="fas fa-file-alt"></i> View Previous Records
                    </button>
                  </div>
               </td>
            `;

                tbody.appendChild(newRow);
                attachRowEvents(newRow);

                alert("Daily Time Record Created successfully!");
            }

            closeModal();
        });

        function attachRowEvents(row) {
            const dropdown = row.querySelector(".progress-dropdown");
            if (dropdown) {
                // Set initial style
                updateDropdownStyle(dropdown);

                dropdown.addEventListener("change", function() {
                    updateDropdownStyle(this);
                });
            }
        }

        function updateDropdownStyle(dropdown) {
            dropdown.classList.remove('completed', 'in-progress', 'pending');

            const value = dropdown.value;
            if (value) {
                dropdown.classList.add(value);
            }
        }

        function updateDateTime() {
            const now = new Date();
            const dateTimeString = now.toLocaleString();
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilterButton();
            applyCurrentSortOrder();

            updateDateTime();
            setInterval(updateDateTime, 1000);

            document.querySelectorAll('.progress-dropdown').forEach(dropdown => {
                updateDropdownStyle(dropdown);
                dropdown.addEventListener('change', function() {
                    updateDropdownStyle(this);
                });
            });

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.getElementById('syncBtn').addEventListener('click', handleSync);
        });
    </script>
</body>

</html>