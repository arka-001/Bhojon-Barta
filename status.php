<?php
// status.php - Updated to include cart data & optimized queries
session_start();
require_once __DIR__ . '/connection/connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json');
// For security, always be specific with your CORS policy.
// header('Access-Control-Allow-Origin: https://yourdomain.com'); 

$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.',
    'data' => null,
    'timestamp' => date('Y-m-d H:i:s'),
];

if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'unauthorized';
    $response['message'] = 'Authentication required. Please log in to view this information.';
    http_response_code(401);
    echo json_encode($response);
    exit; // No need to close DB here, will be closed at the end if script continues
}

$user_id = (int)$_SESSION['user_id'];
$data = [];

try {
    if (!$db) {
        throw new Exception('Database connection failed.');
    }

    // User info
    $stmt_user = $db->prepare("SELECT u_id, username, name, email, phone, city, is_veg_mode FROM users WHERE u_id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    if ($current_user = $user_result->fetch_assoc()) {
        $data['currentUser'] = $current_user;
        $user_city = $current_user['city'] ?? '';
    } else {
        throw new Exception("Logged-in user with ID {$user_id} not found in database.");
    }
    $stmt_user->close();

    // Orders
    $stmt_orders = $db->prepare("SELECT o.o_id, o.title, o.quantity, o.price, o.status, o.date, o.total_amount, r.title as restaurant_name FROM users_orders o LEFT JOIN restaurant r ON o.rs_id = r.rs_id WHERE o.u_id = ? ORDER BY o.date DESC LIMIT 15");
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $data['myOrders'] = $stmt_orders->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_orders->close();

    // Favorites
    $data['myFavorites'] = ['restaurants' => [], 'dishes' => []];
    $stmt_fav_res = $db->prepare("SELECT r.rs_id, r.title, r.image, r.city FROM user_favorite_restaurants ufr JOIN restaurant r ON ufr.rs_id = r.rs_id WHERE ufr.u_id = ?");
    $stmt_fav_res->bind_param("i", $user_id);
    $stmt_fav_res->execute();
    $data['myFavorites']['restaurants'] = $stmt_fav_res->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_fav_res->close();

    $stmt_fav_dish = $db->prepare("SELECT d.d_id, d.title, d.price, d.img, r.title as restaurant_name FROM user_favorite_dishes ufd JOIN dishes d ON ufd.d_id = d.d_id JOIN restaurant r ON d.rs_id = r.rs_id WHERE ufd.u_id = ?");
    $stmt_fav_dish->bind_param("i", $user_id);
    $stmt_fav_dish->execute();
    $data['myFavorites']['dishes'] = $stmt_fav_dish->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_fav_dish->close();

    // Cart
    $stmt_cart = $db->prepare("SELECT c.cart_id, c.d_id, c.res_id, c.quantity, c.price, d.title as dish_name, r.title as restaurant_name FROM cart c JOIN dishes d ON c.d_id = d.d_id JOIN restaurant r ON c.res_id = r.rs_id WHERE c.u_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $data['myCart'] = $stmt_cart->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_cart->close();

    // Open Restaurants (Consistent use of prepared statements)
    $restaurantSql = "SELECT rs_id, title, address, city, o_hr, c_hr, o_days, diet_type, image FROM restaurant WHERE is_open = 1";
    if (!empty($user_city)) {
        $restaurantSql .= " AND city LIKE ?";
        $stmt_res = $db->prepare($restaurantSql);
        $city_param = "%{$user_city}%";
        $stmt_res->bind_param("s", $city_param);
    } else {
        $stmt_res = $db->prepare($restaurantSql);
    }
    $stmt_res->execute();
    $restaurantResult = $stmt_res->get_result();
    
    $restaurantMap = [];
    while ($row = $restaurantResult->fetch_assoc()) {
        $restaurantMap[$row['rs_id']] = array_merge($row, ['dishes' => [], 'ratings' => []]);
    }
    $stmt_res->close();

    if (!empty($restaurantMap)) {
        $openRestaurantIds = implode(',', array_keys($restaurantMap));
        // Using a prepared statement here is also possible but more complex; implode is safe as keys are from DB.
        $dishResult = mysqli_query($db, "SELECT d_id, rs_id, title, slogan, price, offer_price, offer_end_date, diet_type, img FROM dishes WHERE rs_id IN ($openRestaurantIds) AND is_available = 1");
        if ($dishResult) {
            while ($dishRow = $dishResult->fetch_assoc()) {
                if (isset($restaurantMap[$dishRow['rs_id']])) {
                    $restaurantMap[$dishRow['rs_id']]['dishes'][] = $dishRow;
                }
            }
        }
    }
    $data['openRestaurants'] = array_values($restaurantMap);

    // Public Info (Optimized into one query)
    $data['publicInfo'] = [];
    $data['publicInfo']['delivery'] = mysqli_query($db, "SELECT min_order_value, delivery_charge, description FROM delivary_charges LIMIT 1")->fetch_assoc();
    $data['publicInfo']['contact'] = mysqli_query($db, "SELECT address, phone, payment_options FROM footer_settings LIMIT 1")->fetch_assoc();

    $response['status'] = 'success';
    $response['message'] = 'User data retrieved successfully.';
    $response['data'] = $data;

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'An internal error occurred while fetching data.';
    http_response_code(500);
    error_log("API Error in status.php for user {$user_id}: " . $e->getMessage());
} finally {
    if ($db) {
        mysqli_close($db);
    }
}

// JSON_NUMERIC_CHECK can be useful but use with caution. It can incorrectly convert numeric strings (e.g., ZIP codes) to numbers.
echo json_encode($response, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
?>