<?php
include("../connection/connect.php");
error_reporting(0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city'])) {
    $input_city = trim($_POST['city']);
    
    // Log input city
    error_log("Input city: '$input_city'");

    // Fetch all active cities for debugging
    $sql = "SELECT city_name FROM delivery_cities WHERE is_active = 1";
    $result = mysqli_query($db, $sql);
    $available_cities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $available_cities[] = $row['city_name'];
    }
    error_log("Available cities: " . implode(", ", $available_cities));

    // Case-insensitive city check
    $sql = "SELECT city_name FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "s", $input_city);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        error_log("Matched city: " . $row['city_name']);
        echo json_encode(['is_available' => true, 'city' => $row['city_name']]);
    } else {
        error_log("No match for city: '$input_city'");
        echo json_encode(['is_available' => false, 'message' => "City '$input_city' not found in delivery_cities"]);
    }

    mysqli_stmt_close($stmt);
} else {
    error_log("Invalid request: " . json_encode($_POST));
    echo json_encode(['is_available' => false, 'message' => 'Invalid request']);
}

mysqli_close($db);
?>