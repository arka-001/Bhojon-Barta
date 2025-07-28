<?php
header('Content-Type: application/json');
include("../connection/connect.php");
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (empty($email)) {
        echo json_encode(['exists' => false, 'message' => 'Email is required']);
        exit;
    }

    // Check delivery_boy table
    $query = "SELECT COUNT(*) as count FROM delivery_boy WHERE db_email = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $exists = $row['count'] > 0;
    mysqli_stmt_close($stmt);

    // Check delivery_boy_requests table if not found in delivery_boy
    if (!$exists) {
        $query = "SELECT COUNT(*) as count FROM delivery_boy_requests WHERE db_email = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $exists = $row['count'] > 0;
        mysqli_stmt_close($stmt);
    }

    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['exists' => false, 'message' => 'Invalid request']);
}
mysqli_close($db);
?>