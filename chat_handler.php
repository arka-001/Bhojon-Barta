<?php
// ===== CHATBOT BACKEND HANDLER v4.5 (API Key Updated) =====

session_start();
require_once __DIR__ . '/connection/connect.php'; 
require_once __DIR__ . '/vendor/autoload.php';

// --- Error Reporting & Headers ---
ini_set('display_errors', 0); // Correct for production
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json');

// --- Configuration ---
// The API key has been updated as per your latest request.
$openRouterApiKey = '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';
$yourSiteUrl = 'bhojonsathi.test'; // IMPORTANT: For local testing. Change to your real domain when you go live.

if (empty($openRouterApiKey) || strpos($openRouterApiKey, 'sk-or-v1-') !== 0) {
    http_response_code(500);
    error_log("FATAL: OpenRouter API Key is missing or invalid in chat_handler.php.");
    echo json_encode(['reply' => 'Chatbot is not configured correctly. The API key is missing.']);
    exit; 
}

// --- Input Processing ---
$input = json_decode(file_get_contents('php://input'), true);
$question = trim($input['question'] ?? '');

if (empty($question)) {
    http_response_code(400);
    echo json_encode(['reply' => 'Please ask a question.']);
    exit;
}

// --- Context Data Gathering ---
$user_id = $_SESSION['user_id'] ?? null;
$is_logged_in = !is_null($user_id);
$user_city = $_SESSION['user_city'] ?? '';
$contextData = [
    'currentUser' => null, 'orders' => [], 'favorites' => ['dishes' => []],
    'openRestaurants' => [], 'coupons' => [], 'delivery_charges' => null, 
    'static_content' => ['contact' => null]
];

