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

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    
    // First check if user exists
    $checkQuery = "SELECT role FROM users WHERE id = $userId";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $user = mysqli_fetch_assoc($checkResult);
        
        if ($user && isset($user['role']) && $user['role'] != 'admin') {
            $deleteQuery = "DELETE FROM users WHERE id = $userId";
            
            if (mysqli_query($conn, $deleteQuery)) {
                $message = "User deleted successfully";
                // Refresh to show updated list
                header("Location: users.php?msg=user_deleted");
                exit();
            } else {
                $error = "Failed to delete user: " . mysqli_error($conn);
            }
        } else {
            $error = "Cannot delete admin users";
        }
    } else {
        $error = "User not found";
    }
}

// Handle role update
if (isset($_POST['update_role'])) {
    $userId = intval($_POST['user_id']);
    $newRole = mysqli_real_escape_string($conn, $_POST['role']);
    
    $updateQuery = "UPDATE users SET role = '$newRole' WHERE id = $userId";
    
    if (mysqli_query($conn, $updateQuery)) {
        $message = "User role updated successfully";
        header("Location: users.php?msg=role_updated");
        exit();
    } else {
        $error = "Failed to update user role: " . mysqli_error($conn);
    }
}

// Get users with filters
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$searchFilter = isset($_GET['search']) ? $_GET['search'] : '';

$whereClause = "WHERE 1=1";
if ($roleFilter) {
    $whereClause .= " AND u.role = '" . mysqli_real_escape_string($conn, $roleFilter) . "'";
}
if ($searchFilter) {
    $searchFilter = mysqli_real_escape_string($conn, $searchFilter);
    $whereClause .= " AND (u.name LIKE '%$searchFilter%' OR u.email LIKE '%$searchFilter%' OR u.phone LIKE '%$searchFilter%')";
}

// Get all users with order counts
$usersQuery = "SELECT u.* FROM users u $whereClause ORDER BY u.created_at DESC";
$usersResult = mysqli_query($conn, $usersQuery);

if (!$usersResult) {
    die("Query failed: " . mysqli_error($conn));
}

// Get statistics
$statsQuery = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
                SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users,
                (SELECT COUNT(DISTINCT user_id) FROM orders) as users_with_orders
               FROM users";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = $statsResult ? mysqli_fetch_assoc($statsResult) : [];

