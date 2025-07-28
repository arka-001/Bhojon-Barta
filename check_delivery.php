<?php
ob_start(); // Start output buffering

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/path/to/your/php_errors.log'); // Replace with your actual log path

include("connection/connect.php");

// Log that the script is accessed
error_log("check_delivery.php accessed");

// Check if this is an AJAX request
if (!isset($_POST['check_delivery']) || !isset($_POST['city'])) {
    error_log("Invalid request: Missing check_delivery or city parameter");
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$city = mysqli_real_escape_string($db, $_POST['city']);
error_log("AJAX Request Received - City: " . $city);

// Verify database connection
if (!isset($db) || !$db->ping()) {
    error_log("DB Connection Failed: " . ($db->connect_error ?? "No DB object"));
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
error_log("DB Connection Test: Connected");

// Check if the provided city matches any active delivery city
$stmt = $db->prepare("SELECT city_name FROM delivery_cities WHERE LOWER(city_name) LIKE LOWER(?) AND is_active = 1");
if (!$stmt) {
    error_log("Prepare failed for city check: " . $db->error);
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'City query preparation failed']);
    exit;
}

// Use wildcard to match city name within the input (e.g., "kolkata" in "Kolkata, West Bengal")
$city_param = "%" . $city . "%";
$stmt->bind_param("s", $city_param);
if (!$stmt->execute()) {
    error_log("Execute failed for city check: " . $stmt->error);
    $stmt->close();
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'City query execution failed']);
    exit;
}

$stmt->bind_result($city_name);
$city_exists = $stmt->fetch();
$verified_city = $city_exists ? $city_name : $city;
$stmt->close();

error_log("Checking delivery for city: '$city', Exists: " . ($city_exists ? 'Yes' : 'No'));

// Check delivery boy availability
$delivery_boy_available = false;
if ($city_exists) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM delivery_boy WHERE LOWER(city) = LOWER(?) AND current_status = 'available' AND db_status = 1");
    if (!$stmt) {
        error_log("Prepare failed for delivery boy check: " . $db->error);
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Delivery boy query preparation failed']);
        exit;
    }

    $stmt->bind_param("s", $verified_city);
    if ($stmt->execute()) {
        $stmt->bind_result($count);
        $stmt->fetch();
        $delivery_boy_available = $count > 0;
        error_log("Delivery boy check for city '$verified_city': Count = $count");
    } else {
        error_log("Execute failed for delivery boy check: " . $stmt->error);
        $stmt->close();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Delivery boy query execution failed']);
        exit;
    }
    $stmt->close();
}

// If no exact match, list available cities for better feedback
$available_cities = [];
if (!$city_exists) {
    $result = $db->query("SELECT city_name FROM delivery_cities WHERE is_active = 1");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $available_cities[] = $row['city_name'];
        }
        error_log("No match found. Available cities: " . implode(", ", $available_cities));
    } else {
        error_log("Failed to fetch available cities: " . $db->error);
    }
}

ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'available' => $city_exists,
    'delivery_boy_available' => $delivery_boy_available,
    'city' => $verified_city,
    'available_cities' => $city_exists ? [] : $available_cities
]);
exit;
?>