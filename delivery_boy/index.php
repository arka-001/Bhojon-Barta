<?php
include("../connection/connect.php");
error_reporting(0); // Use E_ALL for development, 0 for production
session_start();
$login_error = "";

// Redirect if already logged in
if (!empty($_SESSION["db_id"])) {
    header("Location: delivery_boy_dashboard.php"); // Redirect to dashboard
    exit();
}


if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Use prepared statement to prevent SQL injection
        $loginquery = "SELECT db_id, db_name, db_password FROM delivery_boy WHERE db_email = ?";
        $stmt = mysqli_prepare($db, $loginquery);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt); // Close statement early

            if ($row) {
                // Verify the hashed password
                if (password_verify($password, $row['db_password'])) {
                    $_SESSION["db_id"] = $row['db_id'];
                    $_SESSION["db_name"] = $row['db_name']; // Store name too if needed
                    session_regenerate_id(true); // Prevent session fixation
                    header("Location: delivery_boy_dashboard.php"); // Redirect to dashboard
                    exit();
                } else {
                    $login_error = "Invalid Email or Password.";
                }
            } else {
                $login_error = "Invalid Email or Password."; // Generic error for non-existent email
            }
        } else {
            error_log("DB Error preparing login statement: " . mysqli_error($db));
            $login_error = "Login service unavailable. Please try again later.";
        }
    } else {
        $login_error = "Please enter both Email and Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Login</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #4F80E2;
            --primary-light: #e9effd;
            --primary-dark: #3e6ac4;
            --text-color: #333;
            --text-light: #777;
            --border-color: #e0e0e0;
            --input-bg: #fff;
            --card-bg: #ffffff;
            --body-bg: #f7f8fc;
            --error-bg: #fdecea;
            --error-text: #9b2c2c;
            --error-border: #f5c6cb;
        }

        body {
            font-family: 'Roboto', sans-serif;
            font-weight: 400;
            line-height: 1.6;
            background-color: var(--body-bg);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 45px 35px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04), 0 10px 20px rgba(79, 128, 226, 0.08);
            text-align: center;
        }

        h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .thumbnail {
            margin-top: 20px;
            margin-bottom: 35px;
            line-height: 1;
        }

        .thumbnail .fas.fa-user-circle {
            font-size: 6rem;
            color: var(--primary-light);
            position: relative;
            display: inline-block;
        }

        /* Simple user icon inside the circle */
        .thumbnail .fas.fa-user-circle::before {
            content: "\f007"; /* Font Awesome user icon */
            font-family: "Font Awesome 5 Free";
            font-weight: 900; /* Solid icon */
            color: var(--primary-color);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.4em; /* Adjust size of inner icon */
        }

        .alert { margin-bottom: 20px; text-align: left; font-size: 0.875rem; padding: 12px 18px; border-radius: 8px; border-width: 1px; border-style: solid; display: flex; align-items: center; }
        .alert-danger { background-color: var(--error-bg); border-color: var(--error-border); color: var(--error-text); }
        .alert-danger .close { color: var(--error-text); opacity: 0.6; }
        .alert-danger .close:hover { opacity: 1; }
        .alert .close { padding: 12px 18px; font-size: 1.3rem; line-height: 1; }
        .alert .fas { margin-right: 10px; font-size: 1.1em; }

        .login-form { margin-top: 20px; }

        .login-form input[type="email"],
        .login-form input[type="password"],
        .login-form input[type="text"] /* For password toggle */
        {
            font-family: 'Roboto', sans-serif;
            font-weight: 400;
            font-size: 0.95rem;
            outline: 0;
            background: var(--input-bg);
            width: 100%;
            border: none;
            border-bottom: 1px solid var(--border-color);
            border-radius: 0;
            margin: 0; /* Removed bottom margin here */
            padding: 12px 5px;
            box-sizing: border-box;
            color: var(--text-color);
            transition: border-color 0.3s ease;
        }

        .login-form input[type="email"] {
            margin-bottom: 25px; /* Add margin back to email field */
        }

        .login-form input[type="email"]:focus,
        .login-form input[type="password"]:focus,
        .login-form input[type="text"]:focus {
            border-bottom-color: var(--primary-color);
        }

        .login-form input::placeholder { color: var(--text-light); opacity: 1; }
        .login-form input:-ms-input-placeholder { color: var(--text-light); }
        .login-form input::-ms-input-placeholder { color: var(--text-light); }

        .password-container {
            position: relative;
            margin-bottom: 25px; /* Add margin to the container */
        }

        .password-container .toggle-password {
            position: absolute;
            right: 5px;
            /* Vertically center the icon */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }
        .password-container .toggle-password:hover { color: var(--primary-color); }

        .login-form input[type="submit"] {
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.8px;
            outline: 0;
            background: var(--primary-color);
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 14px;
            color: #FFFFFF;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.2s ease;
            margin-top: 10px;
        }

        .login-form input[type="submit"]:hover,
        .login-form input[type="submit"]:focus {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 128, 226, 0.2);
        }
        .login-form input[type="submit"]:active {
            transform: translateY(0px);
            box-shadow: 0 2px 6px rgba(79, 128, 226, 0.15);
            background-color: var(--primary-dark);
        }

        .message { margin: 30px 0 0; color: var(--text-light); font-size: 0.875rem; font-weight: 400; }
        .message a { color: var(--primary-color); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
        .message a:hover { color: var(--primary-dark); text-decoration: underline; }
        .forgot-link {
            margin-top: 15px;
            margin-bottom: -15px; /* Pulls the next element up slightly */
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Delivery Partner Login</h1>
        <div class="thumbnail">
            <i class="fas fa-user-circle"></i>
        </div>

        <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle"></i>
            <?php echo htmlspecialchars($login_error); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
        <?php endif; ?>

        <form class="login-form" action="index.php" method="post" novalidate>
            <input type="email" placeholder="Email" name="email" required aria-label="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
            <div class="password-container">
                <input type="password" placeholder="Password" name="password" id="password-field" required aria-label="Password"/>
                <span class="toggle-password" title="Show/Hide Password">
                    <i class="fa fa-eye"></i>
                </span>
            </div>
            <input type="submit" name="submit" value="Login" />
            <!-- Forgot Password Link -->
            <p class="message forgot-link"><a href="forgot_password.php">Forgot Password?</a></p>
        </form>
        <p class="message">Interested in becoming a Delivery Partner? <a href="delivery_boy_join.php">Join Us!</a></p>
    </div>

    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password toggle
            const togglePassword = document.querySelector('.toggle-password');
            const passwordField = document.querySelector('#password-field');
            if (togglePassword && passwordField) {
                togglePassword.addEventListener('click', function (e) {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    // Toggle icon
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }

            // Auto-dismiss alert
            window.setTimeout(function() {
                $(".alert.alert-dismissible").fadeTo(500, 0.8).slideUp(500, function(){
                    // Optional: $(this).remove();
                });
            }, 6000); // 6 seconds
        });
    </script>
</body>
</html>