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
    @import url('https://fonts.googleapis.com/css2?family=Gidole&display=swap');

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
      margin-top: 20px;
      margin-bottom: -10px;
      text-align: center;
      position: relative;
    }

    .content-header h1 {
      font-size: 45px;
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

    /* Hide content sections by default */
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

    .container-msg {
      background-color: #ddd;
      width: 100%;
      height: 100%;
      margin: auto;
      max-width: 1200px;
      max-height: 500px;
      box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.2);
      border-radius: 20px;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .container-msg:hover {
      transform: translateY(-25px) scale(1);
      box-shadow: 0px 12px 24px rgba(0, 0, 0, 0.5);
    }

    .about-text {
      font-family: "Gidole", sans-serif;
      text-align: center;
      margin-left: 100px;
      margin-right: 100px;
      padding-top: 70px;
      line-height: 35px;
      font-size: 25px;
      font-weight: 300;
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
          <li><a href="settings.php"><i class="fas fa-chalkboard-teacher"></i> Class Management</a></li>
          <li id="bot-sidebar"><a href="about.php" class="active"><i class="fas fa-info-circle"></i> About us</a></li>


        </ul>
      </div>
    </div>

    <div class="main-content" id="main-content">
      <div class="content-header">
        <h1>About</h1>
      </div>

      <div class="controls-wrapper">
        <button class="toggle-sidebar-btn" id="toggle-sidebar-btn">
          <span id="sidebar-icon">☰</span> <span id="sidebar-text"></span>
        </button>
      </div>
      <div class="container-msg">
        <h3 class="about-text">The Part-Time Faculty Salary Processing System is a web-based platform designed to automate and streamline faculty record management at Pamantasan ng Lungsod ng Pasig. It digitizes Daily Time Records (DTR) with OCR scanning, generates Accomplishment Reports (AR), and manages Faculty Loading Forms, replacing manual processes with an efficient digital workflow. The system features role-based access for admins and part-time faculty, automated time/load calculations, digital approvals, and PDF report generation—reducing errors, saving time, and ensuring accurate payroll processing. Developed using PHP, MySQL, and JavaScript, it provides a secure, transparent solution for managing part-time faculty records.</h3>
      </div>

    </div>
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
    }
  </script>
</body>

</html>