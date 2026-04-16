<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Diagnostic Test ===\n\n";

// Test 1: Check if config can be loaded
echo "1. Testing config.php...\n";
if (file_exists('../includes/config.php')) {
    echo "   ✓ File exists\n";
    require_once '../includes/config.php';
    echo "   ✓ Loaded successfully\n";
} else {
    echo "   ✗ File not found\n";
    exit;
}

// Test 2: Check database connection
echo "\n2. Testing database connection...\n";
$conn = getDBConnection();
if ($conn) {
    echo "   ✓ Connected to database\n";
    
    // Check if tables exist
    $tables = ['users', 'foods', 'categories', 'orders', 'cart'];
    echo "\n3. Checking tables:\n";
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "   ✓ $table\n";
        } else {
            echo "   ✗ $table - MISSING\n";
        }
    }
    mysqli_close($conn);
} else {
    echo "   ✗ Connection failed\n";
    echo "   Database 'bihar_food_db' may not exist\n";
    exit;
}

echo "\n=== All tests passed ===\n";
?>
