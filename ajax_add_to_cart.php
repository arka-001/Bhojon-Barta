<?php
// ajax_add_to_cart.php

error_reporting(E_ALL);
ini_set('display_errors', 1); // Good for development

session_start();
include("connection/connect.php"); // Ensure this path is correct

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'An error occurred. Please try again.'); // Default error

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = "Please login to add items to your cart.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['action']) && $_POST['action'] == 'add' &&
    isset($_POST['d_id']) && isset($_POST['res_id']) &&
    isset($_POST['quantity'])) {

    $d_id = intval($_POST['d_id']);
    $res_id = intval($_POST['res_id']);
    $quantity = intval($_POST['quantity']);
    $u_id = intval($_SESSION['user_id']);

    // Validate quantity
    if ($quantity <= 0) {
        $response['message'] = "Invalid quantity. Please enter a quantity greater than zero.";
        echo json_encode($response);
        exit;
    }

    // --- Start Transaction (Optional but Recommended) ---
    $db->begin_transaction();

    try {
        // Fetch dish details, including current price, offer_price, availability, and restaurant status
        $stmt = $db->prepare("SELECT d.d_id, d.title, d.price AS original_price, d.offer_price, d.is_available, r.is_open AS restaurant_is_open
                              FROM dishes d
                              JOIN restaurant r ON d.rs_id = r.rs_id
                              WHERE d.d_id = ? AND d.rs_id = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error (dish details): " . $db->error);
        }
        $stmt->bind_param('ii', $d_id, $res_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dishDetails = $result->fetch_assoc();
        $stmt->close();

        if (!$dishDetails) {
            throw new Exception("Product not found or does not belong to the specified restaurant.");
        }

        // Check if restaurant is open and dish is available
        if (!$dishDetails['restaurant_is_open']) {
            throw new Exception("Sorry, the restaurant is currently closed.");
        }
        if ($dishDetails['is_available'] != 1) { // Assuming 1 means available
            throw new Exception("Sorry, this dish is currently unavailable.");
        }

        // Determine the actual price to use (offer price if valid, otherwise original price)
        $actual_price = (float)$dishDetails['original_price'];
        if (isset($dishDetails['offer_price']) && (float)$dishDetails['offer_price'] > 0 && (float)$dishDetails['offer_price'] < $actual_price) {
            $actual_price = (float)$dishDetails['offer_price'];
        }

        // Check if item already exists in cart for this user
        $checkStmt = $db->prepare("SELECT cart_id, quantity FROM cart WHERE u_id = ? AND d_id = ?");
        if (!$checkStmt) {
            throw new Exception("Database prepare error (check cart): " . $db->error);
        }
        $checkStmt->bind_param('ii', $u_id, $d_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingCartItem = $checkResult->fetch_assoc();
        $checkStmt->close();

        $success = false;
        if ($existingCartItem) {
            // Update existing item
            $newQuantity = $existingCartItem['quantity'] + $quantity;

            $updateStmt = $db->prepare("UPDATE cart SET quantity = ?, price = ? WHERE cart_id = ?");
            if (!$updateStmt) {
                throw new Exception("Database prepare error (update cart): " . $db->error);
            }
            $updateStmt->bind_param('idi', $newQuantity, $actual_price, $existingCartItem['cart_id']);
            $success = $updateStmt->execute();
            if (!$success) {
                 throw new Exception("Failed to update item in cart: " . $updateStmt->error);
            }
            $updateStmt->close();
        } else {
            // Insert new item
            $insertStmt = $db->prepare("INSERT INTO cart (u_id, d_id, res_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
            if (!$insertStmt) {
                throw new Exception("Database prepare error (insert cart): " . $db->error);
            }
            $insertStmt->bind_param('iiidi', $u_id, $d_id, $res_id, $quantity, $actual_price);
            $success = $insertStmt->execute();
            if (!$success) {
                throw new Exception("Failed to add new item to cart: " . $insertStmt->error);
            }
            $insertStmt->close();
        }

        if ($success) {
            $db->commit(); // --- Commit Transaction ---
            $response['status'] = 'success';
            $response['message'] = htmlspecialchars($dishDetails['title']) . " added to cart!";
            // Optionally, return cart count or total
            // $response['cart_count'] = get_cart_item_count($db, $u_id);
        } else {
            // This 'else' might not be reached if exceptions are thrown for failures
            throw new Exception("An unexpected error occurred while updating the cart.");
        }

    } catch (Exception $e) {
        $db->rollback(); // --- Rollback Transaction on error ---
        $response['message'] = $e->getMessage();
        // In production, you might log $e->getMessage() and show a generic error to the user.
        // error_log("Cart Error: " . $e->getMessage() . " for user " . $u_id . ", dish " . $d_id);
        // $response['message'] = "Could not update cart. Please try again.";
    }

} else {
    $response['message'] = "Invalid request or missing parameters.";
    // Potentially log this as it might indicate a client-side bug or tampering
    // error_log("Invalid cart request: " . print_r($_POST, true));
}

echo json_encode($response);
exit;

/*
// Optional helper function to get cart count (if needed in response)
function get_cart_item_count($db, $u_id) {
    $count_stmt = $db->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE u_id = ?");
    if ($count_stmt) {
        $count_stmt->bind_param('i', $u_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();
        return $count_result['total_items'] ? intval($count_result['total_items']) : 0;
    }
    return 0;
}
*/
?>