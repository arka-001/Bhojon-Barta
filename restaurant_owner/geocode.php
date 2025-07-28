<?php
header('Content-Type: application/json');

$debug_file = __DIR__ . '/debug.log';
function debug_log($message) {
    global $debug_file;
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}
debug_log("geocode.php started");

if (!isset($_POST['address']) || empty(trim($_POST['address']))) {
    debug_log("Error: Address is required");
    echo json_encode(['status' => 'error', 'message' => 'Address is required']);
    exit();
}

$apiKey = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Your LocationIQ API key
$address = urlencode($_POST['address']);
$url = "https://us1.locationiq.com/v1/search.php?key={$apiKey}&q={$address}&format=json";
debug_log("API URL: $url");

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0\r\n"
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'cafile' => 'C:\xampp\php\extras\ssl\cacert.pem'
    ]
]);

$response = @file_get_contents($url, false, $context);
debug_log("API Response: " . ($response === false ? 'Failed' : substr($response, 0, 200)));
if ($response === false) {
    debug_log("Error: Unable to connect to geocoding service");
    echo json_encode(['status' => 'error', 'message' => 'Unable to connect to geocoding service']);
    exit();
}

$data = json_decode($response, true);
if (empty($data)) {
    debug_log("Error: No results found");
    echo json_encode(['status' => 'error', 'message' => 'No results found']);
    exit();
}

$latitude = $data[0]['lat'] ?? null;
$longitude = $data[0]['lon'] ?? null;
$city = $data[0]['address']['city'] ?? $data[0]['address']['town'] ?? $data[0]['address']['village'] ?? 'Unknown';
debug_log("Result: city=$city, lat=$latitude, lon=$longitude");

echo json_encode([
    'status' => 'success',
    'latitude' => $latitude,
    'longitude' => $longitude,
    'city' => $city
]);
?>