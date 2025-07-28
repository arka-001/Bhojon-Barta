<?php
include("connection/connect.php"); // Make sure this path is correct
session_start();

$cart_count = 0;

if (isset($_SESSION["user_id"])) {
    $u_id = $_SESSION["user_id"];
    // Prepare a statement to prevent SQL injection
    $stmt = $db->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE u_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $u_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cart_count = (int)($row['total_items'] ?? 0); // Get total quantity, default to 0 if null
        }
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode(['count' => $cart_count]);
?>