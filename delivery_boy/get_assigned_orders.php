<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION["db_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db_id = $_SESSION["db_id"];

$sql = "SELECT o_id FROM users_orders WHERE delivery_boy_id = ? AND status NOT IN ('closed', 'rejected')";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "i", $db_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$order_ids = array_column($orders, 'o_id');
echo json_encode(['success' => true, 'order_ids' => $order_ids]);
mysqli_close($db);
?>