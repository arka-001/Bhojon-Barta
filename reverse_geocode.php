<?php
header('Content-Type: application/json'); // CRUCIAL: Tell the client it's JSON

if (isset($_GET['lat']) && isset($_GET['lng'])) {
    $latitude = urlencode($_GET['lat']); // Sanitize and encode
    $longitude = urlencode($_GET['lng']);
    $apiKey = "pk.5cf0dbea44f742c63a0aa40f0d8a5c3a"; // Your LocationIQ API key

    // LocationIQ reverse geocoding endpoint
    $apiUrl = "https://us1.locationiq.com/v1/reverse?key={$apiKey}&lat={$latitude}&lon={$longitude}&format=json";

    $response = file_get_contents($apiUrl);

    if ($response === false) {
        error_log("Error: file_get_contents failed to fetch URL: " . $apiUrl);
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to connect to the LocationIQ API."]);
        exit;
    }

    $data = json_decode($response, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error: json_decode failed. Error: " . json_last_error_msg());
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to decode API response."]);
        exit;
    }

    // Check if the response is successful and contains an address
    if (isset($data['display_name']) && !isset($data['error'])) {
        http_response_code(200); // OK
        echo json_encode(["address" => $data['display_name']]);
    } else {
        error_log("LocationIQ API error: " . json_encode($data)); // Log the full error data
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Could not retrieve address from LocationIQ."]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid request. Provide lat and lng parameters."]);
}
?>