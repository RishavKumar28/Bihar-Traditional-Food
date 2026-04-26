<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$orderId = intval($_GET['id']);
$conn = getDBConnection();

// Get order details
$orderQuery = "SELECT o.*, u.name as user_name, u.email, u.phone, u.address 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = $orderId";
$orderResult = mysqli_query($conn, $orderQuery);

if (mysqli_num_rows($orderResult) == 0) {
    header('Location: orders.php');
    exit();
}

$order = mysqli_fetch_assoc($orderResult);

// Get order items
$itemsQuery = "SELECT oi.*, f.name, f.image_path, f.price as unit_price 
               FROM order_items oi 
               JOIN foods f ON oi.food_id = f.id 
               WHERE oi.order_id = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-file-invoice"></i>
                        Order #<?php echo $orderId; ?>
                        <span class="status-badge status-<?php echo $order['status']; ?>" style="font-size: 0.8rem;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </h1>
                    <p>Placed on <?php echo date('F j, Y h:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <div class="header-right">
                    <a href="orders.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>

            <div class="order-details-container">
                <div class="order-details-grid">
                    <!-- Order Summary -->
                    <div class="order-section">
                        <div class="section-header">
                            <h2><i class="fas fa-info-circle"></i> Order Summary</h2>
                        </div>
                        <div class="order-info">
                            <div class="info-row">
                                <span>Order ID:</span>
                                <strong>#<?php echo $orderId; ?></strong>
                            </div>
                            <div class="info-row">
                                <span>Order Date:</span>
                                <span><?php echo date('F j, Y h:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span>Order Status:</span>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span>Total Amount:</span>
                                <strong class="price">₹<?php echo number_format($order['total_price'], 2); ?></strong>
                            </div>
                            <div class="info-row">
                                <span>Payment Method:</span>
                                <span class="payment-method-badge">
                                    <?php echo strtoupper($order['payment_method']); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span>Payment Status:</span>
                                <span class="status-badge <?php echo $order['payment_status'] == 'completed' ? 'status-delivered' : 'status-pending'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span>Delivery Charges:</span>
                                <span><?php echo $order['delivery_charge'] == 0 ? 'FREE' : '₹' . number_format($order['delivery_charge'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span>GST:</span>
                                <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                            </div>
                            <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                            <div class="info-row">
                                <span>Discount:</span>
                                <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span>Order Total:</span>
                                <strong>₹<?php echo number_format($order['total_price'], 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="order-section">
                        <div class="section-header">
                            <h2><i class="fas fa-user"></i> Customer Information</h2>
                        </div>
                        <div class="customer-info">
                            <div class="info-row">
                                <span>Name:</span>
                                <strong><?php echo $order['user_name']; ?></strong>
                            </div>
                            <div class="info-row">
                                <span>Email:</span>
                                <a href="mailto:<?php echo $order['email']; ?>"><?php echo $order['email']; ?></a>
                            </div>
                            <div class="info-row">
                                <span>Phone:</span>
                                <a href="tel:<?php echo $order['phone']; ?>"><?php echo $order['phone']; ?></a>
                            </div>
                            <div class="info-row">
                                <span>Address:</span>
                                <span><?php echo $order['address']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="order-section">
                        <div class="section-header">
                            <h2><i class="fas fa-truck"></i> Delivery Information</h2>
                        </div>
                        <div class="delivery-info">
                            <div class="info-row">
                                <span>Delivery Address:</span>
                                <p><?php echo nl2br($order['delivery_address']); ?></p>
                            </div>
                            <?php if (isset($order['delivery_date']) && !empty($order['delivery_date'])): ?>
                                <div class="info-row">
                                    <span>Delivered On:</span>
                                    <span><?php echo date('F j, Y h:i A', strtotime($order['delivery_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($order['notes']) && !empty($order['notes'])): ?>
                                <div class="info-row">
                                    <span>Customer Notes:</span>
                                    <p><em><?php echo $order['notes']; ?></em></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f8fa;
        }

        .admin-main {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }

        .admin-header {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-header p {
            color: #666;
            font-size: 16px;
            margin-bottom: 0;
        }

        .header-right .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .header-right .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Order Details Container */
        .order-details-container {
            margin: 0;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .order-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-row span:first-child {
            color: #666;
            font-weight: 500;
            font-size: 14px;
        }

        .info-row strong {
            color: #333;
            font-size: 15px;
        }

        .price {
            color: #27ae60;
            font-size: 18px;
            font-weight: 600;
        }

        .payment-method-badge {
            background: #f1c40f;
            color: #333;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Customer Info Links */
        .customer-info a {
            color: #3b82f6;
            text-decoration: none;
        }

        .customer-info a:hover {
            text-decoration: underline;
        }

        /* Delivery Info */
        .delivery-info p {
            margin: 5px 0;
            color: #555;
            line-height: 1.5;
            font-size: 14px;
        }

        /* Order Actions */
        .order-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }

        .btn-action {
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .btn-action:hover {
            background: #2563eb;
            transform: translateY(-2px);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                padding: 20px;
            }

            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }

            .order-actions {
                flex-direction: column;
            }

            .btn-action {
                justify-content: center;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .info-row span:first-child {
                font-size: 13px;
            }
        }
    </style>
</body>

</html>