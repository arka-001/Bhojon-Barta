<?php
session_start();
include("connection/connect.php");

$message = "";

if(!isset($_SESSION['reset_email'])) {
    header("Location: login.php"); // Redirect if email not in session
    exit();
}

$email = $_SESSION['reset_email'];

if(isset($_POST['check-reset-otp'])) {
    $otp = filter_var($_POST['otp'], FILTER_SANITIZE_NUMBER_INT);

    // Verify OTP in database (using prepared statement)
    $sql = "SELECT email FROM users WHERE email = ? AND otp = ? AND otp_expiry > ?";
    $stmt = mysqli_prepare($db, $sql);

    if ($stmt) {
        $now = time();
        mysqli_stmt_bind_param($stmt, "sii", $email, $otp, $now);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            // OTP is valid. Clear OTP and expiry (using prepared statement)
            $update_query = "UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = ?";
            $update_stmt = mysqli_prepare($db, $update_query);

            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "s", $email);

                if (mysqli_stmt_execute($update_stmt)) {
                    // Indicate successful OTP verification

                    mysqli_stmt_close($update_stmt);
                    mysqli_stmt_close($stmt);

                    // Secure way to regenerate session id after sensitive changes
                    session_regenerate_id(true);
                    $_SESSION['reset_success'] = true; // Indicate successful OTP verification

                    header("Location: new-password.php");
                    exit();

                } else {
                    $message = "Failed to clear OTP: " . mysqli_stmt_error($update_stmt);
                }
                mysqli_stmt_close($update_stmt);

            } else {
                $message = "Failed to prepare clear OTP statement: " . mysqli_error($db);
            }
        } else {
            $message = "Invalid or expired OTP.";
        }
        mysqli_stmt_close($stmt); // Close statement
    } else {
        $message = "Failed to prepare OTP verification statement: " . mysqli_error($db);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-4 offset-md-4 form">
            <form action="reset-code.php" method="POST" autocomplete="off">
                <h2 class="text-center">OTP Verification</h2>
                <p class="text-center">Enter the OTP sent to your email address.</p>
                <?php if ($message): ?>
                    <div class="alert alert-danger text-center"><?php echo $message; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <input class="form-control" type="number" name="otp" placeholder="Enter OTP" required>
                </div>
                <div class="form-group">
                    <input class="form-control button" type="submit" name="check-reset-otp" value="Submit">
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>