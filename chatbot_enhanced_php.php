<?php
session_start(); // Access user session for user-specific actions

// --- Load Dependencies ---
require_once 'vendor/autoload.php'; // For Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? ''; // Load Gemini API key

// --- Error Reporting (Enable for Localhost Debugging ONLY!) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Database Connection ---
require_once 'connection/connect.php'; // Adjust path if needed

// --- Set Response Header ---
header('Content-Type: application/json');

// --- Initialize Response Variables ---
$botResponse = "Sorry, I couldn't quite understand that. Type 'help' for options.";
$currentIntent = 'Fallback'; // Default intent
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; // Get logged-in user ID
$systemInstruction = "You are a food ordering assistant. Only answer questions about food, orders, delivery, or menus. For any other topic, respond with: 'Sorry, I can only help with food orders.'";

// --- Get User Input ---
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';
$lowerUserMessage = ''; // Initialize
if ($userMessage) {
    $lowerUserMessage = strtolower($userMessage);
    $lowerUserMessage = preg_replace('/[?!.,;:]$/', '', $lowerUserMessage); // Remove punctuation
    $lowerUserMessage = preg_replace('/\s+/', ' ', $lowerUserMessage); // Normalize spaces
}

// --- CONTEXT Management (Using Session) ---
if (!isset($_SESSION['chatbot_context'])) {
    $_SESSION['chatbot_context'] = [
        'last_intent' => null,
        'expecting' => null,
        'data' => []
    ];
}
$context = &$_SESSION['chatbot_context']; // Use reference

// --- Helper Functions ---
function hasKeywords($message, $keywords) {
    if (empty($message)) return false;
    foreach ($keywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $message)) return true;
    }
    return false;
}

function getRandomResponse($responses) {
    return $responses[array_rand($responses)];
}

function callGeminiAPI($userMessage, $systemInstruction = "") {
    global $geminiApiKey;
    if (empty($geminiApiKey)) {
        error_log("Gemini API key is missing.");
        return "Sorry, the AI service is not configured properly.";
    }

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($geminiApiKey);
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $userMessage]
                ]
            ]
        ]
    ];
    if ($systemInstruction) {
        $payload["systemInstruction"] = [
            "parts" => [
                ["text" => $systemInstruction]
            ]
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        error_log("Gemini API error: HTTP $httpCode, Response: " . ($response ?: 'No response'));
        return "Sorry, I’m having trouble connecting to the AI service.";
    }
    
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    error_log("Gemini API invalid response: " . json_encode($data));
    return "Sorry, I couldn’t process that request.";
}

// --- Define Keyword Lists & Response Variations ---
$findRestaurantKeywords = ['restaurant', 'place', 'food', 'eat', 'eatery', 'find', 'show', 'list', 'search', 'look for', 'where can i', 'serving'];
$orderKeywords = ['order', 'delivery', 'food', 'track', 'status', 'where'];
$priceKeywords = ['price', 'cost', 'how much', 'value', 'charge', 'rate', 'pay for'];
$hoursKeywords = ['hours', 'opening', 'open times', 'when open', 'timing', 'schedule', 'close'];
$cartKeywords = ['cart', 'basket', 'bag', 'items', 'checkout', 'my order'];
$addKeywords = ['add', 'put', 'include', 'want', 'get', 'order', 'buy'];
$removeKeywords = ['remove', 'delete', 'take out', 'cancel', 'empty'];
$helpKeywords = ['help', 'options', 'menu', 'what can you do', 'commands', 'assist', 'guide'];
$greetingKeywords = ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening', 'yo', 'howdy', 'sup', 'what\'s up'];
$cityKeywords = ['in', 'near', 'at', 'around', 'for'];
$affirmativeKeywords = ['yes', 'yeah', 'yep', 'ok', 'okay', 'sure', 'confirm', 'do it', 'please', 'correct', 'right', 'that one', 'proceed'];
$negativeKeywords = ['no', 'nope', 'nah', 'cancel', 'stop', 'don\'t', 'wrong', 'incorrect', 'never mind'];
$knownCities = ['kolkata', 'berhampore', 'murshidabad', 'nalhati'];

