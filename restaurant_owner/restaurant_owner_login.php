<?php
session_start();
include("../connection/connect.php"); // This sets $db

// Redirect if already logged in
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'restaurant_owner') {
    header("Location: restaurant_owner_dashboard.php");
    exit();
}

$error = '';
$success_message = ''; // For password reset success

// Check for success message from password reset
if (isset($_SESSION['reset_success'])) {
    $success_message = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']); // Clear the message after displaying
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and Password are required.";
    } else {
        // Prepare statement with MySQLi using $db
        $stmt = $db->prepare("SELECT owner_id, email, password FROM restaurant_owners WHERE email = ?");
        if (!$stmt) {
            // Log the error internally
            error_log("Prepare failed: " . $db->error);
            $error = "An internal error occurred. Please try again later."; // User-friendly error
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $owner = $result->fetch_assoc();

            // Verify hashed password
            if ($owner && password_verify($password, $owner['password'])) {
                // Regenerate session ID upon successful login for security
                session_regenerate_id(true);

                $_SESSION['user_type'] = 'restaurant_owner';
                $_SESSION['email'] = $email;
                $_SESSION['owner_id'] = $owner['owner_id'];
                header("Location: restaurant_owner_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Owner Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 450px; margin: 80px auto; }
        .card-footer { background-color: #f1f1f1; }
        .btn-secondary { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h3 class="text-center mb-0">Restaurant Owner Login</h3></div>
                <div class="card-body p-4">
                     <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                           <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                           <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="restaurant_owner_login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                             <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                             </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                         <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                         </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small><a href="forgot_password_owner.php">Forgot Password?</a></small> <!-- Link to new script -->
                    <hr>
                     <!-- Go to Main Site Button -->
                    <a href="index.php" class="btn btn-secondary btn-sm">
                       <i class="fas fa-home me-1"></i> Go to dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>