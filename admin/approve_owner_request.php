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

// --- Begin Transaction ---
mysqli_begin_transaction($db);

try {
    // 1. Fetch the request details (ensure it's still pending)
    $stmt_fetch = $db->prepare("SELECT email, password FROM restaurant_owner_requests WHERE request_id = ? AND status = 'pending'");
    if (!$stmt_fetch) {
        throw new Exception("Database prepare error (fetch): " . $db->error);
    }
    $stmt_fetch->bind_param("i", $request_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Request not found or already processed.");
    }

    $request_data = $result->fetch_assoc();
    $owner_email = $request_data['email'];
    $owner_hashed_password = $request_data['password']; // Get the *already hashed* password

    $stmt_fetch->close();


    // 2. Check if owner email already exists in restaurant_owners table
     $stmt_check_owner = $db->prepare("SELECT owner_id FROM restaurant_owners WHERE email = ?");
     if (!$stmt_check_owner) {
        throw new Exception("Database prepare error (check owner): " . $db->error);
     }
     $stmt_check_owner->bind_param("s", $owner_email);
     $stmt_check_owner->execute();
     $stmt_check_owner->store_result(); // Check num_rows

    if ($stmt_check_owner->num_rows > 0) {
         // Email already exists. Reject the request automatically? Or just show error?
         // For now, let's update the request to rejected to avoid conflicts.
         $stmt_reject = $db->prepare("UPDATE restaurant_owner_requests SET status = 'rejected', admin_comment = 'Rejected: Owner email already exists.' WHERE request_id = ?");
         if ($stmt_reject) {
            $stmt_reject->bind_param("i", $request_id);
            $stmt_reject->execute();
            $stmt_reject->close();
         }
         throw new Exception("Owner with email '{$owner_email}' already exists. Request automatically rejected.");
    }
     $stmt_check_owner->close();


    // 3. Insert into restaurant_owners table
    // IMPORTANT: Use the HASHED password directly from the request table
    $stmt_insert = $db->prepare("INSERT INTO restaurant_owners (email, password, created_at) VALUES (?, ?, NOW())");
    if (!$stmt_insert) {
        throw new Exception("Database prepare error (insert): " . $db->error);
    }
    $stmt_insert->bind_param("ss", $owner_email, $owner_hashed_password);

    if (!$stmt_insert->execute()) {
        // Check for specific duplicate entry error (though checked above, belt-and-suspenders)
        if ($db->errno == 1062) {
            throw new Exception("Failed to create owner: Email '{$owner_email}' already exists.");
        } else {
            throw new Exception("Failed to create owner account: " . $stmt_insert->error);
        }
    }
    $stmt_insert->close();


    // 4. Update the request status to 'approved'
    $stmt_update = $db->prepare("UPDATE restaurant_owner_requests SET status = 'approved', admin_comment = 'Approved by Admin ID: {$_SESSION['adm_id']}' WHERE request_id = ?");
     if (!$stmt_update) {
        throw new Exception("Database prepare error (update): " . $db->error);
    }
    $stmt_update->bind_param("i", $request_id);
    if (!$stmt_update->execute()) {
         throw new Exception("Failed to update request status: " . $stmt_update->error);
    }
    $stmt_update->close();

    // --- Commit Transaction ---
    mysqli_commit($db);
    $_SESSION['success_message'] = "Restaurant owner request approved successfully. Owner account created for {$owner_email}.";

} catch (Exception $e) {
    // --- Rollback Transaction on Error ---
    mysqli_rollback($db);
    $_SESSION['error_message'] = "Error approving request: " . $e->getMessage();
    // Log the detailed error for admin review
    error_log("Error approving owner request ID {$request_id}: " . $e->getMessage());
}

// --- Redirect back ---
header("Location: new_restaurant_owner_request.php");
mysqli_close($db);
exit;
?>