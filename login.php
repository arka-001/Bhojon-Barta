<?php
// START OF PHP LOGIC BLOCK
// Ensure connection/connect.php exists and is correctly configured
// For example:
// $db = mysqli_connect("localhost", "your_user", "your_password", "your_database");
// if (!$db) { die("Connection failed: " . mysqli_connect_error()); }
include("connection/connect.php");

// error_reporting(0); // Original setting
ini_set('display_errors', 1); // For development
error_reporting(E_ALL);     // For development

session_start(); // Start session once at the top

$message = "";
$login_identifier_value = ""; // To retain input value

if (isset($_POST['submit'])) {
    $login_identifier = trim($_POST['username']);
    $password = $_POST['password'];
    $login_identifier_value = $login_identifier; // Store for pre-filling

    if (!empty($login_identifier) && !empty($password)) {
        $sql = "SELECT u_id, username, password, email, email_verified, status FROM users WHERE (username = ? OR email = ?) AND status = 1";
        $stmt = mysqli_prepare($db, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $login_identifier, $login_identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row) {
                $stored_password = $row['password'];
                $password_match = false;

                // Check if the stored password is a bcrypt hash
                if (preg_match('/^\$2y\$/', $stored_password)) {
                    if (password_verify($password, $stored_password)) {
                        $password_match = true;
                    }
                }
                // Fallback for plain text passwords (consider migrating these)
                elseif ($password === $stored_password) {
                    $password_match = true;
                    // Optionally, rehash and update the password here if it's plain text
                    // $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // $update_sql = "UPDATE users SET password = ? WHERE u_id = ?";
                    // $update_stmt = mysqli_prepare($db, $update_sql);
                    // mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $row['u_id']);
                    // mysqli_stmt_execute($update_stmt);
                    // mysqli_stmt_close($update_stmt);
                }


                if ($password_match) {
                    if ($row['email_verified'] != 1 && !empty($row['email'])) {
                         $message = "<i class='fas fa-exclamation-triangle mr-2'></i>Please verify your email address before logging in. <a href='resend_verification.php?email=".urlencode($row['email'])."'>Resend verification email?</a>";
                         // Note: You'll need to create resend_verification.php
                    } else {
                        $_SESSION["user_id"] = $row['u_id'];
                        $_SESSION["username"] = $row['username']; // Store username for display if needed
                        // Redirect to index.php or a intended page after login
                        $redirect_url = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'index.php';
                        unset($_SESSION['redirect_to']); // Clear the stored redirect URL
                        header("Location: " . $redirect_url);
                        exit();
                    }
                } else {
                    $message = "<i class='fas fa-times-circle mr-2'></i>Invalid Username/Email or Password!";
                }
            } else {
                $message = "<i class='fas fa-times-circle mr-2'></i>Invalid Username/Email or Password, or your account may be inactive or not yet verified.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "<i class='fas fa-database mr-2'></i>Database error. Please try again later. Details: " . mysqli_error($db);
        }
    } else {
        $message = "<i class='fas fa-exclamation-circle mr-2'></i>Please enter both Username/Email and Password.";
    }
}
// END OF PHP LOGIC BLOCK
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Your Food App</title>
    <link rel="icon" type="image/png" href="images/favicon.png"> <!-- Add a favicon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
    <link rel='stylesheet prefetch'
        href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap'>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet"> <!-- Keep if used by animsition or other elements -->
    <link href="css/style.css" rel="stylesheet"> <!-- Your base theme style -->

    <style>
        :root {
            --primary-color: #6a11cb; /* Deep purple */
            --primary-color-darker: #2575fc; /* Royal blue for gradient end */
            --secondary-color: #5c4ac7; /* Original purple, can be used as accent */
            --text-color: #333;
            --text-color-light: #555;
            --border-color: #e0e0e0;
            --background-light: #f8f9fa;
            --white: #ffffff;
            --danger-bg: #f8d7da;
            --danger-text: #721c24;
            --danger-border: #f5c6cb;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
            line-height: 1.6;
            /* Enable smooth scrolling for anchor links (like in alerts) */
            scroll-behavior: smooth;
        }

        .login-page-wrapper {
            background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('images/img/pimg.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding-top: 100px; /* Increased space for header */
            padding-bottom: 60px; /* Space for footer */
        }

        .login-container {
            background-color: var(--white);
            padding: 40px 45px;
            border-radius: 15px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 450px; /* Slightly wider */
            text-align: center;
            animation: fadeInScale 0.6s ease-out forwards;
            border: 1px solid #efefef;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-header {
            margin-bottom: 30px;
        }
        .login-header .logo-container {
            margin-bottom: 20px;
        }
        .login-header .logo-container img {
            max-height: 50px; /* Adjust if you have a logo here */
            /* filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1)); */ /* Optional logo shadow */
        }
        .login-header h2 {
            font-size: 30px;
            font-weight: 700; /* Bolder */
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        .login-header p {
            font-size: 16px;
            color: var(--text-color-light);
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }

        .form-control-custom {
            width: 100%;
            padding: 15px 20px;
            padding-left: 50px; /* Space for icon */
            border: 1px solid var(--border-color);
            border-radius: 10px; /* More rounded */
            box-sizing: border-box;
            font-size: 16px;
            color: var(--text-color);
            background-color: #fdfdfd;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }

        .form-control-custom:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb, 106, 17, 203), 0.25); /* Define --primary-color-rgb if using rgba like this */
            background-color: var(--white);
            outline: none;
        }
        .form-control-custom::placeholder {
            color: #aaa;
        }

        .input-icon-wrapper {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px; /* Slightly larger icon */
            transition: color 0.3s ease;
        }
        .form-control-custom:focus + .input-icon, /* This won't work due to sibling selector, JS might be needed or restructure */
        .input-icon-wrapper:focus-within .input-icon { /* Correct way to target icon on input focus */
             color: var(--primary-color);
        }


        .password-input-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 17px;
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }

        .btn-login {
            background-image: linear-gradient(to right, var(--primary-color) 0%, var(--primary-color-darker) 100%);
            color: var(--white);
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-size: 18px;
            font-weight: 600; /* Bolder */
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: all 0.35s ease;
            margin-top: 15px; /* More space above button */
            box-shadow: 0 5px 15px rgba(var(--primary-color-rgb, 106, 17, 203), 0.3);
        }

        .btn-login:hover, .btn-login:focus {
            background-image: linear-gradient(to right, var(--primary-color-darker) 0%, var(--primary-color) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(var(--primary-color-rgb, 106, 17, 203), 0.4);
            outline: none;
        }
        .btn-login:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(var(--primary-color-rgb, 106, 17, 203), 0.3);
        }

        .extra-links {
            margin-top: 25px;
            font-size: 14.5px;
        }
        .extra-links a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease, text-decoration 0.3s ease;
        }
        .extra-links a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        .forgot-password-link {
            display: block;
            margin-bottom: 18px; /* More space */
            text-align: right;
        }

        .create-account-link {
            display: block;
            color: var(--text-color-light);
        }
        .create-account-link a {
            font-weight: 600;
        }

        .alert-message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 15px;
            text-align: left;
            display: flex; /* For icon alignment */
            align-items: center; /* For icon alignment */
            border: 1px solid transparent;
        }
        .alert-message i { /* Icon styling in alerts */
            margin-right: 10px;
            font-size: 1.2em;
        }
        .alert-danger-custom {
            background-color: var(--danger-bg);
            color: var(--danger-text);
            border-color: var(--danger-border);
        }
        .alert-success-custom {
            background-color: #d1e7dd; /* Bootstrap 5 success bg */
            color: #0f5132;      /* Bootstrap 5 success text */
            border-color: #badbcc; /* Bootstrap 5 success border */
        }
        .alert-message a {
            color: inherit !important; /* Ensure link color matches alert text */
            font-weight: bold;
            text-decoration: underline;
        }
        .alert-message a:hover {
            text-decoration: none;
        }

        /* Header styling to complement login page */
        #header.header-scroll.top-header.headrom {
            background-color: rgba(255, 255, 255, 0.95); /* Slightly transparent white */
            backdrop-filter: blur(5px); /* Frosted glass effect for modern browsers */
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }
        #header .navbar-brand img {
            max-height: 45px; /* Slightly larger logo */
        }
        #header .nav-link {
             color: var(--text-color-light) !important;
             font-weight: 500;
             transition: color 0.3s ease;
        }
        #header .nav-link:hover, #header .nav-link.active { /* Ensure active also gets style */
             color: var(--primary-color) !important;
        }

        /* Utility class */
        .mr-2 { margin-right: 0.5rem; } /* if not defined elsewhere */
    </style>
