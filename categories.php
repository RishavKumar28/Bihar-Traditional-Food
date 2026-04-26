<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/db.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$conn = getDBConnection();
$currentUser = $auth->getCurrentUser();

// Get category ID from URL
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'popular';

// Get category details
$category = null;
if ($categoryId > 0) {
    $categoryQuery = "SELECT * FROM categories WHERE id = $categoryId AND is_active = 1";
    $categoryResult = mysqli_query($conn, $categoryQuery);
    if ($categoryResult && mysqli_num_rows($categoryResult) > 0) {
        $category = mysqli_fetch_assoc($categoryResult);
    }
}

// Build query for foods
$whereClause = "WHERE f.is_available = 1";
if ($categoryId > 0 && $category) {
    $whereClause .= " AND f.category_id = $categoryId";
}
if (!empty($searchQuery)) {
    $searchQuery = mysqli_real_escape_string($conn, $searchQuery);
    $whereClause .= " AND (f.name LIKE '%$searchQuery%' OR f.description LIKE '%$searchQuery%')";
}

// Sorting
$orderBy = "ORDER BY ";
switch ($sortBy) {
    case 'price_low':
        $orderBy .= "f.price ASC";
        break;
    case 'price_high':
        $orderBy .= "f.price DESC";
        break;
    case 'name':
        $orderBy .= "f.name ASC";
        break;
    case 'new':
        $orderBy .= "f.created_at DESC";
        break;
    default: // popular
        $orderBy .= "f.display_order ASC, f.created_at DESC";
        break;
}

// Get foods
$foodsQuery = "SELECT f.*, c.name as category_name 
               FROM foods f 
               LEFT JOIN categories c ON f.category_id = c.id 
               $whereClause 
               $orderBy";
$foodsResult = mysqli_query($conn, $foodsQuery);

// Get all categories for sidebar
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Get cart items count
$userId = $_SESSION['user_id'];
$cartCountQuery = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $userId";
$cartCountResult = mysqli_query($conn, $cartCountQuery);
$cartCount = $cartCountResult ? mysqli_fetch_assoc($cartCountResult)['count'] : 0;

