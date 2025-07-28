<?php
// favorite_operations.php - Handle favorite operations for chatbot
session_start();
require_once __DIR__ . '/connection/connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in to manage your favorites.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? ''; // 'dish' or 'restaurant'
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

try {
    if (!$db) {
        throw new Exception('Database connection failed.');
    }

    if ($action === 'add') {
        if ($id <= 0 || !in_array($type, ['dish', 'restaurant'])) {
            throw new Exception('Invalid item or type.');
        }

        if ($type === 'dish') {
            // Verify dish exists
            $stmt_dish = $db->prepare("SELECT d_id FROM dishes WHERE d_id = ? AND is_available = 1");
            $stmt_dish->bind_param("i", $id);
            $stmt_dish->execute();
            $dish = $stmt_dish->get_result()->fetch_assoc();
            $stmt_dish->close();

            if (!$dish) {
                throw new Exception('Dish not found or unavailable.');
            }

            // Check if already favorited
            $stmt_check = $db->prepare("SELECT id FROM user_favorite_dishes WHERE u_id = ? AND d_id = ?");
            $stmt_check->bind_param("ii", $user_id, $id);
            $stmt_check->execute();
            $exists = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$exists) {
                $stmt_insert = $db->prepare("INSERT INTO user_favorite_dishes (u_id, d_id) VALUES (?, ?)");
                $stmt_insert->bind_param("ii", $user_id, $id);
                $stmt_insert->execute();
                $stmt_insert->close();
                $response['message'] = 'Dish added to your favorites!';
            } else {
                $response['message'] = 'Dish is already in your favorites.';
            }
        } else {
            // Verify restaurant exists
            $stmt_res = $db->prepare("SELECT rs_id FROM restaurant WHERE rs_id = ? AND is_open = 1");
            $stmt_res->bind_param("i", $id);
            $stmt_res->execute();
            $res = $stmt_res->get_result()->fetch_assoc();
            $stmt_res->close();

            if (!$res) {
                throw new Exception('Restaurant not found or closed.');
            }

            // Check if already favorited
            $stmt_check = $db->prepare("SELECT id FROM user_favorite_restaurants WHERE u_id = ? AND rs_id = ?");
            $stmt_check->bind_param("ii", $user_id, $id);
            $stmt_check->execute();
            $exists = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$exists) {
                $stmt_insert = $db->prepare("INSERT INTO user_favorite_restaurants (u_id, rs_id) VALUES (?, ?)");
                $stmt_insert->bind_param("ii", $user_id, $id);
                $stmt_insert->execute();
                $stmt_insert->close();
                $response['message'] = 'Restaurant added to your favorites!';
            } else {
                $response['message'] = 'Restaurant is already in your favorites.';
            }
        }
        $response['status'] = 'success';
    } elseif ($action === 'remove') {
        if ($id <= 0 || !in_array($type, ['dish', 'restaurant'])) {
            throw new Exception('Invalid item or type.');
        }

        if ($type === 'dish') {
            $stmt_remove = $db->prepare("DELETE FROM user_favorite_dishes WHERE u_id = ? AND d_id = ?");
            $stmt_remove->bind_param("ii", $user_id, $id);
            $stmt_remove->execute();
            $affected = $stmt_remove->affected_rows;
            $stmt_remove->close();
            $response['message'] = $affected > 0 ? 'Dish removed from your favorites.' : 'Dish not found in your favorites.';
        } else {
            $stmt_remove = $db->prepare("DELETE FROM user_favorite_restaurants WHERE u_id = ? AND rs_id = ?");
            $stmt_remove->bind_param("ii", $user_id, $id);
            $stmt_remove->execute();
            $affected = $stmt_remove->affected_rows;
            $stmt_remove->close();
            $response['message'] = $affected > 0 ? 'Restaurant removed from your favorites.' : 'Restaurant not found in your favorites.';
        }
        $response['status'] = 'success';
    } else {
        throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
    error_log("Favorite Operation Error for user {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>