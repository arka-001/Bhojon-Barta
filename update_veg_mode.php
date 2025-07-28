<?php
session_start();
include("connection/connect.php"); // Adjust path if needed
header('Content-Type: application/json'); // Set header for JSON response

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// 1. Check if user is logged in
if (empty($_SESSION["user_id"])) {
    $response['message'] = 'Authentication required. Please log in.';
    echo json_encode($response);
    exit;
}

// 2. Check if data is received via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['new_state'])) {

    $session_user_id = intval($_SESSION["user_id"]);
    $posted_user_id = intval($_POST['user_id']);
    $new_state = $_POST['new_state']; // Keep as string for validation check first

    // 3. Verify the posted user ID matches the session user ID
    if ($session_user_id !== $posted_user_id) {
        $response['message'] = 'User ID mismatch. Action denied.';
        echo json_encode($response);
        exit;
    }

    // 4. Validate the new state (must be 0 or 1)
    if ($new_state !== '0' && $new_state !== '1') {
        $response['message'] = 'Invalid state value provided.';
        echo json_encode($response);
        exit;
    }

    $new_state_int = intval($new_state); // Convert to integer for DB

    // 5. Update the database using prepared statements
    $stmt = $db->prepare("UPDATE users SET is_veg_mode = ? WHERE u_id = ?");

    if ($stmt) {
        // Bind parameters: "i" means integer type
        $stmt->bind_param("ii", $new_state_int, $session_user_id);

        if ($stmt->execute()) {
            // Check if any row was actually updated
            if ($stmt->affected_rows >= 0) { // Use >= 0 because updating to the same value is not an error
                $response['status'] = 'success';
                $response['message'] = ($new_state_int == 1) ? 'Veg mode enabled.' : 'Veg mode disabled.';
            } else {
                 // This case might indicate the user ID wasn't found, though the session check should prevent this.
                $response['message'] = 'Could not find user to update.';
            }
        } else {
            // Execution error
            $response['message'] = 'Database update failed: ' . $stmt->error;
            error_log("Database update failed for veg_mode: " . $stmt->error); // Log error server-side
        }
        $stmt->close(); // Close the statement
    } else {
        // Prepare statement error
        $response['message'] = 'Database prepare failed: ' . $db->error;
        error_log("Database prepare failed for veg_mode: " . $db->error); // Log error server-side
    }

} else {
    // Data not received correctly
    $response['message'] = 'Invalid request data.';
}

// Send the JSON response back to the JavaScript
echo json_encode($response);
exit;
?>