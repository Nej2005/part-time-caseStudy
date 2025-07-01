<?php
session_start();
require_once '../Backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Initialize variables
$image_data = null;
$user_name = "Username";

// Fetch professor details if part-time professor
if (isset($_SESSION['email_address']) && $_SESSION['user_type'] === 'partTime_Professor') {
  $stmt = $conn->prepare("SELECT first_name, last_name, image FROM PartTime_Professor WHERE email_address = ?");
  $stmt->bind_param("s", $_SESSION['email_address']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $professor = $result->fetch_assoc();
    $user_name = htmlspecialchars($professor['last_name'] . ', ' . htmlspecialchars($professor['first_name']));
    $image_data = $professor['image'];
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
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
      z-index: 1001;
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


    .notification {
      background-color: #f0eed0;
      border-left: 5px solid #d0c74f;
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 30px;
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

    .container-msg {
      background-color: #ddd;
      width: 100%;
      height: 100%;
      margin: auto;
      max-width: 1200px;
      max-height: 700px;
      min-height: 470px;
      box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.2);
      border-radius: 20px;
      transition: transform 25s ease, box-shadow 0.25s ease;
      margin-top: 80px;
      animation: color 3s infinite;
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

    .back-button {
      background-color: white;
      color: #3b5525;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      cursor: pointer;
      margin-left: 20px;
      margin-top: 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      transition: all 0.2s ease;

    }


    .back-button:hover {
      background-color: #f0f0f0;
      transform: translateY(-2px);
    }
  </style>
</head>

<body>
  <button class="back-button" onclick="window.history.back()">
    <i id="arr" class="fas fa-arrow-left"></i>
  </button>

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
          <?php echo $user_name; ?>
        </button>
        <div class="dropdown-content-nav">
          <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
          <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>

        </div>
      </div>
    </div>
  </nav>



  <div class="container-msg">
    <h3 class="about-text">The Part-Time Faculty Salary Processing System is a web-based platform designed to automate and streamline faculty record management at Pamantasan ng Lungsod ng Pasig. It digitizes Daily Time Records (DTR) with OCR scanning, generates Accomplishment Reports (AR), and manages Faculty Loading Forms, replacing manual processes with an efficient digital workflow. The system features role-based access for admins and part-time faculty, automated time/load calculations, digital approvals, and PDF report generation—reducing errors, saving time, and ensuring accurate payroll processing. Developed using PHP, MySQL, and JavaScript, it provides a secure, transparent solution for managing part-time faculty records.</h3>
  </div>


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