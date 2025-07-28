<?php
// cart_operations.php - Handle cart operations for chatbot
session_start();
require_once __DIR__ . '/connection/connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in to manage your cart.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$d_id = isset($_POST['d_id']) ? (int)$_POST['d_id'] : 0;
$rs_id = isset($_POST['rs_id']) ? (int)$_POST['rs_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

try {
    if (!$db) {
        throw new Exception('Database connection failed.');
    }

    if ($action === 'add') {
        if ($d_id <= 0 || $rs_id <= 0 || $quantity <= 0) {
            throw new Exception('Invalid dish, restaurant, or quantity.');
        }

        // Verify dish exists and is available
        $stmt_dish = $db->prepare("SELECT price, offer_price, rs_id FROM dishes WHERE d_id = ? AND rs_id = ? AND is_available = 1");
        $stmt_dish->bind_param("ii", $d_id, $rs_id);
        $stmt_dish->execute();
        $dish = $stmt_dish->get_result()->fetch_assoc();
        $stmt_dish->close();

        if (!$dish) {
            throw new Exception('Dish not available or not found at this restaurant.');
        }

        $price = $dish['offer_price'] ?? $dish['price'];

        // Check if item already in cart
        $stmt_cart = $db->prepare("SELECT cart_id, quantity FROM cart WHERE u_id = ? AND d_id = ? AND res_id = ?");
        $stmt_cart->bind_param("iii", $user_id, $d_id, $rs_id);
        $stmt_cart->execute();
        $cart_item = $stmt_cart->get_result()->fetch_assoc();
        $stmt_cart->close();

        if ($cart_item) {
            // Update quantity
            $new_quantity = $cart_item['quantity'] + $quantity;
            $stmt_update = $db->prepare("UPDATE cart SET quantity = ?, price = ? WHERE cart_id = ?");
            $total_price = $price * $new_quantity;
            $stmt_update->bind_param("idi", $new_quantity, $total_price, $cart_item['cart_id']);
            $stmt_update->execute();
            $stmt_update->close();
            $response['message'] = "Updated $quantity more to your cart!";
        } else {
            // Add new item
            $stmt_insert = $db->prepare("INSERT INTO cart (u_id, d_id, res_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $total_price = $price * $quantity;
            $stmt_insert->bind_param("iiidi", $user_id, $d_id, $rs_id, $quantity, $total_price);
            $stmt_insert->execute();
            $stmt_insert->close();
            $response['message'] = "Added $quantity item(s) to your cart!";
        }
        $response['status'] = 'success';
    } elseif ($action === 'remove') {
        if ($d_id <= 0) {
            // Clear entire cart if no d_id provided
            $stmt_clear = $db->prepare("DELETE FROM cart WHERE u_id = ?");
            $stmt_clear->bind_param("i", $user_id);
            $stmt_clear->execute();
            $stmt_clear->close();
            $response['message'] = 'Your cart has been cleared.';
        } else {
            // Remove specific item
            $stmt_remove = $db->prepare("DELETE FROM cart WHERE u_id = ? AND d_id = ? AND res_id = ?");
            $stmt_remove->bind_param("iii", $user_id, $d_id, $rs_id);
            $stmt_remove->execute();
            $affected = $stmt_remove->affected_rows;
            $stmt_remove->close();
            $response['message'] = $affected > 0 ? 'Item removed from your cart.' : 'Item not found in your cart.';
        }
        $response['status'] = 'success';
    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
    error_log("Cart Operation Error for user {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>