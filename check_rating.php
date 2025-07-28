<?php
include("connection/connect.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = mysqli_real_escape_string($db, $_POST['order_id']);
    $user_id = mysqli_real_escape_string($db, $_POST['user_id']);

    $query = "SELECT * FROM restaurant_ratings WHERE rs_id IN (SELECT rs_id FROM users_orders WHERE o_id = '$order_id') AND u_id = '$user_id'";
    $result = mysqli_query($db, $query);

    echo mysqli_num_rows($result) > 0 ? 'rated' : 'not_rated';
}
?>