<?php
header('Content-Type: application/json');

// Function to geocode an address using LocationIQ
function geocodeAddress(string $address, string $api_key): ?array {
    $address = urlencode($address);
    $url = "https://us1.locationiq.com/v1/search?key={$api_key}&q={$address}&format=json&addressdetails=1&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 429) {
        error_log("LocationIQ rate limit exceeded");
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    // Check if we got a valid result
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        $latitude = floatval($data[0]['lat']);
        $longitude = floatval($data[0]['lon']);
        
        // Extract city from address components
        $city = '';
        if (isset($data[0]['address'])) {
            $city = $data[0]['address']['city'] ?? 
                    $data[0]['address']['town'] ?? 
                    $data[0]['address']['village'] ?? '';
        }
        if (empty($city) && isset($data[0]['display_name'])) {
            $parts = array_map('trim', explode(',', $data[0]['display_name']));
            foreach ($parts as $part) {
                if (preg_match('/^[A-Za-z\s]+$/', $part) && !preg_match('/\d/', $part)) {
                    $city = $part;
                    break;
                }
            }
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $city
        ];
    } else {
        error_log("Geocoding error: " . (isset($data['error']) ? $data['error'] : 'No results found'));
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

if (isset($_POST['address']) && !empty(trim($_POST['address']))) {
    $address = $_POST['address'];
    $geocode_result = geocodeAddress($address, $api_key);

    if ($geocode_result === null) {
        echo json_encode(['status' => 'error', 'message' => 'Could not determine the coordinates for the given address']);
    } else {
        echo json_encode([
            'status' => 'success',
            'latitude' => $geocode_result['latitude'],
            'longitude' => $geocode_result['longitude'],
            'city' => $geocode_result['city']
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Address parameter is missing or empty']);
}
?>