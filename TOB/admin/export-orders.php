<?php
// export-orders.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'pdf';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Build WHERE clause
$whereClause = "WHERE 1=1";
if ($statusFilter) {
    $whereClause .= " AND o.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}
if ($dateFilter) {
    $whereClause .= " AND DATE(o.order_date) = '" . mysqli_real_escape_string($conn, $dateFilter) . "'";
}

// Get orders data
$ordersQuery = "SELECT o.*, u.name as user_name, u.email, u.phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                $whereClause 
                ORDER BY o.order_date DESC";
$ordersResult = mysqli_query($conn, $ordersQuery);

// Get statistics
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                SUM(tax_amount) as total_gst
               FROM orders o $whereClause";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

if ($type == 'pdf') {
    // Simple HTML to PDF (browser will print as PDF)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Orders Report - Bihar Food</title>
        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 20mm;
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #4CAF50;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #4CAF50;
                margin: 0 0 10px 0;
            }
            .header p {
                margin: 5px 0;
                color: #666;
            }
            .summary {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .summary h2 {
                margin-top: 0;
                color: #333;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            table th {
                background: #4CAF50;
                color: white;
                font-weight: bold;
                padding: 12px;
                text-align: left;
            }
            table td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            table tr:nth-child(even) {
                background: #f9f9f9;
            }
            table tr:hover {
                background: #f5f5f5;
            }
            .status {
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
                display: inline-block;
            }
            .status-pending { background: #ff9800; color: white; }
            .status-processing { background: #2196F3; color: white; }
            .status-delivered { background: #4CAF50; color: white; }
            .status-cancelled { background: #f44336; color: white; }
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #666;
                font-size: 12px;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            .no-print {
                text-align: center;
                margin: 20px 0;
            }
            .print-btn {
                background: #4CAF50;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                margin: 10px;
            }
            .print-btn:hover {
                background: #45a049;
            }
            .back-btn {
                background: #6c757d;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                text-decoration: none;
                display: inline-block;
                margin: 10px;
            }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print as PDF
            </button>
            <a href="orders.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <div class="header">
            <h1>Bihar Food - Orders Report</h1>
            <p>Report Generated: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Status: <?php echo $statusFilter ? ucfirst($statusFilter) : 'All'; ?> | 
               Date: <?php echo $dateFilter ? $dateFilter : 'All Dates'; ?></p>
        </div>
        
        <div class="summary">
            <h2>Summary</h2>
            <p><strong>Total Orders:</strong> <?php echo $stats['total_orders']; ?></p>
            <p><strong>Total Revenue:</strong> ₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
            <p><strong>Total GST Collected:</strong> ₹<?php echo number_format($stats['total_gst'] ?? 0, 2); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Tax</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Order Date</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalAmount = 0;
                if (mysqli_num_rows($ordersResult) > 0): 
                    while($order = mysqli_fetch_assoc($ordersResult)): 
                        $totalAmount += $order['total_price'];
                ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($order['user_name']); ?></strong>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($order['email']); ?><br>
                        <?php echo htmlspecialchars($order['phone']); ?>
                    </td>
                    <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                    <td>₹<?php echo number_format($order['tax_amount'], 2); ?></td>
                    <td>
                        <span class="status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo ucfirst($order['payment_method']); ?><br>
                        <small><?php echo ucfirst($order['payment_status']); ?></small>
                    </td>
                    <td><?php echo date('d/m/Y h:i A', strtotime($order['order_date'])); ?></td>
                    <td style="max-width: 200px;"><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 50)); ?>...</td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">
                        No orders found for the selected filters.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <?php if (mysqli_num_rows($ordersResult) > 0): ?>
            <tfoot>
                <tr style="background: #e8f5e9;">
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                    <td style="font-weight: bold;">₹<?php echo number_format($totalAmount, 2); ?></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        
        <div class="footer">
            <p>Generated by Bihar Food Admin Panel</p>
            <p>This is a computer-generated report. No signature is required.</p>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        <script>
            // Auto print after page loads (optional)
            window.onload = function() {
                // Optional: Auto print after 1 second
                // setTimeout(function() {
                //     window.print();
                // }, 1000);
            };
            
            // After print, go back to orders page
            window.onafterprint = function() {
                // Optional: Redirect after printing
                // window.location.href = 'orders.php';
            };
        </script>
    </body>
    </html>
    <?php
    
} elseif ($type == 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_report_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV Header
    fputcsv($output, array(
        'Order ID',
        'Customer Name', 
        'Email', 
        'Phone', 
        'Amount (₹)', 
        'Status',
        'Payment Method', 
        'Payment Status', 
        'Order Date',
        'Delivery Address'
    ));
    
    // Data rows
    $totalAmount = 0;
    while($order = mysqli_fetch_assoc($ordersResult)) {
        $totalAmount += $order['total_price'];
        fputcsv($output, array(
            $order['id'],
            $order['user_name'],
            $order['email'],
            $order['phone'],
            number_format($order['total_price'], 2),
            ucfirst($order['status']),
            ucfirst($order['payment_method']),
            ucfirst($order['payment_status']),
            date('d/m/Y H:i:s', strtotime($order['order_date'])),
            $order['delivery_address']
        ));
    }
    
    // Add summary row
    fputcsv($output, array('', '', '', '', '', '', '', '', '', ''));
    fputcsv($output, array('', '', '', 'TOTAL:', '₹' . number_format($totalAmount, 2), '', '', '', '', ''));
    fputcsv($output, array('', '', '', '', '', '', '', '', '', ''));
    fputcsv($output, array('Report Generated:', date('d/m/Y H:i:s'), '', '', '', '', '', '', '', ''));
    fputcsv($output, array('Total Orders:', mysqli_num_rows($ordersResult), '', '', '', '', '', '', '', ''));
    
    fclose($output);
    
} elseif ($type == 'excel') {
    // Excel Export
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=orders_report_' . date('Ymd_His') . '.xls');
    
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body>
        <table border="1">
            <tr style="background: #4CAF50; color: white;">
                <th colspan="10" style="padding: 15px; font-size: 16px;">
                    Bihar Food - Orders Report
                </th>
            </tr>
            <tr style="background: #f0f0f0;">
                <td colspan="5"><strong>Report Date:</strong> <?php echo date('d/m/Y H:i:s'); ?></td>
                <td colspan="5"><strong>Status:</strong> <?php echo $statusFilter ? ucfirst($statusFilter) : 'All'; ?></td>
            </tr>
            <tr style="background: #f0f0f0;">
                <td colspan="10"></td>
            </tr>
            <tr style="background: #e8f5e9; font-weight: bold;">
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Amount (₹)</th>
                <th>Status</th>
                <th>Payment Method</th>
                <th>Payment Status</th>
                <th>Order Date</th>
                <th>Delivery Address</th>
            </tr>
            <?php 
            $totalAmount = 0;
            if (mysqli_num_rows($ordersResult) > 0): 
                while($order = mysqli_fetch_assoc($ordersResult)): 
                    $totalAmount += $order['total_price'];
            ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                <td><?php echo htmlspecialchars($order['email']); ?></td>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                <td><?php echo number_format($order['total_price'], 2); ?></td>
                <td><?php echo ucfirst($order['status']); ?></td>
                <td><?php echo ucfirst($order['payment_method']); ?></td>
                <td><?php echo ucfirst($order['payment_status']); ?></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($order['order_date'])); ?></td>
                <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td colspan="4" style="text-align: right;">Total:</td>
                <td>₹<?php echo number_format($totalAmount, 2); ?></td>
                <td colspan="5"></td>
            </tr>
            <?php else: ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 20px;">
                    No orders found for the selected filters.
                </td>
            </tr>
            <?php endif; ?>
            <tr style="background: #f0f0f0;">
                <td colspan="10"></td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td colspan="5"><strong>Generated By:</strong> Admin Panel</td>
                <td colspan="5"><strong>Total Orders:</strong> <?php echo mysqli_num_rows($ordersResult); ?></td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    
} else {
    echo "<h2>Invalid export type!</h2>";
    echo "<p><a href='orders.php'>Go back to orders</a></p>";
}
?>