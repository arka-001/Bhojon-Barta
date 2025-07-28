<?php
header('Content-Type: application/json');

// Function to fetch address suggestions using LocationIQ Autocomplete API
function getAddressSuggestions(string $query, string $api_key): array {
    $query = urlencode($query);
    $url = "https://us1.locationiq.com/v1/autocomplete?key={$api_key}&q={$query}&limit=5";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (not recommended for production)
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error (autocomplete): " . curl_error($ch));
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    $suggestions = [];

    if (!empty($data)) {
        foreach ($data as $item) {
            if (isset($item['lat']) && isset($item['lon'])) {
                $city = 'Unknown';
                if (isset($item['address'])) {
                    $address_components = $item['address'];
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
                $suggestions[] = [
                    'display_name' => $item['display_name'],
                    'lat' => number_format((float)$item['lat'], 8, '.', ''),
                    'lon' => number_format((float)$item['lon'], 8, '.', ''),
                    'city' => $city
                ];
            }
        }
    }
    return $suggestions;
}

// Function to geocode an address using LocationIQ (existing function)
function geocodeAddress(string $address, string $api_key): ?array {
    $address = urlencode($address);
    $url = "https://us1.locationiq.com/v1/autocomplete?key={$api_key}&q={$address}&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error (geocode): " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        $latitude = number_format((float)$data[0]['lat'], 8, '.', '');
        $longitude = number_format((float)$data[0]['lon'], 8, '.', '');

        $city = 'Unknown';
        if (isset($data[0]['address'])) {
            $address_components = $data[0]['address'];
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
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $city
        ];
    } else {
        error_log("Geocoding error: " . (isset($data['error']) ? $data['error'] : 'No results found'));
        return null;
    }
}

// API Key
$api_key = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Your LocationIQ API key

if (empty($api_key)) {
    error_log("The LOCATIONIQ_API_KEY is Not Set");
    echo json_encode(['status' => 'error', 'message' => 'The LOCATIONIQ_API_KEY is Not Set']);
    exit();
}

// Handle Autocomplete Request
if (isset($_POST['autocomplete']) && !empty(trim($_POST['query']))) {
    $query = $_POST['query'];
    $suggestions = getAddressSuggestions($query, $api_key);
    echo json_encode([
        'status' => 'success',
        'suggestions' => $suggestions
    ]);
    exit();
}

// Handle Geocode Request
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