<?php
include("../connection/connect.php");
error_reporting(E_ALL); // Enable for debugging, set to 0 in production
ini_set('display_errors', 1); // Set to 0 in production
ini_set('log_errors', 1);
session_start();

// Check if admin is logged in
if (!isset($_SESSION["adm_id"])) {
    error_log("delete_restaurant.php: Unauthorized access attempt");
    header("Location: index.php");
    exit();
}

// Validate res_del parameter
if (!isset($_GET['res_del']) || !is_numeric($_GET['res_del'])) {
    error_log("delete_restaurant.php: Invalid or missing restaurant ID");
    $_SESSION['delete_error'] = "Invalid restaurant ID.";
    header("Location: all_restaurant.php");
    exit();
}

$rs_id = (int)$_GET['res_del'];
$db = $GLOBALS['db'];

// Use prepared statement to delete restaurant
$sql = "DELETE FROM restaurant WHERE rs_id = ?";
$stmt = mysqli_prepare($db, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $rs_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['delete_success'] = "Restaurant deleted successfully.";
        error_log("delete_restaurant.php: Successfully deleted restaurant ID $rs_id");
    } else {
        $_SESSION['delete_error'] = "Failed to delete restaurant: " . mysqli_error($db);
        error_log("delete_restaurant.php: Failed to delete restaurant ID $rs_id: " . mysqli_error($db));
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['delete_error'] = "Database error: " . mysqli_error($db);
    error_log("delete_restaurant.php: Prepare statement failed: " . mysqli_error($db));
}

mysqli_close($db);
header("Location: all_restaurant.php");
exit();
?>