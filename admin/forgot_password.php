<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

include("../connection/connect.php");
error_reporting(E_ALL);  // Enable all error reporting for debugging
ini_set('display_errors', 1); // Ensure errors are displayed
ob_start(); // Add output buffering at the top

if (isset($_POST['submit'])) {
    $email = $_POST['email'];

    $check_email_query = "SELECT adm_id, username FROM admin WHERE email = ?";
    $stmt_check_email = mysqli_prepare($db, $check_email_query);

    if ($stmt_check_email === false) {
        echo "Error preparing statement: " . mysqli_error($db) . "<br>"; // Keep error logging
        exit; // Stop execution if there's a database error
    }

    mysqli_stmt_bind_param($stmt_check_email, "s", $email);
    mysqli_stmt_execute($stmt_check_email);
    $result = mysqli_stmt_get_result($stmt_check_email);

    if ($result === false) {
        echo "Error getting result: " . mysqli_error($db) . "<br>"; // Keep error logging
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_check_email);

    if (!$row) {
        // SweetAlert for email not found
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js'></script>"; // Specific Version
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() { // Ensure DOM is ready
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Email address not found!',
                });
            });
        </script>";
    } else {
        $otp = rand(100000, 999999);  // Generate OTP

        // Store OTP in database
        $update_query = "UPDATE admin SET code = ?, date = NOW() WHERE email = ?";
        $stmt_update = mysqli_prepare($db, $update_query);

         if ($stmt_update === false) {
            echo "Error preparing update statement: " . mysqli_error($db) . "<br>"; // Keep error logging
            exit;
         }

        mysqli_stmt_bind_param($stmt_update, "ss", $otp, $email);
        $update_result = mysqli_stmt_execute($stmt_update);

         if ($update_result === false) {
            echo "Error executing update statement: " . mysqli_error($db) . "<br>"; // Keep error logging
            exit;
         }

        mysqli_stmt_close($stmt_update);

        if ($update_result) {
            // Send OTP via Email
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
                $mail->addAddress($email, $row['username']); // Using the username from the database

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - OTP Verification';
                $mail->Body    = 'Your OTP for password reset is: <b>' . $otp . '</b>';
                $mail->AltBody = 'Your OTP for password reset is: ' . $otp;

                $mail->send();

                 // SweetAlert for successful OTP send and redirection
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js'></script>"; // Specific version
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Sent!',
                            text: 'An OTP has been sent to your email address.',
                            showConfirmButton: false,
                            timer: 2000 // Auto-close after 2 seconds
                        }).then(() => {
                            window.location.href = 'reset_password_verify_otp.php?email=" . urlencode($email) . "';
                        });
                    });
                </script>";
                exit();


            } catch (Exception $e) {
                 // SweetAlert for mailer error
                 echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js'></script>"; // Specific version
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                         Swal.fire({
                             icon: 'error',
                             title: 'Error!',
                             text: 'Message could not be sent. Mailer Error: {$mail->ErrorInfo}',
                         });
                    });
                </script>";
            }
        } else {
             // SweetAlert for database error
              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js'></script>"; // Specific Version
            echo "<script>
                 document.addEventListener('DOMContentLoaded', function() {
                     Swal.fire({
                         icon: 'error',
                         title: 'Error!',
                         text: 'Database error updating OTP!',
                     });
                 });
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/login.css">
     <!-- Ensure this is within the <head> and before any other scripts that use Swal -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script> <!-- SweetAlert2 -->

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

        .form-group input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
            color: #333;
        }

        .form-group input[type="email"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        .submit-button {
            background-color: #007bff;
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
        <h1>Forgot Password</h1>
        <form action="forgot_password.php" method="post">
            <div class="form-group">
                <label for="email">Enter your registered email address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="submit-button" name="submit">Submit</button>
        </form>
        <p class="message">Back to <a href="index.php">Login</a></p>
    </div>
</body>
</html>