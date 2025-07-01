<?php
session_start();

$is_viewing_specific_ar = isset($_GET['year']) && isset($_GET['month']);

$current_semester = $_GET['semester'] ?? ($_SESSION['current_semester'] ?? "1st Semester");
$current_semester = match ($current_semester) {
  '1st' => '1st Semester',
  '2nd' => '2nd Semester',
  'Summer' => 'Summer',
  '1st Semester' => '1st Semester',
  '2nd Semester' => '2nd Semester',
  default => $current_semester
};

$current_ay = $_GET['ay'] ?? ($_SESSION['current_ay'] ?? date('Y') . '-' . (date('Y') + 1));

// Store in session
$_SESSION['current_semester'] = $current_semester;
$_SESSION['current_ay'] = $current_ay;

require_once '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$professor_id = $_SESSION['professor_id'] ?? null;
if (!$professor_id) {
  header("Location: pt-dash.php");
  exit();
}

// Get available semesters and academic years for this professor from Form_Loads
$available_periods = [];
if ($professor_id) {
  $stmt = $conn->prepare("
        SELECT DISTINCT 
            fl.semester,
            fl.school_year AS academic_year  
        FROM Form_Loads fl
        WHERE fl.professor_id = ? 
        ORDER BY fl.school_year DESC, 
            CASE 
                WHEN fl.semester = '1st Semester' THEN 1
                WHEN fl.semester = '2nd Semester' THEN 2
                WHEN fl.semester = 'Summer' THEN 3
                ELSE 4
            END
    ");
  $stmt->bind_param("i", $professor_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $year = $row['academic_year'] ?? '';
    $semester = $row['semester'] ?? '';

    if ($year && $semester) {
      if (!isset($available_periods[$year])) {
        $available_periods[$year] = [];
      }

      if (!in_array($semester, $available_periods[$year])) {
        $available_periods[$year][] = $semester;
      }
    }
  }
  $stmt->close();
}

// Initialize variables
$professor_name = "Professor Name";
$department = "Department";
$department_head = "Dean/Dept. Head name";

// Get professor details
if ($professor_id) {
  // Get professor info
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

  $stmt = $conn->prepare("SELECT department_head FROM Courses WHERE department = ? LIMIT 1");
  $stmt->bind_param("s", $courses_department);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $department_head = htmlspecialchars($row['department_head']);
  }
  $stmt->close();
}

// Get current month and format
$current_month = date('F');
$current_year = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, date('n'), $current_year);
$month_display = "$current_month 1-$days_in_month, $current_year";


error_log("Fetching form load data for professor_id: $professor_id, semester: $current_semester, school_year: $current_ay");
// Get subjects from formload for the current semester
$subjects = [];
if ($professor_id) {
  $stmt = $conn->prepare("SELECT 
        fld.detail_id, 
        fld.subject_code, 
        s.subject_name, 
        fld.subject_description, 
        fld.lec_hours, 
        fld.lab_hours, 
        fld.hrs_per_week, 
        GROUP_CONCAT(DISTINCT fld.section SEPARATOR ', ') AS sections,
        fld.monday, 
        fld.tuesday, 
        fld.wednesday, 
        fld.thursday, 
        fld.friday, 
        fld.saturday, 
        fld.sunday,
        GROUP_CONCAT(DISTINCT 
            CASE 
                WHEN fld.sunday != '' THEN CONCAT('Sun ', fld.sunday)
                WHEN fld.saturday != '' THEN CONCAT('Sat ', fld.saturday)
                WHEN fld.friday != '' THEN CONCAT('Fri ', fld.friday)
                WHEN fld.thursday != '' THEN CONCAT('Thu ', fld.thursday)
                WHEN fld.wednesday != '' THEN CONCAT('Wed ', fld.wednesday)
                WHEN fld.tuesday != '' THEN CONCAT('Tue ', fld.tuesday)
                WHEN fld.monday != '' THEN CONCAT('Mon ', fld.monday)
            END ORDER BY 
                CASE 
                    WHEN fld.sunday != '' THEN 1
                    WHEN fld.saturday != '' THEN 2
                    WHEN fld.friday != '' THEN 3
                    WHEN fld.thursday != '' THEN 4
                    WHEN fld.wednesday != '' THEN 5
                    WHEN fld.tuesday != '' THEN 6
                    WHEN fld.monday != '' THEN 7
                END SEPARATOR ', ') AS schedule,
        s.units
        FROM Form_Load_Details fld
        JOIN Form_Loads fl ON fld.form_id = fl.form_id
        JOIN Subjects s ON fld.subject_code = s.subject_code
        WHERE fl.professor_id = ?
        AND fl.school_year = ?
        AND fl.semester = ?
        AND (fld.disabled IS NULL OR fld.disabled = 0)
        GROUP BY 
            fld.detail_id,  -- Include in GROUP BY
            fld.subject_code, 
            s.subject_name, 
            fld.subject_description,
            fld.lec_hours, 
            fld.lab_hours, 
            fld.hrs_per_week,
            fld.monday,
            fld.tuesday,
            fld.wednesday,
            fld.thursday,
            fld.friday,
            fld.saturday,
            fld.sunday,
            s.units");

  $stmt->bind_param("iss", $professor_id, $current_ay, $current_semester);

  if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $subjects[] = $row;
    }
  } else {
    error_log("Error executing query: " . $stmt->error);
  }
  $stmt->close();
} else {
  error_log("Professor ID not set when trying to fetch subjects");
}

if (!$is_viewing_specific_ar) {
  $current_month = date('F');
  $current_year = date('Y');

  $stmt = $conn->prepare("SELECT ar_id FROM AR_Header 
                           WHERE professor_id = ? 
                           AND month = ? 
                           AND year = ?");
  $stmt->bind_param("iss", $professor_id, $current_month, $current_year);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $ar_exists = true;
  } else {
    $ar_exists = false;
  }
  $stmt->close();
} else {
  $ar_exists = false;
}

$dtrData = [];
if ($professor_id && !empty($subjects)) {
  $current_month_name = date('F');
  $current_month_num = date('n');
  $current_year = date('Y');

  $stmt = $conn->prepare("
        SELECT h.dtr_id, h.date_from, h.date_to, h.month, h.year,
               d.date, d.time_in_am, d.time_out_am, d.time_in_pm, d.time_out_pm,
               d.hours_rendered, d.minutes_rendered
        FROM DTR_Header h
        JOIN DTR_Details d ON h.dtr_id = d.dtr_id
        WHERE h.professor_id = ?
        AND h.year = ?
        AND (h.month = ? OR h.month = ?)
    ");
  $stmt->bind_param("isis", $professor_id, $current_year, $current_month_num, $current_month_name);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $dtrData[] = $row;
  }
  $stmt->close();
}

