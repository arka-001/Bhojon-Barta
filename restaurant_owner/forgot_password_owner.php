<?php
session_start();
include("../connection/connect.php"); // For $db connection

// --- PHPMailer Inclusion ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Make sure the path is correct relative to this file
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
// --- End PHPMailer Inclusion ---

$message = '';
$message_type = 'danger'; // Default to error

// --- Email Sending Function (Copied from previous script) ---
function sendOtpEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // --- Server settings (Using your provided Gmail details) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bhojonbarta@gmail.com';
        $mail->Password   = 'zyys vops vyua zetu'; // Use your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        // --- End Server Settings ---

        //Recipients
        $mail->setFrom('no-reply@bhojonbarta.com', 'Bhojon Barta Support'); // Change if needed
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error sending OTP to {$toEmail}: {$mail->ErrorInfo}");
        return false;
    }
}
// --- End Email Sending Function ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $message = "Invalid email format provided.";
    } else {
        // Check if email exists in restaurant_owners table
        $stmt = $db->prepare("SELECT owner_id, email FROM restaurant_owners WHERE email = ?");
        if(!$stmt) {
             error_log("Prepare failed (check email): " . $db->error);
             $message = "An internal error occurred.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $owner = $result->fetch_assoc();
            $stmt->close();

            if ($owner) {
                // Generate OTP
                $otp = rand(100000, 999999); // 6-digit OTP
                // Set expiry time (e.g., 10 minutes from now)
                $expiry_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Store OTP and expiry in database
                $update_stmt = $db->prepare("UPDATE restaurant_owners SET reset_otp = ?, reset_otp_expiry = ? WHERE email = ?");
                 if(!$update_stmt) {
                    error_log("Prepare failed (update otp): " . $db->error);
                    $message = "An internal error occurred while preparing the reset process.";
                 } else {
                    $update_stmt->bind_param("sss", $otp, $expiry_time, $email);
                    if ($update_stmt->execute()) {
                        // Send OTP email
                        $subject = "Your Bhojon Barta Password Reset OTP";
                        $ownerName = $owner['name'] ?? 'Owner'; // Use name if available, otherwise default
                        $body = "<p>Dear {$ownerName},</p>";
                        $body .= "<p>You requested a password reset for your Bhojon Barta owner account.</p>";
                        $body .= "<p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>";
                        $body .= "<p>This OTP is valid for 10 minutes.</p>";
                        $body .= "<p>If you did not request this, please ignore this email.</p>";
                        $body .= "<p>Best regards,<br>The Bhojon Barta Team</p>";

                        if (sendOtpEmail($email, $ownerName, $subject, $body)) {
                            // Redirect to the OTP entry page, passing email
                            $_SESSION['reset_email'] = $email; // Store email in session for next step
                            header("Location: reset_password_owner.php");
                            exit();
                        } else {
                            $message = "Failed to send OTP email. Please try again later or contact support.";
                        }
                    } else {
                         error_log("Execute failed (update otp): " . $update_stmt->error);
                        $message = "An internal error occurred during the reset process.";
                    }
                    $update_stmt->close();
                }

            } else {
                $message = "No owner account found with that email address.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Restaurant Owner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .forgot-container { max-width: 450px; margin: 80px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <div class="card shadow-sm">
                 <div class="card-header bg-secondary text-white">
                    <h3 class="text-center mb-0">Reset Owner Password</h3>
                 </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center">Enter your email address and we'll send you an OTP to reset your password.</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                           <i class="fas fa-<?php echo $message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'; ?> me-2"></i>
                           <?php echo htmlspecialchars($message); ?>
                           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="forgot_password_owner.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your registered email" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send OTP</button>
                        </div>
                    </form>
                </div>
                 <div class="card-footer text-center">
                    <small><a href="restaurant_owner_login.php">Back to Login</a></small>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>