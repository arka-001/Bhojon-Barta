<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

if (isset($_GET['email'])) {
    $email = $_GET['email'];
} else {
    header("Location: admin_register.php");
    exit();
}

$otp_error = false;
$registration_success = false;

if (isset($_POST['verify'])) {
    $otp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

    try {
        $verify_query = "SELECT adm_id FROM admin WHERE email = ? AND code = ? AND date >= NOW() - INTERVAL 10 MINUTE";
        $stmt_verify = mysqli_prepare($db, $verify_query);

        if (!$stmt_verify) {
            throw new Exception("Prepare failed: " . mysqli_error($db));
        }

        mysqli_stmt_bind_param($stmt_verify, "ss", $email, $otp);
        mysqli_stmt_execute($stmt_verify);

        if (mysqli_stmt_errno($stmt_verify)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_verify));
        }

        $result = mysqli_stmt_get_result($stmt_verify);

        if (mysqli_num_rows($result) > 0) {

            // Check if session variables are set
            if (!isset($_SESSION['reg_username']) || !isset($_SESSION['reg_password'])) {
                throw new Exception("Session variables 'reg_username' or 'reg_password' are not set.");
            }

            $username = $_SESSION['reg_username'];
            $password = $_SESSION['reg_password'];
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update_query = "UPDATE admin SET password = ?, code = NULL, date = NULL WHERE email = ?";
            $stmt_update = mysqli_prepare($db, $update_query);

            if (!$stmt_update) {
                throw new Exception("Prepare failed: " . mysqli_error($db));
            }

            mysqli_stmt_bind_param($stmt_update, "ss", $hashed_password, $email);
            mysqli_stmt_execute($stmt_update);

            if (mysqli_stmt_errno($stmt_update)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_update));
            }

            mysqli_stmt_close($stmt_update);

            // Clear session variables
            unset($_SESSION['reg_username']);
            unset($_SESSION['reg_password']);
            unset($_SESSION['reg_email']);

            $row = mysqli_fetch_assoc($result);
            $adm_id = $row['adm_id'];

            session_regenerate_id(true); // Regenerate session ID

            $_SESSION["adm_id"] = $adm_id;

            $registration_success = true;

            header("Location: index.php"); // PHP Redirection
            exit();

        } else {
            $otp_error = true;
        }

        mysqli_stmt_close($stmt_verify);

    } catch (Exception $e) {
        // Log the error (do NOT expose to the user)
        error_log("Database error: " . $e->getMessage());
        $otp_error = true; // OR show a generic error message to user (e.g., "An error occurred. Please try again later.")
        // Handle the exception appropriately - display a user-friendly error message or redirect to an error page.
    }
}

// Function to mask the email.
function maskEmail($email) {
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];
    $maskedUsername = substr($username, 0, 3) . str_repeat('*', strlen($username) - 3);
    return $maskedUsername . '@' . $domain;
}