function calculateHoursRenderedFromData($subject, $dtrData, $month_display)
{
  $totalHours = 0;

  $day = getDayFromSchedule($subject);
  if (empty($day)) {
    return 0;
  }

  preg_match('/(\w+) (\d+)-(\d+), (\d+)/', $month_display, $matches);
  if (count($matches) !== 5) {
    return 0;
  }

  $monthName = $matches[1];
  $startDay = (int)$matches[2];
  $endDay = (int)$matches[3];
  $year = (int)$matches[4];

  $dayLower = strtolower($day);
  $scheduleTime = $subject[$dayLower] ?? '';
  if (empty($scheduleTime)) {
    return 0;
  }

  $scheduleParts = explode('-', $scheduleTime);
  if (count($scheduleParts) !== 2) {
    return 0;
  }

  $startTimeStr = trim($scheduleParts[0]);
  $endTimeStr = trim($scheduleParts[1]);

  try {
    $startTime = DateTime::createFromFormat('h:iA', $startTimeStr);
    $endTime = DateTime::createFromFormat('h:iA', $endTimeStr);

    if (!$startTime || !$endTime) {
      return 0;
    }

    if ($endTime < $startTime) {
      $endTime->add(new DateInterval('P1D'));
    }

    $scheduledDuration = $endTime->getTimestamp() - $startTime->getTimestamp();
    $maxAllowedHours = $scheduledDuration / 3600;
  } catch (Exception $e) {
    return 0;
  }

  foreach ($dtrData as $dtr) {
    try {
      $dtrDate = new DateTime($dtr['date']);
      $dtrDay = $dtrDate->format('l');
      $dtrDayNum = (int)$dtrDate->format('j');

      if ($dtrDay === $day && $dtrDayNum >= $startDay && $dtrDayNum <= $endDay) {
        $dayHours = 0;

        if (!empty($dtr['time_in_am']) && !empty($dtr['time_out_am'])) {
          $loginAM = DateTime::createFromFormat('h:iA', $dtr['time_in_am']);
          $logoutAM = DateTime::createFromFormat('h:iA', $dtr['time_out_am']);

          if ($loginAM && $logoutAM) {
            $overlapStart = max($loginAM, $startTime);
            $overlapEnd = min($logoutAM, $endTime);

            if ($overlapStart < $overlapEnd) {
              $overlapSeconds = $overlapEnd->getTimestamp() - $overlapStart->getTimestamp();
              $dayHours += $overlapSeconds / 3600;
            }
          }
        }

        if (!empty($dtr['time_in_pm']) && !empty($dtr['time_out_pm'])) {
          $loginPM = DateTime::createFromFormat('h:iA', $dtr['time_in_pm']);
          $logoutPM = DateTime::createFromFormat('h:iA', $dtr['time_out_pm']);

          if ($loginPM && $logoutPM) {
            $overlapStart = max($loginPM, $startTime);
            $overlapEnd = min($logoutPM, $endTime);

            if ($overlapStart < $overlapEnd) {
              $overlapSeconds = $overlapEnd->getTimestamp() - $overlapStart->getTimestamp();
              $dayHours += $overlapSeconds / 3600;
            }
          }
        }

        $dayHours = min($dayHours, $maxAllowedHours);
        $totalHours += $dayHours;
      }
    } catch (Exception $e) {
      continue;
    }
  }

  return round($totalHours, 2);
}

function getDayFromSchedule($subject)
{
  $days = [
    'monday' => 'Monday',
    'tuesday' => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday',
    'saturday' => 'Saturday',
    'sunday' => 'Sunday'
  ];

  foreach ($days as $field => $name) {
    if (!empty($subject[$field])) {
      return $name;
    }
  }
  return '';
}

$total_units = 0;
foreach ($subjects as $subject) {
  $total_units += floatval($subject['units']);
}

