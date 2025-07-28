<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

include("connection/connect.php");

$error_message = "";
$success_message = "";

if (isset($_POST['submit'])) {
    if (
        empty($_POST['firstname']) ||
        empty($_POST['lastname']) ||
        empty($_POST['email']) ||
        empty($_POST['phone']) ||
        empty($_POST['password']) ||
        empty($_POST['cpassword']) ||
        empty($_POST['username'])
    ) {
        $error_message = "All fields must be Required!";
    } else {
        $check_username = mysqli_query($db, "SELECT username FROM users where username = '" . $_POST['username'] . "' ");
        $check_email = mysqli_query($db, "SELECT email FROM users where email = '" . $_POST['email'] . "' ");

        $phone = $_POST['phone'];
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error_message = "Invalid phone number. Must be 10 digits.";
        } elseif (strlen($_POST['password']) < 6) {
            $error_message = "Password must be at least 6 characters long!";
        } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{6,}$/', $_POST['password'])) {
            $error_message = "Password must contain at least one special character, one alphanumeric character, and be at least 6 characters long!";
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email address!";
        } elseif (strlen($_POST['username']) < 4) {
            $error_message = "Username must be at least 4 characters long!";
        } elseif (mysqli_num_rows($check_username) > 0) {
            $error_message = "Username already exists!";
        } elseif (mysqli_num_rows($check_email) > 0) {
            $error_message = "Email already exists!";
        } elseif ($_POST['password'] != $_POST['cpassword']) {
            $error_message = "Passwords do not match!";
        } else {
            $username = $_POST['username'];
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Generate OTP
            $otp = rand(100000, 999999);

            // Create verification token
            $verification_token = bin2hex(random_bytes(32));

            // Store user data, OTP, and verification token in session
            $_SESSION['reg_username'] = $username;
            $_SESSION['reg_firstname'] = $firstname;
            $_SESSION['reg_lastname'] = $lastname;
            $_SESSION['reg_email'] = $email;
            $_SESSION['reg_phone'] = $phone;
            $_SESSION['reg_password'] = $password;
            $_SESSION['reg_otp'] = $otp;
            $_SESSION['verification_token'] = $verification_token;

            error_log("Session Data being set:" . print_r($_SESSION, true));

            // Send OTP via email
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'bhojonbarta@gmail.com';
                $mail->Password = 'zyys vops vyua zetu';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipients
                $mail->setFrom('your_email@gmail.com', 'Food Delivery System');
                $mail->addAddress($email, $firstname . ' ' . $lastname);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification OTP';
                $mail->Body = 'Dear ' . $firstname . ' ' . $lastname . ',<br><br>Your OTP for email verification is: <b>' . $otp . '</b>. Please use this OTP to verify your email address.<br><br>Thank you,<br>Food Delivery System';
                $mail->AltBody = 'Dear ' . $firstname . ' ' . $lastname . ', Your OTP for email verification is: ' . $otp . '. Please use this OTP to verify your email address. Thank you, Food Delivery System';

                $mail->send();

                $success_message = "Registration successful! Please check your email for OTP verification.";

                // Redirect to OTP verification page
                header("Location: verify_otp.php");
                exit();
            } catch (Exception $e) {
                $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Registration</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg?auto=compress&cs=tinysrgb&w=600') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.75);
            z-index: -1;
        }

        .registration-container {
            max-width: 800px;
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        h2 {
            font-weight: 700;
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #343a40;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.3);
        }

        .btn-primary {
            background: #28a745;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .password-container {
            position: relative;
            margin-bottom: 20px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            font-size: 18px;
            z-index: 1;
            margin-top: 18px;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
            position: relative;
        }

        .password-strength {
            height: 6px;
            margin-top: 5px;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .navbar-nav {
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        .navbar-nav .nav-item {
            margin-left: 15px;
        }

        .navbar-nav .nav-item:first-child {
            margin-left: 0;
        }

        .password-strength.weak {
            background: #dc3545;
            width: 33%;
        }

        .password-strength.medium {
            background: #ffc107;
            width: 66%;
        }

        .password-strength.strong {
            background: #28a745;
            width: 100%;
        }

        @media (max-width: 768px) {
            .registration-container {
                margin: 30px 15px;
                padding: 20px;
            }

            .btn-primary {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            h2 {
                font-size: 1.5rem;
            }

            .form-control {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div style="background-image: url('images/img/pimg.jpg');">
        <header id="header" class="header-scroll top-header headrom">
            <nav class="navbar navbar-dark">
                <div class="container">
                    <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#mainNavbarCollapse">☰</button>
                    <a class="navbar-brand" href="index.php"> <img class="img-rounded" src="images/icn.png" alt=""> </a>
                    <div class="collapse navbar-toggleable-md float-lg-right" id="mainNavbarCollapse">
                        <ul class="nav navbar-nav">
                            <li class="nav-item"> <a class="nav-link active" href="index.php">Home <span class="sr-only">(current)</span></a> </li>
                            <li class="nav-item"> <a class="nav-link active" href="restaurants.php">Restaurants <span class="sr-only"></span></a> </li>
                            <?php
                            if (empty($_SESSION["user_id"])) {
                                echo '<li class="nav-item"><a href="login.php" class="nav-link active">Login</a> </li>
                                      <li class="nav-item"><a href="registration.php" class="nav-link active">Register</a> </li>';
                            } else {
                                echo '<li class="nav-item"><a href="your_orders.php" class="nav-link active">My Orders</a> </li>';
                                echo '<li class="nav-item"><a href="logout.php" class="nav-link active">Logout</a> </li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="page-wrapper">
            <section class="contact-page inner-page">
                <div class="registration-container">
                    <h2>Register Now</h2>

                    <?php if ($error_message != "") { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                    <?php } ?>

                    <?php if ($success_message != "") { ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php } ?>

                    <form action="" method="post" onsubmit="return validateForm()">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="username" oninput="validateUsername()">
                                <span id="username-error" class="error-message"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" name="firstname" id="firstname">
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastname" id="lastname">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="text" class="form-control" name="email" id="email" aria-describedby="emailHelp" oninput="validateEmail()">
                                <span id="email-error" class="error-message"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" id="phone" oninput="validatePhone()">
                                <span id="phone-error" class="error-message"></span>
                            </div>
                            <div class="col-md-6">
                                <div class="password-container">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" oninput="validatePassword()">
                                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('password')" aria-hidden="true"></i>
                                </div>
                                <span id="password-error" class="error-message"></span>
                                <div id="password-strength" class="password-strength"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="password-container">
                                    <label for="cpassword" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="cpassword" id="cpassword" oninput="validateConfirmPassword()">
                                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('cpassword')" aria-hidden="true"></i>
                                </div>
                                <span id="confirm-password-error" class="error-message"></span>
                            </div>
                            <div class="col-12 text-center">
                                <input type="submit" value="Register" name="submit" class="btn btn-primary">
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function validateUsername() {
            const usernameInput = document.getElementById("username");
            const username = usernameInput.value;
            const errorSpan = document.getElementById("username-error");

            if (!username) {
                errorSpan.textContent = "Username is required.";
                return false;
            } else if (username.length < 4) {
                errorSpan.textContent = "Username must be at least 4 characters.";
                return false;
            } else {
                errorSpan.textContent = "";
                return true;
            }
        }

        function validateEmail() {
            const emailInput = document.getElementById("email");
            const email = emailInput.value;
            const errorSpan = document.getElementById("email-error");
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!email) {
                errorSpan.textContent = "Email is required.";
                return false;
            } else if (!emailRegex.test(email)) {
                errorSpan.textContent = "Invalid email address.";
                return false;
            } else {
                errorSpan.textContent = "";
                return true;
            }
        }

        function validatePhone() {
            const phoneInput = document.getElementById("phone");
            const phone = phoneInput.value;
            const errorSpan = document.getElementById("phone-error");
            const phoneRegex = /^[0-9]{10}$/;

            if (!phone) {
                errorSpan.textContent = "Phone number is required.";
                return false;
            } else if (!phoneRegex.test(phone)) {
                errorSpan.textContent = "Invalid phone number (10 digits required).";
                return false;
            } else {
                errorSpan.textContent = "";
                return true;
            }
        }

        function validatePassword() {
            const passwordInput = document.getElementById("password");
            const password = passwordInput.value;
            const errorSpan = document.getElementById("password-error");
            const strengthDiv = document.getElementById("password-strength");

            let strength = 0;
            if (password.length >= 6) strength++;
            if (/[a-zA-Z]/.test(password) && /\d/.test(password)) strength++;
            if (/[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]/.test(password)) strength++;

            if (!password) {
                errorSpan.textContent = "Password is required.";
                strengthDiv.className = "password-strength";
                strengthDiv.style.width = "0%";
                return false;
            } else if (password.length < 6) {
                errorSpan.textContent = "Password must be at least 6 characters long.";
                strengthDiv.className = "password-strength weak";
                strengthDiv.style.width = "33%";
                return false;
            } else if (!/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{6,}$/.test(password)) {
                errorSpan.textContent = "Password must contain at least one special character, one alphanumeric character, and be at least 6 characters long!";
                if (strength === 1) {
                    strengthDiv.className = "password-strength weak";
                    strengthDiv.style.width = "33%";
                } else if (strength === 2) {
                    strengthDiv.className = "password-strength medium";
                    strengthDiv.style.width = "66%";
                } else {
                    strengthDiv.className = "password-strength strong";
                    strengthDiv.style.width = "100%";
                }
                return false;
            } else {
                errorSpan.textContent = "";
                strengthDiv.className = "password-strength strong";
                strengthDiv.style.width = "100%";
                return true;
            }
        }

        function validateConfirmPassword() {
            const passwordInput = document.getElementById("password");
            const confirmPasswordInput = document.getElementById("cpassword");
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const errorSpan = document.getElementById("confirm-password-error");

            if (!confirmPassword) {
                errorSpan.textContent = "Confirm password is required.";
                return false;
            } else if (password !== confirmPassword) {
                errorSpan.textContent = "Passwords do not match.";
                return false;
            } else {
                errorSpan.textContent = "";
                return true;
            }
        }

        function validateForm() {
            let isUsernameValid = validateUsername();
            let isEmailValid = validateEmail();
            let isPhoneValid = validatePhone();
            let isPasswordValid = validatePassword();
            let isConfirmPasswordValid = validateConfirmPassword();

            if (!isUsernameValid || !isEmailValid || !isPhoneValid || !isPasswordValid || !isConfirmPasswordValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please correct the errors in the form.',
                });
                return false;
            }

            return true;
        }

        $(document).ready(function () {
            function getLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition, showError);
                } else {
                    $('#location-message').text("Geolocation is not supported by this browser.");
                }
            }

            function showPosition(position) {
                var latitude = position.coords.latitude;
                var longitude = position.coords.longitude;
                getAddress(latitude, longitude);
            }

            function showError(error) {
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        $('#location-message').text("User denied the request for Geolocation.");
                        break;
                    case error.POSITION_UNAVAILABLE:
                        $('#location-message').text("Location information is unavailable.");
                        break;
                    case error.TIMEOUT:
                        $('#location-message').text("The request to get user location timed out.");
                        break;
                    case error.UNKNOWN_ERROR:
                        $('#location-message').text("An unknown error occurred.");
                        break;
                }
            }

            function getAddress(latitude, longitude) {
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/reverse',
                    type: 'GET',
                    data: {
                        format: 'jsonv2',
                        lat: latitude,
                        lon: longitude
                    },
                    success: function (response) {
                        if (response && response.display_name) {
                        } else {
                            $('#location-message').text('Could not determine address from coordinates.');
                        }
                    },
                    error: function () {
                        $('#location-message').text('Error occurred while getting address.');
                    }
                });
            }

            $('#get-location-btn').click(function () {
                getLocation();
            });
        });

        function togglePasswordVisibility(inputId) {
            var passwordInput = document.getElementById(inputId);
            var eyeIcon = document.querySelector(`#${inputId} + .toggle-password`);

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>