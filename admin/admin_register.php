<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

include("../connection/connect.php");
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="css/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Custom CSS for the registration form */
        body {
            background-color: #f5f5f5;
            font-family: sans-serif;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative; /* Required for positioning the toggle icon */
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group label .required {
            color: red;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
            color: #333;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        .register-button {
            background-color: #3498db;
            color: #fff;
            padding: 15px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            transition: background-color 0.2s ease;
        }

        .register-button:hover {
            background-color: #2980b9;
        }

        .message {
            text-align: center;
            margin-top: 20px;
        }

        .otp-input { /* Style for OTP input */
            width: 60px;
            margin: 0 5px;
            text-align: center;
            font-size: 18px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 65%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <form class="register-form" action="" method="post" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Name <span class="required">*</span></label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-eye password-toggle" id="password-toggle" onclick="togglePasswordVisibility('password')"></i>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-eye password-toggle" id="confirm-password-toggle" onclick="togglePasswordVisibility('confirm_password')"></i>
            </div>
            <button type="submit" class="register-button" name="register">Register</button>
        </form>
        <p class="message">Already registered? <a href="index.php">Login</a></p>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (password !== confirm_password) {
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords do not match!',
                    text: 'Please make sure your passwords match.'
                });
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }

        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId + '-toggle'); // Corrected icon ID
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

<?php

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate Password Match (Already done in JavaScript, but good to double-check on server-side)
    if ($password != $confirm_password) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Passwords do not match!',
            }).then(function() {
                window.location = 'admin_register.php';
            });
        </script>";
        exit();
    }

    // Check if username already exists
    $check_username_query = "SELECT COUNT(*) FROM admin WHERE username = ?";
    $stmt_username = mysqli_prepare($db, $check_username_query);
    mysqli_stmt_bind_param($stmt_username, "s", $username);
    mysqli_stmt_execute($stmt_username);
    $username_result = mysqli_stmt_get_result($stmt_username);
    $username_count = mysqli_fetch_row($username_result)[0];
    mysqli_stmt_close($stmt_username);

    if ($username_count > 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Username already exists. Please choose a different username.',
            }).then(function() {
                window.location = 'admin_register.php';
            });
        </script>";
        exit();
    }

    // Check if email already exists
    $check_email_query = "SELECT COUNT(*) FROM admin WHERE email = ?";
    $stmt_email = mysqli_prepare($db, $check_email_query);
    mysqli_stmt_bind_param($stmt_email, "s", $email);
    mysqli_stmt_execute($stmt_email);
    $email_result = mysqli_stmt_get_result($stmt_email);
    $email_count = mysqli_fetch_row($email_result)[0];
    mysqli_stmt_close($stmt_email);

    if ($email_count > 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Email already exists. Please use a different email.',
            }).then(function() {
                window.location = 'admin_register.php';
            });
        </script>";
        exit();
    }

    // Generate OTP
    $otp = rand(100000, 999999);

    // Store username, email and password in the session
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
        $mail->Port       = 465;   // Or 587 for 'tls'

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

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Registration successful! An OTP has been sent to your email. Please verify.',
            }).then(function() {
                window.location = 'admin_verify_otp.php?email=" . urlencode($email) . "';
            });
        </script>";

    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Message could not be sent. Mailer Error: " . $mail->ErrorInfo . "',
            }).then(function() {
                window.location = 'admin_register.php';
            });
        </script>";
    }

    mysqli_close($db);
}

?>

</body>
</html>