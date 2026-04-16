<?php
// cancel-order.php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['error_message'] = 'Please login to cancel orders';
    header('Location: ../login.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Order ID is required';
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // 1. First, check if the order exists and belongs to the user
    $checkQuery = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
    
    error_log("Executing query: " . $checkQuery);
    
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (!$checkResult) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($checkResult) == 0) {
        throw new Exception("Order not found or you don't have permission to cancel it");
    }
    
    $order = mysqli_fetch_assoc($checkResult);
    
    error_log("Order Data: " . print_r($order, true));
    
    // 2. Check if order can be cancelled based on status
    // Statuses that can be cancelled: 'pending', 'confirmed' (maybe 'preparing' too)
    $cancellableStatuses = ['pending', 'confirmed'];
    
    error_log("Current Order Status: " . $order['status']);
    error_log("Cancellable Statuses: " . print_r($cancellableStatuses, true));
    
    if (!in_array($order['status'], $cancellableStatuses)) {
        throw new Exception("Order cannot be cancelled because it's already {$order['status']}");
    }
    
    // 3. Check if cancellation is within time limit (30 minutes)
    $orderTime = strtotime($order['order_date']); // Using order_date instead of created_at
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $maxCancellationTime = 30 * 60; // 30 minutes in seconds
    
    error_log("Order Time: " . date('Y-m-d H:i:s', $orderTime));
    error_log("Current Time: " . date('Y-m-d H:i:s', $currentTime));
    error_log("Time Difference: " . $timeDifference . " seconds");
    error_log("Max Cancellation Time: " . $maxCancellationTime . " seconds");
    
    if ($timeDifference > $maxCancellationTime) {
        $minutes = floor($timeDifference / 60);
        throw new Exception("Order cannot be cancelled after 30 minutes of placement. Order was placed $minutes minutes ago.");
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // 4. Update order status to 'cancelled'
    $updateOrderQuery = "UPDATE orders SET 
                        status = 'cancelled', 
                        cancelled_at = NOW(), 
                        cancellation_reason = 'Cancelled by user',
                        updated_at = NOW() 
                        WHERE id = $order_id";
    
    error_log("Update Query: " . $updateOrderQuery);
    
    $updateResult = mysqli_query($conn, $updateOrderQuery);
    
    if (!$updateResult) {
        throw new Exception("Failed to update order status: " . mysqli_error($conn));
    }
    
    error_log("Order status updated to cancelled successfully");
    
    // 5. Insert into order_tracking (if table exists)
    $checkTrackingTable = mysqli_query($conn, "SHOW TABLES LIKE 'order_tracking'");
    if (mysqli_num_rows($checkTrackingTable) > 0) {
        $trackingQuery = "INSERT INTO order_tracking (order_id, status, message, tracked_at) 
                         VALUES ($order_id, 'cancelled', 'Order cancelled by user', NOW())";
        
        error_log("Tracking Query: " . $trackingQuery);
        
        $trackingResult = mysqli_query($conn, $trackingQuery);
        
        if (!$trackingResult) {
            error_log("Failed to add order tracking: " . mysqli_error($conn));
        } else {
            error_log("Order tracking added successfully");
        }
    } else {
        error_log("order_tracking table does not exist, skipping");
    }
    
    // 6. If payment was made, update payment status to refunded
    error_log("Original Payment Status: " . $order['payment_status']);
    if ($order['payment_status'] === 'completed') {
        $refundQuery = "UPDATE orders 
                        SET payment_status = 'refunded',
                            updated_at = NOW() 
                        WHERE id = $order_id";
        
        error_log("Refund Query: " . $refundQuery);
        
        $refundResult = mysqli_query($conn, $refundQuery);
        
        if (!$refundResult) {
            error_log("Failed to update refund status: " . mysqli_error($conn));
        } else {
            error_log("Payment status updated to refunded");
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    error_log("Transaction committed successfully");
    
    // Send success message
    $_SESSION['success_message'] = "Order #$order_id has been cancelled successfully!";
    
    // Close connection
    mysqli_close($conn);
    
    // Redirect back to orders page
    error_log("Redirecting to orders.php");
    header('Location: orders.php');
    exit;
    
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    
    // Rollback transaction on error
    if (isset($conn)) {
        mysqli_rollback($conn);
        mysqli_close($conn);
    }
    
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: orders.php');
    exit;
}
?>