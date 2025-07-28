<?php
// check_city_service.php
session_start(); // Optional: if you need session data, though likely not for this check
header('Content-Type: application/json');

// Assuming connect.php is one level up
include("../connection/connect.php"); // Adjust path as needed

$response = ['serviceable' => false, 'message' => 'City name not provided.']; // Default response

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city_name'])) {
    $cityName = trim($_POST['city_name']);

    if (empty($cityName)) {
        $response['message'] = 'City name cannot be empty.';
        echo json_encode($response);
        exit;
    }

    if ($db) { // Check if DB connection exists
        $city_check_sql = "SELECT city_id FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1";
        $stmt_city = $db->prepare($city_check_sql);

        if ($stmt_city) {
            $city_lower = strtolower($cityName);
            $stmt_city->bind_param("s", $city_lower);

            if ($stmt_city->execute()) {
                $stmt_city->store_result();
                if ($stmt_city->num_rows > 0) {
                    $response['serviceable'] = true; // City found and is active
                    $response['message'] = 'City is serviceable.';
                } else {
                    $response['serviceable'] = false; // City not found or inactive
                    $response['message'] = 'Service not available in this city.';
                }
            } else {
                 error_log("City Check Execute Error: " . $stmt_city->error);
                 $response['message'] = 'Error checking city status.';
            }
            $stmt_city->close();
        } else {
            error_log("City Check Prepare Error: " . $db->error);
            $response['message'] = 'Database error during city check preparation.';
        }
    } else {
        error_log("City Check DB Connection Error: Database connection not available.");
        $response['message'] = 'Database connection error.';
    }
}

// Close the database connection if it was opened
if (isset($db) && $db && $db instanceof mysqli) {
    $db->close();
}

echo json_encode($response);
exit;
?>