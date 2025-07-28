<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("connection/connect.php");

// Function to send email (consider using a library)
function smtp_mailer($to, $subject, $msg)
{
    include('smtp/PHPMailerAutoload.php'); // Include here to avoid global scope issues
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    //$mail->SMTPDebug = 2;
    $mail->Username = "bhojonbarta@gmail.com";  // **Important:** Use your actual email address
    $mail->Password = "zyys vops vyua zetu";  // **Important:** Use your app password
    $mail->SetFrom("bhojonbarta@gmail.com"); // **Important:** Use your actual email address
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress($to);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => false
        )
    );
    if (!$mail->Send()) {
        return 'Error: ' . $mail->ErrorInfo; // Return error message
    } else {
        return 'Sent';
    }
}

if (isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    // Check if reg_otp is set in the session
    if (!isset($_SESSION['reg_otp'])) {
        error_log("Error: reg_otp is not set in the session.");
        die("Error: OTP not found in session. Please register again."); // Stop script execution
    }

    if ($entered_otp == $_SESSION['reg_otp']) {
        // OTP is correct, proceed with registration

        $username = $_SESSION['reg_username'];
        $firstname = $_SESSION['reg_firstname'];
        $lastname = $_SESSION['reg_lastname'];
        $email = $_SESSION['reg_email'];
        $phone = $_SESSION['reg_phone'];
        $password = $_SESSION['reg_password'];
        $address = $_SESSION['reg_address'];
        $verification_token = $_SESSION['verification_token'];
        $latitude = $_SESSION['latitude'];
        $longitude = $_SESSION['longitude'];

        $safe_username = mysqli_real_escape_string($db, $username);
        $safe_firstname = mysqli_real_escape_string($db, $firstname);
        $safe_lastname = mysqli_real_escape_string($db, $lastname);
        $safe_email = mysqli_real_escape_string($db, $email);
        $safe_phone = mysqli_real_escape_string($db, $phone);
        $safe_address = mysqli_real_escape_string($db, $address);

        // Store Password as Plain Text (VERY INSECURE!)
        $plain_password = $password;  // NO HASHING!

        $email_verified = 1; // Correct placement

        // Use Prepared Statements (Important for Security!)
        $stmt = mysqli_prepare($db, "INSERT INTO users(username, f_name, l_name, email, phone, password, address, verification_token, email_verified, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $error = mysqli_error($db);
            error_log("mysqli_prepare() failed: " . $error);  // Log the error
            die("mysqli_prepare() failed: " . $error);       // Display the error (for debugging only!)
        }

        mysqli_stmt_bind_param($stmt, "ssssssssidd", $safe_username, $safe_firstname, $safe_lastname, $safe_email, $safe_phone, $plain_password, $safe_address, $verification_token, $email_verified, $latitude, $longitude);

        if (mysqli_stmt_execute($stmt) === false) {
            $error = mysqli_stmt_error($stmt);
            error_log("mysqli_stmt_execute() failed: " . $error);
            die("mysqli_stmt_execute() failed: " . $error);
        }

        // Clear session data
        unset($_SESSION['reg_username']);
        unset($_SESSION['reg_firstname']);
        unset($_SESSION['reg_lastname']);
        unset($_SESSION['reg_email']);
        unset($_SESSION['reg_phone']);
        unset($_SESSION['reg_password']);
        unset($_SESSION['reg_address']);
        unset($_SESSION['reg_otp']);
        unset($_SESSION['verification_token']);
        unset($_SESSION['latitude']);
        unset($_SESSION['longitude']);
        mysqli_stmt_close($stmt);

        // Redirect using PHP header (most reliable)
        header("Location: login.php");
        exit();
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "error",
                    title: "Incorrect OTP",
                    text: "Please enter the correct OTP.",
                });
            });
        </script>';
    }
}

