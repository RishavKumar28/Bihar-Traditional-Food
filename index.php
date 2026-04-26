<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Now include wishlist support
require_once 'users/get-wishlist.php';

// Get all active categories
$categories = [];
$foods = [];
$selected_category = null;

try {
    $conn = getDBConnection();

    // Get all active categories
    $catQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
    $catResult = mysqli_query($conn, $catQuery);

    if ($catResult && mysqli_num_rows($catResult) > 0) {
        while ($category = mysqli_fetch_assoc($catResult)) {
            $categories[] = $category;
        }
    }

    // Check if a specific category is selected
    if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
        $category_id = intval($_GET['category_id']);

        // Get category details
        $categoryQuery = "SELECT * FROM categories WHERE id = $category_id AND is_active = 1";
        $categoryResult = mysqli_query($conn, $categoryQuery);

        if ($categoryResult && mysqli_num_rows($categoryResult) > 0) {
            $selected_category = mysqli_fetch_assoc($categoryResult);

            // Get foods for this category
            $foodQuery = "SELECT f.*, c.name as category_name 
                          FROM foods f 
                          LEFT JOIN categories c ON f.category_id = c.id 
                          WHERE f.category_id = $category_id 
                          AND f.is_available = 1 
                          ORDER BY f.display_order";
        } else {
            // If category doesn't exist, show all foods
            $selected_category = null;
            $foodQuery = "SELECT f.*, c.name as category_name 
                          FROM foods f 
                          LEFT JOIN categories c ON f.category_id = c.id 
                          WHERE f.is_available = 1 
                          ORDER BY c.display_order, f.display_order";
        }
    } else {
        // Show all foods if no category selected
        $foodQuery = "SELECT f.*, c.name as category_name 
                      FROM foods f 
                      LEFT JOIN categories c ON f.category_id = c.id 
                      WHERE f.is_available = 1 
                      ORDER BY c.display_order, f.display_order";
    }

    // Execute food query
    $foodResult = mysqli_query($conn, $foodQuery);

    if ($foodResult && mysqli_num_rows($foodResult) > 0) {
        while ($food = mysqli_fetch_assoc($foodResult)) {
            $foods[] = $food;
        }
    }

    mysqli_close($conn);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Check if user is logged in for JavaScript
