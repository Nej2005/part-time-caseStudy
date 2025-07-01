<?php
session_start();
require_once '../Backend/db_connect.php';

// Get parameters from URL or session
$current_semester = $_GET['semester'] ?? $_SESSION['current_semester'] ?? "1st Semester";
$current_ay = $_GET['ay'] ?? $_SESSION['current_ay'] ?? date('Y') . '-' . (date('Y') + 1);

// Update session with current values
$_SESSION['current_semester'] = $current_semester;
$_SESSION['current_ay'] = $current_ay;

// Check if professor_id is provided in the URL
$professor_id = isset($_GET['professor_id']) ? $_GET['professor_id'] : null;

if (!$professor_id) {
  // Redirect back if no professor ID is provided
  header("Location: ar-pt.php");
  exit();
}

// semester date
$period_covered = "";
$stmt = $conn->prepare("
    SELECT date_from, date_to 
    FROM Semesters 
    WHERE academic_year = ? AND semester = ?
    LIMIT 1
");
$stmt->bind_param("ss", $current_ay, $current_semester);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $date_from = new DateTime($row['date_from']);
  $date_to = new DateTime($row['date_to']);
  $period_covered = $date_from->format('F j, Y') . ' - ' . $date_to->format('F j, Y');
}
$stmt->close();

// Get professor details
$professor_name = "";
$department = "";
$stmt = $conn->prepare("
    SELECT p.first_name, p.last_name, p.middle_initial, p.department
    FROM PartTime_Professor p
    WHERE p.professor_id = ?
");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $professor = $result->fetch_assoc();
  $professor_name = htmlspecialchars($professor['first_name'] . ' ' .
    ($professor['middle_initial'] ? $professor['middle_initial'] . '. ' : '') .
    $professor['last_name']);
  $department = htmlspecialchars($professor['department']);
}
$stmt->close();

// Get department head
$department_mapping = [
  'College of Computer Studies' => 'computer-studies',
  'College of Nursing' => 'nursing',
  'College of Engineering' => 'engineering',
  'College of Education' => 'education',
  'College of Arts and Science' => 'arts-science',
  'College of Business and Accountancy' => 'business-accountancy',
  'College of Hospitality Management' => 'hospitality-management',
];

$courses_department = $department_mapping[$department] ?? strtolower(str_replace(' ', '-', $department));

