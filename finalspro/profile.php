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
$first_name = '';
$middle_initial = '';
$last_name = '';
$email = '';
$employee_id = '';
$department = '';
$image_data = null;

// Get user info based on user type
if ($user_type === 'Admin_Secretary') {
  $stmt = $conn->prepare("SELECT a.firstname, a.middle_initial, a.lastname, u.email_address, u.employee_id, a.department, a.image 
                           FROM Admin_Secretary a 
                           JOIN Users u ON a.admin_id = u.user_id 
                           WHERE u.user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first_name = $row['firstname'];
    $middle_initial = $row['middle_initial'];
    $last_name = $row['lastname'];
    $email = $row['email_address'];
    $employee_id = $row['employee_id'];
    $department = $row['department'];
    $image_data = $row['image'];
  }
} elseif ($user_type === 'partTime_Professor') {
  $stmt = $conn->prepare("SELECT first_name, middle_initial, last_name, u.email_address, u.employee_id, department, image 
                           FROM PartTime_Professor p
                           JOIN Users u ON p.email_address = u.email_address
                           WHERE u.user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first_name = $row['first_name'];
    $middle_initial = $row['middle_initial'];
    $last_name = $row['last_name'];
    $email = $row['email_address'];
    $employee_id = $row['employee_id'];
    $department = $row['department'];
    $image_data = $row['image'];
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['save_personal_info'])) {
    // Handle personal info update
    $new_first_name = $_POST['first_name'];
    $new_middle_initial = $_POST['middle_initial'];
    $new_last_name = $_POST['last_name'];
    $new_department = $_POST['department'];
    $new_email = $_POST['email'];
    $new_employee_id = $_POST['employee_id'];

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION['profile_update_error'] = "Invalid email format";
      header("Location: profile.php");
      exit();
    }

    $allowed_domains = ['gmail.com', 'plpasig.edu.ph'];
    $email_parts = explode('@', $new_email);
    $domain = strtolower(end($email_parts));

    if (!in_array($domain, $allowed_domains)) {
      $_SESSION['profile_update_error'] = "Only @gmail.com or @plpasig.edu.ph email addresses are allowed!";
      header("Location: profile.php");
      exit();
    }

    // Check if email is already in use by another user
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email_address = ? AND user_id != ?");
    $stmt->bind_param("si", $new_email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $_SESSION['profile_update_error'] = "Email address already in use";
      header("Location: profile.php");
      exit();
    }

    // Check if employee ID is already in use by another user
    if (!empty($new_employee_id)) {
      $stmt = $conn->prepare("SELECT user_id FROM Users WHERE employee_id = ? AND user_id != ?");
      $stmt->bind_param("si", $new_employee_id, $user_id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $_SESSION['profile_update_error'] = "Employee ID already in use";
        header("Location: profile.php");
        exit();
      }
    }

    // Handle image upload
    $image_updated = false;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
      $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
      $file_type = $_FILES['profile_image']['type'];
      $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

      if (!in_array($file_type, $allowed_types) || !in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
        $_SESSION['profile_update_error'] = "Only JPG, JPEG, and PNG files are allowed.";
        header("Location: profile.php");
        exit();
      }

      $max_size = 2 * 1024 * 1024;
      if ($_FILES['profile_image']['size'] > $max_size) {
        $_SESSION['profile_update_error'] = "File size must be less than 2MB.";
        header("Location: profile.php");
        exit();
      }

      $image_tmp = $_FILES['profile_image']['tmp_name'];
      $image_data = file_get_contents($image_tmp);
      $image_updated = true;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
      // First update Users table (email and employee_id)
      $updateUser = $conn->prepare("UPDATE Users SET email_address = ?, employee_id = ? WHERE user_id = ?");
      $updateUser->bind_param("ssi", $new_email, $new_employee_id, $user_id);
      $updateUser->execute();

      // Then update the specific profile table
      if ($user_type === 'Admin_Secretary') {
        $updateProfile = $conn->prepare("UPDATE Admin_Secretary 
                                               SET firstname = ?, middle_initial = ?, lastname = ?, department = ?" .
          ($image_updated ? ", image = ?" : "") .
          " WHERE admin_id = ?");

        if ($image_updated) {
          $updateProfile->bind_param(
            "sssssi",
            $new_first_name,
            $new_middle_initial,
            $new_last_name,
            $new_department,
            $image_data,
            $user_id
          );
        } else {
          $updateProfile->bind_param(
            "ssssi",
            $new_first_name,
            $new_middle_initial,
            $new_last_name,
            $new_department,
            $user_id
          );
        }
      } elseif ($user_type === 'partTime_Professor') {
        $updateProfile = $conn->prepare("UPDATE PartTime_Professor 
                                               SET first_name = ?, middle_initial = ?, last_name = ?, department = ?" .
          ($image_updated ? ", image = ?" : "") .
          " WHERE email_address = ?");

        if ($image_updated) {
          $updateProfile->bind_param(
            "ssssss",
            $new_first_name,
            $new_middle_initial,
            $new_last_name,
            $new_department,
            $image_data,
            $email
          );
        } else {
          $updateProfile->bind_param(
            "sssss",
            $new_first_name,
            $new_middle_initial,
            $new_last_name,
            $new_department,
            $email
          );
        }
      }

      $updateProfile->execute();

      // Commit transaction
      $conn->commit();

      $_SESSION['profile_update_success'] = "Profile updated successfully!";
      $_SESSION['first_name'] = $new_first_name;
      $_SESSION['last_name'] = $new_last_name;
      $_SESSION['email'] = $new_email;
      header("Location: profile.php");
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $_SESSION['profile_update_error'] = "Error updating profile: " . $e->getMessage();
      header("Location: profile.php");
      exit();
    }
  } elseif (isset($_POST['change_password'])) {
    // Password change logic
    $currentPassword = trim($_POST['currentPassword']);
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Verify current password first
    $stmt = $conn->prepare("SELECT password FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      $storedPassword = $user['password'];

      // Debug output (remove in production)
      // error_log("Input password: " . $currentPassword);
      // error_log("Stored password: " . $storedPassword);

      if ($currentPassword !== $storedPassword) {
        $_SESSION['profile_update_error'] = "Current password is incorrect";
        header("Location: profile.php");
        exit();
      }

      if ($newPassword !== $confirmPassword) {
        $_SESSION['profile_update_error'] = "New passwords do not match";
        header("Location: profile.php");
        exit();
      }

      if (strlen($newPassword) < 8) {
        $_SESSION['profile_update_error'] = "Password must be at least 8 characters";
        header("Location: profile.php");
        exit();
      }

      // Update password (store in plain text)
      $updatePass = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
      $updatePass->bind_param("si", $newPassword, $user_id);

      if ($updatePass->execute()) {
        $_SESSION['profile_update_success'] = "Password changed successfully!";
        header("Location: profile.php");
        exit();
      } else {
        $_SESSION['profile_update_error'] = "Error updating password: " . $conn->error;
        header("Location: profile.php");
        exit();
      }
    } else {
      $_SESSION['profile_update_error'] = "User not found";
      header("Location: profile.php");
      exit();
    }
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faculty Portal - Profile Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap");

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      transition: margin-left 0.3s ease;
    }

    body {
      background-color: #f5f5f5;
      font-family: "Poppins", sans-serif;
      color: #333;
      overflow-x: hidden;
    }

    header {
      background: linear-gradient(135deg, #3b5525 0%, #1a2a0d 100%);
      color: white;
      padding: 15px 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: relative;
      margin-bottom: 30px;
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
      background-color: #fff;
      border-radius: 50%;
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

    /* Profile Settings Styles */
    .profile-settings {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 30px;
      margin-bottom: 30px;
    }

    .settings-title {
      font-family: "Montserrat", sans-serif;
      font-size: 1.5rem;
      font-weight: 600;
      color: #3b5525;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e5e5e5;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: 250px 1fr;
      gap: 30px;
    }

    .profile-picture-section {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .profile-picture {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #3b5525;
      margin-bottom: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .upload-btn {
      background-color: #3b5525;
      color: white;
      border: none;
      border-radius: 5px;
      padding: 8px 15px;
      font-family: "Poppins", sans-serif;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .upload-btn:hover {
      background-color: #2a3e1c;
      transform: translateY(-2px);
    }

    .profile-info-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .alert {
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      display: none;
    }

    .alert-success {
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      display: block;
    }

    .alert-danger {
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      display: block;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #555;
    }

    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: "Poppins", sans-serif;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: #3b5525;
      box-shadow: 0 0 0 2px rgba(59, 85, 37, 0.2);
    }

    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }

    .section-title {
      font-family: "Montserrat", sans-serif;
      font-size: 1.1rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      margin-top: 20px;
    }

    .password-section {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e5e5e5;
    }

    .btn-save {
      background-color: #3b5525;
      color: white;
      border: none;
      border-radius: 5px;
      padding: 12px 25px;
      font-family: "Poppins", sans-serif;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      margin-top: 20px;
    }

    .btn-save:hover {
      background-color: #2a3e1c;
      transform: translateY(-2px);
    }

    .btn-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }

    /* Password field styles */
    .password-container {
      position: relative;
    }

    .password-input-wrapper {
      position: relative;
      width: 50%;
    }

    .password-input {
      padding-right: 35px;
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #666;
      background: none;
      border: none;
      padding: 5px;
    }

    .toggle-password:hover {
      color: #3b5525;
    }

    input[type="password"]::-webkit-reveal,
    input[type="password"]::-webkit-caps-lock-indicator,
    input[type="password"]::-webkit-credentials-auto-fill-button,
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
      position: absolute;
      right: -9999px;
      width: 0;
      height: 0;
      pointer-events: none;
    }

    input::-ms-reveal,
    input::-ms-clear {
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
      position: absolute;
      right: -9999px;
      width: 0;
      height: 0;
      pointer-events: none;
    }

    @media (max-width: 768px) {
      .profile-grid {
        grid-template-columns: 1fr;
      }

      .profile-picture-section {
        margin-bottom: 20px;
      }

      .password-input-wrapper {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="container">
      <div class="header-content">
        <div class="logo-container">
          <button class="back-button" onclick="goBack()">&larr;</button>
          <div class="logo">
            <img class="logo" src="Logo.ico" alt="School Logo" />
          </div>
          <div class="school-name">Faculty Portal</div>
        </div>
        <div class="date-time" id="date"></div>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="profile-settings">
      <h2 class="settings-title">Profile Settings</h2>

      <?php if (isset($_SESSION['profile_update_success'])): ?>
        <div id="successAlert" class="alert-success">
          <?php echo $_SESSION['profile_update_success'];
          unset($_SESSION['profile_update_success']); ?>
        </div>
        <script>
          // Scroll to the alert
          document.getElementById('successAlert').scrollIntoView({
            behavior: 'smooth'
          });
        </script>
      <?php endif; ?>

      <?php if (isset($_SESSION['profile_update_error'])): ?>
        <div id="errorAlert" class="alert-danger">
          <?php echo $_SESSION['profile_update_error'];
          unset($_SESSION['profile_update_error']); ?>
        </div>
        <script>
          document.getElementById('errorAlert').scrollIntoView({
            behavior: 'smooth'
          });
        </script>
      <?php endif; ?>

      <form method="POST" action="profile.php" enctype="multipart/form-data">
        <div class="profile-grid">
          <div class="profile-picture-section">
            <img id="profileImage" src="<?php echo $image_data ? 'data:image/jpeg;base64,' . base64_encode($image_data) : '/api/placeholder/200/200'; ?>" alt="Profile Picture" class="profile-picture" />
            <input type="file" id="imageUpload" name="profile_image" accept=".jpg,.jpeg,.png" style="display: none;" />
            <button type="button" class="upload-btn" onclick="document.getElementById('imageUpload').click()">
              Change Picture
            </button>
          </div>

          <div class="profile-info-section">
            <h3 class="section-title">Personal Information</h3>
            <div class="form-group">
              <label for="employee_id">Employee ID</label>
              <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employee_id); ?>">
            </div>
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
            </div>
            <div class="form-group">
              <label for="middleInitial">Middle Initial</label>
              <input type="text" id="middleInitial" name="middle_initial" class="form-control" value="<?php echo htmlspecialchars($middle_initial); ?>" maxlength="1">
            </div>
            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
            </div>
            <div class="form-group">
              <label for="department">Department</label>
              <input type="text" id="department" name="department" class="form-control" value="<?php echo htmlspecialchars($department); ?>">
            </div>
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required pattern="[a-zA-Z0-9._%+-]+@(gmail\.com|plpasig\.edu\.ph)$" title="Only @gmail.com or @plpasig.edu.ph email addresses are allowed">
            </div>

            <div class="btn-container">
              <button type="submit" name="save_personal_info" class="btn-save">Save Personal Info</button>
            </div>

            <div class="password-section">
              <h3 class="section-title">Change Password</h3>
              <div class="form-group password-container">
                <label for="currentPassword">Current Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="currentPassword" name="currentPassword" class="form-control password-input" />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('currentPassword')">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-group password-container">
                <label for="newPassword">New Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="newPassword" name="newPassword" class="form-control password-input" />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('newPassword')">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-group password-container">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="password-input-wrapper">
                  <input type="password" id="confirmPassword" name="confirmPassword" class="form-control password-input" />
                  <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword')">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="btn-container">
                <button type="submit" name="change_password" class="btn-save">Change Password</button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Update date and time
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
      const formattedDate = now.toLocaleString("en-US", options);
      document.getElementById("date").innerHTML = formattedDate;
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Profile image preview
    const imageUpload = document.getElementById('imageUpload');
    const profileImage = document.getElementById('profileImage');

    imageUpload.addEventListener('change', function() {
      const file = this.files[0];
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

      if (file) {
        // Client-side validation
        if (!allowedTypes.includes(file.type)) {
          alert('Only JPG, JPEG, and PNG files are allowed.');
          this.value = ''; // Clear the file input
          return;
        }

        // Optional size validation
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
          alert('File size must be less than 2MB.');
          this.value = ''; // Clear the file input
          return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
          profileImage.src = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });

    // Toggle password visibility
    function togglePasswordVisibility(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const eyeIcon = passwordField.nextElementSibling.querySelector('i');

      if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
      } else {
        passwordField.type = "password";
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
      }
    }

    document.getElementById('email').addEventListener('input', function() {
      const email = this.value;
      const allowedDomains = ['gmail.com', 'plpasig.edu.ph'];
      const domain = email.split('@')[1];

      if (email.includes('@') && domain && !allowedDomains.includes(domain)) {
        this.setCustomValidity('Only @gmail.com or @plpasig.edu.ph email addresses are allowed');
        this.reportValidity();
      } else {
        this.setCustomValidity('');
      }
    })

    function goBack() {
      <?php if ($user_type === 'Admin_Secretary'): ?>
        window.location.href = 'management.php';
      <?php elseif ($user_type === 'partTime_Professor'): ?>
        window.location.href = 'pt-dash.php';
      <?php else: ?>
        history.back();
      <?php endif; ?>
    }
  </script>
</body>

</html>