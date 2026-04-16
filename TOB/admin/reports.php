<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Date range filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';

// If the current month has no orders and no explicit filter is set, fall back to the full available date range.
if (!isset($_GET['start_date']) && !isset($_GET['end_date'])) {
    $rangeQuery = "SELECT MIN(DATE(order_date)) as min_date, MAX(DATE(order_date)) as max_date FROM orders";
    $rangeResult = mysqli_query($conn, $rangeQuery);
    if ($rangeResult) {
        $range = mysqli_fetch_assoc($rangeResult);
        if (!empty($range['min_date']) && !empty($range['max_date'])) {
            $startDate = $range['min_date'];
            $endDate = $range['max_date'];
        }
    }
}

// Get sales report based on report type
if ($reportType == 'weekly') {
    $groupBy = "YEARWEEK(order_date)";
    $dateFormat = "Week %U, %Y";
} elseif ($reportType == 'monthly') {
    $groupBy = "DATE_FORMAT(order_date, '%Y-%m')";
    $dateFormat = "%M %Y";
} else {
    $groupBy = "DATE(order_date)";
    $dateFormat = "%M %d, %Y";
}

$salesQuery = "SELECT 
                MIN(order_date) as date,
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_order_value,
                SUM(tax_amount) as total_gst,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
               FROM orders 
               WHERE DATE(order_date) BETWEEN '$startDate' AND '$endDate'
               GROUP BY $groupBy
               ORDER BY date DESC";
$salesResult = mysqli_query($conn, $salesQuery);

// Get summary statistics
$summaryQuery = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_revenue,
                    AVG(total_price) as avg_order_value,
                    SUM(tax_amount) as total_gst,
                    SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as delivered_revenue,
                    SUM(CASE WHEN status = 'cancelled' THEN total_price ELSE 0 END) as cancelled_revenue
                 FROM orders 
                 WHERE DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
$summaryResult = mysqli_query($conn, $summaryQuery);
$summary = $summaryResult ? mysqli_fetch_assoc($summaryResult) : [];

// Get active customers count
$customerQuery = "SELECT COUNT(DISTINCT user_id) as active_customers 
                  FROM orders 
                  WHERE DATE(order_date) BETWEEN '$startDate' AND '$endDate'";
$customerResult = mysqli_query($conn, $customerQuery);
$customerStats = $customerResult ? mysqli_fetch_assoc($customerResult) : [];

// Get quick platform counts for report overview
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'"))['count'] ?? 0;
$totalFoods = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM foods"))['count'] ?? 0;
$totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM categories"))['count'] ?? 0;
$totalOrderCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'] ?? 0;
$totalFeedback = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback"))['count'] ?? 0;

// Get food items and category breakdown for report tables
$foodItemsQuery = "SELECT 
                    f.name as food_name,
                    c.name as category_name,
                    f.price
                  FROM foods f
                  LEFT JOIN categories c ON f.category_id = c.id
                  ORDER BY f.name ASC";
$foodItemsResult = mysqli_query($conn, $foodItemsQuery);

$categorySummaryQuery = "SELECT 
                    c.name as category_name,
                    COUNT(f.id) as total_items
                  FROM categories c
                  LEFT JOIN foods f ON f.category_id = c.id
                  GROUP BY c.id
                  ORDER BY total_items DESC, c.name ASC";
$categorySummaryResult = mysqli_query($conn, $categorySummaryQuery);

// Get top selling foods
$topFoodsQuery = "SELECT 
                    f.id,
                    f.name,
                    c.name as category_name,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.quantity * oi.price) as total_revenue
                  FROM order_items oi
                  JOIN foods f ON oi.food_id = f.id
                  JOIN categories c ON f.category_id = c.id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE DATE(o.order_date) BETWEEN '$startDate' AND '$endDate'
                  GROUP BY f.id
                  ORDER BY total_sold DESC
                  LIMIT 10";
$topFoodsResult = mysqli_query($conn, $topFoodsQuery);

// Get monthly summary (last 6 months)
$monthlyQuery = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month_year,
                    DATE_FORMAT(order_date, '%M %Y') as display_month,
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_revenue
                 FROM orders
                 WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY month_year
                 ORDER BY month_year DESC
                 LIMIT 6";
