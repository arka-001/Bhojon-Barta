<?php
header('Content-Type: application/json');
include("../connection/connect.php");

$response = ['success' => false, 'message' => ''];

// Test mode for debugging
$test_mode = false; // Set to true to bypass LocationIQ
if ($test_mode) {
    $response['success'] = true;
    $response['data'] = [
        'lat' => '22.5726',
        'lon' => '88.3639',
        'display_name' => 'Test Address, Kolkata'
    ];
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address'])) {
    $query = trim($_POST['address']);
    if (empty($query)) {
        $response['message'] = 'Address is required.';
        echo json_encode($response);
        exit;
    }

    // LocationIQ API
    $api_key = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Replace with new key from https://locationiq.com
    $url = 'https://api.locationiq.com/v1/search?key=' . $api_key . '&q=' . urlencode($query) . '&format=json';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: onlinefoodphp2']);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error || $http_code != 200) {
        $response['message'] = 'Cannot connect to LocationIQ. Check API key at https://locationiq.com.';
    } elseif ($result) {
        $data = json_decode($result, true);
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $response['success'] = true;
            $response['data'] = [
                'lat' => $data[0]['lat'],
                'lon' => $data[0]['lon'],
                'display_name' => $data[0]['display_name']
            ];
        } else {
            $response['message'] = 'No results found for the address.';
        }
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>