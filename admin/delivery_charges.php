<?php
include("../connection/connect.php");
error_reporting(0);
session_start();
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit();
}

// Handle form submission
if (isset($_POST['update_delivery_charge'])) {
    $delivery_charge = floatval($_POST['delivery_charge']);
    $min_order_value = !empty($_POST['min_order_value']) ? floatval($_POST['min_order_value']) : null;

    // Update or insert into delivary_charges (assuming single row for simplicity)
    $check_sql = "SELECT id FROM delivary_charges LIMIT 1";
    $check_result = mysqli_query($db, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing row
        $sql = "UPDATE delivary_charges SET delivery_charge = ?, min_order_value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "dd", $delivery_charge, $min_order_value);
    } else {
        // Insert new row if none exists
        $sql = "INSERT INTO delivary_charges (delivery_charge, min_order_value, created_at, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "dd", $delivery_charge, $min_order_value);
    }

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Delivery charges updated successfully!";
    } else {
        $error_message = "Failed to update delivery charges: " . mysqli_error($db);
    }
    mysqli_stmt_close($stmt);
}

// Fetch current values
$sql = "SELECT delivery_charge, min_order_value FROM delivary_charges ORDER BY updated_at DESC LIMIT 1";
$result = mysqli_query($db, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $current_delivery_charge = $row['delivery_charge'];
    $current_min_order_value = $row['min_order_value'];
} else {
    $current_delivery_charge = 50.00; // Default
    $current_min_order_value = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Delivery Charges</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
</head>
<body class="fix-header">
    <div id="main-wrapper">
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <span><img src="../images/inc.jpg" alt="homepage" class="dark-logo" /></span>
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav mr-auto mt-md-0"></ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="images/bookingSystem/user-icn.png" alt="user" class="profile-pic" />
                            </a>
                            <div class="dropdown-menu dropdown-menu-right animated zoomIn">
                                <ul class="dropdown-user">
                                    <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div class="left-sidebar">
            <div class="scroll-sidebar">
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="nav-devider"></li>
                        <li class="nav-label">Home</li>
                        <li><a href="dashboard.php"><i class="fa fa-tachometer"></i><span>Dashboard</span></a></li>
                        <li class="nav-label">Log</li>
                        <li><a href="all_users.php"><span><i class="fa fa-user f-s-20"></i></span><span>Users</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="has-arrow" href="#" aria-expanded="false"><i
                                    class="fa fa-archive f-s-20 color-warning"></i><span
                                    class="hide-menu">Restaurant</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_restaurant.php">All Restaurant</a></li>
                                <li><a href="add_category.php">Add Category</a></li>
                                <li><a href="add_restaurant.php">Add Restaurant</a></li>
                                <li><a id="pendingRestaurantLink" href="pending_restaurant.php">Pending Restaurant</a></li>
                                <li><a id="newRestaurantOwnerRequestLink" href="new_restaurant_owner_request.php">New Restaurant Owner Request</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-cutlery"
                                    aria-hidden="true"></i><span class="hide-menu">Menu</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_menu.php">All Menus</a></li>
                                <li><a href="add_menu.php">Add Menu</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="all_orders.php"><i class="fa fa-shopping-cart"
                                    aria-hidden="true"></i><span>Orders</span></a>
                        </li>
                        <!-- Delivery Boy Menu with Submenu -->
                        <li class="nav-item">
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-motorcycle"
                                    aria-hidden="true"></i><span class="hide-menu">Delivery Boy</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="add_delivery_boy.php">Add Delivery Boy</a></li>
                                <li><a href="all_delivery_boys.php">All Delivery Boys</a></li>
                                <li><a href="assigned_delivary.php">Assigned Delivery</a></li>
                                <li><a href="delivery_charges.php">Delivery Charges</a></li>
                                <li><a href="delivery_city.php">Delivery City</a></li>
                                <li><a id="newDeliveryBoyRequestLink" href="new_delivery_boy_request.php">New Delivery Boy Request</a></li>
                                <li><a id="allOwnersLink" href="all_owners.php">All Owners</a></li>
                            </ul>
                        </li>
                        <!-- End Delivery Boy Menu -->
                        <li class="nav-item">
                            <a href="coupons.php"><i class="fa fa-ticket"></i><span>Coupons</span></a>
                        </li>
                        <!-- Footer Settings Link -->
                        <li><span id="footerSettingsLink" class="footer-settings-link"><i class="fa fa-cog"></i> Footer
                                Settings</span></li>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline-primary">
                            <div class="card-header">
                                <h4 class="m-b-0 text-white">Manage Delivery Charges</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($success_message)) { ?>
                                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                                <?php } ?>
                                <?php if (isset($error_message)) { ?>
                                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                                <?php } ?>
                                <form method="post" action="delivery_charges.php">
                                    <div class="form-group">
                                        <label for="delivery_charge">Delivery Charge (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge" 
                                               value="<?php echo number_format($current_delivery_charge, 2); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="min_order_value">Minimum Order Value for Free Delivery (₹, optional)</label>
                                        <input type="number" step="0.01" class="form-control" id="min_order_value" name="min_order_value" 
                                               value="<?php echo $current_min_order_value ? number_format($current_min_order_value, 2) : ''; ?>" 
                                               placeholder="e.g., 2000 for free delivery above ₹2000">
                                    </div>
                                    <button type="submit" name="update_delivery_charge" class="btn btn-primary">
                                        <i class="fa fa-save"></i> Update Delivery Charges
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script src="js/custom.min.js"></script>
</body>
</html>