<?php
include("connection/connect.php");
header('Content-Type: application/json');

$city = filter_input(INPUT_GET, 'city', FILTER_SANITIZE_STRING);

if (!$city) {
    echo json_encode(['serviceable' => false, 'error' => 'City is required']);
    exit;
}

$sql = "SELECT city_name FROM delivery_cities WHERE city_name = ? AND is_active = 1";
$stmt = mysqli_prepare($db, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $city);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    echo json_encode(['serviceable' => $row !== null]);
} else {
    echo json_encode(['serviceable' => false, 'error' => 'Database error']);
}
?>