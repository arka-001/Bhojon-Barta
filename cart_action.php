<?php
// cart_action.php - Securely handles cart modifications

session_start();
require_once __DIR__ . '/connection/connect.php';

header('Content-Type: application/json');

// Security Check: User must be logged in to modify a cart
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $d_id = (int)($input['d_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            if ($d_id <= 0 || $quantity <= 0) {
                throw new Exception("Invalid dish ID or quantity.");
            }

            // Get dish details (price, restaurant id)
            $stmt = $db->prepare("SELECT rs_id, price, offer_price FROM dishes WHERE d_id = ? AND is_available = 1");
            $stmt->bind_param("i", $d_id);
            $stmt->execute();
            $dish = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$dish) {
                throw new Exception("This dish is not available.");
            }
            $price = !is_null($dish['offer_price']) ? $dish['offer_price'] : $dish['price'];
            $res_id = $dish['rs_id'];

            // Check if item already in cart
            $stmt = $db->prepare("SELECT cart_id, quantity FROM cart WHERE u_id = ? AND d_id = ?");
            $stmt->bind_param("ii", $user_id, $d_id);
            $stmt->execute();
            $cart_item = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cart_item) {
                // Update quantity
                $new_quantity = $cart_item['quantity'] + $quantity;
                $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                $stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
                $stmt->execute();
                $stmt->close();
            } else {
                // Insert new item
                $stmt = $db->prepare("INSERT INTO cart (u_id, d_id, res_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiid", $user_id, $d_id, $res_id, $quantity, $price);
                $stmt->execute();
                $stmt->close();
            }
            echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
            break;

        case 'remove':
            $d_id = (int)($input['d_id'] ?? 0);
            if ($d_id <= 0) {
                throw new Exception("Invalid dish ID.");
            }
            $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ? AND d_id = ?");
            $stmt->bind_param("ii", $user_id, $d_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
            break;

        case 'clear':
            $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Cart has been cleared.']);
            break;

        default:
            throw new Exception("Unknown action.");
    }
} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>