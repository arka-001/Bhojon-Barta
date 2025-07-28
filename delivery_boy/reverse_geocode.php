<?php
header('Content-Type: application/json');
include("../connection/connect.php");

$response = ['success' => false, 'message' => '', 'city' => ''];

// Test mode for debugging
$test_mode = false;
if ($test_mode) {
    $response['success'] = true;
    $response['city'] = 'Kolkata';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lat']) && isset($_POST['lon'])) {
    $lat = trim($_POST['lat']);
    $lon = trim($_POST['lon']);
    if (empty($lat) || empty($lon) || !is_numeric($lat) || !is_numeric($lon)) {
        $response['message'] = 'Valid latitude and longitude are required.';
        echo json_encode($response);
        exit;
    }

    // LocationIQ Reverse Geocoding API
    $api_key = ''; // Replace with new key from https://locationiq.com
    $url = 'https://api.locationiq.com/v1/reverse?key=' . $api_key . '&lat=' . urlencode($lat) . '&lon=' . urlencode($lon) . '&format=json';

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
        if (!empty($data) && isset($data['address'])) {
            $city = isset($data['address']['city']) ? $data['address']['city'] :
                    (isset($data['address']['town']) ? $data['address']['town'] :
                    (isset($data['address']['village']) ? $data['address']['village'] : ''));
            if ($city) {
                $response['success'] = true;
                $response['city'] = $city;
            } else {
                $response['message'] = 'City not found in address data.';
            }
        } else {
            $response['message'] = 'No address data found for coordinates.';
        }
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>