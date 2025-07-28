<?php
// reverse_geocode_checkout.php
header('Content-Type: application/json');

// Function to reverse geocode coordinates using LocationIQ
function reverseGeocode(string $lat, string $lon, string $api_key): ?array {
    $url = "https://us1.locationiq.com/v1/reverse.php?key={$api_key}&lat={$lat}&lon={$lon}&format=json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (not recommended for production)
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data) && isset($data['display_name'])) {
        $city = 'Unknown';
        if (isset($data['address'])) {
            $address_components = $data['address'];
            if (isset($address_components['city'])) {
                $city = $address_components['city'];
            } elseif (isset($address_components['town'])) {
                $city = $address_components['town'];
            } elseif (isset($address_components['village'])) {
                $city = $address_components['village'];
            } elseif (isset($address_components['county'])) {
                $city = $address_components['county'];
            }
        }
        return [
            'address' => $data['display_name'],
            'city' => $city
        ];
    } else {
        error_log("Reverse geocoding error: " . (isset($data['error']) ? $data['error'] : 'No address found'));
        return null;
    }
}

// Get the API key
$api_key = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Your LocationIQ API key

if (empty($api_key)) {
    error_log("The LOCATIONIQ_API_KEY is Not Set");
    echo json_encode(['status' => 'error', 'message' => 'The LOCATIONIQ_API_KEY is Not Set']);
    exit();
}

if (isset($_POST['lat']) && isset($_POST['lon']) && !empty(trim($_POST['lat'])) && !empty(trim($_POST['lon']))) {
    $lat = $_POST['lat'];
    $lon = $_POST['lon'];
    $result = reverseGeocode($lat, $lon, $api_key);

    if ($result === null) {
        echo json_encode(['status' => 'error', 'message' => 'Could not determine the address for the given coordinates']);
    } else {
        echo json_encode([
            'status' => 'success',
            'address' => $result['address'],
            'city' => $result['city']
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Latitude or longitude parameters are missing or empty']);
}
?>