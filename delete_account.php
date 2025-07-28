<?php
/*
  Simplified Hard Account Deletion (delete_account.php)
  - Verifies PLAIN TEXT password.
  - Copies user info to deleted_users_log.
  - DELETES user from 'users' table.
  - Logs out user.
  - SMTP settings are HARDCODED in this file.
  - WARNING: PLAIN TEXT PASSWORD CHECK IS A SECURITY RISK.
*/

session_start();
require_once("connection/connect.php");

// PHPMailer (adjust paths if not using Composer or PHPMailer is elsewhere)
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
error_log("--- Hard Delete (delete_account.php - PLAIN TEXT PW - NO DOTENV) script started ---");

// --- SMTP Configuration (HARDCODED) ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'bhojonbarta@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'zyysvopsvyuazetu');   // Your Gmail App Password
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'bhojonbarta@gmail.com');
define('SMTP_FROM_NAME', 'Bhojon Barta');
// --- End SMTP Configuration ---

// --- Database Connection Check ---
if (!$db) {
    error_log("FATAL: \$db object not available.");
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Critical server error (DB init).']); exit();
}
if (mysqli_connect_errno()) {
    error_log("FATAL: Database connection error - " . mysqli_connect_error());
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Cannot connect to database.']); exit();
}
error_log("Database connection OK.");

// --- CSRF Token Verification ---
$csrf_token_from_post = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
if (empty($csrf_token_from_post) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token_from_post)) {
    error_log("CSRF token mismatch. Session: " . ($_SESSION['csrf_token'] ?? 'NS') . " Post: " . ($csrf_token_from_post ?? 'NS'));
    http_response_code(403); echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Refresh & try again.']); exit();
}
error_log("CSRF token OK.");

// --- User Authentication & Input Validation ---
if (empty($_SESSION["user_id"])) {
    error_log("User not logged in.");
    http_response_code(401); echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Please log in.']); exit();
}
$user_id = (int)$_SESSION["user_id"];
$password_attempt = filter_input(INPUT_POST, 'password'); // Raw password attempt

if (empty($password_attempt)) {
    error_log("Password input empty for user ID: " . $user_id);
    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Password is required.']); exit();
}
error_log("Password received for HARD deletion attempt by user ID: " . $user_id);

// --- Rate Limiting ---
$max_attempts = 5; $attempt_period = 3600;
if (!isset($_SESSION['deletion_attempts_count'])) { $_SESSION['deletion_attempts_count'] = 0; $_SESSION['deletion_first_attempt_time'] = time(); }
if ((time() - $_SESSION['deletion_first_attempt_time']) > $attempt_period) { $_SESSION['deletion_attempts_count'] = 0; $_SESSION['deletion_first_attempt_time'] = time(); }
if ($_SESSION['deletion_attempts_count'] >= $max_attempts) {
    error_log("Too many deletion attempts for user ID: " . $user_id);
    http_response_code(429); echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again later.']); exit();
}
$_SESSION['deletion_attempts_count']++;

// --- Fetch User Data ---
$sql_fetch_user = "SELECT u_id, username, password, email, phone, address, city FROM users WHERE u_id = ?";
$stmt_fetch_user = mysqli_prepare($db, $sql_fetch_user);
if (!$stmt_fetch_user) {
    error_log("Prepare failed (fetch user for hard delete): " . mysqli_error($db));
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Database error (SFU).']); exit();
}
mysqli_stmt_bind_param($stmt_fetch_user, "i", $user_id);
mysqli_stmt_execute($stmt_fetch_user);
$result_user = mysqli_stmt_get_result($stmt_fetch_user);
$user_data_to_delete = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt_fetch_user);

if (!$user_data_to_delete) {
    error_log("User not found for ID: " . $user_id . " during hard delete.");
    http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'User not found.']); exit();
}

// --- Verify Password (PLAIN TEXT COMPARISON - NOT RECOMMENDED FOR PRODUCTION) ---
if ($password_attempt !== $user_data_to_delete['password']) { // Direct string comparison
    // For debugging, you might log the attempted vs stored, but be careful with logging plain passwords
    error_log("Incorrect password (plain text check) for user ID: " . $user_id . ". Attempted: '" . $password_attempt . "' vs Stored: '" . $user_data_to_delete['password'] . "'");
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
    exit();
}
$_SESSION['deletion_attempts_count'] = 0; // Reset attempts on successful password
error_log("Password verified (plain text check) for hard delete of user ID: " . $user_id);


