<?php
include("connection/connect.php"); // Your database connection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "error:User not logged in.";
        exit;
    }

    // Retrieve POST data
    $order_id = $_POST['order_id'];
    $rating = $_POST['rating']; // Order rating (optional in this context)
    $review = $_POST['review']; // Order review (optional in this context)
    $delivery_boy_id = $_POST['delivery_boy_id'];
    $delivery_boy_rating = $_POST['delivery_boy_rating'];
    $restaurant_id = $_POST['restaurant_id'];
    $restaurant_rating = $_POST['restaurant_rating'];
    $restaurant_review = $_POST['restaurant_review'];

    // Sanitize inputs
    $order_id = mysqli_real_escape_string($db, $order_id);
    $rating = intval($rating); // Order rating (0 if not provided)
    $review = mysqli_real_escape_string($db, $review);
    $delivery_boy_id = intval($delivery_boy_id);
    $delivery_boy_rating = intval($delivery_boy_rating);
    $restaurant_id = intval($restaurant_id);
    $restaurant_rating = intval($restaurant_rating);
    $restaurant_review = mysqli_real_escape_string($db, $restaurant_review);

    $user_id = $_SESSION['user_id'];

    // Basic validation
    if ($restaurant_rating < 1 || $restaurant_rating > 5) {
        echo "error:Invalid restaurant rating.";
        exit;
    }

    if ($delivery_boy_id > 0 && ($delivery_boy_rating < 1 || $delivery_boy_rating > 5)) {
        echo "error:Invalid delivery boy rating.";
        exit;
    }

    if ($rating > 0 && ($rating < 1 || $rating > 5)) {
        echo "error:Invalid order rating.";
        exit;
    }

    // Start transaction to ensure all ratings are submitted successfully
    mysqli_begin_transaction($db);

    try {
        // 1. Insert order rating (if provided)
        if ($rating > 0) {
            $check_order_sql = "SELECT * FROM order_ratings WHERE o_id = '$order_id' AND u_id = '$user_id'";
            $check_order_result = mysqli_query($db, $check_order_sql);
            if (mysqli_num_rows($check_order_result) > 0) {
                throw new Exception("You have already rated this order.");
            }

            $order_sql = "INSERT INTO order_ratings (o_id, u_id, rating, review, created_at) 
                          VALUES ('$order_id', '$user_id', '$rating', '$review', NOW())";
            if (!mysqli_query($db, $order_sql)) {
                throw new Exception("Error submitting order rating: " . mysqli_error($db));
            }
        }

        // 2. Insert restaurant rating (mandatory)
        $check_restaurant_sql = "SELECT * FROM restaurant_ratings WHERE rs_id = '$restaurant_id' AND u_id = '$user_id' AND rating_date >= (SELECT date FROM users_orders WHERE o_id = '$order_id')";
        $check_restaurant_result = mysqli_query($db, $check_restaurant_sql);
        if (mysqli_num_rows($check_restaurant_result) > 0) {
            throw new Exception("You have already rated this restaurant for this order.");
        }

        $restaurant_sql = "INSERT INTO restaurant_ratings (rs_id, u_id, rating, review, rating_date) 
                           VALUES ('$restaurant_id', '$user_id', '$restaurant_rating', '$restaurant_review', NOW())";
        if (!mysqli_query($db, $restaurant_sql)) {
            throw new Exception("Error submitting restaurant rating: " . mysqli_error($db));
        }

        // 3. Insert delivery boy rating (if applicable)
        if ($delivery_boy_id > 0 && $delivery_boy_rating > 0) {
            $check_delivery_sql = "SELECT * FROM delivery_boy_ratings WHERE order_id = '$order_id' AND u_id = '$user_id'";
            $check_delivery_result = mysqli_query($db, $check_delivery_sql);
            if (mysqli_num_rows($check_delivery_result) > 0) {
                throw new Exception("You have already rated this delivery boy for this order.");
            }

            $delivery_boy_sql = "INSERT INTO delivery_boy_ratings (db_id, u_id, order_id, rating, review, rating_date) 
                                 VALUES ('$delivery_boy_id', '$user_id', '$order_id', '$delivery_boy_rating', '$review', NOW())";
            if (!mysqli_query($db, $delivery_boy_sql)) {
                throw new Exception("Error submitting delivery boy rating: " . mysqli_error($db));
            }
        }

        // If all queries succeed, commit the transaction
        mysqli_commit($db);
        echo "success";

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($db);
        echo "error:" . $e->getMessage();
    }

} else {
    echo "error:Invalid request method.";
}
?>