// Response Variations
$greetingResponses = ["Hello! How can I help with your food order?", "Hi there! What can I get for you today?", "Hey! Need help finding food or checking an order? Let me know!"];
$askCityResponses = ["Sure, which city are you interested in?", "Okay, what city should I look in?", "Which city's restaurants are you looking for?"];
$askDishPriceResponses = ["Which dish are you asking about?", "Okay, what's the name of the item?", "Which item's price do you need?"];
$askDishAddResponses = ["What item would you like to add?", "Okay, tell me the dish name to add.", "What should I add to your cart?"];
$askHoursResponses = ["Sure, which restaurant's hours do you need?", "Okay, tell me the restaurant name."];
$confirmAddResponses = ["Okay, added 1 %s to your cart!", "Got it, 1 %s is now in your cart.", "Added %s to your cart."];
$fallbackResponses = ["Sorry, I didn't quite catch that. Could you rephrase? Type 'help' for options.", "Hmm, I'm not sure how to answer that. Try asking differently or type 'help'.", "My apologies, I couldn't understand. You can ask me about restaurants, orders, prices, or type 'help'."];

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
                        $restaurants = [];
                        $context['data']['found_restaurants'] = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $restaurants[] = htmlspecialchars($row['title']) . " (" . htmlspecialchars($row['address']) . ")";
                            $context['data']['found_restaurants'][] = $row['rs_id'];
                        }
                        $rawResponse = "Found open restaurants matching '" . htmlspecialchars($cityName) . "':\n- " . implode("\n- ", $restaurants);
                        if (mysqli_num_rows($result) == 5) $rawResponse .= "\n(Showing first 5)";
                        // Format with Gemini
                        $geminiPrompt = "Format this restaurant list into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result);
                    } else {
                        $botResponse = "Sorry, couldn't find open restaurants matching '" . htmlspecialchars($cityName) . "'.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $botResponse = "Sorry, error searching restaurants.";
                    error_log("DB Prep Error (Ctx City): " . mysqli_error($db));
                }
                $context['expecting'] = null;
                $contextHandled = true;
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
                    if ($result && mysqli_num_rows($result) > 0) {
                        $dishesInfo = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $dishesInfo[] = sprintf("%s at %s costs ₹%.2f", htmlspecialchars($row['title']), htmlspecialchars($row['restaurant_name']), $row['price']);
                        }
                        $rawResponse = "Found prices for:\n- " . implode("\n- ", $dishesInfo);
                        if (mysqli_num_rows($result) == 5) $rawResponse .= "\n(Showing first 5 matches)";
                        // Format with Gemini
                        $geminiPrompt = "Format this price list into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result);
                    } else {
                        $botResponse = "Sorry, couldn't find an available dish like '" . htmlspecialchars($dishName) . "'.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $botResponse = "Sorry, error looking up prices.";
                    error_log("DB Prep Error (Ctx Dish Price): " . mysqli_error($db));
                }
                $context['expecting'] = null;
                $contextHandled = true;
                break;

            case 'dish_name_for_add':
                $currentIntent = 'ProvideDishForAdd';
                $dishName = trim($userMessage);
                if ($userId <= 0) {
                    $botResponse = "Please log in to add items.";
                    $contextHandled = true;
                } else {
                    $safeDishName = mysqli_real_escape_string($db, $dishName);
                    $sql_find_dish = "SELECT d_id, rs_id, price, title FROM dishes WHERE title LIKE ? AND is_available = 1 ORDER BY title = ? DESC LIMIT 1";
                    $stmt_find = mysqli_prepare($db, $sql_find_dish);
                    if ($stmt_find) {
                        $searchTerm = "%" . $safeDishName . "%";
                        $exactTerm = $safeDishName;
                        mysqli_stmt_bind_param($stmt_find, "ss", $searchTerm, $exactTerm);
                        mysqli_stmt_execute($stmt_find);
                        $result_find = mysqli_stmt_get_result($stmt_find);
                        if ($dish = mysqli_fetch_assoc($result_find)) {
                            $d_id = $dish['d_id'];
                            $rs_id = $dish['rs_id'];
                            $price = $dish['price'];
                            $actualDishName = $dish['title'];
                            $quantity = 1;
                            $sql_insert = "INSERT INTO cart (u_id, d_id, res_id, quantity, price, added_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
                            $stmt_insert = mysqli_prepare($db, $sql_insert);
                            if ($stmt_insert) {
                                mysqli_stmt_bind_param($stmt_insert, "iiiid", $userId, $d_id, $rs_id, $quantity, $price);
                                if (mysqli_stmt_execute($stmt_insert)) {
                                    $rawResponse = sprintf("Added 1 %s to your cart!", htmlspecialchars($actualDishName));
                                    $geminiPrompt = "Format this cart addition into a friendly response:\n$rawResponse";
                                    $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                                } else {
                                    $botResponse = "Sorry, error adding to cart.";
                                    error_log("Cart INSERT Error: " . mysqli_stmt_error($stmt_insert));
                                }
                                mysqli_stmt_close($stmt_insert);
                            } else {
                                $botResponse = "Sorry, error preparing cart update.";
                                error_log("Cart Prepare Error: " . mysqli_error($db));
                            }
                            mysqli_free_result($result_find);
                        } else {
                            $botResponse = "Sorry, couldn't find an available dish named '" . htmlspecialchars($dishName) . "'.";
                        }
                        mysqli_stmt_close($stmt_find);
                    } else {
                        $botResponse = "Sorry, error finding dish.";
                        error_log("Find Dish Prep Error: " . mysqli_error($db));
                    }
                    $context['expecting'] = null;
                    $contextHandled = true;
                }
                break;

            case 'restaurant_name_for_hours':
                $currentIntent = 'ProvideRestaurantForHours';
                $restaurantName = trim($userMessage);
                $safeRestaurantName = mysqli_real_escape_string($db, $restaurantName);
                $sql = "SELECT title, o_hr, c_hr, o_days FROM restaurant WHERE title LIKE ? AND is_open = 1 ORDER BY title = ? DESC LIMIT 1";
                $stmt = mysqli_prepare($db, $sql);
                if ($stmt) {
                    $searchTerm = "%" . $safeRestaurantName . "%";
                    $exactTerm = $safeRestaurantName;
                    mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $exactTerm);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && $resto = mysqli_fetch_assoc($result)) {
                        $rawResponse = sprintf("%s is open %s from %s to %s.", htmlspecialchars($resto['title']), htmlspecialchars($resto['o_days']), htmlspecialchars($resto['o_hr']), htmlspecialchars($resto['c_hr']));
                        $geminiPrompt = "Format this restaurant hours info into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result);
                    } else {
                        $botResponse = "Couldn't find details for an open restaurant like '" . htmlspecialchars($restaurantName) . "'.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $botResponse = "Sorry, error checking hours.";
                    error_log("DB Prep Error (Ctx Rest Hours): " . mysqli_error($db));
                }
                $context['expecting'] = null;
                $contextHandled = true;
                break;
        }
    }

    // --- General Intent Matching (if no context handled) ---
    if (!$contextHandled) {
        if (hasKeywords($lowerUserMessage, $greetingKeywords)) {
            $currentIntent = 'Greeting';
            $botResponse = getRandomResponse($greetingResponses);
            $context = ['last_intent' => $currentIntent, 'expecting' => null, 'data' => []];
        } elseif (hasKeywords($lowerUserMessage, $findRestaurantKeywords) && preg_match('/\b(?:' . implode('|', $cityKeywords) . ')\s+([\w\s\-]+)\b/i', $lowerUserMessage, $matches_city)) {
            $currentIntent = 'FindRestaurant_WithCity';
            $cityName = trim($matches_city[1]);
            $safeCityName = mysqli_real_escape_string($db, $cityName);
            $sql = "SELECT rs_id, title, address FROM restaurant WHERE city LIKE ? AND is_open = 1 LIMIT 5";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                $searchTerm = "%" . $safeCityName . "%";
                mysqli_stmt_bind_param($stmt, "s", $searchTerm);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && mysqli_num_rows($result) > 0) {
                    $restaurants = [];
                    $context['data']['found_restaurants'] = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $restaurants[] = htmlspecialchars($row['title']) . " (" . htmlspecialchars($row['address']) . ")";
                        $context['data']['found_restaurants'][] = $row['rs_id'];
                    }
                    $rawResponse = "Found open restaurants matching '" . htmlspecialchars($cityName) . "':\n- " . implode("\n- ", $restaurants);
                    if (mysqli_num_rows($result) == 5) $rawResponse .= "\n(Top 5 shown)";
                    $geminiPrompt = "Format this restaurant list into a friendly response:\n$rawResponse";
                    $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                    mysqli_free_result($result);
                } else {
                    $botResponse = "Sorry, no open restaurants found matching '" . htmlspecialchars($cityName) . "'.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $botResponse = "Error searching restaurants.";
                error_log("DB Prep Error (Find City): " . mysqli_error($db));
            }
            $context['expecting'] = null;
        } elseif (hasKeywords($lowerUserMessage, $findRestaurantKeywords)) {
            $currentIntent = 'FindRestaurant_AskCity';
            $botResponse = getRandomResponse($askCityResponses);
            $context['expecting'] = 'city_name';
        } elseif (hasKeywords($lowerUserMessage, $orderKeywords) && preg_match('/\b(status|track|where)\b/i', $lowerUserMessage)) {
            $currentIntent = 'CheckOrderStatus';
            if ($userId > 0) {
                $sql = "SELECT o_id, order_id, title, status, date FROM users_orders WHERE u_id = ? ORDER BY o_id DESC LIMIT 1";
                $stmt = mysqli_prepare($db, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $userId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && $order = mysqli_fetch_assoc($result)) {
                        $status = $order['status'] ?? 'Unknown';
                        $orderTitle = $order['title'] ?? 'your order';
                        $orderDate = date("M d, Y H:i", strtotime($order['date']));
                        $rawResponse = "Latest order (#" . $order['o_id'] . " - " . htmlspecialchars($orderTitle) . " from " . $orderDate . ") status: **" . htmlspecialchars(ucfirst($status)) . "**.";
                        $geminiPrompt = "Format this order status into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result);
                    } else {
                        $botResponse = "Looks like you have no recent orders.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $botResponse = "Error fetching order status.";
                    error_log("DB Prep Error (Order Status): " . mysqli_error($db));
                }
            } else {
                $botResponse = "Please log in to check your order status.";
            }
            $context['expecting'] = null;
        } elseif (hasKeywords($lowerUserMessage, $priceKeywords) && preg_match('/(?:' . implode('|', $priceKeywords) . ')\s+(?:for|of|is|was|are|were)?\s*(.*)/i', $lowerUserMessage, $matches_dish)) {
            $currentIntent = 'GetDishPrice_WithDish';
            $dishName = trim($matches_dish[1]);
            $dishName = preg_replace('/[?]$/', '', $dishName);
            if (!empty($dishName)) {
                $safeDishName = mysqli_real_escape_string($db, $dishName);
                $sql = "SELECT d.title, d.price, r.title as restaurant_name FROM dishes d JOIN restaurant r ON d.rs_id = r.rs_id WHERE d.title LIKE ? AND d.is_available = 1 ORDER BY d.title = ? DESC LIMIT 5";
                $stmt = mysqli_prepare($db, $sql);
                if ($stmt) {
                    $searchTerm = "%" . $safeDishName . "%";
                    $exactTerm = $safeDishName;
                    mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $exactTerm);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && mysqli_num_rows($result) > 0) {
                        $dishesInfo = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $dishesInfo[] = sprintf("%s at %s costs ₹%.2f", htmlspecialchars($row['title']), htmlspecialchars($row['restaurant_name']), $row['price']);
                        }
                        $rawResponse = "Found prices for:\n- " . implode("\n- ", $dishesInfo);
                        if (mysqli_num_rows($result) == 5) $rawResponse .= "\n(Top 5 matches)";
                        $geminiPrompt = "Format this price list into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result);
                    } else {
                        $botResponse = "Sorry, couldn't find an available dish like '" . htmlspecialchars($dishName) . "'.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $botResponse = "Sorry, error looking up prices.";
                    error_log("DB Prep Error (Dish Price): " . mysqli_error($db));
                }
                $context['expecting'] = null;
            } else {
                $currentIntent = 'GetDishPrice_AskDish';
                $botResponse = getRandomResponse($askDishPriceResponses);
                $context['expecting'] = 'dish_name_for_price';
            }
        } elseif (hasKeywords($lowerUserMessage, $priceKeywords)) {
            $currentIntent = 'GetDishPrice_AskDish';
            $botResponse = getRandomResponse($askDishPriceResponses);
            $context['expecting'] = 'dish_name_for_price';
        } elseif (hasKeywords($lowerUserMessage, $addKeywords) && preg_match('/(?:' . implode('|', $addKeywords) . ')\s+(?:an|a|some)?\s*(.*?)\s*(?:to|in)?\s*(?:my|the)?\s*(?:' . implode('|', $cartKeywords) . ')?$/i', $lowerUserMessage, $matches_add)) {
            $currentIntent = 'AddToCart_WithDish';
            $dishName = trim($matches_add[1]);
            $dishName = preg_replace('/^\d+\s+|^one\s+|^two\s+|^three\s+/i', '', $dishName);
            if (!empty($dishName)) {
                if ($userId <= 0) {
                    $botResponse = "Please log in first.";
                } else {
                    $safeDishName = mysqli_real_escape_string($db, $dishName);
                    $sql_find_dish = "SELECT d_id, rs_id, price, title FROM dishes WHERE title LIKE ? AND is_available = 1 ORDER BY title = ? DESC LIMIT 1";
                    $stmt_find = mysqli_prepare($db, $sql_find_dish);
                    if ($stmt_find) {
                        $searchTerm = "%" . $safeDishName . "%";
                        $exactTerm = $safeDishName;
                        mysqli_stmt_bind_param($stmt_find, "ss", $searchTerm, $exactTerm);
                        mysqli_stmt_execute($stmt_find);
                        $result_find = mysqli_stmt_get_result($stmt_find);
                        if ($dish = mysqli_fetch_assoc($result_find)) {
                            $d_id = $dish['d_id'];
                            $rs_id = $dish['rs_id'];
                            $price = $dish['price'];
                            $actualDishName = $dish['title'];
                            $quantity = 1;
                            $sql_insert = "INSERT INTO cart (u_id, d_id, res_id, quantity, price, added_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
                            $stmt_insert = mysqli_prepare($db, $sql_insert);
                            if ($stmt_insert) {
                                mysqli_stmt_bind_param($stmt_insert, "iiiid", $userId, $d_id, $rs_id, $quantity, $price);
                                if (mysqli_stmt_execute($stmt_insert)) {
                                    $rawResponse = sprintf("Added 1 %s to your cart!", htmlspecialchars($actualDishName));
                                    $geminiPrompt = "Format this cart addition into a friendly response:\n$rawResponse";
                                    $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                                } else {
                                    $botResponse = "Sorry, error adding to cart.";
                                    error_log("Cart INSERT Error: " . mysqli_stmt_error($stmt_insert));
                                }
                                mysqli_stmt_close($stmt_insert);
                            } else {
                                $botResponse = "Error preparing cart update.";
                                error_log("Cart Prep Error: " . mysqli_error($db));
                            }
                            mysqli_free_result($result_find);
                        } else {
                            $botResponse = "Sorry, couldn't find an available dish named '" . htmlspecialchars($dishName) . "'.";
                        }
                        mysqli_stmt_close($stmt_find);
                    } else {
                        $botResponse = "Error finding dish.";
                        error_log("Find Dish Prep Error: " . mysqli_error($db));
                    }
                }
                $context['expecting'] = null;
            } else {
                $currentIntent = 'AddToCart_AskDish';
                $botResponse = getRandomResponse($askDishAddResponses);
                $context['expecting'] = 'dish_name_for_add';
            }
        } elseif (hasKeywords($lowerUserMessage, $addKeywords)) {
            $currentIntent = 'AddToCart_AskDish';
            $botResponse = getRandomResponse($askDishAddResponses);
            $context['expecting'] = 'dish_name_for_add';
        } elseif (hasKeywords($lowerUserMessage, $cartKeywords) && preg_match('/\b(view|show|see|display|check)\b/i', $lowerUserMessage)) {
            $currentIntent = 'ViewCart';
            if ($userId <= 0) {
                $botResponse = "Please log in to view your cart.";
            } else {
                $sql_cart = "SELECT c.quantity, c.price, d.title FROM cart c JOIN dishes d ON c.d_id = d.d_id WHERE c.u_id = ?";
                $stmt_cart = mysqli_prepare($db, $sql_cart);
                if ($stmt_cart) {
                    mysqli_stmt_bind_param($stmt_cart, "i", $userId);
                    mysqli_stmt_execute($stmt_cart);
                    $result_cart = mysqli_stmt_get_result($stmt_cart);
                    if ($result_cart && mysqli_num_rows($result_cart) > 0) {
                        $cartItems = [];
                        $total = 0;
                        while ($item = mysqli_fetch_assoc($result_cart)) {
                            $itemTotal = $item['quantity'] * $item['price'];
                            $cartItems[] = sprintf("%d x %s (₹%.2f)", $item['quantity'], htmlspecialchars($item['title']), $itemTotal);
                            $total += $itemTotal;
                        }
                        $rawResponse = "Here's your cart:\n- " . implode("\n- ", $cartItems) . "\n\nTotal: ₹" . number_format($total, 2);
                        $geminiPrompt = "Format this cart summary into a friendly response:\n$rawResponse";
                        $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                        mysqli_free_result($result_cart);
                    } else {
                        $botResponse = "Your cart is currently empty.";
                    }
                    mysqli_stmt_close($stmt_cart);
                } else {
                    $botResponse = "Error fetching cart.";
                    error_log("View Cart Prep Error: " . mysqli_error($db));
                }
            }
            $context['expecting'] = null;
        } elseif (hasKeywords($lowerUserMessage, $hoursKeywords) && preg_match('/(?:for|at)\s+(.*)/i', $lowerUserMessage, $matches)) {
            $currentIntent = 'GetHours_WithRestaurant';
            $restaurantName = trim($matches[1]);
            $safeRestaurantName = mysqli_real_escape_string($db, $restaurantName);
            $sql = "SELECT title, o_hr, c_hr, o_days FROM restaurant WHERE title LIKE ? AND is_open = 1 ORDER BY title = ? DESC LIMIT 1";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                $searchTerm = "%" . $safeRestaurantName . "%";
                $exactTerm = $safeRestaurantName;
                mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $exactTerm);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result && $resto = mysqli_fetch_assoc($result)) {
                    $rawResponse = sprintf("%s is open %s from %s to %s.", htmlspecialchars($resto['title']), htmlspecialchars($resto['o_days']), htmlspecialchars($resto['o_hr']), htmlspecialchars($resto['c_hr']));
                    $geminiPrompt = "Format this restaurant hours info into a friendly response:\n$rawResponse";
                    $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                    mysqli_free_result($result);
                } else {
                    $botResponse = "Couldn't find details for an open restaurant like '" . htmlspecialchars($restaurantName) . "'.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $botResponse = "Sorry, error checking hours.";
                error_log("DB Prep Error (Rest Hours): " . mysqli_error($db));
            }
            $context['expecting'] = null;
        } elseif (hasKeywords($lowerUserMessage, $hoursKeywords)) {
            $currentIntent = 'GetHours_AskRestaurant';
            $botResponse = getRandomResponse($askHoursResponses);
            $context['expecting'] = 'restaurant_name_for_hours';
        } elseif (preg_match('/\b(delivery|shipping)\s+(fee|charge|cost)\b/i', $lowerUserMessage)) {
            $currentIntent = 'DeliveryFee';
            $sql = "SELECT min_order_value, delivery_charge FROM delivary_charges WHERE id = 1 LIMIT 1";
            $result = mysqli_query($db, $sql);
            if ($result && $chargeInfo = mysqli_fetch_assoc($result)) {
                $charge = (float)$chargeInfo['delivery_charge'];
                $minOrder = (float)$chargeInfo['min_order_value'];
                if ($charge > 0) {
                    $rawResponse = sprintf("Std. delivery: ₹%.2f.", $charge);
                    if ($minOrder > 0) {
                        $rawResponse .= sprintf(" May be free over ₹%.2f.", $minOrder);
                    }
                } else {
                    $rawResponse = "Delivery seems free!";
                    if ($minOrder > 0) {
                        $rawResponse .= sprintf(" (Min order: ₹%.2f).", $minOrder);
                    }
                }
                $geminiPrompt = "Format this delivery charge info into a friendly response:\n$rawResponse";
                $botResponse = callGeminiAPI($geminiPrompt, $systemInstruction);
                mysqli_free_result($result);
            } else {
                $botResponse = "Could not fetch delivery fee info.";
            }
            $context['expecting'] = null;
        } elseif (hasKeywords($lowerUserMessage, $helpKeywords)) {
            $currentIntent = 'Help';
            $botResponse = "I can help you:\n- Find restaurants: 'show restaurants in [city]'\n- Check order status: 'track my order'\n- Get dish prices: 'price of [dish name]'\n- Add to cart: 'add [dish name] to cart'\n- View cart: 'show my cart'\n- Check restaurant hours: 'hours for [restaurant name]'\n- See delivery fee: 'delivery charge'";
            $context['expecting'] = null;
        } else {
            // Fallback: Use Gemini API for unrecognized inputs
            $currentIntent = 'Fallback';
            $botResponse = callGeminiAPI($userMessage, $systemInstruction);
            $context['expecting'] = null;
        }
    }
} elseif (empty($userMessage)) {
    $currentIntent = 'EmptyInput';
    $botResponse = "Did you mean to type something?";
}

// --- Update Session Context ---
$_SESSION['chatbot_context']['last_intent'] = $currentIntent;
$_SESSION['chatbot_context'] = $context;

// --- Send Final JSON Response ---
if (isset($db) && $db && mysqli_ping($db)) {
    mysqli_close($db);
}
echo json_encode(['reply' => $botResponse]);
exit;
?>