</head>

<body class="animsition"> <!-- Add animsition class if you plan to use its page transitions -->

    <header id="header" class="header-scroll top-header headrom">
        <nav class="navbar navbar-dark">
            <div class="container">
                <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse"
                    data-target="#mainNavbarCollapse">☰</button>
                <a class="navbar-brand" href="index.php"> <img class="img-rounded" src="images/icn.png" alt="Food App Logo"> </a>
                <div class="collapse navbar-toggleable-md float-lg-right" id="mainNavbarCollapse">
                    <ul class="nav navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="index.php">Home</a> </li>
                        <li class="nav-item"> <a class="nav-link" href="restaurants.php">Restaurants</a> </li>
                        <?php
                        if (empty($_SESSION["user_id"])) {
                            echo '<li class="nav-item"><a href="login.php" class="nav-link active">Login</a> </li>
                                  <li class="nav-item"><a href="registration.php" class="nav-link">Register</a> </li>';
                        } else {
                            echo '<li class="nav-item"><a href="your_orders.php" class="nav-link">My Orders</a> </li>';
                            echo '<li class="nav-item"><a href="logout.php" class="nav-link">Logout</a> </li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="login-page-wrapper">
        <div class="login-container">
            <div class="login-header">
                <!-- Optional: You can put a logo here too -->
                <!-- <div class="logo-container">
                    <img src="images/icn.png" alt="Company Logo">
                </div> -->
                <h2>Welcome Back!</h2>
                <p>Login to continue your delicious journey.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-message <?php
                    // A more robust way to determine class based on message content
                    $is_error = true; // Default to error
                    if (strpos(strtolower($message), 'verify your email') !== false && strpos(strtolower($message), 'resend') !== false) {
                        // This is more of a warning/info, but let's keep it danger for now as it prevents login
                        // Or you could add an alert-warning-custom class
                        $is_error = true;
                    } else if (strpos(strtolower($message), 'success') !== false ) { // Example for a success message
                        $is_error = false;
                    }
                    echo $is_error ? 'alert-danger-custom' : 'alert-success-custom';
                ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" novalidate> <!-- Added novalidate to rely on server-side validation first -->
                <div class="form-group">
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control-custom" placeholder="Username or Email" name="username" required
                               value="<?php echo htmlspecialchars($login_identifier_value); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="password-input-wrapper input-icon-wrapper"> <!-- Added input-icon-wrapper for consistency if lock icon also needs focus color change -->
                         <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control-custom" placeholder="Password" name="password" id="password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="extra-links forgot-password-link">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>

                <button type="submit" name="submit" class="btn-login">Login <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 0.9em;"></i></button>
            </form>

            <div class="extra-links create-account-link">
                <span>Don't have an account?</span> <a href="registration.php">Create one now</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>


    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script> <!-- Updated jQuery -->
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <!-- <script src="js/jquery.sticky.js"></script> --> <!-- If you use sticky header -->
    <!-- <script src="js/foodpicky.js"></script> --> <!-- Your custom theme JS -->


    <script>
        $(document).ready(function() {
            // Animsition initialization (if you use it)
            if ($(".animsition").length) {
                $(".animsition").animsition({
                    inClass: 'fade-in',
                    outClass: 'fade-out',
                    inDuration: 800, // Faster
                    outDuration: 500, // Faster
                    linkElement: '.animsition-link', // Assumes you have links with this class for page transitions
                    loading: true,
                    loadingParentElement: 'body',
                    loadingClass: 'animsition-loading',
                    loadingInner: '<div class="loader-spinner"></div>', // Example simple spinner
                    timeout: false,
                    timeoutCountdown: 5000,
                    onLoadEvent: true,
                    browser: ['animation-duration', '-webkit-animation-duration'],
                    overlay: false,
                    // ... other animsition options
                });
            }


            const togglePasswordIcon = $('#togglePassword');
            const passwordField = $('#password');

            togglePasswordIcon.on('click', function () {
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            // Define --primary-color-rgb for JS usage if needed, or set it directly in CSS
            // For the :root variables, they are CSS variables.
            // For the box-shadow on focus: rgba(var(--primary-color-rgb, 106, 17, 203), 0.25)
            // You can define --primary-color-rgb in :root as:
            // --primary-color-rgb: 106, 17, 203; /* Corresponding to #6a11cb */
            // For #2575fc (royal blue), it would be --primary-color-darker-rgb: 37, 117, 252;
            // This is already handled if you define it in :root. The example below is if you were setting it via JS.
            /*
            function hexToRgb(hex) {
                let r = 0, g = 0, b = 0;
                if (hex.length == 4) { // 3 digits
                    r = parseInt(hex[1] + hex[1], 16);
                    g = parseInt(hex[2] + hex[2], 16);
                    b = parseInt(hex[3] + hex[3], 16);
                } else if (hex.length == 7) { // 6 digits
                    r = parseInt(hex[1] + hex[2], 16);
                    g = parseInt(hex[3] + hex[4], 16);
                    b = parseInt(hex[5] + hex[6], 16);
                }
                return `${r}, ${g}, ${b}`;
            }
            const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
            document.documentElement.style.setProperty('--primary-color-rgb', hexToRgb(primaryColor));
            */
           // For the CSS focus box-shadow to work with rgba(var(--primary-color-rgb), 0.25),
           // ensure this is in your :root in CSS:
           // --primary-color-rgb: 106, 17, 203; /* For #6a11cb */

        });
    </script>
     <div class="loader-spinner" style="display: none;"></div> <!-- For animsition if you customize loadingInner -->
     <style>
        /* Simple spinner for animsition, if you use the loadingInner example */
        .loader-spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 4px solid #fff; /* Or your primary color */
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            position:fixed; /* or absolute within loadingParentElement */
            left:50%;
            top:50%;
            transform: translate(-50%,-50%);
            z-index: 9999; /* Ensure it's on top */
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
     </style>


</body>
</html>