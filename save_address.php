<?php
session_start();
header('Content-Type: application/json');
include("connection/connect.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if (!isset($_POST['user_id']) || !isset($_POST['address']) || !isset($_POST['latitude']) || !isset($_POST['longitude']) || !isset($_POST['city'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$user_id = intval($_POST['user_id']);
$address = mysqli_real_escape_string($db, $_POST['address']);
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$city = mysqli_real_escape_string($db, $_POST['city']);

// Check if the user exists
$check_query = "SELECT COUNT(*) FROM users WHERE u_id = ?";
$stmt = $db->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count == 0) {
    echo json_encode(['status' => 'error', 'message' => 'User does not exist']);
    exit();
}

// Update the user's address, coordinates, and city
$update_query = "UPDATE users SET address = ?, latitude = ?, longitude = ?, city = ? WHERE u_id = ?";
$stmt = $db->prepare($update_query);
$stmt->bind_param("sddsi", $address, $latitude, $longitude, $city, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Address and city saved successfully']);
} else {
    error_log("Save Address Error: " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save address']);
}

$stmt->close();
$db->close();
?>