<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Initialize messages
$message = '';
$error = '';

// Handle status update - validate and map UI status to DB enum if necessary
if (isset($_POST['update_order_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Allowed UI statuses
    $validUiStatuses = ['pending', 'processing', 'delivered', 'cancelled'];

    if (!in_array($newStatus, $validUiStatuses)) {
        $error = "Invalid status selected: $newStatus";
    } else {
        // Determine allowed values in DB enum for `orders`.`status`
        $colRes = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
        $colRow = $colRes ? mysqli_fetch_assoc($colRes) : null;
        $allowed = [];
        if ($colRow && isset($colRow['Type']) && preg_match("/enum\\((.*)\\)/i", $colRow['Type'], $m)) {
            $parts = explode(',', $m[1]);
            foreach ($parts as $p) {
                $allowed[] = trim($p, "'\"");
            }
        }

        // Map UI 'processing' to DB 'preparing' if DB uses that value
        $storeStatus = $newStatus;
        if ($newStatus === 'processing') {
            if (!in_array('processing', $allowed) && in_array('preparing', $allowed)) {
                $storeStatus = 'preparing';
            }
        }

        // Final validation against DB allowed list (if available)
        if (!empty($allowed) && !in_array($storeStatus, $allowed)) {
            $error = "Status '{$storeStatus}' is not supported by the database.";
        } else {
            $storeStatusEscaped = mysqli_real_escape_string($conn, $storeStatus);
            $updateQuery = "UPDATE orders SET status = '$storeStatusEscaped', updated_at = NOW() WHERE id = $orderId";

            if (mysqli_query($conn, $updateQuery)) {
                $message = "Order #$orderId status updated to " . ucfirst($newStatus);
                // Refresh page to show updated status
                header("Location: orders.php?msg=status_updated&id=$orderId");
                exit();
            } else {
                $error = "Failed to update order status: " . mysqli_error($conn);
            }
        }
    }
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $orderId = intval($_GET['delete']);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete order items first
        $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = $orderId";
        mysqli_query($conn, $deleteItemsQuery);

        // Delete order
        $deleteOrderQuery = "DELETE FROM orders WHERE id = $orderId";
        mysqli_query($conn, $deleteOrderQuery);

        mysqli_commit($conn);
        $message = "Order #$orderId deleted successfully";
        header("Location: orders.php?msg=order_deleted");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Failed to delete order: " . $e->getMessage();
    }
}

// Get orders with filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

$whereClause = "WHERE 1=1";
if ($statusFilter) {
    $whereClause .= " AND o.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}
if ($dateFilter) {
    $whereClause .= " AND DATE(o.order_date) = '" . mysqli_real_escape_string($conn, $dateFilter) . "'";
}

$ordersQuery = "SELECT o.*, u.name as user_name, u.email, u.phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                $whereClause 
                ORDER BY o.order_date DESC";
$ordersResult = mysqli_query($conn, $ordersQuery);

// Get statistics
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_price) as total_revenue
               FROM orders";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Check for success messages from redirect
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'status_updated':
            $message = 'Order status updated successfully';
            break;
        case 'order_deleted':
            $message = 'Order deleted successfully';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-info p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        /* Filters */
        .filters-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-form {
            margin: 0;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Dashboard Section */
        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-header span {
            color: #666;
            font-size: 14px;
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 20px;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .data-table thead {
            background-color: #f8fafc;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 14px;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 15px;
            vertical-align: middle;
        }

        .order-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .order-link:hover {
            text-decoration: underline;
        }

        .customer-info strong {
            display: block;
            color: #334155;
            margin-bottom: 3px;
        }

        .customer-info small {
            color: #64748b;
            font-size: 12px;
            display: block;
            line-height: 1.4;
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

        .payment-method {
            font-size: 14px;
        }

        .payment-method small {
            color: #64748b;
            font-size: 12px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-view {
            background-color: #3b82f6;
            color: white;
        }

        .btn-edit {
            background-color: #10b981;
            color: white;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Status Form */
        .status-form {
            margin: 0;
            display: inline-block;
        }

        .status-form select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-form select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .status-form button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 5px;
            transition: all 0.3s ease;
        }

        .status-form button:hover {
            background: #2563eb;
        }

        /* Export Section */
        .export-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .export-section h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .export-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-export {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
                gap: 15px;
            }

            .filter-group {
                min-width: 100%;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .export-options {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1><i class="fas fa-shopping-bag"></i> Order Management</h1>
                    <p>View and manage customer orders</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Order Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['processing_orders']; ?></h3>
                        <p>Processing</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Order Status:</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $statusFilter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="delivered" <?php echo $statusFilter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Order Date:</label>
                            <input type="date" name="date" value="<?php echo $dateFilter; ?>" onchange="this.form.submit()">
                        </div>

                        <div class="filter-group">
                            <a href="orders.php" class="btn-secondary">Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> All Orders</h2>
                    <span>Showing <?php echo mysqli_num_rows($ordersResult); ?> orders</span>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>GST</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($ordersResult) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                                    <tr>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="order-link">
                                                #<?php echo $order['id']; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <strong><?php echo htmlspecialchars($order['user_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                                                <small><?php echo htmlspecialchars($order['phone']); ?></small>
                                            </div>
                                        </td>
                                        <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>₹<?php echo number_format($order['tax_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="payment-method">
                                                <?php echo ucfirst($order['payment_method']); ?><br>
                                                <small><?php echo ucfirst($order['payment_status']); ?></small>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y h:i A', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>"
                                                    class="btn-action btn-view" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <!-- Status Update Form - Simple -->
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="update_order_status" value="1">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; cursor: pointer;">
                                                        <option value="">-- Select Status --</option>
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo ($order['status'] == 'processing' || $order['status'] == 'preparing') ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>

                                                <a href="?delete=<?php echo $order['id']; ?>"
                                                    class="btn-action btn-delete" title="Delete Order"
                                                    onclick="return confirm('Are you sure you want to delete order #<?php echo $order['id']; ?>? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
                                        No orders found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Options -->
            <div class="export-section">
                <h3><i class="fas fa-download"></i> Export Orders</h3>
                <div class="export-options">
                    <a href="export-orders.php?type=pdf&status=<?php echo $statusFilter; ?>&date=<?php echo $dateFilter; ?>"
                        class="btn-export">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.maxHeight = '0';
                alert.style.marginBottom = '0';
                alert.style.paddingTop = '0';
                alert.style.paddingBottom = '0';
                alert.style.overflow = 'hidden';
                
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
    </script>

</body>

</html>