<?php
session_start();
include("../connection/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION["db_id"])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$db_id = $_SESSION["db_id"];
$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? intval($data['order_id']) : null;
$status = isset($data['status']) ? $data['status'] : null;
$request_db_id = isset($data['db_id']) ? intval($data['db_id']) : null;

if (!$order_id || !$status || $request_db_id !== $db_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Verify the order belongs to this delivery boy
$check_sql = "SELECT price, status FROM users_orders WHERE o_id = ? AND delivery_boy_id = ?";
$check_stmt = mysqli_prepare($db, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $db_id);
mysqli_stmt_execute($check_stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
mysqli_stmt_close($check_stmt);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
    exit();
}

// Update order status
$update_sql = "UPDATE users_orders SET status = ? WHERE o_id = ? AND delivery_boy_id = ?";
$update_stmt = mysqli_prepare($db, $update_sql);
mysqli_stmt_bind_param($update_stmt, "sii", $status, $order_id, $db_id);
$success = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    exit();
}

// If status is 'in_transit', log to delivery_boy_history
if ($status === 'in_transit') {
    // Fetch latest delivery charge and min_order_value
    $charge_sql = "SELECT delivery_charge, min_order_value FROM delivary_charges ORDER BY updated_at DESC LIMIT 1";
    $charge_result = mysqli_query($db, $charge_sql);
    $charge_row = mysqli_fetch_assoc($charge_result);
    $delivery_charge = $charge_row['delivery_charge'] ?? 50.00;
    $min_order_value = $charge_row['min_order_value'] ?? null;

    $order_price = $order['price'];
    $applied_charge = ($min_order_value !== null && $order_price >= $min_order_value) ? 0.00 : $delivery_charge;

    // Insert into delivery_boy_history
    $history_sql = "INSERT INTO delivery_boy_history (delivery_boy_id, order_id, delivery_charge, order_price, status, completed_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    $history_stmt = mysqli_prepare($db, $history_sql);
    mysqli_stmt_bind_param($history_stmt, "iidds", $db_id, $order_id, $applied_charge, $order_price, $status);
    $history_success = mysqli_stmt_execute($history_stmt);
    mysqli_stmt_close($history_stmt);

    if (!$history_success) {
        echo json_encode(['success' => false, 'message' => 'Failed to log history']);
        exit();
    }
}

echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
?>