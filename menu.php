<?php
// Enable error reporting for debugging (disabled display to users)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users, log to file instead

require_once '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Now include wishlist support
require_once 'get-wishlist.php';

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    die("Database connection failed");
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all'; // Add filter_type parameter

// Build the query
$foodsQuery = "SELECT f.*, c.name as category_name FROM foods f 
               LEFT JOIN categories c ON f.category_id = c.id 
               WHERE f.is_available = 1";

// Apply filter_type filter (Featured/Popular/All)
if ($filter_type === 'featured') {
    $foodsQuery .= " AND f.is_featured = 1";
} elseif ($filter_type === 'popular') {
    $foodsQuery .= " AND f.is_popular = 1";
}

// Apply search filter
if (!empty($search)) {
    $searchTerm = mysqli_real_escape_string($conn, $search);
    $foodsQuery .= " AND (f.name LIKE '%$searchTerm%' OR f.description LIKE '%$searchTerm%')";
}

// Apply category filter
if ($category > 0) {
    $foodsQuery .= " AND f.category_id = $category";
}

// Apply price filters
if ($min_price > 0) {
    $foodsQuery .= " AND f.price >= $min_price";
}
if ($max_price > 0) {
    $foodsQuery .= " AND f.price <= $max_price";
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $foodsQuery .= " ORDER BY f.price ASC";
        break;
    case 'price_high':
        $foodsQuery .= " ORDER BY f.price DESC";
        break;
    case 'name':
        $foodsQuery .= " ORDER BY f.name ASC";
        break;
    case 'newest':
        $foodsQuery .= " ORDER BY f.id DESC";
        break;
    default: // featured
        $foodsQuery .= " ORDER BY f.is_featured DESC, f.name ASC";
}

// Execute query
$foodsResult = mysqli_query($conn, $foodsQuery);

