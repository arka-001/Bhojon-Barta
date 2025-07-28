<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
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

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 70%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        #password-requirements {
            font-size: 0.8rem;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <h2 class="text-center mb-4">Reset Password</h2>
                    <?php
                    session_start();
                    //error_reporting(0); // REMOVE THIS IN PRODUCTION
                    include("connection/connect.php");

                    $message = "";
                    $redirect = false;

                    // Check if reset_email is set in the session
                    if (!isset($_SESSION['reset_email'])) {
                        $message = "Invalid password reset request.";
                        $redirect = true;
                    }

                    if (isset($_POST['submit']) && !$redirect) { // Only process if not already redirecting

                        $new_password = $_POST['new_password'];
                        $confirm_password = $_POST['confirm_password'];

                        $pattern = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{6,}$/';
                        if (!preg_match($pattern, $new_password)) {
                            $message = "Password must be at least 6 characters long and contain at least one letter, one number, and one special character.";
                        } elseif ($new_password != $confirm_password) {
                            $message = "Passwords do not match.";
                        } else {
                            // DANGEROUS: Storing password in plain text!
                            $plaintext_password = $new_password;

                            // Use prepared statements
                            $email = $_SESSION['reset_email'];
                            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                            $stmt->bind_param("ss", $plaintext_password, $email);

                            if ($stmt->execute()) {
                                unset($_SESSION['reset_email']);
                                $message = "Password has been reset successfully!  (Warning: Stored in plain text!)";
                                $redirect = true;
                            } else {
                                $message = "Database error: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }

                    if ($redirect) {
                        // Secure redirect using PHP header
                        header("Location: login.php");
                        exit(); // Always call exit() after header()
                    }
                    ?>

                    <?php if ($message): ?>
                        <div class="alert <?php echo ($redirect ? 'alert-success' : 'alert-danger'); ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group password-container">
                            <label for="new_password">New Password:</label>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                required>
                            <span class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                            
                        </div>

                        <div class="form-group password-container">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                            <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary btn-block">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.querySelector(`.password-toggle[onclick="togglePasswordVisibility('${inputId}')"] i`);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>

</html>