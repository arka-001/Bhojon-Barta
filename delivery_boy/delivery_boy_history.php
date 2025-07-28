<?php
session_start();
include("../connection/connect.php");

// Check if session is set
if (!isset($_SESSION["db_id"])) {
    header("Location: index.php");
    exit();
}

$db_id = $_SESSION["db_id"];

// Verify database connection
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch delivery boy details
$sql = "SELECT * FROM delivery_boy WHERE db_id = ?";
$stmt = mysqli_prepare($db, $sql);
if ($stmt === false) {
    die("Prepare failed: " . mysqli_error($db));
}
mysqli_stmt_bind_param($stmt, "i", $db_id);
mysqli_stmt_execute($stmt);
$delivery_boy = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$delivery_boy) {
    die("No delivery boy found with db_id: " . htmlspecialchars($db_id));
}
mysqli_stmt_close($stmt);

// Fetch completed orders from delivery_boy_history
$completedOrdersSql = "SELECT dbh.*, uo.title, uo.quantity, u.username, r.title as rest_title
                       FROM delivery_boy_history dbh
                       LEFT JOIN users_orders uo ON dbh.order_id = uo.o_id
                       LEFT JOIN users u ON uo.u_id = u.u_id
                       LEFT JOIN restaurant r ON uo.rs_id = r.rs_id
                       WHERE dbh.delivery_boy_id = ?";
$completedOrdersStmt = mysqli_prepare($db, $completedOrdersSql);
if ($completedOrdersStmt === false) {
    die("Prepare failed for completed orders: " . mysqli_error($db));
}
mysqli_stmt_bind_param($completedOrdersStmt, "i", $db_id);
mysqli_stmt_execute($completedOrdersStmt);
$completedOrders = mysqli_fetch_all(mysqli_stmt_get_result($completedOrdersStmt), MYSQLI_ASSOC);
mysqli_stmt_close($completedOrdersStmt);

// Total earnings (all time)
$totalEarningsSql = "SELECT SUM(delivery_charge) as total_earnings
                     FROM delivery_boy_history
                     WHERE delivery_boy_id = ?";
$totalEarningsStmt = mysqli_prepare($db, $totalEarningsSql);
if ($totalEarningsStmt === false) {
    die("Prepare failed for total earnings: " . mysqli_error($db));
}
mysqli_stmt_bind_param($totalEarningsStmt, "i", $db_id);
mysqli_stmt_execute($totalEarningsStmt);
$totalEarningsResult = mysqli_fetch_assoc(mysqli_stmt_get_result($totalEarningsStmt));
$totalEarnings = $totalEarningsResult['total_earnings'] ?? 0;
mysqli_stmt_close($totalEarningsStmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
       /* ===== Root Variables (Consistent with Dashboard) ===== */
        :root {
            --primary-color: #3498db;        /* A classic blue */
            --secondary-color: #2ecc71;      /* A vibrant green */
            --accent-color: #f39c12;         /* A warm orange */
            --background-color: #f9f9f9;     /* Very light gray */
            --text-color: #333333;           /* Dark gray for main text */
            --light-text-color: #666666;      /* Gray for less important text */
            --card-background: #ffffff;       /* White */
            --border-color: #e0e0e0;        /* Light gray border */
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            --border-radius: 12px;           /* Rounded corners */
            --font-family: 'Poppins', sans-serif; /* Modern sans-serif font */
        }

        /* ===== General Styles ===== */
        body {
            font-family: var(--font-family);
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* ===== Navbar ===== */
        .navbar {
            background-color: var(--primary-color);
            padding: 15px 0;
            box-shadow: var(--box-shadow);
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover, .nav-link:hover {
            color: var(--accent-color) !important;
        }

        /* ===== Earnings Card ===== */
        .earnings-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
        }

        .earnings-card h4 {
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .earnings-card p {
            color: var(--light-text-color);
            font-size: 1.1em;
            margin-bottom: 8px;
        }

        /* ===== Completed Order Card ===== */
        .completed-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .completed-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .order-body {
            padding: 20px;
        }

        .order-info p {
            margin: 8px 0;
            line-height: 1.6;
            color: var(--light-text-color);
        }

        .order-info strong {
            color: var(--text-color);
        }

        /* ===== Status Badge ===== */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            display: inline-block;
            margin-top: 5px;
        }

        .status-badge.bg-primary {
            background-color: var(--primary-color) !important;
            color: #fff;
        }

        .status-badge.bg-danger {
            background-color: #e74c3c !important; /* Red */
            color: #fff;
        }

        /* ===== Buttons ===== */
        .btn-custom {
            border-radius: 25px;
            padding: 10px 22px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.1);
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 9px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: darken(var(--primary-color), 10%) !important;
            border-color: darken(var(--primary-color), 10%) !important;
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 768px) {
            .earnings-card {
                padding: 15px;
            }

            .earnings-card h4 {
                font-size: 1.5em;
            }
        }

        /* ===== Utility Classes ===== */
        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .mt-5 {
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Delivery Hub</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-outline-light btn-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="delivery_boy_dashboard.php" class="nav-link me-3">Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="text-center mb-4" style="color: #1a2b49; font-weight: 600;">Order History for <?php echo htmlspecialchars($delivery_boy['db_name']); ?></h2>

        <div class="earnings-card">
            <h4 class="text-center mb-3">Your Delivery Earnings</h4>
            <div class="row text-center">
                <div class="col-12">
                    <p><strong>Total Earnings:</strong> ₹<?php echo number_format($totalEarnings, 2); ?></p>
                </div>
            </div>
        </div>

        <h3 class="text-center mb-4" style="color: #1a2b49; font-weight: 600;">Accepted Orders</h3>
        <?php if (empty($completedOrders)): ?>
            <div class="alert alert-info text-center" role="alert">
                No accepted orders yet.
            </div>
        <?php else: foreach ($completedOrders as $order): ?>
            <div class="completed-card">
                <div class="order-header">
                    <h5 class="mb-0">Order #<?php echo $order['order_id']; ?></h5>
                </div>
                <div class="order-body">
                    <div class="order-info">
                        <p><strong>Item:</strong> <?php echo htmlspecialchars($order['title']); ?> (x<?php echo htmlspecialchars($order['quantity']); ?>)</p>
                        <p><strong>Order Price:</strong> ₹<?php echo number_format($order['order_price'], 2); ?></p>
                        <p><strong>Delivery Charge:</strong> ₹<?php echo number_format($order['delivery_charge'], 2); ?></p>
                        <p><strong>Status:</strong>
                            <span class="status-badge <?php echo $order['status'] === 'in_transit' ? 'bg-primary' : 'bg-danger'; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                        <p><strong>Restaurant:</strong> <?php echo htmlspecialchars($order['rest_title']); ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Accepted At:</strong> <?php echo htmlspecialchars($order['completed_at']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>

        <div class="text-center mt-5">
            <a href="delivery_boy_dashboard.php" class="btn btn-primary btn-custom">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>