$monthlyResult = mysqli_query($conn, $monthlyQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f8fa;
        }
        
        /* ===== ADMIN LAYOUT ===== */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-main {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }
        
        /* ===== HEADER SECTION ===== */
        .admin-header {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #4CAF50;
        }
        
        .header-left h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-left p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        /* ===== BUTTONS ===== */
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76,175,80,0.3);
        }
        
        /* ===== FILTERS SECTION ===== */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
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
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input[type="date"]:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        /* ===== STATS CARDS ===== */
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        
        .stat-info p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        /* ===== CHARTS SECTION ===== */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .chart-header span {
            color: #666;
            font-size: 14px;
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        /* ===== DASHBOARD SECTIONS ===== */
        .dashboard-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-header h2 {
            font-size: 22px;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
        }
        
        .section-header span {
            color: #666;
            font-size: 14px;
            background: #f8f9fa;
            padding: 6px 14px;
            border-radius: 20px;
        }
        
        /* ===== TABLES ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .data-table thead {
            background: #f8fafc;
        }
        
        .data-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 15px;
            vertical-align: middle;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* ===== PROGRESS BAR ===== */
        .progress-bar {
            width: 100%;
            height: 24px;
            background: #f0f0f0;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 12px;
            transition: width 0.3s ease;
            min-width: 24px;
        }
        
        .progress-bar span {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            color: #475569;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* ===== SUMMARY ROW ===== */
        .summary-row {
            background: #f8f9fa !important;
            font-weight: 600;
        }
        
        .summary-row td {
            border-top: 2px solid #ddd;
            font-size: 16px;
        }
    
        
        /* ===== MONTHLY SUMMARY ===== */
        .monthly-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .overview-grid .table-responsive {
            min-width: 0;
            width: 100%;
        }

        .overview-grid .data-table {
            min-width: 0;
            width: 100%;
            table-layout: fixed;
        }

        .overview-grid .data-table th,
        .overview-grid .data-table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .overview-grid .data-table th:nth-child(1),
        .overview-grid .data-table td:nth-child(1) {
            width: 8%;
        }

        .overview-grid .data-table th:nth-child(2),
        .overview-grid .data-table td:nth-child(2) {
            width: 46%;
        }

        .overview-grid .data-table th:nth-child(3),
        .overview-grid .data-table td:nth-child(3) {
            width: 46%;
        }

        .monthly-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .monthly-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #4CAF50;
        }
        
        .monthly-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .monthly-header h3 {
            font-size: 16px;
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        
        .monthly-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .monthly-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .monthly-stat {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .monthly-stat i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .monthly-stat p {
            font-size: 13px;
            color: #666;
            margin: 0 0 4px 0;
        }
        
        .monthly-stat h4 {
            font-size: 18px;
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        
        /* ===== EMPTY STATES ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
            display: block;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: #475569;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-state p {
            color: #94a3b8;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                padding: 20px;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
                gap: 15px;
                text-align: center;
            }
            
            .export-buttons {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .monthly-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table th,
            .data-table td {
                padding: 12px;
                font-size: 14px;
            }
            
            .chart-container {
                padding: 20px;
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
            
            .monthly-body {
                grid-template-columns: 1fr;
            }
            
            .btn, .btn-export {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        @media print {
            body, html {
                background: white;
                color: #000;
            }
            .admin-sidebar,
            .btn,
            .btn-primary,
            .btn-secondary,
            .filter-group label,
            .filter-row .btn,
            .section-header span,
            .admin-header .header-right,
            .chart-container canvas,
            .data-table tbody tr:hover {
                display: none !important;
            }
            .admin-container {
                flex-direction: column;
            }
            .admin-main {
                margin-left: 0 !important;
                padding: 10px !important;
            }
            .dashboard-section,
            .filters-section,
            .stats-grid,
            .table-responsive {
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
            }
            .data-table,
            .data-table th,
            .data-table td {
                color: #000 !important;
                border-color: #ccc !important;
            }
            .data-table {
                min-width: 0 !important;
            }
            .overview-grid,
            .monthly-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <h1><i class="fas fa-chart-bar"></i> Sales Reports</h1>
                    <p>Analytics and insights for your business</p>
                </div>
                <div class="header-right">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </header>

            <!-- Filters Section -->
            <section class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-chart-pie"></i> Report Type</label>
                            <select name="report_type" onchange="this.form.submit()">
                                <option value="daily" <?php echo $reportType == 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                                <option value="weekly" <?php echo $reportType == 'weekly' ? 'selected' : ''; ?>>Weekly Report</option>
                                <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-calendar-start"></i> Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $startDate; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-calendar-end"></i> End Date</label>
                            <input type="date" name="end_date" value="<?php echo $endDate; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="reports.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Statistics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #2E7D32);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $summary['total_orders'] ?? 0; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #0D47A1);">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #00A8E8, #0077B6);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($summary['total_gst'] ?? 0, 2); ?></h3>
                        <p>Total GST Collected</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #E65100);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($summary['avg_order_value'] ?? 0, 2); ?></h3>
                        <p>Avg Order Value</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0, #4A148C);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $customerStats['active_customers'] ?? 0; ?></h3>
                        <p>Active Customers</p>
                    </div>
                </div>
            </section>

            <!-- Platform Overview -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-layer-group"></i> Platform Overview</h2>
                    <span>Users, Food Items, Categories, Orders, Feedback</span>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #5E35B1, #4527A0);">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #FF5722, #E64A19);">
                            <i class="fas fa-hamburger"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalFoods; ?></h3>
                            <p>Food Items</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #00ACC1, #00838F);">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalCategories; ?></h3>
                            <p>Categories</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43A047, #2E7D32);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalOrderCount; ?></h3>
                            <p>Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #FDD835, #F9A825);">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalFeedback; ?></h3>
                            <p>Feedback</p>
                        </div>
                    </div>
                </section>
            </section>

            <!-- Food Items and Categories Tables -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-utensils"></i> Food Items & Categories</h2>
                    <span>Menu and category breakdown</span>
                </div>
                <div class="overview-grid">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Food Item</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($foodItemsResult && mysqli_num_rows($foodItemsResult) > 0): ?>
                                    <?php $itemIndex = 1; while ($foodItem = mysqli_fetch_assoc($foodItemsResult)): ?>
                                    <tr>
                                        <td><?php echo $itemIndex++; ?></td>
                                        <td><?php echo htmlspecialchars($foodItem['food_name']); ?></td>
                                        <td><?php echo htmlspecialchars($foodItem['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>₹<?php echo number_format($foodItem['price'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <i class="fas fa-utensils"></i>
                                            <h3>No Food Items Found</h3>
                                            <p>No food items are available in the database.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category</th>
                                    <th>Food Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categorySummaryResult && mysqli_num_rows($categorySummaryResult) > 0): ?>
                                    <?php $catIndex = 1; while ($category = mysqli_fetch_assoc($categorySummaryResult)): ?>
                                    <tr>
                                        <td><?php echo $catIndex++; ?></td>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo $category['total_items']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">
                                            <i class="fas fa-tags"></i>
                                            <h3>No Categories Found</h3>
                                            <p>No categories are available in the database.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Sales Chart -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Sales Trend</h2>
                    <span><?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </section>

            <!-- Sales Report Table -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-table"></i> Detailed Sales Report</h2>
                    <span><?php echo mysqli_num_rows($salesResult); ?> records</span>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>GST</th>
                                <th>Avg Order</th>
                                <th>Delivered</th>
                                <th>Cancelled</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($salesResult, 0);
                            $totalOrders = 0;
                            $totalRevenue = 0;
                            $totalGST = 0;
                            $totalDelivered = 0;
                            $totalCancelled = 0;
                            
                            if(mysqli_num_rows($salesResult) > 0):
                                while($row = mysqli_fetch_assoc($salesResult)): 
                                    $totalOrders += $row['total_orders'];
                                    $totalRevenue += $row['total_revenue'];
                                    $totalGST += $row['total_gst'];
                                    $totalDelivered += $row['delivered_orders'];
                                    $totalCancelled += $row['cancelled_orders'];
                                    $successRate = $row['total_orders'] > 0 ? ($row['delivered_orders'] / $row['total_orders']) * 100 : 0;
                                    
                                    $displayDate = date('M d, Y', strtotime($row['date']));
                                ?>
                                <tr>
                                    <td><strong><?php echo $displayDate; ?></strong></td>
                                    <td><?php echo $row['total_orders']; ?></td>
                                    <td><strong>₹<?php echo number_format($row['total_revenue'], 2); ?></strong></td>
                                    <td><strong>₹<?php echo number_format($row['total_gst'], 2); ?></strong></td>
                                    <td>₹<?php echo number_format($row['avg_order_value'], 2); ?></td>
                                    <td><span style="color: #10b981;"><?php echo $row['delivered_orders']; ?></span></td>
                                    <td><span style="color: #ef4444;"><?php echo $row['cancelled_orders']; ?></span></td>
                                    <td style="min-width: 150px;">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $successRate; ?>%"></div>
                                            <span><?php echo number_format($successRate, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <i class="fas fa-chart-bar"></i>
                                        <h3>No Sales Data Found</h3>
                                        <p>No sales data available for the selected period. Try adjusting your date range.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <!-- Summary Row -->
                            <?php if($totalOrders > 0): ?>
                            <tr class="summary-row">
                                <td><strong>TOTAL</strong></td>
                                <td><strong><?php echo $totalOrders; ?></strong></td>
                                <td><strong>₹<?php echo number_format($totalRevenue, 2); ?></strong></td>
                                <td><strong>₹<?php echo number_format($totalGST, 2); ?></strong></td>
                                <td><strong>₹<?php echo $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 2) : '0.00'; ?></strong></td>
                                <td><strong><?php echo $totalDelivered; ?></strong></td>
                                <td><strong><?php echo $totalCancelled; ?></strong></td>
                                <td>
                                    <strong>
                                        <?php echo $totalOrders > 0 ? number_format(($totalDelivered / $totalOrders) * 100, 1) : '0.0'; ?>%
                                    </strong>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Top Selling Foods -->
            <section class="dashboard-section">                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Food Item</th>
                                <th>Category</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                                <th>Popularity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($topFoodsResult && mysqli_num_rows($topFoodsResult) > 0):
                                $rank = 1;
                                while($food = mysqli_fetch_assoc($topFoodsResult)): 
                                    $popularity = min(100, ($food['total_sold'] / 50) * 100);
                            ?>
                            <tr>
                                <td><span class="monthly-badge">#<?php echo $rank++; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($food['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($food['category_name']); ?></td>
                                <td><?php echo $food['total_sold']; ?></td>
                                <td><strong>₹<?php echo number_format($food['total_revenue'], 2); ?></strong></td>
                                <td style="min-width: 150px;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $popularity; ?>%; background: linear-gradient(90deg, #e74c3c, #c0392b);"></div>
                                        <span><?php echo number_format($popularity, 0); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-utensils"></i>
                                    <h3>No Food Sales Data</h3>
                                    <p>No food sales data available for the selected period.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Monthly Summary -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-alt"></i> Monthly Summary</h2>
                    <span>Last 6 Months</span>
                </div>
                
                <div class="monthly-grid">
                    <?php 
                    if($monthlyResult && mysqli_num_rows($monthlyResult) > 0):
                        while($monthly = mysqli_fetch_assoc($monthlyResult)): 
                    ?>
                    <div class="monthly-card">
                        <div class="monthly-header">
                            <h3><?php echo $monthly['display_month']; ?></h3>
                            <span class="monthly-badge"><?php echo $monthly['total_orders']; ?> orders</span>
                        </div>
                        <div class="monthly-body">
                            <div class="monthly-stat">
                                <i class="fas fa-shopping-bag"></i>
                                <div>
                                    <p>Total Orders</p>
                                    <h4><?php echo $monthly['total_orders']; ?></h4>
                                </div>
                            </div>
                            <div class="monthly-stat">
                                <i class="fas fa-rupee-sign"></i>
                                <div>
                                    <p>Total Revenue</p>
                                    <h4>₹<?php echo number_format($monthly['total_revenue'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Monthly Data</h3>
                        <p>No monthly sales data available for analysis.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    // Prepare data for sales chart
    <?php
    mysqli_data_seek($salesResult, 0);
    $dates = [];
    $revenues = [];
    $orders = [];
    
    if(mysqli_num_rows($salesResult) > 0) {
        while($row = mysqli_fetch_assoc($salesResult)) {
            $dates[] = date('M d', strtotime($row['date']));
            $revenues[] = $row['total_revenue'];
            $orders[] = $row['total_orders'];
        }
        $dates = array_reverse($dates);
        $revenues = array_reverse($revenues);
        $orders = array_reverse($orders);
    }
    ?>
    
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($orders); ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e74c3c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 12,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        },
                        grid: {
                            borderDash: [5, 5]
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }
    
    // Print functionality
    document.querySelector('.btn-secondary[onclick*="print"]').addEventListener('click', function(e) {
        e.preventDefault();
        window.print();
    });
    
    // Add hover effects to table rows
    document.querySelectorAll('.data-table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    </script>
</body>
</html>