<?php
// DB credentials. Replace with your actual database details.
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');     // Usually 'localhost' for local development
if (!defined('DB_USER')) define('DB_USER', 'root');          // <<<--- YOUR DB USERNAME
if (!defined('DB_PASS')) define('DB_PASS', '');              // <<<--- YOUR DB PASSWORD
if (!defined('DB_NAME')) define('DB_NAME', 'onlinefoodphp2'); // <<<--- YOUR DATABASE NAME

// Establish connection using MySQLi
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (mysqli_connect_errno()) {
    $error_message = "Database connection failed: " . mysqli_connect_error();
    error_log($error_message); // Log to PHP error log

    $calling_script = basename($_SERVER['PHP_SELF']);
    // Only try to output JSON if called by the specific API script
    if ($calling_script == 'chatbot_enhanced_php.php' || $calling_script == 'chatbot_only_php.php') {
         if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500); // Internal Server Error
         }
         // Send valid JSON even on error
         echo json_encode(['reply' => 'Error: Cannot connect to the database service. Please contact support.']);
         exit; // Stop script execution
    } else {
        die("Database connection failed."); // Fallback for other includes
    }
}

// Set charset to utf8mb4 (recommended)
if (!mysqli_set_charset($db, "utf8mb4")) {
     error_log("Error loading character set utf8mb4: " . mysqli_error($db));
}

// $db variable is now available
?>