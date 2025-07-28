<?php
session_start();
include("connection/connect.php");

$message = "";

if(!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_success']) || $_SESSION['reset_success'] !== true) {
    header("Location: login.php"); // Redirect if not authorized
    exit();
}

$email = $_SESSION['reset_email'];

if(isset($_POST['change-password'])) {
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if($password !== $cpassword) {
        $message = "Passwords do not match.";
    } else {
        // Hash the new password (using password_hash)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update the password in the database (using prepared statement)
        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($db, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email); // Bind as string

            if (mysqli_stmt_execute($stmt)) {
                // Password updated successfully, clear session variables
                 // Secure way to regenerate session id after sensitive changes
                 session_regenerate_id(true);
                 unset($_SESSION['reset_email']);
                 unset($_SESSION['reset_success']);
                $message = "Password updated successfully. You can now login with your new password.";
            } else {
                $message = "Database error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Failed to prepare password update: " . mysqli_error($db);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-4 offset-md-4 form">
            <form action="new-password.php" method="POST" autocomplete="off">
                <h2 class="text-center">Reset Password</h2>
                <?php if ($message): ?>
                    <div class="alert alert-success text-center"><?php echo $message; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <input class="form-control" type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="form-group">
                    <input class="form-control" type="password" name="cpassword" placeholder="Confirm Password" required>
                </div>
                <div class="form-group">
                    <input class="form-control button" type="submit" name="change-password" value="Change Password">
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>