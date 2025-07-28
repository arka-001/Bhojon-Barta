<?php
include("../connection/connect.php");
error_reporting(0);

if (isset($_GET['email'])) {
    $email = $_GET['email'];
} else {
    header("Location: forgot_password.php"); // Redirect if no email is provided
    exit();
}

if (isset($_POST['reset'])) {

    $alert_message = ""; // Reset the alert message at the start.

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password != $confirm_password) {
        $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Passwords do not match!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                          </div>';
    }
    else { // Only validate password if they match

        // Password validation: Allow special characters, 6 or more characters
        $password_pattern = '/^(?=.*[a-zA-Z])(?=.*\d).{6,}$/';

        if (!preg_match($password_pattern, $new_password)) {
            $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                Password must be at least 6 characters long, contain at least one letter, and at least one number.  Special characters are allowed.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                              </div>';
        }
        else {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_query = "UPDATE admin SET password = ? WHERE email = ?";
            $stmt_update = mysqli_prepare($db, $update_query);
            mysqli_stmt_bind_param($stmt_update, "ss", $hashed_password, $email);
            $update_result = mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            if ($update_result) {
                $alert_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Password reset successfully! You can now login with your new password.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                     </div>';
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 2000); // Redirect after 2 seconds
                  </script>';

            } else {
                $alert_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    Database error resetting password!
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                  </div>';
            }
        }
    }
} else {
    $alert_message = ""; // Initialize $alert_message outside the if(isset($_POST['reset'])) block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
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
            position: relative;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            padding-right: 40px; /* Space for eye toggle button */
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        .eye-toggle {
            position: absolute;
            right: 10px;
            top: 66%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #555;
        }

        .submit-button {
            background-color: #17a2b8;
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
            background-color: #138496;
        }

        .message {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <?php echo $alert_message; ?>  <!-- Display the alert message -->
        <form action="reset_password_form.php?email=<?php echo urlencode($email); ?>" method="post">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
                <span class="eye-toggle" onclick="togglePassword('new_password')" aria-label="Toggle password visibility">
                    <i id="new_password_eye" class="fas fa-eye"></i>
                </span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="eye-toggle" onclick="togglePassword('confirm_password')" aria-label="Toggle password visibility">
                    <i id="confirm_password_eye" class="fas fa-eye"></i>
                </span>
            </div>
            <button type="submit" class="submit-button" name="reset">Reset Password</button>
        </form>
        <p class="message">Back to <a href="index.php">Login</a></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            var eyeIcon = document.getElementById(fieldId + '_eye'); // Get the specific eye icon

            if (field && eyeIcon) {
                if (field.type === "password") {
                    field.type = "text";
                    eyeIcon.classList.remove("fa-eye");
                    eyeIcon.classList.add("fa-eye-slash");
                } else {
                    field.type = "password";
                    eyeIcon.classList.remove("fa-eye-slash");
                    eyeIcon.classList.add("fa-eye");
                }
            } else {
                console.error("Element with ID '" + fieldId + "' or eye icon not found.");
            }
        }
    </script>
</body>
</html>