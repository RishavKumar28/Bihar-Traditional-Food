<?php
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration - Update these with your actual email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'rishavkumar62025@gmail.com'); // Update this
define('SMTP_PASS', 'illo cbup rbua rzkq');      // Update this - Use App Password for Gmail
define('FROM_EMAIL', 'noreply@tasteofbihar.com');
define('FROM_NAME', 'Taste of Bihar');

/**
 * Send OTP via Email
 * 
 * @param string $recipientEmail User's email address
 * @param string $otp 6-digit OTP
 * @param string $userName User's name
 * @return bool True if email sent successfully
 */
function sendOTPEmail($recipientEmail, $otp, $userName) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($recipientEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - Taste of Bihar';
        
        $htmlBody = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                    .container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; }
                    .header { text-align: center; color: #ff6b5b; margin-bottom: 20px; }
                    .content { margin: 20px 0; }
                    .otp-box { background-color: #f0f0f0; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #ff6b5b; letter-spacing: 5px; }
                    .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Taste of Bihar</h1>
                    </div>
                    <div class='content'>
                        <p>Hi <strong>{$userName}</strong>,</p>
                        <p>We received a request to reset your password. Use the OTP below to verify your identity and set a new password.</p>
                        <div class='otp-box'>
                            <p style='margin: 0; color: #666; font-size: 14px;'>Your One-Time Password</p>
                            <div class='otp-code'>{$otp}</div>
                            <p style='margin: 10px 0 0 0; color: #999; font-size: 12px;'>This OTP is valid for 10 minutes</p>
                        </div>
                        <p style='color: #d32f2f; font-weight: bold;'>⚠️ Do not share this OTP with anyone. Our support team will never ask for your OTP.</p>
                        <p>If you didn't request this password reset, please ignore this email or contact us immediately.</p>
                    </div>
                    <div class='footer'>
                        <p>© 2026 Taste of Bihar. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply to this message.</p>
                    </div>
                </div>
            </body>
        </html>
        ";
        
        $mail->Body = $htmlBody;
        $mail->AltBody = 'Your OTP is: ' . $otp . '. This OTP is valid for 10 minutes.';
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send Password Reset Confirmation Email
 * 
 * @param string $recipientEmail User's email address
 * @param string $userName User's name
 * @return bool True if email sent successfully
 */
function sendPasswordResetConfirmationEmail($recipientEmail, $userName) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($recipientEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Successfully - Taste of Bihar';
        
        $htmlBody = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                    .container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; }
                    .header { text-align: center; color: #ff6b5b; margin-bottom: 20px; }
                    .content { margin: 20px 0; }
                    .success-box { background-color: #e8f5e9; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50; margin: 20px 0; }
                    .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Taste of Bihar</h1>
                    </div>
                    <div class='content'>
                        <p>Hi <strong>{$userName}</strong>,</p>
                        <div class='success-box'>
                            <p style='margin: 0; color: #2e7d32; font-weight: bold;'>✓ Your password has been successfully reset</p>
                        </div>
                        <p>Your password for Taste of Bihar account has been changed successfully.</p>
                        <p>If you did not perform this action, please contact our support team immediately.</p>
                        <p style='margin-top: 20px;'>Best regards,<br><strong>Taste of Bihar Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>© 2026 Taste of Bihar. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply to this message.</p>
                    </div>
                </div>
            </body>
        </html>
        ";
        
        $mail->Body = $htmlBody;
        $mail->AltBody = 'Your password has been reset successfully. If you did not perform this action, please contact support.';
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generate a random OTP
 * 
 * @return string 6-digit OTP
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
