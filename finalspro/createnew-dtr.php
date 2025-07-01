<?php
include '../Backend/db_connect.php';

// Add this at the beginning to fetch professor data
$professor_id = isset($_GET['professor_id']) ? $_GET['professor_id'] : null;
$professor_data = null;

if ($professor_id) {
    $stmt = $conn->prepare("SELECT * FROM PartTime_Professor WHERE professor_id = ?");
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $professor_data = $result->fetch_assoc();
    $stmt->close();
}

if ($professor_data && isset($professor_data['employee_number'])) {
    $employee_number = $professor_data['employee_number'];
} else {
    $employee_number = '';
}
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

        /* Updated header-info section to match settings.php form styling */
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

        /* Updated form-group styling to match settings.php */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        /* Updated input styling to match settings.php */
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

        /* Updated button styling to match settings.php */
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
        }
    </style>
</head>

<body>
    <div class="content-area">
        <header>
            <div class="container">
                <div class="header-content">
                    <div class="logo-container">
                        <button class="back-button" onclick="window.history.back()">←</button>
                        <img alt="School Logo" class="logo" src="Logo.ico" />
                        <div class="school-name">Faculty Portal</div>
                    </div>
                    <div class="date-time" id="date"></div>
                </div>
            </div>
        </header>
        <main>
            <form id="schedForm">
                <div class="container">
                    <h1 class="page-title">Pamantasan ng Lungsod ng Pasig<br>Daily Time Report</h1>
                    <div class="header-info">
                        <div>
                            <div class="form-group">
                                <label for="fullName">Full Name:</label>
                                <input type="text" class="form-control" required name="fullName" id="fullName" placeholder="Part-Time Employee">
                            </div>
                            <div class="form-group">
                                <label for="employeeNumber">Employee Number:</label>
                                <input type="text" class="form-control" required name="employeeNumber" id="employeeNumber"
                                    placeholder="Employee ID" pattern="[0-9]{1,10}" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    value="<?php echo htmlspecialchars($employee_number); ?>">
                            </div>
                            <div class="form-group">
                                <label for="department">Department:</label>
                                <input type="text" class="form-control" required name="department" id="department" value="CCS- Parttime" readonly>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="dateFrom">Date From:</label>
                                <input type="date" class="form-control" required name="dateFrom" id="dateFrom" value="2024-11-01">
                            </div>
                            <div class="form-group">
                                <label for="dateTo">Date To:</label>
                                <input type="date" class="form-control" required name="dateTo" id="dateTo" value="2024-11-30">
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
                            <!-- Rows will be generated by JavaScript -->
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

                    <div class="upload-container">
                        <input type="file" name="uploadedFile" id="fileUpload" accept=".csv, .xlsx" style="display: none;">
                        <button type="button" class="upload-btn" onclick="document.getElementById('fileUpload').click()">
                            <i class="fas fa-upload"></i> Upload CSV
                        </button>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save"></i> Save Data
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <!-- Save Modal -->
    <div class="modal" id="saveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-save"></i> Save DTR</h2>
                <button class="close-modal" onclick="closeSaveModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <label for="year">Year:</label>
                    <input type="text" id="year" name="year" required>
                </div>
                <div class="form-row">
                    <label for="month">Month:</label>
                    <input type="text" id="month" name="month" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeSaveModal()">Cancel</button>
                <button type="button" class="btn btn-submit" id="confirmSaveBtn">Save</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const professor_id = urlParams.get('professor_id');

        // Function to get month name from date
        function getMonthName(dateString) {
            const date = new Date(dateString);
            const months = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            return months[date.getMonth()];
        }

        // Function to open save modal
        function openSaveModal() {
            // Get the month from dateFrom field
            const dateFrom = document.getElementById('dateFrom').value;
            const month = getMonthName(dateFrom);

            // Extract the year from dateFrom
            const dateObj = new Date(dateFrom);
            const year = dateObj.getFullYear();

            // Set the month and year in the modal
            document.getElementById('month').value = month;
            document.getElementById('year').value = year;

            // Open the modal
            document.getElementById('saveModal').style.display = "flex";
        }

        // Function to close save modal
        function closeSaveModal() {
            document.getElementById('saveModal').style.display = "none";
        }

        // Function to validate and format time input
        function validateTime(timeStr) {
            // Regular expression to validate HH:MM format
            const timePattern = /^([01]?[0-9]|2[0-3]):([0-5]?[0-9])$/;
            return timePattern.test(timeStr);
        }

        // Calculate the time difference
        // Calculate the time difference properly
        function calculateTimeDifference(inTime, outTime) {
            if (!inTime || !outTime) return {
                hours: "",
                minutes: ""
            };

            // Parse the times properly considering AM/PM
            function parseTimeWithPeriod(timeStr) {
                let [time, period] = timeStr.split(/(?=[AP]M)/i);
                if (!period) {
                    // If no AM/PM specified, assume 24-hour format
                    const [hours, minutes] = time.split(':').map(Number);
                    return {
                        hours,
                        minutes
                    };
                }

                let [hours, minutes] = time.split(':').map(Number);
                period = period.toUpperCase();

                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }

                return {
                    hours,
                    minutes
                };
            }

            try {
                const inTimeObj = parseTimeWithPeriod(inTime);
                const outTimeObj = parseTimeWithPeriod(outTime);

                let totalMinutes = (outTimeObj.hours * 60 + outTimeObj.minutes) -
                    (inTimeObj.hours * 60 + inTimeObj.minutes);

                if (totalMinutes < 0) {
                    // Handle overnight case (though unlikely for work hours)
                    totalMinutes += 24 * 60;
                }

                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;

                return {
                    hours,
                    minutes
                };
            } catch (e) {
                console.error("Error calculating time difference:", e);
                return {
                    hours: "",
                    minutes: ""
                };
            }
        }

        // Parse time from "HH:MM" format
        function parseTime(timeString) {
            if (!timeString) return {
                parsedHours: 0,
                parsedMinutes: 0
            };

            // Handle cases where time might have AM/PM
            timeString = timeString.toString().toUpperCase();
            let hours = 0,
                minutes = 0;

            if (timeString.includes('AM') || timeString.includes('PM')) {
                const [time, period] = timeString.split(/(?=[AP]M)/);
                const [h, m] = time.split(':').map(Number);

                hours = h;
                minutes = m || 0;

                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (period === 'AM' && hours === 12) {
                    hours = 0;
                }
            } else {
                // 24-hour format
                const [h, m] = timeString.split(':').map(Number);
                hours = h || 0;
                minutes = m || 0;
            }

            return {
                parsedHours: hours,
                parsedMinutes: minutes
            };
        }

        let attendanceData = [];

        document.getElementById('fileUpload').addEventListener('change', handleFile, false);

        function handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: 'array'
                });

                const sheetName = workbook.SheetNames[0];
                const sheet = workbook.Sheets[sheetName];
                const jsonData = XLSX.utils.sheet_to_json(sheet, {
                    header: 1,
                    raw: false
                });

                let structuredData = {};
                if (jsonData.length >= 3) {
                    structuredData["Full Name"] = jsonData[0][1];
                    structuredData["Employee Number"] = jsonData[1][1] ? String(jsonData[1][1]).padStart(7, '0') : "";
                    structuredData["Department"] = jsonData[2][1] || document.getElementById("department").value;
                    structuredData["Date From"] = jsonData[0][3];
                    structuredData["Date To"] = jsonData[1][3];
                }

                if (!structuredData["Department"]) {
                    structuredData["Department"] = "CCS- Parttime";
                }

                // Convert date strings to proper format "YYYY-MM-DD"
                function formatDateString(input) {
                    if (!input) return '';
                    const date = new Date(input);
                    if (isNaN(date)) return input; // Return original if invalid

                    const yyyy = date.getFullYear();
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                }

                // Set form values
                document.getElementById("fullName").value = structuredData["Full Name"];
                document.getElementById("employeeNumber").value = structuredData["Employee Number"];
                document.getElementById("department").value = structuredData["Department"];
                document.getElementById("dateFrom").value = formatDateString(structuredData["Date From"]);
                document.getElementById("dateTo").value = formatDateString(structuredData["Date To"]);

                // Get the year from the date range
                const dateFrom = new Date(document.getElementById("dateFrom").value);
                const year = dateFrom.getFullYear();

                let dateRows = [];
                for (let i = 5; i < jsonData.length; i++) {
                    const row = jsonData[i];
                    if (row[0] && row[0].trim()) {
                        const dateData = {
                            "DisplayDate": row[0], // Keep original display format (e.g. "FEB-01")
                            "In": row[1] || "",
                            "Out": row[2] || "",
                            "In1": row[3] || "",
                            "Out1": row[4] || ""
                        };
                        dateRows.push(dateData);
                    } else {
                        break;
                    }
                }

                const attendanceBody = document.getElementById("attendanceBody");
                attendanceBody.innerHTML = ""; // Clear previous rows
                attendanceData = []; // Reset attendance data

                let totalHours = 0;
                let totalMinutes = 0;

                dateRows.forEach(row => {
                    const tr = document.createElement("tr");

                    // Display the date in "FEB-01" format
                    const tdDate = document.createElement("td");
                    tdDate.textContent = row.DisplayDate;

                    // Convert display date to proper storage format with correct year
                    let storedDate;
                    if (row.DisplayDate.match(/^[A-Za-z]{3}-\d{2}$/)) {
                        // Format like "FEB-01" - convert to proper date with correct year
                        const monthAbbr = row.DisplayDate.split('-')[0];
                        const day = row.DisplayDate.split('-')[1];
                        const month = new Date(`${monthAbbr} 1, ${year}`).getMonth() + 1;
                        storedDate = `${year}-${String(month).padStart(2, '0')}-${day.padStart(2, '0')}`;
                    } else {
                        // Try to parse other formats
                        const dateObj = new Date(row.DisplayDate);
                        storedDate = !isNaN(dateObj) ? dateObj.toISOString().split('T')[0] : row.DisplayDate;
                    }

                    // Create time cells with inputs for editing
                    const createTimeCell = (time) => {
                        const td = document.createElement("td");
                        const input = document.createElement("input");
                        input.type = "text";
                        input.className = "time-input";
                        input.value = time;
                        td.appendChild(input);
                        return td;
                    };

                    const tdIn1 = createTimeCell(row.In);
                    const tdOut1 = createTimeCell(row.Out);
                    const tdIn2 = createTimeCell(row.In1);
                    const tdOut2 = createTimeCell(row.Out1);

                    const tdHours = document.createElement("td");
                    const tdMinutes = document.createElement("td");

                    // Calculate time differences
                    let hours1 = 0,
                        minutes1 = 0;
                    let hours2 = 0,
                        minutes2 = 0;

                    if (row.In && row.Out) {
                        const morningDiff = calculateTimeDifference(row.In, row.Out);
                        hours1 = morningDiff.hours || 0;
                        minutes1 = morningDiff.minutes || 0;
                    }

                    if (row.In1 && row.Out1) {
                        const afternoonDiff = calculateTimeDifference(row.In1, row.Out1);
                        hours2 = afternoonDiff.hours || 0;
                        minutes2 = afternoonDiff.minutes || 0;
                    }

                    // Calculate total hours and minutes for the day
                    let dayHours = hours1 + hours2;
                    let dayMinutes = minutes1 + minutes2;

                    // Adjust minutes if they exceed 60
                    if (dayMinutes >= 60) {
                        dayHours += Math.floor(dayMinutes / 60);
                        dayMinutes = dayMinutes % 60;
                    }

                    // Add to totals
                    totalHours += dayHours;
                    totalMinutes += dayMinutes;

                    // Display row results
                    tdHours.textContent = dayHours;
                    tdMinutes.textContent = dayMinutes;

                    // Store in attendanceData with both display and storage formats
                    attendanceData.push({
                        Date: storedDate, // For database (YYYY-MM-DD)
                        DisplayDate: row.DisplayDate, // For display (FEB-01)
                        In: row.In,
                        Out: row.Out,
                        In1: row.In1,
                        Out1: row.Out1,
                        Hours: dayHours,
                        Minutes: dayMinutes
                    });

                    // Build the row
                    tr.appendChild(tdDate);
                    tr.appendChild(tdIn1);
                    tr.appendChild(tdOut1);
                    tr.appendChild(tdIn2);
                    tr.appendChild(tdOut2);
                    tr.appendChild(tdHours);
                    tr.appendChild(tdMinutes);

                    attendanceBody.appendChild(tr);
                });

                // Calculate and display totals
                totalHours += Math.floor(totalMinutes / 60);
                totalMinutes = totalMinutes % 60;

                document.getElementById('totalRequiredHrs').textContent = totalHours;
                document.getElementById('totalRequiredMin').textContent = totalMinutes;
            };

            reader.readAsArrayBuffer(file);
        }

        let profId;

        // Save button click handler
        document.getElementById('saveBtn').addEventListener('click', function(e) {
            e.preventDefault();

            const fullName = document.getElementById('fullName').value;
            const employeeNumber = document.getElementById('employeeNumber').value;
            const department = document.getElementById('department').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            // Enhanced validation
            if (!fullName || !employeeNumber || !dateFrom || !dateTo) {
                alert('Please fill in all required fields');
                return;
            }

            // Validate employee number is not empty and contains only digits
            if (!employeeNumber || !/^\d+$/.test(employeeNumber)) {
                alert('Employee number must contain only digits');
                return;
            }

            // Check if there's any attendance data
            if (attendanceData.length === 0) {
                alert('Please add attendance data before saving');
                return;
            }

            // Open the save modal
            openSaveModal();
        });

        // Update the confirmSaveBtn click handler
        // Update the confirmSaveBtn click handler
        document.getElementById('confirmSaveBtn').addEventListener('click', async function() {
            const yearInput = document.getElementById('year');
            const year = yearInput.value;

            if (!year) {
                alert('Please enter the year');
                return;
            }

            // Validate the year is a number and reasonable (e.g., between 2000 and 2100)
            if (!/^\d{4}$/.test(year) || parseInt(year) < 2000 || parseInt(year) > 2100) {
                alert('Please enter a valid year between 2000 and 2100');
                return;
            }

            const fullName = document.getElementById('fullName').value;
            const employeeNumber = document.getElementById('employeeNumber').value;
            const department = document.getElementById('department').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            if (!employeeNumber || !/^\d+$/.test(employeeNumber)) {
                alert('Employee number must contain only digits');
                return;
            }

            try {
                // First save the header
                const headerResponse = await fetch('../Backend/DTR/dtr_header.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: fullName,
                        employee_number: employeeNumber,
                        department: department,
                        date_from: dateFrom,
                        date_to: dateTo,
                        year: year, // Use the year from the input field
                        professor_id: professor_id
                    })
                });

                const headerData = await headerResponse.json();

                if (headerData.success && headerData.dtr_id) {
                    // Then save the attendance details
                    const detailsResponse = await fetch('../Backend/DTR/dtr_details.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            professor_id: headerData.professor_id,
                            dtr_id: headerData.dtr_id,
                            attendance: attendanceData
                        })
                    });

                    const detailsData = await detailsResponse.json();

                    if (detailsData.success) {
                        closeSaveModal();
                        alert('DTR saved successfully!');
                        // Optionally reset the form or redirect
                    } else {
                        throw new Error(detailsData.error || 'Failed to save DTR details');
                    }
                } else {
                    throw new Error(headerData.error || 'Failed to save DTR header');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving DTR: ' + error.message);
            }
        });

        async function insertAttendance(professorId, attendanceData) {
            try {
                const response = await fetch('../Backend/DTR/dtr_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        professor_id: professorId,
                        attendance: attendanceData
                    })
                });

                const text = await response.text();
                console.log("Raw response from server:", text);

                try {
                    const data = JSON.parse(text);
                    if (data.message) {
                        console.log(data.message);
                        attendanceData = [];
                    } else if (data.error) {
                        console.error("Server error:", data.error);
                    }
                } catch (e) {
                    console.error("Failed to parse JSON:", e);
                    console.error("Raw server response:", text);
                }
            } catch (error) {
                console.error("Fetch error:", error);
            }
        }

        // Initialize date time display
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

        // Update date time every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>

</html>