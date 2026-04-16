<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

try {
    $conn = getDBConnection();

    // Verify order belongs to user
    $checkQuery = "SELECT id FROM orders WHERE id = $orderId AND user_id = $userId";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Get current order status
    $orderQuery = "SELECT id, status, payment_status, order_date, updated_at FROM orders WHERE id = $orderId";
    $orderResult = mysqli_query($conn, $orderQuery);
    $order = mysqli_fetch_assoc($orderResult);
    // Normalize DB status to UI status (DB may use 'preparing')
    $rawStatus = $order['status'] ?? '';
    $normalizedStatus = $rawStatus;
    if ($rawStatus === 'preparing') {
        $normalizedStatus = 'processing';
    }

    // Define status timeline
    $statusTimeline = [
        'pending' => 'Order Placed',
        'processing' => 'Being Prepared',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];

    $statusColors = [
        'pending' => '#3498db',
        'processing' => '#f39c12',
        'delivered' => '#27ae60',
        'cancelled' => '#e74c3c'
    ];

    $statusIcons = [
        'pending' => 'shopping-cart',
        'processing' => 'fire',
        'delivered' => 'check-circle',
        'cancelled' => 'times-circle'
    ];

    // Calculate progress percentage for 3 visible statuses (pending, processing, delivered)
    $progressOrder = ['pending', 'processing', 'delivered'];
    $currentIndex = array_search($normalizedStatus, $progressOrder);
    $progress = $currentIndex !== false ? (($currentIndex + 1) / count($progressOrder)) * 100 : 0;

    if ($order['status'] == 'cancelled') {
        $progress = 100;
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'status' => $normalizedStatus,
        'status_label' => $statusTimeline[$normalizedStatus] ?? 'Unknown',
        'status_color' => $statusColors[$normalizedStatus] ?? '#999',
        'status_icon' => $statusIcons[$normalizedStatus] ?? 'info',
        'payment_status' => $order['payment_status'],
        'progress' => $progress,
        'order_date' => date('d M, Y h:i A', strtotime($order['order_date'])),
        'updated_at' => $order['updated_at'] ? date('d M, Y h:i A', strtotime($order['updated_at'])) : null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
