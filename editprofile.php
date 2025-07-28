<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';
// Include PHPMailer classes directly if not relying solely on Composer autoload
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

session_start();

include("connection/connect.php"); // Establishes $db connection
error_reporting(E_ALL);
ini_set('display_errors', 1); // Show errors for debugging, disable in production

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token_value_for_html = $_SESSION['csrf_token'];


// Redirect if user is not logged in
if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// --- Helper Functions ---

// Function to generate OTP
function generateOTP($length = 6) {
    return substr(str_shuffle('0123456789'), 0, $length);
}

// Function to send OTP via email using PHPMailer
function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bhojonbarta@gmail.com'; // Your SMTP username
        $mail->Password   = 'zyysvopsvyuazetu'; // Your SMTP password or App Password // USE YOUR ACTUAL APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('bhojonbarta@gmail.com', 'Bhojon Barta');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(false);
        $mail->Subject = 'Your Bhojon Barta One-Time Password';
        $mail->Body    = "Your One-Time Password (OTP) for password change is: $otp\n\n"
                       . "This OTP is valid for 15 minutes.\n\n"
                       . "If you did not request this, please ignore this email or contact support.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error (OTP Send): " . $mail->ErrorInfo);
        return false;
    }
}


// Function to hash OTP
function hashOTP($otp) {
    return password_hash($otp, PASSWORD_DEFAULT);
}

// Function to verify OTP
function verifyOTP($inputOTP, $hashedOTP) {
    return password_verify($inputOTP, $hashedOTP);
}

// Function to check if city is serviceable
function isServiceableCity($city, $db) {
    $city = trim($city ?? '');
    if (empty($city) || $city === 'Unknown') {
        return false;
    }
    $sql = "SELECT city_name FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $city);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $is_serviceable = (mysqli_num_rows($result) > 0);
        mysqli_stmt_close($stmt);
        return $is_serviceable;
    } else {
        error_log("Failed to prepare statement in isServiceableCity: " . mysqli_error($db));
        return false;
    }
}

// --- Initializations ---
$profile_success = $profile_error = $password_error = "";
$message = [];

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// --- Fetch Current User Data ---
$sql_user = "SELECT u_id, username, f_name, l_name, email, phone, address, latitude, longitude, city FROM users WHERE u_id = ?";
$stmt_user_fetch = mysqli_prepare($db, $sql_user);
$user = [];
if ($stmt_user_fetch) {
    mysqli_stmt_bind_param($stmt_user_fetch, "i", $user_id);
    mysqli_stmt_execute($stmt_user_fetch);
    $result_user = mysqli_stmt_get_result($stmt_user_fetch);
    $user = mysqli_fetch_assoc($result_user);
    if (!$user) {
        $profile_error = "Error: User data not found.";
        $user = [];
    }
    mysqli_stmt_close($stmt_user_fetch);
} else {
    $profile_error = "Database error: Unable to fetch user data. " . mysqli_error($db);
    $user = [];
}

// --- Handle POST Requests ---

if (isset($_POST['request_otp'])) {
    // (OTP request logic as you provided - no changes needed here for delete functionality)
    $email_sql = "SELECT email FROM users WHERE u_id = ?";
    $email_stmt = mysqli_prepare($db, $email_sql);
    if ($email_stmt) {
        mysqli_stmt_bind_param($email_stmt, "i", $user_id);
        mysqli_stmt_execute($email_stmt);
        $email_result = mysqli_stmt_get_result($email_stmt);
        $email_data = mysqli_fetch_assoc($email_result);
        mysqli_stmt_close($email_stmt);

        if ($email_data && !empty($email_data['email'])) {
            $email = $email_data['email'];
            $otp = generateOTP();
            $hashedOTP = hashOTP($otp);
            $expiry = time() + (15 * 60);

            $store_otp_sql = "UPDATE users SET otp = ?, otp_expiry = ? WHERE u_id = ?";
            $store_otp_stmt = mysqli_prepare($db, $store_otp_sql);
            if ($store_otp_stmt) {
                mysqli_stmt_bind_param($store_otp_stmt, "sii", $hashedOTP, $expiry, $user_id);
                if (mysqli_stmt_execute($store_otp_stmt)) {
                    if (sendOTP($email, $otp)) {
                        $_SESSION['otp_requested'] = true;
                        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'OTP sent to ' . htmlspecialchars($email) . '. Check inbox/spam.'];
                        header("Location: editprofile.php");
                        exit();
                    } else {
                        $message = ['type' => 'danger', 'text' => 'Failed to send OTP email. Try again or contact support.'];
                    }
                } else {
                     $message = ['type' => 'danger', 'text' => 'Failed to store OTP. Please try again.'];
                }
                mysqli_stmt_close($store_otp_stmt);
            } else {
                 $message = ['type' => 'danger', 'text' => 'Database error preparing OTP storage.'];
            }
        } else {
             $message = ['type' => 'danger', 'text' => 'Could not retrieve email address for OTP.'];
        }
    } else {
         $message = ['type' => 'danger', 'text' => 'Database error fetching email.'];
    }
}

