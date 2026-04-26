<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Get cart count
$query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $userId";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$count = $row['count'] ?? 0;

// Also get cart total for display
$totalQuery = "SELECT SUM(f.price * c.quantity) as total 
               FROM cart c 
               JOIN foods f ON c.food_id = f.id 
               WHERE c.user_id = $userId AND f.is_available = 1";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);

echo json_encode([
    'success' => true,
    'count' => $count,
    'total' => $totalRow['total'] ?? 0,
    'formatted_total' => '₹' . number_format($totalRow['total'] ?? 0, 2)
]);
?>