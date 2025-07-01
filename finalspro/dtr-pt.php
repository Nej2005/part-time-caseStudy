<?php
session_start();
include '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get professor_id from URL
$professor_id = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : 0;

// Fetch professor data if ID is provided
$professor_data = [];
if ($professor_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, u.employee_id 
                           FROM PartTime_Professor p
                           JOIN Users u ON p.email_address = u.email_address
                           WHERE p.professor_id = ?");
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $professor_data = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pamantasan ng Lungsod ng Pasig - Daily Time Report</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Poppins', sans-serif;
            color: #333;
            overflow-x: hidden;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            transition: margin-left 0.3s ease;
            flex: 1;
        }

        header {
            background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
            color: white;
            padding: 15px 0;
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
            width: 50px;
            height: 50px;
            margin-right: 15px;
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
            position: relative;
            transition: all 0.3s ease;
        }

        .page-title {
            margin-bottom: 25px;
            color: #1a2a0d;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 60px;
            height: 4px;
            background-color: #d0c74f;
            border-radius: 2px;
        }

        /* Updated header-info section to match createnew-dtr.php */
        .header-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* Updated form-group styling to match createnew-dtr.php */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        /* Updated input styling to match createnew-dtr.php */
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

        /* Updated button styling to match createnew-dtr.php */
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #2c5e1a;
            color: white;
        }

        .btn-primary:hover {
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

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: center;
        }

        .attendance-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #555;
        }

        .attendance-table input {
            width: 95%;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
            border-radius: 4px;
        }

        .time-input {
            width: 90% !important;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .btn-container {
            text-align: center;
            margin-top: 20px;
        }

        .upload-container {
            text-align: center;
            margin-top: 20px;
        }

        .upload-btn {
            padding: 10px 15px;
            background-color: #2c5e1a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .upload-btn:hover {
            background-color: #234a15;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        #saveBtn {
            background-color: #D0C74F;
            color: #333333;
        }

        #saveBtn:hover {
            background-color: rgb(180, 167, 78);
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.1);
        }

        #fileUpload {
            display: none;
        }

        .content-area {
            transition: margin-left 0.3s ease;
            flex: 1;
        }

        /* Modal Styles */
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

        .form-row {
            margin-bottom: 15px;
        }

        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-row input,
        .form-row select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Sidebar Styles */
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
            left: 0;
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
            transition: left 0.3s ease;
        }

        .toggle-btn:hover {
            background-color: #d0c74f;
        }

        .toggle-btn.active {
            left: 250px;
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
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
            cursor: pointer;
        }

        select.month-dropdown {
            width: 100%;
            padding: 8px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            background-color: white;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
            cursor: pointer;
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

        /* Delete button styles */
        .btn-delete {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
            font-family: "Poppins", sans-serif;
            font-weight: 500;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }

        .delete-container {
            margin-top: 25px;
            margin-left: -10px;
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

            .header-info {
                grid-template-columns: 1fr;
            }

            body.sidebar-open .container {
                margin-left: 0;
                padding-left: 0;
            }

            main {
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

            .delete-container {
                text-align: center;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <span class="toggle-icon" id="toggle-icon">❯</span>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-header">Filter DTR</div>
            <div class="year-selector">
                <label class="year-label">Select Year:</label>
                <select class="year-dropdown" id="year-dropdown-nav">
                    <!-- Options will be populated by JavaScript -->
                </select>
                <label class="month-label">Select Month:</label>
                <select class="month-dropdown" id="month-dropdown-nav">
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
        </div>
    </div>

    <div class="content-area">
        <header>
            <div class="container">
                <div class="header-content">
                    <div class="logo-container">
                        <button class="back-button" onclick="window.location.href='pt-dash.php'">←</button>
                        <img alt="School Logo" class="logo" src="Logo.ico" />
                        <div class="school-name">Faculty Portal</div>
                    </div>
                    <div class="date-time" id="date"></div>
                </div>
            </div>
        </header>
        <main>
            <div class="container">
                <h1 class="page-title">Pamantasan ng Lungsod ng Pasig<br>Daily Time Report</h1>

                <div class="header-info">
                    <div>
                        <div class="form-group">
                            <label for="fullName">Full Name:</label>
                            <input type="text" class="form-control" id="fullName"
                                value="<?php echo isset($professor_data['last_name']) ? htmlspecialchars($professor_data['last_name'] . ', ' . $professor_data['first_name']) : ''; ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="employeeNumber">Employee Number:</label>
                            <input type="text" class="form-control" id="employeeNumber"
                                value="<?php echo isset($professor_data['employee_id']) ? htmlspecialchars($professor_data['employee_id']) : ''; ?>"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label for="department">Department:</label>
                            <input type="text" class="form-control" id="department"
                                value="<?php echo isset($professor_data['department']) ? htmlspecialchars($professor_data['department']) : ''; ?>"
                                readonly>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label for="dateFrom">Date From:</label>
                            <input type="date" class="form-control" id="dateFrom" readonly>
                        </div>
                        <div class="form-group">
                            <label for="dateTo">Date To:</label>
                            <input type="date" class="form-control" id="dateTo" readonly>
                        </div>
                    </div>
                </div>

                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Date</th>
                            <th rowspan="2">IN</th>
                            <th rowspan="2">OUT</th>
                            <th rowspan="2">IN</th>
                            <th rowspan="2">OUT</th>
                            <th colspan="2">Hours Rendered</th>
                        </tr>
                        <tr>
                            <th style="width: 8%;">Hrs</th>
                            <th style="width: 8%;">Min</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody">
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>Total</td>
                            <td colspan="4"></td>
                            <td id="totalRequiredHrs">0</td>
                            <td id="totalRequiredMin">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleIcon = document.getElementById('toggle-icon');
        const body = document.body;
        const container = document.querySelector('.container');
        const toggleBtn = document.querySelector('.toggle-btn');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            body.classList.toggle('sidebar-open');

            if (sidebar.classList.contains('active')) {
                toggleIcon.innerHTML = '❮';
                toggleBtn.style.left = '250px';
            } else {
                toggleIcon.innerHTML = '❯';
                toggleBtn.style.left = '0';
            }
        }

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
            document.getElementById('date').innerHTML = formattedDate;
        }


        // Function to format date as "MON-DD"
        function formatDateForDisplay(dateStr) {
            const date = new Date(dateStr);
            const monthNames = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
            const month = monthNames[date.getMonth()];
            const day = String(date.getDate()).padStart(2, '0');
            return `${month}-${day}`;
        }

        // Function to get the first and last day of the month
        function getMonthDateRange(year, month) {
            // Note: month is 1-12 (January = 1)
            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);

            return {
                firstDay: firstDay.toISOString().split('T')[0],
                lastDay: lastDay.toISOString().split('T')[0]
            };
        }

        // Function to populate the table with data
        function populateTable(dtrHeader, dtrDetails) {
            console.log('DTR Header:', dtrHeader);
            console.log('DTR Details:', dtrDetails);

            // Set header information
            document.getElementById('fullName').value = dtrHeader.full_name;
            document.getElementById('employeeNumber').value = dtrHeader.employee_id;
            document.getElementById('department').value = dtrHeader.department;

            const dateFromInput = document.getElementById('dateFrom');
            const dateToInput = document.getElementById('dateTo');

            // If DTR header has date_from and date_to, use those
            if (dtrHeader.date_from && dtrHeader.date_to) {
                dateFromInput.value = dtrHeader.date_from;
                dateToInput.value = dtrHeader.date_to;
            } else {
                // Otherwise calculate based on selected month/year
                const year = document.getElementById('year-dropdown-nav').value;
                const month = document.getElementById('month-dropdown-nav').value;
                const dateRange = getMonthDateRange(year, month);
                dateFromInput.value = dateRange.firstDay;
                dateToInput.value = dateRange.lastDay;
            }

            dateFromInput.dataset.dtrId = dtrHeader.dtr_id || '';

            // Set the total hours
            document.getElementById('totalRequiredHrs').textContent = dtrHeader.total_hours || 0;
            document.getElementById('totalRequiredMin').textContent = dtrHeader.total_minutes || 0;

            // Rest of your populateTable function remains the same...
            // Populate the table body
            const tbody = document.getElementById('attendanceBody');
            tbody.innerHTML = '';

            // Create date objects from date_from and date_to
            const startDate = new Date(dateFromInput.value);
            const endDate = new Date(dateToInput.value);

            // Calculate the number of days between dates
            const timeDiff = endDate - startDate;
            const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end dates

            // Generate rows for each day in the range
            for (let i = 0; i < daysDiff; i++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                const dateStr = currentDate.toISOString().split('T')[0];
                const formattedDate = formatDateForDisplay(dateStr);

                // Find the detail record for this exact date
                const detail = dtrDetails.find(d => {
                    const detailDate = new Date(d.date);
                    return detailDate.toISOString().split('T')[0] === dateStr;
                }) || {};

                console.log(`Processing date: ${dateStr}`, 'Detail found:', detail);

                const row = document.createElement('tr');

                // Date column
                const dateCell = document.createElement('td');
                dateCell.textContent = formattedDate;
                row.appendChild(dateCell);

                // First IN column
                const timeInCell1 = document.createElement('td');
                const timeInInput1 = document.createElement('input');
                timeInInput1.type = 'text';
                timeInInput1.className = 'time-input';
                timeInInput1.value = detail.time_in_am || '';
                timeInInput1.readOnly = true;
                timeInCell1.appendChild(timeInInput1);
                row.appendChild(timeInCell1);

                // First OUT column
                const timeOutCell1 = document.createElement('td');
                const timeOutInput1 = document.createElement('input');
                timeOutInput1.type = 'text';
                timeOutInput1.className = 'time-input';
                timeOutInput1.value = detail.time_out_am || '';
                timeOutInput1.readOnly = true;
                timeOutCell1.appendChild(timeOutInput1);
                row.appendChild(timeOutCell1);

                // Second IN column
                const timeInCell2 = document.createElement('td');
                const timeInInput2 = document.createElement('input');
                timeInInput2.type = 'text';
                timeInInput2.className = 'time-input';
                timeInInput2.value = detail.time_in_pm || '';
                timeInInput2.readOnly = true;
                timeInCell2.appendChild(timeInInput2);
                row.appendChild(timeInCell2);

                // Second OUT column
                const timeOutCell2 = document.createElement('td');
                const timeOutInput2 = document.createElement('input');
                timeOutInput2.type = 'text';
                timeOutInput2.className = 'time-input';
                timeOutInput2.value = detail.time_out_pm || '';
                timeOutInput2.readOnly = true;
                timeOutCell2.appendChild(timeOutInput2);
                row.appendChild(timeOutCell2);

                // Hours Rendered Hours
                const renderedHrsCell = document.createElement('td');
                const renderedHrsInput = document.createElement('input');
                renderedHrsInput.type = 'number';
                renderedHrsInput.value = detail.hours_rendered || '';
                renderedHrsInput.readOnly = true;
                renderedHrsCell.appendChild(renderedHrsInput);
                row.appendChild(renderedHrsCell);

                // Hours Rendered Minutes
                const renderedMinCell = document.createElement('td');
                const renderedMinInput = document.createElement('input');
                renderedMinInput.type = 'number';
                renderedMinInput.value = detail.minutes_rendered || '';
                renderedMinInput.readOnly = true;
                renderedMinCell.appendChild(renderedMinInput);
                row.appendChild(renderedMinCell);

                tbody.appendChild(row);
            }

            if (tbody.children.length === 0) {
                console.warn('No rows were added to the table. Check date ranges and detail records.');
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 7;
                emptyCell.textContent = 'No attendance records found for the selected period';
                emptyCell.style.textAlign = 'center';
                emptyCell.style.padding = '20px';
                emptyCell.style.color = '#666';
                emptyRow.appendChild(emptyCell);
                tbody.appendChild(emptyRow);
            }
        }

        // Function to populate year and month dropdowns
        function populateNavigationDropdowns(availableMonths) {
            const yearDropdown = document.getElementById('year-dropdown-nav');
            const monthDropdown = document.getElementById('month-dropdown-nav');

            // Clear existing options
            yearDropdown.innerHTML = '';
            monthDropdown.innerHTML = '';

            // Check if there are no available months
            if (!availableMonths || availableMonths.length === 0) {
                const noDataOption = document.createElement('option');
                noDataOption.value = '';
                noDataOption.textContent = 'No DTR available';
                noDataOption.disabled = true;
                noDataOption.selected = true;

                yearDropdown.appendChild(noDataOption.cloneNode(true));
                monthDropdown.appendChild(noDataOption);
                return;
            }

            // Sort months by year descending, then by month number descending
            availableMonths.sort((a, b) => {
                if (a.year !== b.year) {
                    return b.year - a.year; // Descending year
                }
                return b.month_number - a.month_number; // Descending month
            });

            // Extract unique years from available months
            const years = [...new Set(availableMonths.map(m => m.year))];

            // Populate year dropdown
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearDropdown.appendChild(option);
            });

            // Function to update month dropdown based on selected year
            const updateMonthDropdown = () => {
                const selectedYear = yearDropdown.value;
                const monthsForYear = availableMonths.filter(m => m.year == selectedYear); // Use == for string/number comparison

                // Clear and repopulate month dropdown
                monthDropdown.innerHTML = '';

                if (monthsForYear.length === 0) {
                    const noDataOption = document.createElement('option');
                    noDataOption.value = '';
                    noDataOption.textContent = 'No DTR available';
                    noDataOption.disabled = true;
                    noDataOption.selected = true;
                    monthDropdown.appendChild(noDataOption);
                } else {
                    monthsForYear.forEach(monthData => {
                        const option = document.createElement('option');
                        option.value = monthData.month_number;
                        option.textContent = monthData.month_name;
                        monthDropdown.appendChild(option);
                    });

                    // Automatically select the first available month for the year
                    if (monthsForYear.length > 0) {
                        monthDropdown.value = monthsForYear[0].month_number;
                        // Trigger the change event to load data
                        const event = new Event('change');
                        monthDropdown.dispatchEvent(event);
                    }
                }
            };

            // When year changes, update month dropdown
            yearDropdown.addEventListener('change', updateMonthDropdown);

            // Set initial values and trigger data load
            if (years.length > 0) {
                // Set the first year as selected
                yearDropdown.value = years[0];

                // Trigger month dropdown update
                updateMonthDropdown();
            }
        }

        // Function to fetch DTR data
        async function fetchDTRData(year, month) {
            try {
                // Get professor_id from URL
                const urlParams = new URLSearchParams(window.location.search);
                const professorId = urlParams.get('professor_id');

                // Show loading state
                document.getElementById('attendanceBody').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Loading data...</td></tr>';

                const response = await fetch(`../Backend/DTR/get_dtr.php?year=${year}&month=${month}&professor_id=${professorId}`);

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                if (data.success && data.dtrHeader) {
                    populateTable(data.dtrHeader, data.dtrDetails);
                } else {
                    // Clear the table
                    document.getElementById('attendanceBody').innerHTML = '';
                    document.getElementById('totalRequiredHrs').textContent = '0';
                    document.getElementById('totalRequiredMin').textContent = '0';

                    // Always update the date range based on the selected year/month
                    const dateRange = getMonthDateRange(year, month);
                    document.getElementById('dateFrom').value = dateRange.firstDay;
                    document.getElementById('dateTo').value = dateRange.lastDay;
                    document.getElementById('dateFrom').dataset.dtrId = '';

                    // Show error message
                    const errorRow = document.createElement('tr');
                    const errorCell = document.createElement('td');
                    errorCell.colSpan = 7;
                    errorCell.textContent = data.error || 'No data available for selected period';
                    errorCell.style.textAlign = 'center';
                    errorCell.style.padding = '20px';
                    errorCell.style.color = '#f44336';
                    errorRow.appendChild(errorCell);
                    document.getElementById('attendanceBody').appendChild(errorRow);
                }
            } catch (error) {
                console.error('Error fetching DTR data:', error);
                // Clear the table on error
                document.getElementById('attendanceBody').innerHTML = '';
                document.getElementById('totalRequiredHrs').textContent = '0';
                document.getElementById('totalRequiredMin').textContent = '0';

                // Always update the date range based on the selected year/month
                const year = document.getElementById('year-dropdown-nav').value;
                const month = document.getElementById('month-dropdown-nav').value;
                const dateRange = getMonthDateRange(year, month);
                document.getElementById('dateFrom').value = dateRange.firstDay;
                document.getElementById('dateTo').value = dateRange.lastDay;
                document.getElementById('dateFrom').dataset.dtrId = '';

                const errorRow = document.createElement('tr');
                const errorCell = document.createElement('td');
                errorCell.colSpan = 7;
                errorCell.textContent = 'Error loading data. Please try again.';
                errorCell.style.textAlign = 'center';
                errorCell.style.padding = '20px';
                errorCell.style.color = '#f44336';
                errorRow.appendChild(errorCell);
                document.getElementById('attendanceBody').appendChild(errorRow);
            }
        }

        // Handle year and month selection changes
        function handleYearMonthChange() {
            const selectedYear = document.getElementById('year-dropdown-nav').value;
            const selectedMonth = document.getElementById('month-dropdown-nav').value;

            if (selectedYear && selectedMonth) {
                // Update URL without reloading the page
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('year', selectedYear);
                newUrl.searchParams.set('month', selectedMonth);
                window.history.pushState({}, '', newUrl);

                // Force update the date range immediately
                const dateRange = getMonthDateRange(selectedYear, selectedMonth);
                document.getElementById('dateFrom').value = dateRange.firstDay;
                document.getElementById('dateTo').value = dateRange.lastDay;
                document.getElementById('dateFrom').dataset.dtrId = '';

                // Then fetch the data
                fetchDTRData(selectedYear, selectedMonth);
            }
        }

        function initializeFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const year = urlParams.get('year');
            const month = urlParams.get('month');
            const professorId = urlParams.get('professor_id');

            if (year && month) {
                const yearDropdown = document.getElementById('year-dropdown-nav');
                const monthDropdown = document.getElementById('month-dropdown-nav');

                if (yearDropdown && monthDropdown) {
                    yearDropdown.value = year;

                    const yearChangeEvent = new Event('change');
                    yearDropdown.dispatchEvent(yearChangeEvent);

                    setTimeout(() => {
                        monthDropdown.value = month;

                        // Trigger the change event to load data
                        const monthChangeEvent = new Event('change');
                        monthDropdown.dispatchEvent(monthChangeEvent);
                    }, 100);
                }
            }
        }

        // Function to fetch available months from database
        async function fetchAvailableMonths() {
            const urlParams = new URLSearchParams(window.location.search);
            const professorId = urlParams.get('professor_id');

            try {
                const response = await fetch(`../Backend/DTR/get_available_months.php?professor_id=${professorId}`);
                const data = await response.json();

                if (data.success) {
                    populateNavigationDropdowns(data.availableMonths || []);

                    if (data.availableMonths && data.availableMonths.length > 0) {
                        // Get the dropdown elements
                        const yearDropdown = document.getElementById('year-dropdown-nav');
                        const monthDropdown = document.getElementById('month-dropdown-nav');

                        // Wait for the dropdowns to be populated
                        setTimeout(() => {
                            // Get the selected values from the dropdowns
                            const selectedYear = yearDropdown.value;
                            const selectedMonth = monthDropdown.value;

                            // Only fetch data if we have valid selections
                            if (selectedYear && selectedMonth) {
                                fetchDTRData(selectedYear, selectedMonth);
                            }
                        }, 0);
                    } else {
                        document.getElementById('attendanceBody').innerHTML = '';
                        document.getElementById('totalRequiredHrs').textContent = '0';
                        document.getElementById('totalRequiredMin').textContent = '0';
                    }
                }
            } catch (error) {
                console.error('Error fetching available months:', error);
                populateNavigationDropdowns([]);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Initialize the page
            fetchAvailableMonths();
            initializeFromUrl();

            // Add event listeners for navigation dropdowns
            document.getElementById('year-dropdown-nav').addEventListener('change', handleYearMonthChange);
            document.getElementById('month-dropdown-nav').addEventListener('change', handleYearMonthChange);
        });
    </script>
</body>

</html>