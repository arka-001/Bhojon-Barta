<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Pagination settings
$page_size = 10; // Number of coupons per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;  // Current page number
$offset = ($page - 1) * $page_size;

// Get total number of coupons
$sql_count = "SELECT COUNT(*) AS total FROM coupons";
$result_count = mysqli_query($db, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_coupons = $row_count['total'];
$total_pages = ceil($total_coupons / $page_size);

// Fetch coupons for the current page
$sql = "SELECT * FROM coupons ORDER BY coupon_id DESC LIMIT $page_size OFFSET $offset";
$coupons = mysqli_query($db, $sql);

// Handle coupon deletion (prepared statement)
if (isset($_GET['delete_coupon'])) {
    $coupon_id = intval($_GET['delete_coupon']); // Sanitize input

    $stmt = $db->prepare("DELETE FROM coupons WHERE coupon_id = ?");
    $stmt->bind_param("i", $coupon_id); // 'i' for integer
    if ($stmt->execute()) {
        $success_message = "Coupon deleted successfully!";
    } else {
        $error_message = "Error deleting coupon: " . $stmt->error;
    }
    $stmt->close();
    header("Location: coupons.php"); // Redirect to refresh the page after deletion
    exit;
}

// Handle adding a new coupon
if (isset($_POST['add_coupon'])) {
    $coupon_code = $_POST['coupon_code'];
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $min_order_value = floatval($_POST['min_order_value']);
    $expiration_date = $_POST['expiration_date'];

    // Validate data (e.g., coupon code uniqueness, discount value range)
    if (empty($coupon_code) || empty($discount_type) || !is_numeric($discount_value)) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        // Prepare and execute the INSERT query (using prepared statements to prevent SQL injection)
        $stmt = $db->prepare("INSERT INTO coupons (coupon_code, discount_type, discount_value, min_order_value, expiration_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdds", $coupon_code, $discount_type, $discount_value, $min_order_value, $expiration_date);

        if ($stmt->execute()) {
            $success_message = "Coupon added successfully!";
             header("Location: coupons.php"); // Redirect to refresh the page after addition
             exit;
        } else {
            $error_message = "Error adding coupon: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coupon Management</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        .coupon-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .coupon-table th,
        .coupon-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .coupon-table th {
            background-color: #f2f2f2;
        }

        .pagination {
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            margin: 0 3px;
            text-decoration: none;
            color: #333;
        }

        .pagination a.active {
            background-color: #4CAF50;
            color: white;
        }

        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
    </style>
</head>
<body class="fix-header">
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <div id="main-wrapper">
        <!-- Header and Sidebar (same as before) -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <span><img src="../images/inc.jpg" alt="homepage" class="dark-logo" /></span>
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav mr-auto mt-md-0">
                    </ul>
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
                                <li><a id="allOwnersLink" href="all_owners.php">All Owners</a></li>
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
        <!-- End Header and Sidebar -->

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline-primary">
                            <div class="card-header">
                                <h4 class="m-b-0 text-white">Coupon Management</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                                <?php endif; ?>
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                                <?php endif; ?>

                                <!-- Add Coupon Form -->
                                <h3>Add New Coupon</h3>
                                <form method="POST">
                                    <!-- Form fields (same as before) -->
                                    <div class="form-group">
                                        <label>Coupon Code</label>
                                        <input type="text" class="form-control" name="coupon_code" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Discount Type</label>
                                        <select class="form-control" name="discount_type" required>
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed Amount</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Discount Value</label>
                                        <input type="number" class="form-control" name="discount_value" step="0.01"
                                            required>
                                    </div>

                                    <div class="form-group">
                                         <label>Minimum Order Value</label>
                                         <input type="number" class="form-control" name="min_order_value" step="0.01" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Expiration Date</label>
                                        <input type="text" class="form-control datepicker" name="expiration_date" placeholder="YYYY-MM-DD">
                                    </div>

                                    <button type="submit" class="btn btn-success" name="add_coupon">Add Coupon</button>
                                </form>

                                <!-- Coupon Listing -->
                                <h3>Existing Coupons</h3>
                                <table class="coupon-table">
                                    <thead>
                                        <tr>
                                            <th>Coupon Code</th>
                                            <th>Discount Type</th>
                                            <th>Discount Value</th>
                                            <th>Minimum Order Value</th>
                                            <th>Expiration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($coupons) > 0) {
                                            mysqli_data_seek($coupons, 0); // Reset the result pointer to the beginning
                                            while ($coupon = mysqli_fetch_assoc($coupons)) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($coupon['coupon_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($coupon['discount_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($coupon['discount_value']); ?></td>
                                                    <td><?php echo htmlspecialchars($coupon['min_order_value']); ?></td>
                                                    <td><?php echo htmlspecialchars($coupon['expiration_date']); ?></td>
                                                    <td>
                                                        <a href="coupons.php?delete_coupon=<?php echo $coupon['coupon_id']; ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Are you sure you want to delete this coupon?')">Delete</a>
                                                        <!-- Add Edit Link Here if Necessary -->
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="6">No coupons found.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <!-- Pagination Links -->
                                <div class="pagination">
                                    <?php if ($total_pages > 1): ?>
                                        <?php if ($page > 1): ?>
                                            <a href="coupons.php?page=<?php echo ($page - 1); ?>">Previous</a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="coupons.php?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="coupons.php?page=<?php echo ($page + 1); ?>">Next</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts (same as before) -->
    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                startDate: new Date()  // Optional: Prevents selecting past dates
            });
        });
    </script>
</body>
</html>