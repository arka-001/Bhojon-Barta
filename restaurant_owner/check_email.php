<?php
header('Content-Type: application/json');
include("../connection/connect.php");

if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit();
}

$email = mysqli_real_escape_string($db, trim($_POST['email']));

$sql = "SELECT email FROM restaurant_owners WHERE email = ? UNION SELECT email FROM restaurant_owner_requests WHERE email = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email is already registered']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Email is available']);
}

mysqli_stmt_close($stmt);
mysqli_close($db);
?>