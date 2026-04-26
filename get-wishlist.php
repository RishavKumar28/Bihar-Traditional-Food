<?php
// Only proceed if config and auth are already loaded
if (!function_exists('getDBConnection')) {
    require_once '../includes/config.php';
}

if (!class_exists('Auth')) {
    require_once '../includes/auth.php';
}

// Initialize variables
$userId = 0;
$wishlistItems = [];

// Check if Auth class exists and create instance
if (class_exists('Auth')) {
    $auth = new Auth();
    
    // Only get wishlist if user is logged in
    if ($auth->isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $conn = getDBConnection();

        if ($conn) {
            // Get user's wishlist items
            $wishlistQuery = "SELECT f.*, c.name as category_name 
                              FROM wishlist w 
                              JOIN foods f ON w.food_id = f.id 
                              LEFT JOIN categories c ON f.category_id = c.id 
                              WHERE w.user_id = $userId 
                              ORDER BY w.added_at DESC";

            $wishlistResult = mysqli_query($conn, $wishlistQuery);

            if ($wishlistResult) {
                while ($item = mysqli_fetch_assoc($wishlistResult)) {
                    $wishlistItems[] = $item;
                }
            }
        }
    }
}

// Check if specific food is in wishlist
function isInWishlist($foodId, $userId) {
    if ($userId == 0) {
        return false; // Not logged in
    }
    
    if (!function_exists('getDBConnection')) {
        return false;
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        return false;
    }
    
    $query = "SELECT id FROM wishlist WHERE user_id = $userId AND food_id = $foodId";
    $result = mysqli_query($conn, $query);
    return $result && mysqli_num_rows($result) > 0;
}
?>
