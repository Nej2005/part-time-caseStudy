<?php
include '../Backend/db_connect.php';

// Debugging - log all received data
error_log("===== RESETPASS.PHP ACCESSED =====");
error_log("GET Parameters: " . print_r($_GET, true));

// Check if token is provided and valid
$tokenValid = false;
$token = $_GET['token'] ?? '';
$errorReason = '';

if (!empty($token)) {
  error_log("Checking token validity for: " . $token);

  // Query to check token validity
  $sql = "SELECT token_id, user_id, expires_at, used FROM Password_Reset_Tokens WHERE token = ?";
  $stmt = $conn->prepare($sql);

  if ($stmt) {
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $tokenData = $result->fetch_assoc();
      error_log("Token found in DB: " . print_r($tokenData, true));

      $now = new DateTime();
      $expiresAt = new DateTime($tokenData['expires_at']);

      if ($tokenData['used']) {
        $errorReason = "Token already used";
        error_log("Token already used");
      } elseif ($expiresAt <= $now) {
        $errorReason = "Token expired";
        error_log("Token expired");
      } else {
        $tokenValid = true;
        error_log("Token is valid");
      }
    } else {
      $errorReason = "Token not found in database";
      error_log("Token not found in database");
    }
  } else {
    $errorReason = "Database error";
    error_log("Database prepare error: " . $conn->error);
  }
} else {
  $errorReason = "No token provided";
  error_log("No token provided");
}

