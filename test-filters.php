<?php
require_once 'includes/config.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Test 1: Count featured items
$featuredCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM foods WHERE is_available = 1 AND is_featured = 1");
$featured = mysqli_fetch_assoc($featuredCount);

// Test 2: Count popular items
$popularCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM foods WHERE is_available = 1 AND is_popular = 1");
$popular = mysqli_fetch_assoc($popularCount);

// Test 3: Show featured items
$featuredItems = mysqli_query($conn, "SELECT id, name, is_featured, is_popular FROM foods WHERE is_available = 1 AND is_featured = 1 ORDER BY name");

// Test 4: Show popular items
$popularItems = mysqli_query($conn, "SELECT id, name, is_featured, is_popular FROM foods WHERE is_available = 1 AND is_popular = 1 ORDER BY name");

echo "<h2>Filter Test Results</h2>";
echo "<p><strong>Featured Items Count:</strong> " . $featured['count'] . "</p>";
echo "<p><strong>Popular Items Count:</strong> " . $popular['count'] . "</p>";

echo "<h3>Featured Items:</h3>";
echo "<ul>";
if ($featuredItems && mysqli_num_rows($featuredItems) > 0) {
    while ($item = mysqli_fetch_assoc($featuredItems)) {
        echo "<li>" . htmlspecialchars($item['name']) . " (Featured: " . $item['is_featured'] . ", Popular: " . $item['is_popular'] . ")</li>";
    }
} else {
    echo "<li>No featured items found</li>";
}
echo "</ul>";

echo "<h3>Popular Items:</h3>";
echo "<ul>";
if ($popularItems && mysqli_num_rows($popularItems) > 0) {
    while ($item = mysqli_fetch_assoc($popularItems)) {
        echo "<li>" . htmlspecialchars($item['name']) . " (Featured: " . $item['is_featured'] . ", Popular: " . $item['is_popular'] . ")</li>";
    }
} else {
    echo "<li>No popular items found</li>";
}
echo "</ul>";

echo "<h3>Test Filter URLs:</h3>";
echo "<ul>";
echo "<li><a href='users/menu.php?filter_type=all'>All Items</a></li>";
echo "<li><a href='users/menu.php?filter_type=featured'>Featured Items</a></li>";
echo "<li><a href='users/menu.php?filter_type=popular'>Popular Items</a></li>";
echo "</ul>";
?>
