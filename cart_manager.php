<?php
// cart_manager.php - v2.0 - Securely handles all cart-related database actions

session_start();
require_once __DIR__ . '/connection/connect.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// --- SECURITY: Get user ID from the session, NOT from input ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in to manage the cart.']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Get the action from the JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- Main Logic ---
try {
    $db->begin_transaction();

    switch ($action) {
        case 'add':
            $d_id = (int)($input['d_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            add_item_to_cart($db, $user_id, $d_id, $quantity);
            break;
        case 'remove':
            $d_id = (int)($input['d_id'] ?? 0);
            remove_item_from_cart($db, $user_id, $d_id);
            break;
        case 'clear':
            clear_cart_for_user($db, $user_id);
            break;
        case 'view':
            view_cart($db, $user_id);
            break;
        default:
            throw new Exception("Invalid action specified.");
    }

    $db->commit(); // If we get here, all queries in the functions succeeded

} catch (Exception $e) {
    $db->rollback(); // Undo any partial changes if an error occurred
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($db);

// --- Function Definitions ---
function add_item_to_cart($db, $user_id, $d_id, $quantity) {
    if ($d_id <= 0 || $quantity <= 0) throw new Exception("Invalid dish ID or quantity.");

    $stmt = $db->prepare("SELECT rs_id, price, offer_price FROM dishes WHERE d_id = ? AND is_available = 1");
    $stmt->bind_param("i", $d_id);
    $stmt->execute();
    $dish = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$dish) throw new Exception("This dish is not available.");

    $price = !is_null($dish['offer_price']) ? $dish['offer_price'] : $dish['price'];
    $res_id = $dish['rs_id'];

    $stmt = $db->prepare("SELECT cart_id, quantity FROM cart WHERE u_id = ? AND d_id = ?");
    $stmt->bind_param("ii", $user_id, $d_id);
    $stmt->execute();
    $cart_item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cart_item) {
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
    } else {
        $stmt = $db->prepare("INSERT INTO cart (u_id, d_id, res_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiid", $user_id, $d_id, $res_id, $quantity, $price);
    }
    if(!$stmt->execute()) throw new Exception("Could not update cart.");
    $stmt->close();
    
    // After adding, respond with the updated cart state
    view_cart($db, $user_id, "Item added! Here's your updated cart:");
}

function remove_item_from_cart($db, $user_id, $d_id) {
    if ($d_id <= 0) throw new Exception("Invalid dish ID.");
    
    $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ? AND d_id = ?");
    $stmt->bind_param("ii", $user_id, $d_id);
    $stmt->execute();
    
    if($stmt->affected_rows <= 0) throw new Exception("That item was not found in your cart.");
    $stmt->close();
    view_cart($db, $user_id, "Item removed. Here's what's left:");
}

function clear_cart_for_user($db, $user_id) {
    $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    view_cart($db, $user_id, "Cart cleared successfully!");
}

function view_cart($db, $user_id, $custom_message = "Here is your current cart:") {
    $stmt = $db->prepare("SELECT c.d_id, c.quantity, c.price, d.title FROM cart c JOIN dishes d ON c.d_id = d.d_id WHERE c.u_id = ? ORDER BY c.added_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $subtotal = 0;
    foreach($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => $custom_message,
        'cart' => [
            'items' => $cart_items,
            'item_count' => count($cart_items),
            'subtotal' => number_format($subtotal, 2)
        ]
    ]);
}