<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="admin-logo">
        <h2><i class="fas fa-utensils"></i> Bihar Food Admin</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
    </div>
    
    <ul class="admin-menu">
        <li <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>
            <a href="index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li <?php echo $current_page == 'foods.php' ? 'class="active"' : ''; ?>>
            <a href="foods.php">
                <i class="fas fa-hamburger"></i>
                <span>Food Items</span>
            </a>
        </li>
        
        <li <?php echo $current_page == 'categories.php' ? 'class="active"' : ''; ?>>
            <a href="categories.php">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </li>
        
        <li <?php echo $current_page == 'orders.php' || $current_page == 'order-details.php' ? 'class="active"' : ''; ?>>
            <a href="orders.php">
                <i class="fas fa-shopping-bag"></i>
                <span>Orders</span>
                <?php
                $conn = getDBConnection();
                $pendingQuery = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
                $pendingResult = mysqli_query($conn, $pendingQuery);
                $pendingCount = mysqli_fetch_assoc($pendingResult)['count'];
                if ($pendingCount > 0): ?>
                <span class="menu-badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li <?php echo $current_page == 'users.php' ? 'class="active"' : ''; ?>>
            <a href="users.php">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        
        <li <?php echo $current_page == 'feedback.php' ? 'class="active"' : ''; ?>>
            <a href="feedback.php">
                <i class="fas fa-comments"></i>
                <span>Feedback</span>
                <?php
                $feedbackQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
                $feedbackResult = mysqli_query($conn, $feedbackQuery);
                $feedbackCount = mysqli_fetch_assoc($feedbackResult)['count'];
                if ($feedbackCount > 0): ?>
                <span class="menu-badge"><?php echo $feedbackCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li <?php echo $current_page == 'reports.php' ? 'class="active"' : ''; ?>>
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <p>Version 1.0.0</p>
        <p>© <?php echo date('Y'); ?> Bihar Food</p>
    </div>
</div>

<style>
.menu-badge {
    background: #e74c3c;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 0.8rem;
    margin-left: auto;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    text-align: center;
    border-top: 1px solid #34495e;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.sidebar-footer p {
    margin: 5px 0;
}
</style>