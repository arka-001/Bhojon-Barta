<?php
// get_map_image_url.php
header('Content-Type: application/json');

// Function to fetch polyline from LocationIQ Directions API
function getDirections($origin_lat, $origin_lon, $destination_lat, $destination_lon, $apiKey) {
    $origin = $origin_lon . ',' . $origin_lat; // LocationIQ uses lon,lat order
    $destination = $destination_lon . ',' . $destination_lat;

    $directionsUrl = "https://api.locationiq.com/v1/directions/driving/{$origin};{$destination}?key={$apiKey}&overview=full";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $directionsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['code']) && $data['code'] === 'Ok' && isset($data['routes'][0]['geometry'])) {
        return $data['routes'][0]['geometry']; // Encoded polyline points
    } else {
        error_log("LocationIQ Directions API error: " . ($data['message'] ?? 'Unknown error'));
        return null;
    }
}

// Function to generate the LocationIQ Static Maps API URL
function generateStaticMapUrl($polylinePoints, $destination_lat, $destination_lon, $apiKey) {
    $staticMapUrl = "https://staticmap.locationiq.com/v1/staticmap?key={$apiKey}&size=600x400";
    $staticMapUrl .= "&markers=icon:small-red-cutout|{$destination_lat},{$destination_lon}"; // Destination marker

    if ($polylinePoints) {
        $staticMapUrl .= "&path=color:0xff0000ff|weight:4|enc:{$polylinePoints}"; // Route path
    }

    return $staticMapUrl;
}

// Get the API key
$apiKey = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a';
if ($apiKey === false) {
    error_log("The LOCATIONIQ_API_KEY is Not Set");
    echo json_encode(['error' => "The LOCATIONIQ_API_KEY is Not Set"]);
    exit();
}

// Main Logic
if (isset($_GET['origin_lat']) && is_numeric($_GET['origin_lat']) && 
    isset($_GET['origin_lon']) && is_numeric($_GET['origin_lon']) && 
    isset($_GET['destination_lat']) && is_numeric($_GET['destination_lat']) && 
    isset($_GET['destination_lon']) && is_numeric($_GET['destination_lon'])) {

    $origin_lat = floatval($_GET['origin_lat']);
    $origin_lon = floatval($_GET['origin_lon']);
    $destination_lat = floatval($_GET['destination_lat']);
    $destination_lon = floatval($_GET['destination_lon']);

    // Call the Directions API
    $polylinePoints = getDirections($origin_lat, $origin_lon, $destination_lat, $destination_lon, $apiKey);

    if ($polylinePoints) {
        // Generate the static map URL with the route
        $imageUrl = generateStaticMapUrl($polylinePoints, $destination_lat, $destination_lon, $apiKey);
        echo json_encode(['imageUrl' => $imageUrl]);
    } else {
        echo json_encode(['error' => 'Could not get driving directions.']);
    }
} else {
    echo json_encode(['error' => 'Invalid origin or destination coordinates.']);
}
?>