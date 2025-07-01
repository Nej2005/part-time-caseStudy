<?php
session_start();
include '../Backend/db_connect.php';

$success_message = '';
$professor_id = '';

// Check if professor_id is provided
if (!isset($_GET['professor_id'])) {
    header("Location: emp-formload.php");
    exit();
}

$professor_id = $_GET['professor_id'];

// Get professor details
$professor_query = "SELECT p.*, u.employee_id 
                    FROM PartTime_Professor p
                    JOIN Users u ON p.email_address = u.email_address
                    WHERE p.professor_id = $professor_id";
$professor_result = $conn->query($professor_query);
$professor = $professor_result->fetch_assoc();

// Get all subjects for dropdown
$subjects_query = "SELECT * FROM Subjects";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Get all sections for dropdown
$sections_query = "SELECT * FROM Sections";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row;
}

// Get all semesters for dropdown
$semesters_query = "SELECT * FROM Semesters ORDER BY academic_year DESC, semester";
$semesters_result = $conn->query($semesters_query);
$semesters_list = [];
while ($row = $semesters_result->fetch_assoc()) {
    $semesters_list[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save form data to database
    $period = $conn->real_escape_string($_POST['period']);
    $college_department = $conn->real_escape_string($professor['department']);
    $school_year = $conn->real_escape_string($_POST['school_year']);
    $semester = $conn->real_escape_string($_POST['semester']);

    // Insert into Form_Loads table
    $insert_form = "INSERT INTO Form_Loads (professor_id, employee_id, full_name, status, period, college_department, school_year, semester)
                VALUES ($professor_id, '{$professor['employee_id']}', '{$professor['first_name']} {$professor['last_name']}', 'PT', '$period', '$college_department', '$school_year', '$semester')";
    if ($conn->query($insert_form)) {
        $form_id = $conn->insert_id;

        // Process each row
        foreach ($_POST['subject_code'] as $index => $subject_code) {
            if (!empty($subject_code)) {
                $subject_description = $conn->real_escape_string($_POST['subject_description'][$index]);
                $lec_hours = floatval($_POST['lec_hours'][$index]);
                $lab_hours = floatval($_POST['lab_hours'][$index]);
                $hrs_per_week = floatval($_POST['hrs_per_week'][$index]);
                $monday = $conn->real_escape_string($_POST['monday'][$index]);
                $tuesday = $conn->real_escape_string($_POST['tuesday'][$index]);
                $wednesday = $conn->real_escape_string($_POST['wednesday'][$index]);
                $thursday = $conn->real_escape_string($_POST['thursday'][$index]);
                $friday = $conn->real_escape_string($_POST['friday'][$index]);
                $saturday = $conn->real_escape_string($_POST['saturday'][$index]);
                $sunday = $conn->real_escape_string($_POST['sunday'][$index]);
                $room = $conn->real_escape_string($_POST['room'][$index]);
                $section = $conn->real_escape_string($_POST['section'][$index]);
                $subject_type = $_POST['subject_type'][$index];

                // Split the description into base and type
                if (preg_match('/^(.*?)\s*\((LEC|LAB)\)$/', $subject_description, $matches)) {
                    $subject_base_description = $conn->real_escape_string($matches[1]);
                    $subject_type = $matches[2];
                } else {
                    // No type specified - use empty string
                    $subject_base_description = $conn->real_escape_string($subject_description);
                    $subject_type = '';
                }

                $insert_detail = "INSERT INTO Form_Load_Details 
                                 (form_id, subject_code, subject_base_description, subject_type, lec_hours, lab_hours, hrs_per_week,
                                 monday, tuesday, wednesday, thursday, friday, saturday, sunday, room, section)
                                VALUES ($form_id, '$subject_code', '$subject_base_description', '$subject_type', 
                                        $lec_hours, $lab_hours, $hrs_per_week,
                                        '$monday', '$tuesday', '$wednesday', '$thursday', '$friday', '$saturday', '$sunday', 
                                        '$room', '$section')";
                $conn->query($insert_detail);
            }
        }

        $success_message = "Form saved successfully!";
    } else {
        $success_message = "Error saving form: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part-Time Official Faculty Loading Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #333;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            width: 100%;
            margin: 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            position: relative;
        }

        .logo {
            width: 40px;
            height: 40px;
            margin-right: 12px;
        }

        .school-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .date-time {
            font-family: 'Montserrat', sans-serif;
            font-weight: 300;
            font-size: 0.9rem;
            text-align: right;
        }

        .back-button-container {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 100;
        }

        .back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: white;
            margin-top: 6px;
            color: black;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .back-button:hover {
            background-color: #1a2a0d;
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .container {
            width: 98%;
            max-width: 1800px;
            background-color: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            margin: 20px 0;
            flex-grow: 1;
            overflow-x: auto;
        }

        .header-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        img {
            width: 50px;
            margin-right: 20px;
            margin-left: 85px;
        }

        .title-container {
            flex-grow: 1;
            text-align: center;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            color: #000;
        }

        .subtitle {
            font-size: 12px;
            margin: 5px 0;
        }

        .phone {
            font-size: 12px;
            margin: 5px 0;
        }

        .form-title {
            text-align: center;
            color: #3b5525;
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0;
        }

        .semester {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
            padding: 8px;
            font-size: 14px;
        }

        .info-input {
            flex-grow: 1;
            padding: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .info-period {
            width: 45%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 12px;
            text-align: center;
            font-size: 15px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="text"],
        input[type="number"] {
            width: calc(100% - 10px);
            padding: 10px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 15px;
        }

        .pasig-text {
            position: absolute;
            right: 20px;
            top: 20px;
            text-align: right;
            color: #3b5525;
        }

        .pasig-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .pasig-motto {
            font-size: 6px;
        }

        .small-input {
            width: 100px;
            padding: 8px;
            font-size: 14px;
        }

        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .bottom-table {
            width: 48%;
            border-collapse: collapse;
        }

        .bottom-table td {
            padding: 10px;
            border: 1px solid #000;
            font-size: 14px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkbox-label {
            margin-right: 10px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }

        .add-row-btn {
            padding: 12px 24px;
            background-color: #4A6DA7;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }

        .add-row-btn:hover {
            background-color: #3a5a8f;
        }

        .save-btn {
            padding: 12px 24px;
            background-color: #3b5525;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
            margin-right: 10px;
        }

        .save-btn:hover {
            background-color: #1a2a0d;
        }

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 14px;
        }

        select[name="section[]"] {
            min-width: 100px;
        }


        .subject-container {
            display: flex;
        }

        .subject-container input {
            flex-grow: 1;
        }

        .subject-container select {
            width: 80px;
            margin-left: 5px;
        }

        .subject-code {
            min-width: 90px;
        }

        .send-btn {
            padding: 12px 24px;
            background-color: #d0c74f;
            color: #1a2a0d;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 14px;
        }

        .send-btn:hover {
            background-color: #b8b14a;
        }

        #scheduleTable th:nth-child(1) {
            width: 3%;
        }

        #scheduleTable th:nth-child(2) {
            position: relative;
            transition: all 0.6s ease;
            z-index: 1;
            width: 12%;
            min-width: 200px;
        }

        #scheduleTable td:nth-child(2):hover {
            width: 25%;
            min-width: 300px;
            z-index: 10;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #scheduleTable td:nth-child(2):hover .subject-container {
            width: 95%;
            display: flex;
            flex-wrap: nowrap;
            transition: all 0.6s ease;
        }

        #scheduleTable td:nth-child(2):hover .subject-description {
            width: 100%;
            min-width: 200px;
            transition: all 0.6s ease;
            background-color: white;
        }

        #scheduleTable td:nth-child(2):hover .subject-container select {
            width: 80px;
            margin-left: 5px;
            flex-shrink: 0;
            transition: all 0.6s ease;
        }

        #scheduleTable td:nth-child(2):hover input {
            width: 90%;
            transition: all 0.6s ease;
            background-color: white;
        }

        #scheduleTable th:nth-child(3) {
            width: 6%;
        }

        #scheduleTable th:nth-child(4) {
            width: 6%;
        }

        #scheduleTable th:nth-child(5) {
            width: 3%;
        }

        #scheduleTable th:nth-child(6),
        #scheduleTable th:nth-child(7),
        #scheduleTable th:nth-child(8),
        #scheduleTable th:nth-child(9),
        #scheduleTable th:nth-child(10),
        #scheduleTable th:nth-child(11),
        #scheduleTable th:nth-child(12) {
            width: 4%;
        }

        #scheduleTable th:nth-child(13) {
            width: 4%;
        }

        #scheduleTable th:nth-child(14) {
            width: 2%;
        }

        #scheduleTable th:nth-child(15) {
            width: 8%;
        }

        #scheduleTable td:nth-child(15) {
            position: relative;
            transition: all 0.3s ease;
            z-index: 1;
            width: 8%;
        }

        #scheduleTable td:nth-child(15):hover {
            width: 15%;
            z-index: 10;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #scheduleTable td:nth-child(15):hover input {
            width: 90%;
            transition: all 0.3s ease;
            background-color: white;
        }

        #scheduleTable td:nth-child(6),
        #scheduleTable td:nth-child(7),
        #scheduleTable td:nth-child(8),
        #scheduleTable td:nth-child(9),
        #scheduleTable td:nth-child(10),
        #scheduleTable td:nth-child(11),
        #scheduleTable td:nth-child(12) {
            position: relative;
            transition: all 0.3s ease;
            z-index: 1;
            width: 4%;

        }

        #scheduleTable td:nth-child(6):hover,
        #scheduleTable td:nth-child(7):hover,
        #scheduleTable td:nth-child(8):hover,
        #scheduleTable td:nth-child(9):hover,
        #scheduleTable td:nth-child(10):hover,
        #scheduleTable td:nth-child(11):hover,
        #scheduleTable td:nth-child(12):hover {
            width: 8%;
            z-index: 10;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #scheduleTable td:nth-child(6):hover input,
        #scheduleTable td:nth-child(7):hover input,
        #scheduleTable td:nth-child(8):hover input,
        #scheduleTable td:nth-child(9):hover input,
        #scheduleTable td:nth-child(10):hover input,
        #scheduleTable td:nth-child(11):hover input,
        #scheduleTable td:nth-child(12):hover input {
            width: 90%;
            transition: all 0.3s ease;
            background-color: white;
        }

        #scheduleTable tr:hover td {
            z-index: 2;
        }

        #saveModal .modal-content {
            width: 400px;
        }

        #saveModal .form-row select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
        }

        #saveModal .form-row select:focus {
            border-color: #2c5e1a;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 94, 26, 0.2);
        }

        #Sem {
            width: 150px;
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

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #d6e9c6;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo-container {
                margin-bottom: 10px;
                justify-content: center;
            }

            .date-time {
                text-align: center;
                margin-top: 10px;
            }

            .bottom-section {
                flex-direction: column;
            }

            .bottom-table {
                width: 100%;
                margin-bottom: 15px;
            }

            .button-container {
                flex-direction: column;
                gap: 10px;
            }

            #AY {
                width: 150px;

            }

            .add-row-btn,
            .save-btn,
            .send-btn {
                width: 100%;
                margin-right: 0;
            }
        }

        .sy-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 60px;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-content">
            <div class="logo-container">
                <img src="Logo.ico">
                </svg>
                <div class="school-name">Faculty Portal</div>
            </div>
            <div class="date-time" id="date"></div>
        </div>
    </header>

    <div class="back-button-container">
        <a href="emp-formload.php" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
        </a>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="success-message"
            style="background-color: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 10px; margin-top: 10px; border-radius: 4px;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="pasig-text">
            <div class="pasig-title">PASIG</div>
            <div class="pasig-motto">MAUNLAD ANG PAG-ASA</div>
        </div>
        <div class="header-form">
            <div class="logo-form">
                <svg viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" fill="#4b7b2a" />
                    <circle cx="50" cy="50" r="40" fill="#efd334" />
                    <path d="M50,20 L60,40 L80,45 L65,60 L70,80 L50,70 L30,80 L35,60 L20,45 L40,40 Z" fill="#4b7b2a" />
                    <circle cx="50" cy="50" r="15" fill="#fff" />
                    <text x="50" y="55" font-size="8" text-anchor="middle">PASIG</text>
                </svg>
            </div>
            <div class="title-container">
                <p class="title">PAMANTASAN NG LUNGSOD NG PASIG</p>
                <p class="subtitle">Alcalde Jose St. Barangay Kapasigan, Pasig City 1600</p>
                <p class="phone">Telephone No. 628-1014</p>
            </div>
        </div>

        <div class="form-title">PART-TIME OFFICIAL FACULTY LOADING FORM</div>

        <div class="sy-container">
            <select id="Sem" name="semester" style="width: 150px;" onchange="updatePeriodField()">
                <option value="" selected hidden>Select Semester</option>
                <?php foreach ($semesters_list as $semester): ?>
                    <option value="<?php echo htmlspecialchars($semester['semester']); ?>">
                        <?php echo htmlspecialchars($semester['semester']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="AY" name="academic_year" style="width: 150px;" onchange="updateSemesterDropdown()">
                <option value="" selected hidden>Academic Year</option>
                <?php
                $unique_years = array_unique(array_column($semesters_list, 'academic_year'));
                foreach ($unique_years as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>">
                        <?php echo htmlspecialchars($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <form method="POST" id="loadingForm">
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-input">
                    <input type="text" id="name"
                        value="<?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>"
                        style="width: 100%;" readonly>
                </div>
                <div class="info-label">For the Period of:</div>
                <div class="info-input info-period">
                    <input type="text" id="period" name="period" placeholder="JANUARY 27 - MAY 31, 2025"
                        style="width: 100%;" required>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-input">
                    <input type="text" id="status" value="PT" style="width: 100%;" readonly>
                </div>
                <div class="info-label">College/Department:</div>
                <div class="info-input info-period">
                    <input type="text" id="college" name="college_department" value="<?php echo htmlspecialchars($professor['department'] ?? ''); ?>" style="width: 100%;" readonly>
                </div>
            </div>

            <table id="scheduleTable">
                <thead>
                    <tr>
                        <th>CODE</th>
                        <th>SUBJECT DESCRIPTION</th>
                        <th>LEC.</th>
                        <th>LAB.</th>
                        <th>NO. OF HRS/WK</th>
                        <th>MON</th>
                        <th>TUE</th>
                        <th>WED</th>
                        <th>THU</th>
                        <th>FRI</th>
                        <th>SAT</th>
                        <th>SUN</th>
                        <th>ROOM</th>
                        <th>SECTION</th>
                        <th>PERIOD</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Initial row -->
                    <tr>
                        <td>
                            <select name="subject_code[]" class="subject-code"
                                onchange="updateSubjectDescription(this)">
                                <option value="" hidden>Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['subject_code']); ?>"
                                        data-units="<?php echo htmlspecialchars($subject['units']); ?>"
                                        data-description="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                        <?php echo htmlspecialchars($subject['subject_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div class="subject-container">
                                <input type="text" name="subject_description[]" class="subject-description" readonly>
                                <select name="subject_type[]" onchange="updateSubjectType(this)">
                                    <option value="(LEC)" selected>(LEC)</option>
                                    <option value="(LAB)">(LAB)</option>
                                    <option value="">(None)</option>
                                </select>
                            </div>
                        </td>
                        <td><input type="number" name="lec_hours[]" value="2" min="0" step="0.5"
                                onchange="calculateHours(this)"></td>
                        <td><input type="number" name="lab_hours[]" value="0" min="0" step="0.5"
                                onchange="calculateHours(this)"></td>
                        <td><input type="number" name="hrs_per_week[]" value="0" readonly></td>
                        <td><input type="text" name="monday[]"></td>
                        <td><input type="text" name="tuesday[]"></td>
                        <td><input type="text" name="wednesday[]"></td>
                        <td><input type="text" name="thursday[]"></td>
                        <td><input type="text" name="friday[]"></td>
                        <td><input type="text" name="saturday[]"></td>
                        <td><input type="text" name="sunday[]"></td>
                        <td><input type="text" name="room[]"></td>
                        <td>
                            <select name="section[]">
                                <option value="" hidden>Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['section_name']); ?>">
                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="period_col[]" readonly></td>
                    </tr>
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td id="totalLec">0</td>
                        <td id="totalLab">0</td>
                        <td id="totalHrsWk">0</td>
                        <td colspan="10"></td>
                    </tr>
                </tbody>
            </table>

            <div class="button-container">
                <button type="button" id="addRowBtn" class="add-row-btn">
                    <i class="fas fa-plus"></i> Add Row
                </button>
                <div>
                    <button type="submit" id="saveBtn" class="save-btn">
                        <i class="fas fa-save button-icon"></i> Save
                    </button>
                </div>
            </div>

            <div class="bottom-section">
                <table class="bottom-table">
                    <tr>
                        <td>NO. OF PREPARATIONS</td>
                        <td><input type="number" id="numPreparations" value="0" class="small-input" readonly></td>
                    </tr>
                    <tr>
                        <td>LOWEST TEACHING HRS/DAY</td>
                        <td><input type="number" id="lowestHrs" value="0" class="small-input" readonly></td>
                    </tr>
                    <tr>
                        <td>HIGHEST TEACHING HRS/DAY</td>
                        <td><input type="number" id="highestHrs" value="0" class="small-input" readonly></td>
                    </tr>
                </table>

                <table class="bottom-table">
                    <tr>
                        <td>TOTAL LOAD UNITS</td>
                        <td><input type="number" id="totalLoadUnits" value="0" class="small-input" readonly></td>
                    </tr>
                    <tr>
                        <td>TOTAL LOAD HRS</td>
                        <td><input type="number" id="totalLoadHrs" value="0" class="small-input" readonly></td>
                    </tr>
                    <tr>
                        <td>CURRENTLY EMPLOYED IN OTHER GOV'T INSTITUTION</td>
                        <td class="checkbox-container">
                            <input type="checkbox" id="yes" name="employed_elsewhere" value="1">
                            <label for="yes" class="checkbox-label">YES</label>
                            <input type="checkbox" id="no" name="not_employed_elsewhere" value="1" checked>
                            <label for="no">NO</label>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
    </div>

    <script>
        const subjects = <?php echo json_encode($subjects); ?>;

        // Main initialization function
        function initializeForm() {
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Get references to elements
            const yearSelect = document.getElementById('AY');
            const semesterSelect = document.getElementById('Sem');
            const periodElement = document.getElementById('period');

            // Add event listeners if elements exist
            if (yearSelect) {
                yearSelect.addEventListener('change', updateSemesterDropdown);
            }

            if (semesterSelect) {
                semesterSelect.addEventListener('change', updatePeriodField);
            }

            if (periodElement) {
                periodElement.addEventListener('change', function() {
                    const periodValue = this.value;
                    document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                        const rowSelect = input.closest('tr')?.querySelector('select[name="subject_code[]"]');
                        if (rowSelect?.value) {
                            input.value = periodValue;
                        }
                    });
                });
            }

            // Initialize the first row
            const initialRow = document.querySelector('#scheduleTable tbody tr');
            if (initialRow) {
                addRowEventListeners(initialRow);
            }

            // Initialize semester and year dropdowns
            if (semesterSelect && yearSelect) {
                if (semesterSelect.selectedIndex === 0 && semesterSelect.options.length > 1) {
                    semesterSelect.selectedIndex = 1;
                }
                if (yearSelect.selectedIndex === 0 && yearSelect.options.length > 1) {
                    yearSelect.selectedIndex = 1;
                }
                updatePeriodField();
            }

            // Add row button
            const addRowBtn = document.getElementById('addRowBtn');
            if (addRowBtn) {
                addRowBtn.addEventListener('click', addRow);
            }

            // Save button
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    validateAndSubmitForm();
                });
            }

            // Employment checkboxes
            const yesCheckbox = document.getElementById('yes');
            const noCheckbox = document.getElementById('no');
            if (yesCheckbox && noCheckbox) {
                yesCheckbox.addEventListener('change', function() {
                    if (this.checked) noCheckbox.checked = false;
                });
                noCheckbox.addEventListener('change', function() {
                    if (this.checked) yesCheckbox.checked = false;
                });
            }

            // Confirm save button in modal
            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            if (confirmSaveBtn) {
                confirmSaveBtn.addEventListener('click', function() {
                    const schoolYearInput = document.getElementById('schoolYear');
                    const semesterSelect = document.getElementById('semester');

                    if (!schoolYearInput || !semesterSelect) return;

                    const schoolYear = schoolYearInput.value;
                    const semester = semesterSelect.value;

                    if (!schoolYear) {
                        alert('Please enter School Year');
                        return;
                    }

                    // Create hidden inputs for school year and semester
                    const schoolYearHidden = document.createElement('input');
                    schoolYearHidden.type = 'hidden';
                    schoolYearHidden.name = 'school_year';
                    schoolYearHidden.value = schoolYear;

                    const semesterHidden = document.createElement('input');
                    semesterHidden.type = 'hidden';
                    semesterHidden.name = 'semester';
                    semesterHidden.value = semester;

                    // Add them to the form
                    const form = document.getElementById('loadingForm');
                    if (form) {
                        form.appendChild(schoolYearHidden);
                        form.appendChild(semesterHidden);
                        form.submit();
                    }
                });
            }

            // Form submission handler
            const loadingForm = document.getElementById('loadingForm');
            if (loadingForm) {
                loadingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    validateAndSubmitForm();
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initializeForm);

        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const formattedDate = now.toLocaleString('en-US', options);
            const dateElement = document.getElementById('date');
            if (dateElement) dateElement.innerHTML = formattedDate;
        }

        function calculateTotals() {
            let totalLec = 0;
            let totalLab = 0;
            let totalHrsWk = 0;
            const uniqueSubjects = new Set();
            const subjectUnitsMap = {};

            const rows = document.querySelectorAll('#scheduleTable tbody tr:not(:last-child)');

            rows.forEach(row => {
                const lecInput = row.querySelector('input[name="lec_hours[]"]');
                const labInput = row.querySelector('input[name="lab_hours[]"]');
                const hrsInput = row.querySelector('input[name="hrs_per_week[]"]');
                const subjectSelect = row.querySelector('select[name="subject_code[]"]');

                if (!lecInput || !labInput || !hrsInput || !subjectSelect) return;

                const lecValue = parseFloat(lecInput.value) || 0;
                const labValue = parseFloat(labInput.value) || 0;
                const hrsValue = parseFloat(hrsInput.value) || 0;
                const subjectCode = subjectSelect.value;
                const units = parseFloat(subjectSelect.options[subjectSelect.selectedIndex]?.dataset.units) || 0;

                totalLec += lecValue;
                totalLab += labValue;
                totalHrsWk += hrsValue;

                if (subjectCode) {
                    uniqueSubjects.add(subjectCode);
                    if (!subjectUnitsMap[subjectCode]) {
                        subjectUnitsMap[subjectCode] = units;
                    }
                }
            });

            const totalLecElement = document.getElementById('totalLec');
            const totalLabElement = document.getElementById('totalLab');
            const totalHrsWkElement = document.getElementById('totalHrsWk');
            if (totalLecElement) totalLecElement.textContent = totalLec;
            if (totalLabElement) totalLabElement.textContent = totalLab;
            if (totalHrsWkElement) totalHrsWkElement.textContent = totalHrsWk;

            const numPrepsElement = document.getElementById('numPreparations');
            const totalUnitsElement = document.getElementById('totalLoadUnits');
            const totalHrsElement = document.getElementById('totalLoadHrs');
            if (numPrepsElement) numPrepsElement.value = uniqueSubjects.size;

            const totalLoadUnits = totalLec + totalLab;
            if (totalUnitsElement) totalUnitsElement.value = totalLoadUnits;

            if (totalHrsElement) totalHrsElement.value = totalHrsWk;

            calculateDayHours();
        }

        function addRow() {
            const tbody = document.querySelector('#scheduleTable tbody');
            if (!tbody) return;

            const lastRow = tbody.lastElementChild;
            if (!lastRow) return;

            const newRow = lastRow.previousElementSibling.cloneNode(true);

            // Clear all inputs in the new row
            const inputs = newRow.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                    if (input.name === 'lec_hours[]') {
                        input.value = '2';
                    } else if (input.name === 'lab_hours[]') {
                        input.value = '0';
                    } else if (input.name === 'hrs_per_week[]') {
                        input.value = '0';
                    }
                }
            });

            // Set default select values
            const selects = newRow.querySelectorAll('select');
            selects.forEach(select => {
                if (select.name === 'subject_type[]') {
                    select.selectedIndex = 0;
                } else {
                    select.selectedIndex = 0;
                }
            });

            // Insert before the last row (total row)
            tbody.insertBefore(newRow, lastRow);

            // Add event listeners to the new row
            addRowEventListeners(newRow);
        }

        function addRowEventListeners(row) {
            const subjectCodeSelect = row.querySelector('select[name="subject_code[]"]');
            const subjectTypeSelect = row.querySelector('select[name="subject_type[]"]');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');

            if (subjectCodeSelect) {
                subjectCodeSelect.addEventListener('change', function() {
                    updateSubjectDescription(this);
                });
            }

            if (subjectTypeSelect) {
                subjectTypeSelect.addEventListener('change', function() {
                    updateSubjectType(this);
                });
            }

            if (lecInput) {
                lecInput.addEventListener('change', function() {
                    calculateHours(this);
                });
            }

            if (labInput) {
                labInput.addEventListener('change', function() {
                    calculateHours(this);
                });
            }

            // Add change listeners to day inputs
            const dayInputs = row.querySelectorAll('input[name^="monday"], input[name^="tuesday"], input[name^="wednesday"], input[name^="thursday"], input[name^="friday"], input[name^="saturday"], input[name^="sunday"]');
            dayInputs.forEach(input => {
                input.addEventListener('change', calculateDayHours);
            });
        }


        function saveForm() {
            document.getElementById('loadingForm').submit();
        }

        function sendToProfessor() {
            alert('Form sent to professor successfully!');
        }



        // Update subject description when code is selected
        function updateSubjectType(select) {
            const row = select.closest('tr');
            if (!row) return;

            const subjectDescriptionInput = row.querySelector('.subject-description');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');

            if (!subjectDescriptionInput || !lecInput || !labInput) return;

            let currentDescription = subjectDescriptionInput.value;
            currentDescription = currentDescription.replace(/\(LEC\)|\(LAB\)/g, '').trim();

            if (select.value === '(LEC)') {
                subjectDescriptionInput.value = currentDescription + ' ' + select.value;
                lecInput.value = '2';
                labInput.value = '0';
            } else if (select.value === '(LAB)') {
                subjectDescriptionInput.value = currentDescription + ' ' + select.value;
                lecInput.value = '0';
                labInput.value = '1';
            } else if (select.value === '') {
                // This is the new empty option - treat it like LAB
                subjectDescriptionInput.value = currentDescription;
                lecInput.value = '0';
                labInput.value = '1';
            }

            calculateHours(lecInput);
        }


        function updateSubjectDescription(select) {
            const row = select.closest('tr');
            if (!row) return;

            const subjectDescriptionInput = row.querySelector('.subject-description');
            const subjectTypeSelect = row.querySelector('select[name="subject_type[]"]');
            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');
            const periodInput = row.querySelector('input[name="period_col[]"]');

            if (!subjectDescriptionInput || !subjectTypeSelect || !lecInput || !labInput || !periodInput) return;

            const subjectCode = select.value;

            if (subjectCode) {
                const subject = subjects.find(s => s.subject_code === subjectCode);
                if (subject) {
                    let baseDescription = subject.subject_name.replace(/\(LEC\)|\(LAB\)/g, '').trim();
                    subjectDescriptionInput.value = baseDescription;

                    const subjectType = subjectTypeSelect.value;
                    if (subjectType !== '') {
                        subjectDescriptionInput.value = baseDescription + ' ' + subjectType;
                    }

                    if (subjectType === '(LEC)') {
                        lecInput.value = '2';
                        labInput.value = '0';
                    } else if (subjectType === '(LAB)' || subjectType === '') {
                        // Treat empty type same as LAB
                        lecInput.value = '0';
                        labInput.value = '1';
                    }

                    const periodElement = document.getElementById('period');
                    if (periodElement) {
                        periodInput.value = periodElement.value;
                    }

                    calculateHours(lecInput);
                }
            } else {
                subjectDescriptionInput.value = '';
                periodInput.value = '';
                lecInput.value = '0';
                labInput.value = '0';
            }

            calculateTotals();
        }

        // Calculate hours per week with correct lab hour calculations
        function calculateHours(input) {
            if (!input) return;

            const row = input.closest('tr');
            if (!row) return;

            const lecInput = row.querySelector('input[name="lec_hours[]"]');
            const labInput = row.querySelector('input[name="lab_hours[]"]');
            const hrsPerWeekInput = row.querySelector('input[name="hrs_per_week[]"]');

            if (!lecInput || !labInput || !hrsPerWeekInput) return;

            const lecHours = parseFloat(lecInput.value) || 0;
            const labHours = parseFloat(labInput.value) || 0;

            let totalHrs = lecHours;

            if (labHours === 1) {
                totalHrs += 3;
            } else if (labHours === 2) {
                totalHrs += 2;
            } else if (labHours > 0) {
                totalHrs += labHours;
            }

            hrsPerWeekInput.value = totalHrs;
            calculateTotals();
        }

        function calculateDayHours() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            const dayHours = {};

            days.forEach(day => {
                dayHours[day] = 0;
            });

            const rows = document.querySelectorAll('#scheduleTable tbody tr:not(:last-child)');
            rows.forEach(row => {
                const hrsInput = row.querySelector('input[name="hrs_per_week[]"]');
                if (!hrsInput) return;

                const hrsValue = parseFloat(hrsInput.value) || 0;

                days.forEach(day => {
                    const dayInput = row.querySelector(`input[name="${day}[]"]`);
                    if (dayInput && dayInput.value) {
                        dayHours[day] += hrsValue;
                    }
                });
            });

            const nonZeroDays = Object.entries(dayHours).filter(([day, hours]) => hours > 0);
            const lowestHrsElement = document.getElementById('lowestHrs');
            const highestHrsElement = document.getElementById('highestHrs');

            if (nonZeroDays.length > 0) {
                const sortedDays = nonZeroDays.sort((a, b) => a[1] - b[1]);
                if (lowestHrsElement) lowestHrsElement.value = sortedDays[0][1];
                if (highestHrsElement) highestHrsElement.value = sortedDays[sortedDays.length - 1][1];
            } else {
                if (lowestHrsElement) lowestHrsElement.value = 0;
                if (highestHrsElement) highestHrsElement.value = 0;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Update period column when period input changes
            document.getElementById('period').addEventListener('change', function() {
                document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                    if (input.closest('tr').querySelector('select[name="subject_code[]"]').value) {
                        input.value = this.value;
                    }
                });
            });

            const initialRow = document.querySelector('#scheduleTable tbody tr');
            addRowEventListeners(initialRow);

            document.getElementById('addRowBtn').addEventListener('click', addRow);

            document.getElementById('saveBtn').addEventListener('click', function(e) {
                e.preventDefault();
                validateAndSubmitForm();
            });

            document.getElementById('sendBtn').addEventListener('click', sendToProfessor);

            document.getElementById('yes').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('no').checked = false;
                }
            });

            document.getElementById('no').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('yes').checked = false;
                }
            });

            document.querySelector('.close-modal')?.addEventListener('click', closeSaveModal);

            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'confirmSaveBtn') {
                    const schoolYear = document.getElementById('schoolYear').value;
                    const semester = document.getElementById('semester').value;

                    if (!schoolYear) {
                        alert('Please enter School Year');
                        return;
                    }

                    const schoolYearInput = document.createElement('input');
                    schoolYearInput.type = 'hidden';
                    schoolYearInput.name = 'school_year';
                    schoolYearInput.value = schoolYear;

                    const semesterInput = document.createElement('input');
                    semesterInput.type = 'hidden';
                    semesterInput.name = 'semester';
                    semesterInput.value = semester;

                    // Add them to the form
                    const form = document.getElementById('loadingForm');
                    form.appendChild(schoolYearInput);
                    form.appendChild(semesterInput);

                    // Submit the form
                    form.submit();
                }
            });
        });

        function closeSaveModal() {
            const modal = document.getElementById('saveModal');
            if (modal) modal.style.display = "none";
        }

        // Handle save confirmation
        document.getElementById('confirmSaveBtn').addEventListener('click', function() {
            const schoolYear = document.getElementById('schoolYear').value;
            const semester = document.getElementById('semester').value;

            if (!schoolYear) {
                alert('Please enter School Year');
                return;
            }

            // Create hidden inputs for school year and semester
            const schoolYearInput = document.createElement('input');
            schoolYearInput.type = 'hidden';
            schoolYearInput.name = 'school_year';
            schoolYearInput.value = schoolYear;

            const semesterInput = document.createElement('input');
            semesterInput.type = 'hidden';
            semesterInput.name = 'semester';
            semesterInput.value = semester;

            // Add them to the form
            const form = document.getElementById('loadingForm');
            form.appendChild(schoolYearInput);
            form.appendChild(semesterInput);

            // Submit the form
            form.submit();
        });

        document.getElementById('loadingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // First validate required fields in the main form
            const period = document.getElementById('period').value;
            const college = document.getElementById('college').value;

            if (!period || !college) {
                alert('Please fill in all required fields (Period and College/Department)');
                return;
            }

            const subjectCodes = document.querySelectorAll('select[name="subject_code[]"]');
            let hasSubject = false;
            subjectCodes.forEach(select => {
                if (select.value) hasSubject = true;
            });

            if (!hasSubject) {
                alert('Please add at least one subject');
                return;
            }

            // If all validations pass, open the save modal
            document.getElementById('saveModal').style.display = "flex";
        });

        function initializeEventListeners() {
            updateDateTime();
            setInterval(updateDateTime, 1000);

            const periodElement = document.getElementById('period');
            if (periodElement) {
                periodElement.addEventListener('change', function() {
                    document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                        const rowSelect = input.closest('tr')?.querySelector('select[name="subject_code[]"]');
                        if (rowSelect?.value) {
                            input.value = this.value;
                        }
                    });
                });
            }

            const initialRow = document.querySelector('#scheduleTable tbody tr');
            if (initialRow) addRowEventListeners(initialRow);

            const addRowBtn = document.getElementById('addRowBtn');
            if (addRowBtn) {
                addRowBtn.addEventListener('click', addRow);
            }

            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    validateAndSubmitForm();
                });
            }

            const yesCheckbox = document.getElementById('yes');
            const noCheckbox = document.getElementById('no');
            if (yesCheckbox && noCheckbox) {
                yesCheckbox.addEventListener('change', function() {
                    if (this.checked) noCheckbox.checked = false;
                });
                noCheckbox.addEventListener('change', function() {
                    if (this.checked) yesCheckbox.checked = false;
                });
            }

            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            if (confirmSaveBtn) {
                confirmSaveBtn.addEventListener('click', function() {
                    const schoolYearInput = document.getElementById('schoolYear');
                    const semesterSelect = document.getElementById('semester');

                    if (!schoolYearInput || !semesterSelect) return;

                    const schoolYear = schoolYearInput.value;
                    const semester = semesterSelect.value;

                    if (!schoolYear) {
                        alert('Please enter School Year');
                        return;
                    }

                    // Create hidden inputs for school year and semester
                    const schoolYearHidden = document.createElement('input');
                    schoolYearHidden.type = 'hidden';
                    schoolYearHidden.name = 'school_year';
                    schoolYearHidden.value = schoolYear;

                    const semesterHidden = document.createElement('input');
                    semesterHidden.type = 'hidden';
                    semesterHidden.name = 'semester';
                    semesterHidden.value = semester;

                    // Add them to the form
                    const form = document.getElementById('loadingForm');
                    if (form) {
                        form.appendChild(schoolYearHidden);
                        form.appendChild(semesterHidden);
                        form.submit();
                    }
                });
            }

            const loadingForm = document.getElementById('loadingForm');
            if (loadingForm) {
                loadingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    validateAndSubmitForm();
                });
            }
        }

        function updatePeriodField() {
            const semesterSelect = document.getElementById('Sem');
            const yearSelect = document.getElementById('AY');
            const periodInput = document.getElementById('period');

            if (!semesterSelect || !yearSelect || !periodInput) return;

            const selectedSemester = semesterSelect.value;
            const selectedYear = yearSelect.value;

            if (selectedSemester && selectedYear) {
                const matchingSemester = <?php echo json_encode($semesters_list); ?>.find(sem =>
                    sem.semester === selectedSemester && sem.academic_year === selectedYear
                );

                if (matchingSemester) {
                    const dateFrom = new Date(matchingSemester.date_from);
                    const dateTo = new Date(matchingSemester.date_to);

                    const monthNames = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE",
                        "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"
                    ];

                    const fromMonth = monthNames[dateFrom.getMonth()];
                    const fromDay = dateFrom.getDate();
                    const toMonth = monthNames[dateTo.getMonth()];
                    const toDay = dateTo.getDate();
                    const year = dateTo.getFullYear();

                    let periodText = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${year}`;

                    if (fromMonth === toMonth) {
                        periodText = `${fromMonth} ${fromDay} - ${toDay}, ${year}`;
                    }

                    periodInput.value = periodText;

                    document.querySelectorAll('input[name="period_col[]"]').forEach(input => {
                        const rowSelect = input.closest('tr')?.querySelector('select[name="subject_code[]"]');
                        if (rowSelect?.value) {
                            input.value = periodText;
                        }
                    });
                }
            }
        }

        function updateSemesterDropdown() {
            const yearSelect = document.getElementById('AY');
            const semesterSelect = document.getElementById('Sem');

            if (!yearSelect || !semesterSelect) return;

            const selectedYear = yearSelect.value;

            semesterSelect.innerHTML = '<option value="" hidden>Select Semester</option>';

            if (!selectedYear) {
                semesterSelect.disabled = true;
                return;
            }

            semesterSelect.disabled = false;

            const semestersForYear = <?php echo json_encode($semesters_list); ?>.filter(
                sem => sem.academic_year === selectedYear
            );

            const uniqueSemesters = [...new Set(semestersForYear.map(sem => sem.semester))];

            uniqueSemesters.forEach(semester => {
                const option = document.createElement('option');
                option.value = semester;
                option.textContent = semester;
                semesterSelect.appendChild(option);
            });

            updatePeriodField();
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', initializeEventListeners);
    </script>

    <div class="modal" id="saveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-save"></i> Save Form</h2>
                <button class="close-modal" onclick="closeSaveModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <label for="schoolYear">Academic Year</label>
                    <input type="text" id="schoolYear" name="school_year" placeholder="e.g. 2024-2025" required>
                </div>
                <div class="form-row">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" class="progress-dropdown" required>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeSaveModal()">Cancel</button>
                <button type="button" class="btn btn-submit" id="confirmSaveBtn">Save</button>
            </div>
        </div>
    </div>

    <script>
        // Update the openSaveModal function to auto-fill the values
        function openSaveModal() {
            const modal = document.getElementById('saveModal');
            if (!modal) return;

            // Get values from the dropdowns
            const semesterSelect = document.getElementById('Sem');
            const yearSelect = document.getElementById('AY');
            const modalYearInput = document.getElementById('schoolYear');
            const modalSemesterSelect = document.getElementById('semester');

            if (semesterSelect && yearSelect && modalYearInput && modalSemesterSelect) {
                // Set the academic year in the modal
                modalYearInput.value = yearSelect.value;

                // Map the semester values from the dropdown to the modal options
                const selectedSemesterText = semesterSelect.options[semesterSelect.selectedIndex].text;

                // Simple mapping - adjust as needed based on your actual values
                if (selectedSemesterText.includes('1st')) {
                    modalSemesterSelect.value = '1st';
                } else if (selectedSemesterText.includes('2nd')) {
                    modalSemesterSelect.value = '2nd';
                } else if (selectedSemesterText.includes('Summer')) {
                    modalSemesterSelect.value = 'Summer';
                } else {
                    // Default to 1st semester if no match
                    modalSemesterSelect.value = '1st';
                }
            }

            modal.style.display = "flex";
        }

        function validateAndSubmitForm() {
            const period = document.getElementById('period')?.value;
            const college = document.getElementById('college')?.value;

            if (!period || !college) {
                alert('Please fill in all required fields (Period and College/Department)');
                return;
            }

            const subjectCodes = document.querySelectorAll('select[name="subject_code[]"]');
            let hasSubject = false;
            subjectCodes.forEach(select => {
                if (select.value) hasSubject = true;
            });

            if (!hasSubject) {
                alert('Please add at least one subject');
                return;
            }

            openSaveModal();
        }
    </script>
</body>

</html>