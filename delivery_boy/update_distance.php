<?php
include("../connection/connect.php");

// Start the session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function (same as in view_order_delivery_boy.php)
function distance($lat1, $lon1, $lat2, $lon2, $unit) {
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}

// Get coordinates from GET parameters and validate
$deliveryBoyLat = isset($_GET['deliveryBoyLat']) && is_numeric($_GET['deliveryBoyLat']) ? floatval($_GET['deliveryBoyLat']) : null;
$deliveryBoyLon = isset($_GET['deliveryBoyLon']) && is_numeric($_GET['deliveryBoyLon']) ? floatval($_GET['deliveryBoyLon']) : null;
$customerLat = isset($_GET['customerLat']) && is_numeric($_GET['customerLat']) ? floatval($_GET['customerLat']) : null;
$customerLon = isset($_GET['customerLon']) && is_numeric($_GET['customerLon']) ? floatval($_GET['customerLon']) : null;

// Validate coordinates
if ($deliveryBoyLat === null || $deliveryBoyLon === null || $customerLat === null || $customerLon === null) {
    error_log("Invalid coordinates received in update_distance.php");
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// Calculate distance
$distance = distance($deliveryBoyLat, $deliveryBoyLon, $customerLat, $customerLon, "K");

// Check if we should update the database
$updateDb = isset($_GET['update_db']) && $_GET['update_db'] == 1;

if ($updateDb) {
    // Get the delivery boy ID from the session
    $db_id = $_SESSION['db_id'] ?? null;

    if ($db_id === null) {
        error_log("Delivery boy ID not found in session in update_distance.php");
        echo json_encode(['error' => 'Delivery boy ID not found in session.']);
        exit;
    }

    // Sanitize the coordinates before using them in the query
    $deliveryBoyLat = mysqli_real_escape_string($db, $deliveryBoyLat);
    $deliveryBoyLon = mysqli_real_escape_string($db, $deliveryBoyLon);

    // Update the delivery_boy table
    $updateSql = "UPDATE delivery_boy SET latitude = ?, longitude = ? WHERE db_id = ?";
    $updateStmt = mysqli_prepare($db, $updateSql);

    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, "ddi", $deliveryBoyLat, $deliveryBoyLon, $db_id);
        mysqli_stmt_execute($updateStmt);

        if (mysqli_stmt_affected_rows($updateStmt) > 0) {
            error_log("Location updated in database for delivery boy ID: " . $db_id);
        } else {
            error_log("No rows updated.  Possible causes: Location update failed or the ID does not exist. for delivery boy ID: " . $db_id);
        }

        mysqli_stmt_close($updateStmt);
    } else {
        error_log("Database error preparing location update statement: " . mysqli_error($db));
        echo json_encode(['error' => 'Database error updating location.']);
        exit;
    }
}

// Return distance as JSON
echo json_encode(['distance' => number_format($distance, 2)]);
?>