if (!$is_viewing_specific_ar) {
  $current_month = date('F');
  $current_year = date('Y');

  $stmt = $conn->prepare("SELECT ar_id FROM AR_Header 
                           WHERE professor_id = ? 
                           AND month = ? 
                           AND year = ?");
  $stmt->bind_param("iss", $professor_id, $current_month, $current_year);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $ar_exists = true;
  } else {
    $ar_exists = false;
  }
  $stmt->close();
} else {
  $ar_exists = false;
}

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
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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
      /* background-color: #e0e0e0; */
      /* font-weight: bold; */
      /* color: #333; */
    }

    .signature-section {
      page-break-inside: avoid;
      margin-top: 30px;
    }

    .signature-section>div:first-child {
      margin-bottom: 15px;
    }

    .signature-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 60px;
    }

    .signature-box {
      width: 45%;
      text-align: center;
      font-size: 12px;
    }

    .signature-line {
      border-bottom: 1px solid #000;
      height: 30px;
      margin-bottom: 5px;
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
      max-width: 120px;
      max-height: 40px;
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

    .ar-table thead th {
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

    .sidebar-create-btn {
      width: 100%;
      padding: 10px 15px;
      margin-bottom: 35px;
      background-color: #3b5525;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-family: "Poppins", sans-serif;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.2s ease;
    }

    .sidebar-create-btn:hover {
      background-color: #2c5e1a;
    }

    .toggle-btn {
      position: fixed;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 45px;
      height: 45px;
      background-color: #3b5525;
      display: flex;
      align-items: center;
      justify-content: center;
      border-top-right-radius: 8px;
      border-bottom-right-radius: 8px;
      cursor: pointer;
      z-index: 1001;
      transition: left 0.3s ease, background-color 0.2s ease;
    }


    body.sidebar-open .toggle-btn {
      left: 250px;
    }

    .toggle-btn:hover {
      background-color: #d0c74f;
    }

    .toggle-icon {
      font-size: 22px;
      color: white;
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

    select.year-dropdown,
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

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      transition: margin-left 0.3s ease;
      flex: 1;
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


    .esign-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: 1050;
      justify-content: center;
      align-items: center;
      padding: 15px;
    }

    .esign-content {
      background-color: white;
      border-radius: 10px;
      padding: 25px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      position: relative;
    }

    .esign-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .esign-title {
      font-family: "Montserrat", sans-serif;
      font-weight: 600;
      color: #1a2a0d;
      font-size: 1.1rem;
      margin: 0;
    }

    .close-modal {
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

    .close-modal:hover {
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

    .esign-prompt {
      font-size: 0.85rem;
      color: #666;
      text-align: center;
      margin-bottom: 15px;
    }

    .esign-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
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

    #computeButton {
      background-color: #4a6baf;
      margin-right: 10px;
    }

    #computeButton:hover {
      background-color: #3a5a9f;
    }

    .action-button:disabled {
      cursor: not-allowed;
      opacity: 0.7;
    }

    @media (max-width: 768px) {
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

      body.sidebar-open .toggle-btn {
        left: 250px;
      }

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

      .esign-content {
        padding: 20px;
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

      .sidebar,
      .toggle-btn,
      header,
      #action-controls,
      #uploadStatus,
      #esign-file,
      .back-button,
      .back-btn,
      .pdf-export-hide,
      .esign-modal {
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
        /* Remove rounding for print */
        text-align: inherit !important;
        /* Reset text align */
        width: 100% !important;
        /* Ensure full width */
        margin: 0 !important;
        /* Reset margin */
      }

      .ar-table td input[type="number"].editable-hours-input {
        text-align: center !important;
        /* Re-apply center align specifically */
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

    .input-dayhrs {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    #cts {
      min-width: 80px;
    }
  </style>
</head>

<body>
  <div class="sidebar" id="sidebar">
    <div class="toggle-btn" onclick="toggleSidebar()">
      <span class="toggle-icon" id="toggle-icon">❯</span>
    </div>
    <div class="sidebar-content">
      <div class="sidebar-header">Submit new AR</div>
      <button class="sidebar-create-btn">
        <i class="fas fa-plus-circle" style="margin-right: 8px"></i> Create New
      </button>
      <div class="sidebar-header">View Previous</div>
      <div class="section-header">Regular AR</div>
      <div class="year-selector">
        <label class="year-label">Select Year:</label>
        <select class="year-dropdown" id="year-dropdown-nav">
          <option value="" hidden>Select Year</option>
          <!-- Will be populated by JavaScript -->
        </select>
        <label class="month-label">Select Month:</label>
        <select class="month-dropdown" id="month-dropdown-nav">
          <option value="" hidden>Select Month</option>
          <!-- Will be populated by JavaScript -->
        </select>
      </div>
    </div>
  </div>

  <div class="content-area">
    <header>
      <div class="header-content">
        <div class="logo-container">
          <button
            class="back-button pdf-export-hide"
            onclick="window.location.href='pt-dash.php'">
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
          <select id="semester-select" class="editable-span" style="width: 120px;"
            aria-label="Select semester" <?php echo $is_viewing_specific_ar ? 'disabled' : ''; ?>>
            <option value="">Select Semester</option>
            <?php
            if (!empty($available_periods)) {
              $semesters = $available_periods[$current_ay] ?? [];
              foreach ($semesters as $semester) {
                $selected = ($semester === $current_semester) ? 'selected' : '';
                echo "<option value=\"$semester\" $selected>$semester</option>";
              }
            }
            ?>
          </select>
          A.Y.
          <select id="ay-select" class="editable-span" style="width: 100px;"
            aria-label="Select academic year">
            <option value="" hidden>Academic Year</option>
            <?php
            if (!empty($available_periods)) {
              foreach ($available_periods as $year => $semesters) {
                $selected = ($year === $current_ay) ? 'selected' : '';
                echo "<option value=\"$year\" $selected>$year</option>";
              }
            }
            ?>
          </select>
        </h3>
        <p>
          For the Month of
          <span contenteditable="true" class="editable-span"><?php echo $month_display; ?></span>
        </p>
      </div>

      <div class="info-section">
        <div class="info-row">
          <div class="info-label">Name of Faculty</div>
          <div class="info-input">
            <input
              type="text"
              id="faculty-name-input"
              value="<?php echo $professor_name; ?>"
              readonly />
          </div>
        </div>
        <div class="info-row">
          <div class="info-label">College/ Dept.</div>
          <div class="info-input">
            <input type="text" value="<?php echo $department; ?>" readonly />
          </div>
        </div>
        <div class="info-row">
          <div class="info-label">No. of Units</div>
          <div class="info-input">
            <input type="number" value="<?php echo $total_units; ?>" style="width: 50px" readonly />
          </div>
        </div>
      </div>

      <?php if (!$is_viewing_specific_ar && $ar_exists && !isset($_GET['success'])): ?>
        <div style="color: #dc3545; margin-bottom: 15px; font-weight: bold; text-align: center;">
          You already submitted your AR this month.
        </div>
      <?php endif; ?>

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
          <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $subject): ?>
              <?php
              $hoursRendered = calculateHoursRenderedFromData($subject, $dtrData, $month_display);
              $scheduleDisplay = $subject['schedule'] ?? '';
              $scheduleDisplay = preg_replace('/(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*/', '', $scheduleDisplay);
              $day = getDayFromSchedule($subject);
              ?>
              <tr>
                <td>
                  <input type="text" value="<?= htmlspecialchars($subject['subject_description']) ?>" class="ar-input" readonly />
                </td>
                <td>
                  <input type="number" value="<?= $subject['units'] ?>" style="width: 40px" class="ar-input unit-input" readonly />
                </td>
                <td>
                  <input type="text" value="<?= htmlspecialchars($month_display) ?>" class="ar-input inclusive-dates-input" readonly />
                </td>
                <td>
                  <input type="text" value="<?= htmlspecialchars($scheduleDisplay) ?>" class="ar-input" readonly />
                </td>
                <td>
                  <input type="text" value="<?= $day ?>" class="ar-input day-input" style="width: 60%; display: inline-block" readonly />
                  <input type="number" value="<?= $hoursRendered ?>" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" />
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
                <input type="number" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" />
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
              <input type="text" class="ar-input" id="total-hours" value="0.00" readonly />
            </td>
          </tr>
        </tfoot>
      </table>

      <div class="signature-section">
        <div>Certified by:</div>

        <div class="signature-row">
          <div class="signature-box">
            <div class="signature-line" id="faculty-signature-line"></div>
            <input
              type="text"
              id="faculty-name-display"
              value="<?php echo $professor_name; ?>"
              class="signature-name"
              readonly />
            <div>Faculty printed name Over Signature</div>
          </div>

          <div class="signature-box">
            <div class="signature-line" id="head-signature-line"></div>
            <input
              type="text"
              id="head-name-display"
              value="<?php echo $department_head; ?>"
              class="signature-name"
              readonly />
            <div>Dean/Dept. Head printed name Over Signature</div>
          </div>
        </div>

        <div class="checked-by-section">
          <div>Checked and Computed by:</div>
          <div class="checked-by-line"></div>
          <input
            type="text"
            value="ALBERT P. CABIAO"
            class="checked-by-name"
            id="checked-by-input"
            readonly
            style="
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

      <div class="button-container" id="action-controls">
        <button
          onclick="document.location='WFH.php?professor_id=<?php echo $professor_id; ?>&semester=<?php echo urlencode($current_semester); ?>&ay=<?php echo urlencode($current_ay); ?>'"
          style="background-color: #ccc; color: #333; font-weight: 700"
          id="uploadWfhButton"
          class="action-button pdf-export-hide">
          <i class="fas fa-paperclip" style="margin-right: 6px;"></i>
          Attach WFH AR
        </button>
        <div class="right-buttons">
          <button id="computeButton" class="action-button pdf-export-hide">
            <i class="fas fa-calculator button-icon"></i> Compute Hours
          </button>
          <button id="saveButton" class="action-button pdf-export-hide">
            <i class="fas fa-paper-plane button-icon"></i>
            Submit
          </button>
          <button id="esignButton" class="action-button pdf-export-hide">
            <i class="fas fa-pencil-alt button-icon"></i>
            E-Sign
          </button>
          <button id="exportExcelButton" class="action-button">
            <i class="fas fa-print button-icon"></i> Export
          </button>
          <button
            id="exportPdfButton"
            class="action-button"
            style="display: none">
            <i class="fas fa-file-pdf button-icon"></i> Export to PDF
            (html2pdf)
          </button>
        </div>
      </div>

      <div id="uploadStatus"></div>
    </div>
  </div>

  <div class="esign-modal" id="esign-modal">
    <div class="esign-content">
      <div class="esign-header">
        <h2 class="esign-title">Add Your E-Signature</h2>
        <button class="close-modal" id="close-modal">×</button>
      </div>
      <div class="signature-pad" id="signature-pad">
        <canvas id="signature-canvas"></canvas>
      </div>
      <p class="esign-prompt">Sign above using your mouse or touch screen</p>
      <div class="esign-actions">
        <button class="btn clear-btn" id="clear-signature">Clear</button>
        <button class="btn" id="save-signature">Save Signature</button>
      </div>
    </div>
  </div>

  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
    integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer">
  </script>

  <script>
    // Initialize available periods from PHP
    const availablePeriods = <?php echo json_encode($available_periods, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Semester display mapping
    const semesterDisplayMap = {
      '1st Semester': 'First Semester',
      '2nd Semester': 'Second Semester',
      'Summer': 'Summer'
    };

    // Reverse mapping for database values
    const semesterValueMap = {
      'First Semester': '1st Semester',
      'Second Semester': '2nd Semester',
      'Summer': 'Summer',
      '1st': '1st Semester',
      '2nd': '2nd Semester'
    };

    document.getElementById("semester-select").addEventListener("change", function(e) {
      const semester = this.value;
      const ay = document.getElementById("ay-select").value;

      if (!semester || !ay) return;

      updateURLParams(semester, ay);

      // Convert to short format for session storage
      const sessionSemester = semester === '1st Semester' ? '1st' :
        semester === '2nd Semester' ? '2nd' :
        semester;

      fetch('../Backend/store_session.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `semester=${encodeURIComponent(sessionSemester)}&ay=${encodeURIComponent(ay)}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            reloadFormData();
          } else {
            console.error('Error storing session:', data.message);
          }
        });
    });

    document.getElementById("ay-select").addEventListener("change", function(e) {
      const ay = this.value;
      const semester = document.getElementById("semester-select").value;

      if (!ay) return;

      updateURLParams(semester, ay);

      const sessionSemester = semester === '1st Semester' ? '1st' :
        semester === '2nd Semester' ? '2nd' :
        semester === 'Summer' ? 'Summer' :
        semester;

      fetch('../Backend/store_session.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `semester=${encodeURIComponent(sessionSemester)}&ay=${encodeURIComponent(ay)}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            populateSemesters(ay);
            reloadFormData();
          } else {
            console.error('Error storing session:', data.message);
          }
        });
    });

    // Helper function to update URL parameters without reload
    function updateURLParams(semester, ay) {
      const url = new URL(window.location.href);

      const urlSemester = semester === '1st Semester' ? '1st' :
        semester === '2nd Semester' ? '2nd' :
        semester === 'Summer' ? 'Summer' :
        semester;

      url.searchParams.set('semester', urlSemester);
      url.searchParams.set('ay', ay);

      window.history.pushState({}, '', url);
    }

    window.addEventListener('popstate', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const semester = urlParams.get('semester');
      const ay = urlParams.get('ay');

      if (semester && ay) {
        // Convert short semester code to full name
        const fullSemester = semester === '1st' ? '1st Semester' :
          semester === '2nd' ? '2nd Semester' :
          semester === 'Summer' ? 'Summer' :
          semester;

        // Update dropdowns
        document.getElementById('semester-select').value = fullSemester;
        document.getElementById('ay-select').value = ay;

        // Reload data
        reloadFormData();
      }
    });

    let arPeriods = {};

    // Function to load AR periods
    function loadARPeriods() {
      fetch('../Backend/AR/get_ar_periods.php')
        .then(response => response.json())
        .then(data => {
          if (data.ar_periods) {
            arPeriods = data.ar_periods;
            populateYearDropdown();
          }
        })
        .catch(error => {
          console.error('Error loading AR periods:', error);
        });
    }

    // Function to populate year dropdown
    function populateYearDropdown() {
      const yearDropdown = document.getElementById('year-dropdown-nav');
      yearDropdown.innerHTML = '';

      // Add default option
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Select Year';
      defaultOption.hidden = true;
      yearDropdown.appendChild(defaultOption);

      // Add available years
      Object.keys(arPeriods).sort().reverse().forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearDropdown.appendChild(option);
      });
    }

    // Function to populate month dropdown based on selected year
    function populateMonthDropdown(selectedYear) {
      const monthDropdown = document.getElementById('month-dropdown-nav');
      monthDropdown.innerHTML = '';

      // Add default option
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Select Month';
      defaultOption.hidden = true;
      monthDropdown.appendChild(defaultOption);

      if (selectedYear && arPeriods[selectedYear]) {
        const monthMap = {
          'January': '01',
          'February': '02',
          'March': '03',
          'April': '04',
          'May': '05',
          'June': '06',
          'July': '07',
          'August': '08',
          'September': '09',
          'October': '10',
          'November': '11',
          'December': '12'
        };

        arPeriods[selectedYear].forEach(month => {
          const option = document.createElement('option');
          option.value = monthMap[month] || month;
          option.textContent = month;
          monthDropdown.appendChild(option);
        });
      }
    }

    // Function to load AR data
    function loadARData(year, month) {
      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];
      const monthName = monthNames[parseInt(month) - 1] || month;

      fetch(`../Backend/AR/get_ar_data.php?year=${year}&month=${monthName}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Disable editing controls
            document.getElementById('computeButton').style.display = 'none';
            document.getElementById('saveButton').style.display = 'none';
            document.getElementById('esignButton').style.display = 'none';

            // Update form with AR data
            updateFormWithARData(data);
          } else {
            alert('Error loading AR: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error loading AR:', error);
          alert('An error occurred while loading AR data.');
        });
    }

    function viewAR(year, month) {
      // Disable editing controls
      document.getElementById('computeButton').style.display = 'none';
      document.getElementById('saveButton').style.display = 'none';
      document.getElementById('esignButton').style.display = 'none';

      // Show loading state
      const formContainer = document.getElementById("report-form");
      formContainer.style.opacity = "0.5";

      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];
      const monthName = monthNames[parseInt(month) - 1] || month;

      fetch(`../Backend/AR/get_ar_data.php?year=${year}&month=${monthName}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          formContainer.style.opacity = "1";
          if (data.success) {
            // First disable the dropdowns
            document.getElementById('semester-select').disabled = true;
            document.getElementById('ay-select').disabled = true;

            // Then update the form with the data
            updateFormWithARData(data);

            // Remove the "already submitted" message if it exists
            const existingMessage = document.querySelector('.title-section + div[style*="color: #dc3545"]');
            if (existingMessage) {
              existingMessage.remove();
            }
          } else {
            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.style.color = '#dc3545';
            errorDiv.style.marginBottom = '15px';
            errorDiv.style.fontWeight = 'bold';
            errorDiv.style.textAlign = 'center';
            errorDiv.textContent = 'No AR found for the selected period.';

            // Insert it after the title section
            const titleSection = document.querySelector('.title-section');
            titleSection.insertAdjacentElement('afterend', errorDiv);

            // Clear the table
            document.querySelector('.ar-table tbody').innerHTML = '';
          }
        })
        .catch(error => {
          formContainer.style.opacity = "1";
          console.error('Error loading AR:', error);
          alert('An error occurred while loading AR data. Please try again.');
        });
    }

    // Function to update form with AR data
    function updateFormWithARData(data) {
      // Validate input data
      if (!data || typeof data !== 'object') {
        console.error('Invalid data received in updateFormWithARData');
        return;
      }

      // Update header information if available
      if (data.header && typeof data.header === 'object') {
        // Update basic information
        const setValue = (id, value, defaultValue = '') => {
          const element = document.getElementById(id);
          if (element) {
            element.value = value !== undefined ? value : defaultValue;
          }
        };

        const setTextContent = (selector, value, defaultValue = '') => {
          const element = document.querySelector(selector);
          if (element) {
            element.textContent = value !== undefined ? value : defaultValue;
          }
        };

        // Set total hours from header data
        setValue('total-hours', data.header.total_hours_rendered ? parseFloat(data.header.total_hours_rendered).toFixed(2) : '0.00');

        // Update basic info
        setValue('faculty-name-input', data.header.full_name);
        setValue('faculty-name-display', data.header.full_name);
        setValue('checked-by-input', data.header.checked_by, 'ALBERT P. CABIAO');

        // Update department and units
        const departmentInput = document.querySelector('.info-row:nth-child(2) .info-input input');
        if (departmentInput) {
          departmentInput.value = data.header.department || '';
        }

        const unitsInput = document.querySelector('.info-row:nth-child(3) .info-input input');
        if (unitsInput) {
          unitsInput.value = data.header.no_of_units || 0;
        }

        // Update academic period - THIS IS THE CRITICAL PART
        if (data.header.academic_year) {
          const aySelect = document.getElementById("ay-select");
          if (aySelect) {
            aySelect.value = data.header.academic_year;
            aySelect.disabled = true;
          }
        }

        if (data.header.semester) {
          const semesterSelect = document.getElementById("semester-select");
          if (semesterSelect) {
            // First enable the select to set the value
            semesterSelect.disabled = false;

            // Convert to display format if needed
            const displaySemester = semesterDisplayMap[data.header.semester] || data.header.semester;

            // Set the value
            semesterSelect.value = data.header.semester;

            // Now disable it (since we're viewing a specific AR)
            semesterSelect.disabled = true;
          }
        }

        // Update month display
        if (data.header.period) {
          const monthDisplay = document.querySelector('.editable-span');
          if (monthDisplay) {
            monthDisplay.textContent = data.header.period;
          }
        }

        // Update faculty signature
        const facultySignatureLine = document.getElementById('faculty-signature-line');
        if (facultySignatureLine) {
          facultySignatureLine.innerHTML = '';

          if (data.header.faculty_signature) {
            try {
              const img = document.createElement('img');
              img.src = data.header.faculty_signature;
              img.style.maxWidth = '120px';
              img.style.maxHeight = '40px';
              img.alt = 'Faculty Signature';
              img.onerror = () => {
                facultySignatureLine.innerHTML = 'Signature not available';
              };
              facultySignatureLine.appendChild(img);
            } catch (e) {
              console.error('Error loading signature image:', e);
              facultySignatureLine.innerHTML = 'Signature not available';
            }
          }
        }

        const headSignatureLine = document.getElementById('head-signature-line');
        if (headSignatureLine) {
          headSignatureLine.innerHTML = '';

          if (data.header.head_signature) {
            try {
              const img = document.createElement('img');
              img.src = data.header.head_signature;
              img.style.maxWidth = '120px';
              img.style.maxHeight = '40px';
              img.alt = 'Head Signature';
              img.onerror = () => {
                headSignatureLine.innerHTML = 'Signature not available';
              };
              headSignatureLine.appendChild(img);
            } catch (e) {
              console.error('Error loading head signature image:', e);
              headSignatureLine.innerHTML = 'Signature not available';
            }
          }
        }
      }

      // Update AR details table
      const tbody = document.querySelector('.ar-table tbody');
      if (!tbody) return;

      // Clear existing rows
      tbody.innerHTML = '';

      // Add new rows if details exist
      if (Array.isArray(data.details)) {
        data.details.forEach((detail, index) => {
          if (!detail || typeof detail !== 'object') return;

          const row = document.createElement('tr');

          // Create cells with proper escaping and null checks
          const createCell = (value, isNumber = false, extraAttributes = '') => {
            const escapedValue = escapeHtml(value !== undefined ? value.toString() : '');
            return `<input type="${isNumber ? 'number' : 'text'}" 
                         value="${escapedValue}" 
                         class="ar-input" 
                         readonly 
                         ${extraAttributes}>`;
          };

          row.innerHTML = `
                <td>${createCell(detail.subject_description)}</td>
                <td>${createCell(detail.no_of_units, true, 'style="width: 40px"')}</td>
                <td>${createCell(detail.inclusive_dates)}</td>
                <td>${createCell(detail.class_time_schedule)}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        ${createCell(detail.day_of_week, false, 'style="width: 60%" class="day-input"')}
                        ${createCell(detail.hours_rendered, true, 'style="width: 30%" class="hours-rendered-input"')}
                    </div>
                </td>
            `;

          tbody.appendChild(row);
        });

        // If no details, add an empty row
        if (data.details.length === 0) {
          const emptyRow = document.createElement('tr');
          emptyRow.innerHTML = `
                <td><input type="text" class="ar-input" readonly></td>
                <td><input type="number" style="width: 40px" class="ar-input unit-input" readonly></td>
                <td><input type="text" class="ar-input inclusive-dates-input" readonly></td>
                <td><input type="text" class="ar-input" readonly></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="text" class="ar-input day-input" style="width: 60%" readonly>
                        <input type="number" class="ar-input hours-rendered-input" style="width: 30%" readonly>
                    </div>
                </td>
            `;
          tbody.appendChild(emptyRow);
        }
      } else {
        // Add empty row if no details provided
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td><input type="text" class="ar-input" readonly></td>
            <td><input type="number" style="width: 40px" class="ar-input unit-input" readonly></td>
            <td><input type="text" class="ar-input inclusive-dates-input" readonly></td>
            <td><input type="text" class="ar-input" readonly></td>
            <td>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <input type="text" class="ar-input day-input" style="width: 60%" readonly>
                    <input type="number" class="ar-input hours-rendered-input" style="width: 30%" readonly>
                </div>
            </td>
        `;
        tbody.appendChild(emptyRow);
      }
    }

    // Add event listeners for dropdowns
    document.getElementById('year-dropdown-nav').addEventListener('change', function() {
      const selectedYear = this.value;
      populateMonthDropdown(selectedYear);

      // Clear the month selection when year changes
      document.getElementById('month-dropdown-nav').value = '';
    })

    document.getElementById('month-dropdown-nav').addEventListener('change', function() {
      const selectedYear = document.getElementById('year-dropdown-nav').value;
      const selectedMonth = this.value;

      if (selectedYear && selectedMonth) {
        window.location.href = `ar-pt.php?professor_id=<?php echo $professor_id; ?>&year=${selectedYear}&month=${selectedMonth}`;
      }
    });

    document.querySelector('.sidebar-create-btn').addEventListener('click', function() {
      fetch('../Backend/AR/check_ar_exists.php?professor_id=<?php echo $professor_id; ?>')
        .then(response => response.json())
        .then(data => {
          if (data.exists) {
            alert("You already submitted your AR this month!");
          } else {
            window.location.href = 'ar-pt.php';
          }
        })
        .catch(error => {
          console.error('Error checking AR:', error);
          alert("Error checking for existing AR submissions.");
        });
    });


    function computeHoursRendered() {
      // Get the current month display
      const monthDisplay = document.querySelector(".editable-span").textContent;
      const currentYear = new Date().getFullYear();
      const currentMonthNum = new Date().getMonth() + 1;

      // Fetch DTR data
      fetch(`../Backend/get_dtr_data.php?professor_id=<?php echo $professor_id; ?>&year=${currentYear}&month=${currentMonthNum}`)
        .then(response => response.json())
        .then(dtrData => {
          if (!dtrData || dtrData.length === 0) {
            alert("No DTR data found for the current month!");
            return;
          }

          // Process each subject row
          document.querySelectorAll(".ar-table tbody tr").forEach(row => {
            const day = row.querySelector(".day-input").value;
            const scheduleTime = row.querySelector("td:nth-child(4) input").value;
            const subjectDescription = row.querySelector("td:nth-child(1) input").value;

            // Create a subject object that matches the expected format
            const subject = {
              subject_description: subjectDescription,
              schedule: `${day} ${scheduleTime}`,
              [day.toLowerCase()]: scheduleTime
            };

            const hoursRendered = calculateHoursRenderedFromData(subject, dtrData, monthDisplay);
            const hoursInput = row.querySelector(".hours-rendered-input");
            if (hoursInput) {
              hoursInput.value = hoursRendered.toFixed(2);
            }
          });

          calculateTotalHours();
          alert("Hours computed successfully!");
        })
        .catch(error => {
          console.error('Error:', error);
          alert("Error computing hours. Please check console for details.");
        });
    }

    // Add event listener
    document.getElementById("computeButton").addEventListener("click", computeHoursRendered);

    function calculateTotalHours() {
      const hoursInputs = document.querySelectorAll(".hours-rendered-input");
      const totalHoursInput = document.getElementById("total-hours");
      let total = 0;

      hoursInputs.forEach((input) => {
        if (input.closest("tbody") && input.classList.contains("editable-hours-input")) {
          const value = parseFloat(input.value) || 0;
          total += value;
        }
      });

      if (totalHoursInput) {
        totalHoursInput.value = total > 0 ? total.toFixed(2) : "0.00";
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const toggleIcon = document.getElementById("toggle-icon");
      const body = document.body;
      sidebar.classList.toggle("active");
      body.classList.toggle("sidebar-open");
      toggleIcon.textContent = sidebar.classList.contains("active") ?
        "❮" :
        "❯";
      const contentArea = document.querySelector(".content-area");
      if (window.innerWidth <= 768) {
        contentArea.style.transform = body.classList.contains("sidebar-open") ?
          "translateX(250px)" :
          "translateX(0)";
      } else {
        contentArea.style.transform = "none";
      }
    }

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
      try {
        const dateElement = document.getElementById("date");
        if (dateElement) {
          dateElement.textContent = now.toLocaleDateString("en-US", options);
        }
      } catch (e) {
        console.error("Error updating date:", e);
      }
    }

    setInterval(updateDateTime, 1000);
    updateDateTime();

    // Function to reload form data based on selected semester and AY
    function reloadFormData() {
      const semester = document.getElementById("semester-select").value;
      const ay = document.getElementById("ay-select").value;
      const urlParams = new URLSearchParams(window.location.search);
      const professorId = urlParams.get('professor_id') || <?php echo $_SESSION['professor_id']; ?>;

      if (!semester || !ay) return;

      updateURLParams(semester, ay);

      const formContainer = document.getElementById("report-form");
      formContainer.style.opacity = "0.5";

      // Get current month and year
      const currentMonth = new Date().getMonth() + 1;
      const currentYear = new Date().getFullYear();

      fetch(`../Backend/get_form_load_data.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `professor_id=${encodeURIComponent(professorId)}&semester=${encodeURIComponent(semester)}&school_year=${encodeURIComponent(ay)}&month=${currentMonth}&year=${currentYear}`
        })
        .then(response => response.json())
        .then(data => {
          console.log('Form load data response:', data);
          formContainer.style.opacity = "1";
          if (data.success) {
            updateFormWithData(data);
          } else {
            console.error('Error loading form data:', data.message);
            alert('Error loading form data: ' + data.message);
          }
        })
        .catch(error => {
          formContainer.style.opacity = "1";
          console.error('Error:', error);
          alert('An error occurred while loading data.');
        });
    }

    function getDayFromSchedule(subject) {
      const days = [{
          field: 'monday',
          name: 'Monday'
        },
        {
          field: 'tuesday',
          name: 'Tuesday'
        },
        {
          field: 'wednesday',
          name: 'Wednesday'
        },
        {
          field: 'thursday',
          name: 'Thursday'
        },
        {
          field: 'friday',
          name: 'Friday'
        },
        {
          field: 'saturday',
          name: 'Saturday'
        },
        {
          field: 'sunday',
          name: 'Sunday'
        }
      ];

      for (const day of days) {
        if (subject[day.field]) {
          return day.name;
        }
      }
      return '';
    }

    function updateFormWithData(data) {
      // Calculate total units from the subjects data
      let totalUnits = 0;
      if (data.subjects && data.subjects.length > 0) {
        data.subjects.forEach(subject => {
          totalUnits += parseFloat(subject.units) || 0;
        });
      }

      // Update the No. of Units field with the calculated total
      document.querySelector(".info-row:nth-child(3) .info-input input").value = totalUnits;

      if (data.professor) {
        document.getElementById("faculty-name-input").value = data.professor.full_name;
        document.getElementById("faculty-name-display").value = data.professor.full_name;
      }

      if (data.department_head) {
        document.getElementById("head-name-display").value = data.department_head;
      }

      const monthDisplay = document.querySelector(".editable-span");
      if (monthDisplay && data.month_display) {
        monthDisplay.textContent = data.month_display;

        const match = data.month_display.match(/(\w+) (\d+)-(\d+), (\d+)/);
        if (match) {
          const monthName = match[1];
          const year = match[4];

          const tbody = document.querySelector(".ar-table tbody");
          tbody.innerHTML = '';

          if (data.subjects && data.subjects.length > 0) {
            data.subjects.forEach(subject => {
              const row = document.createElement("tr");

              let scheduleDisplay = subject.schedule || '';
              scheduleDisplay = scheduleDisplay.replace(/(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*/g, '');

              // Get the day from the schedule
              const day = getDayFromSchedule(subject);

              row.innerHTML = `
                        <td>
                            <input type="text" value="${escapeHtml(subject.subject_description)}" class="ar-input" readonly />
                        </td>
                        <td>
                            <input type="number" value="${subject.units}" style="width: 40px" class="ar-input unit-input" readonly />
                        </td>
                        <td>
                            <input type="text" value="${escapeHtml(data.month_display)}" class="ar-input inclusive-dates-input" readonly />
                        </td>
                        <td>
                            <input type="text" value="${escapeHtml(scheduleDisplay)}" class="ar-input" readonly />
                        </td>
                        <td>
                            <input type="text" value="${day}" class="ar-input day-input" style="width: 60%; display: inline-block" readonly />
                            <input type="number" value="0" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" />
                        </td>
                    `;

              // If there's a day specified, fetch the dates for that day
              if (day) {
                fetch(`../Backend/get_dates.php?month=${monthName}&year=${year}&day=${day.toLowerCase()}`)
                  .then(response => response.json())
                  .then(dates => {
                    if (dates.length > 0) {
                      const datesFormatted = `${monthName} ${dates.join(', ')}`;
                      row.querySelector('.inclusive-dates-input').value = datesFormatted;
                    }
                  })
                  .catch(error => console.error('Error fetching dates:', error));
              }

              if (data.dtr_data && data.dtr_data.length > 0) {
                const hoursRendered = calculateHoursRenderedFromData(subject, data.dtr_data, data.month_display);
                row.querySelector('.editable-hours-input').value = hoursRendered;
              }

              tbody.appendChild(row);
            });
          } else {
            // Add one empty row if no subjects
            const row = document.createElement("tr");
            row.innerHTML = `
                    <td><input type="text" class="ar-input" readonly /></td>
                    <td><input type="number" style="width: 40px" class="ar-input unit-input" readonly /></td>
                    <td><input type="text" class="ar-input inclusive-dates-input" readonly /></td>
                    <td><input type="text" class="ar-input" readonly /></td>
                    <td>
                        <input type="text" class="ar-input day-input" style="width: 60%; display: inline-block" readonly />
                        <input type="number" class="ar-input hours-rendered-input editable-hours-input" style="width: 30%; display: inline-block" />
                    </td>
                `;
            tbody.appendChild(row);
          }

          document.querySelectorAll(".editable-hours-input").forEach(input => {
            input.addEventListener("input", calculateTotalHours);
          });
        }
      }
      calculateTotalHours();
    }

    function calculateHoursRenderedFromData(subject, dtrData, monthDisplay) {
      let totalHours = 0;
      const day = getDayFromSchedule(subject);

      if (!day || !dtrData || dtrData.length === 0) {
        return 0;
      }

      const match = monthDisplay.match(/(\w+) (\d+)-(\d+), (\d+)/);
      if (!match) return 0;

      const monthName = match[1];
      const startDay = parseInt(match[2]);
      const endDay = parseInt(match[3]);
      const year = parseInt(match[4]);

      const dayLower = day.toLowerCase();
      const scheduleTime = subject[dayLower] || '';

      if (!scheduleTime) {
        return 0;
      }

      const scheduleParts = scheduleTime.split('-');
      if (scheduleParts.length !== 2) {
        return 0;
      }

      const startTimeStr = scheduleParts[0].trim();
      const endTimeStr = scheduleParts[1].trim();

      let scheduledDuration = 0;
      try {
        const startTime = new Date(`2000-01-01T${formatTimeString(startTimeStr)}`);
        const endTime = new Date(`2000-01-01T${formatTimeString(endTimeStr)}`);

        // Handle overnight schedules (e.g., 11:00PM-1:00AM)
        if (endTime < startTime) {
          endTime.setDate(endTime.getDate() + 1);
        }

        scheduledDuration = (endTime - startTime) / 3600000; // Convert to hours
      } catch (e) {
        console.error('Error parsing schedule time:', e);
        return 0;
      }

      dtrData.forEach(dtr => {
        const dtrDate = new Date(dtr.date);
        const dtrDay = dtrDate.toLocaleDateString('en-US', {
          weekday: 'long'
        });
        const dtrDayNum = dtrDate.getDate();

        if (dtrDay === day && dtrDayNum >= startDay && dtrDayNum <= endDay) {
          let dayHours = 0;

          // Process AM session if exists
          if (dtr.time_in_am && dtr.time_out_am) {
            try {
              const loginAM = new Date(`2000-01-01T${formatTimeString(dtr.time_in_am)}`);
              const logoutAM = new Date(`2000-01-01T${formatTimeString(dtr.time_out_am)}`);
              const classStart = new Date(`2000-01-01T${formatTimeString(startTimeStr)}`);
              const classEnd = new Date(`2000-01-01T${formatTimeString(endTimeStr)}`);

              // Adjust for overnight schedules
              if (classEnd < classStart) {
                classEnd.setDate(classEnd.getDate() + 1);
              }
              if (logoutAM < loginAM) {
                logoutAM.setDate(logoutAM.getDate() + 1);
              }

              // Calculate overlap with scheduled time
              const overlapStart = new Date(Math.max(loginAM.getTime(), classStart.getTime()));
              const overlapEnd = new Date(Math.min(logoutAM.getTime(), classEnd.getTime()));

              if (overlapStart < overlapEnd) {
                const overlapHours = (overlapEnd - overlapStart) / 3600000;
                dayHours += overlapHours;
              }
            } catch (e) {
              console.error('Error processing AM session:', e);
            }
          }

          // Process PM session if exists
          if (dtr.time_in_pm && dtr.time_out_pm) {
            try {
              const loginPM = new Date(`2000-01-01T${formatTimeString(dtr.time_in_pm)}`);
              const logoutPM = new Date(`2000-01-01T${formatTimeString(dtr.time_out_pm)}`);
              const classStart = new Date(`2000-01-01T${formatTimeString(startTimeStr)}`);
              const classEnd = new Date(`2000-01-01T${formatTimeString(endTimeStr)}`);

              if (classEnd < classStart) {
                classEnd.setDate(classEnd.getDate() + 1);
              }
              if (logoutPM < loginPM) {
                logoutPM.setDate(logoutPM.getDate() + 1);
              }
              const overlapStart = new Date(Math.max(loginPM.getTime(), classStart.getTime()));
              const overlapEnd = new Date(Math.min(logoutPM.getTime(), classEnd.getTime()));

              if (overlapStart < overlapEnd) {
                const overlapHours = (overlapEnd - overlapStart) / 3600000;
                dayHours += overlapHours;
              }
            } catch (e) {
              console.error('Error processing PM session:', e);
            }
          }

          dayHours = Math.min(dayHours, scheduledDuration);
          totalHours += dayHours;
        }
      });

      return Math.round(totalHours * 100) / 100;
    }

    function formatTimeString(timeStr) {
      const time = timeStr.trim().toUpperCase();
      const period = time.includes('AM') ? 'AM' : 'PM';
      const timeParts = time.replace(/[AP]M/, '').split(':');

      let hours = parseInt(timeParts[0]);
      const minutes = timeParts[1] ? parseInt(timeParts[1]) : 0;

      if (period === 'PM' && hours < 12) hours += 12;
      if (period === 'AM' && hours === 12) hours = 0;

      return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
      if (!unsafe) return '';
      return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Initialize dropdowns
    const semesterSelect = document.getElementById("semester-select");
    const aySelect = document.getElementById("ay-select");

    function populateSemesters(selectedYear) {
      const semesterSelect = document.getElementById("semester-select");
      if (!semesterSelect) return;

      semesterSelect.innerHTML = '<option value="" hidden>Select Semester</option>';

      if (selectedYear && availablePeriods[selectedYear]) {
        availablePeriods[selectedYear].forEach(semester => {
          const option = document.createElement("option");
          option.value = semester;
          option.textContent = semester;

          // Check if this is the current semester from PHP
          if (semester === '<?php echo $current_semester; ?>' &&
            selectedYear === '<?php echo $current_ay; ?>') {
            option.selected = true;
          }
          semesterSelect.appendChild(option);
        });
      }
    }

    aySelect.addEventListener("change", function() {
      const selectedYear = this.value;
      populateSemesters(selectedYear);

      const semesterSelect = document.getElementById("semester-select");
      if (semesterSelect.options.length === 2) {
        semesterSelect.selectedIndex = 1;
        reloadFormData();
      }
    });

    document.getElementById("saveButton").addEventListener("click", function() {
      const totalHoursInput = document.getElementById("total-hours");
      if (!totalHoursInput.value || parseFloat(totalHoursInput.value) <= 0) {
        alert("Please compute hours before submitting!");
        document.getElementById("computeButton").scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
        return;
      }

      // First check if AR already exists for this month
      fetch('../Backend/AR/check_ar_exists.php?professor_id=<?php echo $professor_id; ?>')
        .then(response => response.json())
        .then(data => {
          if (data.exists) {
            alert("You already submitted your AR this month!");
            return;
          }

          // Proceed with submission if no AR exists
          const facultySignatureLine = document.getElementById("faculty-signature-line");
          const hasSignature = facultySignatureLine.querySelector("img") !== null;

          if (!hasSignature) {
            alert("Please add your e-signature before submitting the form!");

            facultySignatureLine.style.border = "1px solid #9b3b30";
            facultySignatureLine.style.borderRadius = "4px";

            facultySignatureLine.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            });

            setTimeout(() => {
              facultySignatureLine.style.border = "";
            }, 3000);

            return;
          }

          // Get header data
          const professorId = <?php echo $professor_id; ?>;
          const employeeId = "<?php echo $_SESSION['employee_id']; ?>";
          const fullName = document.getElementById("faculty-name-input").value;
          const department = document.querySelector(".info-row:nth-child(2) .info-input input").value;
          const noOfUnits = document.querySelector(".info-row:nth-child(3) .info-input input").value;

          // Get semester and AY from URL parameters if available, otherwise from dropdowns
          const urlParams = new URLSearchParams(window.location.search);
          let semester = urlParams.get('semester') || document.getElementById("semester-select").value;
          let academicYear = urlParams.get('ay') || document.getElementById("ay-select").value;

          // Convert semester to short format for consistency
          if (semester === '1st Semester') semester = '1st';
          else if (semester === '2nd Semester') semester = '2nd';

          const monthDisplay = document.querySelector(".editable-span").textContent;
          const totalHours = document.getElementById("total-hours").value;

          // Parse month and year from display
          const monthMatch = monthDisplay.match(/(\w+) \d+-\d+, (\d+)/);
          const month = monthMatch ? monthMatch[1] : new Date().toLocaleString('default', {
            month: 'long'
          });
          const year = monthMatch ? monthMatch[2] : new Date().getFullYear();

          // Get signature data
          const facultySignature = facultySignatureLine.querySelector("img").src;

          // Collect all subject details
          const details = [];
          document.querySelectorAll(".ar-table tbody tr").forEach(row => {
            const subjectDesc = row.querySelector("td:nth-child(1) input").value;
            const units = row.querySelector("td:nth-child(2) input").value;
            const inclusiveDates = row.querySelector("td:nth-child(3) input").value;
            const classSchedule = row.querySelector("td:nth-child(4) input").value;
            const day = row.querySelector("td:nth-child(5) .day-input").value;
            const hoursRendered = row.querySelector("td:nth-child(5) .editable-hours-input").value;

            if (subjectDesc) {
              details.push({
                subject_description: subjectDesc,
                no_of_units: units,
                inclusive_dates: inclusiveDates,
                class_time_schedule: classSchedule,
                day_of_week: day,
                hours_rendered: hoursRendered
              });
            }
          });

          // Prepare data for submission
          const formData = {
            header: {
              professor_id: professorId,
              employee_id: employeeId,
              full_name: fullName,
              department: department,
              no_of_units: noOfUnits,
              academic_year: academicYear,
              semester: semester,
              month: month,
              year: year,
              period: monthDisplay,
              total_hours_rendered: totalHours,
              faculty_signature: facultySignature,
              head_signature: '',
              checked_by: document.getElementById("checked-by-input").value
            },
            details: details
          };

          // Show loading state
          const saveButton = document.getElementById("saveButton");
          const originalText = saveButton.innerHTML;
          saveButton.innerHTML = '<i class="fas fa-spinner fa-spin button-icon"></i> Saving...';
          saveButton.disabled = true;

          // Send data to server
          fetch('../Backend/AR/save_ar.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert("AR successfully submitted for this month.");
                window.location.href = 'ar-pt.php?success=1';
                return;
              } else {
                alert("Error submitting AR: " + (data.message || "Unknown error"));
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert("An error occurred while submitting the AR.");
            })
            .finally(() => {
              if (!saveButton.disabled) {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
              }
            });
        })
        .catch(error => {
          console.error('Error checking AR:', error);
          alert("Error checking for existing AR submissions.");
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
      // Initial calculation
      //calculateTotalHours();
      loadARPeriods();

      const semesterSelect = document.getElementById("semester-select");
      const aySelect = document.getElementById("ay-select");

      const urlParams = new URLSearchParams(window.location.search);
      const year = urlParams.get('year');
      const month = urlParams.get('month');
      const semester = urlParams.get('semester');
      const ay = urlParams.get('ay');

      if (year && month) {
        viewAR(year, month);
      } else if (semester && ay) {
        const fullSemester = semester === '1st' ? '1st Semester' :
          semester === '2nd' ? '2nd Semester' :
          semester === 'Summer' ? 'Summer' :
          semester;

        document.getElementById('semester-select').value = fullSemester;
        document.getElementById('ay-select').value = ay;

        reloadFormData();
      }

      // Add listener to editable inputs only
      document.querySelectorAll(".editable-hours-input").forEach((input) => {
        input.addEventListener("input", calculateTotalHours);
      });

      if (semesterSelect) {
        semesterSelect.innerHTML = `
          <option value="">Select Semester</option>
          <?php
          if (!empty($available_periods[$current_ay])) {
            foreach ($available_periods[$current_ay] as $semester) {
              echo "<option value=\"$semester\" " . ($semester === $current_semester ? 'selected' : '') . ">$semester</option>";
            }
          }
          ?>
        `;
      }

      // When academic year changes, update semester dropdown
      if (aySelect) {
        aySelect.addEventListener("change", function() {
          const selectedYear = this.value;
          populateSemesters(selectedYear);

          const semesterSelect = document.getElementById("semester-select");
          if (semesterSelect.options.length === 2) {
            semesterSelect.selectedIndex = 1;
            reloadFormData();
          }
        });

        // Initialize with current AY
        if (aySelect.value) {
          populateSemesters(aySelect.value);
        }
      }

      if (semesterSelect && aySelect) {
        // If we have values in the dropdowns, trigger a reload
        if (aySelect.value && semesterSelect.value) {
          reloadFormData();
        }

        // When academic year changes, update semester dropdown
        aySelect.addEventListener("change", function() {
          const selectedYear = this.value;
          populateSemesters(selectedYear);

          const semesterSelect = document.getElementById("semester-select");
          if (semesterSelect.options.length === 2) {
            semesterSelect.selectedIndex = 1;
            reloadFormData();
          }
        });
      }

      const esignModal = document.getElementById("esign-modal");
      const esignButton = document.getElementById("esignButton");
      const closeModalBtn = document.getElementById("close-modal");
      const clearSignatureBtn = document.getElementById("clear-signature");
      const saveSignatureBtn = document.getElementById("save-signature");
      const canvas = document.getElementById("signature-canvas");
      const signaturePad = document.getElementById("signature-pad");
      const facultySignatureLine = document.getElementById("faculty-signature-line");
      const uploadStatus = document.getElementById("uploadStatus");

      let ctx;
      let isDrawing = false;

      if (canvas && signaturePad) {
        ctx = canvas.getContext("2d");

        if (esignButton) {
          esignButton.addEventListener("click", () => {
            if (esignModal) {
              esignModal.style.display = "flex";
              resizeCanvas();
              clearCanvas();
              if (uploadStatus) uploadStatus.textContent = "";
            }
          });
        }

        if (closeModalBtn) {
          closeModalBtn.addEventListener("click", () => {
            if (esignModal) esignModal.style.display = "none";
          });
        }

        window.addEventListener("click", (event) => {
          if (event.target === esignModal) {
            esignModal.style.display = "none";
          }
        });

        function resizeCanvas() {
          const container = signaturePad;
          const ratio = Math.max(window.devicePixelRatio || 1, 1);
          canvas.width = container.offsetWidth * ratio;
          canvas.height = container.offsetHeight * ratio;
          canvas.style.width = container.offsetWidth + "px";
          canvas.style.height = container.offsetHeight + "px";
          ctx.scale(ratio, ratio);
          ctx.lineWidth = 2;
          ctx.strokeStyle = "#333";
          ctx.lineCap = "round";
          ctx.lineJoin = "round";
        }

        function clearCanvas() {
          if (ctx) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
          }
        }

        if (clearSignatureBtn) {
          clearSignatureBtn.addEventListener("click", clearCanvas);
        }

        function getEventPos(e) {
          const rect = canvas.getBoundingClientRect();
          const scaleX = canvas.width / (rect.width * (window.devicePixelRatio || 1));
          const scaleY = canvas.height / (rect.height * (window.devicePixelRatio || 1));
          const clientX = e.clientX || (e.touches && e.touches[0].clientX);
          const clientY = e.clientY || (e.touches && e.touches[0].clientY);
          return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY,
          };
        }

        function startDrawing(e) {
          e.preventDefault();
          isDrawing = true;
          const pos = getEventPos(e);
          ctx.beginPath();
          ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
          if (!isDrawing) return;
          e.preventDefault();
          const pos = getEventPos(e);
          ctx.lineTo(pos.x, pos.y);
          ctx.stroke();
        }

        function stopDrawing() {
          if (isDrawing) {
            isDrawing = false;
          }
        }
        canvas.addEventListener("mousedown", startDrawing);
        canvas.addEventListener("touchstart", startDrawing, {
          passive: false
        });
        canvas.addEventListener("mousemove", draw);
        canvas.addEventListener("touchmove", draw, {
          passive: false
        });
        canvas.addEventListener("mouseup", stopDrawing);
        canvas.addEventListener("touchend", stopDrawing);
        canvas.addEventListener("mouseleave", stopDrawing);

        if (saveSignatureBtn) {
          saveSignatureBtn.addEventListener("click", () => {
            const blank = document.createElement("canvas");
            blank.width = canvas.width;
            blank.height = canvas.height;
            if (canvas.toDataURL() === blank.toDataURL()) {
              alert("Please provide a signature first.");
              return;
            }

            const signatureImage = canvas.toDataURL("image/png");
            const signatureImgElement = document.createElement("img");
            signatureImgElement.src = signatureImage;
            signatureImgElement.style.maxWidth = "120px";
            signatureImgElement.style.maxHeight = "40px";
            signatureImgElement.style.display = "block";
            signatureImgElement.style.margin = "0 auto";
            signatureImgElement.style.objectFit = "contain";
            signatureImgElement.alt = "Faculty Signature";

            if (facultySignatureLine) {
              facultySignatureLine.innerHTML = "";
              facultySignatureLine.appendChild(signatureImgElement);
              if (uploadStatus) {
                uploadStatus.textContent = "Faculty signature added.";
                uploadStatus.className = "";
              }
            } else {
              console.error("Target signature line not found!");
              if (uploadStatus) {
                uploadStatus.textContent = "Error: Could not find where to place the faculty signature.";
                uploadStatus.className = "error";
              }
            }

            if (esignModal) esignModal.style.display = "none";
          });
        }

        window.addEventListener("resize", resizeCanvas);
      } else {
        console.error("E-Signature canvas or pad element not found.");
      }

      const backButton = document.querySelector(".back-button");
      if (backButton && !backButton.hasAttribute("onclick")) {
        backButton.addEventListener("click", () => window.history.back());
      }

      // Initial form load if both values are set
      if (aySelect && aySelect.value && semesterSelect && semesterSelect.value) {
        reloadFormData();
      }

      // AR exist
      fetch('../Backend/AR/check_ar_exists.php?professor_id=<?php echo $professor_id; ?>')
        .then(response => response.json())
        .then(data => {
          if (data.exists) {
            const saveButton = document.getElementById("saveButton");
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-check-circle button-icon"></i> AR Submitted';
            saveButton.style.backgroundColor = '#6c757d';
            saveButton.style.cursor = 'not-allowed';
          }
        });
    });

    document.getElementById("exportExcelButton").addEventListener("click", function() {
      // Get the current professor ID
      const professorId = <?php echo $professor_id; ?>;

      // Get current month and year
      const currentMonth = new Date().getMonth() + 1;
      const currentYear = new Date().getFullYear();

      // Check if we're viewing a specific AR (from URL parameters)
      const urlParams = new URLSearchParams(window.location.search);
      const viewYear = urlParams.get('year');
      const viewMonth = urlParams.get('month');

      // Determine which parameters to use
      const exportYear = viewYear || currentYear;
      const exportMonth = viewMonth || currentMonth;

      // Construct the export URL
      const exportUrl = `../Backend/Exporting/export_ar.php?professor_id=${professorId}&year=${exportYear}&month=${exportMonth}`;

      // Create a hidden iframe to trigger the download
      const iframe = document.createElement('iframe');
      iframe.style.display = 'none';
      iframe.src = exportUrl;
      document.body.appendChild(iframe);

      // Remove the iframe after a short delay
      setTimeout(() => {
        document.body.removeChild(iframe);
      }, 5000);
    });
  </script>
</body>

</html>