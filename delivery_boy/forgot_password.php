<?php
include("../connection/connect.php"); // Adjust path if needed
require '../PHPMailer/src/PHPMailer.php'; // Adjust path if needed
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL); // Use E_ALL for development, 0 for production
ini_set('display_errors', 1); // Show errors during development
ini_set('log_errors', 1);

session_start(); // Start session to store email for next step

$message = "";
$message_type = ""; // 'success' or 'danger'

// --- Function to send the password reset OTP email ---
function sendPasswordResetOTP($email, $name, $otp) {
    // --- Configure Your Mailer ---
    $mailerUsername = 'bhojonbarta@gmail.com'; // Your Gmail address
    // !!! IMPORTANT: REPLACE WITH YOUR GENERATED APP PASSWORD !!!
    $mailerPassword = 'zyys vops vyua zetu';
    $mailerFromName = 'Bhojon Barta Support';

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to DEBUG_SERVER for troubleshooting
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailerUsername;
        $mail->Password   = $mailerPassword; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom($mailerUsername, $mailerFromName);
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP - Bhojon Barta Delivery Partner';
        $mail->Body    = "Dear " . htmlspecialchars($name) . ",<br><br>You requested a password reset for your Bhojon Barta Delivery Partner account.<br><br>Your One-Time Password (OTP) is: <strong style='font-size: 1.2em;'>" . $otp . "</strong><br><br>This OTP is valid for 10 minutes.<br><br>If you did not request this, please ignore this email.<br><br>Best regards,<br>Bhojon Barta Team";
        $mail->AltBody = "Dear " . htmlspecialchars($name) . ",\n\nYou requested a password reset.\nYour One-Time Password (OTP) is: " . $otp . "\nThis OTP is valid for 10 minutes.\n\nIf you did not request this, ignore this email.\n\nBest regards,\nBhojon Barta Team";

        $mail->send();
        error_log("Password reset OTP sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error sending OTP to " . $email . ": " . $mail->ErrorInfo);
        return false;
    }
}


