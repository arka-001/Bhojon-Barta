<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../connection/connect.php"); // Adjust if connect.php is elsewhere
session_start();
header('Content-Type: application/json');

// Check database connection
if (!$db) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Check if the user is an authorized restaurant owner
if (!isset($_SESSION['owner_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in as a restaurant owner."]);
    exit;
}

$owner_id = intval($_SESSION['owner_id']);
$action = $_POST['action'] ?? '';
$response = ["status" => "error", "message" => "Invalid action."];

// Debug POST data
// error_log("POST data: " . print_r($_POST, true));

if ($action === "toggle_restaurant_status") {
    $rs_id = intval($_POST['id'] ?? 0);
    $is_open = intval($_POST['status'] ?? 0); // 1 for open, 0 for closed

    if ($rs_id <= 0) {
        $response = ["status" => "error", "message" => "Invalid restaurant ID."];
    } else {
        // Verify the restaurant belongs to the owner
        $stmt = $db->prepare("SELECT rs_id FROM restaurant WHERE rs_id = ? AND owner_id = ?");
        if (!$stmt) {
            $response = ["status" => "error", "message" => "Prepare failed: " . $db->error];
        } else {
            $stmt->bind_param("ii", $rs_id, $owner_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Update restaurant status
                $update_stmt = $db->prepare("UPDATE restaurant SET is_open = ? WHERE rs_id = ?");
                if (!$update_stmt) {
                    $response = ["status" => "error", "message" => "Prepare failed: " . $db->error];
                } else {
                    $update_stmt->bind_param("ii", $is_open, $rs_id);
                    if ($update_stmt->execute()) {
                        $response = ["status" => "success", "message" => "Restaurant status updated successfully."];
                    } else {
                        $response = ["status" => "error", "message" => "Failed to update restaurant status: " . $update_stmt->error];
                    }
                    $update_stmt->close();
                }
            } else {
                $response = ["status" => "error", "message" => "Restaurant not found or you are not authorized to modify it."];
            }
            $stmt->close();
        }
    }
} elseif ($action === "toggle_dish_availability") {
    $d_id = intval($_POST['id'] ?? 0);
    $is_available = intval($_POST['status'] ?? 0); // 1 for available, 0 for unavailable

    if ($d_id <= 0) {
        $response = ["status" => "error", "message" => "Invalid dish ID."];
    } else {
        // Verify the dish belongs to the owner's restaurant
        $stmt = $db->prepare("
            SELECT d.d_id 
            FROM dishes d
            JOIN restaurant r ON d.rs_id = r.rs_id
            WHERE d.d_id = ? AND r.owner_id = ?
        ");
        if (!$stmt) {
            $response = ["status" => "error", "message" => "Prepare failed: " . $db->error];
        } else {
            $stmt->bind_param("ii", $d_id, $owner_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Update dish availability
                $update_stmt = $db->prepare("UPDATE dishes SET is_available = ? WHERE d_id = ?");
                if (!$update_stmt) {
                    $response = ["status" => "error", "message" => "Prepare failed: " . $db->error];
                } else {
                    $update_stmt->bind_param("ii", $is_available, $d_id);
                    if ($update_stmt->execute()) {
                        $response = ["status" => "success", "message" => "Dish availability updated successfully."];
                    } else {
                        $response = ["status" => "error", "message" => "Failed to update dish availability: " . $update_stmt->error];
                    }
                    $update_stmt->close();
                }
            } else {
                $response = ["status" => "error", "message" => "Dish not found or you are not authorized to modify it."];
            }
            $stmt->close();
        }
    }
} else {
    $response = ["status" => "error", "message" => "Invalid action: " . htmlspecialchars($action)];
}

echo json_encode($response);
$db->close();
?>