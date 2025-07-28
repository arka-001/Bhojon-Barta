<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION["db_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db_id = $_SESSION["db_id"];
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$longitude = isset($_GET['lon']) ? floatval($_GET['lon']) : null;

if ($latitude === null || $longitude === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit();
}

// Update latitude and longitude in the delivery_boy table
$sql = "UPDATE delivery_boy SET latitude = ?, longitude = ?, updated_at = CURRENT_TIMESTAMP WHERE db_id = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ddi", $latitude, $longitude, $db_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

mysqli_stmt_close($stmt);
mysqli_close($db);
?>