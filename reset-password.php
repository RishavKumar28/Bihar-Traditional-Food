<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: users/profile.php');
    exit();
}

// Check if email and token are in session (from verify-otp page)
if (!isset($_SESSION['password_reset_email']) || !isset($_SESSION['password_reset_token'])) {
    header('Location: forgot-password.php');
    exit();
}

$email = $_SESSION['password_reset_email'];
$resetToken = $_SESSION['password_reset_token'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    $passwordErrors = [];
    
    if (empty($newPassword)) {
        $passwordErrors[] = 'Password is required';
    } elseif (strlen($newPassword) < 8) {
        $passwordErrors[] = 'Password must be at least 8 characters long';
    }
    
    if (empty($confirmPassword)) {
        $passwordErrors[] = 'Please confirm your password';
    }
    
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'Passwords do not match';
    }
    
    if (!empty($passwordErrors)) {
        $message = implode('<br>', $passwordErrors);
        $messageType = 'error';
    } else {
        $auth_obj = new Auth();
        $result = $auth_obj->resetPassword($email, $resetToken, $newPassword, $confirmPassword);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
            // Don't redirect here, let user see the success message
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Taste of Bihar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b5b 0%, #ff8c42 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #ff6b5b;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .password-input-group {
            position: relative;
        }

        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus, input[type="text"]:focus {
            outline: none;
            border-color: #ff6b5b;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #ff6b5b;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            background-color: #f44336;
            transition: width 0.3s, background-color 0.3s;
        }

        .password-strength-bar.weak {
            width: 33%;
            background-color: #f44336;
        }

        .password-strength-bar.medium {
            width: 66%;
            background-color: #ff9800;
        }

        .password-strength-bar.strong {
            width: 100%;
            background-color: #4caf50;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .requirement {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement .icon {
            width: 16px;
            text-align: center;
        }

        .requirement.met {
            color: #4caf50;
        }

        .requirement.unmet {
            color: #999;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ff6b5b 0%, #ff8c42 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 15px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 107, 91, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #ff6b5b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #ff8c42;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .info-box {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .info-box strong {
            color: #333;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-lock"></i> Reset Password</h1>
            <p>Create a strong new password for your account</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong><i class="fas fa-shield-alt"></i> Password Security Tips:</strong><br>
            • Use at least 8 characters<br>
            • Mix uppercase and lowercase letters<br>
            • Include numbers and special characters<br>
            • Avoid using personal information
        </div>

        <form method="POST" action="" id="resetForm">
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-key"></i> New Password
                </label>
                <div class="password-input-group">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter new password" 
                        required
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('password')"></i>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement unmet" id="req-length">
                        <span class="icon"><i class="fas fa-check"></i></span>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="requirement unmet" id="req-uppercase">
                        <span class="icon"><i class="fas fa-check"></i></span>
                        <span>Uppercase letter (A-Z)</span>
                    </div>
                    <div class="requirement unmet" id="req-lowercase">
                        <span class="icon"><i class="fas fa-check"></i></span>
                        <span>Lowercase letter (a-z)</span>
                    </div>
                    <div class="requirement unmet" id="req-number">
                        <span class="icon"><i class="fas fa-check"></i></span>
                        <span>Number (0-9)</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm Password
                </label>
                <div class="password-input-group">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your password" 
                        required
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('confirm_password')"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>

        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </di    <?php if ($messageType === 'success'): ?>
                <p style="margin-top: 15px; color: #2e7d32; font-size: 13px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Password reset successful! Redirecting to login in 3 seconds...
                </p>
                <script>
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
            <?php endif; ?>
        v>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.target;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 'weak';
            
            if (password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password)) {
                strength = 'strong';
            } else if (password.length >= 8 &&
                       ((/[A-Z]/.test(password) && /[a-z]/.test(password)) ||
                        (/[a-z]/.test(password) && /[0-9]/.test(password)) ||
                        (/[A-Z]/.test(password) && /[0-9]/.test(password)))) {
                strength = 'medium';
            }
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar ' + strength;
            
            // Update requirements
            document.getElementById('req-length').className = password.length >= 8 ? 'requirement met' : 'requirement unmet';
            document.getElementById('req-uppercase').className = /[A-Z]/.test(password) ? 'requirement met' : 'requirement unmet';
            document.getElementById('req-lowercase').className = /[a-z]/.test(password) ? 'requirement met' : 'requirement unmet';
            document.getElementById('req-number').className = /[0-9]/.test(password) ? 'requirement met' : 'requirement unmet';
        }

        document.getElementById('password').addEventListener('input', checkPasswordStrength);
        document.getElementById('password').addEventListener('keyup', checkPasswordStrength);
    </script>
</body>
</html>
