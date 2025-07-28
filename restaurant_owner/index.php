<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'restaurant_owner') {
    header("Location: restaurant_owner_dashboard.php");
    exit();
}
include("../connection/connect.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Owner Portal - Online Food Ordering</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .hero-section {
            background-color: #f8f9fa;
            padding: 60px 0;
            text-align: center;
        }
        footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Restaurant Owner Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="restaurant_owner_login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Welcome, Restaurant Owners!</h1>
            <p class="lead">Manage your restaurants, menus, and orders with ease.</p>
            <a href="restaurant_owner_login.php" class="btn btn-primary btn-lg mt-3">Get Started</a>
            <a href="join_restaurant_owner_form.php" class="btn btn-success btn-lg mt-3">Join as Restaurant Owner</a>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="container py-5">
        <h2 class="text-center mb-4">Contact Us</h2>
        <p class="text-center">Need help? Reach out to our support team.</p>
        <div class="text-center">
            <p>Email: <a href="mailto:bhojonbarta@gmail.com">bhojonbarta@gmail.com</a></p>
            <p>Phone: +91 0000000000</p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© <?php echo date("Y"); ?> Online Food Ordering System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
mysqli_close($db);
?>