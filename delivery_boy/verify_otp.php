<?php
// --- Production Error Settings (Use E_ALL, 1 for debugging) ---
error_reporting(0);
ini_set('display_errors', 0);
// --- Always log errors ---
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Optional: custom log file

include("../connection/connect.php"); // Adjust path if needed

session_start(); // Needed to get email and manage state

$message = "";
$message_type = ""; // 'success' or 'danger' or 'info'
$show_otp_form = true; // Show OTP form by default
$show_password_form = false; // Hide password form by default
$reset_email = $_SESSION['reset_email'] ?? null; // Get email from session

// --- Redirect if email is not in session (user shouldn't be here directly) ---
if (!$reset_email) {
    // Optionally set a flash message for the forgot password page
    $_SESSION['forgot_message'] = ["type" => "warning", "text" => "Session invalid or expired. Please request OTP again."];
    header("Location: forgot_password.php");
    exit();
}

// --- Handle OTP Verification ---
if (isset($_POST['verify_otp'])) {
    $submitted_otp = trim($_POST['otp']);

    if (empty($submitted_otp) || !ctype_digit($submitted_otp) || strlen($submitted_otp) != 6) {
        $message = "Please enter a valid 6-digit OTP.";
        $message_type = "danger";
    } else {
        // Fetch current user data including OTP and expiry based on session email
        $sql_fetch_user = "SELECT db_id, reset_otp, reset_otp_expiry FROM delivery_boy WHERE db_email = ?";
        $stmt_fetch = mysqli_prepare($db, $sql_fetch_user);
        $db_data_found = false;
        $current_db_user_data = null;

        if ($stmt_fetch) {
            mysqli_stmt_bind_param($stmt_fetch, "s", $reset_email);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            $current_db_user_data = mysqli_fetch_assoc($result_fetch);
            mysqli_stmt_close($stmt_fetch);
            if ($current_db_user_data) {
                $db_data_found = true;
            }
        } else {
            error_log("DB Error preparing user fetch for OTP verify: " . mysqli_error($db));
            $message = "A database error occurred. Please try again later.";
            $message_type = "danger";
        }

        // Proceed only if user data was fetched successfully
        if ($db_data_found) {
            // 1. Check if OTPs match
            $otp_matches = ($current_db_user_data['reset_otp'] == $submitted_otp);

            // 2. Check if expiry time is valid and in the future (use PHP time comparison)
            $is_expired = true; // Assume expired unless proven otherwise
            if (!empty($current_db_user_data['reset_otp_expiry'])) {
                $expiry_timestamp = strtotime($current_db_user_data['reset_otp_expiry']);
                $current_timestamp = time(); // Current UTC timestamp
                // Ensure strtotime didn't fail and expiry is in the future
                if ($expiry_timestamp && $expiry_timestamp > $current_timestamp) {
                    $is_expired = false;
                }
            }

            // 3. Determine verification status
            if ($otp_matches && !$is_expired) {
                // --- OTP is Correct and Not Expired ---
                $message = "OTP verified successfully. Please set your new password below.";
                $message_type = "success";
                $show_otp_form = false; // Hide OTP form
                $show_password_form = true; // Show password form
                $_SESSION['reset_db_id'] = $current_db_user_data['db_id']; // Store ID for password reset step

                // Clear OTP immediately after successful verification
                $sql_clear = "UPDATE delivery_boy SET reset_otp = NULL, reset_otp_expiry = NULL WHERE db_id = ?";
                $stmt_clear = mysqli_prepare($db, $sql_clear);
                if ($stmt_clear) {
                    mysqli_stmt_bind_param($stmt_clear, "i", $current_db_user_data['db_id']);
                    if (mysqli_stmt_execute($stmt_clear)) {
                         error_log("OTP Cleared Successfully after verification for db_id: " . $current_db_user_data['db_id']);
                    } else {
                        error_log("DB Error executing OTP clear for db_id " . $current_db_user_data['db_id'] . ": " . mysqli_stmt_error($stmt_clear));
                        // Non-fatal, proceed but log error
                    }
                    mysqli_stmt_close($stmt_clear);
                } else {
                     error_log("DB Error preparing OTP clear after verification: " . mysqli_error($db));
                     // Non-fatal, proceed but log error
                }
            } else {
                // --- OTP Verification Failed ---
                 if (!$otp_matches) {
                     $message = "Invalid OTP entered. Please check the OTP and try again.";
                     error_log("Invalid OTP attempt for email: " . $reset_email . " (Submitted: " . $submitted_otp . ")");
                 } else { // OTP matched, so it must have expired
                     $message = "The OTP has expired. Please request a new one.";
                     error_log("Expired OTP attempt for email: " . $reset_email . " (Submitted: " . $submitted_otp . ")");
                     // Optionally clear the expired OTP here as well
                     // $sql_clear_expired = "UPDATE delivery_boy SET reset_otp = NULL, reset_otp_expiry = NULL WHERE db_id = ? AND reset_otp = ?";
                     // ...
                 }
                 $message_type = "danger";
                 $show_otp_form = true; // Keep OTP form visible on error
                 $show_password_form = false;
            }
        } else if (empty($message)) { // Only show general error if no specific DB error occurred
            $message = "Could not retrieve user data. Please try requesting the OTP again.";
            $message_type = "danger";
        }
    }
}