// Resend OTP logic
if (isset($_POST['resend_otp'])) {
    // Generate a new OTP
    $new_otp = rand(100000, 999999);

    // Store the new OTP in the session
    $_SESSION['reg_otp'] = $new_otp;

    // Get user details from the session
    $email = $_SESSION['reg_email'];
    $firstname = $_SESSION['reg_firstname'];
    $lastname = $_SESSION['reg_lastname'];

    // Send the new OTP via email
    $subject = "Email Verification OTP";
    $message = 'Dear ' . $firstname . ' ' . $lastname . ',<br><br>Your new OTP for email verification is: <b>' . $new_otp . '</b>. Please use this OTP to verify your email address.<br><br>Thank you,<br>Food Delivery System';

    $mail_result = smtp_mailer($email, $subject, $message);

    if ($mail_result == 'Sent') {
        echo "<script>alert('New OTP has been sent to your email address.');</script>";
    } else {
        echo "<script>alert('Error sending new OTP: " . $mail_result . "');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Apply this CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to right, #4facfe, #00f2fe);
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 350px;
        }

        header i {
            font-size: 50px;
            color: #4facfe;
        }

        h4 {
            margin: 15px 0;
            color: #333;
        }

        .input-field {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 5px;
            transition: 0.3s;
        }

        .otp-input:focus {
            border-color: #4facfe;
            outline: none;
            box-shadow: 0 0 8px rgba(79, 172, 254, 0.5);
        }

        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        #submit-btn {
            background: #4facfe;
            color: white;
            font-weight: bold;
        }

        #submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        #resend-btn {
            background: #ddd;
            color: #333;
        }

        #resend-btn:hover {
            background: #bbb;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container">
        <header>
            <i class="bx bxs-check-shield"></i>
        </header>
        <h4>Enter OTP Code</h4>
        <form action="" method="POST" id="otp-form">
            <!-- Hidden field for combined OTP -->
            <input type="hidden" name="otp" id="full-otp">

            <div class="input-field">
                <input type="text" maxlength="1" class="otp-input" />
                <input type="text" maxlength="1" class="otp-input" disabled />
                <input type="text" maxlength="1" class="otp-input" disabled />
                <input type="text" maxlength="1" class="otp-input" disabled />
                <input type="text" maxlength="1" class="otp-input" disabled />
                <input type="text" maxlength="1" class="otp-input" disabled />
            </div>

            <button type="submit" id="submit-btn" name="verify_otp" disabled>Verify OTP</button>
            <!-- Used form submit instead of calling resend_otp.php, now stays on the same page -->
            <button type="submit" name="resend_otp" id="resend-btn">Resend</button>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // OTP input behavior
            const inputs = document.querySelectorAll('.otp-input');
            const submitButton = document.getElementById('submit-btn');
            const fullOtpInput = document.getElementById('full-otp');

            inputs[0].focus();

            inputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    if (input.value.length > 1) {
                        input.value = input.value.slice(0, 1);
                    }

                    if (input.value !== "" && inputs[index + 1]) {
                        inputs[index + 1].removeAttribute('disabled');
                        inputs[index + 1].focus();
                    }

                    if (e.inputType === 'deleteContentBackward' && input.value === "") {
                        if (inputs[index - 1]) {
                            inputs[index - 1].focus();
                        }
                    }

                    // Enable submit button when all fields are filled
                    const allFilled = [...inputs].every(input => input.value !== "");
                    submitButton.disabled = !allFilled;

                    // Update hidden input field with the full OTP
                    fullOtpInput.value = [...inputs].map(input => input.value).join('');
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && input.value === "" && inputs[index - 1]) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // Allow pasting OTP
            document.addEventListener("paste", (e) => {
                const pastedData = e.clipboardData.getData("text").trim();
                if (pastedData.length === inputs.length && /^\d+$/.test(pastedData)) {
                    pastedData.split("").forEach((char, i) => {
                        inputs[i].value = char;
                        if (inputs[i + 1]) inputs[i + 1].removeAttribute('disabled');
                    });
                    fullOtpInput.value = pastedData;
                    submitButton.disabled = false;
                }
            });


        });
    </script>
</body>

</html>