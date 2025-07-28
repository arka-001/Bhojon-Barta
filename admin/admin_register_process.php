<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

include("../connection/connect.php");
session_start(); // Start the session at the beginning of the file

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        echo "<div class='alert alert-danger' role='alert'>Passwords do not match.</div>";
        echo "<script>setTimeout(function(){ window.location='admin_register.php'; }, 3000);</script>";
        exit();
    }

    $check_username_query = "SELECT COUNT(*) FROM admin WHERE username = ?";
    $check_email_query = "SELECT COUNT(*) FROM admin WHERE email = ?";

    $stmt_username = mysqli_prepare($db, $check_username_query);
    mysqli_stmt_bind_param($stmt_username, "s", $username);
    mysqli_stmt_execute($stmt_username);
    $username_result = mysqli_stmt_get_result($stmt_username);
    $username_count = mysqli_fetch_row($username_result)[0];
    mysqli_stmt_close($stmt_username);

    $stmt_email = mysqli_prepare($db, $check_email_query);
    mysqli_stmt_bind_param($stmt_email, "s", $email);
    mysqli_stmt_execute($stmt_email);
    $email_result = mysqli_stmt_get_result($stmt_email);
    $email_count = mysqli_fetch_row($email_result)[0];
    mysqli_stmt_close($stmt_email);

    if ($username_count > 0) {
        echo "<div class='alert alert-danger' role='alert'>Username already exists. Please choose a different username.</div>";
        echo "<script>setTimeout(function(){ window.location='admin_register.php'; }, 3000);</script>";
        exit();
    }

    if ($email_count > 0) {
        echo "<div class='alert alert-danger' role='alert'>Email already exists. Please use a different email.</div>";
        echo "<script>setTimeout(function(){ window.location='admin_register.php'; }, 3000);</script>";
        exit();
    }

    // Generate OTP
    $otp = rand(100000, 999999);

    // Store username and password in the session
    $_SESSION['reg_username'] = $username;
    $_SESSION['reg_password'] = $password;
    $_SESSION['reg_email'] = $email;

    // Send OTP via Email
    $mail = new PHPMailer(true);

    try {
        //Server settings (adjust these for your SMTP server)
        $mail->SMTPDebug = 0; // Disable debugging (set to 2 for verbose debugging)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Replace with your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bhojonbarta@gmail.com'; // Replace with your SMTP username
        $mail->Password   = 'zyys vops vyua zetu'; // Replace with your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // Use 'ssl' or 'tls'
        $mail->Port       = 465;   // Or 465 for 'ssl'

        //Recipients
        $mail->setFrom('from@example.com', 'Admin Registration'); // Replace with your "from" email
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Admin Registration - OTP Verification';
        $mail->Body    = 'Your OTP for admin registration is: <b>' . $otp . '</b>';
        $mail->AltBody = 'Your OTP for admin registration is: ' . $otp;

        $mail->send();

        // Insert user data and OTP into database (including date), *without* the password initially
        $insert_query = "INSERT INTO admin (username, email, code, date) VALUES (?, ?, ?, NOW())";  // No password here!
        $stmt_insert = mysqli_prepare($db, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, "sss", $username, $email, $otp);
        $insert_result = mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);


        echo "<div class='alert alert-success' role='alert'>Registration successful! An OTP has been sent to your email. Please verify.</div>";
        echo "<script>setTimeout(function(){ window.location='admin_verify_otp.php?email=" . urlencode($email) . "'; }, 3000);</script>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger' role='alert'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        echo "<script>setTimeout(function(){ window.location='admin_register.php'; }, 5000);</script>"; // Return to registration page after error.
    }

    mysqli_close($db);
} else {
    header("Location: admin_register.php");
    exit();
}
?>