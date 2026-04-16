<?php
require_once 'includes/config.php';

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Update foods to mark them as featured or popular based on the original data
$updates = [
    // Featured and Popular items
    ['id' => 1, 'featured' => 1, 'popular' => 1],  // Litti Chokha Combo
    ['id' => 3, 'featured' => 1, 'popular' => 1],  // Sattu Paratha
    ['id' => 4, 'featured' => 1, 'popular' => 1],  // Sattu Sharbat
    ['id' => 5, 'featured' => 1, 'popular' => 1],  // Thekua
    ['id' => 7, 'featured' => 1, 'popular' => 1],  // Kadhi Chawal
    ['id' => 8, 'featured' => 1, 'popular' => 1],  // Samosa
    ['id' => 9, 'featured' => 1, 'popular' => 1],  // Khaja
    ['id' => 10, 'featured' => 1, 'popular' => 1], // Masala Chai
    
    // Only Popular items
    ['id' => 2, 'featured' => 0, 'popular' => 1],  // Masala Litti
    ['id' => 6, 'featured' => 0, 'popular' => 1],  // Dal Puri
];

foreach ($updates as $item) {
    $query = "UPDATE foods SET is_featured = {$item['featured']}, is_popular = {$item['popular']} WHERE id = {$item['id']}";
    if (mysqli_query($conn, $query)) {
        echo "Updated item {$item['id']}: Featured={$item['featured']}, Popular={$item['popular']}<br>";
    } else {
        echo "Error updating item {$item['id']}: " . mysqli_error($conn) . "<br>";
    }
}

// Show current status
echo "<hr>";
echo "<h2>Current Food Items Status</h2>";
$result = mysqli_query($conn, "SELECT id, name, is_featured, is_popular FROM foods ORDER BY id");
while ($row = mysqli_fetch_assoc($result)) {
    $featured = $row['is_featured'] ? '✓' : '✗';
    $popular = $row['is_popular'] ? '✓' : '✗';
    echo "ID {$row['id']}: {$row['name']} - Featured: $featured | Popular: $popular<br>";
}

echo "<hr>";
echo "<h2>Test Filter Results</h2>";
$featured_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM foods WHERE is_featured = 1"));
$popular_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM foods WHERE is_popular = 1"));
echo "Featured Items: {$featured_count['count']}<br>";
echo "Popular Items: {$popular_count['count']}<br>";

echo "<h2>Links to Test</h2>";
echo "<a href='users/menu.php?filter_type=all'>View All Items</a><br>";
echo "<a href='users/menu.php?filter_type=featured'>View Featured Items</a><br>";
echo "<a href='users/menu.php?filter_type=popular'>View Popular Items</a><br>";
?>
