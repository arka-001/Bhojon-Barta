<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
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
                    <h2 class="text-center mb-4">Verify OTP</h2>
                    <?php
                    session_start();
                    error_reporting(0);
                    include("connection/connect.php");

                    $message = "";

                    if (isset($_POST['submit'])) {
                        $otp = $_POST['otp'];
                        $email = $_SESSION['reset_email'];  // Get email from session

                        // Verify OTP against what stored in database with the email
                         $check_otp = mysqli_query($db, "SELECT email FROM users WHERE email = '$email' AND otp = '$otp'");

                          if (mysqli_num_rows($check_otp) > 0) {

                                // OTP is correct. Clear OTP now.
                                $clear_otp = mysqli_query($db, "UPDATE users SET otp = NULL WHERE email = '$email'");


                                // Redirect to reset password page
                                header("Location: reset-password.php");  // Redirect to reset-password.php
                                exit();
                            } else {
                                $message = "Invalid OTP. Please try again.";
                            }
                        }
                    ?>

                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="otp">Enter OTP:</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary btn-block">Verify OTP</button>
                    </form>
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