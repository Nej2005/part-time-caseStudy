<?php

include '../Backend/db_connect.php';

session_start();
$error = "";
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pamantasan ng Lungsod ng Pasig - Login</title>
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

        #logo {
            width: 155px;
            max-width: 30vw;
            margin-bottom: -38px;
            box-shadow: 0px 0px 25px 7px rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            z-index: 100;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .container {
            position: relative;
            width: 90%;
            max-width: 600px;
            height: 450px;
            margin-top: 30px;
            overflow: hidden;
        }

        .form-container {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .login-container,
        .forgot-container,
        .verification-container {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.218);
            border-radius: 10px;
            box-shadow: 0px 9px 40px 2px rgba(0, 0, 0, 0.5);
            display: flex;
            max-height: 350px;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 20px;
        }

        .login-container.has-message,
        .forgot-container.has-message,
        .verification-container.has-message {
            max-height: 430px;
        }

        .login-container {
            left: 0;
            opacity: 1;
            transform: translateX(0) rotateZ(0);
            z-index: 3;
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

        .forgot-container {
            right: -100%;
            opacity: 0;
            transform: translateX(0) rotateZ(-5deg);
            z-index: 2;
            max-height: 350px;
        }

        .forgot-container.has-message {
            max-height: 420px;
        }

        .forgot-container #error-message {
            display: none;
            width: 100%;
            max-width: 382px;
            margin: 10px auto;
        }

        .verification-container {
            right: -200%;
            opacity: 0;
            transform: translateX(0) rotateZ(-5deg);
            z-index: 1;
        }

        .flip .login-container {
            opacity: 0;
            transform: translateX(-110%) rotateZ(5deg);
            z-index: 1;
        }

        .flip .forgot-container {
            opacity: 1;
            transform: translateX(-100%) rotateZ(0);
            z-index: 3;
        }

        .verify .forgot-container {
            opacity: 0;
            transform: translateX(-210%) rotateZ(5deg);
            z-index: 1;
        }

        .verify .verification-container {
            opacity: 1;
            transform: translateX(-200%) rotateZ(0);
            z-index: 3;
        }

        .form-header {
            width: 100%;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }

        .form-header h2 {
            color: #1d2715;
            font-family: "Gidole", sans-serif;
            font-weight: 600;
            font-size: 26px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #1e1b1b;
            font-size: 14px;
            line-height: 1.5;
            max-width: 90%;
            margin: 0 auto;
            font-weight: 600;
        }

        #title {
            font-size: 26px;
            color: #1d2715;
            font-weight: 600;
            margin-bottom: 20px;
            font-family: "Gidole", sans-serif;
        }

        #con-inp {
            width: 100%;
            max-width: 382px;
            margin-top: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .forgot-form,
        .verification-form {
            width: 100%;
            max-width: 382px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #back-to-forgot {
            margin-top: 7px;
        }

        .input-field {
            display: block;
            width: 100%;
            height: 40px;
            margin: 10px auto;
            padding: 0 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-family: "Gidole", sans-serif;
            font-weight: 400;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .verification-code {
            display: flex;
            width: 100%;
            justify-content: center;
            gap: 8px;
            margin: 15px auto;
        }

        .verification-code input {
            width: 40px;
            height: 45px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            border-radius: 5px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .verification-code input:focus {
            box-shadow: 0px 0px 5px 2px rgba(31, 48, 16, 0.3);
            transform: scale(1.05);
            border-color: #1f3010;
            outline: none;
        }

        .input-field:focus {
            box-shadow: 0px 0px 5px 2px rgba(31, 48, 16, 0.3);
            transform: scale(1.009, 1.009);
            border-color: #1f3010;
            outline: none;
        }

        .btn {
            width: 70%;
            max-width: 270px;
            margin: 20px auto 5px;
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

        .toggle-form {
            color: rgb(211, 222, 210);
            font-family: "Exo 2", sans-serif;
            font-size: 14px;
            text-decoration: none;
            font-weight: 550;
            margin-top: 10px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
        }

        .toggle-form::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .toggle-form:hover {
            color: #ffff99;
        }

        .toggle-form:hover::after {
            width: 100%;
        }

        #error-message,
        .notification {
            display: none;
            text-align: center;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.1s ease;
        }

        #error-message.warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        #error-message.danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        #error-message {
            background-color: #f8d7da;
            color: #721c24;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
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

        .timer {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            font-size: 13px;
            color: #1d3d11;
        }

        .timer-icon {
            margin-right: 5px;
            width: 14px;
            height: 14px;
        }

        #resend-code {
            color: #1d3d11;
            text-decoration: underline;
            cursor: pointer;
            margin-left: 5px;
            font-weight: 600;
        }

        #resend-code:hover {
            color: #2c5e1a;
        }

        .resend-disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
        }

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

        .shake {
            animation: shake 0.5s;
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

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
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

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        @media (max-width: 480px) {

            #title,
            .form-header h2 {
                font-size: 20px;
            }

            .btn {
                width: 85%;
            }

            .input-field {
                width: 90%;
            }

            .container {
                height: 480px;
            }

            #logo {
                width: 120px;
                margin-bottom: -30px;
            }

            .verification-code input {
                width: 35px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <img alt="Logo" id="logo" src="Logo.ico" />
        <div class="container">
            <div class="particles-container" id="particles-container"></div>
            <div class="form-container" id="form-container">
                <!-- Login Container -->
                <div class="login-container">
                    <h2 id="title">PAMANTASAN NG LUNGSOD NG PASIG</h2>

                    <div id="error-message" class="notification"></div>

                    <form id="con-inp" action="../Backend/loginSubmit.php" method="POST">
                        <input class="input-field" id="employee_id" name="userid" type="text"
                            placeholder="Employee ID or Email" maxlength="60" required>
                        <div class="password-field-container">
                            <input class="input-field" id="password" name="password" type="password"
                                placeholder="Password" maxlength="20" required>
                            <button type="button" class="password-toggle">Show</button>
                        </div>
                        <button id="login-btn" class="btn" type="submit">Login</button>
                    </form>

                    <a class="toggle-form" id="forgot-link">Forgot Password?</a>
                </div>

                <!-- Forgot Password Container -->
                <div class="forgot-container">
                    <div class="form-header">
                        <h2>Reset Your Password</h2>
                        <p>Enter your email address and we will send you a verification code.</p>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Sending verification code...</p>
                    </div>

                    <div class="notification success" id="success-message">
                        Verification code has been sent to your email.<br>Please check your inbox.
                    </div>

                    <form id="forgot-password-form" class="forgot-form">
                        <input class="input-field" id="email" name="email" type="email"
                            placeholder="Enter your email" required autocomplete="email">

                        <button type="submit" class="btn" id="send-code-btn">Send Code</button>
                    </form>

                    <a class="toggle-form" id="back-to-login">Back to Login</a>
                </div>

                <!-- Verification Code Container -->
                <div class="verification-container">
                    <div class="form-header">
                        <h2>Enter Verification Code</h2>
                        <p>Please enter the 6-digit code sent to your email</p>
                    </div>

                    <div class="loading" id="verification-loading">
                        <div class="spinner"></div>
                        <p>Verifying code...</p>
                    </div>

                    <div class="notification success" id="verification-success">
                        Code verified successfully!<br>You will be redirected to reset your password.
                    </div>

                    <form id="verification-form" class="verification-form">
                        <div class="verification-code">
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                            <input type="text" maxlength="1" pattern="[0-9]" required>
                        </div>

                        <div class="timer">
                            <svg class="timer-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span id="timer-count"></span>
                            <span id="resend-code" class="resend-disabled">Resend Code</span>
                        </div>

                        <button type="submit" class="btn">Verify Code</button>
                    </form>

                    <a class="toggle-form" id="back-to-forgot">Back</a>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            setupVerificationInputs();
        });

        document.querySelector('.password-toggle').addEventListener('click', function() {
            const passwordField = document.getElementById('password');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.textContent = 'Hide';
            } else {
                passwordField.type = 'password';
                this.textContent = 'Show';
            }
        });

        // Toggle between login and forgot password forms
        document.getElementById('forgot-link').addEventListener('click', function() {
            document.getElementById('form-container').classList.add('flip');
            document.getElementById('form-container').classList.remove('verify');
            document.getElementById('logo').style.transform = 'scale(1) rotate(360deg)';
            createTransitionParticles();
        });

        document.getElementById('back-to-login').addEventListener('click', function() {
            document.getElementById('form-container').classList.remove('flip');
            document.getElementById('form-container').classList.remove('verify');
            document.getElementById('logo').style.transform = 'scale(1) rotate(0deg)';
            createTransitionParticles();
            resetForms();
        });

        // Toggle between forgot password and verification forms
        document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const loadingElement = document.getElementById('loading');
            const successElement = document.getElementById('success-message');

            // Get or create error message element
            let errorElement = document.querySelector('.forgot-container #error-message');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = 'error-message';
                errorElement.className = 'notification';
                errorElement.style.backgroundColor = '#f8d7da';
                errorElement.style.color = '#721c24';
                document.querySelector('.forgot-form').prepend(errorElement);
            }

            // Clear previous messages and show loading
            errorElement.style.display = 'none';
            errorElement.textContent = '';
            successElement.style.display = 'none';
            loadingElement.style.display = 'block';
            document.querySelector('.forgot-container').classList.add('has-message');

            fetch('../Backend/forgot_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loadingElement.style.display = 'none';

                    if (data.status === 'success') {
                        successElement.style.display = 'block';
                        document.querySelector('.forgot-container').classList.add('has-message');
                        startCountdown(180);

                        setTimeout(() => {
                            document.getElementById('form-container').classList.add('verify');
                        }, 2000);
                    } else {
                        errorElement.textContent = data.message;
                        errorElement.style.display = 'block';
                        document.querySelector('.forgot-container').classList.add('has-message');
                        // Shake animation for error
                        errorElement.style.animation = 'shake 0.5s';
                        setTimeout(() => {
                            errorElement.style.animation = '';
                        }, 500);
                    }
                })
                .catch(error => {
                    loadingElement.style.display = 'none';
                    errorElement.textContent = 'An error occurred. Please try again.';
                    errorElement.style.display = 'block';
                    console.error('Error:', error);
                });
        })

        function resetForgotForm() {
            const loadingElement = document.getElementById('loading');
            const successElement = document.getElementById('success-message');
            const errorElement = document.querySelector('.forgot-container #error-message');

            // Hide all messages and loading
            loadingElement.style.display = 'none';
            successElement.style.display = 'none';
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }

            document.querySelector('.forgot-container').classList.remove('has-message');

            // Reset the timer
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            document.getElementById('resend-code').classList.add('resend-disabled');
            document.getElementById('timer-count').textContent = '03:00';
        }

        document.getElementById('back-to-forgot').addEventListener('click', function() {
            document.getElementById('form-container').classList.remove('verify');
            createTransitionParticles();
            resetForgotForm();

            // Also reset the verification form
            const verificationInputs = document.querySelectorAll('.verification-code input');
            verificationInputs.forEach(input => input.value = '');
            document.getElementById('verification-loading').style.display = 'none';
            document.getElementById('verification-success').style.display = 'none';
        });

        // Handle verification form submission
        document.getElementById('verification-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const inputs = document.querySelectorAll('.verification-code input');
            let code = Array.from(inputs).map(input => input.value).join('');

            if (code.length !== 6) {
                alert('Please enter a complete 6-digit code');
                return;
            }

            const loadingElement = document.getElementById('verification-loading');
            const successElement = document.getElementById('verification-success');

            // Get or create error message element
            let errorElement = document.querySelector('.verification-container #error-message');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = 'error-message';
                errorElement.className = 'notification';
                errorElement.style.backgroundColor = '#f8d7da';
                errorElement.style.color = '#721c24';
                // Insert it right after the form header
                document.querySelector('.verification-container .form-header').after(errorElement);
            }

            // Clear previous messages and show loading
            errorElement.style.display = 'none';
            errorElement.textContent = '';
            successElement.style.display = 'none';
            loadingElement.style.display = 'block';

            fetch('../Backend/verify_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `code=${encodeURIComponent(code)}`
                })
                .then(response => response.json())
                .then(data => {
                    loadingElement.style.display = 'none';

                    if (data.status === 'success') {
                        successElement.style.display = 'block';
                        document.querySelector('.verification-container').classList.add('has-message');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    } else {
                        errorElement.textContent = data.message;
                        errorElement.style.display = 'block';
                        document.querySelector('.verification-container').classList.add('has-message');
                        // Shake animation for error
                        errorElement.style.animation = 'shake 0.5s';
                        setTimeout(() => {
                            errorElement.style.animation = '';
                        }, 500);
                    }
                })
                .catch(error => {
                    loadingElement.style.display = 'none';
                    errorElement.textContent = 'Verification failed. Please try again.';
                    errorElement.style.display = 'block';
                    console.error('Error:', error);
                });
        });

        // Handle resend code
        document.getElementById('resend-code').addEventListener('click', function() {
            if (this.classList.contains('resend-disabled')) {
                return;
            }

            this.classList.add('resend-disabled');

            const loadingElement = document.getElementById('loading');
            const successElement = document.getElementById('success-message');
            const errorElement = document.querySelector('.forgot-container #error-message');

            // Show loading and hide other messages
            loadingElement.style.display = 'block';
            successElement.style.display = 'none';
            if (errorElement) errorElement.style.display = 'none';

            // Get the email from the form
            const email = document.getElementById('email').value;

            fetch('../Backend/forgot_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loadingElement.style.display = 'none';

                    if (data.status === 'success') {
                        successElement.style.display = 'block';
                        startCountdown(180); // Reset timer to 3 minutes
                    } else {
                        // Show error message
                        if (!errorElement) {
                            errorElement = document.createElement('div');
                            errorElement.id = 'error-message';
                            errorElement.className = 'notification';
                            errorElement.style.backgroundColor = '#f8d7da';
                            errorElement.style.color = '#721c24';
                            document.querySelector('.forgot-form').prepend(errorElement);
                        }
                        errorElement.textContent = data.message;
                        errorElement.style.display = 'block';
                    }
                })
                .catch(error => {
                    loadingElement.style.display = 'none';
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.id = 'error-message';
                        errorElement.className = 'notification';
                        errorElement.style.backgroundColor = '#f8d7da';
                        errorElement.style.color = '#721c24';
                        document.querySelector('.forgot-form').prepend(errorElement);
                    }
                    errorElement.textContent = 'Failed to resend code. Please try again.';
                    errorElement.style.display = 'block';
                    console.error('Error:', error);
                });
        });

        function setupVerificationInputs() {
            const inputs = document.querySelectorAll('.verification-code input');

            inputs.forEach((input, index) => {
                // Move to next input after entering a digit
                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            this.blur();
                        }
                    }
                });

                // Handle backspace key
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                // Only allow numbers
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
        }

        let countdownInterval;

        function startCountdown(seconds = 180) {
            const timerElement = document.getElementById('timer-count');
            const resendButton = document.getElementById('resend-code');

            // Clear any existing timer
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            resendButton.classList.add('resend-disabled');
            updateTimerDisplay(seconds);

            countdownInterval = setInterval(() => {
                seconds--;
                updateTimerDisplay(seconds);

                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    resendButton.classList.remove('resend-disabled');
                }
            }, 1000);
        }

        function updateTimerDisplay(seconds) {
            const timerElement = document.getElementById('timer-count');
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            timerElement.textContent = `${mins}:${secs}`;
        }

        function createTransitionParticles() {
            const particlesContainer = document.getElementById('particles-container');
            for (let i = 0; i < 10; i++) {
                setTimeout(() => {
                    const particle = document.createElement('div');
                    particle.classList.add('particle');

                    const posX = Math.random() * 100;
                    const posY = 20 + Math.random() * 60;

                    particle.style.left = `${posX}%`;
                    particle.style.bottom = `${posY}%`;

                    particlesContainer.appendChild(particle);

                    // Remove extra particles after animation
                    setTimeout(() => {
                        particle.remove();
                    }, 3000);
                }, i * 100);
            }
        }

        function resetForms() {
            document.getElementById('con-inp').reset();
            document.getElementById('error-message').style.display = 'none';
            document.querySelector('.login-container').classList.remove('has-message');

            // Reset forgot password form
            document.getElementById('forgot-password-form').reset();
            document.getElementById('loading').style.display = 'none';
            document.getElementById('success-message').style.display = 'none';
            document.querySelector('.forgot-container').classList.remove('has-message');

            document.getElementById('forgot-password-form').reset();
            resetForgotForm();
        }

        // Handle login form submission
        document.getElementById('con-inp').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const errorDiv = document.getElementById('error-message');
            const loginBtn = document.getElementById('login-btn');


            fetch('../Backend/loginSubmit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.href = data.redirect;
                    } else {
                        errorDiv.textContent = data.message;
                        errorDiv.style.display = 'block';
                        document.querySelector('.login-container').classList.add('has-message');

                        // Add visual feedback for multiple attempts
                        if (data.attempts >= 2) {
                            errorDiv.style.backgroundColor = '#fff3cd';
                            errorDiv.style.color = '#856404';
                            if (data.attempts === 2) {
                                errorDiv.textContent += " (1 attempt remaining)";
                            }
                        }

                        // Add shake effect
                        errorDiv.classList.add('shake');
                        setTimeout(() => {
                            errorDiv.classList.remove('shake');
                        }, 500);
                    }
                })
                .catch(error => {
                    errorDiv.textContent = "An error occurred.";
                    errorDiv.style.display = 'block';
                    document.querySelector('.login-container').classList.add('has-message');

                    // Add shake effect
                    errorDiv.classList.add('shake');
                    setTimeout(() => {
                        errorDiv.classList.remove('shake');
                    }, 500);
                    console.error('Error:', error);
                });
        });
    </script>
</body>

</html>