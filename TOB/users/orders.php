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

// Get user's orders
$ordersQuery = "SELECT * FROM orders WHERE user_id = $userId ORDER BY order_date DESC";
$ordersResult = mysqli_query($conn, $ordersQuery);

// Get order statistics
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_price) as total_spent
               FROM orders WHERE user_id = $userId";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="orders-container">
        <div class="orders-header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
            <p>Track and manage your food orders</p>
        </div>
        
        <!-- Order Statistics -->
        <div class="orders-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['delivered_orders'] ?? 0; ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_orders'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="orders-content">
            <?php if (mysqli_num_rows($ordersResult) > 0): ?>
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($ordersResult)): 
                            // Get order item count
                            $itemQuery = "SELECT COUNT(*) as count FROM order_items WHERE order_id = " . $order['id'];
                            $itemResult = mysqli_query($conn, $itemQuery);
                            $itemCount = mysqli_fetch_assoc($itemResult)['count'];
                        ?>
                        <tr>
                            <td>
                                <div class="order-id">
                                    #<?php echo $order['id']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="order-date">
                                    <?php echo date('d/m/Y', strtotime($order['order_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="order-items">
                                    <i class="fas fa-utensils"></i>
                                    <span><?php echo $itemCount; ?> items</span>
                                </div>
                            </td>
                            <td>
                                <div class="order-amount">
                                    <strong>₹<?php echo number_format($order['total_price'], 2); ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="payment-method">
                                    <?php echo strtoupper($order['payment_method']); ?><br>
                                    <small><?php echo ucfirst($order['payment_status']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="order-actions">
                                    <a href="order-tracking.php?id=<?php echo $order['id']; ?>" class="btn-track">
                                        <i class="fas fa-map-pin"></i> Track
                                    </a>
                                    <?php if ($order['status'] == 'pending'): ?>
                                    <a href="cancel-order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($order['status'] == 'delivered'): ?>
                                    <a href="feedback.php?order_id=<?php echo $order['id']; ?>" class="btn-feedback">
                                        <i class="fas fa-star"></i> Rate
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Order Status Legend -->
            <div class="status-legend">
                <h3><i class="fas fa-info-circle"></i> Order Status Guide</h3>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="status-badge status-pending"></span>
                        <span>Pending - Order received, not yet processed</span>
                    </div>
                    <div class="legend-item">
                        <span class="status-badge status-processing"></span>
                        <span>Processing - Food being prepared</span>
                    </div>
                    <div class="legend-item">
                        <span class="status-badge status-delivered"></span>
                        <span>Delivered - Order delivered successfully</span>
                    </div>
                    <div class="legend-item">
                        <span class="status-badge status-cancelled"></span>
                        <span>Cancelled - Order has been cancelled</span>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="no-orders">
                <div class="no-orders-icon">
                    <i class="fas fa-shopping-bag fa-4x"></i>
                </div>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start exploring our menu!</p>
                <div class="no-orders-actions">
                    <a href="menu.php" class="btn-explore">
                        <i class="fas fa-utensils"></i> Explore Menu
                    </a>
                    <a href="../index.php#categories" class="btn-categories">
                        <i class="fas fa-list"></i> View Categories
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="menu.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <span>Place New Order</span>
                </a>
                <a href="cart.php" class="action-card">
                    <i class="fas fa-shopping-cart"></i>
                    <span>View Cart</span>
                    <?php
                    $cartCountQuery = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $userId";
                    $cartCountResult = mysqli_query($conn, $cartCountQuery);
                    $cartCount = mysqli_fetch_assoc($cartCountResult)['count'] ?? 0;
                    if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="feedback.php" class="action-card">
                    <i class="fas fa-comment"></i>
                    <span>Give Feedback</span>
                </a>
                <a href="profile.php" class="action-card">
                    <i class="fas fa-user"></i>
                    <span>Update Profile</span>
                </a>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <style>
    .orders-container {
        max-width: 1200px;
        margin: 100px auto 50px;
        padding: 0 20px;
    }
    
    .orders-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .orders-header h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }
    
    .orders-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background: #667eea;
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-info h3 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 5px;
    }
    
    .stat-info p {
        color: #666;
        font-size: 0.9rem;
    }
    
    .orders-table-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow-x: auto;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }
    
    .orders-table td {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .orders-table tr:hover {
        background: #f8f9fa;
    }
    
    .order-id {
        font-weight: 600;
        color: #667eea;
    }
    
    .order-date small {
        color: #666;
        font-size: 0.8rem;
    }
    
    .order-items {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .order-items i {
        color: #e74c3c;
    }
    
    .payment-method small {
        color: #666;
        font-size: 0.8rem;
    }
    
    .order-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .btn-view,
    .btn-track,
    .btn-cancel,
    .btn-feedback {
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    
    .btn-view {
        background: #3498db;
        color: white;
    }

    .btn-track {
        background: #27ae60;
        color: white;
    }
    
    .btn-cancel {
        background: #e74c3c;
        color: white;
    }
    
    .btn-feedback {
        background: #f1c40f;
        color: #333;
    }
    
    .status-legend {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .status-legend h3 {
        margin-bottom: 15px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .legend-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .legend-item .status-badge {
        width: 20px;
        height: 20px;
        border-radius: 3px;
    }
    
    .no-orders {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .no-orders-icon {
        margin-bottom: 20px;
        color: #ddd;
    }
    
    .no-orders h2 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .no-orders p {
        color: #666;
        margin-bottom: 30px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .no-orders-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-explore,
    .btn-categories {
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-explore {
        background: #e74c3c;
        color: white;
    }
    
    .btn-categories {
        background: #667eea;
        color: white;
    }
    
    .quick-actions {
        margin-top: 40px;
    }
    
    .quick-actions h2 {
        margin-bottom: 20px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .action-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-decoration: none;
        color: #333;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
        position: relative;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .action-card i {
        font-size: 2rem;
        color: #667eea;
    }
    
    .cart-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #e74c3c;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .orders-stats {
            grid-template-columns: 1fr 1fr;
        }
        
        .legend-items {
            grid-template-columns: 1fr;
        }
        
        .no-orders-actions {
            flex-direction: column;
        }
        
        .actions-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>