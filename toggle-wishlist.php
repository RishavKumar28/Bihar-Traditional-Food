<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to add items to wishlist']);
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Get the food ID from POST request
$foodId = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;

if (!$foodId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid food ID']);
    exit();
}

try {
    // Check if item already exists in wishlist
    $checkQuery = "SELECT id FROM wishlist WHERE user_id = $userId AND food_id = $foodId";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (!$checkResult) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($checkResult) > 0) {
        // Remove from wishlist
        $deleteQuery = "DELETE FROM wishlist WHERE user_id = $userId AND food_id = $foodId";
        if (!mysqli_query($conn, $deleteQuery)) {
            throw new Exception("Failed to remove from wishlist: " . mysqli_error($conn));
        }
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Removed from wishlist',
            'inWishlist' => false
        ]);
    } else {
        // Add to wishlist
        $insertQuery = "INSERT INTO wishlist (user_id, food_id, added_at) 
                        VALUES ($userId, $foodId, NOW())";
        if (!mysqli_query($conn, $insertQuery)) {
            throw new Exception("Failed to add to wishlist: " . mysqli_error($conn));
        }
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'Added to wishlist',
            'inWishlist' => true
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