// --- Handle New Password Submission ---
if (isset($_POST['reset_password'])) {
    // If OTP verification was just successful, password form should be visible
    $show_otp_form = false;
    $show_password_form = true; // Keep showing password form if there's an error here

    $db_id = $_SESSION['reset_db_id'] ?? null;
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Ensure user came from successful OTP verification step (db_id should be in session)
    if (!$db_id) {
        $message = "Your session seems to have expired or is invalid. Please start the password reset process again.";
        $message_type = "danger";
        $show_password_form = false; // Hide form
        unset($_SESSION['reset_email']); // Clean up session
    } elseif (empty($new_password) || empty($confirm_password)) {
        $message = "Please enter and confirm your new password.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 6) { // Basic length check
        $message = "Password must be at least 6 characters long.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "The passwords you entered do not match.";
        $message_type = "danger";
    } else {
        // --- Passwords match and meet criteria ---
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password and ensure OTP fields are NULL
        $sql_update = "UPDATE delivery_boy SET db_password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE db_id = ?";
        $stmt_update = mysqli_prepare($db, $sql_update);

        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "si", $new_password_hash, $db_id);
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);

                // --- Password reset success! ---
                error_log("Password successfully reset for db_id: " . $db_id);

                // Clean up session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_db_id']);
                session_regenerate_id(true); // Security: Generate new session ID after password change

                // Set success message for the login page
                $_SESSION['password_reset_success'] = "Your password has been reset successfully! Please log in with your new password.";
                header("Location: index.php"); // Redirect to login page
                exit();

            } else {
                // --- Password Update DB Error ---
                error_log("DB Error updating password for db_id " . $db_id . ": " . mysqli_stmt_error($stmt_update));
                $message = "An error occurred while updating your password. Please try again.";
                $message_type = "danger";
                mysqli_stmt_close($stmt_update);
            }
        } else {
             // --- Password Update Prepare Error ---
             error_log("DB Error preparing password update: " . mysqli_error($db));
             $message = "A database error occurred. Please try again.";
             $message_type = "danger";
        }
        // Ensure password form stays visible if update failed
        $show_password_form = true;
        $show_otp_form = false;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP & Reset Password</title>
    <!-- Stylesheets and Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Shared Styles -->
     <style>
        :root { --primary-color: #4F80E2; --primary-light: #e9effd; --primary-dark: #3e6ac4; --text-color: #333; --text-light: #777; --border-color: #e0e0e0; --input-bg: #fff; --card-bg: #ffffff; --body-bg: #f7f8fc; --error-bg: #fdecea; --error-text: #9b2c2c; --error-border: #f5c6cb; --success-bg: #d1fae5; --success-text: #065f46; --success-border: #a7f3d0; --info-bg: #e0f2fe; --info-text: #075985; --info-border: #bae6fd;}
        body { font-family: 'Roboto', sans-serif; background-color: var(--body-bg); color: var(--text-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { max-width: 420px; width: 100%; background-color: var(--card-bg); border-radius: 12px; padding: 45px 35px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04), 0 10px 20px rgba(79, 128, 226, 0.08); text-align: center; }
        h1 { font-family: 'Montserrat', sans-serif; font-size: 1.7rem; font-weight: 700; color: var(--primary-color); margin-bottom: 10px; line-height: 1.3; }
        p.info { color: var(--text-light); font-size: 0.95rem; margin-bottom: 25px; }
        .email-display { font-weight: 500; color: var(--primary-dark); margin-bottom: 20px; word-wrap: break-word; }
        .alert { margin-bottom: 20px; text-align: left; font-size: 0.875rem; padding: 12px 18px; border-radius: 8px; border-width: 1px; border-style: solid; display: flex; align-items: center; }
        .alert-danger { background-color: var(--error-bg); border-color: var(--error-border); color: var(--error-text); }
        .alert-success { background-color: var(--success-bg); border-color: var(--success-border); color: var(--success-text); }
        .alert-info { background-color: var(--info-bg); border-color: var(--info-border); color: var(--info-text); }
        .alert .fas { margin-right: 10px; font-size: 1.1em; }
        .alert .close { color: inherit; opacity: 0.6; padding: 12px 18px; font-size: 1.3rem; line-height: 1;}
        .alert .close:hover { opacity: 1; }
        .login-form { margin-top: 20px; }
        /* Input styling */
        .login-form input[type="text"],
        .login-form input[type="password"],
        .login-form input[type="email"] { font-family: 'Roboto', sans-serif; font-weight: 400; font-size: 0.95rem; outline: 0; background: var(--input-bg); width: 100%; border: none; border-bottom: 1px solid var(--border-color); border-radius: 0; margin: 0 0 25px; padding: 12px 5px; box-sizing: border-box; color: var(--text-color); transition: border-color 0.3s ease; }
        .login-form input[type="text"]:focus,
        .login-form input[type="password"]:focus { border-bottom-color: var(--primary-color); }
        .login-form input::placeholder { color: var(--text-light); opacity: 1; }
        .login-form input[readonly] { background-color: #e9ecef; opacity: 0.7; cursor: not-allowed; }

        /* Password input specific */
        .password-container-reset { position: relative; margin-bottom: 25px; }
        .login-form .password-container-reset input[type="password"],
        .login-form .password-container-reset input[type="text"] { padding-right: 40px; margin-bottom: 0; }
        .toggle-password-reset { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light); font-size: 1.1rem; transition: color 0.2s ease; }
        .toggle-password-reset:hover { color: var(--primary-color); }

        /* Submit button */
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

        <?php if ($show_otp_form): ?>
            <h1>Verify Your Identity</h1>
            <p class="info">An OTP has been sent to your email address:</p>
            <p class="email-display"><?php echo htmlspecialchars($reset_email); ?></p>
        <?php elseif ($show_password_form): ?>
             <h1>Set New Password</h1>
             <p class="info">Enter and confirm your new password below for:</p>
             <p class="email-display"><?php echo htmlspecialchars($reset_email); ?></p>
        <?php else: ?>
            <!-- Fallback title if neither form is shown -->
            <h1>Password Reset</h1>
        <?php endif; ?>


        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo ($message_type == 'success' ? 'fa-check-circle' : ($message_type == 'danger' ? 'fa-times-circle' : 'fa-info-circle')); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <?php endif; ?>

        <?php // --- OTP Form --- ?>
        <?php if ($show_otp_form): ?>
            <form class="login-form" action="verify_otp.php" method="post" novalidate>
                 <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-Digit OTP" name="otp" required aria-label="OTP" autocomplete="off"/>
                <input type="submit" name="verify_otp" value="Verify OTP" />
            </form>
             <p class="message">Didn't receive OTP? <a href="forgot_password.php">Request a new one</a></p>
        <?php endif; ?>

        <?php // --- New Password Form --- ?>
        <?php if ($show_password_form): ?>
             <form class="login-form" action="verify_otp.php" method="post" novalidate>
                 <div class="password-container-reset">
                    <input type="password" placeholder="Enter New Password" name="password" id="password-field-new" required aria-label="New Password"/>
                    <span class="toggle-password-reset" title="Show/Hide Password">
                        <i class="fa fa-eye"></i>
                    </span>
                </div>
                 <div class="password-container-reset">
                    <input type="password" placeholder="Confirm New Password" name="confirm_password" id="password-field-confirm" required aria-label="Confirm New Password"/>
                     <span class="toggle-password-reset" title="Show/Hide Password">
                        <i class="fa fa-eye"></i>
                    </span>
                </div>
                <input type="submit" name="reset_password" value="Reset Password" />
            </form>
        <?php endif; ?>

        <?php // --- Login Link (Show if forms are hidden after process or on session error) --- ?>
        <?php if (!$show_otp_form && !$show_password_form): ?>
             <p class="message">Return to <a href="index.php">Login page</a></p>
        <?php endif; ?>

    </div>

    <!-- JavaScript -->
    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
             // Password toggle for new password fields
            document.querySelectorAll('.toggle-password-reset').forEach(item => {
                item.addEventListener('click', event => {
                    const passwordInput = item.previousElementSibling;
                    if (!passwordInput) return;
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = item.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            });

            // Auto-dismiss alerts
            window.setTimeout(function() {
                $(".alert.alert-dismissible:not(.alert-success)").fadeTo(500, 0.8).slideUp(500);
                 // Give success message a bit longer
                 $(".alert.alert-success.alert-dismissible").delay(2000).fadeTo(500, 0.8).slideUp(500);
            }, 7000);
        });
    </script>
</body>
</html>