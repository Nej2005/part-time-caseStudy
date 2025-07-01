<?php
session_start();
require_once '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$professor_last_name = "Professor";
$professor_first_name = "";
$image_data = null;
$employee_id = "";
$professor_id = "";
$notification_message = "";
$notification_class = "";

// Always fetch professor details using email address
if (isset($_SESSION['email_address']) && $_SESSION['user_type'] === 'partTime_Professor') {
    // Get basic professor info
    $stmt = $conn->prepare("SELECT professor_id, first_name, last_name, image FROM PartTime_Professor WHERE email_address = ?");
    $stmt->bind_param("s", $_SESSION['email_address']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $professor = $result->fetch_assoc();
        $_SESSION['first_name'] = $professor['first_name'];
        $_SESSION['last_name'] = $professor['last_name'];
        $_SESSION['professor_id'] = $professor['professor_id'];
        $professor_last_name = $professor['last_name'];
        $professor_first_name = $professor['first_name'];
        $image_data = $professor['image'];
        $professor_id = $professor['professor_id'];
    }
    $stmt->close();

    // Get employee ID from Users table
    $stmt = $conn->prepare("SELECT employee_id FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $employee_id = $user['employee_id'];
    }
    $stmt->close();

    // Check if professor has submitted AR for current month
    $currentMonth = date('F');
    $currentYear = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as ar_count FROM AR_Header WHERE professor_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iss", $professor_id, $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $arRow = $result->fetch_assoc();

    if ($arRow['ar_count'] > 0) {
        $notification_message = "No pending submission. DTR and Form Loading is available for viewing";
        $notification_class = "notification-green";
    } else {
        $notification_message = "Submit an AR for this month";
        $notification_class = "notification-red";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Portal</title>
    <!-- Added Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Poppins', sans-serif;
            color: #333;
            padding-top: 69px;
            /* Added to account for fixed navbar */
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 0 20px;
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
            gap: 15px;
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
            background-color: white;
            color: #2c5e1a;
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
        }

        .dropdown-button-nav:hover {
            background-color: rgb(236, 233, 233);
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
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #ddd
        }

        .dropdown-content-nav a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
            background-color: white;
        }

        .dropdown-content-nav a:hover {
            background-color: #f5f5f5;
            color: #2c5e1a;
        }

        .dropdown-nav:hover .dropdown-content-nav {
            display: block;
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

        main {
            padding: 30px 0;
        }

        .welcome-bar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .welcome-message h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2a0d;
        }

        .welcome-message p {
            color: #666;
            margin-top: 5px;
        }

        .notification {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .notification-red {
            background-color: #ffebee;
            border-left: 5px rgb(119, 54, 54);
            color: rgb(161, 75, 75);
        }

        .notification-green {
            background-color: #e8f5e9;
            border-left: 5px rgb(54, 92, 56);
            color: rgb(90, 124, 92);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 45px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(45deg, #d0c74f 0%, #b8b14a 100%);
            padding: 15px;
            text-align: center;
        }

        .card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #3b5525;
        }

        .card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a2a0d;
        }

        .card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
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
            font-family: 'Poppins', sans-serif;
            text-align: center;
            width: 100%;
        }

        .btn:hover {
            background-color: #1a2a0d;
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

            .welcome-bar {
                flex-direction: column;
                text-align: center;
            }

            .welcome-message {
                margin-bottom: 15px;
            }
        }

        .icon-svg {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
        }

        #logout {
            padding: 6px 10px;
            font-size: 14px;
            border-radius: 5px;
            border-style: none;
            background-color: #3b5525;
            color: white;
            position: absolute;
            left: 740px;
            font-family: 'Poppins', sans-serif;
            margin-top: 30px;
            transition: 0.1s ease-in-out;
            font-weight: 400;
        }

        #logout:hover {
            cursor: pointer;
            box-shadow: 0px 0px 4px rgb(0, 0, 0, 1);
        }

        #logout:active {
            transform: scale(0.94, 0.94);
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
                <?php if ($image_data): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($image_data); ?>" alt="User Profile" />
                <?php else: ?>
                    <img src="/api/placeholder/40/40" alt="User Profile" />
                <?php endif; ?>
            </div>
            <div class="dropdown-nav">
                <button class="dropdown-button-nav">
                    <?php
                    if (isset($_SESSION['last_name'], $_SESSION['first_name'])) {
                        echo htmlspecialchars($_SESSION['last_name'] . ', ' . $_SESSION['first_name']);
                    } else {
                        echo 'Username';
                    }
                    ?>
                </button>
                <div class="dropdown-content-nav">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="about2.php"><i class="fas fa-info-circle"></i> About</a>
                    <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="welcome-bar">
                <div class="welcome-message">
                    <h1>Welcome, Prof. <?php echo htmlspecialchars($professor_last_name); ?>!</h1>
                    <p><?php echo htmlspecialchars($employee_id); ?></p>
                </div>
            </div>

            <div class="notification <?php echo $notification_class; ?>">
                <p><?php echo $notification_message; ?></p>
            </div>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-body">
                        <svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#3b5525" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <h3>Daily Time Record</h3>
                        <p style="text-align: center;">Track and view your daily attendance and time records</p>
                        <a href="dtr-pt.php?professor_id=<?php echo htmlspecialchars($professor_id); ?>" class="btn">Access DTR</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#3b5525" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <h3>Form Loading</h3>
                        <p style="text-align: center;">Access and submit required faculty forms and documents</p>
                        <a href="formload-PT.php?professor_id=<?php echo htmlspecialchars($professor_id); ?>" class="btn">Access Forms</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#3b5525" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <h3>Accomplishment Report</h3>
                        <p style="text-align: center;">Create monthly AR and view previous submitted</p>
                        <a href="ar-pt.php?professor_id=<?php echo htmlspecialchars($professor_id); ?>" class="btn">Create & View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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

        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>

</html>