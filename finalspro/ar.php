<?php
session_start();
include '../Backend/db_connect.php';

// Check if professor_id is provided
if (!isset($_GET['professor_id'])) {
  header("Location: emp-ar.php");
  exit();
}

function convertSemesterText($semester)
{
  $conversions = [
    '1st Semester' => 'First Semester',
    '2nd Semester' => 'Second Semester',
    'Summer' => 'Summer'
  ];
  return $conversions[$semester] ?? $semester;
}

function cleanSubjectDescription($description)
{
  $cleaned = preg_replace_callback('/\((?!LAB|LEC)([^)]*)\)/', function ($matches) {
    return '';
  }, $description);
  $cleaned = trim($cleaned);
  $cleaned = preg_replace('/\s+/', ' ', $cleaned);
  return $cleaned;
}

$semester = convertSemesterText($ar_header['semester'] ?? 'First Semester');

$professor_id = $_GET['professor_id'];

// Get current month and year
$currentMonth = date('F');
$currentYear = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, date('n'), $currentYear);
$month_display = "$currentMonth 1-$days_in_month, $currentYear";

// Fetch AR Header data for current month/year if exists
$stmt_header = $conn->prepare("SELECT * FROM AR_Header 
                             WHERE professor_id = ? 
                             AND month = ? 
                             AND year = ?");
$stmt_header->bind_param("iss", $professor_id, $currentMonth, $currentYear);
$stmt_header->execute();
$header_result = $stmt_header->get_result();
$ar_header = $header_result->fetch_assoc();

// Fetch professor details
$stmt_prof = $conn->prepare("SELECT p.*, u.employee_id 
                           FROM PartTime_Professor p
                           JOIN Users u ON p.email_address = u.email_address
                           WHERE p.professor_id = ?");
$stmt_prof->bind_param("i", $professor_id);
$stmt_prof->execute();
$prof_result = $stmt_prof->get_result();

if ($prof_result->num_rows === 0) {
  header("Location: emp-ar.php");
  exit();
}

$professor = $prof_result->fetch_assoc();

// Department mapping and head
$department_mapping = [
  'College of Computer Studies' => 'computer-studies',
  'College of Nursing' => 'nursing',
  'College of Engineering' => 'engineering',
  'College of Education' => 'education',
  'College of Arts and Science' => 'arts-science',
  'College of Business and Accountancy' => 'business-accountancy',
  'College of Hospitality Management' => 'hospitality-management',
];

$db_department = isset($department_mapping[$professor['department']])
  ? $department_mapping[$professor['department']]
  : $professor['department'];

$stmt_dept = $conn->prepare("SELECT department_head FROM Courses WHERE department = ? LIMIT 1");
$stmt_dept->bind_param("s", $db_department);
$stmt_dept->execute();
$result_dept = $stmt_dept->get_result();
$department_head = "Dean/Dept. Head name";
if ($result_dept->num_rows > 0) {
  $dept_data = $result_dept->fetch_assoc();
  $department_head = $dept_data['department_head'];
}

// Fetch AR Details if header exists
$ar_details = [];
if ($ar_header) {
  $stmt_details = $conn->prepare("SELECT * FROM AR_Details WHERE ar_id = ?");
  $stmt_details->bind_param("i", $ar_header['ar_id']);
  $stmt_details->execute();
  $details_result = $stmt_details->get_result();
  $ar_details = $details_result->fetch_all(MYSQLI_ASSOC);
}

// Calculate total hours
$total_hours = 0;
if ($ar_details) {
  foreach ($ar_details as $detail) {
    $total_hours += $detail['hours_rendered'];
  }
}

// Set semester and academic year
$semester = $ar_header['semester'] ?? 'First Semester';
$academic_year = $ar_header['academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
$period = $ar_header['period'] ?? $month_display;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Part-Time Faculty Accomplishment Report</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", sans-serif;
      line-height: 1.6;
      background-color: #f5f5f5;
      color: #333;
      overflow-x: hidden;
      display: flex;
      justify-content: center;
    }

    .content-area {
      transition: margin-left 0.3s ease;
      flex: 1;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      margin-left: 0;
    }

    body.sidebar-active .content-area {
      margin-left: 250px;
    }

    header {
      background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
      color: white;
      padding: 15px 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 100%;
      position: relative;
    }

    .form-container {
      background-color: #f5f5f5;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: relative;
      max-width: 800px;
      width: 100%;
      margin: 20px auto;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .logo {
      width: 60px;
      height: 60px;
      background-color: #f0f0f0;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .back-btn {
      position: fixed;
      left: 10px;
      top: 10px;
      background-color: #2c5e1a;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      z-index: 999;
    }

    .title-section {
      text-align: center;
      margin-bottom: 20px;
    }

    .title-section h1 {
      font-size: 24px;
      margin-bottom: 0;
    }

    .title-section h2 {
      font-size: 18px;
      margin-top: 5px;
      font-weight: normal;
    }

    .title-section h3 {
      font-size: 16px;
      margin-top: 5px;
      font-weight: normal;
    }

    .info-section {
      margin-bottom: 20px;
    }

    .info-row {
      display: flex;
      margin-bottom: 10px;
    }

    .info-label {
      width: 150px;
      font-weight: bold;
    }

    .info-input {
      flex: 1;
      border-bottom: 1px solid #000;
    }

    input,
    select {
      width: 100%;
      border: none;
      border-bottom: 1px solid #999;
      padding: 5px 0;
      font-size: 14px;
      outline: none;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      font-size: 14px;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 6px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    .signature-section {
      margin-top: 40px;
      font-size: 14px;
      page-break-inside: avoid;
    }

    .signature-section>div:first-child {
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 16px;
    }

    .signature-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 80px;
    }

    .signature-box {
      width: 45%;
      text-align: center;
    }

    .signature-line {
      border-bottom: 1px solid #000;
      height: 80px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .signature-line img {
      max-width: 150px !important;
      max-height: 80px !important;
      display: block;
      margin: 0 auto;
      object-fit: contain;
    }

    .signature-box input {
      font-size: 13px;
      border: none !important;
      text-align: center;
      font-weight: bold;
    }

    .checked-by-section {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      page-break-inside: avoid;
    }

    .checked-by-line {
      width: 300px;
      margin: 10px auto 5px auto;
      border-bottom: 1px solid #000;
      height: 30px;
    }

    .checked-by-name {
      text-align: center;
      width: 300px;
      margin: 0 auto;
      display: block;
      border: none;
      background: transparent;
      font-weight: bold;
      font-size: 16px;
    }

    .green-header {
      background-color: #2c5e1a;
      color: white;
      padding: 8px 15px;
      font-weight: bold;
      text-align: center;
    }

    .ar-input {
      width: 100%;
      padding: 6px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 0.9em;
    }

    .ar-input:disabled {
      background-color: #f9f9f9;
      border: none;
      cursor: not-allowed;
    }

    .ar-table th {
      background-color: #3b5525;
      color: white;
      padding: 8px;
      text-align: left;
      font-size: 0.9em;
    }

    .ar-table td {
      padding: 8px;
      border-bottom: 1px solid #ddd;
    }

    .ar-table th:nth-child(3),
    .ar-table td:nth-child(3) {
      width: 150px;
    }

    .ar-table tr:last-child td {
      border-bottom: none;
    }

    .ar-table tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    .ar-table tfoot {
      font-weight: bold;
      background-color: #f0f0f0;
    }

    .hours-rendered-input {
      width: 100%;
    }

    #uploadStatus {
      margin-top: 10px;
      font-size: 0.9em;
      color: green;
    }

    #uploadStatus.error {
      color: red;
    }

    .image-preview-container {
      margin-top: 20px;
      text-align: center;
    }

    #imagePreview {
      max-width: 200px;
      max-height: 200px;
      border: 1px solid #ccc;
      border-radius: 4px;
      display: none;
      margin-top: 10px;
    }

    .sidebar {
      position: fixed;
      left: -250px;
      top: 0;
      width: 250px;
      height: 100%;
      background-color: #e9e7c0;
      z-index: 1000;
      transition: all 0.3s ease;
      box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
      overflow-y: auto;
      padding-top: 70px;
    }

    .sidebar.active {
      left: 0;
    }

    .sidebar-content {
      padding: 20px;
    }

    .sidebar-header {
      text-align: center;
      font-weight: 600;
      color: #3b5525;
      padding: 10px 0;
      margin-bottom: 20px;
      border-bottom: 2px solid #d0c74f;
    }

    .toggle-btn {
      position: fixed;
      left: 0px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 60px;
      background-color: #3b5525;
      display: flex;
      align-items: center;
      justify-content: center;
      border-top-right-radius: 8px;
      border-bottom-right-radius: 8px;
      cursor: pointer;
      z-index: 1001;
      transition: all 0.3s ease;
    }

    .toggle-btn:hover {
      background-color: #d0c74f;
    }

    .toggle-btn.active {
      left: 250px;
    }

    .sidebar.active .toggle-btn {
      left: 250px;
    }

    body:not(.sidebar-active) .toggle-btn {
      left: 0;
    }

    .toggle-icon {
      color: white;
      font-size: 18px;
    }

    .year-selector {
      margin-bottom: 20px;
    }

    .year-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #3b5525;
    }

    select.year-dropdown {
      width: 100%;
      padding: 8px;
      border: 1px solid #d0d0d0;
      border-radius: 5px;
      background-color: white;
      font-family: "Poppins", sans-serif;
      margin-bottom: 15px;
      cursor: pointer;
    }

    select.month-dropdown {
      width: 100%;
      padding: 8px;
      border: 1px solid #d0d0d0;
      border-radius: 5px;
      background-color: white;
      font-family: "Poppins", sans-serif;
      margin-bottom: 15px;
      cursor: pointer;
    }

    .section-header {
      font-weight: 600;
      color: #3b5525;
      margin: 20px 0 10px 0;
      padding-bottom: 5px;
      border-bottom: 1px solid #d0c74f;
    }

    .semester-card {
      background-color: white;
      border-radius: 8px;
      margin-bottom: 15px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .semester-title {
      text-align: center;
      padding: 15px 10px;
      font-weight: 600;
      color: #333;
    }

    .view-btn {
      display: block;
      text-align: center;
      background-color: #f0f0f0;
      padding: 8px;
      color: #3b5525;
      text-decoration: none;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .view-btn:hover {
      background-color: #d0c74f;
      color: #1a2a0d;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      transition: margin-left 0.3s ease;
      flex: 1;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo-container {
      display: flex;
      align-items: center;
    }

    .logo {
      width: 50px;
      height: 50px;
      margin-right: 15px;
    }

    .school-name {
      font-family: "Montserrat", sans-serif;
      font-weight: 600;
      font-size: 1.2rem;
    }

    .date-time {
      font-family: "Montserrat", sans-serif;
      font-weight: 300;
      font-size: 0.9rem;
      text-align: right;
    }

    .back-button {
      background-color: white;
      color: #3b5525;
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      cursor: pointer;
      margin-right: 15px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      transition: all 0.2s ease;
    }

    .back-button:hover {
      background-color: #f0f0f0;
      transform: translateY(-2px);
    }

    .back-to-current {
      display: block;
      background-color: #3b5525;
      color: white;
      border: none;
      border-radius: 5px;
      padding: 10px;
      text-align: center;
      margin-top: 20px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
      margin-left: 0;
    }

    .back-to-current:hover {
      background-color: #1a2a0d;
    }

    /* Button Styles */
    .action-button {
      background-color: #2c5e1a;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      margin-right: 10px;
      transition: background-color 0.2s ease;
    }

    .action-button:hover {
      background-color: #1a3e0a;
    }

    .button-icon {
      margin-right: 8px;
    }

    .button-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 30px;
    }

    .button-actions-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }

    .button-group-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .action-button {
      background-color: #2c5e1a;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      transition: background-color 0.2s ease;
      font-size: 13px;
    }

    .action-button:hover {
      background-color: #1a3e0a;
    }

    .button-icon {
      margin-right: 8px;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
      width: 400px;
      max-width: 90%;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      position: absolute;
      top: 10px;
      right: 10px;
    }

    .modal-header {
      position: relative;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-footer {
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-cancel {
      background-color: #f1f1f1;
      color: #333;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .btn-submit {
      background-color: #3b5525;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .content-area {
        margin-left: 0;
      }

      body.sidebar-open .content-area {
        transform: translateX(250px);
        margin-left: 0;
      }

      .sidebar {
        left: -250px;
      }

      .sidebar.active {
        left: 0;
      }

      .toggle-btn {
        left: 0;
      }

      .form-container {
        padding: 15px;
      }

      .info-label {
        width: 120px;
      }

      .button-container {
        flex-direction: column;
      }

      .action-button {
        margin-bottom: 10px;
        margin-right: 0;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar" id="sidebar">
    <div class="toggle-btn" id="sidebar-toggle">
      <span class="toggle-icon" id="toggle-icon">❯</span>
    </div>
    <div class="sidebar-content">
      <div class="sidebar-header">View Previous</div>
      <div class="section-header">Regular AR</div>
      <div class="year-selector">
        <label class="year-label">Select Year:</label>
        <select class="year-dropdown" id="year-dropdown-nav">
          <?php
          // Reconnect to database if needed
          include '../Backend/db_connect.php';

          // Query to get distinct years that have AR with head_signature
          $yearQuery = "SELECT DISTINCT year FROM AR_Header 
                             WHERE professor_id = ? 
                             AND head_signature IS NOT NULL
                             ORDER BY year DESC";
          $stmt = $conn->prepare($yearQuery);
          $stmt->bind_param("i", $professor_id);
          $stmt->execute();
          $yearResult = $stmt->get_result();

          $availableYears = [];
          while ($row = $yearResult->fetch_assoc()) {
            $availableYears[] = $row['year'];
          }

          // Always include current year if not already present
          if (!in_array($currentYear, $availableYears)) {
            array_unshift($availableYears, $currentYear);
          }

          foreach ($availableYears as $year) {
            $selected = ($year == $currentYear) ? 'selected' : '';
            echo "<option value='$year' $selected>$year</option>";
          }
          ?>
        </select>
        <label class="month-label">Select Month:</label>
        <select class="month-dropdown" id="month-dropdown-nav">
          <?php
          // Query to get distinct months that have AR with head_signature for the current year
          $monthQuery = "SELECT DISTINCT month FROM AR_Header 
                               WHERE professor_id = ? 
                               AND year = ?
                               AND head_signature IS NOT NULL
                               ORDER BY FIELD(month, 
                                   'January', 'February', 'March', 'April', 
                                   'May', 'June', 'July', 'August', 
                                   'September', 'October', 'November', 'December')";
          $stmt = $conn->prepare($monthQuery);
          $stmt->bind_param("is", $professor_id, $currentYear);
          $stmt->execute();
          $monthResult = $stmt->get_result();

          $availableMonths = [];
          while ($row = $monthResult->fetch_assoc()) {
            $availableMonths[] = $row['month'];
          }

          // Always include current month if not already present
          if (!in_array($currentMonth, $availableMonths)) {
            array_unshift($availableMonths, $currentMonth);
          }

          $monthNames = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
          ];

          foreach ($monthNames as $month) {
            if (in_array($month, $availableMonths)) {
              $selected = ($month == $currentMonth) ? 'selected' : '';
              echo "<option value='$month' $selected>$month</option>";
            }
          }
          ?>
        </select>
      </div>
    </div>
  </div>

  <div class="content-area">
    <header>
      <div class="header-content">
        <div class="logo-container">
          <button class="back-button" onclick="window.history.back()">
            ←
          </button>
          <img alt="School Logo" class="logo" src="Logo.ico" />
          <div class="school-name">Faculty Portal</div>
        </div>
        <div class="date-time" id="date"></div>
      </div>
    </header>

    <div class="form-container">
      <div class="title-section">
        <h1>PART-TIME FACULTY</h1>
        <h2>ACCOMPLISHMENT REPORT</h2>
        <h3>
          <?php echo htmlspecialchars($semester); ?> A.Y.
          <input
            type="text"
            value="<?php echo htmlspecialchars($academic_year); ?>"
            style="width: 100px; text-align: center"
            readonly />
        </h3>
        <p>
          For the Month of
          <input
            type="text"
            value="<?php echo htmlspecialchars($period); ?>"
            style="width: 200px; text-align: center"
            readonly />
        </p>
      </div>

      <div class="info-section">
        <div class="info-row">
          <div class="info-label">Name of Faculty</div>
          <div class="info-input">
            <input type="text" value="<?php echo htmlspecialchars($professor['first_name'] . ' ' .
                                        (!empty($professor['middle_initial']) ? htmlspecialchars($professor['middle_initial']) . '. ' : '')) .
                                        htmlspecialchars($professor['last_name']); ?>" readonly />
          </div>
        </div>
        <div class="info-row">
          <div class="info-label">College/ Dept.</div>
          <div class="info-input">
            <input type="text" value="<?php echo htmlspecialchars($professor['department']); ?>" readonly />
          </div>
        </div>
        <div class="info-row">
          <div class="info-label">No. of Units</div>
          <div class="info-input">
            <input type="number" value="<?php echo htmlspecialchars($ar_header['no_of_units'] ?? '0'); ?>" readonly />
          </div>
        </div>
      </div>

      <table class="ar-table">
        <thead>
          <tr>
            <th>Subject Description</th>
            <th>No. of Units</th>
            <th>Inclusive Dates</th>
            <th>Class Time Schedule</th>
            <th>Actual Total number of hours rendered</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($ar_details)): ?>
            <?php foreach ($ar_details as $detail): ?>
              <tr>
                <td>
                  <input
                    type="text"
                    value="<?php echo htmlspecialchars($detail['subject_description']); ?>"
                    class="ar-input"
                    readonly />
                </td>
                <td>
                  <input
                    type="number"
                    value="<?php echo htmlspecialchars($detail['no_of_units']); ?>"
                    style="width: 40px"
                    class="ar-input unit-input"
                    readonly />
                </td>
                <td>
                  <input
                    type="text"
                    value="<?php echo htmlspecialchars($detail['inclusive_dates']); ?>"
                    class="ar-input"
                    readonly />
                </td>
                <td>
                  <input
                    type="text"
                    value="<?php echo htmlspecialchars($detail['class_time_schedule']); ?>"
                    class="ar-input"
                    readonly />
                </td>
                <td>
                  <input
                    type="text"
                    value="<?php echo htmlspecialchars($detail['day_of_week']); ?>"
                    class="ar-input"
                    style="width: 60%; display: inline-block"
                    readonly />
                  <input
                    type="number"
                    value="<?php echo htmlspecialchars($detail['hours_rendered']); ?>"
                    class="ar-input hours-rendered-input"
                    style="width: 30%; display: inline-block"
                    readonly />
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td><input type="text" class="ar-input" readonly /></td>
              <td>
                <input
                  type="number"
                  style="width: 40px"
                  class="ar-input unit-input"
                  readonly />
              </td>
              <td><input type="text" class="ar-input" readonly /></td>
              <td><input type="text" class="ar-input" readonly /></td>
              <td>
                <input
                  type="text"
                  class="ar-input"
                  style="width: 60%; display: inline-block"
                  readonly />
                <input
                  type="number"
                  class="ar-input hours-rendered-input"
                  style="width: 30%; display: inline-block"
                  readonly />
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="text-align: right">
              <strong>TOTAL:</strong>
            </td>
            <td>
              <input type="text" class="ar-input" id="total-hours" value="<?php echo htmlspecialchars($total_hours); ?>" readonly />
            </td>
          </tr>
        </tfoot>
      </table>

      <div class="signature-section">
        <div>Certified by:</div>

        <div class="signature-row">
          <div class="signature-box">
            <?php if (!empty($ar_header['faculty_signature'])): ?>
              <?php
              if (strpos($ar_header['faculty_signature'], 'data:image/png;base64,') === 0) {
                $signatureSrc = $ar_header['faculty_signature'];
              } else {
                $signatureSrc = 'data:image/png;base64,' . $ar_header['faculty_signature'];
              }
              ?>
              <div class="signature-line">
                <img src="<?php echo htmlspecialchars($signatureSrc); ?>" alt="Faculty Signature">
              </div>
            <?php else: ?>
              <div class="signature-line"></div>
            <?php endif; ?>
            <input type="text" id="faculty-name-display" value="<?php
                                                                echo htmlspecialchars($professor['first_name']) . ' ' .
                                                                  (!empty($professor['middle_initial']) ? htmlspecialchars($professor['middle_initial']) . '. ' : '') .
                                                                  htmlspecialchars($professor['last_name']);
                                                                ?>" class="signature-name" readonly />
            <div>Faculty printed name Over Signature</div>
          </div>

          <div class="signature-box">
            <?php if (!empty($ar_header['head_signature'])): ?>
              <?php
              if (strpos($ar_header['head_signature'], 'data:image/png;base64,') === 0) {
                $signatureSrc = $ar_header['head_signature'];
              } else {
                $signatureSrc = 'data:image/png;base64,' . $ar_header['head_signature'];
              }
              ?>
              <div class="signature-line">
                <img src="<?php echo htmlspecialchars($signatureSrc); ?>" alt="Head Signature">
              </div>
            <?php else: ?>
              <div class="signature-line"></div>
            <?php endif; ?>
            <input type="text" id="head-name-display" value="<?php echo htmlspecialchars($department_head); ?>" class="signature-name" readonly />
            <div>Dean/Dept. Head printed name Over Signature</div>
          </div>
        </div>

        <div class="checked-by-section">
          <div>Checked and Computed by:</div>
          <div class="checked-by-line"></div>
          <input type="text" value="<?php echo htmlspecialchars($ar_header['checked_by'] ?? 'ALBERT P. CABIAO'); ?>" class="checked-by-name" id="checked-by-input" readonly style="
              width: 300px;
              margin: 0 auto;
              display: block;
              padding: 8px 12px;
              font-size: 14px;
              font-weight: bold;
              text-align: center;
              border: 1px solid #3b5525;
              border-radius: 4px;
              background-color: #f8f9fa;
              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
              transition: all 0.3s ease;
            " />
          <div style="
              text-align: center;
              margin-top: 5px;
              font-size: 12px;
              color: #555;
            ">PLP-HRD Payroll</div>
        </div>
      </div>

      <div class="button-actions-container">
        <div class="button-group-right">
          <?php if ($ar_header && isset($ar_header['ar_id'])): ?>
            <button id="deleteButton" class="action-button" style="background-color: #dc3545;">
              <i class="fas fa-trash button-icon"></i> Delete
            </button>
          <?php endif; ?>
          <button id="printButton" class="action-button">
            <i class="fas fa-print button-icon"></i> Export
          </button>
        </div>
      </div>

      <div id="uploadStatus"></div>
      <input
        type="file"
        id="esign-file"
        accept="image/*"
        style="display: none" />
      <div class="image-preview-container">
        <img id="imagePreview" src="#" alt="eSign Preview" />
      </div>
    </div>
  </div>

  <div class="modal" id="noDataModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="fas fa-exclamation-circle"></i> No Data Found</h2>
        <button class="close-modal" onclick="closeNoDataModal()">×</button>
      </div>
      <div class="modal-body">
        <p>No accomplishment report data found for the selected period.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeNoDataModal()">OK</button>
      </div>
    </div>
  </div>
</body>

</html>

<script>
  function showNoDataModal() {
    document.getElementById('noDataModal').style.display = "flex";
  }

  function closeNoDataModal() {
    document.getElementById('noDataModal').style.display = "none";
    window.location.href = 'emp-ar.php';
  }

  // Show no data modal if no data found
  <?php if (!$ar_header): ?>
    showNoDataModal();
  <?php endif; ?>

  const deleteButton = document.getElementById('deleteButton');
  if (deleteButton) {
    deleteButton.addEventListener('click', function() {
      if (confirm('Are you sure you want to delete this AR record? This action cannot be undone.')) {
        const arId = <?php echo $ar_header['ar_id'] ?? 'null'; ?>;

        if (arId) {
          fetch(`../Backend/AR/delete_ar.php?ar_id=${arId}`, {
              method: 'DELETE'
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                alert('AR record deleted successfully!');
                window.location.href = `ar.php?professor_id=<?php echo $professor_id; ?>`;
              } else {
                alert('Error deleting AR record: ' + (data.message || 'Unknown error'));
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('Error deleting AR record. Please try again.');
            });
        }
      }
    });
  }

  document.getElementById('printButton').addEventListener('click', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const professorId = urlParams.get('professor_id');
    const year = urlParams.get('year') || '<?php echo $currentYear; ?>';
    const month = urlParams.get('month') || '<?php echo $currentMonth; ?>';

    window.location.href = '../Backend/Exporting/export_ar.php?professor_id=' + professorId + '&year=' + year + '&month=' + month;
  });

  // Add this function to your existing JavaScript
  function formatDayAbbreviation(day) {
    const abbreviations = {
      'Monday': 'Mon',
      'Tuesday': 'Tues',
      'Wednesday': 'Wed',
      'Thursday': 'Thur',
      'Friday': 'Fri',
      'Saturday': 'Sat'
    };
    return abbreviations[day] || substr(day, 0, 3);
  }

  document.addEventListener("DOMContentLoaded", function() {
    // Initialize sidebar toggle
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("sidebar-toggle");
    const toggleIcon = document.getElementById("toggle-icon");
    const body = document.body;

    // Toggle sidebar function
    function toggleSidebar() {
      sidebar.classList.toggle("active");
      body.classList.toggle("sidebar-active");
      if (sidebar.classList.contains("active")) {
        toggleIcon.textContent = "❮";
      } else {
        toggleIcon.textContent = "❯";
      }
    }

    // Add click event to toggle button
    if (toggleBtn) {
      toggleBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        toggleSidebar();
      });


      // Close sidebar when clicking outside
      document.addEventListener("click", function(e) {
        if (sidebar.classList.contains("active") &&
          !sidebar.contains(e.target) &&
          e.target !== toggleBtn) {
          toggleSidebar();
        }
      });

      // Update date and time
      function updateDateTime() {
        const now = new Date();
        const options = {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
          hour: "numeric",
          minute: "numeric",
          second: "numeric",
          hour12: true,
        };
        const dateElement = document.getElementById("date");
        if (dateElement) {
          dateElement.textContent = now.toLocaleDateString("en-US", options);
        }
      }

      setInterval(updateDateTime, 1000);
      updateDateTime();

      // Year and month dropdown functionality
      const yearDropdownNav = document.getElementById("year-dropdown-nav");
      const monthDropdownNav = document.getElementById("month-dropdown-nav");

      if (yearDropdownNav && monthDropdownNav) {
        yearDropdownNav.addEventListener("change", function() {
          const selectedYear = this.value;
          const professorId = <?php echo $professor_id; ?>;

          fetch(`../Backend/AR/get_months.php?professor_id=${professorId}&year=${selectedYear}`)
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              monthDropdownNav.innerHTML = '';
              data.months.forEach(month => {
                const option = document.createElement('option');
                option.value = month;
                option.textContent = month;
                monthDropdownNav.appendChild(option);
              });
            })
            .catch(error => {
              console.error('Error fetching months:', error);
              // Fallback to current month if fetch fails
              const option = document.createElement('option');
              option.value = '<?php echo $currentMonth; ?>';
              option.textContent = '<?php echo $currentMonth; ?>';
              option.selected = true;
              monthDropdownNav.innerHTML = '';
              monthDropdownNav.appendChild(option);
            });
        });

        monthDropdownNav.addEventListener("change", function() {
          const selectedYear = yearDropdownNav.value;
          const selectedMonth = this.value;
          const professorId = <?php echo $professor_id; ?>;

          window.location.href = `ar.php?professor_id=${professorId}&year=${selectedYear}&month=${selectedMonth}`;
        });
      }

      // Calculate total hours (if applicable)
      const hoursInputs = document.querySelectorAll(".hours-rendered-input");
      const totalHoursInput = document.getElementById("total-hours");

      if (hoursInputs.length > 0 && totalHoursInput) {
        function calculateTotalHours() {
          let total = 0;
          hoursInputs.forEach((input) => {
            const value = parseInt(input.value) || 0;
            total += value;
          });
          totalHoursInput.value = total;
        }

        hoursInputs.forEach((input) => {
          input.addEventListener("input", calculateTotalHours);
        });

        calculateTotalHours();
      }
    }
  });
</script>