<?php
include("../connection/connect.php");
session_start();

// Redirect if not logged in
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Sanitize inputs
function sanitizeInput($data) {
    global $db;
    return mysqli_real_escape_string($db, trim($data));
}

// Handle form submission
$error = null; // Initialize error variable
$successMessage = null; // Initialize success message variable

if (isset($_POST['assign_delivery_boy'])) {
    $order_id = sanitizeInput($_POST['order_id']);
    $delivery_boy_id = sanitizeInput($_POST['delivery_boy_id']);

    // Input validation
    if (empty($order_id) || empty($delivery_boy_id)) {
        $error = "Order ID and Delivery Boy ID are required!";
    } else {
        // Check if the order exists
        $checkOrderSql = "SELECT o_id FROM users_orders WHERE o_id = '$order_id'";
        $checkOrderResult = mysqli_query($db, $checkOrderSql);

        if (mysqli_num_rows($checkOrderResult) == 0) {
            $error = "Invalid Order ID!";
        } else {
            // Check if the delivery boy exists
            $checkDeliveryBoySql = "SELECT db_id FROM delivery_boy WHERE db_id = '$delivery_boy_id'";
            $checkDeliveryBoyResult = mysqli_query($db, $checkDeliveryBoySql);

            if (mysqli_num_rows($checkDeliveryBoyResult) == 0) {
                $error = "Invalid Delivery Boy ID!";
            } else {
                // Update the users_orders table
                $updateSql = "UPDATE users_orders SET delivery_boy_id = '$delivery_boy_id', status = 'assigned' WHERE o_id = '$order_id'";
                if (mysqli_query($db, $updateSql)) {
                    $successMessage = "Delivery boy assigned successfully to Order ID: " . $order_id;
                } else {
                    $error = "Error assigning delivery boy: " . mysqli_error($db);
                }
            }
        }
    }
}

// Fetch orders (only those that are not yet assigned.)
$ordersSql = "SELECT * FROM users_orders WHERE delivery_boy_id IS NULL OR delivery_boy_id = 0"; //Modified query to only show unassigned orders.
$ordersResult = mysqli_query($db, $ordersSql);

// Fetch all delivery boys
$deliveryBoysSql = "SELECT db_id, db_name, db_photo FROM delivery_boy";  //include db_photo here
$deliveryBoysResult = mysqli_query($db, $deliveryBoysSql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Assign Delivery Boy</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .circle-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 5px;
            vertical-align: middle; /* Align image vertically in the middle */
        }

        .delivery-boy-info {
            display: flex;
            align-items: center;  /* Align items vertically in the center */
        }
    </style>
</head>

<body class="fix-header fix-sidebar">

    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" /> </svg>
    </div>

    <div id="main-wrapper">
        <!-- Header -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <span><img src="../images/inc.jpg" alt="homepage" class="dark-logo" /></span>
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav mr-auto mt-md-0">
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  " href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                    </ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <div class="dropdown-menu dropdown-menu-right mailbox animated zoomIn">
                                <ul>
                                    <li>
                                        <div class="drop-title">Notifications</div>
                                    </li>
                                    <li>
                                        <a class="nav-link text-center" href="javascript:void(0);"> <strong>Check all notifications</strong> <i class="fa fa-angle-right"></i> </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted  " href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="images/bookingSystem/user-icn.png" alt="user" class="profile-pic" /></a>
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
        <!-- End header header -->

        <!-- Left Sidebar -->
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
        <!-- End Left Sidebar -->

        <!-- Page wrapper  -->
        <div class="page-wrapper">
            <!-- Container fluid  -->
            <div class="container-fluid">
                <!-- Start Page Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="col-lg-12">
                            <div class="card card-outline-primary">
                                <div class="card-header">
                                    <h4 class="m-b-0 text-white">Assign Delivery Boy to Order</h4>
                                </div>
                                <div class="card-body">

                                    <?php if ($error): ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>

                                    <?php if ($successMessage): ?>
                                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>User ID</th>
                                                    <th>Title</th>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th>Delivery Boy</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($ordersResult && mysqli_num_rows($ordersResult) > 0) {
                                                    while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($order['o_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['u_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['title']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                                            <td><?php echo htmlspecialchars($order['price']); ?></td>
                                                            <td>
                                                                <?php
                                                                // Display assigned delivery boy or "Not Assigned"
                                                                $deliveryBoyId = $order['delivery_boy_id'];
                                                                if ($deliveryBoyId) {
                                                                    $assignedDeliveryBoySql = "SELECT db_name, db_photo FROM delivery_boy WHERE db_id = '$deliveryBoyId'";
                                                                    $assignedDeliveryBoyResult = mysqli_query($db, $assignedDeliveryBoySql);

                                                                    if ($assignedDeliveryBoyResult) {
                                                                        $assignedDeliveryBoy = mysqli_fetch_assoc($assignedDeliveryBoyResult);
                                                                        if ($assignedDeliveryBoy) {
                                                                            echo '<div class="delivery-boy-info">';
                                                                            echo '<img src="' . htmlspecialchars($assignedDeliveryBoy['db_photo']) . '" class="circle-img" alt="Delivery Boy Photo">';
                                                                            echo htmlspecialchars($assignedDeliveryBoy['db_name']);
                                                                            echo '</div>';
                                                                        } else {
                                                                            echo "Delivery Boy Not Found";
                                                                        }
                                                                    } else {
                                                                        echo "Error fetching delivery boy: " . mysqli_error($db);
                                                                    }
                                                                } else {
                                                                    echo "Not Assigned";
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                                                            <td>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['o_id']); ?>">
                                                                    <div class="form-row">
                                                                        <div class="col">
                                                                            <select name="delivery_boy_id" class="form-control">
                                                                                <option value="">Select Delivery Boy</option>
                                                                                <?php
                                                                                mysqli_data_seek($deliveryBoysResult, 0); // Reset pointer
                                                                                while ($deliveryBoy = mysqli_fetch_assoc($deliveryBoysResult)): ?>
                                                                                    <option value="<?php echo htmlspecialchars($deliveryBoy['db_id']); ?>">
                                                                                       <?php echo htmlspecialchars($deliveryBoy['db_name']); ?>
                                                                                    </option>
                                                                                <?php endwhile; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col">
                                                                            <button type="submit" name="assign_delivery_boy" class="btn btn-primary">Assign</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile;
                                                } else {
                                                    echo '<tr><td colspan="8">No orders available to assign.</td></tr>'; // Display message when no orders are available
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End PAge Content -->
            </div>
            <!-- End Container fluid  -->
            <?php include 'footer.php'; ?>
        </div>
        <!-- End Page wrapper  -->
    </div>
    <!-- End Wrapper -->

    <!-- All Jquery -->
    <script src="js/lib/jquery/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="js/jquery.slimscroll.js"></script>
    <!--Menu sidebar -->
    <script src="js/sidebarmenu.js"></script>
    <!--stickey kit -->
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <!--Custom JavaScript -->
    <script src="js/custom.min.js"></script>
</body>

</html>