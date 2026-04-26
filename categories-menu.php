<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users, log to file

session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Initialize variables
$categories = [];
$foods = [];
$selected_category = null;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Redirect if no category_id provided
if ($category_id <= 0) {
    header("Location: menu.php");
    exit();
}

try {
    $conn = getDBConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get all active categories for sidebar
    $catQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
    $catResult = mysqli_query($conn, $catQuery);
    
    if ($catResult) {
        while ($category = mysqli_fetch_assoc($catResult)) {
            $categories[] = $category;
        }
    }
    
    // Get the selected category details
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
        
        $foodResult = mysqli_query($conn, $foodQuery);
        
        if ($foodResult) {
            while ($food = mysqli_fetch_assoc($foodResult)) {
                $foods[] = $food;
            }
        }
    } else {
        // Category not found
        header("Location: menu.php");
        exit();
    }
    
    mysqli_close($conn);
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error = $e->getMessage();
}

// Check if user is logged in for JavaScript
$isLoggedIn = $auth->isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selected_category['name'] ?? 'Category'); ?> - Menu - Bihar Traditional Food</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Main Layout */
        .category-page {
            display: flex;
            gap: 40px;
            padding: 40px 0 80px;
            margin-top: 50px;
        }

        /* Sidebar */
        .sidebar {
            flex: 0 0 280px;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar h3 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: #333;
            padding-bottom: 15px;
            border-bottom: 2px solid #e74c3c;
        }

        .categories-list {
            list-style: none;
        }

        .categories-list li {
            margin-bottom: 12px;
        }

        .categories-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .categories-list a:hover {
            background: #e74c3c;
            color: white;
            transform: translateX(5px);
            border-left: 4px solid #c0392b;
        }

        .categories-list .active {
            background: #e74c3c;
            color: white;
            font-weight: 600;
            border-left: 4px solid #c0392b;
        }

        .cat-count {
            background: white;
            color: #333;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .categories-list .active .cat-count {
            background: #c0392b;
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .content-header h2 {
            font-size: 1.8rem;
            color: #333;
        }

        .food-count {
            color: #e74c3c;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Foods Grid */
        .foods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .food-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .food-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .food-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .food-card:hover .food-image img {
            transform: scale(1.1);
        }

        .food-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 5px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }

        .badge.popular {
            background: #e74c3c;
        }

        .badge.featured {
            background: #27ae60;
        }

        .badge.new {
            background: #3498db;
        }

        .food-info {
            padding: 20px;
        }

        .food-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .food-info h3 {
            font-size: 1.2rem;
            color: #333;
            line-height: 1.4;
            flex: 1;
        }

        .food-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #e74c3c;
            white-space: nowrap;
            margin-left: 10px;
        }

        .original-price {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 5px;
        }

        .food-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
            min-height: 40px;
        }

        .food-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .prep-time {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .rating {
            color: #ffcc00;
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

        .btn-add-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* No Items Message */
        .no-items {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .no-items i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-items h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }

        .no-items p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .btn-explore {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .btn-explore:hover {
            background: #c0392b;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .category-page {
                flex-direction: column;
            }
            
            .sidebar {
                position: static;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .foods-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .foods-grid {
                grid-template-columns: 1fr;
            }
            
            .content-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <!-- Main Content -->
    <div class="container">
        <div class="category-page">
            <!-- Sidebar Categories -->
            <aside class="sidebar">
                <h3>Categories</h3>
                <ul class="categories-list">
                    <li>
                        <a href="menu.php">
                            <span>All Categories</span>
                            <span class="cat-count"><?php echo count($categories); ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        // Count foods in this category
                        $conn = getDBConnection();
                        $countQuery = "SELECT COUNT(*) as food_count FROM foods 
                                      WHERE category_id = {$cat['id']} 
                                      AND is_available = 1";
                        $countResult = mysqli_query($conn, $countQuery);
                        $foodCount = 0;
                        if ($countResult) {
                            $countRow = mysqli_fetch_assoc($countResult);
                            $foodCount = $countRow['food_count'];
                        }
                        mysqli_close($conn);
                        ?>
                        <li>
                            <a href="categories-menu.php?category_id=<?php echo $cat['id']; ?>" 
                               class="<?php echo ($category_id == $cat['id']) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="cat-count"><?php echo $foodCount; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <div class="content-header">
                    <h2><?php echo htmlspecialchars($selected_category['name']); ?> Items</h2>
                    <div class="food-count"><?php echo count($foods); ?> Items Available</div>
                </div>

                <?php if (!empty($foods)): ?>
                    <div class="foods-grid">
                        <?php foreach ($foods as $food): ?>
                            <?php
                            // Handle image path
                            $imagePath = BASE_URL . 'assets/images/default-food.jpg';
                            if (!empty($food['image_path'])) {
                                if (strpos($food['image_path'], 'assets/') === 0) {
                                    $imagePath = BASE_URL . $food['image_path'];
                                } else {
                                    $imagePath = BASE_URL . 'assets/uploads/foods/' . basename($food['image_path']);
                                }
                            }
                            
                            // Calculate discount if any
                            $hasDiscount = ($food['original_price'] && $food['original_price'] > $food['price']);
                            $discountPercent = $hasDiscount ? round((($food['original_price'] - $food['price']) / $food['original_price']) * 100) : 0;
                            ?>
                            <div class="food-card">
                                <div class="food-image">
                                    <img src="<?php echo $imagePath; ?>"
                                         alt="<?php echo htmlspecialchars($food['name']); ?>"
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-food.jpg'">
                                    
                                    <div class="food-badges">
                                        <?php if ($food['is_popular']): ?>
                                            <span class="badge popular">Popular</span>
                                        <?php endif; ?>
                                        <?php if ($food['is_featured']): ?>
                                            <span class="badge featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($discountPercent > 0): ?>
                                            <span class="badge new">-<?php echo $discountPercent; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="food-info">
                                    <div class="food-title">
                                        <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                                        <div class="food-price-row">
                                            <span class="food-price">₹<?php echo number_format($food['price'], 2); ?></span>
                                            <?php if ($hasDiscount): ?>
                                                <span class="original-price">₹<?php echo number_format($food['original_price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="food-desc"><?php echo htmlspecialchars(substr($food['description'] ?? '', 0, 100)); ?>
                                       <?php if (strlen($food['description'] ?? '') > 100): ?>...<?php endif; ?>
                                    </p>
                                    
                                    <div class="food-meta">
                                        <?php if (isset($food['preparation_time']) && $food['preparation_time']): ?>
                                            <div class="prep-time">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($food['preparation_time']); ?> mins</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($food['rating']) && $food['rating']): ?>
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($food['rating'], 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($auth->isLoggedIn()): ?>
                                        <button class="btn-add-cart" data-food-id="<?php echo $food['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/login.php" class="btn-add-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-items">
                        <i class="fas fa-utensils"></i>
                        <h3>No Items Available</h3>
                        <p>Sorry, there are currently no food items available in this category.</p>
                        <a href="menu.php" class="btn-explore">
                            <i class="fas fa-arrow-left"></i> Back to All Categories
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
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
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="notification-close">&times;</button>
                `;
                
                // Add styles
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
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
    </script>
</body>
</html>