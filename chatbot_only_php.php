<?php
session_start(); // Access user session for user-specific actions

// --- Error Reporting (Enable for Localhost Debugging ONLY!) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Database Connection ---
// IMPORTANT: Make sure this path is correct relative to this file's location
require_once 'connection/connect.php'; // <<<--- CHECK THIS PATH

// --- Set Response Header ---
header('Content-Type: application/json');

// --- Initialize Response Variables ---
$botResponse = "Sorry, I couldn't quite understand that. Type 'help' for options.";
$currentIntent = 'Fallback'; // Default intent
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; // Get logged-in user ID

// --- Get User Input ---
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';
$lowerUserMessage = ''; // Initialize
if ($userMessage) { // Only proceed if message is not empty
    $lowerUserMessage = strtolower($userMessage);
}

// --- CONTEXT Management (Using Session) ---
if (!isset($_SESSION['chatbot_context'])) {
    $_SESSION['chatbot_context'] = [
        'last_intent' => null,
        'expecting' => null, // e.g., 'city_name', 'dish_name_for_price', 'dish_name_for_add'
        'data' => [] // e.g., store found restaurant IDs
    ];
}
$context = &$_SESSION['chatbot_context']; // Use reference

// --- Helper Function ---
function hasKeywords($message, $keywords) {
    if (empty($message)) return false;
    foreach ($keywords as $keyword) {
        if (strpos($message, $keyword) !== false) return true;
    }
    return false;
}

// --- Define Keyword Lists (Customize!) ---
$findRestaurantKeywords = ['restaurant', 'place', 'food', 'eat', 'eatery', 'find', 'show', 'list', 'search', 'look for'];
$orderKeywords = ['order', 'delivery', 'food', 'track', 'status', 'where'];
$priceKeywords = ['price', 'cost', 'how much', 'value', 'charge'];
$hoursKeywords = ['hours', 'opening', 'open times', 'when open', 'timing'];
$cartKeywords = ['cart', 'basket', 'bag', 'items', 'checkout'];
$addKeywords = ['add', 'put', 'include', 'want', 'get', 'order']; // Use 'order' carefully
$removeKeywords = ['remove', 'delete', 'take out', 'cancel'];
$helpKeywords = ['help', 'options', 'menu', 'what can you do', 'commands', 'assist'];
$greetingKeywords = ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good evening', 'yo', 'howdy'];
$cityKeywords = ['in', 'near', 'at', 'around', 'for'];
$affirmativeKeywords = ['yes', 'yeah', 'yep', 'ok', 'sure', 'confirm', 'do it', 'please'];
$negativeKeywords = ['no', 'nope', 'nah', 'cancel', 'stop', 'don\'t'];


