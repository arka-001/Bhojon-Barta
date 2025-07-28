<?php
include("../connection/connect.php");
session_start();

// Error logging function
function log_error($message) {
    error_log(date("Y-m-d H:i:s") . " - " . $message . "\n", 3, "../logs/error.log"); // Log to a file
}

if (empty($_SESSION["adm_id"])) {
    header('location: index.php');
    exit;
}

if (isset($_GET['db_id']) && is_numeric($_GET['db_id'])) {
    $db_id = $_GET['db_id'];

    // Sanitize the input
    $db_id = mysqli_real_escape_string($db, $db_id);

    // **1. Debugging: Log the db_id we are trying to delete.**
    log_error("Attempting to delete delivery boy with db_id: " . $db_id);


    // **2. Reassign Orders (or set to NULL).  Crucially, check if there ARE orders to reassign!**
    // Check if there are any orders assigned to this delivery boy.
    $check_sql = "SELECT COUNT(*) FROM users_orders WHERE delivery_boy_id = '$db_id'";
    $check_result = mysqli_query($db, $check_sql);
    if (!$check_result) {
        log_error("Failed to check for orders: " . mysqli_error($db));
        $_SESSION['error_message'] = "Failed to check for existing orders. See error log.";
        header("Location: all_delivery_boys.php");
        exit;
    }

    $order_count = mysqli_fetch_row($check_result)[0];  // Get the count from the result

    if ($order_count > 0) { //Only try to reassign if there ARE orders.

          // Reassign to delivery_boy with db_id = 1 (or NULL).  CHANGE THIS.
          $reassign_sql = "UPDATE users_orders SET delivery_boy_id = 1 WHERE delivery_boy_id = '$db_id'"; // Change 1 to an existing delivery boy if needed.
          $reassign_result = mysqli_query($db, $reassign_sql);


          if (!$reassign_result) {
              log_error("Failed to reassign orders: " . mysqli_error($db));
              $_SESSION['error_message'] = "Failed to reassign orders. See error log.";
              header("Location: all_delivery_boys.php");
              exit;
          }  else {
              log_error("Successfully reassigned " . mysqli_affected_rows($db) . " orders from delivery boy " . $db_id . " to delivery boy 1.");
          }

    } else {
        log_error("No orders found assigned to delivery boy " . $db_id . ".  Proceeding with deletion.");
    }

    // **3. Now, delete the delivery boy**
    $sql = "DELETE FROM delivery_boy WHERE db_id = '$db_id'";
    $result = mysqli_query($db, $sql);

    if ($result) {
        // Deletion successful
        log_error("Successfully deleted delivery boy with db_id: " . $db_id);
        $_SESSION['success_message'] = "Delivery boy deleted successfully!";  //set a success message
       header("Location: all_delivery_boys.php"); // Redirect back to the list
       exit;
    } else {
        // Deletion failed
        log_error("Failed to delete delivery boy with db_id " . $db_id . ": " . mysqli_error($db));
        $_SESSION['error_message'] = "Failed to delete delivery boy: " . mysqli_error($db); //set a error message
         header("Location: all_delivery_boys.php"); // Redirect back to the list
        exit;
    }
} else {
    // Invalid or missing db_id
     $_SESSION['error_message'] = "Invalid request.";  //set a error message
    header("Location: all_delivery_boys.php"); // Redirect back to the list
    exit;
}
?>