try {
    if ($is_logged_in) {
        $stmt_user = $db->prepare("SELECT username, name, email, phone, city FROM users WHERE u_id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $contextData['currentUser'] = $stmt_user->get_result()->fetch_assoc();
        $stmt_user->close();
        if(!empty($contextData['currentUser']['city'])) { $user_city = $contextData['currentUser']['city']; }

        $stmt_orders = $db->prepare("SELECT o.title, o.quantity, o.status, o.date, r.title as restaurant_name FROM users_orders o LEFT JOIN restaurant r ON o.rs_id = r.rs_id WHERE o.u_id = ? ORDER BY o.date DESC LIMIT 5");
        $stmt_orders->bind_param("i", $user_id);
        $stmt_orders->execute();
        $contextData['orders'] = $stmt_orders->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_orders->close();

        $stmt_fav_dishes = $db->prepare("SELECT d.title as dish_name, r.title as restaurant_name FROM user_favorite_dishes ufd JOIN dishes d ON ufd.d_id = d.d_id JOIN restaurant r ON d.rs_id = r.rs_id WHERE ufd.u_id = ?");
        $stmt_fav_dishes->bind_param("i", $user_id);
        $stmt_fav_dishes->execute();
        $contextData['favorites']['dishes'] = $stmt_fav_dishes->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_fav_dishes->close();
    }
    
    $restaurantSql = "SELECT r.rs_id, r.title AS restaurant_name, r.address, r.city, r.o_hr, r.c_hr, d.title AS dish_name, d.slogan AS dish_description, d.price, d.offer_price FROM restaurant r LEFT JOIN dishes d ON r.rs_id = d.rs_id AND d.is_available = 1 WHERE r.is_open = 1";
    if (!empty($user_city)) {
        $restaurantSql .= " AND r.city LIKE ?";
        $stmt_rest = $db->prepare($restaurantSql);
        $city_param = "%{$user_city}%";
        $stmt_rest->bind_param('s', $city_param);
    } else {
        $stmt_rest = $db->prepare($restaurantSql);
    }
    
    $stmt_rest->execute();
    $result = $stmt_rest->get_result();
    $restaurantMap = [];
    while ($row = $result->fetch_assoc()) {
        $rs_id = $row['rs_id'];
        if (!isset($restaurantMap[$rs_id])) {
            $restaurantMap[$rs_id] = [
                'id' => $rs_id, 'name' => $row['restaurant_name'], 'address' => $row['address'],
                'hours' => "{$row['o_hr']} to {$row['c_hr']}", 'dishes' => []
            ];
        }
        if ($row['dish_name']) {
            $price = !is_null($row['offer_price']) ? $row['offer_price'] : $row['price'];
            $restaurantMap[$rs_id]['dishes'][] = [ 'name' => $row['dish_name'], 'description' => $row['dish_description'], 'price' => (float)$price ];
        }
    }
    $contextData['openRestaurants'] = array_values($restaurantMap);
    $stmt_rest->close();

    $contextData['coupons'] = mysqli_query($db, "SELECT coupon_code, discount_type, discount_value, min_order_value FROM coupons WHERE is_active = 1 AND (expiration_date IS NULL OR expiration_date >= CURDATE())")->fetch_all(MYSQLI_ASSOC);
    $contextData['delivery_charges'] = mysqli_query($db, "SELECT min_order_value, delivery_charge FROM delivary_charges LIMIT 1")->fetch_assoc();
    $contextData['static_content']['contact'] = mysqli_query($db, "SELECT address, phone FROM footer_settings LIMIT 1")->fetch_assoc();

} catch (Exception $e) {
    http_response_code(500);
    error_log("Chatbot Data Fetch Error: " . $e->getMessage());
    echo json_encode(['reply' => 'I am having trouble accessing our restaurant information right now. Please try again in a moment.']);
    exit;
}

$contextJson = json_encode($contextData, JSON_PRETTY_PRINT);

// ===== SYSTEM PROMPT (v3.7) =====
$systemPrompt = <<<EOT
You are 'FoodieBot', an AI assistant for a food ordering website.
**Your Instructions:**
1.  **Golden Rule (HIGHEST PRIORITY):** If a user's message is not a clear, understandable question about food, restaurants, or orders (e.g., it is gibberish like 'sdkjfh' or a random statement like 'the sky is blue'), your ONLY permitted response is: "Sorry, I can only answer questions about our menu and orders. How can I help?" Under NO circumstances should you provide any other information, greetings, or summaries when you don't understand the request. Do not try to be helpful by guessing.
2.  **Be Brief & Direct:** When you DO understand the question, get straight to the point. Answer it clearly and then stop. Example: If asked "price of fried rice", simply reply "The fried rice is 160."
3.  **Use ONLY Provided Data:** Your knowledge is STRICTLY limited to the JSON data below. If the information isn't in the JSON, you must state that you don't have that information. Example: "Sorry, I don't have information on daily specials."
4.  **Logged-in Users:** If `currentUser` has data, you can greet them by their username simply. Example: "Hi, arka0001. How can I help?"
5.  **Coupon Code Rule:** Never state actual coupon codes. Just say, "Eligible discounts are applied automatically at checkout."
Your primary goal is to follow the Golden Rule strictly and provide brief, accurate answers for valid questions.
Here is the JSON data you must use:
$contextJson
EOT;

// --- API Call and Response Processing ---
$payload = ['model' => 'mistralai/mistral-7b-instruct:free', 'messages' => [['role' => 'system', 'content' => $systemPrompt], ['role' => 'user', 'content' => $question]]];

// *** FIX FOR 502 ERROR ***
// OpenRouter requires these headers for free-tier models.
$headers = [
    'Authorization: Bearer ' . $openRouterApiKey,
    'Content-Type: application/json',
    'HTTP-Referer: http://' . $yourSiteUrl, // The site URL you set above
    'X-Title: BhojonSathi Chatbot'          // A title for your app
];

$ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 60]);
$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    http_response_code(502); // Bad Gateway
    // Enhanced error log for debugging
    error_log("OpenRouter API Error: [HTTP Code: $httpCode] [cURL Error: $curlError] [API Response: $apiResponse]");
    echo json_encode(['reply' => 'My apologies, I am having trouble connecting to my knowledge base right now. Please try again later.']);
    exit;
}

$result = json_decode($apiResponse, true);
$reply = $result['choices'][0]['message']['content'] ?? 'I am sorry, I am unable to answer that right now.';
echo json_encode(['reply' => trim($reply)]);