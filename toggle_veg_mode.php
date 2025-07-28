<?php
session_start();

// Check if the request is valid
if (isset($_POST['veg_mode'])) {
    // Set veg_mode session variable: 1 for on, 0 for off
    $_SESSION['veg_mode'] = ($_POST['veg_mode'] === 'on') ? 1 : 0;
    
    // Return success response
    echo json_encode(['status' => 'success', 'veg_mode' => $_SESSION['veg_mode']]);
} else {
    // Return error response
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>