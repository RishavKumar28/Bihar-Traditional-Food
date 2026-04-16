<?php
session_start();

echo "<h1>Forgot Password System Test</h1>";

// Check if files exist
$files = [
    'forgot-password.php',
    'verify-otp.php', 
    'reset-password.php',
    'includes/mailer.php',
    'includes/auth.php',
    'includes/config.php'
];

echo "<h2>File Existence Check:</h2>";
echo "<ul>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? '✓ EXISTS' : '✗ MISSING';
    echo "<li>$file - $exists</li>";
}
echo "</ul>";

// Check database connection
echo "<h2>Database Connection:</h2>";
require_once 'includes/config.php';
$conn = @getDBConnection();
if ($conn) {
    echo "✓ Database connection successful<br>";
    
    // Check if users table has required columns
    $query = "DESCRIBE users";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "✓ Users table exists<br>";
        echo "<h3>Users Table Columns:</h3>";
        echo "<ul>";
        $columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
            echo "<li>{$row['Field']}</li>";
        }
        echo "</ul>";
        
        // Check for password reset columns
        $requiredCols = ['otp', 'otp_expiry', 'reset_token', 'reset_token_expiry'];
        echo "<h3>Required Password Reset Columns:</h3>";
        echo "<ul>";
        foreach ($requiredCols as $col) {
            $exists = in_array($col, $columns) ? '✓ EXISTS' : '✗ MISSING';
            echo "<li>$col - $exists</li>";
        }
        echo "</ul>";
    } else {
        echo "✗ Error checking users table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✗ Database connection failed<br>";
}

// Test email configuration
echo "<h2>Email Configuration:</h2>";
require_once 'includes/mailer.php';
echo "SMTP_HOST: " . SMTP_HOST . "<br>";
echo "SMTP_PORT: " . SMTP_PORT . "<br>";
echo "FROM_EMAIL: " . FROM_EMAIL . "<br>";
echo "FROM_NAME: " . FROM_NAME . "<br>";

// Check if functions exist
echo "<h2>Mail Functions:</h2>";
echo "<ul>";
echo "<li>sendOTPEmail: " . (function_exists('sendOTPEmail') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>sendPasswordResetConfirmationEmail: " . (function_exists('sendPasswordResetConfirmationEmail') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>generateOTP: " . (function_exists('generateOTP') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "</ul>";

// Check Auth class methods
echo "<h2>Auth Class Methods:</h2>";
$auth = new Auth();
echo "<ul>";
echo "<li>forgotPassword: " . (method_exists($auth, 'forgotPassword') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>verifyOTP: " . (method_exists($auth, 'verifyOTP') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "<li>resetPassword: " . (method_exists($auth, 'resetPassword') ? '✓ EXISTS' : '✗ MISSING') . "</li>";
echo "</ul>";

echo "<h2>Test Results Summary:</h2>";
echo "<p><strong>Status:</strong> If all items above show ✓, the forgot password system is properly set up!</p>";
echo "<p><a href='forgot-password.php' style='background: #ff6b5b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Forgot Password Page</a></p>";
?>
