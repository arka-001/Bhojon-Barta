<?php
// chatbot_api_local.php (This contains the database logic as a function)

function fetch_chatbot_data($action, $params) {
    // This function will be called directly by the main chatbot file.
    require __DIR__ . '/connection/connect.php'; // Fresh database connection

    $data = [];
    $action_key = str_replace('get_', '', $action);

    switch ($action_key) {
        case 'open_restaurants':
            $city = isset($params['city']) ? '%' . $params['city'] . '%' : null;
            $query = "SELECT title, address, city FROM restaurant WHERE is_open = 1";
            if ($city) {
                $query .= " AND city LIKE ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("s", $city);
            } else {
                $stmt = $db->prepare($query);
            }
            break;

        case 'available_dishes':
            $dish_name = isset($params['dish_name']) ? '%' . $params['dish_name'] . '%' : null;
            if (!$dish_name) return ['status' => 'error', 'message' => 'A dish name is required.'];
            $query = "SELECT d.title, d.price, d.offer_price, r.title as restaurant_name, r.city FROM dishes d JOIN restaurant r ON d.rs_id = r.rs_id WHERE d.is_available = 1 AND r.is_open = 1 AND d.title LIKE ? LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $dish_name);
            break;

        case 'delivery_charges':
            $query = "SELECT min_order_value, delivery_charge, description FROM delivary_charges LIMIT 1";
            $stmt = $db->prepare($query);
            break;

        case 'order_status':
            $user_id = filter_var($params['user_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$user_id) return ['status' => 'error', 'message' => 'User ID is required for order status.'];
            $query = "SELECT order_id, title, quantity, total_amount, status, date FROM users_orders WHERE u_id = ? ORDER BY date DESC LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $user_id);
            break;
            
        // Add other cases like 'get_categories', 'get_favorite_dishes' etc. as needed.
        
        default:
            return ['status' => 'error', 'message' => 'Unknown action requested.'];
    }

    if (isset($stmt)) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            $db->close();
            return ['status' => 'success', 'data' => $data];
        } else {
            error_log("DB Query Failed: " . $stmt->error);
            $db->close();
            return ['status' => 'error', 'message' => 'Database query failed.'];
        }
    }
    
    $db->close();
    return ['status' => 'error', 'message' => 'Could not process the request.'];
}
?>