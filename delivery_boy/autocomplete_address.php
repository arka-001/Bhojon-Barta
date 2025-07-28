<?php
header('Content-Type: application/json');
include("../connection/connect.php");

$response = ['success' => false, 'message' => '', 'suggestions' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    if (empty($query)) {
        $response['message'] = 'Query is required.';
        echo json_encode($response);
        exit;
    }

    // LocationIQ Autocomplete API
    $api_key = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Replace with new key from https://locationiq.com
    $url = 'https://api.locationiq.com/v1/autocomplete?key=' . $api_key . '&q=' . urlencode($query) . '&format=json&limit=5';

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
        if (!empty($data)) {
            $suggestions = [];
            foreach ($data as $item) {
                $suggestions[] = [
                    'display_name' => $item['display_name'],
                    'city' => isset($item['address']['city']) ? $item['address']['city'] : (isset($item['address']['town']) ? $item['address']['town'] : ''),
                    'lat' => $item['lat'],
                    'lon' => $item['lon']
                ];
            }
            $response['success'] = true;
            $response['suggestions'] = $suggestions;
        } else {
            $response['message'] = 'No suggestions found.';
        }
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>