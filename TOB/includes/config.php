<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
// During debugging enable display of errors. Set to 0 in production.
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bihar_food_db');

define('GST_RATE', 0.05);

// Create connection
function getDBConnection() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if (!$conn) {
        error_log("Connection failed: " . mysqli_connect_error());
        return false;
    }
    
    // Set charset to utf8mb4
    mysqli_set_charset($conn, "utf8mb4");
    
    return $conn;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration: derive from document root and project folder
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Attempt to compute project web path relative to document root
$basePath = '/';
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $projectRootFs = realpath(__DIR__ . '/..');
    if ($docRoot && $projectRootFs && strpos($projectRootFs, $docRoot) === 0) {
        $basePath = str_replace('\\', '/', substr($projectRootFs, strlen($docRoot)));
        $basePath = rtrim($basePath, '/') . '/';
    }
}

define('BASE_URL', $protocol . $host . $basePath);
define('ADMIN_URL', BASE_URL . 'admin/');
?>