<?php
session_start();
require_once '../includes/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to cart'
    ]);
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Try to get data from JSON body first
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Fall back to $_POST if JSON is not available
    if (!$data) {
        $data = $_POST;
    }
    
    // Extract food_id and quantity
    $foodId = isset($data['food_id']) ? intval($data['food_id']) : 0;
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if ($foodId <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid food ID or quantity'
        ]);
        exit();
    }
    
    // Connect to database
    $conn = getDBConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error'
        ]);
        exit();
    }
    
    // Check if cart table exists, if not create it
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'cart'");
    if (mysqli_num_rows($tableCheck) == 0) {
        // Create cart table
        $createTable = "CREATE TABLE cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            food_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (food_id) REFERENCES foods(id),
            UNIQUE KEY unique_cart_item (user_id, food_id)
        )";
        
        if (!mysqli_query($conn, $createTable)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create cart table'
            ]);
            exit();
        }
    }
    
    // Verify food exists and is available
    $foodCheck = "SELECT id FROM foods WHERE id = $foodId AND is_available = 1";
    $foodCheckResult = mysqli_query($conn, $foodCheck);
    
    if (mysqli_num_rows($foodCheckResult) == 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Food item not found or not available'
        ]);
        exit();
    }
    
    // Check if item already exists in cart
    $checkQuery = "SELECT quantity FROM cart WHERE user_id = $userId AND food_id = $foodId";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Update quantity
        $updateQuery = "UPDATE cart SET quantity = quantity + $quantity WHERE user_id = $userId AND food_id = $foodId";
        if (!mysqli_query($conn, $updateQuery)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cart: ' . mysqli_error($conn)
            ]);
            exit();
        }
        $message = "Item quantity updated in cart!";
    } else {
        // Insert new item
        $insertQuery = "INSERT INTO cart (user_id, food_id, quantity) VALUES ($userId, $foodId, $quantity)";
        if (!mysqli_query($conn, $insertQuery)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add item to cart: ' . mysqli_error($conn)
            ]);
            exit();
        }
        $message = "Item added to cart successfully!";
    }
    
    // Set success message in session for form-based requests
    $_SESSION['success_message'] = $message;
    
    // Return JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    exit();
    
} else {
    // Invalid request method
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}
?>