$masked_email = maskEmail($email);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Lighter background */
            color: #495057; /* Darker text color */
            padding-top: 60px; /* Increased top padding */
        }

        .container {
            max-width: 550px; /* Slightly reduced max-width */
            margin: 0 auto;
            background-color: #fff;
            padding: 40px; /* Increased padding */
            border-radius: 12px; /* Softer rounded corners */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Enhanced box shadow */
            text-align: center;
            overflow: hidden;  /* Add overflow hidden for animations */
            position: relative;  /* Set position relative for absolute positioning of alerts */
        }

        h1 {
            text-align: center;
            margin-bottom: 25px; /* Increased margin-bottom */
            color: #343a40; /* Slightly darker heading color */
            font-weight: 600; /* Semi-bold font-weight */
        }

        p {
            margin-bottom: 20px; /* Increased margin-bottom for paragraphs */
            color: #6c757d; /* Slightly lighter paragraph color */
        }

        .otp-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px; /* Increased margin-bottom for OTP container */
        }

        .otp-input {
            width: 48px; /* Slightly larger input boxes */
            height: 48px;
            margin: 0 6px; /* Adjusted margin for better spacing */
            border: 1px solid #ced4da; /* Softer border color */
            border-radius: 8px; /* Rounded corners for input boxes */
            text-align: center;
            font-size: 18px; /* Slightly larger font size */
            outline: none;
            box-shadow: none;
            transition: border-color 0.3s ease, transform 0.1s ease; /* Added transform for subtle animation */
            background-color: #ffffff; /* White background */
            color: #495057; /* Text color */
            font-weight: 500; /* Medium font-weight */
            position: relative; /* Position relative for focus animation */
        }

         .otp-input::before {
            content: "";
            position: absolute;
            top: -3px;
            left: -3px;
            width: calc(100% + 6px);
            height: calc(100% + 6px);
            border-radius: 10px;  /* Match border radius of inputs */
            border: 2px solid transparent;
            animation: pulseBorder 1.5s linear infinite;
            opacity: 0;
            z-index: -1;  /* Place the pseudo-element behind the input */
        }

        .otp-input:focus::before {
            opacity: 1;  /* Make the outline visible on focus */
        }

        @keyframes pulseBorder {
            0% {
                border-color: rgba(0, 123, 255, 0.5);
                transform: scale(1);
            }
            50% {
                border-color: rgba(0, 123, 255, 0.8);
                transform: scale(1.1);
            }
            100% {
                border-color: rgba(0, 123, 255, 0.5);
                transform: scale(1);
            }
        }

        .verify-button {
            background-color: #007bff; /* Modern blue color */
            color: #fff;
            padding: 14px 24px; /* Adjusted padding for button */
            border: none;
            border-radius: 8px; /* Rounded corners for button */
            cursor: pointer;
            font-size: 18px;
            width: auto; /* Adjusted width for button */
            transition: background-color 0.3s ease, transform 0.1s ease; /* Added transform for button */
            font-weight: 500; /* Medium font-weight */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); /* Button shadow */
            position: relative; /* For ripple effect */
            overflow: hidden;
        }

          .verify-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
            transform: translateY(-1px); /* Slight lift on hover */
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15); /* Enhanced shadow on hover */
        }

        /* Ripple Effect CSS */
        .verify-button .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            background-color: rgba(255, 255, 255, 0.4); /* Lighter ripple color */
        }

        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }


        .alert {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            border-radius: 0;
            padding: 15px;
            z-index: 10; /* Ensure the alert is on top */
            opacity: 0;
            transform: translateY(-100%);
            transition: transform 0.4s ease, opacity 0.4s ease;
        }

       .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

       .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            display: none; /* Initially hidden */
        }

        .alert.show {
            transform: translateY(0);
            opacity: 1;
        }

         .success-icon {
            font-size: 24px;
            margin-right: 10px;
            color: #28a745; /* Success color */
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Your Account</h1>

        <p>We emailed you a six-digit OTP on your email the <?php echo $masked_email; ?></p>
        <p>Enter the code to continue.</p>

        <?php if ($otp_error): ?>
            <div class="alert alert-danger" role="alert">
                Incorrect OTP. Please try again.
            </div>
        <?php endif; ?>

        <div class="alert alert-success" role="alert" id="success-message">
           Registration successful! Redirecting to index.php...
        </div>

        <form action="admin_verify_otp.php?email=<?php echo urlencode($email); ?>" method="post">
            <div class="otp-container">
                <input type="text" name="otp1" class="otp-input" maxlength="1" required>
                <input type="text" name="otp2" class="otp-input" maxlength="1" required>
                <input type="text" name="otp3" class="otp-input" maxlength="1" required>
                <input type="text" name="otp4" class="otp-input" maxlength="1" required>
                <input type="text" name="otp5" class="otp-input" maxlength="1" required>
                <input type="text" name="otp6" class="otp-input" maxlength="1" required>
            </div>
            <button type="submit" class="verify-button" name="verify">Verify</button>
        </form>

    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === this.maxLength) {
                        const nextInput = otpInputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                        }
                    }
                });

                input.addEventListener('keydown', function(event) {
                    if (event.key === 'Backspace' || event.key === 'Delete') {
                        return; // Allow backspace for deleting the previous digit
                    }
                    if (!/^[0-9]$/.test(event.key)) {
                        event.preventDefault();  // No letters or special characters
                    }
                });
            });


         const button = document.querySelector('.verify-button');

            button.addEventListener('click', function(e) {
                // Create Ripple effect on click
                const x = e.clientX - button.offsetLeft;
                const y = e.clientY - button.offsetTop;

                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600); // Remove ripple after animation
            });
        });
    </script>
</body>
</html>