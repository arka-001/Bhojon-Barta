```php
<?php
session_start();

// Debug: Log script execution
error_log("Attempting to load callback.php at " . __FILE__);

// Check if file is accessible
if (!file_exists(__FILE__)) {
    error_log("callback.php file not found at " . __FILE__);
    die("Server error: callback.php not found.");
}

require 'vendor/autoload.php';
include("connection/connect.php");

// Debug: Check dependencies
if (!file_exists('vendor/autoload.php')) {
    error_log("vendor/autoload.php not found in " . realpath('vendor/autoload.php'));
    die("Server error: Missing vendor/autoload.php");
}
if (!file_exists('connection/connect.php')) {
    error_log("connection/connect.php not found in " . realpath('connection/connect.php'));
    die("Server error: Missing connection/connect.php");
}

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

error_log("Callback accessed with code: " . ($_GET['code'] ?? 'No code'));

$error_message = "";

$client = new Google_Client();
$client->setClientId('1068721017091-58fq679teevpt5aouhqffsnegvhdar01.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/OnlineFood/OnlineFood-PHP/callback.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            $error_message = "Error fetching access token: " . $token['error'];
            error_log("Token error: " . $error_message);
            header("Location: /OnlineFood/OnlineFood-PHP/login.php?error=" . urlencode($error_message));
            exit();
        }

        $client->setAccessToken($token);
        $google_service = new Google_Service_Oauth2($client);
        $user_info = $google_service->userinfo->get();

        $google_id = $user_info->id;
        $email = $user_info->email;
        $name = $user_info->name;
        $picture = $user_info->picture;

        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        // Check if user exists
        $sql = "SELECT u_id, google_id, email FROM users WHERE google_id = ? OR email = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $google_id, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Existing user: Set session and redirect to login.php
            $_SESSION['user_id'] = $user['u_id'];
            error_log("Existing user logged in: " . $user['u_id']);
            header("Location: /OnlineFood/OnlineFood-PHP/login.php");
            exit();
        } else {
            // New user: Register
            $username = strtolower(str_replace(' ', '', $first_name . $last_name));
            $check_username = mysqli_query($db, "SELECT username FROM users WHERE username = '" . mysqli_real_escape_string($db, $username) . "'");
            $counter = 1;
            $original_username = $username;
            while (mysqli_num_rows($check_username) > 0) {
                $username = $original_username . $counter;
                $check_username = mysqli_query($db, "SELECT username FROM users WHERE username = '" . mysqli_real_escape_string($db, $username) . "'");
                $counter++;
            }

            $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            $verification_token = bin2hex(random_bytes(32));
            $email_verified = 1; // Google accounts are verified
            $address = '';
            $phone = '';
            $status = 1;

            $sql = "INSERT INTO users (google_id, username, name, f_name, l_name, email, google_picture, password, address, phone, verification_token, email_verified, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, "sssssssssssis", $google_id, $username, $name, $first_name, $last_name, $email, $picture, $password, $address, $phone, $verification_token, $email_verified, $status);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['user_id'] = mysqli_insert_id($db);
                error_log("New user registered: " . $_SESSION['user_id']);
                header("Location: /OnlineFood/OnlineFood-PHP/login.php");
                exit();
            } else {
                $error_message = "Error registering user: " . mysqli_error($db);
                error_log("Registration error: " . $error_message);
                header("Location: /OnlineFood/OnlineFood-PHP/login.php?error=" . urlencode($error_message));
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        $error_message = "Google authentication failed: " . $e->getMessage();
        error_log("Authentication error: " . $error_message);
        header("Location: /OnlineFood/OnlineFood-PHP/login.php?error=" . urlencode($error_message));
        exit();
    }
} else {
    $error_message = "No authorization code received.";
    error_log("No code error: " . $error_message);
    header("Location: /OnlineFood/OnlineFood-PHP/login.php?error=" . urlencode($error_message));
    exit();
}
?>