if (isset($_POST['verify_otp'])) {
    // (OTP verification logic as you provided - no changes needed here for delete functionality)
    $entered_otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
    if (empty($entered_otp)) {
         $message = ['type' => 'danger', 'text' => 'Please enter the OTP.'];
    } else {
        $fetch_otp_sql = "SELECT otp, otp_expiry FROM users WHERE u_id = ?";
        $fetch_otp_stmt = mysqli_prepare($db, $fetch_otp_sql);
        if ($fetch_otp_stmt) {
            mysqli_stmt_bind_param($fetch_otp_stmt, "i", $user_id);
            mysqli_stmt_execute($fetch_otp_stmt);
            $otp_result = mysqli_stmt_get_result($fetch_otp_stmt);
            $otp_data = mysqli_fetch_assoc($otp_result);
            mysqli_stmt_close($fetch_otp_stmt);

            if ($otp_data && !empty($otp_data['otp']) && !empty($otp_data['otp_expiry'])) {
                if (time() > $otp_data['otp_expiry']) {
                    $clear_otp_sql = "UPDATE users SET otp = NULL, otp_expiry = NULL WHERE u_id = ?";
                    $clear_stmt = mysqli_prepare($db, $clear_otp_sql);
                    mysqli_stmt_bind_param($clear_stmt, "i", $user_id);
                    mysqli_stmt_execute($clear_stmt);
                    mysqli_stmt_close($clear_stmt);
                    unset($_SESSION['otp_requested']);
                    $message = ['type' => 'danger', 'text' => 'OTP has expired. Please request a new one.'];
                } elseif (verifyOTP($entered_otp, $otp_data['otp'])) {
                    $_SESSION['otp_verified'] = true;
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'OTP verified. You can now set a new password.'];
                    header("Location: editprofile.php");
                    exit();
                } else {
                    $message = ['type' => 'danger', 'text' => 'Incorrect OTP entered.'];
                }
            } else {
                 $message = ['type' => 'danger', 'text' => 'No valid OTP found. Request one first.'];
                 unset($_SESSION['otp_requested']);
            }
        } else {
             $message = ['type' => 'danger', 'text' => 'Database error verifying OTP.'];
        }
    }
    if(isset($message['type']) && $message['type'] === 'danger') {
      $_SESSION['otp_requested'] = true;
    }
}

if (isset($_POST['change_password'])) {
    // (Password change logic as you provided - no changes needed here for delete functionality)
     if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $password_error = "OTP verification required before changing password. Please verify OTP first.";
        unset($_SESSION['otp_requested'], $_SESSION['otp_verified']);
    } else {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $password_valid = true;

        if (strlen($new_password) < 6) { $password_error = "Password must be at least 6 characters."; $password_valid = false; }
        elseif (!preg_match('/[A-Z]/', $new_password)) { $password_error = "Password needs an uppercase letter."; $password_valid = false; }
        elseif (!preg_match('/[a-z]/', $new_password)) { $password_error = "Password needs a lowercase letter."; $password_valid = false; }
        elseif (!preg_match('/[0-9]/', $new_password)) { $password_error = "Password needs a number."; $password_valid = false; }
        elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~]/', $new_password)) { $password_error = "Password needs a special character."; $password_valid = false; }
        elseif ($new_password !== $confirm_password) { $password_error = "Passwords do not match."; $password_valid = false; }

        if ($password_valid) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_sql = "UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE u_id = ?";
            $update_password_stmt = mysqli_prepare($db, $update_password_sql);
            if ($update_password_stmt) {
                mysqli_stmt_bind_param($update_password_stmt, "si", $hashed_password, $user_id);
                if (mysqli_stmt_execute($update_password_stmt)) {
                    unset($_SESSION['otp_verified'], $_SESSION['otp_requested']);
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Password changed successfully!'];
                    header("Location: editprofile.php");
                    exit();
                } else { $password_error = "Failed to update password. Try again."; }
                mysqli_stmt_close($update_password_stmt);
            } else { $password_error = "DB error preparing password update."; }
        }
        $_SESSION['otp_requested'] = true;
        $_SESSION['otp_verified'] = true;
    }
}

