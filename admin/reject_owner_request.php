<?php
include("../connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if admin is logged in
if(empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Check if request_id is provided and is numeric
if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
    $_SESSION['error_message'] = "Invalid request ID.";
    header("Location: new_restaurant_owner_request.php");
    exit;
}

$request_id = intval($_GET['request_id']); // Sanitize input

// Optional: Add functionality to get a rejection reason from the admin later

// Prepare update statement
$stmt = $db->prepare("UPDATE restaurant_owner_requests SET status = 'rejected', admin_comment = 'Rejected by Admin ID: {$_SESSION['adm_id']}' WHERE request_id = ? AND status = 'pending'");

if ($stmt) {
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        // Check if any row was actually affected
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Restaurant owner request rejected successfully.";
        } else {
             $_SESSION['error_message'] = "Request not found or already processed.";
        }
    } else {
        $_SESSION['error_message'] = "Database error: Failed to reject request. " . $stmt->error;
        error_log("Error rejecting owner request ID {$request_id}: " . $stmt->error);
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Database error: Could not prepare statement. " . $db->error;
     error_log("Database Prepare Error rejecting owner request ID {$request_id}: " . $db->error);
}

// Redirect back
header("Location: new_restaurant_owner_request.php");
mysqli_close($db);
exit;
?>