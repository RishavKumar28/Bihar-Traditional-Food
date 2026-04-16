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

// Check if email is in session (from forgot-password page)
if (!isset($_SESSION['password_reset_email'])) {
    header('Location: forgot-password.php');
    exit();
}

$email = $_SESSION['password_reset_email'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    
    if (empty($otp)) {
        $message = 'Please enter the OTP';
        $messageType = 'error';
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $message = 'OTP must be 6 digits';
        $messageType = 'error';
    } else {
        $auth_obj = new Auth();
        $result = $auth_obj->verifyOTP($email, $otp);
        
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
    <title>Verify OTP - Taste of Bihar</title>
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
            margin-bottom: 5px;
        }

        .email-info {
            color: #999;
            font-size: 13px;
            margin-top: 8px;
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

        .otp-input-group {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .otp-input-group input {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            transition: border-color 0.3s;
        }

        .otp-input-group input:focus {
            outline: none;
            border-color: #ff6b5b;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #ff6b5b;
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
            margin-top: 10px;
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
            align-items: center;
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
            text-align: center;
        }

        .info-box strong {
            color: #333;
        }

        .timer {
            text-align: center;
            margin: 15px 0;
            font-size: 13px;
            color: #ff6b5b;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .otp-input-group {
                gap: 6px;
            }

            .otp-input-group input {
                font-size: 16px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Verify OTP</h1>
            <p>Enter the 6-digit code sent to your email</p>
            <p class="email-info"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong><i class="fas fa-clock"></i> OTP valid for 10 minutes</strong>
        </div>

        <form method="POST" action="" id="otpForm">
            <div class="form-group">
                <label for="otp">
                    <i class="fas fa-key"></i> Enter OTP
                </label>
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    placeholder="000000" 
                    maxlength="6"
                    inputmode="numeric"
                    pattern="\d{6}"
                    required
                    autocomplete="off"
                    value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>"
                >
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-check"></i> Verify OTP
            </button>
        </form>

        <div class="timer">
            <i class="fas fa-info-circle"></i> Didn't receive the OTP? <a href="forgot-password.php" style="color: #ff6b5b; text-decoration: none; font-weight: 600;">Request again</a>
        </div>

        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
            <?php if ($messageType === 'success'): ?>
                <p style="margin-top: 15px; color: #999; font-size: 13px;">
                    <i class="fas fa-check-circle"></i> OTP verified! Redirecting to password reset in 3 seconds...
                </p>
                <script>
                    setTimeout(() => {
                        window.location.href = 'reset-password.php';
                    }, 3000);
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-focus on first input and allow only numbers
        document.getElementById('otp').addEventListener('keydown', function(e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
                e.preventDefault();
            }
        });

        // Format OTP input
        document.getElementById('otp').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
        });
    </script>
</body>
</html>