if (!$tokenValid) {
  // For debugging, show error on page instead of redirecting
  die("Invalid token. Reason: " . htmlspecialchars($errorReason) .
    ". Token: " . htmlspecialchars($token) .
    ". Please check the server logs for details.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pamantasan ng Lungsod ng Pasig - Reset Password</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Exo+2&family=Kanit&family=Nunito&family=Varela+Round&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Gidole&family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap');

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-image: url(back.png);
      background-size: cover;
      background-position: center;
      font-family: 'Nunito', sans-serif;
      height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(29, 61, 17, 0.1);
      z-index: -1;
    }

    #nav-top {
      background: linear-gradient(to right, #2c5e1a, #1d3d11);
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 69px;
      width: 100%;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .nav-left {
      display: flex;
      align-items: center;
    }

    .nav-right {
      display: flex;
      align-items: center;
    }

    .nav-link {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
    }

    .nav-link:hover {
      color: #ffff99;
    }

    .nav-link::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -4px;
      left: 0;
      background: #ffff99;
      transition: all 0.3s ease;
    }

    .nav-link:hover::after {
      width: 100%;
    }

    #logo-nav {
      width: 43px;
      margin-right: 15px;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    }

    .university-name {
      display: flex;
      flex-direction: column;
    }

    #top-text {
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.7px;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    #top-text2 {
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 0.7px;
      color: #ffff99;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    #wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      flex-grow: 1;
      padding: 20px;
    }

    .container {
      position: relative;
      width: 90%;
      max-width: 600px;
      margin-top: 30px;
      overflow: hidden;
    }

    .form-container {
      position: relative;
      width: 100%;
      background-color: rgba(255, 255, 255, 0.32);
      border-radius: 10px;
      box-shadow: 0px 9px 40px 2px rgba(0, 0, 0, 0.5);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px 30px;
      transition: all 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }

    .form-header {
      width: 100%;
      text-align: center;
      margin-bottom: 25px;
      position: relative;
    }

    .form-header h2 {
      color: #1f3010;
      font-family: "Gidole", sans-serif;
      font-weight: 600;
      font-size: 26px;
      margin-bottom: 10px;
    }

    .form-header p {
      color: #333;
      font-size: 14px;
      line-height: 1.5;
      max-width: 90%;
      margin: 0 auto;
    }

    .reset-form {
      width: 100%;
      max-width: 382px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .input-field {
      display: block;
      width: 100%;
      height: 40px;
      margin: 12px auto;
      padding: 0 10px;
      font-size: 14px;
      border-radius: 5px;
      border: 1px solid rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      font-family: "Gidole", sans-serif;
      font-weight: 400;
      background-color: rgba(255, 255, 255, 0.8);
    }

    .input-field:focus {
      box-shadow: 0px 0px 5px 2px rgba(31, 48, 16, 0.3);
      transform: scale(1.009, 1.009);
      border-color: #1f3010;
      outline: none;
    }

    .password-field-container {
      position: relative;
      width: 100%;
    }

    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #555;
      font-size: 14px;
      background: none;
      border: none;
      outline: none;
      padding: 0 8px;
      font-family: 'Nunito', sans-serif;
    }

    .password-toggle:hover {
      color: #1f3010;
      text-decoration: underline;
    }

    .btn {
      width: 70%;
      max-width: 270px;
      margin: 20px auto 15px;
      height: 40px;
      font-weight: 600;
      font-size: 15px;
      color: white;
      background: linear-gradient(to right, #2c5e1a, #1d3d11);
      border: none;
      border-radius: 6px;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
    }

    .btn:active {
      transform: translateY(0);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .btn::after {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(to right,
          rgba(255, 255, 255, 0),
          rgba(255, 255, 255, 0.3),
          rgba(255, 255, 255, 0));
      transform: skewX(-25deg);
      transition: all 0.5s;
    }

    .btn:hover::after {
      left: 100%;
    }

    .back-link {
      color: #1f3010;
      font-family: "Exo 2", sans-serif;
      font-size: 14px;
      text-decoration: none;
      font-weight: 500;
      margin-top: 20px;
      cursor: pointer;
      text-align: center;
      transition: all 0.3s ease;
      display: inline-block;
      position: relative;
    }

    .back-link::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 50%;
      background: #1f3010;
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }

    .back-link:hover {
      color: #2c5e1a;
    }

    .back-link:hover::after {
      width: 100%;
    }

    .notification {
      display: none;
      text-align: center;
      padding: 15px;
      margin: 15px 0;
      border-radius: 5px;
      width: 100%;
      font-size: 14px;
      font-weight: 500;
    }

    .notification.success {
      background-color: #d4edda;
      color: #155724;
    }

    .notification.error {
      background-color: #f8d7da;
      color: #721c24;
    }

    .notification.info {
      background-color: #cce5ff;
      color: #004085;
    }

    .loading {
      display: none;
      text-align: center;
      margin: 15px 0;
    }

    .spinner {
      border: 3px solid rgba(0, 0, 0, 0.1);
      border-top: 3px solid #2c5e1a;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      animation: spin 1s linear infinite;
      margin: 0 auto 10px;
    }

    /* Password strength indicator */
    .password-strength {
      width: 100%;
      margin: 5px 0 15px;
      display: flex;
      flex-direction: column;
    }

    .strength-meter {
      height: 6px;
      width: 100%;
      background: #ddd;
      border-radius: 3px;
      margin-top: 5px;
      position: relative;
      overflow: hidden;
    }

    .strength-meter::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 0;
      border-radius: 3px;
      transition: width 0.3s ease, background-color 0.3s ease;
    }

    .password-strength.weak .strength-meter::before {
      width: 25%;
      background-color: #ff4d4d;
    }

    .password-strength.medium .strength-meter::before {
      width: 50%;
      background-color: #ffa64d;
    }

    .password-strength.strong .strength-meter::before {
      width: 75%;
      background-color: #4da6ff;
    }

    .password-strength.very-strong .strength-meter::before {
      width: 100%;
      background-color: #4caf50;
    }

    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none;
    }

    input[type="password"]::-webkit-contacts-auto-fill-button,
    input[type="password"]::-webkit-credentials-auto-fill-button {
      visibility: hidden;
      display: none !important;
      pointer-events: none;
      position: absolute;
      right: 0;
    }

    .strength-text {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
      text-align: right;
    }

    /* Password requirements */
    .password-requirements {
      width: 100%;
      margin-bottom: 15px;
      font-size: 12px;
      color: #666;
    }

    .requirement {
      margin: 4px 0;
      display: flex;
      align-items: center;
    }

    .requirement.valid {
      color: #2c5e1a;
      font-size: 14px;
      font-weight: 900;
    }

    .requirement.valid::before {
      content: "✓";
      margin-right: 5px;
      color: #2c5e1a;
    }

    .requirement.invalid {
      color: black;
      font-size: 14px;
    }

    .requirement.invalid::before {
      content: "○";
      margin-right: 5px;
      color: #999;
    }

    /* Particles animation */
    .particles-container {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: -1;
      overflow: hidden;
    }

    .particle {
      position: absolute;
      width: 5px;
      height: 5px;
      background-color: rgba(44, 94, 26, 0.6);
      border-radius: 50%;
      opacity: 0;
      animation: particle-animation 3s ease-in-out infinite;
    }

    @keyframes particle-animation {
      0% {
        transform: translateY(0) scale(0);
        opacity: 0;
      }

      50% {
        opacity: 0.8;
      }

      100% {
        transform: translateY(-100px) scale(1);
        opacity: 0;
      }
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    @media (max-width: 480px) {
      .form-header h2 {
        font-size: 20px;
      }

      .btn {
        width: 85%;
      }

      .input-field {
        width: 90%;
      }

      .form-container {
        padding: 30px 15px 20px;
      }
    }
  </style>
</head>

<body>
  <nav id="nav-top">
    <div class="nav-left">
      <img id="logo-nav" src="Logo.ico" alt="PLP Logo" />
      <div class="university-name">
        <h1 id="top-text">Pamantasan ng Lungsod ng Pasig</h1>
        <h1 id="top-text2">University of Pasig</h1>
      </div>
    </div>
  </nav>

  <div id="wrapper">
    <div class="container">
      <div class="particles-container" id="particles-container"></div>
      <div class="form-container">
        <div class="form-header">
          <h2>Reset Your Password</h2>
          <p>Please enter a new secure password for your account.</p>
        </div>

        <div class="loading" id="loading">
          <div class="spinner"></div>
          <p>Processing your request...</p>
        </div>

        <div class="notification success" id="success-message">
          Password has been reset successfully! Redirecting to login...
        </div>

        <div class="notification error" id="error-message">
          There was an error resetting your password. Please try again.
        </div>

        <div class="notification info" id="info-message">
          For security reasons, please reset your password after multiple failed login attempts.
        </div>

        <form id="reset-password-form" class="reset-form">
          <input type="hidden" id="reset-token" name="reset_token" value="<?php echo htmlspecialchars($token); ?>">


          <div class="password-field-container">
            <input class="input-field" id="new-password" name="new_password" type="password" placeholder="New Password"
              required autocomplete="new-password">
            <button type="button" class="password-toggle" data-target="new-password">Show</button>
          </div>

          <div class="password-strength">
            <div class="strength-meter"></div>
            <div class="strength-text">Password strength: None</div>
          </div>

          <div class="password-requirements">
            <div class="requirement invalid" id="req-length">At least 8 characters</div>
            <div class="requirement invalid" id="req-uppercase">At least 1 uppercase letter</div>
            <div class="requirement invalid" id="req-lowercase">At least 1 lowercase letter</div>
            <div class="requirement invalid" id="req-number">At least 1 number</div>
            <div class="requirement invalid" id="req-special">At least 1 special character</div>
          </div>

          <div class="password-field-container">
            <input class="input-field" id="confirm-password" name="confirm_password" type="password"
              placeholder="Confirm New Password" required autocomplete="new-password">
            <button type="button" class="password-toggle" data-target="confirm-password">Show</button>
          </div>

          <button type="submit" class="btn">Reset Password</button>
        </form>

        <a href="login.php" class="back-link">Back to Login</a>
      </div>
    </div>
  </div>

  <script>
    // Create floating particles effect
    function createParticles() {
      const particlesContainer = document.getElementById('particles-container');
      const particleCount = 20;

      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');

        // Random positioning
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const size = Math.random() * 5 + 2;
        const delay = Math.random() * 2;
        const duration = Math.random() * 2 + 2;

        particle.style.left = `${posX}%`;
        particle.style.bottom = `${posY}%`;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;

        particlesContainer.appendChild(particle);
      }
    }

    // Create particles on page load
    document.addEventListener('DOMContentLoaded', function () {
      createParticles();

      // Show info message by default
      document.getElementById('info-message').style.display = 'block';

      // Check for reset token in URL
      const urlParams = new URLSearchParams(window.location.search);
      const resetToken = urlParams.get('token');
      if (resetToken) {
        document.getElementById('reset-token').value = resetToken;
      }
    });

    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
      button.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const passwordField = document.getElementById(targetId);

        if (passwordField.type === 'password') {
          passwordField.type = 'text';
          this.textContent = 'Hide';
        } else {
          passwordField.type = 'password';
          this.textContent = 'Show';
        }
      });
    });

    // Password strength checker
    const newPassword = document.getElementById('new-password');
    const strengthIndicator = document.querySelector('.password-strength');
    const strengthText = document.querySelector('.strength-text');

    // Password requirement validation
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    newPassword.addEventListener('input', function () {
      const password = this.value;

      // Check requirements
      const hasLength = password.length >= 8;
      const hasUppercase = /[A-Z]/.test(password);
      const hasLowercase = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

      // Update requirement indicators
      updateRequirement(reqLength, hasLength);
      updateRequirement(reqUppercase, hasUppercase);
      updateRequirement(reqLowercase, hasLowercase);
      updateRequirement(reqNumber, hasNumber);
      updateRequirement(reqSpecial, hasSpecial);

      // Calculate strength
      let strength = 0;
      if (hasLength) strength += 1;
      if (hasUppercase) strength += 1;
      if (hasLowercase) strength += 1;
      if (hasNumber) strength += 1;
      if (hasSpecial) strength += 1;

      // Update strength indicator
      strengthIndicator.className = 'password-strength';

      if (password.length === 0) {
        strengthText.textContent = 'Password strength: None';
      } else if (strength < 2) {
        strengthIndicator.classList.add('weak');
        strengthText.textContent = 'Password strength: Weak';
      } else if (strength < 4) {
        strengthIndicator.classList.add('medium');
        strengthText.textContent = 'Password strength: Medium';
      } else if (strength < 5) {
        strengthIndicator.classList.add('strong');
        strengthText.textContent = 'Password strength: Strong';
      } else {
        strengthIndicator.classList.add('very-strong');
        strengthText.textContent = 'Password strength: Very Strong';
      }
    });

    function updateRequirement(element, isValid) {
      if (isValid) {
        element.classList.remove('invalid');
        element.classList.add('valid');
      } else {
        element.classList.remove('valid');
        element.classList.add('invalid');
      }
    }

    // Form submission
    // Form submission
    document.getElementById('reset-password-form').addEventListener('submit', function (e) {
      e.preventDefault();

      const newPassword = document.getElementById('new-password').value;
      const confirmPassword = document.getElementById('confirm-password').value;
      const resetToken = document.getElementById('reset-token').value;
      const errorMessage = document.getElementById('error-message');
      const successMessage = document.getElementById('success-message');
      const infoMessage = document.getElementById('info-message');
      const loadingElement = document.getElementById('loading'); // Define loading element

      // Clear previous messages
      errorMessage.style.display = 'none';
      successMessage.style.display = 'none';
      infoMessage.style.display = 'none';

      // Basic validation - passwords match
      if (newPassword !== confirmPassword) {
        errorMessage.textContent = 'New passwords do not match.';
        errorMessage.style.display = 'block';
        return;
      }

      // Check all requirements are met
      const hasLength = newPassword.length >= 8;
      const hasUppercase = /[A-Z]/.test(newPassword);
      const hasLowercase = /[a-z]/.test(newPassword);
      const hasNumber = /[0-9]/.test(newPassword);
      const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPassword);

      if (!(hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial)) {
        errorMessage.textContent = 'Your new password does not meet all requirements.';
        errorMessage.style.display = 'block';
        return;
      }

      // Show loading spinner
      loadingElement.style.display = 'block';

      // Prepare form data
      const formData = new FormData(this);

      console.log("Token being sent:", resetToken);

      // Send request to server
      fetch('../Backend/Failed attempts/reset_password.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          // First check if the response is OK (status 200-299)
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          // Try to parse JSON
          return response.text().then(text => {
            try {
              return text ? JSON.parse(text) : {}
            } catch (err) {
              console.error("Failed to parse JSON:", text);
              throw new Error("Invalid JSON response");
            }
          });
        })
        .then(data => {
          // Hide loading spinner
          loadingElement.style.display = 'none';

          if (data && data.status === 'success') {
            successMessage.style.display = 'block';
            document.getElementById('reset-password-form').reset();

            // Reset strength indicator
            strengthIndicator.className = 'password-strength';
            strengthText.textContent = 'Password strength: None';

            // Reset requirements
            document.querySelectorAll('.requirement').forEach(req => {
              req.classList.remove('valid');
              req.classList.add('invalid');
            });

            // Redirect to login after 3 seconds
            setTimeout(() => {
              window.location.href = data.redirect || 'login.php';
            }, 3000);
          } else {
            const errorMsg = data && data.message
              ? data.message
              : 'Failed to reset password. Please try again.';
            errorMessage.textContent = errorMsg;
            errorMessage.style.display = 'block';
          }
        })
        .catch(error => {
          loadingElement.style.display = 'none';
          errorMessage.textContent = 'An error occurred. Please try again. Error: ' + error.message;
          errorMessage.style.display = 'block';
          console.error('Error:', error);
        });
    });
  </script>
</body>

</html>