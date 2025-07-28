<?php
include("../connection/connect.php");
header('Content-Type: application/json');

$debug_file = __DIR__ . '/debug.log';
function debug_log($message) {
    global $debug_file;
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}
debug_log("check_delivery_city.php started");

if (!isset($_POST['city']) || empty(trim($_POST['city']))) {
    echo json_encode(['status' => 'error', 'message' => 'City is required']);
    exit();
}

$city = mysqli_real_escape_string($db, trim($_POST['city']));
debug_log("Checking city: $city");

$sql = "SELECT city FROM delivery_cities WHERE LOWER(city) = LOWER(?)";
$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    debug_log("Prepare failed: " . mysqli_error($db));
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $city);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    debug_log("City found: $city");
    echo json_encode(['status' => 'success', 'message' => 'City is serviceable']);
} else {
    debug_log("City not found: $city");
    echo json_encode(['status' => 'error', 'message' => 'City is not serviceable']);
}

mysqli_stmt_close($stmt);
mysqli_close($db);
?>