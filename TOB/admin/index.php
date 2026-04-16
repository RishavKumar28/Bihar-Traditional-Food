<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Check if admin is logged in
if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Get statistics
$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'"))['count'];
$totalFoods = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM foods"))['count'];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as revenue FROM orders WHERE status='delivered'"))['revenue'] ?? 0;

// Recent orders
$recentOrdersQuery = "SELECT o.*, u.name as user_name FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.order_date DESC LIMIT 5";
$recentOrders = mysqli_query($conn, $recentOrdersQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bihar Food</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-utensils"></i> Bihar Food Admin</h2>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="foods.php"><i class="fas fa-hamburger"></i> Food Items</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <p>Welcome, <?php echo $_SESSION['admin_username']; ?></p>
                </div>
                <div class="header-right">
                    <span class="admin-date"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalOrders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Registered Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalFoods; ?></h3>
                        <p>Food Items</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($totalRevenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Recent Orders</h2>
                    <a href="orders.php" class="view-all">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = mysqli_fetch_assoc($recentOrders)): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['user_name']; ?></td>
                                <td>₹<?php echo $order['total_price']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="actions-grid">
                    <a href="foods.php?action=add" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add New Food</span>
                    </a>
                    <a href="orders.php" class="action-card">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Process Orders</span>
                    </a>
                    <a href="reports.php" class="action-card">
                        <i class="fas fa-chart-line"></i>
                        <span>View Reports</span>
                    </a>
                    <a href="feedback.php" class="action-card">
                        <i class="fas fa-comment-dots"></i>
                        <span>Check Feedback</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

