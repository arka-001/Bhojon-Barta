<?php
include("connection/connect.php"); // Your database connection
session_start(); // Start the session

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "error:User not logged in.";
        exit;
    }

    $order_id = $_POST['order_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    $delivery_boy_id = $_POST['delivery_boy_id'];

    // Sanitize and validate inputs
    $order_id = mysqli_real_escape_string($db, $order_id);
    $rating = intval($rating); // Ensure it's an integer
    $review = mysqli_real_escape_string($db, $review);
    $delivery_boy_id = intval($delivery_boy_id); //Ensure Integer

    // Basic validation
    if ($rating < 1 || $rating > 5) {
        echo "error:Invalid rating.";
        exit;
    }

    // Check if delivery_boy_id is empty
    if (empty($delivery_boy_id)) {
        echo "error:Delivery boy ID is missing.";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Check if user has already rated the delivery boy for this order
    $check_sql = "SELECT * FROM delivery_boy_ratings WHERE order_id = '$order_id' AND user_id = '$user_id'";
    $check_result = mysqli_query($db, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        echo "error:You have already rated this rider for this order.";
        exit;
    }

    // Insert the rating into the delivery_boy_ratings table
    $sql = "INSERT INTO delivery_boy_ratings (delivery_boy_id, user_id, order_id, rating, review)
            VALUES ('$delivery_boy_id', '$user_id', '$order_id', '$rating', '$review')";

    if (mysqli_query($db, $sql)) {
        echo "success";
    } else {
        echo "error:" . mysqli_error($db);
    }

} else {
    echo "error:Invalid request method."; // Not a POST request
}
?>