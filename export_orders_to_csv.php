<?php
require("connection/connect.php");

$recommendations_dir = __DIR__ . '/recommendations';
if (!is_dir($recommendations_dir)) {
    mkdir($recommendations_dir, 0777, true);
}

// Export favorites
$favorites_csv = $recommendations_dir . '/user_favorites.csv';
$file = fopen($favorites_csv, 'w');
if (!$file) {
    die("Failed to open $favorites_csv for writing.");
}
fputcsv($file, ['u_id', 'd_id', 'quantity']);
$sql = "SELECT u_id, d_id, 1 as quantity FROM user_favorite_dishes";
$result = mysqli_query($db, $sql);
if ($result === false) {
    error_log("SQL error: " . mysqli_error($db));
    die("Failed to fetch favorites data.");
}
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($file, [$row['u_id'], $row['d_id'], $row['quantity']]);
}
fclose($file);

// Export cart
$cart_csv = $recommendations_dir . '/user_cart.csv';
$file = fopen($cart_csv, 'w');
if (!$file) {
    die("Failed to open $cart_csv for writing.");
}
fputcsv($file, ['u_id', 'd_id', 'quantity']);
$sql = "SELECT u_id, d_id, quantity FROM cart";
$result = mysqli_query($db, $sql);
if ($result === false) {
    error_log("SQL error: " . mysqli_error($db));
    die("Failed to fetch cart data.");
}
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($file, [$row['u_id'], $row['d_id'], $row['quantity']]);
}
fclose($file);

mysqli_close($db);
echo "Favorites and cart data exported to $favorites_csv and $cart_csv";
?>