$isLoggedIn = $auth->isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Bihar Traditional Food</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Menu Page Styles */
        .menu-page {
            padding-top: 70px;
        }

        .menu-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url('assets/images/menu-bg.jpg') center/cover no-repeat;
            color: white;
            text-align: center;
            padding: 80px 0;
            margin-bottom: 40px;
        }

        .menu-hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .menu-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        /* Categories Section */
        .categories-section {
            padding: 40px 0;
            background: #f8f9fa;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .category-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .category-image {
            height: 180px;
            overflow: hidden;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-image img {
            transform: scale(1.05);
        }

        .category-info {
            padding: 20px;
        }

        .category-info h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }

        .category-info p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .category-count {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Current Category Header */
        .current-category {
            background: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .category-header {
            text-align: center;
        }

        .category-header h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        .category-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 20px;
        }

        .back-to-menu {
            display: inline-block;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .back-to-menu:hover {
            text-decoration: underline;
        }

        /* Foods Grid */
        .foods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 30px 0;
        }

        .food-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .food-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .food-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .food-card:hover .food-image img {
            transform: scale(1.05);
        }

        .popular-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .food-info {
            padding: 20px;
        }

        .food-info h3 {
            margin-bottom: 8px;
            font-size: 1.2rem;
            color: #333;
            line-height: 1.4;
        }

        .food-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .food-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .food-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .food-price-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .food-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #e74c3c;
        }

        .original-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .prep-time {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .btn-add-cart {
            width: 100%;
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-add-cart:hover {
            background: #219653;
        }

        .btn-wishlist {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .btn-wishlist:hover {
            background: #c0392b;
            transform: scale(1.1);
        }

        .btn-wishlist.in-wishlist {
            background: #e74c3c;
            color: white;
        }

        .btn-wishlist.in-wishlist i {
            animation: heartBeat 0.3s ease;
        }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* No Data Styling */
        .no-data {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .no-data i {
            margin-bottom: 20px;
            color: #ddd;
        }

        .no-data h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 1.5rem;
        }

        .no-data p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        /* Section Titles */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-size: 2.5rem;
            color: #333;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .menu-hero h1 {
                font-size: 2.2rem;
            }

            .menu-hero {
                padding: 60px 0;
            }

            .categories-grid,
            .foods-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .category-header h2 {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .menu-hero h1 {
                font-size: 1.8rem;
            }

            .categories-grid,
            .foods-grid {
                grid-template-columns: 1fr;
            }

            .category-image {
                height: 150px;
            }

            .food-image {
                height: 180px;
            }

            .category-header h2 {
                font-size: 1.8rem;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 30px 0;
        }

        .filter-btn {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e74c3c;
            color: #e74c3c;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #e74c3c;
            color: white;
        }

        /* Search Bar */
        .search-container {
            max-width: 500px;
            margin: 30px auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e74c3c;
            border-radius: 30px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }

        .search-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 1.2rem;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="menu-page">
        <!-- Hero Section -->
        <section class="menu-hero">
            <div class="container" style="margin-top: 70px;">
                <h1>Our Delicious Menu</h1>
                <p>Explore authentic Bihari cuisine with our wide selection of traditional dishes made from secret family recipes.</p>
                <div class="search-container">
                    <input type="text" class="search-input" id="searchFood" placeholder="Search for dishes...">
                    <button class="search-btn" id="searchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="categories-section">
            <div class="container">
                <h2 class="section-title">Food Categories</h2>
                <p class="text-center" style="color: #666; margin-bottom: 30px; font-size: 1.1rem;">
                    Browse our menu by category
                </p>

                <div class="categories-grid">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <?php
                            // Count foods in this category
                            $conn = getDBConnection();
                            $countQuery = "SELECT COUNT(*) as food_count FROM foods 
                                          WHERE category_id = {$category['id']} 
                                          AND is_available = 1";
                            $countResult = mysqli_query($conn, $countQuery);
                            $foodCount = 0;
                            if ($countResult) {
                                $countRow = mysqli_fetch_assoc($countResult);
                                $foodCount = $countRow['food_count'];
                            }
                            mysqli_close($conn);
                            ?>
                            <a href="users/categories-menu.php?category_id=<?php echo $category['id']; ?>" class="category-card">
                                <div class="category-image">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($category['image']); ?>"
                                            alt="<?php echo htmlspecialchars($category['name']); ?>"
                                            onerror="this.src='assets/images/default-category.jpg'">
                                    <?php else: ?>
                                        <img src="assets/images/default-category.jpg"
                                            alt="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="category-info">
                                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 100)); ?>
                                        <?php if (strlen($category['description'] ?? '') > 100): ?>...<?php endif; ?>
                                    </p>
                                    <span class="category-count"><?php echo $foodCount; ?> items</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="grid-column: 1 / -1;">
                            <i class="fas fa-utensils fa-3x"></i>
                            <h3>No Categories Available</h3>
                            <p>Categories will be added soon</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Current Category Header (if category is selected) -->
        <?php if ($selected_category): ?>
            <section class="current-category">
                <div class="container">
                    <div class="category-header">
                        <h2><?php echo htmlspecialchars($selected_category['name']); ?></h2>
                        <p><?php echo htmlspecialchars($selected_category['description'] ?? ''); ?></p>
                        <a href="menu.php" class="back-to-menu">
                            <i class="fas fa-arrow-left"></i> Back to All Categories
                        </a>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Foods Section -->
        <section class="foods-section" style="padding: 40px 0;">
            <div class="container">
                <?php if ($selected_category): ?>
                    <h2 class="section-title"><?php echo htmlspecialchars($selected_category['name']); ?> Menu</h2>
                <?php else: ?>
                    <h2 class="section-title">All Menu Items</h2>
                <?php endif; ?>

                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All Items</button>
                    <button class="filter-btn" data-filter="featured">Featured</button>
                    <button class="filter-btn" data-filter="popular">Popular</button>
                </div>

                <!-- Foods Grid -->
                <div class="foods-grid" id="foodsGrid">
                    <?php if (!empty($foods)): ?>
                        <?php foreach ($foods as $food): ?>
                            <?php
                            // Handle image path
                            $imagePath = 'assets/images/default-food.jpg';
                            if (!empty($food['image_path'])) {
                                if (strpos($food['image_path'], 'assets/') === 0) {
                                    $imagePath = $food['image_path'];
                                } else {
                                    $imagePath = 'assets/uploads/foods/' . basename($food['image_path']);
                                }
                            }

                            // Determine data-filter value
                            $filterClass = '';
                            if ($food['is_featured']) {
                                $filterClass = 'featured';
                            }
                            if ($food['is_popular']) {
                                $filterClass = 'popular';
                            }
                            ?>
                            <div class="food-card" data-filter="<?php echo $filterClass; ?>">
                                <div class="food-image">
                                    <img src="<?php echo $imagePath; ?>"
                                        alt="<?php echo htmlspecialchars($food['name']); ?>"
                                        onerror="this.src='assets/images/default-food.jpg'">
                                    <?php if ($food['is_popular']): ?>
                                        <span class="popular-badge">Popular</span>
                                    <?php endif; ?>
                                    <?php if ($food['is_featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                    <?php if ($auth->isLoggedIn()): ?>
                                        <button type="button" class="btn-wishlist <?php echo isInWishlist($food['id'], $_SESSION['user_id'] ?? 0) ? 'in-wishlist' : ''; ?>" 
                                                onclick="toggleWishlist(<?php echo $food['id']; ?>, event)" 
                                                title="<?php echo isInWishlist($food['id'], $_SESSION['user_id'] ?? 0) ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                                            <i class="<?php echo isInWishlist($food['id'], $_SESSION['user_id'] ?? 0) ? 'fas' : 'far'; ?> fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="food-info">
                                    <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                                    <?php if (!empty($food['category_name'])): ?>
                                        <div class="food-category"><?php echo htmlspecialchars($food['category_name']); ?></div>
                                    <?php endif; ?>
                                    <p class="food-desc"><?php echo htmlspecialchars(substr($food['description'] ?? '', 0, 100)); ?>
                                        <?php if (strlen($food['description'] ?? '') > 100): ?>...<?php endif; ?>
                                    </p>

                                    <div class="food-details">
                                        <div class="food-price-row">
                                            <div class="food-price">₹<?php echo number_format($food['price'], 2); ?></div>
                                            <?php if ($food['original_price'] && $food['original_price'] > $food['price']): ?>
                                                <div class="original-price">₹<?php echo number_format($food['original_price'], 2); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($food['preparation_time']): ?>
                                            <div class="prep-time">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo $food['preparation_time']; ?> mins</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($auth->isLoggedIn()): ?>
                                        <button class="btn-add-cart" data-food-id="<?php echo $food['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn-add-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-utensils fa-3x"></i>
                            <h3>No Food Items Available</h3>
                            <p>Food items will be added soon</p>
                            <a href="menu.php" class="btn-hero">View All Categories</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        // Add to Cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.btn-add-cart[data-food-id]');

            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const foodId = this.getAttribute('data-food-id');
                    addToCart(foodId, this);
                });
            });

            function addToCart(foodId, button) {
                const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

                if (!isLoggedIn) {
                    window.location.href = '<?php echo BASE_URL; ?>/login.php?redirect=' + encodeURIComponent(window.location.href);
                    return;
                }

                // Disable button and show loading
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                button.disabled = true;

                fetch('<?php echo BASE_URL; ?>users/add-to-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            food_id: foodId,
                            quantity: 1
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Item added to cart!', 'success');
                            updateCartCount();
                        } else {
                            showNotification(data.message || 'Failed to add item to cart', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Failed to add item to cart. Please try again.', 'error');
                    })
                    .finally(() => {
                        // Re-enable button
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    });
            }

            function showNotification(message, type) {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="notification-close">&times;</button>
                `;

                // Add styles
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#27ae60' : type === 'info' ? '#3498db' : '#e74c3c'};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 15px;
                    max-width: 400px;
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s ease;
                `;

                document.body.appendChild(notification);

                // Show notification
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                    notification.style.opacity = '1';
                }, 10);

                // Auto hide after 5 seconds
                setTimeout(() => {
                    hideNotification(notification);
                }, 5000);

                // Close button event
                notification.querySelector('.notification-close').addEventListener('click', () => {
                    hideNotification(notification);
                });
            }

            function hideNotification(notification) {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }

            function updateCartCount() {
                fetch('<?php echo BASE_URL; ?>users/get-cart-count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart count in header
                            const cartCountElements = document.querySelectorAll('.cart-count');
                            cartCountElements.forEach(element => {
                                element.textContent = data.count;
                                element.style.display = data.count > 0 ? 'inline-flex' : 'none';
                            });
                        }
                    })
                    .catch(error => console.error('Error updating cart count:', error));
            }

            // Initialize cart count
            updateCartCount();
        });

        // Wishlist toggle functionality
        function toggleWishlist(foodId, event) {
            event.preventDefault();
            event.stopPropagation();

            const button = event.currentTarget;

            fetch('users/toggle-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'food_id=' + foodId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button appearance
                    if (data.inWishlist) {
                        button.classList.add('in-wishlist');
                        button.querySelector('i').classList.remove('far');
                        button.querySelector('i').classList.add('fas');
                        button.title = 'Remove from wishlist';
                        showNotification('Added to wishlist!', 'success');
                    } else {
                        button.classList.remove('in-wishlist');
                        button.querySelector('i').classList.add('far');
                        button.querySelector('i').classList.remove('fas');
                        button.title = 'Add to wishlist';
                        showNotification('Removed from wishlist', 'info');
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update wishlist', 'error');
            });
        }
    </script>
</body>

</html>