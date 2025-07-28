<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner' || !isset($_SESSION['owner_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$owner_id = intval($_SESSION['owner_id']);
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    // Validate restaurant ownership
    $rs_id = intval($_POST['rs_id']);
    $stmt_check = $db->prepare("SELECT rs_id FROM restaurant WHERE rs_id = ? AND owner_id = ?");
    $stmt_check->bind_param("ii", $rs_id, $owner_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows === 0) {
        throw new Exception('Permission denied for this restaurant');
    }
    $stmt_check->close();

    $db->begin_transaction();

    if ($action === 'add_offer' || $action === 'edit_offer') {
        $d_id = intval($_POST['d_id']);
        $discounted_price = floatval($_POST['discounted_price']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Validate dish belongs to restaurant
        $stmt_dish = $db->prepare("SELECT price FROM dishes WHERE d_id = ? AND rs_id = ?");
        $stmt_dish->bind_param("ii", $d_id, $rs_id);
        $stmt_dish->execute();
        $dish_result = $stmt_dish->get_result();
        if ($dish_result->num_rows === 0) {
            throw new Exception('Dish not found or permission denied');
        }
        $dish = $dish_result->fetch_assoc();
        $original_price = floatval($dish['price']);
        $stmt_dish->close();

        // Validate inputs
        if ($discounted_price >= $original_price) {
            throw new Exception('Discounted price must be less than original price');
        }
        if (strtotime($end_date) <= strtotime($start_date)) {
            throw new Exception('End date must be after start date');
        }
        if (strtotime($start_date) < time()) {
            throw new Exception('Start date cannot be in the past');
        }

        if ($action === 'add_offer') {
            // Check for existing active offer
            $stmt_check_offer = $db->prepare("SELECT offer_id FROM offers WHERE d_id = ? AND end_date > NOW()");
            $stmt_check_offer->bind_param("i", $d_id);
            $stmt_check_offer->execute();
            if ($stmt_check_offer->get_result()->num_rows > 0) {
                throw new Exception('An active offer already exists for this dish');
            }
            $stmt_check_offer->close();

            // Insert new offer
            $stmt = $db->prepare("INSERT INTO offers (d_id, rs_id, original_price, discounted_price, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddss", $d_id, $rs_id, $original_price, $discounted_price, $start_date, $end_date);
            $stmt->execute();
            $offer_id = $stmt->insert_id;
            $stmt->close();

            // Update dish with offer_id
            $stmt_update = $db->prepare("UPDATE dishes SET offer_id = ? WHERE d_id = ?");
            $stmt_update->bind_param("ii", $offer_id, $d_id);
            $stmt_update->execute();
            $stmt_update->close();

            $response = ['status' => 'success', 'message' => 'Offer added successfully'];
        } elseif ($action === 'edit_offer') {
            $offer_id = intval($_POST['offer_id']);
            // Verify offer exists
            $stmt_check = $db->prepare("SELECT offer_id FROM offers WHERE offer_id = ? AND d_id = ?");
            $stmt_check->bind_param("ii", $offer_id, $d_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows === 0) {
                throw new Exception('Offer not found');
            }
            $stmt_check->close();

            // Update offer
            $stmt = $db->prepare("UPDATE offers SET original_price = ?, discounted_price = ?, start_date = ?, end_date = ? WHERE offer_id = ?");
            $stmt->bind_param("ddssi", $original_price, $discounted_price, $start_date, $end_date, $offer_id);
            $stmt->execute();
            $stmt->close();

            $response = ['status' => 'success', 'message' => 'Offer updated successfully'];
        }
    } elseif ($action === 'delete_offer') {
        $offer_id = intval($_POST['offer_id']);
        $d_id = intval($_POST['d_id']);

        // Verify offer exists
        $stmt_check = $db->prepare("SELECT offer_id FROM offers WHERE offer_id = ? AND d_id = ? AND rs_id = ?");
        $stmt_check->bind_param("iii", $offer_id, $d_id, $rs_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            throw new Exception('Offer not found or permission denied');
        }
        $stmt_check->close();

        // Delete offer
        $stmt = $db->prepare("DELETE FROM offers WHERE offer_id = ?");
        $stmt->bind_param("i", $offer_id);
        $stmt->execute();
        $stmt->close();

        // Remove offer_id from dish
        $stmt_update = $db->prepare("UPDATE dishes SET offer_id = NULL WHERE d_id = ?");
        $stmt_update->bind_param("i", $d_id);
        $stmt_update->execute();
        $stmt_update->close();

        $response = ['status' => 'success', 'message' => 'Offer deleted successfully'];
    }

    $db->commit();
    echo json_encode($response);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$db->close();
?>