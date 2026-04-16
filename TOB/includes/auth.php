<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Register new user
    public function register($name, $email, $password, $phone = null, $address = null) {
        $name = mysqli_real_escape_string($this->conn, $name);
        $email = mysqli_real_escape_string($this->conn, $email);
        $phone = mysqli_real_escape_string($this->conn, $phone);
        $address = mysqli_real_escape_string($this->conn, $address);
        
        // Validate that name is not purely numeric
        if (ctype_digit(str_replace(' ', '', $name))) {
            return ['success' => false, 'message' => 'Name must contain letters, not just numbers'];
        }
        
        // Validate phone number (if provided)
        if (!empty($phone)) {
            if (!preg_match('/^\d{10}$/', $phone)) {
                return ['success' => false, 'message' => 'Mobile number must be exactly 10 digits'];
            }
        }
        
        // Check if user already exists
        $checkQuery = "SELECT id FROM users WHERE email = '$email'";
        $checkResult = mysqli_query($this->conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $query = "INSERT INTO users (name, email, password, phone, address) 
                  VALUES ('$name', '$email', '$hashedPassword', '$phone', '$address')";
        
        if (mysqli_query($this->conn, $query)) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    // User login
    public function login($email, $password) {
        $email = mysqli_real_escape_string($this->conn, $email);
        
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                return ['success' => true, 'role' => $user['role']];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Admin login
    public function adminLogin($username, $password) {
        $username = mysqli_real_escape_string($this->conn, $username);
        
        $query = "SELECT * FROM admin WHERE username = '$username'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                return true;
            }
        }
        
        return false;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if admin is logged in
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    // Logout
    public function logout() {
        session_destroy();
        header('Location: ../login.php');
        exit();
    }
    
    // Get current user data
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $query = "SELECT * FROM users WHERE id = $userId";
            $result = mysqli_query($this->conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
        }
        return null;
    }
    
    // Forgot Password - Generate and send OTP
    public function forgotPassword($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        
        // Check if user exists
        $query = "SELECT id, name FROM users WHERE email = '$email'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 0) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $user = mysqli_fetch_assoc($result);
        
        // Generate OTP
        require_once 'mailer.php';
        $otp = generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update user with OTP
        $updateQuery = "UPDATE users SET otp = '$otp', otp_expiry = '$otpExpiry' WHERE id = {$user['id']}";
        
        if (mysqli_query($this->conn, $updateQuery)) {
            // Send OTP via email
            if (sendOTPEmail($email, $otp, $user['name'])) {
                $_SESSION['password_reset_email'] = $email;
                $_SESSION['password_reset_user_id'] = $user['id'];
                return ['success' => true, 'message' => 'OTP sent to your email'];
            } else {
                return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to process request'];
        }
    }
    
    // Verify OTP
    public function verifyOTP($email, $otp) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $otp = mysqli_real_escape_string($this->conn, $otp);
        
        $query = "SELECT id, otp, otp_expiry FROM users WHERE email = '$email'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 0) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $user = mysqli_fetch_assoc($result);
        
        // Check if OTP is expired
        $currentTime = date('Y-m-d H:i:s');
        if ($currentTime > $user['otp_expiry']) {
            return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
        }
        
        // Check if OTP matches
        if ($user['otp'] !== $otp) {
            return ['success' => false, 'message' => 'Invalid OTP'];
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $updateQuery = "UPDATE users SET reset_token = '$resetToken', reset_token_expiry = '$resetTokenExpiry' WHERE id = {$user['id']}";
        
        if (mysqli_query($this->conn, $updateQuery)) {
            $_SESSION['password_reset_token'] = $resetToken;
            $_SESSION['password_reset_email'] = $email;
            return ['success' => true, 'message' => 'OTP verified successfully', 'token' => $resetToken];
        } else {
            return ['success' => false, 'message' => 'Failed to verify OTP'];
        }
    }
    
    // Reset Password
    public function resetPassword($email, $resetToken, $newPassword, $confirmPassword) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $resetToken = mysqli_real_escape_string($this->conn, $resetToken);
        
        // Validate passwords match
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        // Validate password strength
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        // Check if token is valid and not expired
        $query = "SELECT id, name FROM users WHERE email = '$email' AND reset_token = '$resetToken'";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) == 0) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        $user = mysqli_fetch_assoc($result);
        
        $currentTime = date('Y-m-d H:i:s');
        $checkQuery = "SELECT reset_token_expiry FROM users WHERE id = {$user['id']}";
        $checkResult = mysqli_query($this->conn, $checkQuery);
        $userData = mysqli_fetch_assoc($checkResult);
        
        if ($currentTime > $userData['reset_token_expiry']) {
            return ['success' => false, 'message' => 'Reset token has expired. Please request a new one.'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $updateQuery = "UPDATE users SET password = '$hashedPassword', otp = NULL, otp_expiry = NULL, reset_token = NULL, reset_token_expiry = NULL WHERE id = {$user['id']}";
        
        if (mysqli_query($this->conn, $updateQuery)) {
            // Send confirmation email
            require_once 'mailer.php';
            sendPasswordResetConfirmationEmail($email, $user['name']);
            
            // Clear session variables
            unset($_SESSION['password_reset_email']);
            unset($_SESSION['password_reset_token']);
            unset($_SESSION['password_reset_user_id']);
            
            return ['success' => true, 'message' => 'Password reset successfully. Please login with your new password.'];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
}
?>