$department_head = "Dean/Dept. Head name";
$stmt = $conn->prepare("SELECT department_head FROM Courses WHERE department = ? LIMIT 1");
$stmt->bind_param("s", $courses_department);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $department_head = htmlspecialchars($row['department_head']);
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Work From Home Accomplishment Report</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap");

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-color: #f5f5f5;
      font-family: "Poppins", sans-serif;
      color: #333;
    }

    .container {
      max-width: 1200px;
      margin: auto;
      padding: 0 20px;
    }

    header {
      background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
      color: white;
      padding: 25px 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: relative;
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
      /* Placeholder, not used */
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

    .header-left {
      display: flex;
      align-items: center;
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

    main {
      padding: 30px 0;
    }

    h1 {
      text-align: center;
      font-family: "Montserrat", sans-serif;
      font-weight: 600;
      margin-bottom: 20px;
      color: #1a2a0d;
    }

    h2 {
      text-align: center;
      font-family: "Montserrat", sans-serif;
      font-weight: 500;
      margin-bottom: 10px;
      color: #1a2a0d;
    }

    h3 {
      text-align: center;
      font-family: "Montserrat", sans-serif;
      font-weight: 400;
      margin-bottom: 30px;
      color: #1a2a0d;
    }

    .report-form {
      background: white;
      border-radius: 10px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
      padding: 30px;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -10px;
    }

    .form-col {
      flex: 1;
      padding: 0 10px;
      min-width: 200px;
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #1a2a0d;
    }

    input[type="text"],
    input[type="date"] {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: "Poppins", sans-serif;
      font-size: 0.9rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #f0eed0;
      color: #1a2a0d;
      font-weight: 600;
    }

    .signature-section {
      margin-top: 40px;
    }

    .signature-row {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      margin: 30px 0;
    }

    .signature-col {
      flex: 0 0 45%;
      text-align: center;
    }

    .signature-img-container {
      min-height: 60px;
      margin-bottom: -30px;
      /* Pull the signature up over the line */
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      z-index: 1;
    }

    .signature-img-container img {
      max-height: 60px;
      max-width: 100%;
      object-fit: contain;
    }

    .signature-line {
      border-top: 1px solid #333;
      width: 80%;
      margin: 25px auto 5px;
      /* Adjust spacing */
      position: relative;
    }

    .printed-name {
      margin-top: 8px;
      font-weight: 500;
      min-height: 1.2em;
    }

    .subtitle {
      font-size: 0.8rem;
      color: #666;
      margin-top: 2px;
    }

    .button-group {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }

    .btn {
      display: inline-block;
      background-color: #3b5525;
      color: white;
      padding: 10px 30px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: "Poppins", sans-serif;
      font-size: 1rem;
    }

    .btn:hover {
      background-color: #1a2a0d;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-print {
      /* Changed from btn-export */
      background-color: #d0c74f;
      color: #1a2a0d;
    }

    .btn-print:hover {
      /* Changed from btn-export:hover */
      background-color: #b8b14a;
    }

    .btn-esign {
      background-color: #ddd;
      color: black;
    }

    .btn-esign:hover {
      background-color: #3b5525;
      color: white;
    }

    .add-row-btn {
      display: block;
      margin: 15px auto;
      background-color: #f0eed0;
      color: #1a2a0d;
      border: 1px solid #d0c74f;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-family: "Poppins", sans-serif;
      transition: all 0.3s ease;
    }

    .add-row-btn:hover {
      background-color: #d0c74f;
    }

    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      padding: 15px;
    }

    .modal-content {
      background-color: white;
      border-radius: 10px;
      padding: 25px 30px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      position: relative;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .modal-title {
      font-family: "Montserrat", sans-serif;
      font-weight: 600;
      color: #1a2a0d;
      font-size: 1.1rem;
      margin: 0;
    }

    .close-modal-btn {
      background: none;
      border: none;
      font-size: 1.8rem;
      line-height: 1;
      cursor: pointer;
      color: #aaa;
      padding: 0;
      position: absolute;
      top: 10px;
      right: 15px;
    }

    .close-modal-btn:hover {
      color: #333;
    }

    .signature-pad {
      border: 1px dashed #ccc;
      border-radius: 5px;
      margin-bottom: 15px;
      height: 200px;
      width: 100%;
      position: relative;
      background-color: #fdfdfd;
      cursor: crosshair;
    }

    .signature-pad canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .modal-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
    }

    .signature-section {
      order: 2;
      margin-top: 20px;
      padding-top: 20px;
      page-break-inside: avoid;
    }

    .signature-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin: 0;
    }

    .signature-col {
      flex: 0 0 48%;
      padding: 0;
      margin: 0;
    }

    .signature-col.employee {
      text-align: left;
    }

    .signature-col.dean {
      text-align: right;
    }

    .signature-line {
      border-top: 1px solid #000;
      width: 80%;
      margin-bottom: 5px;
    }

    .signature-col.employee .signature-line {
      margin-left: 0;
      margin-right: auto;
    }

    .signature-col.dean .signature-line {
      margin-left: auto;
      margin-right: 0;
    }

    .printed-name {
      font-weight: 500;
      margin: 0;
    }

    .subtitle {
      font-size: 0.8rem;
      color: #666;
      margin: 0;
    }

    .clear-btn {
      background-color: #f5f5f5;
      color: #333;
      border: 1px solid #ddd;
    }

    .clear-btn:hover {
      background-color: #eee;
      box-shadow: none;
      transform: none;
    }

    .btn-attach {
      background-color: #4a6da7;
      color: white;
    }

    .btn-attach:hover {
      background-color: #3a5a8f;
    }

    #screenshots-container {
      display: none;
      order: 3;
      margin-top: 20px;
      flex-wrap: wrap;
      /* Changed from nowrap to wrap for preview */
      gap: 15px;
      padding: 10px 0;
    }

    .screenshot-preview {
      max-width: 100%;
      max-height: 300px;
      object-fit: contain;
      border: 1px solid #ddd;
      border-radius: 5px;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .screenshot-preview:hover {
      transform: scale(1.05);
    }

    #screenshot-input {
      display: none;
    }

    .screenshot-item {
      position: relative;
      display: inline-block;
      margin: 0 10px 10px 0;
      flex: 0 0 auto;
    }

    .remove-screenshot {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #ff4444;
      color: white;
      border-radius: 50%;
      width: 25px;
      height: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      cursor: pointer;
      border: none;
      z-index: 10;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .remove-screenshot:hover {
      background-color: #cc0000;
    }

    .button-group {
      order: 4;
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }

    .left-buttons {
      display: flex;
      gap: 10px;
    }

    .right-buttons {
      display: flex;
      gap: 10px;
    }

    @media (max-width: 768px) {
      .form-col {
        flex: 0 0 100%;
      }

      .signature-col {
        flex: 0 0 100%;
        margin-bottom: 30px;
      }

      .button-group {
        flex-direction: column;
        align-items: center;
      }

      .btn {
        width: 100%;
        margin-bottom: 10px;
        text-align: center;
      }

      .modal-content {
        padding: 20px;
      }
    }

    /* Print-specific styles */
    @media print {
      body {
        background-color: #fff !important;
        color: #000 !important;
        font-family: "Times New Roman", Times, serif;
        font-size: 12pt;
        margin: 0;
        padding: 0;
      }

      header {
        background: none !important;
        color: #000 !important;
        box-shadow: none !important;
        border-bottom: 1px solid #ccc !important;
        padding: 15px 0 !important;
      }

      /* Hide elements not for printing */
      .back-button,
      .date-time,
      .button-group,
      .add-row-btn,
      #esign-btn,
      .modal-overlay

      /* Hide all modals */
        {
        display: none !important;
      }

      .header-content {
        justify-content: flex-start;
      }

      .school-name {
        color: #000 !important;
      }

      main {
        padding: 20px 0 !important;
      }

      .report-form {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin-bottom: 0 !important;
      }

      h1,
      h2,
      h3,
      label {
        color: #000 !important;
      }

      /* Make inputs look like static text */
      input[type="text"],
      input[type="date"],
      td input[type="text"],
      td input[type="date"] {
        border: none !important;
        padding: 1px 0 !important;
        background-color: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      table {
        page-break-inside: auto;
      }

      tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }

      thead {
        display: table-header-group;
      }

      tbody {
        display: table-row-group;
      }

      table,
      th,
      td {
        border: 1px solid #666 !important;
        color: #000 !important;
      }

      th {
        background-color: #eee !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .signature-section {
        position: relative;
        bottom: auto;
        width: 100%;
        margin-top: 40px;
        padding-top: 0;
        page-break-inside: avoid;
        page-break-before: avoid;
      }

      .signature-row {
        position: relative;
        bottom: 0;
      }

      .signature-col {
        flex: 0 0 48% !important;
      }

      .signature-img-container img {
        margin-bottom: -25px;
      }

      .signature-line-itself {
        margin-top: 30px;
      }

      .printed-name,
      .subtitle {
        color: #000 !important;
      }

      .container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
      }

      .signature-col {
        page-break-inside: avoid;
      }

      body {
        padding-bottom: 0;
        /* Make space for the signature section */
      }

      #screenshots-container {
        display: flex !important;
        flex-direction: column;
        align-items: flex-start;
        margin-top: 20px;
        page-break-inside: avoid;
      }

      .screenshot-preview {
        max-width: 100% !important;
        max-height: none !important;
        height: auto !important;
        margin-bottom: 20px;
        page-break-inside: avoid;
      }

      .screenshots-title {
        display: block !important;
        width: 100%;
        font-weight: bold;
        margin-bottom: 15px;
        page-break-after: avoid;
      }

      .remove-screenshot {
        display: none !important;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="container">
      <div class="header-content">
        <div class="header-left">
          <button class="back-button" id="back-btn" title="Go Back">←</button>
          <div class="logo-container">
            <div class="school-name">Faculty Portal</div>
          </div>
        </div>
        <div class="date-time" id="date"></div>
      </div>
    </div>
  </header>

  <main>
    <div class="container">
      <div class="report-form" id="report-content-to-print">

        <h1>WORK FROM HOME ACCOMPLISHMENT REPORT</h1>
        <h2>(PART-TIME FACULTY)</h2>
        <h3>SY <?php echo str_replace('-', ' – ', htmlspecialchars($current_ay)); ?></h3>

        <form id="wfh-form">
          <div class="form-row">
            <div class="form-col">
              <label for="name">Name:</label>
              <input type="text" id="name" name="name" value="<?php echo $professor_name; ?>" required readonly />
            </div>
            <div class="form-col">
              <label for="college">College:</label>
              <input type="text" id="college" name="college" value="<?php echo $department; ?>" required readonly />
            </div>
          </div>

          <div class="form-group">
            <label for="period">Period Covered:</label>
            <input type="text" id="period" name="period" value="<?php echo htmlspecialchars($period_covered); ?>" required readonly />
          </div>

          <table id="accomplishment-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Actual Accomplishment</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="date" name="date[]" /></td>
                <td>
                  <input
                    type="text"
                    name="time[]"
                    placeholder="e.g. 9:00 AM - 12:00 PM" />
                </td>
                <td><input type="text" name="accomplishment[]" /></td>
              </tr>
              <tr>
                <td><input type="date" name="date[]" /></td>
                <td>
                  <input
                    type="text"
                    name="time[]"
                    placeholder="e.g. 9:00 AM - 12:00 PM" />
                </td>
                <td><input type="text" name="accomplishment[]" /></td>
              </tr>
              <tr>
                <td><input type="date" name="date[]" /></td>
                <td>
                  <input
                    type="text"
                    name="time[]"
                    placeholder="e.g. 9:00 AM - 12:00 PM" />
                </td>
                <td><input type="text" name="accomplishment[]" /></td>
              </tr>
              <tr>
                <td><input type="date" name="date[]" /></td>
                <td>
                  <input
                    type="text"
                    name="time[]"
                    placeholder="e.g. 9:00 AM - 12:00 PM" />
                </td>
                <td><input type="text" name="accomplishment[]" /></td>
              </tr>
              <tr>
                <td><input type="date" name="date[]" /></td>
                <td>
                  <input
                    type="text"
                    name="time[]"
                    placeholder="e.g. 9:00 AM - 12:00 PM" />
                </td>
                <td><input type="text" name="accomplishment[]" /></td>
              </tr>
            </tbody>
          </table>

          <button type="button" class="add-row-btn" id="add-row">
            + Add Row
          </button>

          <div class="form-group">
            <label for="prepared-by">Prepared by:</label>
          </div>

          <div class="signature-section">
            <div class="signature-row">
              <div class="signature-col employee">
                <div
                  class="signature-img-container"
                  id="employee-signature-img-container">
                  <!-- Signature will appear here -->
                </div>
                <div class="signature-line"></div>
                <div class="printed-name" id="employee-printed-name">
                  <?php echo $professor_name; ?>
                </div>
                <div class="subtitle">(Part-Time Employee Signature)</div>
              </div>
              <div class="signature-col dean">
                <div class="signature-line"></div>
                <div class="printed-name"><?php echo $department_head; ?></div>
                <div class="subtitle">(College Dean/Department Head Signature)</div>
              </div>
            </div>
          </div>

          <!-- E-Signature Modal -->
          <div class="modal-overlay" id="esign-modal">
            <div class="modal-content">
              <div class="modal-header">
                <h2 class="modal-title" id="esign-modal-title">
                  Add Your E-Signature
                </h2>
                <button
                  class="close-modal-btn"
                  id="close-esign-modal-btn"
                  title="Close">
                  ×
                </button>
              </div>
              <div class="signature-pad" id="signature-pad-container">
                <canvas id="signature-canvas"></canvas>
              </div>
              <p
                style="
                    text-align: center;
                    font-size: 0.9em;
                    color: #666;
                    margin-bottom: 15px;
                  ">
                Sign above using your mouse or touch screen
              </p>
              <div class="modal-actions">
                <button class="btn clear-btn" id="clear-signature">
                  Clear
                </button>
                <button class="btn btn-esign" id="save-signature">
                  Save Signature
                </button>
              </div>
            </div>
          </div>

          <input
            type="file"
            id="screenshot-input"
            accept="image/jpeg,image/png"
            multiple />
          <div id="screenshots-container">
            <div class="screenshots-title">
              Attachment Means of Verification (e.g. Screenshot of
              time-stamped Class):
            </div>
            <!-- Screenshot rows will be added here -->
          </div>

          <div class="button-group">
            <div class="left-buttons">
              <button
                type="button"
                class="btn btn-attach"
                id="attach-screenshots-btn">
                Attach Screenshots
              </button>
            </div>
            <div class="right-buttons">
              <button type="button" class="btn btn-esign" id="esign-btn">
                <i class="fas fa-signature"></i> E-Sign
              </button>

              <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save
              </button>

              <button type="button" class="btn btn-print" id="print-report-btn">
                <i class="fas fa-print"></i> Export
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- Export Options Modal Removed -->

  <script>
    function updateDateTime() {
      const now = new Date();
      const options = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
      };
      document.getElementById("date").innerHTML = now.toLocaleString(
        "en-US",
        options
      );
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    document.getElementById("add-row").addEventListener("click", function() {
      const tbody = document.querySelector("#accomplishment-table tbody");
      const newRow = document.createElement("tr");
      newRow.innerHTML = `
                <td><input type="date" name="date[]"></td>
                <td><input type="text" name="time[]" placeholder="e.g. 9:00 AM - 12:00 PM"></td>
                <td><input type="text" name="accomplishment[]"></td>
            `;
      tbody.appendChild(newRow);
    });

    document
      .getElementById("wfh-form")
      .addEventListener("submit", function(e) {
        e.preventDefault();
        alert("Form submitted successfully! (Placeholder)");
      });

    document.getElementById("back-btn").addEventListener("click", function() {
      const semester = "<?php echo $_GET['semester'] ?? $_SESSION['current_semester'] ?? ''; ?>";
      const ay = "<?php echo $_GET['ay'] ?? $_SESSION['current_ay'] ?? ''; ?>";

      window.location.href = `ar-pt.php?semester=${encodeURIComponent(semester)}&ay=${encodeURIComponent(ay)}`;
    });

    // E-signature functionality
    const esignModal = document.getElementById("esign-modal");
    const esignBtn = document.getElementById("esign-btn");
    const closeEsignModalBtn = document.getElementById(
      "close-esign-modal-btn"
    );
    const clearSignatureBtn = document.getElementById("clear-signature");
    const saveSignatureBtn = document.getElementById("save-signature");
    const canvas = document.getElementById("signature-canvas");
    const signaturePadContainer = document.getElementById(
      "signature-pad-container"
    );
    const esignModalTitle = document.getElementById("esign-modal-title");
    let ctx,
      isDrawing = false;
    // currentSignatureType will always be 'employee' now for e-sign

    if (canvas && signaturePadContainer) {
      ctx = canvas.getContext("2d");
    } else {
      console.error("Signature canvas or its container not found.");
    }

    function resizeCanvas() {
      if (!canvas || !signaturePadContainer || !ctx) return;
      const container = signaturePadContainer;
      const dpr = window.devicePixelRatio || 1;
      canvas.width = container.offsetWidth * dpr;
      canvas.height = container.offsetHeight * dpr;
      canvas.style.width = container.offsetWidth + "px";
      canvas.style.height = container.offsetHeight + "px";
      ctx.scale(dpr, dpr);
      ctx.lineWidth = 2;
      ctx.strokeStyle = "#333";
      ctx.lineCap = "round";
      ctx.lineJoin = "round";
    }

    if (esignBtn) {
      esignBtn.addEventListener("click", function() {
        esignModal.style.display = "flex";
        resizeCanvas();
        // No longer ask who is signing, it's always the employee for e-sign
        esignModalTitle.textContent = "Add Employee E-Signature";
        if (ctx)
          ctx.clearRect(
            0,
            0,
            canvas.width / (window.devicePixelRatio || 1),
            canvas.height / (window.devicePixelRatio || 1)
          );
      });
    }

    if (closeEsignModalBtn) {
      closeEsignModalBtn.addEventListener(
        "click",
        () => (esignModal.style.display = "none")
      );
    }

    function getEventPos(canvasDom, event) {
      const rect = canvasDom.getBoundingClientRect();
      const clientX =
        event.clientX || (event.touches && event.touches[0].clientX);
      const clientY =
        event.clientY || (event.touches && event.touches[0].clientY);
      return {
        x: clientX - rect.left,
        y: clientY - rect.top
      };
    }

    function startDrawing(e) {
      if (!ctx) return;
      e.preventDefault();
      isDrawing = true;
      const pos = getEventPos(canvas, e);
      ctx.beginPath();
      ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
      if (!isDrawing || !ctx) return;
      e.preventDefault();
      const pos = getEventPos(canvas, e);
      ctx.lineTo(pos.x, pos.y);
      ctx.stroke();
    }

    function stopDrawing() {
      isDrawing = false;
      if (ctx) ctx.beginPath();
    }

    if (canvas) {
      canvas.addEventListener("mousedown", startDrawing);
      canvas.addEventListener("touchstart", startDrawing, {
        passive: false
      });
      canvas.addEventListener("mousemove", draw);
      canvas.addEventListener("touchmove", draw, {
        passive: false
      });
      ["mouseup", "touchend", "mouseout", "touchcancel"].forEach((evt) =>
        canvas.addEventListener(evt, stopDrawing)
      );
    }

    if (clearSignatureBtn) {
      clearSignatureBtn.addEventListener("click", () => {
        if (ctx)
          ctx.clearRect(
            0,
            0,
            canvas.width / (window.devicePixelRatio || 1),
            canvas.height / (window.devicePixelRatio || 1)
          );
      });
    }

    if (saveSignatureBtn) {
      saveSignatureBtn.addEventListener("click", function() {
        if (!ctx || !canvas) return;
        const blank = document.createElement("canvas");
        blank.width = canvas.width;
        blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) {
          alert("Please provide a signature first.");
          return;
        }
        const signatureDataUrl = canvas.toDataURL("image/png");
        const img = document.createElement("img");
        img.src = signatureDataUrl;
        img.alt = "Employee signature";
        img.style.maxHeight = "60px";
        img.style.marginBottom = "-15px"; // Adjust to position over line

        const targetContainer = document.getElementById(
          "employee-signature-img-container"
        );
        if (targetContainer) {
          targetContainer.innerHTML = "";
          targetContainer.appendChild(img);
        }
        ctx.clearRect(
          0,
          0,
          canvas.width / (window.devicePixelRatio || 1),
          canvas.height / (window.devicePixelRatio || 1)
        );
        esignModal.style.display = "none";
      });
    }

    const nameInput = document.getElementById("name");
    const employeePrintedNameEl = document.getElementById("employee-printed-name");

    if (nameInput && employeePrintedNameEl) {
      nameInput.addEventListener("input", function() {
        if (this.value) { // Only update if there's a value
          employeePrintedNameEl.textContent = this.value;
        }
      });
      // Initialize with current value if empty
      if (!nameInput.value) {
        employeePrintedNameEl.textContent = "(Employee Name)";
      }
    }

    const printReportBtn = document.getElementById("print-report-btn");
    if (printReportBtn) {
      printReportBtn.addEventListener("click", () => {
        window.print();
      });
    }

    document
      .getElementById("print-report-btn")
      .addEventListener("click", function() {
        document.getElementById("screenshots-container").style.display =
          "flex";
      });

    window.addEventListener("click", function(e) {
      if (e.target === esignModal) esignModal.style.display = "none";
    });

    window.addEventListener("resize", resizeCanvas);

    const attachScreenshotsBtn = document.getElementById(
      "attach-screenshots-btn"
    );
    const screenshotInput = document.getElementById("screenshot-input");
    const screenshotsContainer = document.getElementById(
      "screenshots-container"
    );

    if (attachScreenshotsBtn && screenshotInput) {
      attachScreenshotsBtn.addEventListener("click", function() {
        screenshotInput.click();
      });
    }

    if (screenshotInput) {
      screenshotInput.addEventListener("change", function(e) {
        const files = e.target.files;
        if (files.length > 0) {
          const screenshotsContainer = document.getElementById(
            "screenshots-container"
          );
          const title =
            screenshotsContainer.querySelector(".screenshots-title");

          // Clear existing screenshots only if this is the first upload
          if (screenshotsContainer.children.length <= 1) {
            screenshotsContainer.innerHTML = "";
            if (title) screenshotsContainer.appendChild(title);
          }

          screenshotsContainer.style.display = "flex";

          // Limit to 20 screenshots to prevent performance issues
          const maxFiles = Math.min(files.length, 20);

          for (let i = 0; i < maxFiles; i++) {
            const file = files[i];
            if (file.type.match("image.*")) {
              const reader = new FileReader();

              reader.onload = function(e) {
                const screenshotItem = document.createElement("div");
                screenshotItem.className = "screenshot-item";

                const imgWrapper = document.createElement("div");
                imgWrapper.style.position = "relative";

                const img = document.createElement("img");
                img.className = "screenshot-preview";
                img.src = e.target.result;
                img.alt =
                  "Screenshot " + screenshotsContainer.children.length;
                img.style.maxWidth = "100%";
                img.style.maxHeight = "300px";

                const removeBtn = document.createElement("button");
                removeBtn.className = "remove-screenshot";
                removeBtn.innerHTML = "×";
                removeBtn.title = "Remove this screenshot";
                removeBtn.addEventListener("click", function() {
                  screenshotItem.remove();
                  if (screenshotsContainer.children.length <= 1) {
                    screenshotsContainer.style.display = "none";
                  }
                });

                imgWrapper.appendChild(img);
                imgWrapper.appendChild(removeBtn);
                screenshotItem.appendChild(imgWrapper);
                screenshotsContainer.appendChild(screenshotItem);
              };

              reader.readAsDataURL(file);
            }
          }

          // Show message if files were skipped
          if (files.length > maxFiles) {
            const message = document.createElement("div");
            message.style.width = "100%";
            message.style.color = "#666";
            message.style.fontSize = "0.9em";
            message.textContent = `Note: Showing ${maxFiles} of ${files.length} screenshots (maximum reached)`;
            screenshotsContainer.appendChild(message);
          }
        }
      });
    }
  </script>
</body>

</html>