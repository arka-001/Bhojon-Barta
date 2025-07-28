<?php
header('Content-Type: application/json');
// Corrected include path: Go up one level from restaurant_owner
include("../connection/connect.php");

$response = ['serviceable' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city'])) {
    $cityName = trim($_POST['city']);

    if (!empty($cityName)) {
        $db = $GLOBALS['db']; // Use global connection
        if (!$db) {
             error_log("DB connection missing in check_city.php");
             echo json_encode(['serviceable' => false, 'error' => 'Server configuration error.']);
             exit;
        }

        $stmt = $db->prepare("SELECT city_id FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1");
        if ($stmt) {
            $cityNameLower = strtolower($cityName);
            $stmt->bind_param("s", $cityNameLower);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $response['serviceable'] = true;
                }
            } else { error_log("Error executing city check: " . $stmt->error); $response['error'] = 'DB query failed.'; }
            $stmt->close();
        } else { error_log("Failed preparing city check: " . $db->error); $response['error'] = 'DB error.'; }
    } else { $response['error'] = 'City name missing.'; }
} else { $response['error'] = 'Invalid request.'; }

echo json_encode($response);
exit();
?>