// Get cart items for quick add functionality
$cartItemsQuery = "SELECT food_id, quantity FROM cart WHERE user_id = $userId";
$cartItemsResult = mysqli_query($conn, $cartItemsQuery);
$cartItems = [];
if ($cartItemsResult) {
    while ($item = mysqli_fetch_assoc($cartItemsResult)) {
        $cartItems[$item['food_id']] = $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? htmlspecialchars($category['name']) . ' - ' : ''; ?>Menu - Bihar Traditional Food</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Menu Page Layout */
        .menu-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .menu-title h1 {
            font-size: 32px;
            color: #1e293b;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .menu-title p {
            color: #64748b;
            font-size: 16px;
            margin: 0;
        }

        /* Menu Layout */
        .menu-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }

        /* Categories Sidebar */
        .categories-sidebar {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .categories-sidebar h3 {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li {
            margin-bottom: 10px;
        }

        .category-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 500;
        }

        .category-list a:hover {
            background: #f0fdf4;
            color: #16a34a;
            transform: translateX(5px);
        }

        .category-list a.active {
            background: #dcfce7;
            color: #16a34a;
            font-weight: 600;
            border-left: 4px solid #16a34a;
        }

        .category-count {
            background: #22c55e;
            color: white;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 24px;
            text-align: center;
        }

        .category-list a:hover .category-count {
            background: #16a34a;
        }

        .category-list a.active .category-count {
            background: #15803d;
        }

        /* Search and Filter */
        .menu-filters {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
        }

        .search-btn:hover {
            color: #22c55e;
        }

        .sort-dropdown {
            position: relative;
            min-width: 200px;
        }

        .sort-dropdown select {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            appearance: none;
        }

        .sort-dropdown:after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        /* Foods Grid */
        .foods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .food-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #22c55e;
        }

        .food-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #22c55e;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
        }

        .food-image {
            height: 200px;
            overflow: hidden;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
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

        .food-info {
            padding: 20px;
        }

        .food-category {
            display: inline-block;
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .food-title {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 10px;
            font-weight: 600;
            line-height: 1.3;
        }

        .food-desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            min-height: 42px;
        }

        .food-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .food-price {
            font-size: 20px;
            color: #22c55e;
            font-weight: 700;
        }

        .food-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f0fdf4;
            border-radius: 8px;
            padding: 4px;
            border: 1px solid #dcfce7;
        }

        .qty-btn {
            background: white;
            border: 1px solid #dcfce7;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            color: #16a34a;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #dcfce7;
            color: #15803d;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .qty-input:focus {
            outline: none;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
        }

        .btn-added {
            background: linear-gradient(135deg, #15803d, #166534);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #22c55e;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .menu-layout {
                grid-template-columns: 240px 1fr;
                gap: 20px;
            }
            
            .foods-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .menu-layout {
                grid-template-columns: 1fr;
            }
            
            .categories-sidebar {
                position: static;
                margin-bottom: 20px;
            }
            
            .menu-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .sort-dropdown {
                min-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .menu-container {
                padding: 15px;
            }
            
            .foods-grid {
                grid-template-columns: 1fr;
            }
            
            .food-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .food-actions {
                justify-content: space-between;
            }
            
            .menu-title h1 {
                font-size: 24px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            border: 3px solid #f0fdf4;
            border-top: 3px solid #22c55e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #22c55e;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.error {
            background: #ef4444;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <main class="menu-container">
        <!-- Page Header -->
        <div class="menu-header">
            <div class="menu-title">
                <h1>
                    <?php if($category): ?>
                        <?php echo htmlspecialchars($category['name']); ?>
                        <span style="color: #22c55e; font-size: 20px;">Menu</span>
                    <?php else: ?>
                        All Food Items
                    <?php endif; ?>
                </h1>
                <p>
                    <?php if($category): ?>
                        <?php echo htmlspecialchars($category['description'] ?? 'Traditional Bihari cuisine'); ?>
                    <?php else: ?>
                        Browse our complete collection of traditional Bihari foods
                    <?php endif; ?>
                </p>
            </div>
            <div class="cart-indicator">
                <a href="cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="menu-layout">
            <!-- Categories Sidebar -->
            <aside class="categories-sidebar">
                <h3><i class="fas fa-list"></i> Categories</h3>
                <ul class="category-list">
                    <li>
                        <a href="menu.php" class="<?php echo !$categoryId ? 'active' : ''; ?>">
                            <span>All Items</span>
                            <span class="category-count">
                                <?php 
                                $allCountQuery = "SELECT COUNT(*) as count FROM foods WHERE is_available = 1";
                                $allCountResult = mysqli_query($conn, $allCountQuery);
                                echo $allCountResult ? mysqli_fetch_assoc($allCountResult)['count'] : 0;
                                ?>
                            </span>
                        </a>
                    </li>
                    <?php if(mysqli_num_rows($categoriesResult) > 0): ?>
                        <?php mysqli_data_seek($categoriesResult, 0); ?>
                        <?php while($cat = mysqli_fetch_assoc($categoriesResult)): ?>
                            <?php 
                            $catCountQuery = "SELECT COUNT(*) as count FROM foods WHERE category_id = " . $cat['id'] . " AND is_available = 1";
                            $catCountResult = mysqli_query($conn, $catCountQuery);
                            $itemCount = $catCountResult ? mysqli_fetch_assoc($catCountResult)['count'] : 0;
                            ?>
                            <li>
                                <a href="menu.php?category=<?php echo $cat['id']; ?>" 
                                   class="<?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <span class="category-count"><?php echo $itemCount; ?></span>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </aside>

            <!-- Main Content -->
            <div class="menu-content">
                <!-- Search and Filter -->
                <div class="menu-filters">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <div class="filter-row">
                            <div class="search-box">
                                <input type="text" name="search" placeholder="Search food items..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="sort-dropdown">
                                <select name="sort" onchange="this.form.submit()">
                                    <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                    <option value="price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                                    <option value="new" <?php echo $sortBy == 'new' ? 'selected' : ''; ?>>Newest First</option>
                                </select>
                            </div>
                            <?php if(!empty($searchQuery)): ?>
                                <a href="menu.php?category=<?php echo $categoryId; ?>" class="btn-clear">
                                    Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Foods Grid -->
                <div id="foods-container">
                    <?php if(mysqli_num_rows($foodsResult) > 0): ?>
                        <div class="foods-grid">
                            <?php while($food = mysqli_fetch_assoc($foodsResult)): ?>
                                <?php 
                                $isInCart = isset($cartItems[$food['id']]);
                                $cartQty = $isInCart ? $cartItems[$food['id']] : 1;
                                $foodImage = !empty($food['image_url']) ? BASE_URL . 'uploads/foods/' . $food['image_url'] : BASE_URL . 'assets/images/default-food.jpg';
                                ?>
                                <div class="food-card" data-food-id="<?php echo $food['id']; ?>">
                                    <?php if($food['is_featured']): ?>
                                        <div class="food-badge">Featured</div>
                                    <?php endif; ?>
                                    <div class="food-image">
                                        <img src="<?php echo $foodImage; ?>" 
                                             alt="<?php echo htmlspecialchars($food['name']); ?>"
                                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-food.jpg'">
                                    </div>
                                    <div class="food-info">
                                        <span class="food-category"><?php echo htmlspecialchars($food['category_name']); ?></span>
                                        <h3 class="food-title"><?php echo htmlspecialchars($food['name']); ?></h3>
                                        <p class="food-desc"><?php echo substr(htmlspecialchars($food['description']), 0, 80); ?>...</p>
                                        <div class="food-footer">
                                            <div class="food-price">₹<?php echo number_format($food['price'], 2); ?></div>
                                            <div class="food-actions">
                                                <?php if($isInCart): ?>
                                                    <div class="quantity-controls">
                                                        <button class="qty-btn minus" onclick="updateQuantity(<?php echo $food['id']; ?>, -1)">-</button>
                                                        <input type="text" class="qty-input" value="<?php echo $cartQty; ?>" readonly>
                                                        <button class="qty-btn plus" onclick="updateQuantity(<?php echo $food['id']; ?>, 1)">+</button>
                                                    </div>
                                                    <button class="btn-add-cart btn-added" onclick="removeFromCart(<?php echo $food['id']; ?>)">
                                                        <i class="fas fa-check"></i> Added
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-add-cart" onclick="addToCart(<?php echo $food['id']; ?>)">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-utensils"></i>
                            <h3>No Food Items Found</h3>
                            <p>
                                <?php if(!empty($searchQuery)): ?>
                                    No items found for "<?php echo htmlspecialchars($searchQuery); ?>"
                                <?php elseif($category): ?>
                                    No items available in <?php echo htmlspecialchars($category['name']); ?> category
                                <?php else: ?>
                                    No food items available at the moment
                                <?php endif; ?>
                            </p>
                            <?php if(!empty($searchQuery) || $category): ?>
                                <a href="menu.php" class="btn-back">
                                    <i class="fas fa-arrow-left"></i> View All Items
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <script>
    // Cart functionality
    async function addToCart(foodId) {
        const foodCard = document.querySelector(`.food-card[data-food-id="${foodId}"]`);
        const addButton = foodCard.querySelector('.btn-add-cart');
        
        // Show loading state
        addButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        addButton.disabled = true;
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `food_id=${foodId}&quantity=1&action=add`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                addButton.innerHTML = '<i class="fas fa-check"></i> Added';
                addButton.className = 'btn-add-cart btn-added';
                addButton.onclick = () => removeFromCart(foodId);
                
                // Add quantity controls
                const foodActions = foodCard.querySelector('.food-actions');
                foodActions.innerHTML = `
                    <div class="quantity-controls">
                        <button class="qty-btn minus" onclick="updateQuantity(${foodId}, -1)">-</button>
                        <input type="text" class="qty-input" value="1" readonly>
                        <button class="qty-btn plus" onclick="updateQuantity(${foodId}, 1)">+</button>
                    </div>
                    <button class="btn-add-cart btn-added" onclick="removeFromCart(${foodId})">
                        <i class="fas fa-check"></i> Added
                    </button>
                `;
                
                // Update cart count
                updateCartCount(data.cart_count);
                
                showNotification('Item added to cart successfully!', 'success');
            } else {
                showNotification(data.message || 'Failed to add item', 'error');
                resetButton(addButton, 'Add to Cart');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
            resetButton(addButton, 'Add to Cart');
        }
    }
    
    async function removeFromCart(foodId) {
        const foodCard = document.querySelector(`.food-card[data-food-id="${foodId}"]`);
        const removeButton = foodCard.querySelector('.btn-added');
        
        // Show loading state
        removeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
        removeButton.disabled = true;
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `food_id=${foodId}&action=remove`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const foodActions = foodCard.querySelector('.food-actions');
                foodActions.innerHTML = `
                    <button class="btn-add-cart" onclick="addToCart(${foodId})">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                `;
                
                // Update cart count
                updateCartCount(data.cart_count);
                
                showNotification('Item removed from cart', 'success');
            } else {
                showNotification(data.message || 'Failed to remove item', 'error');
                resetButton(removeButton, 'Added');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
            resetButton(removeButton, 'Added');
        }
    }
    
    async function updateQuantity(foodId, change) {
        const foodCard = document.querySelector(`.food-card[data-food-id="${foodId}"]`);
        const qtyInput = foodCard.querySelector('.qty-input');
        let currentQty = parseInt(qtyInput.value);
        const newQty = currentQty + change;
        
        if (newQty < 1) {
            removeFromCart(foodId);
            return;
        }
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `food_id=${foodId}&quantity=${newQty}&action=update`
            });
            
            const data = await response.json();
            
            if (data.success) {
                qtyInput.value = newQty;
                updateCartCount(data.cart_count);
                
                if (newQty === 1) {
                    showNotification('Quantity updated', 'success');
                }
            } else {
                showNotification(data.message || 'Failed to update quantity', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
        }
    }
    
    function resetButton(button, text) {
        button.disabled = false;
        if (text === 'Add to Cart') {
            button.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
            button.className = 'btn-add-cart';
        } else {
            button.innerHTML = '<i class="fas fa-check"></i> Added';
            button.className = 'btn-add-cart btn-added';
        }
    }
    
    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
    }
    
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Initialize cart buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers for quantity controls
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('qty-btn')) {
                e.preventDefault();
            }
        });
        
        // Update cart count on page load
        updateCartCount(<?php echo $cartCount; ?>);
    });
    </script>
</body>
</html>