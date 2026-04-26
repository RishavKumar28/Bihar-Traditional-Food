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

// Get order ID from URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    header('Location: orders.php');
    exit();
}

// Get order details
$orderQuery = "SELECT o.*, u.name as user_name, u.email, u.phone, u.address 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = $orderId AND o.user_id = $userId";

$orderResult = mysqli_query($conn, $orderQuery);

if (!$orderResult || mysqli_num_rows($orderResult) == 0) {
    header('Location: orders.php');
    exit();
}

$order = mysqli_fetch_assoc($orderResult);

// Get order items
$itemsQuery = "SELECT oi.*, f.name as food_name, f.image_path 
               FROM order_items oi 
               JOIN foods f ON oi.food_id = f.id 
               WHERE oi.order_id = $orderId";

$itemsResult = mysqli_query($conn, $itemsQuery);
$items = [];

if ($itemsResult) {
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $items[] = $item;
    }
}

// Define status timeline
$statusTimeline = [
    'pending' => [
        'label' => 'Order Placed',
        'icon' => 'shopping-cart',
        'color' => '#3498db',
        'description' => 'Your order has been placed successfully'
    ],
    'processing' => [
        'label' => 'Being Prepared',
        'icon' => 'fire',
        'color' => '#f39c12',
        'description' => 'Our team is preparing your delicious food'
    ],
    'delivered' => [
        'label' => 'Delivered',
        'icon' => 'check-circle',
        'color' => '#27ae60',
        'description' => 'Your order has been delivered'
    ],
    'cancelled' => [
        'label' => 'Cancelled',
        'icon' => 'times-circle',
        'color' => '#e74c3c',
        'description' => 'This order has been cancelled'
    ]
];

$rawStatus = $order['status'];
// Normalize DB status to UI status (DB may use 'preparing')
$normalizedStatus = $rawStatus;
if ($rawStatus === 'preparing') {
    $normalizedStatus = 'processing';
}

