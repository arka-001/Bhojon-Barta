<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

include("../connection/connect.php");
error_reporting(0);

if (isset($_GET['email'])) {
    $email = $_GET['email'];
} else {
    header("Location: forgot_password.php");  // If no email, redirect
    exit();
}

$alert_message = null;  // Initialize alert message
$resend_success = false; // Flag for resend success
$resend_error = false;   // Flag for resend error

if (isset($_POST['verify'])) {
    $otp = $_POST['otp'];

    $verify_query = "SELECT adm_id FROM admin WHERE email = ? AND code = ? AND date >= NOW() - INTERVAL 10 MINUTE";  // Check OTP and time
    $stmt_verify = mysqli_prepare($db, $verify_query);
    mysqli_stmt_bind_param($stmt_verify, "ss", $email, $otp);
    mysqli_stmt_execute($stmt_verify);
    $result = mysqli_stmt_get_result($stmt_verify);

    if (mysqli_num_rows($result) > 0) {
        // OTP is valid.  Clear OTP and redirect to reset password form
        $clear_otp_query = "UPDATE admin SET code = NULL, date = NULL WHERE email = ?";
        $stmt_clear_otp = mysqli_prepare($db, $clear_otp_query);
        mysqli_stmt_bind_param($stmt_clear_otp, "s", $email);
        mysqli_stmt_execute($stmt_clear_otp);
        mysqli_stmt_close($stmt_clear_otp);

        header("Location: reset_password_form.php?email=" . urlencode($email));
        exit();

    } else {
        $alert_message = "Invalid OTP or OTP has expired!";  // Store the message in a variable
    }
    mysqli_stmt_close($stmt_verify);
}

// Handle OTP Resend
if (isset($_POST['resend'])) {
    // Generate a new OTP
    $new_otp = rand(100000, 999999);

    // Update the database with the new OTP
    $update_query = "UPDATE admin SET code = ?, date = NOW() WHERE email = ?";
    $stmt_update = mysqli_prepare($db, $update_query);
    mysqli_stmt_bind_param($stmt_update, "ss", $new_otp, $email);
    $update_result = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($update_result) {
        // Send the new OTP via Email
        $mail = new PHPMailer(true);

        try {
            //Server settings (adjust these for your SMTP server)
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bhojonbarta@gmail.com';
            $mail->Password   = 'zyys vops vyua zetu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom('bhojonbarta@gmail.com', 'Admin Password Reset');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset - New OTP Verification';
            $mail->Body    = 'Your new OTP for password reset is: <b>' . $new_otp . '</b>';
            $mail->AltBody = 'Your new OTP for password reset is: ' . $new_otp;

            $mail->send();

            $resend_success = true;  //Set success flag
            $alert_message = "New OTP has been sent to your email address.";


        } catch (Exception $e) {
            $resend_error = true;   // set error flag
            $alert_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
       $resend_error = true;   // set error flag
       $alert_message = "Database error updating OTP!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="css/login.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Basic styling */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 400px;
            margin: 100px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
            color: #333;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        .submit-button {
            background-color: #28a745;
            color: #fff;
            padding: 15px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            transition: background-color 0.2s ease;
        }

        .submit-button:hover {
            background-color: #218838;
        }

        .resend-button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s ease;
            margin-top: 10px;
        }

        .resend-button:hover {
            background-color: #0056b3;
        }

        .message {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify OTP</h1>

        <!-- Bootstrap Alert -->
        <?php if (isset($alert_message)): ?>
            <div class="alert <?php echo ($resend_success ? 'alert-success' : 'alert-danger'); ?> alert-dismissible fade show" role="alert">
                <?php echo $alert_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        <?php endif; ?>

        <form action="reset_password_verify_otp.php?email=<?php echo urlencode($email); ?>" method="post">
            <div class="form-group">
                <label for="otp">Enter the OTP sent to your email:</label>
                <input type="text" id="otp" name="otp" required>
            </div>
            <button type="submit" class="submit-button" name="verify">Verify</button>
        </form>

        <!-- Resend OTP Button -->
        <form action="reset_password_verify_otp.php?email=<?php echo urlencode($email); ?>" method="post">
            <button type="submit" class="resend-button" name="resend">Resend OTP</button>
        </form>

        <p class="message">Back to <a href="forgot_password.php">Forgot Password</a></p>
    </div>

    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>