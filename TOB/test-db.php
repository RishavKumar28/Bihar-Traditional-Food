<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Database Connection...\n";
echo "================================\n\n";

// Try to connect
$conn = mysqli_connect('localhost', 'root', '', 'bihar_food_db');

if (!$conn) {
    echo "❌ Connection FAILED\n";
    echo "Error: " . mysqli_connect_error() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Make sure MySQL is running\n";
    echo "2. Check if 'bihar_food_db' database exists\n";
    echo "3. If not, create it and import the SQL file from database/bihar_food.sql\n";
} else {
    echo "✓ Connection SUCCESSFUL\n\n";
    
    // List all databases
    $result = mysqli_query($conn, "SHOW DATABASES");
    echo "Available Databases:\n";
    while ($row = mysqli_fetch_row($result)) {
        echo "  - " . $row[0] . "\n";
    }
    
    mysqli_close($conn);
}
?>