$statusOrder = ['pending', 'processing', 'delivered'];
$currentStatusIndex = array_search($normalizedStatus, $statusOrder);
if ($rawStatus == 'cancelled') {
    $currentStatusIndex = -1;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $orderId; ?> - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }

        .tracking-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .tracking-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        .tracking-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .order-tracking-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .order-tracking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }

        .order-info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 20px;
        }

        .order-info-item h3 {
            font-size: 0.9rem;
            margin-bottom: 8px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-info-item p {
            font-size: 1.2rem;
            margin: 0;
        }

        /* Timeline Styles */
        .timeline-section {
            padding: 40px;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e0e0e0;
            transform: translateX(-50%);
        }

        .timeline-item {
            margin-bottom: 40px;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-marker {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            z-index: 2;
            top: 0;
        }

        .timeline-item.active .timeline-marker {
            border-color: #27ae60;
            background: #27ae60;
            color: white;
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1);
        }

        .timeline-item.completed .timeline-marker {
            border-color: #27ae60;
            background: #27ae60;
            color: white;
        }

        .timeline-item.cancelled .timeline-marker {
            border-color: #e74c3c;
            background: #e74c3c;
            color: white;
        }

        .timeline-content {
            margin-left: 80px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .timeline-item.active .timeline-content {
            background: #d1fae5;
            border-left: 4px solid #27ae60;
        }

        .timeline-item.completed .timeline-content {
            background: #e8f5e9;
            border-left: 4px solid #27ae60;
        }

        .timeline-item.cancelled .timeline-content {
            background: #ffebee;
            border-left: 4px solid #e74c3c;
        }

        .timeline-label {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .timeline-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .timeline-time {
            font-size: 0.85rem;
            color: #999;
        }

        /* Order Items */
        .order-items-section {
            padding: 40px;
            border-top: 1px solid #e0e0e0;
        }

        .order-items-section h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 25px;
        }

        .order-items-list {
            display: grid;
            gap: 15px;
        }

        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            align-items: center;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-meta {
            display: flex;
            gap: 20px;
            font-size: 0.95rem;
            color: #666;
        }

        .item-price {
            text-align: right;
            font-size: 1.2rem;
            font-weight: 600;
            color: #e74c3c;
        }

        /* Order Summary */
        .order-summary {
            padding: 40px;
            border-top: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-item {
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .summary-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e74c3c;
            margin-top: 20px;
        }

        .order-total-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .order-total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e74c3c;
        }

        /* Delivery Info */
        .delivery-info {
            padding: 40px;
            border-top: 1px solid #e0e0e0;
        }

        .delivery-info h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .info-box {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-box h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-box p {
            color: #333;
            margin: 5px 0;
            line-height: 1.6;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 20px;
            }

            .timeline-marker {
                left: 20px;
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .timeline-content {
                margin-left: 70px;
            }

            .order-item {
                flex-direction: column;
            }

            .item-price {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="tracking-container">
        <a href="orders.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>

        <div class="tracking-header">
            <h1><i class="fas fa-map-pin"></i> Track Order #<?php echo $orderId; ?></h1>
            <p>Real-time order tracking and delivery updates</p>
        </div>

        <!-- Order Tracking Card -->
        <div class="order-tracking-card">
            <div class="order-tracking-header">
                <div class="order-info-row">
                    <div class="order-info-item">
                        <h3>Order Number</h3>
                        <p>#<?php echo $order['id']; ?></p>
                    </div>
                    <div class="order-info-item">
                        <h3>Order Date</h3>
                        <p><?php echo date('d M, Y', strtotime($order['order_date'])); ?></p>
                    </div>
                    <div class="order-info-item">
                        <h3>Total Amount</h3>
                        <p>₹<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>
                    <div class="order-info-item">
                        <h3>Payment Status</h3>
                        <p><?php echo ucfirst($order['payment_status']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="timeline-section">
                <h2 style="margin-bottom: 30px; font-size: 1.5rem; color: #333;">
                    <i class="fas fa-tasks"></i> Order Progress
                </h2>

                <!-- Progress Bar -->
                <div style="margin-bottom: 40px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="font-weight: 600; color: #333;">Overall Progress</span>
                        <span style="font-weight: 600; color: #667eea;" id="progressPercent">
                            <?php 
                                $statusOrder = ['pending', 'processing', 'delivered'];
                                $currentIndex = array_search($normalizedStatus, $statusOrder);
                                $progress = $currentIndex !== false ? (($currentIndex + 1) / count($statusOrder)) * 100 : 0;
                                if ($rawStatus == 'cancelled') $progress = 100;
                                echo round($progress) . '%';
                            ?>
                        </span>
                    </div>
                    <div style="background: #e0e0e0; height: 8px; border-radius: 10px; overflow: hidden;">
                        <div id="progressBar" style="height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); width: <?php 
                            $statusOrder = ['pending', 'processing', 'delivered'];
                            $currentIndex = array_search($normalizedStatus, $statusOrder);
                            $progress = $currentIndex !== false ? (($currentIndex + 1) / count($statusOrder)) * 100 : 0;
                            if ($rawStatus == 'cancelled') $progress = 100;
                            echo $progress;
                        ?>%; transition: width 0.5s ease;">
                        </div>
                    </div>
                </div>

                <div class="timeline">
                    <?php foreach ($statusOrder as $index => $status): 
                        $isActive = $index == $currentStatusIndex;
                        $isCompleted = $index < $currentStatusIndex && $currentStatusIndex != -1;
                        $isCancelled = $order['status'] == 'cancelled';
                    ?>
                        <div class="timeline-item <?php echo $isCompleted ? 'completed' : ($isActive ? 'active' : ''); ?> <?php echo $isCancelled ? 'cancelled' : ''; ?>">
                            <div class="timeline-marker">
                                <i class="fas fa-<?php echo $statusTimeline[$status]['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-label"><?php echo $statusTimeline[$status]['label']; ?></div>
                                <div class="timeline-description"><?php echo $statusTimeline[$status]['description']; ?></div>
                                <?php if ($isActive || $isCompleted): ?>
                                    <div class="timeline-time">
                                        <?php 
                                            if ($status == 'pending') {
                                                echo date('d M, Y h:i A', strtotime($order['order_date']));
                                            } elseif ($status == 'processing' && !empty($order['updated_at'])) {
                                                echo 'Processing started...';
                                            } elseif ($status == 'delivered' && !empty($order['updated_at'])) {
                                                echo 'Delivered on ' . date('d M, Y h:i A', strtotime($order['updated_at']));
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($order['status'] == 'cancelled'): ?>
                        <div class="timeline-item cancelled">
                            <div class="timeline-marker">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-label"><?php echo $statusTimeline['cancelled']['label']; ?></div>
                                <div class="timeline-description"><?php echo $statusTimeline['cancelled']['description']; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items-section">
                <h2><i class="fas fa-shopping-bag"></i> Order Items</h2>
                <div class="order-items-list">
                    <?php foreach ($items as $item): 
                        $imagePath = '../assets/images/default-food.jpg';
                        if (!empty($item['image_path'])) {
                            $possiblePaths = [
                                '../' . $item['image_path'],
                                '../assets/uploads/foods/' . basename($item['image_path']),
                                '../assets/images/foods/' . basename($item['image_path']),
                            ];
                            foreach ($possiblePaths as $path) {
                                if (file_exists($path)) {
                                    $imagePath = $path;
                                    break;
                                }
                            }
                        }
                    ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($item['food_name']); ?>"
                                     onerror="this.src='../assets/images/default-food.jpg'">
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                                <div class="item-meta">
                                    <span>Qty: <strong><?php echo $item['quantity']; ?></strong></span>
                                    <span>Unit Price: <strong>₹<?php echo number_format($item['unit_price'], 2); ?></strong></span>
                                </div>
                            </div>
                            <div class="item-price">
                                ₹<?php echo number_format($item['total_price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Subtotal</div>
                        <div class="summary-value">₹<?php echo number_format($order['subtotal'], 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Delivery Charges</div>
                        <div class="summary-value"><?php echo $order['delivery_charge'] == 0 ? 'FREE' : '₹' . number_format($order['delivery_charge'], 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">GST</div>
                        <div class="summary-value">₹<?php echo number_format($order['tax_amount'], 2); ?></div>
                    </div>
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="summary-item">
                        <div class="summary-label">Discount</div>
                        <div class="summary-value">-₹<?php echo number_format($order['discount_amount'], 2); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="order-total">
                    <span class="order-total-label">Total Amount Paid</span>
                    <span class="order-total-amount">₹<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="delivery-info">
                <h2><i class="fas fa-map-marker-alt"></i> Delivery Details</h2>
                <div class="info-grid">
                    <div class="info-box">
                        <h3><i class="fas fa-user"></i> Customer Name</h3>
                        <p><?php echo htmlspecialchars($order['user_name']); ?></p>
                    </div>
                    <div class="info-box">
                        <h3><i class="fas fa-phone"></i> Phone Number</h3>
                        <p><?php echo htmlspecialchars($order['phone']); ?></p>
                    </div>
                    <div class="info-box">
                        <h3><i class="fas fa-envelope"></i> Email Address</h3>
                        <p><?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                    <div class="info-box" style="grid-column: 1 / -1;">
                        <h3><i class="fas fa-map-pin"></i> Delivery Address</h3>
                        <p><?php echo htmlspecialchars($order['address'] ?? 'Not provided'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Real-time order tracking
        const orderId = new URLSearchParams(window.location.search).get('id');
        let lastStatus = '<?php echo $order['status']; ?>';

        function updateOrderStatus() {
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            fetch(`api-order-tracking.php?id=${orderId}&t=${timestamp}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Check if status has changed
                        if (data.status !== lastStatus) {
                            lastStatus = data.status;
                            console.log('Status changed to: ' + data.status);
                            
                            // Show notification
                            if (data.status === 'delivered') {
                                showStatusNotification('Your order has been delivered! ✓', 'success');
                            } else if (data.status === 'processing') {
                                showStatusNotification('Your order is being prepared...', 'info');
                            } else if (data.status === 'cancelled') {
                                showStatusNotification('Your order has been cancelled', 'error');
                            } else if (data.status === 'pending') {
                                showStatusNotification('Order confirmed, waiting for preparation', 'info');
                            }
                            
                            // Reload page immediately to reflect changes
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        }
                    }
                })
                .catch(error => console.error('Error checking order status:', error));
        }

        function showStatusNotification(message, type) {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? '#27ae60' : (type === 'error' ? '#e74c3c' : '#3498db');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s ease;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'times-circle' : 'info-circle')}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Check for status updates every 5 seconds (more frequent)
        setInterval(updateOrderStatus, 5000);
        
        // Check immediately on page load
        setTimeout(updateOrderStatus, 1000);

        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
