<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Filename
$filename = sprintf('bihar_food_export_user_%d_%s.csv', $userId, date('Ymd_His'));

// Send headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
if (!$out) {
    http_response_code(500);
    echo "Failed to open output";
    exit();
}

// Write User Info
$userQuery = "SELECT id, name, email, phone, address, created_at FROM users WHERE id = $userId LIMIT 1";
$userRes = mysqli_query($conn, $userQuery);
$user = $userRes ? mysqli_fetch_assoc($userRes) : null;
fputcsv($out, ['Section', 'Field', 'Value']);
if ($user) {
    fputcsv($out, ['User Info', 'ID', $user['id']]);
    fputcsv($out, ['User Info', 'Name', $user['name']]);
    fputcsv($out, ['User Info', 'Email', $user['email']]);
    fputcsv($out, ['User Info', 'Phone', $user['phone']]);
    fputcsv($out, ['User Info', 'Address', $user['address']]);
    fputcsv($out, ['User Info', 'Created At', $user['created_at']]);
} else {
    fputcsv($out, ['User Info', 'Error', 'User not found']);
}

fputcsv($out, []);

// Orders
fputcsv($out, ['Orders', 'Order ID', 'Subtotal', 'Delivery Charge', 'GST', 'Discount', 'Total Price', 'Status', 'Order Date']);
$ordersQuery = "SELECT id, order_number, subtotal, delivery_charge, tax_amount, discount_amount, total_price, status, order_date FROM orders WHERE user_id = $userId ORDER BY order_date DESC";
$ordersRes = mysqli_query($conn, $ordersQuery);
if ($ordersRes && mysqli_num_rows($ordersRes) > 0) {
    while ($row = mysqli_fetch_assoc($ordersRes)) {
        fputcsv($out, [
            'Orders',
            $row['order_number'] ?? $row['id'],
            $row['subtotal'],
            $row['delivery_charge'],
            $row['tax_amount'],
            $row['discount_amount'],
            $row['total_price'],
            $row['status'],
            $row['order_date']
        ]);
    }
} else {
    fputcsv($out, ['Orders', 'None', '', '', '']);
}

fputcsv($out, []);

// Wishlist
fputcsv($out, ['Wishlist', 'Food ID', 'Food Name', 'Price', 'Added At']);
$wishQuery = "SELECT w.id as wish_id, f.id as food_id, f.name, f.price, w.added_at FROM wishlist w LEFT JOIN foods f ON w.food_id = f.id WHERE w.user_id = $userId";
$wishRes = mysqli_query($conn, $wishQuery);
if ($wishRes && mysqli_num_rows($wishRes) > 0) {
    while ($w = mysqli_fetch_assoc($wishRes)) {
        fputcsv($out, ['Wishlist', $w['food_id'], $w['name'], $w['price'], $w['added_at']]);
    }
} else {
    fputcsv($out, ['Wishlist', 'None', '', '', '']);
}

fclose($out);
exit();
?>