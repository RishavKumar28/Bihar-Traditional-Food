<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (!preg_match('/@gmail\.com$/', $email)) {
        $error = 'Please use your Gmail address (example: yourname@gmail.com)';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $result = $auth->register($name, $email, $password, $phone, $address);

        if ($result['success']) {
            $success = $result['message'];
            header('refresh:2;url=login.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bihar Traditional Food</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* Two Column Layout */
        .container {
            display: flex;
            width: 100%;
            height: 100vh;
            max-width: 1400px;
            /* Limit overall width */
            margin: 0 auto;
            /* Center container */
        }

        /* Left Content Column - 40% (SMALLER) */
        .brand-logo {
            position: absolute;
            top: 30px;
            left: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            /* Smaller font */
            font-weight: 700;
            color: #1e293b;
            z-index: 2;
        }

        .brand-logo i {
            color: #3b82f6;
            font-size: 22px;
        }

        .welcome-content {
            max-width: 380px;
            /* Smaller max width */
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .welcome-content h1 {
            font-size: 32px;
            /* Smaller font */
            color: #1e293b;
            margin-bottom: 15px;
            line-height: 1.2;
            font-weight: 800;
        }

        .welcome-content h1 span {
            color: #3b82f6;
        }

        .welcome-content p {
            color: #64748b;
            font-size: 16px;
            /* Smaller font */
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .features-list {
            list-style: none;
            margin-top: 30px;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            color: #475569;
            font-size: 14px;
            /* Smaller font */
        }

        .features-list li i {
            width: 26px;
            height: 26px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 12px;
        }

        /* Right Form Column - 60% (SMALLER) */
        .right-column {
            flex: 1.5;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        /* SMALLER Form Container */
        .auth-form {
            background: white;
            border-radius: 12px;
            /* Smaller radius */
            width: 100%;
            max-width: 380px;
            /* Much smaller width */
            padding: 25px;
            /* Smaller padding */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            /* Smaller shadow */
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
        }

        /* Scrollbar Styling */
        .auth-form::-webkit-scrollbar {
            width: 5px;
        }

        .auth-form::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
            margin: 4px 0;
        }

        .auth-form::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }

        .auth-form::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h2 {
            font-size: 22px;
            /* Smaller */
            color: #1e293b;
            margin-bottom: 5px;
        }

        .form-header p {
            color: #64748b;
            font-size: 13px;
            /* Smaller */
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            /* Smaller gap */
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #475569;
            font-size: 13px;
            /* Smaller */
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-group label i {
            width: 12px;
            color: #64748b;
            font-size: 12px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            /* Smaller padding */
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            /* Smaller radius */
            font-size: 13px;
            /* Smaller */
            transition: all 0.2s;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 70px;
            /* Smaller */
            font-family: inherit;
            font-size: 13px;
        }

        /* Alerts - Smaller */
        .alert {
            padding: 10px 12px;
            /* Smaller */
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease;
            grid-column: span 2;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 3px solid #10b981;
        }

        /* Password Strength - Smaller */
        .password-strength {
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .strength-meter {
            flex: 1;
            height: 3px;
            /* Thinner */
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: #ef4444;
            transition: all 0.3s ease;
        }

        .strength-text {
            font-size: 10px;
            /* Smaller */
            color: #64748b;
            min-width: 45px;
        }

        /* Buttons - Smaller */
        .form-actions {
            grid-column: span 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            /* Smaller gap */
            margin-top: 20px;
        }

        .btn {
            padding: 11px;
            /* Smaller */
            border-radius: 6px;
            font-size: 13px;
            /* Smaller */
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background: white;
            color: #64748b;
            border-color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        /* Footer - Smaller */
        .auth-footer {
            grid-column: span 2;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer p {
            color: #64748b;
            font-size: 12px;
        }

        .auth-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            font-size: 12px;
        }

        .auth-footer a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        /* Required Field */
        .required::after {
            content: ' *';
            color: #ef4444;
            font-size: 12px;
        }

        /* Password Toggle - Smaller */
        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 32px;
            /* Adjusted for smaller input */
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 3px;
            font-size: 14px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #3b82f6;
        }

        /* Character Counter - Smaller */
        .char-counter {
            text-align: right;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* Validation Message - Smaller */
        .validation-message {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .validation-message.valid {
            color: #10b981;
        }

        .validation-message.invalid {
            color: #ef4444;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
                max-width: 100%;
            }

            .left-column {
                padding: 30px;
                min-height: 250px;
                max-width: 100%;
            }

            .right-column {
                padding: 20px;
                flex: 1;
            }

            .auth-form {
                max-width: 400px;
                max-height: none;
                margin-bottom: 20px;
            }

            .brand-logo {
                top: 20px;
                left: 30px;
            }
        }

        @media (max-width: 768px) {
            .left-column {
                padding: 25px 20px;
            }

            .brand-logo {
                left: 20px;
                top: 20px;
                font-size: 18px;
            }

            .brand-logo i {
                font-size: 20px;
            }

            .welcome-content h1 {
                font-size: 26px;
            }

            .welcome-content p {
                font-size: 14px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .form-actions {
                grid-column: span 1;
                grid-template-columns: 1fr;
            }

            .alert {
                grid-column: span 1;
                font-size: 12px;
                padding: 8px 10px;
            }

            .auth-footer {
                grid-column: span 1;
            }

            .features-list li {
                font-size: 13px;
                margin-bottom: 12px;
            }

            .auth-form {
                padding: 20px;
                max-width: 350px;
            }
        }

        @media (max-width: 480px) {
            .auth-form {
                padding: 18px 15px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
                max-width: 320px;
            }

            .right-column {
                padding: 15px;
            }

            .left-column {
                padding: 20px 15px;
            }

            .welcome-content h1 {
                font-size: 22px;
            }

            .btn {
                padding: 10px;
                font-size: 12px;
            }

            .form-header h2 {
                font-size: 20px;
            }

            .form-header p {
                font-size: 12px;
            }

            .brand-logo {
                font-size: 16px;
            }

            .features-list li {
                font-size: 12px;
            }
        }

        @media (max-width: 360px) {
            .auth-form {
                max-width: 300px;
                padding: 15px;
            }

            .form-group input,
            .form-group textarea {
                padding: 9px 10px;
                font-size: 12px;
            }

            .btn {
                padding: 9px;
                font-size: 11px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-form {
            animation: fadeIn 0.5s ease;
        }

        .left-column {
            animation: slideInLeft 0.5s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-15px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Compact view for very small screens */
        @media (max-height: 700px) {
            .auth-form {
                max-height: 80vh;
                padding: 20px;
            }

            .form-grid {
                gap: 10px;
            }

            .form-actions {
                margin-top: 15px;
            }

            .auth-footer {
                margin-top: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="right-column">
            <div class="auth-form">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Join our food community in seconds</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <div class="form-grid">
                        <!-- Part 1: Basic Information -->
                        <div class="form-group">
                            <label for="name" class="required">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="name" name="name" required
                                placeholder="Enter your full name"
                                onkeyup="validateNameField()"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            <div class="validation-message" id="name-validation">
                                <i class="fas fa-info-circle"></i> Enter your name (letters only)
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Mobile Number
                            </label>
                            <input type="tel" id="phone" name="phone"
                                placeholder="Enter 10-digit mobile number"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                onkeyup="validatePhoneField()"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <div class="validation-message" id="phone-validation">
                                <i class="fas fa-info-circle"></i> Enter 10-digit mobile number
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="email" class="required">
                                <i class="fas fa-envelope"></i> Email Address (Gmail)
                            </label>
                            <input type="email" id="email" name="email" required
                                placeholder="yourname@gmail.com"
                                pattern="[a-zA-Z0-9._%+-]+@gmail\.com"
                                title="Please enter a valid Gmail address"
                                onkeyup="validateEmailField()"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="validation-message" id="email-validation">
                                <i class="fas fa-info-circle"></i> Use your Gmail address
                            </div>
                        </div>

                        <!-- Part 2: Address -->
                        <div class="form-group full-width">
                            <label for="address">
                                <i class="fas fa-map-marker-alt"></i> Delivery Address
                            </label>
                            <textarea id="address" name="address" rows="3"
                                placeholder="Enter your complete delivery address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            <div class="char-counter">
                                <span id="address-counter">0</span> characters
                            </div>
                        </div>

                        <!-- Part 3: Passwords -->
                        <div class="form-group password-toggle">
                            <label for="password" class="required">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" id="password" name="password" required
                                placeholder="Create a strong password"
                                onkeyup="checkPasswordStrength(this.value)">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="password-strength" id="password-strength">
                                <div class="strength-text" id="strength-text">Very weak</div>
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strength-fill"></div>
                                </div>
                            </div>
                            <div class="validation-message" id="password-length">
                                <i class="fas fa-info-circle"></i> Minimum 6 characters
                            </div>
                        </div>

                        <div class="form-group password-toggle">
                            <label for="confirm_password" class="required">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                placeholder="Re-enter your password"
                                onkeyup="checkPasswordMatch()">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="validation-message" id="password-match">
                                <i class="fas fa-info-circle"></i> Passwords must match
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>

                            <a href="login.php" class="btn btn-secondary">
                                <i class="fas fa-sign-in-alt"></i> Back to Login
                            </a>
                        </div>

                        <!-- Footer -->
                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Validate name field
        function validateNameField() {
            const nameInput = document.getElementById('name');
            const nameValidation = document.getElementById('name-validation');
            const name = nameInput.value.trim();

            if (name === '') {
                nameValidation.className = 'validation-message';
                nameValidation.innerHTML = '<i class="fas fa-info-circle"></i> Enter your name (letters only)';
                return false;
            }

            // Check if name contains only digits (numeric only)
            if (/^\d+$/.test(name)) {
                nameValidation.className = 'validation-message invalid';
                nameValidation.innerHTML = '<i class="fas fa-exclamation-circle"></i> Name must contain letters, not just numbers';
                return false;
            }

            // Check if name contains at least one letter
            if (!/[a-zA-Z]/.test(name)) {
                nameValidation.className = 'validation-message invalid';
                nameValidation.innerHTML = '<i class="fas fa-exclamation-circle"></i> Name must contain at least one letter';
                return false;
            }

            nameValidation.className = 'validation-message valid';
            nameValidation.innerHTML = '<i class="fas fa-check-circle"></i> Name looks good';
            return true;
        }

        // Validate email field
        function validateEmailField() {
            const emailInput = document.getElementById('email');
            const emailValidation = document.getElementById('email-validation');
            const email = emailInput.value.trim();

            if (email === '') {
                emailValidation.className = 'validation-message';
                emailValidation.innerHTML = '<i class="fas fa-info-circle"></i> Use your Gmail address';
                return false;
            }

            // Check if email is valid format
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email)) {
                emailValidation.className = 'validation-message invalid';
                emailValidation.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid email address';
                return false;
            }

            // Check if email is Gmail
            if (!email.endsWith('@gmail.com')) {
                emailValidation.className = 'validation-message invalid';
                emailValidation.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please use your Gmail address (example: yourname@gmail.com)';
                return false;
            }

            emailValidation.className = 'validation-message valid';
            emailValidation.innerHTML = '<i class="fas fa-check-circle"></i> Gmail address looks good';
            return true;
        }

        // Validate phone field
        function validatePhoneField() {
            const phoneInput = document.getElementById('phone');
            const phoneValidation = document.getElementById('phone-validation');
            const phone = phoneInput.value.trim();

            if (phone === '') {
                phoneValidation.className = 'validation-message';
                phoneValidation.innerHTML = '<i class="fas fa-info-circle"></i> Enter 10-digit mobile number';
                return false;
            }

            // Check if phone contains exactly 10 digits
            if (!/^\d{10}$/.test(phone)) {
                phoneValidation.className = 'validation-message invalid';
                phoneValidation.innerHTML = '<i class="fas fa-exclamation-circle"></i> Mobile number must be exactly 10 digits';
                return false;
            }

            phoneValidation.className = 'validation-message valid';
            phoneValidation.innerHTML = '<i class="fas fa-check-circle"></i> Mobile number is valid';
            return true;
        }

        // Toggle password visibility
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.parentElement.querySelector('.toggle-password i');

            if (field.type === 'password') {
                field.type = 'text';
                button.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                button.className = 'fas fa-eye';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            const passwordLength = document.getElementById('password-length');

            let strength = 0;
            let color = '#ef4444'; // Red
            let text = 'Very weak';

            // Check length
            if (password.length >= 8) strength += 25;

            // Check for uppercase
            if (/[A-Z]/.test(password)) strength += 25;

            // Check for lowercase
            if (/[a-z]/.test(password)) strength += 25;

            // Check for numbers and special characters
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^A-Za-z0-9]/.test(password)) strength += 10;

            // Set color and text based on strength
            if (strength > 75) {
                color = '#10b981'; // Green
                text = 'Strong';
                passwordLength.className = 'validation-message valid';
                passwordLength.innerHTML = '<i class="fas fa-check-circle"></i> Password strength is good';
            } else if (strength > 50) {
                color = '#f59e0b'; // Yellow
                text = 'Good';
                passwordLength.className = 'validation-message valid';
                passwordLength.innerHTML = '<i class="fas fa-check-circle"></i> Password strength is good';
            } else if (strength > 25) {
                color = '#f97316'; // Orange
                text = 'Fair';
                passwordLength.className = 'validation-message';
                passwordLength.innerHTML = '<i class="fas fa-info-circle"></i> Add more characters';
            } else {
                color = '#ef4444'; // Red
                text = 'Weak';
                passwordLength.className = 'validation-message invalid';
                passwordLength.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password too weak';
            }

            // Minimum length validation
            if (password.length < 6) {
                strength = 0;
                color = '#ef4444';
                text = 'Too short';
                passwordLength.className = 'validation-message invalid';
                passwordLength.innerHTML = '<i class="fas fa-exclamation-circle"></i> Minimum 6 characters required';
            }

            strengthFill.style.width = strength + '%';
            strengthFill.style.background = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        }

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchMessage = document.getElementById('password-match');

            if (confirmPassword === '') {
                matchMessage.className = 'validation-message';
                matchMessage.innerHTML = '<i class="fas fa-info-circle"></i> Passwords must match';
                return;
            }

            if (password === confirmPassword) {
                matchMessage.className = 'validation-message valid';
                matchMessage.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            } else {
                matchMessage.className = 'validation-message invalid';
                matchMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
            }
        }

        // Address character counter
        document.getElementById('address').addEventListener('input', function() {
            const counter = document.getElementById('address-counter');
            counter.textContent = this.value.length;
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Validate name is not purely numeric
            if (!/[a-zA-Z]/.test(name) || /^\d+$/.test(name)) {
                e.preventDefault();
                alert('Name must contain letters, not just numbers');
                document.getElementById('name').focus();
                return;
            }

            // Validate email is Gmail
            if (email === '') {
                e.preventDefault();
                alert('Please enter your email address');
                document.getElementById('email').focus();
                return;
            }
            
            if (!email.endsWith('@gmail.com')) {
                e.preventDefault();
                alert('Please use your Gmail address (example: yourname@gmail.com)');
                document.getElementById('email').focus();
                return;
            }

            // Validate phone number (if provided)
            if (phone !== '' && !/^\d{10}$/.test(phone)) {
                e.preventDefault();
                alert('Mobile number must be exactly 10 digits');
                document.getElementById('phone').focus();
                return;
            }

            // Check password length
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                document.getElementById('password').focus();
                return;
            }

            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
                return;
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.maxHeight = '0';
                alert.style.marginBottom = '0';
                alert.style.padding = '0';
                alert.style.overflow = 'hidden';

                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            // Check initial password strength
            const password = document.getElementById('password').value;
            if (password) {
                checkPasswordStrength(password);
            }

            // Check initial password match
            const confirmPassword = document.getElementById('confirm_password').value;
            if (confirmPassword) {
                checkPasswordMatch();
            }

            // Initialize address counter
            const address = document.getElementById('address').value;
            document.getElementById('address-counter').textContent = address.length;

            // Add scroll indicator to form
            const authForm = document.querySelector('.auth-form');
            if (authForm.scrollHeight > authForm.clientHeight) {
                authForm.style.borderRight = '2px solid #e2e8f0';
            }
        });

        // Scroll to first error on form submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const errorFields = this.querySelectorAll('.validation-message.invalid');
            if (errorFields.length > 0) {
                errorFields[0].parentElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    </script>
</body>

</html>