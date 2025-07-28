<?php
header('Content-Type: application/json');
include("../connection/connect.php");

$order_id = isset($_GET['o_id']) && is_numeric($_GET['o_id']) ? (int)$_GET['o_id'] : null;

if (!$order_id) {
    echo json_encode(['error' => 'Invalid Order ID']);
    exit();
}

$sql = "SELECT status FROM users_orders WHERE o_id = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($order) {
    echo json_encode(['status' => $order['status']]);
} else {
    echo json_encode(['error' => 'Order not found']);
}
?>