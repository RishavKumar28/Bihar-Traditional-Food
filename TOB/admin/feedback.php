<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize messages
$message = '';
$error = '';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle status update
if (isset($_POST['update_status'])) {
    $feedbackId = intval($_POST['feedback_id']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    
    $updateQuery = "UPDATE feedback SET status = '$newStatus' WHERE id = $feedbackId";
    
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: feedback.php?msg=status_updated");
        exit();
    } else {
        $error = "Failed to update feedback status: " . mysqli_error($conn);
    }
}

// Handle feedback deletion
if (isset($_GET['delete'])) {
    $feedbackId = intval($_GET['delete']);
    
    $deleteQuery = "DELETE FROM feedback WHERE id = $feedbackId";
    
    if (mysqli_query($conn, $deleteQuery)) {
        header("Location: feedback.php?msg=feedback_deleted");
        exit();
    } else {
        $error = "Failed to delete feedback: " . mysqli_error($conn);
    }
}

// Get feedback with filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Build query based on actual column names from debug
$feedbackQuery = "SELECT * FROM feedback WHERE 1=1";

if (!empty($statusFilter) && $statusFilter != 'all') {
    $feedbackQuery .= " AND status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}
if (!empty($dateFilter)) {
    $feedbackQuery .= " AND DATE(created_at) = '" . mysqli_real_escape_string($conn, $dateFilter) . "'";
}
if ($ratingFilter > 0) {
    $feedbackQuery .= " AND rating = $ratingFilter";
}

$feedbackQuery .= " ORDER BY id DESC";

$feedbackResult = mysqli_query($conn, $feedbackQuery);

if (!$feedbackResult) {
    die("Query failed: " . mysqli_error($conn));
}

// Get total count
$totalRows = mysqli_num_rows($feedbackResult);

// Check for success messages
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'feedback_deleted':
            $message = 'Feedback deleted successfully';
            break;
        case 'status_updated':
            $message = 'Status updated successfully';
            break;
        case 'response_added':
            $message = 'Response added successfully';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Left Sidebar Styles */
        .admin-sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            font-size: 22px;
            margin-bottom: 5px;
            color: white;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: #bdc3c7;
            opacity: 0.8;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-nav li {
            margin: 5px 0;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #3498db;
        }
        
        .sidebar-nav a.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-left-color: #3498db;
        }
        
        .sidebar-nav i {
            width: 24px;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            color: white;
        }
        
        .user-details h4 {
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .user-details span {
            font-size: 12px;
            color: #bdc3c7;
        }
        
        .logout-btn {
            display: block;
            width: 100%;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
            color: white;
        }
        
        .admin-main {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }
        
        .admin-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .admin-header h1 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 28px;
        }
        
        .admin-header p {
            color: #7f8c8d;
            font-size: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
            font-size: 26px;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 6px 0 0 0;
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 14px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f4f8;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 22px;
        }
        
        .section-header span {
            color: #7f8c8d;
            font-size: 14px;
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 6px;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 2px solid #f0f4f8;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e1e8ed;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .rating-stars small {
            color: #7f8c8d;
            margin-left: 8px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-reviewed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn-action {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .btn-submit {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #95a5a6;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(149, 165, 166, 0.3);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px !important;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #dfe6e9;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #636e72;
            margin-bottom: 12px;
            font-size: 22px;
        }
        
        .empty-state p {
            color: #7f8c8d;
            max-width: 500px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }
        
        .feedback-message {
            max-width: 350px;
            line-height: 1.6;
            color: #2c3e50;
        }
        
        .read-more {
            color: #3498db;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
            display: inline-block;
        }
        
        .read-more:hover {
            text-decoration: underline;
        }
        
        .status-select {
            padding: 8px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #2c3e50;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        form[style*="display: inline"] {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Left Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Customer Feedback System</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                            <i class="fas fa-comments"></i>
                            Customer Feedback
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            User Management
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            Reports & Analytics
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="help.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
                            <i class="fas fa-question-circle"></i>
                            Help & Support
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo $_SESSION['username'] ?? 'Administrator'; ?></h4>
                        <span>Admin User</span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1><i class="fas fa-comments"></i> Customer Feedback</h1>
                    <p>Manage customer reviews and feedback</p>
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

            <!-- Feedback Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #45a049);">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalRows; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #e68900);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                        // Count pending
                        $pendingQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'";
                        $pendingResult = mysqli_query($conn, $pendingQuery);
                        $pendingCount = $pendingResult ? mysqli_fetch_assoc($pendingResult)['count'] : 0;
                        echo $pendingCount;
                        ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #0b7dda);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php 
                            $avgQuery = "SELECT AVG(rating) as avg FROM feedback";
                            $avgResult = mysqli_query($conn, $avgQuery);
                            $avgRating = $avgResult ? mysqli_fetch_assoc($avgResult)['avg'] : 0;
                            echo number_format($avgRating, 1);
                            ?>
                        </h3>
                        <p>Average Rating</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0, #7b1fa2);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                        $resolvedQuery = "SELECT COUNT(*) as count FROM feedback WHERE status = 'resolved'";
                        $resolvedResult = mysqli_query($conn, $resolvedQuery);
                        $resolvedCount = $resolvedResult ? mysqli_fetch_assoc($resolvedResult)['count'] : 0;
                        echo $resolvedCount;
                        ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Status:</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo (empty($statusFilter) || $statusFilter == 'all') ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $statusFilter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="resolved" <?php echo $statusFilter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Date:</label>
                            <input type="date" name="date" value="<?php echo $dateFilter; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <div class="filter-group">
                            <label>Rating:</label>
                            <select name="rating" onchange="this.form.submit()">
                                <option value="0" <?php echo $ratingFilter == 0 ? 'selected' : ''; ?>>All Ratings</option>
                                <option value="5" <?php echo $ratingFilter == 5 ? 'selected' : ''; ?>>★★★★★ (5)</option>
                                <option value="4" <?php echo $ratingFilter == 4 ? 'selected' : ''; ?>>★★★★ (4)</option>
                                <option value="3" <?php echo $ratingFilter == 3 ? 'selected' : ''; ?>>★★★ (3)</option>
                                <option value="2" <?php echo $ratingFilter == 2 ? 'selected' : ''; ?>>★★ (2)</option>
                                <option value="1" <?php echo $ratingFilter == 1 ? 'selected' : ''; ?>>★ (1)</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="feedback.php" class="btn-secondary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Feedback Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> All Feedback</h2>
                    <span>Showing <?php echo $totalRows; ?> feedback entries</span>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Rating</th>
                                <th>Message</th>
                                <th>Food Quality</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($totalRows > 0): ?>
                                <?php 
                                // Reset pointer
                                mysqli_data_seek($feedbackResult, 0);
                                
                                while($row = mysqli_fetch_assoc($feedbackResult)): 
                                    // IMPORTANT: Check the actual column names from debug output
                                    // Use the exact column names shown in debug section
                                    $id = $row['id'] ?? 0;
                                    $userId = $row['user_id'] ?? ($row['userid'] ?? 'N/A');
                                    $messageText = $row['message'] ?? ($row['feedback_text'] ?? ($row['comment'] ?? ''));
                                    $rating = isset($row['rating']) ? intval($row['rating']) : (isset($row['stars']) ? intval($row['stars']) : 0);
                                    $foodQuality = isset($row['food_quality']) ? intval($row['food_quality']) : (isset($row['foodquality']) ? intval($row['foodquality']) : 0);
                                    $status = $row['status'] ?? ($row['feedback_status'] ?? 'pending');
                                    $createdAt = isset($row['created_at']) ? date('Y-m-d H:i', strtotime($row['created_at'])) : 
                                               (isset($row['timestamp']) ? date('Y-m-d H:i', strtotime($row['timestamp'])) : 
                                               (isset($row['date']) ? date('Y-m-d H:i', strtotime($row['date'])) : 'N/A'));
                                    
                                    // Clean message
                                    $cleanMessage = htmlspecialchars($messageText);
                                ?>
                                <tr>
                                    <td>#<?php echo $id; ?></td>
                                    <td>
                                        <div class="customer-info">
                                            <strong>User ID: <?php echo $userId; ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $rating):
                                                    echo '<i class="fas fa-star"></i>';
                                                else:
                                                    echo '<i class="far fa-star"></i>';
                                                endif;
                                            endfor;
                                            ?>
                                            <small><?php echo $rating; ?>/5</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="feedback-message">
                                            <?php 
                                            if (!empty($cleanMessage) && trim($cleanMessage) !== '') {
                                                echo nl2br(substr($cleanMessage, 0, 100)); 
                                                if (strlen($cleanMessage) > 100): 
                                                ?>
                                                ... <a href="#" class="read-more" onclick="showFullMessage(<?php echo $id; ?>, '<?php echo addslashes($cleanMessage); ?>')">
                                                    Read more
                                                </a>
                                                <?php endif;
                                            } else {
                                                echo '<em style="color: #95a5a6;">No message</em>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $foodQuality):
                                                    echo '<i class="fas fa-star"></i>';
                                                else:
                                                    echo '<i class="far fa-star"></i>';
                                                endif;
                                            endfor;
                                            ?>
                                            <small><?php echo $foodQuality; ?>/5</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $createdAt; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!empty($cleanMessage) && trim($cleanMessage) !== ''): ?>
                                            <button class="btn-action btn-view" title="View Details" 
                                                    onclick="showFullMessage(<?php echo $id; ?>, '<?php echo addslashes($cleanMessage); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="feedback_id" value="<?php echo $id; ?>">
                                                <select name="status" onchange="this.form.submit()" class="status-select">
                                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="reviewed" <?php echo $status == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="resolved" <?php echo $status == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            
                                            <a href="?delete=<?php echo $id; ?>" 
                                               class="btn-action btn-delete" title="Delete Feedback"
                                               onclick="return confirm('Are you sure you want to delete this feedback?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <i class="fas fa-comments"></i>
                                        <h3>No Feedback Found</h3>
                                        <p>There is no feedback in the database or no feedback matches your filters.</p>
                                        <?php if($statusFilter || $dateFilter || $ratingFilter): ?>
                                        <p style="margin-top: 15px;">
                                            <a href="feedback.php" class="btn-submit" style="font-size: 14px;">
                                                <i class="fas fa-redo"></i> Clear all filters
                                            </a>
                                        </p>
                                        <?php endif; ?>
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
    // Show full message in modal
    function showFullMessage(feedbackId, message) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f4f8;">
                    <h3 style="margin: 0; color: #2c3e50; font-size: 20px;">
                        <i class="fas fa-comment" style="margin-right: 10px; color: #3498db;"></i>
                        Feedback Message (ID: #${feedbackId})
                    </h3>
                    <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #95a5a6; padding: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;">
                        &times;
                    </button>
                </div>
                <div style="padding: 25px; background: #f8f9fa; border-radius: 8px; margin-bottom: 25px;">
                    <p style="white-space: pre-wrap; word-wrap: break-word; line-height: 1.8; color: #2c3e50; font-size: 15px;">
                        ${message.replace(/\n/g, '\n')}
                    </p>
                </div>
                <div style="text-align: center;">
                    <button onclick="this.closest('.modal').remove()" style="background: #95a5a6; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;">
                        <i class="fas fa-times" style="margin-right: 8px;"></i> Close
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.remove();
            }
        });
    }
    
    // Auto-hide alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>