if (isset($_POST['submit'])) {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));

    if (!$email) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } else {
        // Find the delivery boy by email
        $sql_find = "SELECT db_id, db_name FROM delivery_boy WHERE db_email = ?";
        $stmt_find = mysqli_prepare($db, $sql_find);

        if ($stmt_find) {
            mysqli_stmt_bind_param($stmt_find, "s", $email);
            mysqli_stmt_execute($stmt_find);
            $result_find = mysqli_stmt_get_result($stmt_find);
            $user = mysqli_fetch_assoc($result_find);
            mysqli_stmt_close($stmt_find);

            if ($user) {
                // User exists, generate OTP and expiry
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // 6-digit OTP
                $expiry_time = date('Y-m-d H:i:s', strtotime('+10 minutes')); // OTP expiry (e.g., 10 mins)

                // Update delivery_boy table with OTP and expiry
                $sql_update = "UPDATE delivery_boy SET reset_otp = ?, reset_otp_expiry = ? WHERE db_id = ?";
                $stmt_update = mysqli_prepare($db, $sql_update);

                if ($stmt_update) {
                    mysqli_stmt_bind_param($stmt_update, "ssi", $otp, $expiry_time, $user['db_id']);

                    if (mysqli_stmt_execute($stmt_update)) {
                        mysqli_stmt_close($stmt_update); // Close statement after execution

                        // Send OTP email
                        if (sendPasswordResetOTP($email, $user['db_name'], $otp)) {
                             // Store email in session for the verification page
                             $_SESSION['reset_email'] = $email;
                             // Redirect to OTP verification page
                             header("Location: verify_otp.php");
                             exit();
                        } else {
                             $message = "Could not send the OTP email. Please try again or contact support.";
                             $message_type = "danger";
                             // Optional: Rollback OTP update if email fails? Consider implications.
                             // $sql_clear_otp = "UPDATE delivery_boy SET reset_otp = NULL, reset_otp_expiry = NULL WHERE db_id = ?";
                             // ... execute clear otp ...
                        }
                    } else {
                        error_log("DB Error updating OTP for db_id " . $user['db_id'] . ": " . mysqli_stmt_error($stmt_update));
                        $message = "An internal error occurred saving the OTP. Please try again.";
                        $message_type = "danger";
                        mysqli_stmt_close($stmt_update);
                    }
                } else {
                     error_log("DB Error preparing OTP update: " . mysqli_error($db));
                     $message = "A database error occurred. Please try again.";
                     $message_type = "danger";
                }
            } else {
                // Email not found, show generic message for security
                // Still simulate a delay or process to prevent email enumeration
                usleep(500000); // Simulate processing time
                $message = "If an account with that email exists, an OTP has been sent. Check your inbox/spam folder (OTP expires in 10 minutes).";
                $message_type = "info"; // Use 'info' or 'success' styling
                error_log("Password reset OTP requested for non-existent delivery boy email: " . $email);
            }
        } else {
            error_log("DB Error preparing user lookup: " . mysqli_error($db));
            $message = "A database error occurred. Please try again later.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Delivery Partner</title>
    <!-- Include same CSS/Fonts as index.php -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Copy relevant styles from index.php or link to a shared CSS file -->
    <style>
        :root { --primary-color: #4F80E2; --primary-light: #e9effd; --primary-dark: #3e6ac4; --text-color: #333; --text-light: #777; --border-color: #e0e0e0; --input-bg: #fff; --card-bg: #ffffff; --body-bg: #f7f8fc; --error-bg: #fdecea; --error-text: #9b2c2c; --error-border: #f5c6cb; --success-bg: #d1fae5; --success-text: #065f46; --success-border: #a7f3d0; --info-bg: #e0f2fe; --info-text: #075985; --info-border: #bae6fd;}
        body { font-family: 'Roboto', sans-serif; background-color: var(--body-bg); color: var(--text-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { max-width: 420px; width: 100%; background-color: var(--card-bg); border-radius: 12px; padding: 45px 35px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04), 0 10px 20px rgba(79, 128, 226, 0.08); text-align: center; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 1.7rem; font-weight: 700; color: var(--primary-color); margin-bottom: 10px; line-height: 1.3; }
        p.info { color: var(--text-light); font-size: 0.95rem; margin-bottom: 25px; }
        .alert { margin-bottom: 20px; text-align: left; font-size: 0.875rem; padding: 12px 18px; border-radius: 8px; border-width: 1px; border-style: solid; display: flex; align-items: center; }
        .alert-danger { background-color: var(--error-bg); border-color: var(--error-border); color: var(--error-text); }
        .alert-success { background-color: var(--success-bg); border-color: var(--success-border); color: var(--success-text); }
        .alert-info { background-color: var(--info-bg); border-color: var(--info-border); color: var(--info-text); }
        .alert .fas { margin-right: 10px; font-size: 1.1em; }
        .alert .close { color: inherit; opacity: 0.6; padding: 12px 18px; font-size: 1.3rem; line-height: 1;}
        .alert .close:hover { opacity: 1; }
        .login-form { margin-top: 20px; }
        .login-form input[type="email"] { font-family: 'Roboto', sans-serif; font-weight: 400; font-size: 0.95rem; outline: 0; background: var(--input-bg); width: 100%; border: none; border-bottom: 1px solid var(--border-color); border-radius: 0; margin: 0 0 25px; padding: 12px 5px; box-sizing: border-box; color: var(--text-color); transition: border-color 0.3s ease; }
        .login-form input[type="email"]:focus { border-bottom-color: var(--primary-color); }
        .login-form input::placeholder { color: var(--text-light); opacity: 1; }
        .login-form input[type="submit"] { font-family: 'Montserrat', sans-serif; text-transform: uppercase; font-weight: 600; font-size: 0.875rem; letter-spacing: 0.8px; outline: 0; background: var(--primary-color); width: 100%; border: 0; border-radius: 8px; padding: 14px; color: #FFFFFF; cursor: pointer; transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.2s ease; margin-top: 10px; }
        .login-form input[type="submit"]:hover, .login-form input[type="submit"]:focus { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(79, 128, 226, 0.2); }
        .login-form input[type="submit"]:active { transform: translateY(0px); box-shadow: 0 2px 6px rgba(79, 128, 226, 0.15); background-color: var(--primary-dark); }
        .message { margin: 25px 0 0; color: var(--text-light); font-size: 0.875rem; font-weight: 400; }
        .message a { color: var(--primary-color); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
        .message a:hover { color: var(--primary-dark); text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Forgot Your Password?</h1>
        <p class="info">Enter your email address below. If an account exists, we'll send an OTP to reset your password.</p>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo ($message_type == 'danger' ? 'fa-times-circle' : 'fa-info-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <?php endif; ?>

        <form class="login-form" action="forgot_password.php" method="post" novalidate>
            <input type="email" placeholder="Enter your registered email" name="email" required aria-label="Email"/>
            <input type="submit" name="submit" value="Send OTP" />
        </form>
        <p class="message">Remember your password? <a href="index.php">Login here</a></p>
    </div>

    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Optional: Auto-dismiss alerts after a delay
        $(document).ready(function() {
            window.setTimeout(function() {
                $(".alert.alert-dismissible").fadeTo(500, 0.8).slideUp(500);
            }, 7000); // 7 seconds
        });
    </script>
</body>
</html>