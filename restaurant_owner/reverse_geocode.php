<?php
header('Content-Type: application/json');

$debug_file = __DIR__ . '/debug.log';
function debug_log($message) {
    global $debug_file;
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}
debug_log("reverse_geocode.php started");

if (!isset($_POST['lat']) || !isset($_POST['lon']) || empty(trim($_POST['lat'])) || empty(trim($_POST['lon']))) {
    debug_log("Error: Latitude and longitude are required");
    echo json_encode(['status' => 'error', 'message' => 'Latitude and longitude are required']);
    exit();
}

$apiKey = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Your LocationIQ API key
$lat = floatval($_POST['lat']);
$lon = floatval($_POST['lon']);
$endpoints = [
    "https://eu1.locationiq.com/v1/reverse.php?key={$apiKey}&lat={$lat}&lon={$lon}&format=json",
    "https://us1.locationiq.com/v1/reverse.php?key={$apiKey}&lat={$lat}&lon={$lon}&format=json"
];

$response = null;
$curl_error = '';
foreach ($endpoints as $url) {
    debug_log("Trying API URL: $url");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CAINFO, 'C:\xampp\php\extras\ssl\cacert.pem');
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        $curl_error = curl_error($ch);
        debug_log("CURL Error: $curl_error, HTTP Code: $http_code");
    } else {
        debug_log("API Response (HTTP $http_code): " . substr($response, 0, 200));
        if ($http_code == 200) {
            break; // Success, exit loop
        }
    }
    curl_close($ch);
}

if ($response === false || $http_code != 200) {
    debug_log("Error: Unable to connect to geocoding service ($curl_error)");
    echo json_encode(['status' => 'error', 'message' => 'Unable to connect to geocoding service: ' . $curl_error]);
    exit();
}

$data = json_decode($response, true);
if (empty($data)) {
    debug_log("Error: No results found");
    echo json_encode(['status' => 'error', 'message' => 'No results found']);
    exit();
}

$city = $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? 'Unknown';
debug_log("Reverse geocode result: city=$city, lat=$lat, lon=$lon");

echo json_encode([
    'status' => 'success',
    'city' => $city
]);
?>