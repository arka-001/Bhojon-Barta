<?php
include("connection/connect.php");
error_reporting(0);
session_start();

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    $stmt = $db->prepare("SELECT city FROM users WHERE u_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['city'])) {
            $response = [
                'status' => 'success',
                'city' => $row['city']
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No city found for user'
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'User not found'
        ];
    }
    $stmt->close();
} else {
    $response = [
        'status' => 'error',
        'message' => 'User ID not provided'
    ];
}

echo json_encode($response);
?>