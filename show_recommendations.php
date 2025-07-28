<?php
session_start();
require("connection/connect.php");

$python_path = 'C:\\xampp\\htdocs\\OnlineFood-PHP\\OnlineFood-PHP\\reco_env\\Scripts\\python.exe';
$python_script = __DIR__ . '\\recommend.py';
$command = "\"$python_path\" \"$python_script\" 2>&1";

echo "<pre>Executing command: $command</pre>";
$output = [];
$return_var = 0;
exec($command, $output, $return_var);

if ($return_var !== 0) {
    error_log("Python script error: " . implode("\n", $output));
    echo "<pre>Python script error:\n" . implode("\n", $output) . "</pre>";
    exit;
}

$recommendations_file = __DIR__ . '\\recommendations\\recommendations.json';
if (!file_exists($recommendations_file)) {
    echo "<p>Error: Recommendations file not found at $recommendations_file.</p>";
    exit;
}

$recommendations = json_decode(file_get_contents($recommendations_file), true);
if ($recommendations === null) {
    echo "<p>Error: Failed to parse recommendations JSON.</p>";
    exit;
}

$user_id = isset($_SESSION['u_id']) ? $_SESSION['u_id'] : null;
if (!$user_id || !isset($recommendations[$user_id])) {
    echo "<p>No recommendations available.</p>";
    exit;
}

$rec_dish_ids = $recommendations[$user_id];
$rec_dish_ids_str = implode(',', array_map('intval', $rec_dish_ids));
$sql = "SELECT d_id, title, slogan, price, img FROM dishes WHERE d_id IN ($rec_dish_ids_str) AND is_available = 1";
$result = mysqli_query($db, $sql);

if ($result === false) {
    error_log("SQL error: " . mysqli_error($db));
    echo "<p>Error fetching recommendations.</p>";
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo "<h2>Recommended Dishes for You</h2><div class='recommendations'>";
    while ($row = mysqli_fetch_assoc($result)) {
        $img_path = "admin/Res_img/dishes/" . htmlspecialchars($row['img']);
        echo "<div class='dish'>";
        echo "<img src='$img_path' alt='" . htmlspecialchars($row['title']) . "' style='width:100px;'>";
        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
        echo "<p>" . htmlspecialchars($row['slogan']) . "</p>";
        echo "<p>Price: $" . number_format($row['price'], 2) . "</p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p>No recommendations available at the moment.</p>";
}
mysqli_close($db);
?>