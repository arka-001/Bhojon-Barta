<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <!-- Add Bootstrap CSS link if not already included -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Add your custom styles if any -->
    <style>
        /* Custom styles for better look and feel */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 30px;
            background-color: #fff;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="text-center mb-4">Forgot Password</h2>
                    <?php
                    use PHPMailer\PHPMailer\PHPMailer;
                    use PHPMailer\PHPMailer\SMTP;
                    use PHPMailer\PHPMailer\Exception;

                    require 'phpmailer/src/Exception.php';
                    require 'phpmailer/src/PHPMailer.php';
                    require 'phpmailer/src/SMTP.php';

                    session_start();
                    error_reporting(0);
                    include("connection/connect.php");

                    $message = "";

                    if (isset($_POST['submit'])) {
                        $email = $_POST['email'];

                        // Check if the email exists in the database
                        $check_email = mysqli_query($db, "SELECT email, f_name, l_name FROM users WHERE email = '$email'");

                        if (mysqli_num_rows($check_email) > 0) {
                            $row = mysqli_fetch_assoc($check_email);
                            $firstname = $row['f_name'];
                            $lastname = $row['l_name'];
                            $email = $row['email'];

                            // Generate OTP
                            $otp = rand(100000, 999999);
                            //$_SESSION['reset_email'] = $email;  // Store email in session
                            //$_SESSION['otp'] = $otp;   //Store otp session

                              // Save OTP and email in database
                                $update_query = mysqli_query($db, "UPDATE users SET otp = '$otp' WHERE email = '$email'");

                                if($update_query){

                            // Send OTP via email
                            $mail = new PHPMailer(true);

                            try {
                                //Server settings
                                $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
                                $mail->isSMTP();                                            //Send using SMTP
                                $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
                                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                                $mail->Username   = 'bhojonbarta@gmail.com';                     //SMTP username
                                $mail->Password   = 'zyys vops vyua zetu';                               //SMTP password
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                                $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                                //Recipients
                                $mail->setFrom('bhojonbarta@gmail.com', 'Food Delivery System'); // Replace with your email and name
                                $mail->addAddress($email, $firstname . ' ' . $lastname);     //Add a recipient

                                //Content
                                $mail->isHTML(true);                                  //Set email format to HTML
                                $mail->Subject = 'Password Reset OTP';
                                $mail->Body    = 'Dear ' . $firstname . ' ' . $lastname . ',<br><br>Your OTP for password reset is: <b>' . $otp . '</b>. Please use this OTP to reset your password.<br><br>Thank you,<br>Food Delivery System';
                                $mail->AltBody = 'Dear ' . $firstname . ' ' . $lastname . ', Your OTP for password reset is: ' . $otp . '. Please use this OTP to reset your password. Thank you, Food Delivery System';

                                $mail->send();

                                 // Store email in session
                                    $_SESSION['reset_email'] = $email;

                                // Redirect to OTP verification page
                                header("Location: verify-otp.php");  // New page verify-otp.php
                                exit();
                            } catch (Exception $e) {
                                $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                            }
                        }
                         else {
                                $message = "Error: OTP faild update try agine ! ";
                            }
                        } else {
                            $message = "Email address not found in our system.";
                        }
                    }
                    ?>

                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary btn-block">Send OTP</button>
                    </form>
                    <br>
                    <p class="text-center">Remember your password? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap JS and Popper.js if needed -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>