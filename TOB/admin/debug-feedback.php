<?php
require_once '../includes/config.php';

// Simple debug page for feedback table
header('Content-Type: text/plain; charset=utf-8');

$conn = getDBConnection();
if (!$conn) {
    echo "DB connection failed\n";
    exit;
}

echo "Feedback Debug Report\n";
echo "=====================\n\n";

// Total rows
$res = mysqli_query($conn, "SELECT COUNT(*) as c FROM feedback");
$row = mysqli_fetch_assoc($res);
echo "Total rows in feedback: " . ($row['c'] ?? '0') . "\n\n";

// Counts by status
$res = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM feedback GROUP BY status");
echo "Counts by status:\n";
while ($r = mysqli_fetch_assoc($res)) {
    $st = $r['status'] ?? '(NULL)';
    echo " - $st : " . $r['cnt'] . "\n";
}

echo "\nRecent 100 rows (id, user_id, rating, status, created_at):\n";
$res = mysqli_query($conn, "SELECT id, user_id, rating, status, created_at FROM feedback ORDER BY id DESC LIMIT 100");
while ($r = mysqli_fetch_assoc($res)) {
    echo sprintf("%d | %s | %s | %s | %s\n", $r['id'], $r['user_id'] ?? 'NULL', $r['rating'] ?? 'NULL', $r['status'] ?? 'NULL', $r['created_at'] ?? 'NULL');
}

mysqli_close($conn);

?>