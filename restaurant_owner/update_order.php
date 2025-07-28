<?php
include("../connection/connect.php");

$stmt = $db->prepare("UPDATE users_orders SET status = ? WHERE o_id = ?");
$stmt->bind_param("si", $_POST['status'], $_POST['order_id']);
$stmt->execute();
$stmt->close();
echo json_encode(['success' => true]);
?>