// --- Proceed with Hard Deletion ---
mysqli_begin_transaction($db);
try {
    // 1. Log user's details to deleted_users_log
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $sql_log_deleted_user = "INSERT INTO deleted_users_log
                                (original_user_id, username, email, phone, address, city, ip_address, user_agent, deletion_timestamp)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_log = mysqli_prepare($db, $sql_log_deleted_user);
    if (!$stmt_log) throw new Exception("Prepare log deleted user failed: " . mysqli_error($db));
    mysqli_stmt_bind_param($stmt_log, "isssssss",
        $user_data_to_delete['u_id'], $user_data_to_delete['username'], $user_data_to_delete['email'],
        $user_data_to_delete['phone'], $user_data_to_delete['address'], $user_data_to_delete['city'],
        $ip_address, $user_agent
    );
    if (!mysqli_stmt_execute($stmt_log)) throw new Exception("Logging deleted user failed: " . mysqli_stmt_error($stmt_log));
    mysqli_stmt_close($stmt_log);
    error_log("User details logged for original_user_id: " . $user_id);

    // 2. Delete related data
    $related_tables_to_delete_from = [
        'cart' => 'u_id', 'user_favorite_dishes' => 'u_id', 'user_favorite_restaurants' => 'u_id',
        'order_ratings' => 'u_id', 'restaurant_ratings' => 'u_id', 'delivery_boy_ratings' => 'u_id',
        'order_messages' => 'u_id', 'account_deletion_log' => 'user_id'
    ];
    foreach ($related_tables_to_delete_from as $table => $column) {
        $sql_delete_related = "DELETE FROM `$table` WHERE `$column` = ?";
        $stmt_delete_related = mysqli_prepare($db, $sql_delete_related);
        if (!$stmt_delete_related) throw new Exception("Prepare delete from `$table` failed: " . mysqli_error($db));
        mysqli_stmt_bind_param($stmt_delete_related, "i", $user_id);
        if (!mysqli_stmt_execute($stmt_delete_related)) throw new Exception("Delete from `$table` failed: " . mysqli_stmt_error($stmt_delete_related));
        mysqli_stmt_close($stmt_delete_related);
        error_log("Deleted from `$table` for user_id: " . $user_id);
    }
    $sql_delete_user_orders = "DELETE FROM `users_orders` WHERE `u_id` = ?";
    $stmt_delete_user_orders = mysqli_prepare($db, $sql_delete_user_orders);
    if (!$stmt_delete_user_orders) throw new Exception("Prepare delete `users_orders` failed: " . mysqli_error($db));
    mysqli_stmt_bind_param($stmt_delete_user_orders, "i", $user_id);
    if (!mysqli_stmt_execute($stmt_delete_user_orders)) throw new Exception("Delete `users_orders` failed: " . mysqli_stmt_error($stmt_delete_user_orders));
    mysqli_stmt_close($stmt_delete_user_orders);
    error_log("Deleted from `users_orders` for user_id: " . $user_id);

    // 3. Delete user from 'users' table
    $sql_delete_user = "DELETE FROM users WHERE u_id = ?";
    $stmt_delete_user = mysqli_prepare($db, $sql_delete_user);
    if (!$stmt_delete_user) throw new Exception("Prepare delete user failed: " . mysqli_error($db));
    mysqli_stmt_bind_param($stmt_delete_user, "i", $user_id);
    if (!mysqli_stmt_execute($stmt_delete_user)) throw new Exception("Deleting user failed: " . mysqli_stmt_error($stmt_delete_user));
    mysqli_stmt_close($stmt_delete_user);
    error_log("User record deleted from users table for user ID: " . $user_id);

    mysqli_commit($db);

    // --- Send Deletion Confirmation Email ---
    $original_email = $user_data_to_delete['email'];
    $original_username = $user_data_to_delete['username'];
    if (!empty($original_email)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST; $mail->SMTPAuth   = true; $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD; $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($original_email, $original_username);
            $mail->isHTML(true); $mail->Subject = 'Bhojon Barta Account Deletion Confirmation';
            $mail->Body    = "Dear " . htmlspecialchars($original_username) . ",<br><br>Your Bhojon Barta account (" . htmlspecialchars($original_email) . ") has been permanently deleted.<br>This action is irreversible.<br><br>Best regards,<br>The Bhojon Barta Team";
            $mail->send();
            error_log("Hard deletion email sent to: " . $original_email);
        } catch (Exception $e) {
            error_log("PHPMailer (Hard Deletion Email) Error for " . $original_email . ": " . $mail->ErrorInfo);
        }
    }

    // --- Clear Session & Log Out ---
    session_unset(); session_destroy();
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    echo json_encode(['status' => 'success', 'message' => 'Account permanently deleted. You will be logged out.']);
    exit();

} catch (Exception $e) {
    mysqli_rollback($db);
    error_log("Account hard deletion transaction FAILED for user ID $user_id: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete account due to a server error.']);
    exit();
}
?>