// Get categories for dropdown
$categoriesQuery = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Get price range for price filter
$priceRangeQuery = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM foods WHERE is_available = 1";
$priceRangeResult = mysqli_query($conn, $priceRangeQuery);
$priceRange = mysqli_fetch_assoc($priceRangeResult);
$min_price_range = $priceRange['min_price'] ?? 0;
$max_price_range = $priceRange['max_price'] ?? 1000;

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Count results
$foodsCount = $foodsResult ? mysqli_num_rows($foodsResult) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .menu-container {
            max-width: 1400px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }

        .menu-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .menu-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        /* Quick Filters Section */
        .quick-filters-section {
            text-align: center;
            margin: 30px 0 40px 0;
            padding: 20px;
        }

        .quick-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .quick-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #e74c3c;
            border: 2px solid #e74c3c;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-filter-btn:hover {
            background: #ffe5e0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2);
        }

        .quick-filter-btn.active {
            background: #e74c3c;
            color: white;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .quick-filter-btn i {
            font-size: 1.1rem;
        }

        .menu-filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .price-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-inputs input {
            width: 100px;
        }

        .price-inputs span {
            color: #666;
        }

        .btn-filter {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: #c0392b;
        }

        .btn-clear {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            width: 100%;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-clear:hover {
            background: #7f8c8d;
        }

        .results-count {
            margin-bottom: 20px;
            color: #666;
            font-size: 1.1rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .menu-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-5px);
        }

        .menu-card-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .menu-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-card:hover .menu-card-image img {
            transform: scale(1.05);
        }

        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .unavailable-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(149, 165, 166, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .menu-card-content {
            padding: 20px;
        }

        .menu-card-content h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: #333;
            min-height: 60px;
        }

        .menu-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
            min-height: 60px;
        }

        .menu-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #e74c3c;
        }

        .original-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .btn-add-to-cart {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn-add-to-cart:hover {
            background: #219653;
        }

        .btn-add-to-cart:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .btn-wishlist {
            background: #e74c3c;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: absolute;
            bottom: 10px;
            left: 10px;
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

        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: #666;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .no-results i {
            margin-bottom: 20px;
            color: #ddd;
            font-size: 4rem;
        }

        .no-results h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 2rem;
        }

        .no-results p {
            margin-bottom: 30px;
            font-size: 1.1rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .solution-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn-hero {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-hero:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .filter-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }

        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .filter-tag {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-filter {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .price-inputs {
                flex-direction: column;
                align-items: flex-start;
            }

            .price-inputs input {
                width: 100%;
            }

            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="menu-container">
        <div class="menu-header">
            <h1><i class="fas fa-utensils"></i> Our Menu</h1>
            <p>Discover authentic Bihari flavors</p>
        </div>

        <!-- Quick Filter Buttons -->
        <div class="quick-filters-section">
            <div class="quick-filters">
                <button type="button" class="quick-filter-btn <?php echo $filter_type === 'all' ? 'active' : ''; ?>"
                    onclick="filterByType('all')">
                    <i class="fas fa-th"></i> All Items
                </button>
                <button type="button" class="quick-filter-btn <?php echo $filter_type === 'featured' ? 'active' : ''; ?>"
                    onclick="filterByType('featured')">
                    <i class="fas fa-star"></i> Featured
                </button>
                <button type="button" class="quick-filter-btn <?php echo $filter_type === 'popular' ? 'active' : ''; ?>"
                    onclick="filterByType('popular')">
                    <i class="fas fa-fire"></i> Popular
                </button>
            </div>
        </div>

        <!-- Active Filters Info -->
        <?php if ($category > 0 || $min_price > 0 || $max_price > 0 || !empty($search) || $sort != 'featured' || $filter_type != 'all'): ?>
            <div class="filter-info">
                <strong>Active Filters:</strong>
                <div class="filter-tags">
                    <?php if ($filter_type === 'featured'): ?>
                        <span class="filter-tag">
                            <i class="fas fa-star"></i> Featured Items
                            <button class="remove-filter" onclick="removeFilter('filter_type')">&times;</button>
                        </span>
                    <?php elseif ($filter_type === 'popular'): ?>
                        <span class="filter-tag">
                            <i class="fas fa-fire"></i> Popular Items
                            <button class="remove-filter" onclick="removeFilter('filter_type')">&times;</button>
                        </span>
                    <?php endif; ?>
                    <?php if ($category > 0):
                        $catQuery = "SELECT name FROM categories WHERE id = $category";
                        $catResult = mysqli_query($conn, $catQuery);
                        $catName = $catResult ? mysqli_fetch_assoc($catResult)['name'] : 'Category';
                    ?>
                        <span class="filter-tag">
                            Category: <?php echo htmlspecialchars($catName); ?>
                            <button class="remove-filter" onclick="removeFilter('category')">&times;</button>
                        </span>
                    <?php endif; ?>

                    <?php if ($min_price > 0): ?>
                        <span class="filter-tag">
                            Min Price: ₹<?php echo number_format($min_price, 2); ?>
                            <button class="remove-filter" onclick="removeFilter('min_price')">&times;</button>
                        </span>
                    <?php endif; ?>

                    <?php if ($max_price > 0): ?>
                        <span class="filter-tag">
                            Max Price: ₹<?php echo number_format($max_price, 2); ?>
                            <button class="remove-filter" onclick="removeFilter('max_price')">&times;</button>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($search)): ?>
                        <span class="filter-tag">
                            Search: "<?php echo htmlspecialchars($search); ?>"
                            <button class="remove-filter" onclick="removeFilter('search')">&times;</button>
                        </span>
                    <?php endif; ?>

                    <?php if ($sort != 'featured'):
                        $sortNames = [
                            'price_low' => 'Price: Low to High',
                            'price_high' => 'Price: High to Low',
                            'name' => 'Name: A-Z',
                            'newest' => 'Newest First',
                            'featured' => 'Featured'
                        ];
                    ?>
                        <span class="filter-tag">
                            Sort: <?php echo $sortNames[$sort] ?? 'Featured'; ?>
                            <button class="remove-filter" onclick="removeFilter('sort')">&times;</button>
                        </span>
                    <?php endif; ?>

                    <?php if ($category > 0 || $min_price > 0 || $max_price > 0 || !empty($search) || $sort != 'featured'): ?>
                        <span class="filter-tag" style="background: #3498db;">
                            <a href="menu.php" style="color: white; text-decoration: none;">Clear All Filters</a>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Results Count -->
        <div class="results-count">
            <?php echo $foodsCount; ?> item<?php echo $foodsCount != 1 ? 's' : ''; ?> found
        </div>

        <!-- Filters -->
        <div class="menu-filters">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <!-- Search -->
                    <div class="filter-group">
                        <label for="search"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" id="search" placeholder="Search food items..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <!-- Category -->
                    <div class="filter-group">
                        <label for="category"><i class="fas fa-tags"></i> Category</label>
                        <select name="category" id="category">
                            <option value="0">All Categories</option>
                            <?php
                            if ($categoriesResult && mysqli_num_rows($categoriesResult) > 0):
                                while ($cat = mysqli_fetch_assoc($categoriesResult)):
                            ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                            <?php
                                endwhile;
                                mysqli_data_seek($categoriesResult, 0);
                            endif;
                            ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <label><i class="fas fa-rupee-sign"></i> Price Range</label>
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="Min"
                                min="0" max="<?php echo $max_price_range; ?>" step="10"
                                value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max"
                                min="0" max="<?php echo $max_price_range; ?>" step="10"
                                value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                        </div>
                        <small>Price range: ₹<?php echo number_format($min_price_range, 2); ?> - ₹<?php echo number_format($max_price_range, 2); ?></small>
                    </div>

                    <!-- Sort -->
                    <div class="filter-group">
                        <label for="sort"><i class="fas fa-sort"></i> Sort By</label>
                        <select name="sort" id="sort">
                            <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button type="button" class="btn-clear" onclick="window.location.href='menu.php'">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Menu Grid -->
        <div class="menu-content">
            <?php if ($foodsResult && $foodsCount > 0): ?>
                <div class="menu-grid">
                    <?php while ($food = mysqli_fetch_assoc($foodsResult)):
                        // Handle image path
                        $imagePath = '../assets/images/default-food.jpg';
                        if (!empty($food['image_path'])) {
                            // Check different possible locations
                            $possiblePaths = [
                                '../' . $food['image_path'],
                                '../assets/uploads/foods/' . basename($food['image_path']),
                                '../assets/images/foods/' . basename($food['image_path']),
                                $food['image_path']
                            ];

                            foreach ($possiblePaths as $path) {
                                if (file_exists($path)) {
                                    $imagePath = $path;
                                    break;
                                }
                            }
                        }
                    ?>
                        <div class="menu-card">
                            <div class="menu-card-image">
                                <img src="<?php echo $imagePath; ?>"
                                    alt="<?php echo htmlspecialchars($food['name']); ?>"
                                    onerror="this.src='../assets/images/default-food.jpg'">
                                <?php if (!empty($food['category_name'])): ?>
                                    <span class="category-badge"><?php echo htmlspecialchars($food['category_name']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($food['is_featured']) && $food['is_featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
                                <?php if (isset($food['is_available']) && !$food['is_available']): ?>
                                    <span class="unavailable-badge">Unavailable</span>
                                <?php endif; ?>
                                <?php if ($isLoggedIn): ?>
                                    <?php $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>
                                    <button type="button" class="btn-wishlist <?php echo isInWishlist($food['id'], $userId) ? 'in-wishlist' : ''; ?>" 
                                            onclick="toggleWishlist(<?php echo $food['id']; ?>, event)" 
                                            title="<?php echo isInWishlist($food['id'], $userId) ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                                        <i class="<?php echo isInWishlist($food['id'], $userId) ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="menu-card-content">
                                <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                                <p class="menu-description"><?php echo htmlspecialchars(substr($food['description'], 0, 80)); ?>...</p>

                                <div class="menu-card-footer">
                                    <div class="price-row">
                                        <div class="price">₹<?php echo number_format($food['price'] ?? 0, 2); ?></div>
                                        <?php if ((isset($food['original_price']) && $food['original_price']) && ((isset($food['original_price']) && $food['original_price']) > ($food['price'] ?? 0))): ?>
                                            <div class="original-price">₹<?php echo number_format($food['original_price'], 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ((isset($food['is_available']) && $food['is_available']) && $isLoggedIn): ?>
                                        <button class="btn-add-to-cart" onclick="addToCart(<?php echo $food['id']; ?>)">
                                            <i class="fas fa-cart-plus"></i> Add
                                        </button>
                                    <?php elseif (isset($food['is_available']) && $food['is_available']): ?>
                                        <a href="../login.php" class="btn-add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-add-to-cart" disabled style="background: #95a5a6;">
                                            <i class="fas fa-ban"></i> Unavailable
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-utensils"></i>
                    <h2>No Food Items Found</h2>
                    <p>We couldn't find any items matching your criteria. Try adjusting your filters or browse our full menu.</p>
                    <div class="solution-buttons">
                        <a href="menu.php" class="btn-hero" style="background: #3498db;">
                            <i class="fas fa-eye"></i> View All Items
                        </a>
                        <a href="../index.php" class="btn-hero" style="background: #2ecc71;">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Filter by type function (All, Featured, Popular)
        function filterByType(type) {
            // Use current pathname and add filter_type parameter
            const url = new URL(window.location.href);
            url.searchParams.set('filter_type', type);
            window.location.href = url.toString();
        }

        // Add to Cart functionality
        function addToCart(foodId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add-to-cart.php';

            const foodIdInput = document.createElement('input');
            foodIdInput.type = 'hidden';
            foodIdInput.name = 'food_id';
            foodIdInput.value = foodId;

            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = 1;

            form.appendChild(foodIdInput);
            form.appendChild(quantityInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Remove individual filter
        function removeFilter(filterName) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }

        // Price validation
        document.querySelector('input[name="min_price"]').addEventListener('change', function() {
            const maxInput = document.querySelector('input[name="max_price"]');
            const minValue = parseFloat(this.value);
            const maxValue = parseFloat(maxInput.value);

            if (maxValue > 0 && minValue > maxValue) {
                alert('Minimum price cannot be greater than maximum price');
                this.value = '';
            }
        });

        document.querySelector('input[name="max_price"]').addEventListener('change', function() {
            const minInput = document.querySelector('input[name="min_price"]');
            const maxValue = parseFloat(this.value);
            const minValue = parseFloat(minInput.value);

            if (minValue > 0 && maxValue < minValue) {
                alert('Maximum price cannot be less than minimum price');
                this.value = '';
            }
        });

        // Quick filter buttons (optional - you can add these if needed)
        const quickFilters = document.querySelectorAll('.quick-filter');
        quickFilters.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.dataset.filter;
                const value = this.dataset.value;

                if (filter === 'price') {
                    document.querySelector('input[name="min_price"]').value = value.split('-')[0];
                    document.querySelector('input[name="max_price"]').value = value.split('-')[1];
                } else if (filter === 'category') {
                    document.querySelector('select[name="category"]').value = value;
                }

                document.querySelector('.filter-form').submit();
            });
        });
    </script>

    <script>
        // Add to Cart functionality - using fetch API
        function addToCart(foodId) {
            // Check if user is logged in (you should set this from PHP)
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
                window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }

            // Show loading state
            const button = event.target;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;

            // Try AJAX first
            fetch('add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'food_id=' + foodId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success - show message
                        showCartMessage(data.message, 'success');
                        updateCartCount();
                    } else {
                        // Error - show message
                        showCartMessage(data.message, 'error');
                    }
                    // Restore button
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to form submission if AJAX fails
                    fallbackFormSubmit(foodId);
                });
        }

        // Fallback method using form submission
        function fallbackFormSubmit(foodId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add-to-cart.php';

            const foodIdInput = document.createElement('input');
            foodIdInput.type = 'hidden';
            foodIdInput.name = 'food_id';
            foodIdInput.value = foodId;

            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = 1;

            form.appendChild(foodIdInput);
            form.appendChild(quantityInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Show cart message
        function showCartMessage(message, type) {
            // Remove any existing message
            const existingMessage = document.querySelector('.cart-message');
            if (existingMessage) {
                existingMessage.remove();
            }

            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `cart-message ${type}`;
            messageDiv.innerHTML = `
        <div style="position: fixed; top: 100px; right: 20px; background: ${type === 'success' ? '#27ae60' : '#e74c3c'}; 
                    color: white; padding: 15px 20px; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                    z-index: 1000; display: flex; align-items: center; gap: 10px; max-width: 300px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" 
                    style="background: none; border: none; color: white; cursor: pointer; margin-left: auto;">
                &times;
            </button>
        </div>
    `;

            document.body.appendChild(messageDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentElement) {
                    messageDiv.remove();
                }
            }, 5000);
        }

        // Update cart count in header
        function updateCartCount() {
            // You can implement this to update cart count in header
            // For example, fetch cart count via AJAX
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount) {
                            cartCount.textContent = data.count;
                            cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                        }
                    }
                })
                .catch(console.error);
        }

        // Check for session messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a cart message from session
            <?php if (isset($_SESSION['cart_message'])): ?>
                showCartMessage('<?php echo addslashes($_SESSION['cart_message']); ?>',
                    '<?php echo $_SESSION['cart_success'] ? 'success' : 'error'; ?>');
                <?php
                // Clear session message
                unset($_SESSION['cart_message']);
                unset($_SESSION['cart_success']);
                ?>
            <?php endif; ?>
        });

        // Wishlist toggle functionality
        function toggleWishlist(foodId, event) {
            event.preventDefault();
            event.stopPropagation();

            const button = event.currentTarget;

            fetch('toggle-wishlist.php', {
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
                        showNotification(data.message, 'success');
                    } else {
                        button.classList.remove('in-wishlist');
                        button.querySelector('i').classList.add('far');
                        button.querySelector('i').classList.remove('fas');
                        button.title = 'Add to wishlist';
                        showNotification(data.message, 'info');
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

        // Notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#27ae60' : type === 'info' ? '#3498db' : '#e74c3c'};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;

            const icon = type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle';
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" 
                        style="background: none; border: none; color: white; cursor: pointer; margin-left: auto;">
                    &times;
                </button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Add slide animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

</body>

</html>