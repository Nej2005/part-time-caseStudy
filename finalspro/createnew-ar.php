<?php
session_start();
include '../Backend/db_connect.php';

// Check if professor_id is provided
if (!isset($_GET['professor_id'])) {
    header("Location: emp-ar.php");
    exit();
}

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

$no_data_found = false;
if (!$ar_header) {
    $no_data_found = true;
}

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
$semester = $ar_header['semester'] ?? '1st Semester';
$academic_year = $ar_header['academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
$period = $ar_header['period'] ?? $month_display;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part-Time Faculty Accomplishment Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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

        body.sidebar-open .content-area {
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
            background-color: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            max-width: 1000px;
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

        .title-section p {
            font-size: 14px;
            margin-top: 10px;
        }

        .editable-span {
            border-bottom: 1px dotted #888;
            cursor: text;
            display: inline-block;
            min-width: 50px;
            padding: 0 3px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
        }

        .info-label {
            width: 150px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .info-input {
            flex: 1;
            border-bottom: 1px solid #333;
            padding: 2px 0;
            min-height: 25px;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            border: none;
            padding: 5px 2px;
            font-size: 14px;
            outline: none;
            background-color: transparent;
        }

        input:read-only {
            color: #333;
            cursor: default;
        }

        .info-input input {
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #3b5525;
            color: white;
            padding: 10px 10px;
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #1a2a0d;
            border-left: none;
            border-right: none;
            border-top: none;
        }

        .ar-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 12px;
        }

        .ar-table th:nth-child(1),
        .ar-table td:nth-child(1) {
            width: 270px;
        }

        .ar-table th:nth-child(3),
        .ar-table td:nth-child(3) {
            width: 130px;
        }

        .ar-table th:nth-child(2),
        .ar-table td:nth-child(2) {
            width: 60px;
            text-align: center;
        }

        .ar-table th:nth-child(4),
        .ar-table td:nth-child(4) {
            width: 140px;
            text-align: center;
        }

        .ar-table th:nth-child(5),
        .ar-table td:nth-child(5) {
            width: 180px;
            text-align: center;
        }

        .ar-table td:nth-child(5) input[type="text"] {
            width: 45%;
            display: inline-block;
            margin-right: 5px;
        }

        .ar-table td:nth-child(5) input[type="number"] {
            width: 45%;
            display: inline-block;
        }

        .ar-table td input[type="number"] {
            text-align: center;
        }

        .ar-table tbody tr:last-child td {
            border-bottom: none;
        }

        .ar-table tfoot {
            font-weight: bold;
            background-color: #f5f5f5;
            color: #333;
            border-top: 2px solid #cccccc;
        }

        .ar-table tfoot td {
            border: 1px solid #e0e0e0;
        }

        .ar-table tfoot input {
            font-weight: bold;
            text-align: center;
        }

        .hours-rendered-input {
            width: 90%;
            margin: 0 auto;
            text-align: center;
        }

        /* Style for Editable Hours Input (Screen Only) */
        .ar-table tbody .editable-hours-input {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 5px 8px;
            text-align: center;
            width: 80%;
            margin: 0 auto;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.07);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .ar-table tbody .editable-hours-input:focus {
            border-color: #3b5525;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.07),
                0 0 0 2px rgba(59, 85, 37, 0.25);
            outline: none;
            background-color: #fff;
        }

        #uploadStatus {
            margin-top: 10px;
            font-size: 0.9em;
            color: green;
        }

        #uploadStatus.error {
            color: red;
        }

        #imagePreview {
            max-width: 150px;
            max-height: 40px;
            border: none;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }

        .signature-section {
            page-break-inside: avoid;
            margin-top: 30px;
        }

        .signature-section>div:first-child {
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 80px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            font-size: 14px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 60px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .signature-name {
            text-align: center;
            width: 100%;
            border: none;
            background: transparent;
            font-weight: bold;
        }

        .checked-by-section {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            page-break-inside: avoid;
        }

        .checked-by-line {
            width: 300px;
            margin: 10px auto 5px auto;
            border-bottom: 1px solid #000;
            height: 30px;
        }

        .signature-line img {
            max-width: 150px;
            max-height: 80px;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }

        .checked-by-name {
            text-align: center;
            width: 300px;
            margin: 0 auto;
            display: block;
            border: none;
            background: transparent;
            font-weight: bold;
        }

        .signature-box input {
            font-size: 13px;
            border: none !important;
            text-align: center;
            font-weight: bold;
        }

        .ar-input {
            width: 100%;
            padding: 4px;
            box-sizing: border-box;
            border: none;
            font-size: 12px;
            background-color: transparent;
        }

        .ar-input:disabled,
        .ar-input:read-only {
            background-color: transparent;
            border: none;
            cursor: default;
            color: #333;
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

        .action-button {
            background-color: #2c5e1a;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            margin-left: 10px;
            transition: background-color 0.2s ease;
            font-size: 13px;
        }

        .action-button:first-child {
            margin-left: 0;
        }

        .action-button:hover {
            background-color: #1a3e0a;
        }

        .button-icon {
            margin-right: 8px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .right-buttons {
            display: flex;
            gap: 3px;
        }

        .btn {
            display: inline-block;
            background-color: #3b5525;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
            font-size: 0.9rem;
        }

        .btn:hover {
            background-color: #1a2a0d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #attachHeadSignatureButton {
            background-color: #ccc;
            color: #333;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            font-weight: 700;
        }

        #saveHeadSignature {
            background-color: #D0C74F;
            color: #333;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            font-weight: 700;
        }

        .button-actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .button-group-right {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .modal-body {
            margin-bottom: 15px;
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

        .action-button:disabled {
            background-color: #cccccc !important;
            color: #666666 !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }

        .action-button:disabled:hover {
            background-color: #cccccc !important;
            transform: none !important;
            box-shadow: none !important;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
            }

            .info-label {
                width: 100px;
                font-size: 13px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 15px;
            }

            .info-input {
                width: 100%;
                border-bottom: 1px solid #999;
            }

            .button-container {
                flex-direction: column;
                align-items: stretch;
            }

            .action-button {
                margin: 5px 0;
                width: 100%;
                justify-content: center;
            }

            .ar-table th,
            .ar-table td {
                font-size: 10px;
                padding: 5px;
            }

            .ar-table thead th {
                font-size: 9px;
                padding: 8px 5px;
            }

            .title-section h1 {
                font-size: 20px;
            }

            .title-section h2 {
                font-size: 16px;
            }

            .title-section h3 {
                font-size: 14px;
            }

            .signature-row {
                flex-direction: column;
                align-items: center;
            }

            .signature-box {
                width: 80%;
                margin-bottom: 20px;
            }
        }

        @media print {
            body {
                background-color: #fff !important;
                color: #000 !important;
                margin: 0;
                padding: 0;
                font-size: 10pt;
                width: 100%;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            header,
            #action-controls,
            #uploadStatus,
            .back-button,
            .back-btn,
            .pdf-export-hide {
                display: none !important;
            }

            .content-area {
                margin-left: 0 !important;
                width: 100% !important;
                transform: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .form-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto !important;
                padding: 10mm !important;
                max-width: 100% !important;
                width: auto !important;
                border-radius: 0 !important;
                page-break-inside: avoid;
            }

            .ar-table {
                font-size: 9pt;
                border: 1px solid #333 !important;
                margin-top: 15px;
                margin-bottom: 15px;
                border-radius: 0 !important;
            }

            .ar-table th,
            .ar-table td {
                padding: 4px;
                border: 1px solid #666 !important;
                color: #000 !important;
            }

            .ar-table th {
                background-color: #e0e0e0 !important;
                color: #000 !important;
                text-transform: none;
                letter-spacing: normal;
                font-weight: bold;
                border-bottom: 1px solid #666 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ar-table tfoot {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ar-table tfoot td {
                font-weight: bold !important;
            }

            #imagePreview,
            .signature-line img {
                display: block !important;
                border: none !important;
                max-width: 120px !important;
                max-height: 40px !important;
                margin: 0 auto !important;
                object-fit: contain;
            }

            .signature-line {
                border-bottom: 1px solid #000 !important;
                height: 40px !important;
                margin-bottom: 1px !important;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .signature-box {
                page-break-inside: avoid;
            }

            .signature-section {
                margin-top: 25px;
            }

            .signature-box input {
                border: none !important;
                color: #000 !important;
                font-weight: bold !important;
            }

            /* Print styles for inputs */
            input[type="text"],
            input[type="number"],
            select,
            .ar-input,
            .editable-span,
            .editable-hours-input {
                border: none !important;
                background-color: transparent !important;
                color: #000 !important;
                box-shadow: none !important;
                padding: 1px 0 !important;
                appearance: none !important;
                -webkit-appearance: none !important;
                font-size: inherit !important;
                font-family: inherit !important;
                line-height: inherit !important;
                cursor: default !important;
                border-bottom: none !important;
                border-radius: 0 !important;
                text-align: inherit !important;
                width: 100% !important;
                margin: 0 !important;
            }

            .ar-table td input[type="number"].editable-hours-input {
                text-align: center !important;
            }

            input:read-only,
            .ar-input:read-only {
                color: #000 !important;
            }

            .info-input {
                border-bottom: 1px solid #000 !important;
                padding-bottom: 1px !important;
                min-height: 20px !important;
            }

            .info-input input {
                padding: 0 !important;
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

            tfoot {
                display: table-footer-group;
            }

            h1,
            h2,
            h3,
            h4,
            h5,
            h6 {
                page-break-after: avoid;
            }

            p {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="content-area">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <button class="back-button pdf-export-hide" onclick="window.history.back()">
                        ←
                    </button>
                    <img alt="School Logo" class="logo" src="Logo.ico" />
                    <div class="school-name">Faculty Portal</div>
                </div>
                <div class="date-time" id="date"></div>
            </div>
        </header>

        <div class="form-container" id="report-form">
            <div class="title-section">
                <h1>PART-TIME FACULTY</h1>
                <h2>ACCOMPLISHMENT REPORT</h2>
                <h3>
                    <input type="text" class="editable-span" style="width: 120px;"
                        value="<?php echo htmlspecialchars($semester); ?>"
                        data-original-value="<?php echo htmlspecialchars($semester); ?>"
                        readonly>
                    A.Y.
                    <input type="text" class="editable-span" style="width: 100px;" value="<?php echo htmlspecialchars($academic_year); ?>" readonly>
                </h3>
                <p>
                    For the Month of
                    <span class="editable-span"><?php echo htmlspecialchars($period); ?></span>
                </p>
            </div>

            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Name of Faculty</div>
                    <div class="info-input">
                        <input type="text" id="faculty-name-input" value="<?php
                                                                            echo htmlspecialchars($professor['first_name']) . ' ' .
                                                                                (!empty($professor['middle_initial']) ? htmlspecialchars($professor['middle_initial']) . '. ' : '') .
                                                                                htmlspecialchars($professor['last_name']);
                                                                            ?>" readonly />
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
                        <input type="number" value="<?php echo htmlspecialchars($ar_header['no_of_units'] ?? '0'); ?>" style="width: 50px" readonly />
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
                                    <input type="text" value="<?= htmlspecialchars($detail['subject_description']) ?>" class="ar-input" readonly />
                                </td>
                                <td>
                                    <input type="number" value="<?= $detail['no_of_units'] ?>" style="width: 40px" class="ar-input unit-input" readonly />
                                </td>
                                <td>
                                    <input type="text" value="<?= htmlspecialchars($detail['inclusive_dates']) ?>" class="ar-input inclusive-dates-input" readonly />
                                </td>
                                <td>
                                    <input type="text" value="<?= htmlspecialchars($detail['class_time_schedule']) ?>" class="ar-input" readonly />
                                </td>
                                <td>
                                    <input type="text" value="<?= htmlspecialchars($detail['day_of_week']) ?>" class="ar-input day-input" style="width: 60%; display: inline-block" readonly />
                                    <input type="number" value="<?= $detail['hours_rendered'] ?>" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" readonly />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><input type="text" class="ar-input" readonly /></td>
                            <td><input type="number" style="width: 40px" class="ar-input unit-input" readonly /></td>
                            <td><input type="text" class="ar-input inclusive-dates-input" readonly /></td>
                            <td><input type="text" class="ar-input" readonly /></td>
                            <td>
                                <input type="text" class="ar-input day-input" style="width: 60%; display: inline-block" readonly />
                                <input type="number" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" readonly />
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

            <div class="button-actions-container" id="action-controls">
                <button id="attachHeadSignatureButton" class="pdf-export-hide">
                    <i class="fas fa-paperclip" style="margin-right: 6px;"></i>
                    Attach Head Signature
                </button>

                <div class="button-group-right">
                    <button id="saveHeadSignature" class="pdf-export-hide">
                        <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                    </button>
                    <button id="printButton" class="action-button" disabled>
                        <i class="fas fa-print button-icon"></i> Export
                    </button>
                </div>
            </div>

            <div id="uploadStatus"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric',
                hour12: true
            };
            document.getElementById('date').textContent = now.toLocaleDateString('en-US', options);

            let currentHeadSignature = null;

            document.getElementById('attachHeadSignatureButton').addEventListener('click', function() {
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.accept = 'image/png, image/jpeg';

                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                        alert('Please upload a JPG or PNG image.');
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('Image must be less than 2MB.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        currentHeadSignature = event.target.result;

                        // Create an image element
                        const img = document.createElement('img');
                        img.src = currentHeadSignature;
                        img.style.maxWidth = '120px';
                        img.style.maxHeight = '40px';
                        img.style.display = 'block';
                        img.style.margin = '0 auto';
                        img.style.objectFit = 'contain';

                        const deanSignatureBox = document.querySelector('.signature-box:nth-child(2)');
                        if (deanSignatureBox) {
                            deanSignatureBox.innerHTML = `
                                <div class="signature-line">
                                    <img src="${currentHeadSignature}" alt="Head Signature">
                                </div>
                                <input type="text" id="head-name-display" value="<?php echo htmlspecialchars($department_head); ?>" class="signature-name" readonly />
                                <div>Dean/Dept. Head printed name Over Signature</div>
                            `;
                            document.getElementById('uploadStatus').textContent = 'Head signature uploaded successfully!';
                            document.getElementById('uploadStatus').className = '';
                        }
                    };
                    reader.readAsDataURL(file);
                });

                // Trigger the file selection dialog
                fileInput.click();
            });

            document.getElementById('saveHeadSignature').addEventListener('click', function() {
                const checkedByValue = document.getElementById('checked-by-input').value;

                if (!currentHeadSignature) {
                    alert('Upload Dean/Head Signature first.');
                    return;
                }

                const saveButton = this;
                const originalText = saveButton.innerHTML;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i> Saving...';
                saveButton.disabled = true;

                // Get the current AR ID or create a new one if needed
                const professorId = <?php echo $professor_id; ?>;
                const currentMonth = '<?php echo $currentMonth; ?>';
                const currentYear = '<?php echo $currentYear; ?>';

                // Prepare the data to send
                const formData = new FormData();
                formData.append('professor_id', professorId);
                formData.append('month', currentMonth);
                formData.append('year', currentYear);
                formData.append('head_signature', currentHeadSignature);
                formData.append('checked_by', checkedByValue);

                fetch('../Backend/AR/save_head_signature.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('uploadStatus').textContent = 'Data saved successfully!';
                            document.getElementById('uploadStatus').className = '';
                            alert('Data saved successfully!');

                            const exportButton = document.getElementById('printButton');
                            exportButton.disabled = false;
                            exportButton.classList.remove('disabled-button');
                            exportButton.classList.add('action-button');
                        } else {
                            document.getElementById('uploadStatus').textContent = 'Error saving data: ' + (data.message || 'Unknown error');
                            document.getElementById('uploadStatus').className = 'error';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('uploadStatus').textContent = 'An error occurred while saving the data.';
                        document.getElementById('uploadStatus').className = 'error';
                    })
                    .finally(() => {
                        saveButton.innerHTML = originalText;
                        saveButton.disabled = false;
                    });
            });

            document.getElementById('printButton').addEventListener('click', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const professorId = urlParams.get('professor_id');

                window.location.href = '../Backend/Exporting/export_ar.php?professor_id=' + professorId;
            });

            const semesterInputs = document.querySelectorAll('input.editable-span[value="1st Semester"], input.editable-span[value="2nd Semester"]');
            semesterInputs.forEach(input => {
                const originalValue = input.getAttribute('data-original-value');
                input.value = convertSemesterText(originalValue);
            });

            // Show no data modal if no data found
            <?php if ($no_data_found): ?>
                showNoDataModal();
            <?php endif; ?>

            <?php if ($ar_header && !$no_data_found): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('printButton').disabled = false;
                });
            <?php endif; ?>
        });

        function convertSemesterText(semester) {
            const conversions = {
                '1st Semester': 'First Semester',
                '2nd Semester': 'Second Semester',
                'Summer': 'Summer'
            };
            return conversions[semester] || semester;
        }

        function showNoDataModal() {
            document.getElementById('noDataModal').style.display = "flex";
        }

        function closeNoDataModal() {
            document.getElementById('noDataModal').style.display = "none";
            window.location.href = 'emp-ar.php';
        }
    </script>


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