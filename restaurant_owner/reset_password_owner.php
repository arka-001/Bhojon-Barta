<?php
session_start();
include("../connection/connect.php"); // For $db connection

$message = '';
$message_type = 'danger'; // Default message type

// Check if email is set from the previous step
if (!isset($_SESSION['reset_email'])) {
    // Redirect back if email is not in session (user shouldn't be here directly)
    header("Location: forgot_password_owner.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validations
    if (empty($otp) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) { // Example: Enforce minimum password length
         $message = "Password must be at least 6 characters long.";
    } else {
        // Verify OTP and check expiry
        $current_time = date('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT owner_id FROM restaurant_owners WHERE email = ? AND reset_otp = ? AND reset_otp_expiry > ?");

        if(!$stmt) {
            error_log("Prepare failed (verify otp): " . $db->error);
            $message = "An internal error occurred.";
        } else {
            $stmt->bind_param("sss", $email, $otp, $current_time);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                // OTP is valid and not expired
                $owner = $result->fetch_assoc();

                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update password and clear OTP fields
                $update_stmt = $db->prepare("UPDATE restaurant_owners SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE email = ?");

                if(!$update_stmt) {
                    error_log("Prepare failed (update password): " . $db->error);
                    $message = "An internal error occurred while updating password.";
                } else {
                    $update_stmt->bind_param("ss", $hashed_password, $email);

                    if ($update_stmt->execute()) {
                        // Password updated successfully
                        unset($_SESSION['reset_email']); // Clear the email from session
                        $_SESSION['reset_success'] = "Your password has been reset successfully. Please log in.";
                        header("Location: restaurant_owner_login.php");
                        exit();
                    } else {
                         error_log("Execute failed (update password): " . $update_stmt->error);
                        $message = "Failed to update password. Please try again.";
                    }
                    $update_stmt->close();
                }
            } else {
                // Invalid OTP or expired
                $message = "Invalid or expired OTP. Please request a new one if needed.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Restaurant Owner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .reset-container { max-width: 450px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="card shadow-sm">
                 <div class="card-header bg-secondary text-white">
                    <h3 class="text-center mb-0">Enter OTP & New Password</h3>
                 </div>
                <div class="card-body p-4">
                     <p class="text-muted text-center">An OTP was sent to <strong><?php echo htmlspecialchars($email); ?></strong>. Enter it below along with your new password.</p>
                     <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="reset_password_owner.php">
                         <!-- Hidden email field might not be strictly necessary if using session, but can be useful -->
                         <!-- <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>"> -->

                        <div class="mb-3">
                            <label for="otp" class="form-label">Enter OTP</label>
                            <div class="input-group">
                                 <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="text" class="form-control" id="otp" name="otp" required pattern="\d{6}" title="Enter the 6-digit OTP">
                             </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                             <div class="input-group">
                                 <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                             </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                             <div class="input-group">
                                 <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                             </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                </div>
                 <div class="card-footer text-center">
                    <small><a href="restaurant_owner_login.php">Back to Login</a></small> |
                    <small><a href="forgot_password_owner.php">Resend OTP?</a></small> <!-- Links back to start -->
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>