if (isset($_POST['update_profile'])) {
    // (Profile update logic as you provided - no changes needed here for delete functionality)
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $submitted_city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);

    $validation_passed = true;
    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($address)) { $profile_error = "All profile fields are required."; $validation_passed = false; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $profile_error = "Invalid email format."; $validation_passed = false; }
    elseif (!preg_match("/^[0-9]{10}$/", $phone)) { $profile_error = "Phone must be 10 digits."; $validation_passed = false; }
    elseif (strlen($firstname) < 2) { $profile_error = "First name too short."; $validation_passed = false; }
    elseif (strlen($lastname) < 2) { $profile_error = "Last name too short."; $validation_passed = false; }

    if ($validation_passed) {
        $latitude = $user['latitude'] ?? null;
        $longitude = $user['longitude'] ?? null;
        $detected_city = null;
        $address_changed = (!isset($user['address']) || $address !== $user['address']);

        if ($address_changed) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $script_path = dirname($_SERVER['PHP_SELF']);
            $geocode_url = $protocol . $host . ($script_path == '/' ? '' : $script_path) . "/geocode.php";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $geocode_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['address' => $address]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: BhojonBartaProfileUpdate/1.0'));
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error_no = curl_errno($ch);
            $curl_error_msg = curl_error($ch);
            curl_close($ch);

            if ($curl_error_no === 0 && $httpcode == 200 && $response !== false) {
                $geo_data = json_decode($response, true);
                error_log("Server Geocode Response for '$address': " . $response);
                if ($geo_data && $geo_data['status'] === 'success' && isset($geo_data['latitude']) && isset($geo_data['longitude']) && isset($geo_data['city']) && $geo_data['city'] !== 'Unknown') {
                    $latitude = $geo_data['latitude'];
                    $longitude = $geo_data['longitude'];
                    $detected_city = $geo_data['city'];
                } else {
                    $profile_error = "Could not determine city for '" . htmlspecialchars($address) . "'. Error: " . ($geo_data['message'] ?? 'Geocode issue');
                    $detected_city = null;
                }
            } else {
                 $profile_error = "Error verifying address. Check address or try later.";
                 error_log("Curl error geocode.php: Errno $curl_error_no - $curl_error_msg (HTTP: $httpcode)");
                 $detected_city = null;
            }
        } else {
            $detected_city = $user['city'] ?? $submitted_city;
            $latitude = $user['latitude'] ?? null;
            $longitude = $user['longitude'] ?? null;
        }

        if (empty($detected_city) || $detected_city === 'Unknown') {
             if (!$profile_error) { $profile_error = "City could not be determined."; }
        } elseif (!isServiceableCity($detected_city, $db)) {
             $profile_error = "Service unavailable in '".htmlspecialchars($detected_city)."'. Try serviceable areas.";
             // JavaScript will handle more prominent SweetAlert for this
        } else {
            $update_sql = "UPDATE users SET f_name = ?, l_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ?, city = ? WHERE u_id = ?";
            $stmt_update = mysqli_prepare($db, $update_sql);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "sssssddsi", $firstname, $lastname, $email, $phone, $address, $latitude, $longitude, $detected_city, $user_id);
                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
                    header("Location: editprofile.php");
                    exit();
                } else {
                    $profile_error = "Failed to update profile. Error: " . mysqli_error($db);
                    error_log("Profile update failed user $user_id: " . mysqli_stmt_error($stmt_update));
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $profile_error = "DB error preparing profile update. " . mysqli_error($db);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Your Account | Bhojon Barta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --bb-primary-color: #1abc9c; --bb-secondary-color: #3498db; --bb-dark-text: #2c3e50;
            --bb-light-text: #5a6a7a; --bb-body-bg-start: #f4f7f6; --bb-body-bg-end: #d4e4f3;
            --bb-card-bg: #ffffff; --bb-input-bg: #fdfdff; --bb-input-border: #d1d9e6;
            --bb-danger-color: #e74c3c; --bb-success-color: #2ecc71;
        }
        body {
            background: linear-gradient(-45deg, var(--bb-body-bg-start), var(--bb-body-bg-end));
            background-size: 200% 200%; animation: gradientBG 25s ease infinite; min-height: 100vh;
            padding-top: 3rem; padding-bottom: 4rem; font-family: 'Roboto', sans-serif;
            color: var(--bb-dark-text); line-height: 1.6;
        }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .main-container { max-width: 1140px; margin: auto; padding: 0 15px; }
        .page-header { text-align: center; margin-bottom: 3rem; color: var(--bb-dark-text); }
        .page-header h1 { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 2.5rem; letter-spacing: 0.2px; margin-bottom: 0.75rem; }
        .page-header .breadcrumb-container { margin-top: 0.25rem; }
        .page-header .breadcrumb { background: transparent; padding: 0.3rem 0.8rem; border-radius: 50px; display: inline-block; font-size: 0.9rem; }
        .page-header .breadcrumb-item a { color: var(--bb-secondary-color); text-decoration: none; font-weight: 500; }
        .page-header .breadcrumb-item a:hover { color: var(--bb-primary-color); text-decoration: underline; }
        .page-header .breadcrumb-item.active { color: var(--bb-light-text); font-weight: 400; }
        .form-section-card {
            background: var(--bb-card-bg); border-radius: 16px;
            box-shadow: 0 8px 25px rgba(44,62,80,0.07), 0 2px 8px rgba(44,62,80,0.04);
            padding: 2.5rem 3rem; animation: fadeIn 0.5s ease-out;
            margin: 0 auto 2.5rem auto; max-width: 760px;
            border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column;
        }
        .form-section-card .card-content-wrapper { flex-grow: 1; }
        @keyframes fadeIn { from { transform: translateY(25px) scale(0.98); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        h2.section-title {
            color: var(--bb-dark-text); position: relative; padding-bottom: 1rem; margin-bottom: 2.5rem;
            font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 1.6rem; text-align: center;
            display: flex; align-items: center; justify-content: center;
        }
        h2.section-title i { margin-right: 0.85rem; color: var(--bb-primary-color); font-size: 1.4rem; }
        h2.section-title::after {
            content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 60px; height: 3px; background: var(--bb-primary-color);
            border-radius: 3px; transition: width 0.3s ease;
        }
        h2.section-title:hover::after { width: 100px; }
        .form-control, .form-select {
            border: 1px solid var(--bb-input-border); border-radius: 8px;
            transition: all 0.25s ease-in-out; padding: 0.9rem 1rem;
            background-color: var(--bb-input-bg); font-size: 0.95rem; color: var(--bb-dark-text);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--bb-primary-color);
            box-shadow: 0 0 0 0.15rem rgba(26,188,156,0.2); background-color: #fff;
        }
        .form-floating > .form-control { padding: 1.25rem 1rem 0.5rem; }
        .form-floating > label { padding: 0.9rem 1rem; color: var(--bb-light-text); font-size: 0.95rem; }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label { transform: scale(.82) translateY(-.55rem) translateX(.15rem); opacity: 0.9; }
        .btn {
            border-radius: 8px; padding: 0.8rem 2rem; transition: all 0.25s cubic-bezier(0.25,0.8,0.25,1);
            font-weight: 500; font-family: 'Poppins', sans-serif; letter-spacing: 0.2px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05); text-transform: none; font-size: 0.95rem;
            border: 1px solid transparent;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(0,0,0,0.08); }
        .btn-primary { background-color: var(--bb-primary-color); border-color: var(--bb-primary-color); color: #fff; }
        .btn-primary:hover { background-color: #16a085; border-color: #16a085; color: #fff; }
        .btn-secondary { background: #e9ecef; color: var(--bb-dark-text); border-color: #ced4da; }
        .btn-secondary:hover { background: #d8dde1; border-color: #b9c0c7; color: var(--bb-dark-text); }
        .btn-danger { background-color: var(--bb-danger-color); border-color: var(--bb-danger-color); color: #fff; }
        .btn-danger:hover { background-color: #c0392b; border-color: #c0392b; color: #fff; }
        .form-text { font-size: 0.85rem; color: var(--bb-light-text); }
        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #90a0b0; transition: all 0.2s ease; padding: 5px; }
        .toggle-password:hover { color: var(--bb-primary-color); }
        .alert {
            border-radius: 8px; margin-bottom: 1.75rem; padding: 1rem 1.25rem; display: flex;
            align-items: center; font-size: 0.9rem; border-width: 1px; border-style: solid;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .alert .btn-close { margin-left: auto; opacity: 0.6; }
        .alert .btn-close:hover { opacity: 0.9; }
        .alert-success { background-color: #e6f9f3; color: #0e6245; border-color: rgba(26,188,156,0.3); }
        .alert-danger { background-color: #fdecea; color: #a9261a; border-color: rgba(231,76,60,0.3); }
        .alert-warning { background-color: #fff8e1; color: #8a6d3b; border-color: rgba(241,196,15,0.3); }
        .alert-info { background-color: #e7f3fe; color: #1e5d8b; border-color: rgba(52,152,219,0.3); }
        .alert i.fas { font-size: 1.1em; margin-right: 0.7rem; }
        #addressSuggestions {
            position: absolute; z-index: 1050; width: 100%; left: 0; top: calc(100% - 1px);
            background: white; border: 1px solid var(--bb-input-border); border-top: none;
            border-radius: 0 0 8px 8px; max-height: 220px; overflow-y: auto; display: none;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
        }
        #addressSuggestions div { padding: 12px 15px; cursor: pointer; font-size: 0.9rem; border-bottom: 1px solid #f0f0f0; transition: background-color 0.15s ease, color 0.15s ease; }
        #addressSuggestions div:last-child { border-bottom: none; }
        #addressSuggestions div:hover { background: #f0f8f6; color: var(--bb-primary-color); }
        .address-loading { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); display: none; color: var(--bb-secondary-color); font-size: 0.9rem; }
        .form-floating > .address-loading { right: 15px; }
        .position-relative { position: relative !important; }
        .sticky-alerts { position: sticky; top: 1.5rem; z-index: 1055; max-width: 760px; margin: 0 auto 1.5rem auto; }
        .home-button-container { position: fixed; top: 20px; left: 20px; z-index: 1100; }
        .home-button-container .btn {
            background: rgba(255,255,255,0.95); color: var(--bb-dark-text); border: 1px solid rgba(0,0,0,0.08);
            width: 45px; height: 45px; padding: 0; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        .home-button-container .btn:hover { background: #fff; color: var(--bb-primary-color); box-shadow: 0 5px 12px rgba(0,0,0,0.15); transform: translateY(-1px) scale(1.03); }
        .home-button-container .btn i { font-size: 1.1rem; }
        .otp-section form { max-width: 360px; margin: auto; }
        .otp-section .form-floating input#otp { text-align: center; font-size: 1.1rem; letter-spacing: 0.25em; font-family: 'Poppins', monospace; }
        .swal-custom-toast { font-size: 0.9rem !important; }

        @media (max-width: 767.98px) {
            .page-header h1 { font-size: 2rem; }
            .form-section-card { padding: 2rem 1.5rem; margin-left: 10px; margin-right: 10px; max-width: none; }
            h2.section-title { font-size: 1.4rem; }
            .home-button-container { top: 15px; left: 15px; }
            .home-button-container .btn { width: 42px; height: 42px; }
            .home-button-container .btn i { font-size: 1rem;}
            .sticky-alerts { top: 1rem; margin-left: 10px; margin-right: 10px; max-width: none;}
            .d-grid.gap-2, .d-flex.gap-3 { flex-direction: column; }
            .d-grid.gap-2 .btn, .d-flex.gap-3 .btn { width: 100%; }
            .form-floating > .form-control { padding: 1.2rem 0.9rem 0.4rem; }
            .form-floating > label { padding: 0.8rem 0.9rem; font-size: 0.9rem; }
        }
        @media (max-width: 575.98px) {
            .page-header { margin-bottom: 2rem;}
            .page-header h1 { font-size: 1.8rem; }
            .form-section-card { padding: 1.5rem 1rem; }
            h2.section-title { font-size: 1.3rem; margin-bottom: 2rem;}
            .btn { font-size: 0.9rem; padding: 0.7rem 1.5rem; }
            .form-control, .form-select { font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="home-button-container">
        <a href="index.php" class="btn" title="Back to Home">
            <i class="fas fa-home"></i>
        </a>
    </div>

    <!-- CSRF Token for Delete Action -->
    <input type="hidden" id="csrfTokenForDelete" value="<?php echo htmlspecialchars($csrf_token_value_for_html); ?>">

    <div class="main-container">
        <div class="page-header">
            <h1>Manage Your Account</h1>
            <div class="breadcrumb-container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="sticky-alerts">
        <?php
        if (!empty($message)) {
             $icon_class = $message['type'] == 'success' ? 'fa-check-circle' : ($message['type'] == 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
             echo "<div class='alert alert-{$message['type']} alert-dismissible fade show' role='alert'>"
                . "<span><i class='fas " . $icon_class . " me-2'></i>" . htmlspecialchars($message['text']) . "</span>"
                . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>"
              . "</div>";
        }
        if ($profile_error) {
            echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>"
                . "<span><i class='fas fa-times-circle me-2'></i>" . htmlspecialchars($profile_error) . "</span>"
                . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>"
              . "</div>";
        }
        if ($password_error) {
             echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>"
                . "<span><i class='fas fa-times-circle me-2'></i>" . htmlspecialchars($password_error) . "</span>"
                . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>"
              . "</div>";
        }
        ?>
        </div>

        <!-- Profile Edit Section Card -->
        <div class="form-section-card">
            <div class="card-content-wrapper">
                <h2 class="section-title"><i class="fas fa-user-edit"></i>Edit Profile Information</h2>
                <form action="editprofile.php" method="post" id="profileForm" novalidate>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['f_name'] ?? ''); ?>" placeholder="First Name" required minlength="2">
                                <label for="firstname">First Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['l_name'] ?? ''); ?>" placeholder="Last Name" required minlength="2">
                                <label for="lastname">Last Name</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Email Address" required>
                        <label for="email">Email Address</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Phone Number (10 digits)" required pattern="[0-9]{10}">
                        <label for="phone">Phone Number (10 digits)</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Full Address" required autocomplete="off">
                        <label for="address">Full Address</label>
                        <span class="address-loading"><i class="fas fa-spinner fa-spin"></i></span>
                        <div id="addressSuggestions"></div>
                    </div>
                    <input type="hidden" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 pt-2">
                        <button type="submit" class="btn btn-primary" name="update_profile"><i class="fas fa-save me-2"></i>Save Changes</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Change Section Card -->
        <div class="form-section-card">
             <div class="card-content-wrapper otp-section">
                <h2 class="section-title"><i class="fas fa-shield-alt"></i>Change Password</h2>
                <?php if (!isset($_SESSION['otp_requested'])): ?>
                    <p class="text-center text-muted mb-3">Verify identity via email to change password.</p>
                    <form method="post" action="editprofile.php" class="text-center">
                        <button type="submit" name="request_otp" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Request OTP</button>
                    </form>
                <?php endif; ?>
                <?php if (isset($_SESSION['otp_requested']) && !isset($_SESSION['otp_verified'])): ?>
                    <p class="text-center text-muted mb-3">An OTP was sent. Enter it below.</p>
                    <form method="post" action="editprofile.php">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="otp" name="otp" required placeholder="Enter OTP" pattern="[0-9]{6}" inputmode="numeric" maxlength="6">
                            <label for="otp">Enter 6-Digit OTP</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="verify_otp" class="btn btn-primary"><i class="fas fa-check-circle me-2"></i>Verify OTP</button>
                        </div>
                    </form>
                <?php endif; ?>
                <?php if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified']): ?>
                    <p class="text-center text-success mb-3"><i class="fas fa-check-circle"></i> OTP Verified. Set new password.</p>
                    <form method="post" id="changePasswordForm" action="editprofile.php" novalidate>
                        <div class="form-floating mb-3 password-container">
                            <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="New Password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+~=\`{\}[\]:;'<>,.?/|\\-]).{6,}">
                            <label for="new_password">New Password</label>
                            <span class="toggle-password" data-target="new_password"><i class="fas fa-eye"></i></span>
                            <div class="form-text ps-1">Min. 6 chars: 1 upper, 1 lower, 1 num, 1 special.</div>
                        </div>
                        <div class="form-floating mb-3 password-container">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm New Password">
                            <label for="confirm_password">Confirm New Password</label>
                            <span class="toggle-password" data-target="confirm_password"><i class="fas fa-eye"></i></span>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" name="change_password" class="btn btn-primary"><i class="fas fa-lock me-2"></i>Set New Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

         <!-- Delete Account Section Card (Hard Delete) -->
        <div class="form-section-card">
            <div class="card-content-wrapper">
                <h2 class="section-title"><i class="fas fa-user-slash text-danger"></i>Account Deletion</h2>
                <p class="text-center text-muted mb-3">
                    Permanently delete your account. Basic information will be logged. <br><strong class="text-danger">This action is irreversible.</strong>
                </p>
                <div class="d-grid">
                    <button type="button" id="deleteAccountBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Delete My Account Permanently
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /.main-container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => { /* ... (password toggle logic as before) ... */
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
                }
            });
        });

        const addressInput = document.getElementById('address');
        const cityInput = document.getElementById('city');
        const suggestionsContainer = document.getElementById('addressSuggestions');
        const loadingIndicator = document.querySelector('.address-loading');
        let debounceTimeout;
        let geocodeController;

        async function checkCityServiceability(cityName, showSuccess = false) { /* ... (as before) ... */
            Swal.close();
            if (!cityName || cityName === 'Unknown' || cityName.trim() === '') {
                cityInput.value = '';
                Swal.fire({ icon: 'warning', title: 'City Not Identified', text: 'Could not determine city.', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true, customClass: { popup: 'swal-custom-toast' } });
                return false;
            }
            const checkingToast = Swal.fire({ title: `Checking in ${cityName}...`, toast: true, position: 'top-end', showConfirmButton: false, didOpen: () => { Swal.showLoading() }, customClass: { popup: 'swal-custom-toast' } });
            try {
                const response = await fetch(`check_city.php?city=${encodeURIComponent(cityName)}`);
                if (!response.ok) throw new Error(`Network error: ${response.statusText}`);
                const data = await response.json();
                checkingToast.close();
                if (data.serviceable) {
                     cityInput.value = cityName;
                     if (showSuccess) Swal.fire({ icon: 'success', title: 'Service Available!', text: `We deliver to ${cityName}.`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, customClass: { popup: 'swal-custom-toast' } });
                    return true;
                } else {
                    cityInput.value = cityName;
                    Swal.fire({ icon: 'error', title: 'Service Unavailable', html: `No delivery to <strong>${cityName}</strong>. Try Kolkata, Berhampore.`, confirmButtonText: 'OK', confirmButtonColor: 'var(--bb-danger-color)' });
                    return false;
                }
            } catch (error) {
                console.error('Error checking city serviceability:', error);
                checkingToast.close(); cityInput.value = '';
                Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Could not verify serviceability.', confirmButtonText: 'OK', confirmButtonColor: 'var(--bb-danger-color)' });
                return false;
            }
        }


        if (addressInput) { /* ... (address autocomplete & geocoding logic as before, ensure geocode.php path is correct) ... */
             addressInput.addEventListener('input', function() {
                clearTimeout(debounceTimeout);
                const query = this.value.trim();
                if (query.length < 3) { suggestionsContainer.style.display = 'none'; suggestionsContainer.innerHTML = ''; return; }
                if (geocodeController) geocodeController.abort();
                geocodeController = new AbortController();
                const signal = geocodeController.signal;
                debounceTimeout = setTimeout(() => {
                    loadingIndicator.style.display = 'inline-block';
                    const protocol = window.location.protocol;
                    const host = window.location.host;
                    let basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                    if (basePath === "") basePath = "."; // Handle if script is in root
                    const geocodeUrl = `${protocol}//${host}${basePath}/geocode.php`;


                    fetch(geocodeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `autocomplete=true&query=${encodeURIComponent(query)}`,
                        signal: signal
                    })
                    .then(response => { if (!response.ok) throw new Error(`HTTP error ${response.status}`); return response.json(); })
                    .then(data => {
                        loadingIndicator.style.display = 'none'; suggestionsContainer.innerHTML = '';
                        if (data.status === 'success' && data.suggestions?.length > 0) {
                            data.suggestions.forEach(suggestion => {
                                const div = document.createElement('div');
                                div.textContent = suggestion.display_name;
                                div.dataset.city = suggestion.city;
                                div.addEventListener('click', async () => {
                                    addressInput.value = suggestion.display_name;
                                    suggestionsContainer.style.display = 'none'; suggestionsContainer.innerHTML = '';
                                    let cityFromSuggestion = suggestion.city;
                                    if (cityFromSuggestion && cityFromSuggestion !== 'Unknown' && cityFromSuggestion.trim() !== '') {
                                       await checkCityServiceability(cityFromSuggestion, true);
                                    } else {
                                        loadingIndicator.style.display = 'inline-block';
                                        try {
                                            const geoResponse = await fetch(geocodeUrl, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                                body: `address=${encodeURIComponent(suggestion.display_name)}`
                                            });
                                            if (!geoResponse.ok) throw new Error('Geocode failed');
                                            const geoData = await geoResponse.json();
                                            loadingIndicator.style.display = 'none';
                                            if (geoData.status === 'success' && geoData.city && geoData.city !== 'Unknown') {
                                                await checkCityServiceability(geoData.city, true);
                                            } else { await checkCityServiceability(null); }
                                        } catch (e) { loadingIndicator.style.display = 'none'; await checkCityServiceability(null); }
                                    }
                                });
                                suggestionsContainer.appendChild(div);
                            });
                            suggestionsContainer.style.display = 'block';
                        } else { suggestionsContainer.style.display = 'none'; }
                    })
                    .catch(error => {
                        loadingIndicator.style.display = 'none';
                        if (error.name === 'AbortError') console.log('Autocomplete fetch aborted.');
                        else console.error('Autocomplete fetch error:', error);
                        suggestionsContainer.style.display = 'none';
                    });
                }, 350);
            });
            addressInput.addEventListener('blur', function() {
                 setTimeout(async () => {
                    if (document.activeElement === suggestionsContainer || suggestionsContainer.contains(document.activeElement)) { return; }
                    suggestionsContainer.style.display = 'none';
                    const currentAddress = this.value.trim();
                    const currentCitySet = cityInput.value.trim();
                    if (currentAddress && (!currentCitySet || currentCitySet === 'Unknown' ) ) {
                        if (geocodeController) geocodeController.abort();
                        geocodeController = new AbortController();
                        const signal = geocodeController.signal;
                        loadingIndicator.style.display = 'inline-block';
                        const protocol = window.location.protocol;
                        const host = window.location.host;
                        let basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                        if (basePath === "") basePath = ".";
                        const geocodeUrl = `${protocol}//${host}${basePath}/geocode.php`;
                        try {
                            const response = await fetch(geocodeUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `address=${encodeURIComponent(currentAddress)}`, signal: signal
                            });
                            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
                            const data = await response.json();
                            loadingIndicator.style.display = 'none';
                            if (data.status === 'success' && data.city && data.city !== 'Unknown') {
                                await checkCityServiceability(data.city);
                            } else if (currentAddress) { await checkCityServiceability(null); }
                        } catch (error) {
                            loadingIndicator.style.display = 'none';
                            if (error.name !== 'AbortError' && currentAddress) { console.error('Blur Geocode fetch error:', error); await checkCityServiceability(null); }
                        }
                    }
                }, 200);
            });
            document.addEventListener('click', function(e) {
                if (addressInput && !addressInput.contains(e.target) && suggestionsContainer && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });
        }

        const formsToValidate = document.querySelectorAll('form[novalidate]');
        Array.from(formsToValidate).forEach(form => { /* ... (validation logic as before) ... */
            form.addEventListener('submit', event => {
            if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
            form.classList.add('was-validated');
            }, false);
        });

        <?php if ($profile_error || $password_error): ?>
        document.getElementById('profileForm')?.classList.add('was-validated');
        document.getElementById('changePasswordForm')?.classList.add('was-validated');
        <?php endif; ?>

        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if(deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', function() {
                const csrfToken = document.getElementById('csrfTokenForDelete').value; // Get CSRF token
                Swal.fire({
                    title: 'Permanently Delete Account?',
                    html: `
                        <p class="text-danger fw-bold">This action is IRREVERSIBLE and will permanently delete your account and associated data.</p>
                        <p>Your basic information will be logged for record-keeping.</p>
                        <p class="mt-3">To confirm, please enter your <strong>current password</strong>:</p>
                    `,
                    icon: 'error',
                    input: 'password',
                    inputPlaceholder: 'Enter current password',
                    inputAttributes: { autocapitalize: 'off', autocorrect: 'off', name: 'current_password_delete_confirm', autocomplete: 'current-password' },
                    inputValidator: (value) => { if (!value) return 'Password is required!' },
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Yes, DELETE PERMANENTLY',
                    confirmButtonColor: 'var(--bb-danger-color)',
                    cancelButtonText: '<i class="fas fa-times me-1"></i> No, keep account',
                    cancelButtonColor: '#6c757d',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        return fetch('delete_account.php', { // This now calls the hard delete script
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `password=${encodeURIComponent(password)}&csrf_token=${encodeURIComponent(csrfToken)}` // Send CSRF token
                        })
                        .then(response => {
                            const statusCode = response.status;
                            // Try to parse JSON regardless of status, as backend might send JSON error messages
                            return response.json().then(data => ({
                                status: statusCode, // Add original status code to the parsed data
                                data: data
                            })).catch(parseError => {
                                // If JSON parsing fails, it means the response was not valid JSON (e.g. HTML error page)
                                console.error("JSON Parsing Error:", parseError);
                                console.error("Raw Response Text:", response.text ? response.text() : "Could not get raw text"); // Log raw response if possible
                                throw new Error(`Server returned non-JSON response (Status: ${statusCode}). Check console/PHP logs.`);
                            });
                        })
                        .catch(error => { // Catches network errors or the error thrown above if JSON parsing failed
                            Swal.showValidationMessage(`Request failed: ${error.message}`);
                            // Return a simulated error structure so .then((result)) can handle it consistently
                            return { status: 500, data: { status: 'error', message: `Network error or invalid server response: ${error.message}` } };
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value) { // result.value will contain {status, data}
                        const responseStatus = result.value.status; // HTTP status code
                        const responseData = result.value.data;   // Parsed JSON from backend

                        if (responseData.status === 'success') { // Check 'status' within the JSON data
                            Swal.fire({
                                title: 'Account Deleted',
                                text: responseData.message || 'Your account has been permanently deleted.',
                                icon: 'success',
                                confirmButtonColor: 'var(--bb-primary-color)'
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        } else {
                            // More specific error messages based on backend response
                            let detailedMessage = responseData.message || 'Could not delete account. Please try again.';
                            if (responseStatus === 403) { // CSRF or other Forbidden by HTTP status
                                detailedMessage = responseData.message || 'Security error. Please refresh the page and try again.';
                            } else if (responseStatus === 401) { // Unauthorized (wrong password) by HTTP status
                                detailedMessage = responseData.message || 'Incorrect password.';
                            }
                            // For other 4xx/5xx errors, use the message from JSON if available

                            Swal.fire({
                                title: 'Deletion Failed',
                                html: detailedMessage, // Use html to render potential <br> or other formatting
                                icon: 'error',
                                confirmButtonColor: 'var(--bb-danger-color)'
                            });
                        }
                    } else if (result.dismissReason) {
                        // User cancelled or clicked outside the modal
                        console.log('Account deletion cancelled by user:', result.dismissReason);
                    }
                });
            });
        }
    });
</script>

</body>
</html>