// --- Main Logic ---
if (!empty($lowerUserMessage)) {

    $contextHandled = false;
    // --- Handle Contextual Responses FIRST ---
    if ($context['expecting'] && !hasKeywords($lowerUserMessage, $helpKeywords)) {
        switch ($context['expecting']) {
            case 'city_name':
                $currentIntent = 'ProvideCity';
                $cityName = trim($userMessage);
                $safeCityName = mysqli_real_escape_string($db, $cityName);
                $sql = "SELECT rs_id, title, address FROM restaurant WHERE city LIKE ? AND is_open = 1 LIMIT 5";
                $stmt = mysqli_prepare($db, $sql);
                if ($stmt) {
                    $searchTerm = "%" . $safeCityName . "%";
                    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && mysqli_num_rows($result) > 0) {
                        $restaurants = []; $context['data']['found_restaurants'] = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $restaurants[] = htmlspecialchars($row['title']) . " (" . htmlspecialchars($row['address']) . ")";
                            $context['data']['found_restaurants'][] = $row['rs_id'];
                        }
                        mysqli_free_result($result);
                        $botResponse = "Okay, found these open restaurants matching '" . htmlspecialchars($cityName) . "':\n- " . implode("\n- ", $restaurants);
                    } else { $botResponse = "Sorry, couldn't find open restaurants matching '" . htmlspecialchars($cityName) . "'."; }
                    mysqli_stmt_close($stmt);
                } else { $botResponse = "Sorry, error searching for restaurants."; error_log("DB Prep Error (Ctx City): " . mysqli_error($db));}
                $context['expecting'] = null; $contextHandled = true;
                break;

            case 'dish_name_for_price':
                 $currentIntent = 'ProvideDishForPrice';
                 $dishName = trim($userMessage);
                 $safeDishName = mysqli_real_escape_string($db, $dishName);
                 $sql = "SELECT d.title, d.price, r.title as restaurant_name FROM dishes d JOIN restaurant r ON d.rs_id = r.rs_id WHERE d.title LIKE ? AND d.is_available = 1 LIMIT 5";
                 $stmt = mysqli_prepare($db, $sql);
                 if ($stmt) {
                     $searchTerm = "%" . $safeDishName . "%";
                     mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                     mysqli_stmt_execute($stmt);
                     $result = mysqli_stmt_get_result($stmt);
                     if($result && mysqli_num_rows($result) > 0) {
                         $dishesInfo = [];
                         while($row = mysqli_fetch_assoc($result)) { $dishesInfo[] = sprintf("%s at %s costs ₹%.2f", htmlspecialchars($row['title']), htmlspecialchars($row['restaurant_name']), $row['price']); }
                         mysqli_free_result($result);
                         $botResponse = "Okay, found prices for:\n- " . implode("\n- ", $dishesInfo);
                     } else { $botResponse = "Sorry, couldn't find an available dish like '" . htmlspecialchars($dishName) . "'."; }
                      mysqli_stmt_close($stmt);
                 } else { $botResponse = "Sorry, error looking up prices."; error_log("DB Prep Error (Ctx Dish Price): " . mysqli_error($db)); }
                 $context['expecting'] = null; $contextHandled = true;
                 break;

             case 'dish_name_for_add':
                 $currentIntent = 'ProvideDishForAdd';
                 $dishName = trim($userMessage);
                 if ($userId <= 0) { $botResponse = "Please log in to add items."; $contextHandled = true; }
                 else {
                     $safeDishName = mysqli_real_escape_string($db, $dishName);
                     $sql_find_dish = "SELECT d_id, rs_id, price, title FROM dishes WHERE title LIKE ? AND is_available = 1 LIMIT 1";
                     $stmt_find = mysqli_prepare($db, $sql_find_dish);
                     if ($stmt_find) {
                         $searchTerm = "%" . $safeDishName . "%";
                         mysqli_stmt_bind_param($stmt_find, "s", $searchTerm);
                         mysqli_stmt_execute($stmt_find);
                         $result_find = mysqli_stmt_get_result($stmt_find);
                         if ($dish = mysqli_fetch_assoc($result_find)) {
                             $d_id = $dish['d_id']; $rs_id = $dish['rs_id']; $price = $dish['price']; $actualDishName = $dish['title']; $quantity = 1;
                             $sql_insert = "INSERT INTO cart (u_id, d_id, res_id, quantity, price, added_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
                             $stmt_insert = mysqli_prepare($db, $sql_insert);
                             if($stmt_insert) {
                                 mysqli_stmt_bind_param($stmt_insert, "iiiid", $userId, $d_id, $rs_id, $quantity, $price);
                                 if (mysqli_stmt_execute($stmt_insert)) { $botResponse = "Okay, added 1 " . htmlspecialchars($actualDishName) . " to your cart!"; }
                                 else { $botResponse = "Sorry, error adding to cart."; error_log("Cart INSERT Error: " . mysqli_stmt_error($stmt_insert)); }
                                 mysqli_stmt_close($stmt_insert);
                             } else { $botResponse = "Sorry, error preparing cart update."; error_log("Cart Prepare Error: " . mysqli_error($db));}
                             mysqli_free_result($result_find);
                         } else { $botResponse = "Sorry, couldn't find an available dish named '" . htmlspecialchars($dishName) . "'."; }
                         mysqli_stmt_close($stmt_find);
                     } else { $botResponse = "Sorry, error finding dish."; error_log("Find Dish Prep Error: " . mysqli_error($db)); }
                     $context['expecting'] = null; $contextHandled = true;
                 }
                 break;

             case 'restaurant_name_for_hours':
                 $currentIntent = 'ProvideRestaurantForHours';
                 $restaurantName = trim($userMessage);
                 $safeRestaurantName = mysqli_real_escape_string($db, $restaurantName);
                 $sql = "SELECT title, o_hr, c_hr, o_days FROM restaurant WHERE title LIKE ? AND is_open = 1 LIMIT 1";
                 $stmt = mysqli_prepare($db, $sql);
                 if($stmt){
                    $searchTerm = "%" . $safeRestaurantName . "%";
                    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && $resto = mysqli_fetch_assoc($result)) {
                         $botResponse = sprintf("%s is open %s from %s to %s.", htmlspecialchars($resto['title']), htmlspecialchars($resto['o_days']), htmlspecialchars($resto['o_hr']), htmlspecialchars($resto['c_hr']));
                         mysqli_free_result($result);
                    } else { $botResponse = "Couldn't find details for an open restaurant like '".htmlspecialchars($restaurantName)."'."; }
                    mysqli_stmt_close($stmt);
                 } else { $botResponse = "Sorry, error checking hours."; error_log("DB Prep Error (Ctx Rest Hours): " . mysqli_error($db)); }
                 $context['expecting'] = null; $contextHandled = true;
                 break;

            // Add more 'case' blocks here
        }
    }

    // --- General Intent Matching (if no context handled) ---
    if (!$contextHandled) {
        if (hasKeywords($lowerUserMessage, $greetingKeywords)) {
            $currentIntent = 'Greeting';
            $botResponse = "Hello! How can I assist with your food order?";
            $context = ['last_intent' => $currentIntent, 'expecting' => null, 'data' => []]; // Reset context
        }
        // Find Restaurants with City
        elseif (hasKeywords($lowerUserMessage, $findRestaurantKeywords) && preg_match('/(?:' . implode('|', $cityKeywords) . ')\s+([\w\s\-]+)/', $lowerUserMessage, $matches)) {
            $currentIntent = 'FindRestaurant_WithCity';
            $cityName = trim($matches[1]); $safeCityName = mysqli_real_escape_string($db, $cityName);
            $sql = "SELECT rs_id, title, address FROM restaurant WHERE city LIKE ? AND is_open = 1 LIMIT 5";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                $searchTerm = "%" . $safeCityName . "%";
                mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && mysqli_num_rows($result) > 0) {
                    $restaurants = []; $context['data']['found_restaurants'] = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $restaurants[] = htmlspecialchars($row['title']) . " (" . htmlspecialchars($row['address']) . ")";
                        $context['data']['found_restaurants'][] = $row['rs_id'];
                    }
                    $botResponse = "Found these open restaurants matching '" . htmlspecialchars($cityName) . "':\n- " . implode("\n- ", $restaurants);
                    if(mysqli_num_rows($result) == 5) $botResponse .= "\n(Showing first 5 results)";
                    mysqli_free_result($result);
                } else { $botResponse = "Sorry, couldn't find open restaurants matching '" . htmlspecialchars($cityName) . "'."; }
                mysqli_stmt_close($stmt);
            } else { $botResponse = "Sorry, error searching."; error_log("DB Prep Error (Find Resto): " . mysqli_error($db)); }
            $context['expecting'] = null;
        }
        // Find Restaurants - Ask City
        elseif (hasKeywords($lowerUserMessage, $findRestaurantKeywords)) {
            $currentIntent = 'FindRestaurant_AskCity';
            $botResponse = "Sure, I can look for restaurants. Which city are you interested in?";
            $context['expecting'] = 'city_name';
        }
        // Order Status
        elseif (hasKeywords($lowerUserMessage, $orderKeywords) && (strpos($lowerUserMessage, 'status') !== false || strpos($lowerUserMessage, 'track') !== false || strpos($lowerUserMessage, 'where') !== false)) {
            $currentIntent = 'CheckOrderStatus';
             if ($userId > 0) {
                $sql = "SELECT o_id, order_id, title, status, date FROM users_orders WHERE u_id = ? ORDER BY o_id DESC LIMIT 1";
                $stmt = mysqli_prepare($db, $sql);
                if($stmt){
                    mysqli_stmt_bind_param($stmt, "i", $userId); mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && $order = mysqli_fetch_assoc($result)) {
                        $status = $order['status'] ?? 'Unknown'; $orderTitle = $order['title'] ?? 'your order'; $orderDate = date("M d, Y H:i", strtotime($order['date']));
                        $botResponse = "Your latest order (#" . $order['o_id'] . " - " . htmlspecialchars($orderTitle) . " from " . $orderDate . ") status is: **" . htmlspecialchars(ucfirst($status)) . "**.";
                        mysqli_free_result($result);
                    } else { $botResponse = "Looks like you don't have recent orders."; }
                    mysqli_stmt_close($stmt);
                } else { $botResponse = "Error fetching order status."; error_log("DB Prep Error (Order Status): " . mysqli_error($db)); }
            } else { $botResponse = "Please log in to check your order status."; }
            $context['expecting'] = null;
        }
        // Dish Price with Dish Name
        elseif (hasKeywords($lowerUserMessage, $priceKeywords) && preg_match('/(?:of|for)\s+(.*)/', $lowerUserMessage, $matches)) {
            $currentIntent = 'GetDishPrice_WithDish';
            $dishName = trim($matches[1]); $safeDishName = mysqli_real_escape_string($db, $dishName);
            $sql = "SELECT d.title, d.price, r.title as restaurant_name FROM dishes d JOIN restaurant r ON d.rs_id = r.rs_id WHERE d.title LIKE ? AND d.is_available = 1 LIMIT 5";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                 $searchTerm = "%" . $safeDishName . "%";
                 mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                 mysqli_stmt_execute($stmt);
                 $result = mysqli_stmt_get_result($stmt);
                 if($result && mysqli_num_rows($result) > 0) { /* ... Format response ... */ mysqli_free_result($result); }
                 else { $botResponse = "Sorry, couldn't find an available dish like '" . htmlspecialchars($dishName) . "'."; }
                  mysqli_stmt_close($stmt);
            } else { $botResponse = "Sorry, error looking up prices."; error_log("DB Prep Error (Dish Price): " . mysqli_error($db)); }
            $context['expecting'] = null;
        }
        // Dish Price - Ask Dish Name
        elseif (hasKeywords($lowerUserMessage, $priceKeywords)) {
            $currentIntent = 'GetDishPrice_AskDish';
            $botResponse = "Which dish are you asking about the price for?";
            $context['expecting'] = 'dish_name_for_price';
        }
        // Add to Cart with Dish Name
        elseif (hasKeywords($lowerUserMessage, $addKeywords) && preg_match('/(.*)\s+(?:to|in).*(?:' . implode('|', $cartKeywords) . ')/', $lowerUserMessage, $matches)) {
             $currentIntent = 'AddToCart_WithDish';
             $dishName = trim($matches[1]);
             if ($userId <= 0) { $botResponse = "Please log in first."; }
             else { /* ... (Execute AddToCart logic like context block) ... */ }
             $context['expecting'] = null;
         }
         // Add to Cart - Ask Dish Name
        elseif (hasKeywords($lowerUserMessage, $addKeywords)) {
             $currentIntent = 'AddToCart_AskDish';
             $botResponse = "What item would you like to add to your cart?";
             $context['expecting'] = 'dish_name_for_add';
        }
        // View Cart
        elseif(hasKeywords($lowerUserMessage, $cartKeywords) && (strpos($lowerUserMessage, 'view') !== false || strpos($lowerUserMessage, 'show') !== false || strpos($lowerUserMessage, 'see') !== false)) {
            $currentIntent = 'ViewCart';
             if ($userId <= 0) { $botResponse = "Please log in to view your cart."; }
             else {
                $sql_cart = "SELECT c.quantity, c.price, d.title FROM cart c JOIN dishes d ON c.d_id = d.d_id WHERE c.u_id = ?";
                $stmt_cart = mysqli_prepare($db, $sql_cart);
                 if ($stmt_cart) {
                     mysqli_stmt_bind_param($stmt_cart, "i", $userId); mysqli_stmt_execute($stmt_cart);
                     $result_cart = mysqli_stmt_get_result($stmt_cart);
                     if ($result_cart && mysqli_num_rows($result_cart) > 0) {
                         $cartItems = []; $total = 0;
                          while ($item = mysqli_fetch_assoc($result_cart)) { /* ... Format items ... */ }
                          mysqli_free_result($result_cart);
                          $botResponse = "Here's your cart:\n- " . implode("\n- ", $cartItems) . "\n\nTotal: ₹" . number_format($total, 2);
                     } else { $botResponse = "Your cart is currently empty."; }
                     mysqli_stmt_close($stmt_cart);
                 } else { $botResponse = "Error fetching cart."; error_log("View Cart Prep Error: ".mysqli_error($db));}
             }
             $context['expecting'] = null;
        }
        // Restaurant Hours with Name
        elseif (hasKeywords($lowerUserMessage, $hoursKeywords) && preg_match('/(?:for|at)\s+(.*)/', $lowerUserMessage, $matches)) {
            $currentIntent = 'GetHours_WithRestaurant';
            $restaurantName = trim($matches[1]); $safeRestaurantName = mysqli_real_escape_string($db, $restaurantName);
            $sql = "SELECT title, o_hr, c_hr, o_days FROM restaurant WHERE title LIKE ? AND is_open = 1 LIMIT 1";
            $stmt = mysqli_prepare($db, $sql);
            if($stmt){ /* ... (Execute and format response like context block) ... */ } else { /* ... */ }
            $context['expecting'] = null;
        }
        // Restaurant Hours - Ask Name
         elseif (hasKeywords($lowerUserMessage, $hoursKeywords)) {
            $currentIntent = 'GetHours_AskRestaurant';
            $botResponse = "Which restaurant's hours are you interested in?";
            $context['expecting'] = 'restaurant_name_for_hours';
        }
        // Delivery Fee
         elseif (strpos($lowerUserMessage, 'delivery fee') !== false || strpos($lowerUserMessage, 'delivery charge') !== false) {
            $currentIntent = 'DeliveryFee';
            $sql = "SELECT min_order_value, delivery_charge FROM delivary_charges WHERE id = 1"; // Assuming ID 1
            $result = mysqli_query($db, $sql);
            if ($result && $chargeInfo = mysqli_fetch_assoc($result)) {
                $charge = (float)$chargeInfo['delivery_charge']; $minOrder = (float)$chargeInfo['min_order_value'];
                if ($charge > 0) { $botResponse = sprintf("Std. delivery: ₹%.2f.", $charge); if ($minOrder > 0) { $botResponse .= sprintf(" May be free over ₹%.2f.", $minOrder); } }
                else { $botResponse = "Delivery seems free!"; if ($minOrder > 0) { $botResponse .= sprintf(" (Min order: ₹%.2f).", $minOrder); } }
                mysqli_free_result($result);
             } else { $botResponse = "Could not fetch delivery fee info."; }
            $context['expecting'] = null;
        }
        // Help
         elseif (hasKeywords($lowerUserMessage, $helpKeywords)) {
             $currentIntent = 'Help';
             $botResponse = "I can help you:\n- Find restaurants: 'show restaurants in [city]'\n- Check order status: 'track my order'\n- Get dish prices: 'price of [dish name]'\n- Add to cart: 'add [dish name] to cart'\n- View cart: 'show my cart'\n- Check restaurant hours: 'hours for [restaurant name]'\n- See delivery fee: 'delivery charge'";
             $context['expecting'] = null;
         }
         // Fallback (if nothing else matched)
         else {
             // $currentIntent remains 'Fallback'
             // Keep the default "Sorry..." response
             $context['expecting'] = null; // Clear expectation on fallback
         }
    } // End of general matching block

} elseif (empty($userMessage)) {
    $currentIntent = 'EmptyInput';
    $botResponse = "Did you mean to type something?";
}

// --- Update context's last_intent ---
// Only update if intent changed from fallback or initial state
if ($currentIntent !== 'Fallback' || $context['last_intent'] === null) {
    $_SESSION['chatbot_context']['last_intent'] = $currentIntent;
}
// Persist context changes made via the reference $context
$_SESSION['chatbot_context'] = $context;


// --- Send Response ---
// Ensure $db connection is closed *only* if it was successfully opened and is not already closed
if (isset($db) && $db && mysqli_ping($db)) { // mysqli_ping checks if connection is still alive
    mysqli_close($db);
}
echo json_encode(['reply' => $botResponse]);
exit; // Ensure script termination after sending JSON
?>