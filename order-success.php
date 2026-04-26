<?php
session_start();
require_once '../includes/config.php';

// Check if order was placed
if (!isset($_SESSION['order_id'])) {
    header('Location: menu.php');
    exit();
}

$orderId = $_SESSION['order_id'];
$orderNumber = $_SESSION['order_number'];
$orderTotal = $_SESSION['order_total'];

$conn = getDBConnection();

// Get order details from database
$orderQuery = "SELECT * FROM orders WHERE id = $orderId";
$orderResult = mysqli_query($conn, $orderQuery);
$order = mysqli_fetch_assoc($orderResult);

// Get order items
$itemsQuery = "SELECT * FROM order_items WHERE order_id = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);

// Clear session data
unset($_SESSION['order_id']);
unset($_SESSION['order_number']);
unset($_SESSION['order_total']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        /* Add space for fixed header */
        .success-container {
            max-width: 700px;
            margin: 80px auto 30px auto;
            padding: 0 15px;
        }
        
        .success-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 25px;
            text-align: center;
        }
        
        .success-icon {
            width: 60px;
            height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
        }
        
        .success-card h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.6rem;
            font-weight: 600;
        }
        
        .success-card p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 18px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: left;
            border-left: 3px solid #007bff;
        }
        
        .order-details h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .detail-item {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            font-size: 0.82rem;
            margin-bottom: 3px;
        }
        
        .detail-value {
            color: #333;
            font-size: 0.92rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #ffc107;
            color: #333;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.78rem;
        }
        
        .order-items {
            margin: 18px 0;
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .order-items h3 {
            color: #333;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .item-list {
            max-height: 160px;
            overflow-y: auto;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.88rem;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 2;
            color: #333;
        }
        
        .item-qty {
            flex: 1;
            text-align: center;
            color: #666;
        }
        
        .item-price {
            flex: 1;
            text-align: right;
            color: #333;
            font-weight: 600;
        }
        
        .next-steps {
            background: #e8f5e9;
            padding: 18px;
            border-radius: 6px;
            margin: 18px 0;
            text-align: left;
        }
        
        .next-steps h2 {
            color: #2e7d32;
            margin-bottom: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .steps-list {
            list-style: none;
            padding-left: 0;
        }
        
        .steps-list li {
            margin-bottom: 10px;
            color: #555;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.88rem;
            line-height: 1.4;
        }
        
        .steps-list li i {
            color: #28a745;
            margin-top: 2px;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 18px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            min-width: 160px;
        }
        
        .btn-download {
            background: #007bff;
            color: white;
        }
        
        .btn-download:hover {
            background: #0056b3;
        }
        
        .btn-menu {
            background: #28a745;
            color: white;
        }
        
        .btn-menu:hover {
            background: #1e7e34;
        }
        
        .btn-home {
            background: #6c757d;
            color: white;
        }
        
        .btn-home:hover {
            background: #545b62;
        }

        .btn-track {
            background: #ff6b6b;
            color: white;
        }

        .btn-track:hover {
            background: #ee5a52;
        }
        
        .contact-info {
            margin-top: 20px;
            padding: 12px;
            background: #f0f8ff;
            border-radius: 5px;
            text-align: center;
            color: #555;
            font-size: 0.82rem;
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 70px auto 20px auto;
                max-width: 95%;
            }
            
            .success-card {
                padding: 20px 15px;
            }
            
            .success-card h1 {
                font-size: 1.4rem;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
                padding: 10px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .success-container {
                margin: 65px auto 15px auto;
                padding: 0 10px;
            }
            
            .success-card {
                padding: 18px 12px;
            }
            
            .success-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .success-card h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. Your delicious Bihari food is being prepared with care.</p>
            
            <div class="order-details">
                <h2><i class="fas fa-receipt"></i> Order Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Order Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Order Date</div>
                        <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Payment Method</div>
                        <div class="detail-value">Cash on Delivery</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Total Amount</div>
                        <div class="detail-value" style="color: #28a745; font-weight: 700; font-size: 1rem;">
                            ₹<?php echo number_format($order['total_price'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Order Status</div>
                        <div class="detail-value">
                            <span class="status-badge"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Estimated Delivery</div>
                        <div class="detail-value">
                            <?php echo date('g:i A', strtotime($order['estimated_delivery_time'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (mysqli_num_rows($itemsResult) > 0): ?>
            <div class="order-items">
                <h3><i class="fas fa-utensils"></i> Your Order Items</h3>
                <div class="item-list">
                    <?php while($item = mysqli_fetch_assoc($itemsResult)): ?>
                    <div class="item-row">
                        <div class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></div>
                        <div class="item-qty">x<?php echo $item['quantity']; ?></div>
                        <div class="item-price">₹<?php echo number_format($item['total_price'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h2><i class="fas fa-list-check"></i> What happens next?</h2>
                <ul class="steps-list">
                    <li><i class="fas fa-utensils"></i> <strong>Food Preparation:</strong> Our chefs are preparing your authentic Bihari dishes</li>
                    <li><i class="fas fa-clock"></i> <strong>Delivery Time:</strong> Estimated delivery by <?php echo date('g:i A', strtotime($order['estimated_delivery_time'])); ?></li>
                    <li><i class="fas fa-phone"></i> <strong>Confirmation Call:</strong> Our delivery partner will call you before arrival</li>
                    <li><i class="fas fa-money-bill-wave"></i> <strong>Payment:</strong> Pay ₹<?php echo number_format($order['total_price'], 2); ?> in cash when you receive your order</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="order-tracking.php?id=<?php echo $orderId; ?>" class="btn btn-track">
                    <i class="fas fa-map-pin"></i> Track Order
                </a>
                <button onclick="downloadInvoice()" class="btn btn-download">
                    <i class="fas fa-file-invoice"></i> Download Invoice
                </button>
                <a href="menu.php" class="btn btn-menu">
                    <i class="fas fa-utensils"></i> Order More
                </a>
                <a href="../index.php" class="btn btn-home">
                    <i class="fas fa-home"></i> Go Home
                </a>
            </div>
            
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> Need help? Call: <strong>+91 1234567890</strong></p>
                <p><i class="fas fa-envelope"></i> Email: <strong>support@tastyofbihar.com</strong></p>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    function downloadInvoice() {
        // Create invoice content with smaller fonts
        const invoiceContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Invoice - <?php echo $order['order_number']; ?></title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 15px; 
                        line-height: 1.4;
                        font-size: 13px;
                    }
                    .invoice-header { 
                        text-align: center; 
                        margin-bottom: 20px;
                        border-bottom: 2px solid #333;
                        padding-bottom: 12px;
                    }
                    .invoice-header h1 { 
                        color: #333; 
                        margin-bottom: 5px;
                        font-size: 22px;
                    }
                    .invoice-header h2 {
                        color: #007bff;
                        margin-bottom: 12px;
                        font-size: 16px;
                    }
                    .company-info { 
                        text-align: center;
                        margin-bottom: 18px;
                        color: #666;
                        font-size: 12px;
                    }
                    .order-info {
                        margin-bottom: 18px;
                        background: #f5f5f5;
                        padding: 12px;
                        border-radius: 4px;
                        font-size: 12px;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-bottom: 18px;
                        font-size: 12px;
                    }
                    th, td { 
                        padding: 6px 8px; 
                        text-align: left; 
                        border-bottom: 1px solid #ddd;
                    }
                    th { 
                        background: #333; 
                        color: white;
                        font-weight: 600;
                        font-size: 12px;
                    }
                    .total-row { 
                        font-weight: bold; 
                        background: #f8f9fa;
                        font-size: 13px;
                    }
                    .grand-total {
                        background: #e8f5e9 !important;
                        color: #28a745;
                        font-size: 14px;
                    }
                    .footer { 
                        margin-top: 25px; 
                        text-align: center; 
                        color: #666;
                        font-size: 11px;
                        border-top: 1px solid #ddd;
                        padding-top: 12px;
                    }
                    .highlight {
                        background: #fff3cd;
                        padding: 10px;
                        border-radius: 4px;
                        margin: 15px 0;
                        border-left: 3px solid #ffc107;
                        font-size: 12px;
                    }
                </style>
            </head>
            <body>
                <div class="invoice-header">
                    <h1>TASTY OF BIHAR</h1>
                    <p style="color: #666; font-size: 13px;">Authentic Bihari Cuisine</p>
                    <h2>INVOICE</h2>
                </div>
                
                <div class="company-info">
                    <p><strong>Invoice Number:</strong> <?php echo $order['order_number']; ?></p>
                    <p><strong>Date:</strong> ${new Date().toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' })}</p>
                    <p><strong>Time:</strong> ${new Date().toLocaleTimeString('en-IN', {hour: '2-digit', minute:'2-digit'})}</p>
                </div>
                
                <div class="order-info">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p><strong>Payment:</strong> Cash on Delivery</p>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        mysqli_data_seek($itemsResult, 0);
                        while($item = mysqli_fetch_assoc($itemsResult)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($item['food_name']) . "</td>";
                            echo "<td>" . $item['quantity'] . "</td>";
                            echo "<td>₹" . number_format($item['unit_price'], 2) . "</td>";
                            echo "<td>₹" . number_format($item['total_price'], 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Subtotal:</td>
                            <td>₹<?php echo number_format($order['subtotal'], 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Delivery:</td>
                            <td><?php echo $order['delivery_charge'] == 0 ? 'FREE' : '₹' . number_format($order['delivery_charge'], 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Tax (5%):</td>
                            <td>₹<?php echo number_format($order['tax_amount'], 2); ?></td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Discount:</td>
                            <td style="color: #28a745;">-₹<?php echo number_format($order['discount_amount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row grand-total">
                            <td colspan="3" style="text-align: right;">TOTAL:</td>
                            <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="highlight">
                    <p><strong>Payment:</strong> ₹<?php echo number_format($order['total_price'], 2); ?> in CASH on delivery</p>
                    <p><strong>Delivery:</strong> By <?php echo date('g:i A', strtotime($order['estimated_delivery_time'])); ?></p>
                </div>
                
                <div class="footer">
                    <p><strong>TASTY OF BIHAR</strong></p>
                    <p>📞 +91 1234567890 | ✉️ info@tastyofbihar.com</p>
                    <p>Thank you for your order!</p>
                </div>
            </body>
            </html>
        `;
        
        // Open in new window and print
        const invoiceWindow = window.open('', '_blank');
        invoiceWindow.document.write(invoiceContent);
        invoiceWindow.document.close();
        
        // Auto-print after a short delay
        setTimeout(() => {
            invoiceWindow.print();
        }, 300);
    }
    
    // Auto-focus on download button
    document.addEventListener('DOMContentLoaded', function() {
        const downloadBtn = document.querySelector('.btn-download');
        downloadBtn.focus();
    });
    </script>
</body>
</html>