// Check for success messages from redirect
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'user_deleted':
            $message = 'User deleted successfully';
            break;
        case 'role_updated':
            $message = 'User role updated successfully';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Main Layout */
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
        
        /* Header */
        .admin-header {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        
        /* Filters Section */
        .filters-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            min-width: 300px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }
        
        .filter-group input[type="text"],
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        }
        
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 15px;
            vertical-align: middle;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-details strong {
            display: block;
            color: #334155;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .user-details small {
            display: block;
            color: #64748b;
            font-size: 13px;
            line-height: 1.3;
        }
        
        /* Role Badges */
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        
        .role-user {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .role-admin {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Order Count & Total Spent */
        .order-count,
        .total-spent {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .order-count i,
        .total-spent i {
            color: #666;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background-color: #fef3c7;
            color: #92400e;
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Role Dropdown - FIXED */
        .role-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .role-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 6px;
            z-index: 1000;
            min-width: 150px;
            top: 100%;
            left: 0;
            overflow: hidden;
        }
        
        .role-option {
            display: block;
            width: 100%;
            padding: 10px 15px;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            color: #475569;
            transition: all 0.3s ease;
        }
        
        .role-option:hover {
            background-color: #f8fafc;
        }
        
        .role-option.active {
            background-color: #667eea;
            color: white;
        }
        
        .role-dropdown-content form {
            margin: 0;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Responsive */
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
            
            .section-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px;
                font-size: 14px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 24px;
            }
            
            .section-header h2 {
                font-size: 20px;
            }
            
            .stat-info h3 {
                font-size: 24px;
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
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    <p>Manage registered users and their permissions</p>
                </div>
            </div>

            <?php if($message): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-error" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users'] ?? 0; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['admin_users'] ?? 0; ?></h3>
                        <p>Admin Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['regular_users'] ?? 0; ?></h3>
                        <p>Regular Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #9C27B0;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['users_with_orders'] ?? 0; ?></h3>
                        <p>Active Customers</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Search:</label>
                            <input type="text" name="search" placeholder="Search by name, email, or phone" 
                                   value="<?php echo htmlspecialchars($searchFilter); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label>Role:</label>
                            <select name="role" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="user" <?php echo $roleFilter == 'user' ? 'selected' : ''; ?>>Regular User</option>
                                <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="users.php" class="btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> All Users</h2>
                    <span>Showing <?php echo mysqli_num_rows($usersResult); ?> users</span>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Info</th>
                                <th>Role</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($usersResult) > 0): ?>
                                <?php while($user = mysqli_fetch_assoc($usersResult)): 
                                    // Get order count for this user
                                    $orderCountQuery = "SELECT COUNT(*) as total_orders FROM orders WHERE user_id = " . $user['id'];
                                    $orderCountResult = mysqli_query($conn, $orderCountQuery);
                                    $orderCount = $orderCountResult ? mysqli_fetch_assoc($orderCountResult) : ['total_orders' => 0];
                                    
                                    // Get total spent for this user
                                    $totalSpentQuery = "SELECT SUM(total_price) as total_spent FROM orders WHERE user_id = " . $user['id'] . " AND status = 'delivered'";
                                    $totalSpentResult = mysqli_query($conn, $totalSpentQuery);
                                    $totalSpent = $totalSpentResult ? mysqli_fetch_assoc($totalSpentResult) : ['total_spent' => 0];
                                ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <small><?php echo $user['email']; ?></small>
                                                <small><?php echo $user['phone'] ?: 'No phone'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-count">
                                            <i class="fas fa-shopping-bag"></i>
                                            <span><?php echo $orderCount['total_orders']; ?> orders</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="total-spent">
                                            <i class="fas fa-rupee-sign"></i>
                                            <span>₹<?php echo number_format($totalSpent['total_spent'] ?? 0, 2); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($orderCount['total_orders'] > 0): ?>
                                        <span class="status-badge status-active">
                                            Active
                                        </span>
                                        <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            Inactive
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Role Change Dropdown -->
                                            <div class="role-dropdown" id="roleDropdown<?php echo $user['id']; ?>">
                                                <button type="button" class="btn-action btn-edit" title="Change Role">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                                <div class="role-dropdown-content">
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to change the role?')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="update_role" value="user" 
                                                                class="role-option <?php echo $user['role'] == 'user' ? 'active' : ''; ?>">
                                                            <i class="fas fa-user"></i> Regular User
                                                        </button>
                                                        <button type="submit" name="update_role" value="admin"
                                                                class="role-option <?php echo $user['role'] == 'admin' ? 'active' : ''; ?>">
                                                            <i class="fas fa-user-tie"></i> Admin
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <?php if ($user['role'] != 'admin'): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" 
                                               class="btn-action btn-delete" title="Delete User"
                                               onclick="return confirm('Are you sure you want to delete user <?php echo htmlspecialchars(addslashes($user['name'])); ?>? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <i class="fas fa-users"></i>
                                        No users found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        
        // Role dropdown functionality
        document.querySelectorAll('.role-dropdown').forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const content = dropdown.querySelector('.role-dropdown-content');
            
            // Show dropdown when clicking the button
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all other dropdowns
                document.querySelectorAll('.role-dropdown-content').forEach(otherContent => {
                    if (otherContent !== content) {
                        otherContent.style.display = 'none';
                    }
                });
                
                // Toggle current dropdown
                content.style.display = content.style.display === 'block' ? 'none' : 'block';
            });
            
            // Keep dropdown open when clicking inside
            content.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
            // Handle role option clicks
            content.querySelectorAll('.role-option').forEach(option => {
                option.addEventListener('click', (e) => {
                    // The form will submit automatically since it's a submit button
                });
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.role-dropdown')) {
                document.querySelectorAll('.role-dropdown-content').forEach(content => {
                    content.style.display = 'none';
                });
            }
        });
        
        // Close dropdown with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.role-dropdown-content').forEach(content => {
                    content.style.display = 'none';
                });
            }
        });
    });
    
    // Function to change role
    function changeRole(userId, role) {
        if (confirm('Are you sure you want to change the role?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            const roleInput = document.createElement('input');
            roleInput.type = 'hidden';
            roleInput.name = 'role';
            roleInput.value = role;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'update_role';
            submitInput.value = '1';
            
            form.appendChild(userIdInput);
            form.appendChild(roleInput);
            form.appendChild(submitInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>