<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();

// Get cart count for logged in users
$cartCount = 0;
if ($auth->isLoggedIn() && isset($_SESSION['user_id'])) {
    try {
        $conn = getDBConnection();
        if ($conn) {
            $userId = $_SESSION['user_id'];
            $cartCountQuery = "SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $cartCountQuery);
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $cartCountResult = mysqli_stmt_get_result($stmt);
            if ($cartCountResult) {
                $cartData = mysqli_fetch_assoc($cartCountResult);
                $cartCount = $cartData['count'] ?? 0;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Bihar Traditional Food'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation Styles */
        .navbar {
            background: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .navbar .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand a {
            font-size: 1.5rem;
            font-weight: 700;
            color: #13100f;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-brand a span {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 25px;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            padding: 8px 0;
            white-space: nowrap;
        }

        /* RED TEXT ON HOVER */
        .nav-menu a:hover,
        .nav-menu a.active {
            color: #e74c3c !important;
            /* Red color on hover */
            background: none !important;
            /* Ensure no background */
        }

        .nav-menu a i {
            font-size: 1rem;
            min-width: 20px;
        }

        /* Register Button */
        .btn-register {
            background: #e74c3c;
            color: white !important;
            padding: 8px 20px !important;
            border-radius: 5px;
            transition: all 0.3s ease !important;
            border: none;
            cursor: pointer;
        }

        .btn-register:hover {
            background: #c0392b !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
            color: white !important;
            /* Keep text white on hover */
        }

        /* Dropdown Menu */
        .dropdown {
            position: relative;
        }

        .dropdown>a {
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 10px 0;
            z-index: 1001;
            border: 1px solid #eee;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px !important;
            color: #333 !important;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
            font-weight: 400;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        /* Dropdown items also turn red on hover */
        .dropdown-content a:hover {
            background: #f8f9fa;
            color: #e74c3c !important;
            padding-left: 25px !important;
        }

        /* Cart Count Badge */
        .cart-count {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            background: none;
            border: none;
            padding: 5px;
        }

        /* Main Content Area */
        main {
            margin-top: 70px;
            flex: 1;
            padding: 20px 0;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .nav-menu {
                gap: 15px;
            }

            .nav-menu a {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                height: 60px;
            }

            .mobile-toggle {
                display: block;
            }

            .nav-menu {
                position: fixed;
                top: 60px;
                left: -100%;
                width: 280px;
                height: calc(100vh - 60px);
                background: white;
                flex-direction: column;
                gap: 0;
                transition: left 0.3s ease;
                padding: 20px 0;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                align-items: stretch;
            }

            .nav-menu.active {
                left: 0;
            }

            .nav-menu li {
                width: 100%;
                border-bottom: 1px solid #eee;
            }

            .nav-menu a {
                padding: 15px 20px !important;
                justify-content: flex-start;
                font-size: 1rem;
            }

            .dropdown-content {
                position: static;
                box-shadow: none;
                background: #f8f9fa;
                display: none;
                animation: none;
                border: none;
                border-radius: 0;
                padding-left: 20px;
            }

            .dropdown:hover .dropdown-content {
                display: none;
            }

            .dropdown.active .dropdown-content {
                display: block;
            }

            .btn-register {
                margin: 10px 20px;
                width: calc(100% - 40px);
            }

            main {
                margin-top: 60px;
            }
        }

        @media (max-width: 480px) {
            .navbar .container {
                padding: 0 15px;
            }

            .nav-brand a {
                font-size: 1.2rem;
            }

            .nav-menu {
                width: 100%;
            }
        }

        /* Heading Style */
        #headling {
            font-weight: bold;
            font-size: 24px;
            color: #e74c3c;
            /* Red color to match theme */
        }

        /* Remove gap between header and body */
        body {
            margin: 0;
            padding: 0;
        }

        /* Ensure header has no bottom margin */
        header,
        .header,
        .navbar,
        nav {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        /* Remove top margin from main content */
        main,
        .main-content,
        .content,
        .hero,
        section:first-of-type {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .navbar.fixed-top {
            margin-bottom: 0;
        }

        body>*:first-child {
            margin-top: 0 !important;
        }

        .hero {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <span id="headline">Bihar Traditional Food</span>
                </a>
            </div>

            <button class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li>
                    <a href="<?php echo BASE_URL; ?>index.php"
                        <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>users/menu.php"
                        <?php echo (basename($_SERVER['PHP_SELF']) == 'menu.php') ? 'class="active"' : ''; ?>>
                        <i class="fas fa-utensils"></i>
                        <span>Menu</span>
                    </a>
                </li>

                <?php if ($auth->isLoggedIn()): ?>
                    <li class="dropdown" id="userDropdown">
                        <a href="#">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 5px;"></i>
                        </a>
                        <div class="dropdown-content">
                            <a href="<?php echo BASE_URL; ?>users/profile.php">
                                <i class="fas fa-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="<?php echo BASE_URL; ?>users/orders.php">
                                <i class="fas fa-shopping-bag"></i>
                                <span>My Orders</span>
                            </a>
                            <a href="<?php echo BASE_URL; ?>users/cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Cart</span>
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-count"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>login.php">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn-register">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main>