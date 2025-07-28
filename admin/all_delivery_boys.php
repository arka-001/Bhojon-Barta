<?php
include("../connection/connect.php");
session_start();
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Function to calculate the average rating for a delivery boy
function getAverageRating($db, $db_id) {
    $sql = "SELECT AVG(rating) AS avg_rating FROM delivery_boy_ratings WHERE db_id = '$db_id'";
    $result = mysqli_query($db, $sql);
    $row = mysqli_fetch_assoc($result);
    return round($row['avg_rating'] ?: 0, 1);
}

// Fetch all delivery boys
$sql = "SELECT * FROM delivery_boy";
$result = mysqli_query($db, $sql);

// Function to activate or deactivate a delivery boy
function updateDeliveryBoyStatus($db, $db_id, $status) {
    $db_id = mysqli_real_escape_string($db, $db_id);
    $status = ($status == 'activate') ? 1 : 0;
    $update_sql = "UPDATE delivery_boy SET db_status = '$status' WHERE db_id = '$db_id'";
    return mysqli_query($db, $update_sql);
}

// Handle activate/deactivate actions
if (isset($_POST['action']) && isset($_POST['db_id'])) {
    $action = $_POST['action'];
    $db_id = $_POST['db_id'];
    if ($action == 'activate' || $action == 'deactivate') {
        if (updateDeliveryBoyStatus($db, $db_id, $action)) {
            echo '<script>
                Swal.fire({
                    title: "Success!",
                    text: "Delivery boy ' . ($action == 'activate' ? 'activated' : 'deactivated') . ' successfully.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "all_delivery_boys.php";
                });
            </script>';
        } else {
            echo '<script>
                Swal.fire({
                    title: "Error!",
                    text: "Failed to ' . ($action == 'activate' ? 'activate' : 'deactivate') . ' delivery boy.",
                    icon: "error",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "all_delivery_boys.php";
                });
            </script>';
        }
    }
}

// Refetch delivery boys
$sql = "SELECT * FROM delivery_boy";
$result = mysqli_query($db, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>All Delivery Boys</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
        .circle-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }
        .circle-img:hover {
            transform: scale(1.1);
        }
        img {
            width: 50px;
            height: auto;
            transition: transform 0.2s ease;
        }
        img:hover {
            transform: scale(1.1);
        }
        table th, table td {
            white-space: nowrap;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body class="fix-header fix-sidebar">
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <div id="main-wrapper">
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <span><img src="../images/inc.jpg" alt="homepage" class="dark-logo" /></span>
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav mr-auto mt-md-0">
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted" href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted" href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                    </ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="images/bookingSystem/user-icn.png" alt="user" class="profile-pic" /></a>
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
                    <div class="col-12">
                        <div class="col-lg-12">
                            <div class="card card-outline-primary">
                                <div class="card-header">
                                    <h4 class="m-b-0 text-white">All Delivery Boys</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Phone</th>
                                                    <th>Email</th>
                                                    <th>Address</th>
                                                    <th>City</th>
                                                    <th>Profile Photo</th>
                                                    <th>License Number</th>
                                                    <th>License Photo</th>
                                                    <th>Aadhaar PDF</th>
                                                    <th>Status</th>
                                                    <th>Rating</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($delivery_boy = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($delivery_boy['db_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['db_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['db_phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['db_email'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['db_address']); ?></td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['city']); ?></td>
                                                    <td>
                                                        <?php if ($delivery_boy['db_photo']): ?>
                                                            <a href="../<?php echo htmlspecialchars($delivery_boy['db_photo']); ?>" target="_blank">
                                                                <img src="../<?php echo htmlspecialchars($delivery_boy['db_photo']); ?>" alt="Profile Photo" class="circle-img">
                                                            </a>
                                                        <?php else: ?>
                                                            No Photo
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($delivery_boy['driving_license_number']); ?></td>
                                                    <td>
                                                        <?php if ($delivery_boy['driving_license_photo']): ?>
                                                            <a href="../<?php echo htmlspecialchars($delivery_boy['driving_license_photo']); ?>" target="_blank">
                                                                <img src="../<?php echo htmlspecialchars($delivery_boy['driving_license_photo']); ?>" alt="License Photo">
                                                            </a>
                                                        <?php else: ?>
                                                            No Photo
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($delivery_boy['aadhaar_pdf']): ?>
                                                            <a href="../<?php echo htmlspecialchars($delivery_boy['aadhaar_pdf']); ?>" target="_blank">View Aadhaar PDF</a>
                                                        <?php else: ?>
                                                            No PDF
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status = ($delivery_boy['db_status'] == 1) ? 'Active' : 'Inactive';
                                                        echo htmlspecialchars($status);
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(getAverageRating($db, $delivery_boy['db_id'])); ?></td>
                                                    <td>
                                                        <form method="post">
                                                            <input type="hidden" name="db_id" value="<?php echo htmlspecialchars($delivery_boy['db_id']); ?>">
                                                            <input type="hidden" name="action" value="<?php echo ($delivery_boy['db_status'] == 1) ? 'deactivate' : 'activate'; ?>">
                                                            <button type="submit" class="btn btn-sm <?php echo ($delivery_boy['db_status'] == 1) ? 'btn-warning' : 'btn-success'; ?>">
                                                                <i class="fa <?php echo ($delivery_boy['db_status'] == 1) ? 'fa-times' : 'fa-check'; ?>" style="font-size:16px"></i>
                                                                <?php echo ($delivery_boy['db_status'] == 1) ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo htmlspecialchars($delivery_boy['db_id']); ?>)" class="btn btn-danger btn-flat btn-addon btn-xs m-b-10"><i class="fa fa-trash-o" style="font-size:16px"></i></a>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="add_delivery_boy.php" class="btn btn-primary">Add New Delivery Boy</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script>
        function confirmDelete(db_id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to delete this delivery boy?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_delivery_boy.php?db_id=' + db_id;
                }
            });
        }
    </script>
</body>
</html>