<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
error_reporting(0);
session_start();
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
} else {
// Handle form submission for updating delivery city
if (isset($_POST['update_delivery_city'])) {
    $new_delivery_city = mysqli_real_escape_string($db, $_POST['delivery_city']); // Sanitize input

    // Update the Delivery City in the settings table
    $sql = "UPDATE settings SET setting_value = '$new_delivery_city' WHERE setting_name = 'delivery_city'";
    $update_result = mysqli_query($db, $sql);
    if ($update_result) {
        $success_message = "Delivery city updated successfully!";
    } else {
        $error_message = "Failed to update delivery city.";
    }
}

// Fetch the current delivery city from the settings table
$sql = "SELECT setting_value FROM settings WHERE setting_name = 'delivery_city'";
$delivery_city_query = mysqli_query($db, $sql);
if ($delivery_city_query && mysqli_num_rows($delivery_city_query) > 0) {
    $delivery_city_row = mysqli_fetch_assoc($delivery_city_query);
    $current_delivery_city = $delivery_city_row['setting_value'];
} else {
    $current_delivery_city = "N/A"; // Default value if not found
}

// Handle form submission for updating delivery charge
if (isset($_POST['update_delivery_charge'])) {
    $new_delivery_charge = floatval($_POST['delivery_charge']); // Validate input

    // Update the Delivery Charges in the settings table
    $sql = "UPDATE settings SET setting_value = '$new_delivery_charge' WHERE setting_name = 'delivery_charge'";
    $update_result = mysqli_query($db, $sql);
    if ($update_result) {
        $success_message = "Delivery charge updated successfully!";
    } else {
        $error_message = "Failed to update delivery charge.";
    }
}

// Fetch the current delivery charge from the settings table
$sql = "SELECT setting_value FROM settings WHERE setting_name = 'delivery_charge'";
$delivery_charge_query = mysqli_query($db, $sql);
if ($delivery_charge_query && mysqli_num_rows($delivery_charge_query) > 0) {
    $delivery_charge_row = mysqli_fetch_assoc($delivery_charge_query);
    $current_delivery_charge = $delivery_charge_row['setting_value'];
} else {
    $current_delivery_charge = 0.00; // Default value if not found
}
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <style>
    .footer-settings-link {
        cursor: pointer;
        margin-left: 7.5%;
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
        <div class="page-wrapper">
            <div class="container-fluid">
                <div id="contentArea">
                </div>
                <div class="row" id="Dashboard">
                    <div class="col-lg-12">
                        <div class="card card-outline-primary">
                            <div class="card-header">
                                <h4 class="m-b-0 text-white">Admin Dashboard</h4>
                            </div>
                            <?php if (isset($success_message)) { ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php } ?>
                            <?php if (isset($error_message)) { ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-home f-s-40"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM restaurant";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Restaurants</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-cutlery f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM dishes";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Dishes</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-users f-s-40"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM users";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Users</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-shopping-cart f-s-40"
                                                        aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM users_orders";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Total Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-th-large f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM res_category";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Restro Categories</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-spinner f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM users_orders WHERE status = 'in process'";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Processing Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-check f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM users_orders WHERE status = 'closed'";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Delivered Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-times f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM users_orders WHERE status = 'rejected'";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Cancelled Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-inr f-s-40" aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $result = mysqli_query($db, "SELECT SUM(price) AS value_sum FROM users_orders WHERE status = 'closed'");
                                                    $row = mysqli_fetch_assoc($result);
                                                    $sum = $row['value_sum'];
                                                    echo $sum;
                                                    ?></h2>
                                                <p class="m-b-0">Total Earnings</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-30">
                                        <div class="media">
                                            <div class="media-left meida media-middle">
                                                <span><i class="fa fa-motorcycle f-s-40"
                                                        aria-hidden="true"></i></span>
                                            </div>
                                            <div class="media-body media-text-right">
                                                <h2><?php
                                                    $sql = "SELECT * FROM delivery_boy";
                                                    $result = mysqli_query($db, $sql);
                                                    $rws = mysqli_num_rows($result);
                                                    echo $rws;
                                                    ?></h2>
                                                <p class="m-b-0">Delivery Boys</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'admin_chat.php'; // Include the combined widget file ?>


    <!-- Scripts (loaded at the end of body) -->
    <!-- jQuery -->
    <script src="js/lib/jquery/jquery.min.js"></script>
    <!-- Popper and Bootstrap JS -->
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <!-- Custom Scripts -->
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('dashboard.php: Document ready');

        // Test SweetAlert2
        if (typeof Swal !== 'undefined') {
            console.log('SweetAlert2 is loaded');
        } else {
            console.error('SweetAlert2 is not loaded');
        }

        // Test DataTables
        if (typeof $.fn.DataTable !== 'undefined') {
            console.log('DataTables is loaded');
        } else {
            console.error('DataTables is not loaded');
        }

        // Function to load content into the contentArea
        function loadContent(url, data = null) {
            $.ajax({
                url: url,
                type: data ? 'POST' : 'GET',
                data: data,
                success: function(response) {
                    $('#Dashboard').hide();
                    $('#contentArea').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Content load error:', status, error);
                    $('#contentArea').html('<p>Error loading content.</p>');
                }
            });
        }

        // Handle click on Footer Settings link
        $('#footerSettingsLink').click(function(e) {
            e.preventDefault();
            loadContent('footer_settings.php');
        });

        // Handle click on New Delivery Boy Request link
        $('#newDeliveryBoyRequestLink').click(function(e) {
            e.preventDefault();
            loadContent('new_delivery_boy_request.php');
        });

        // // Handle click on Pending Restaurant link
        // $('#pendingRestaurantLink').click(function(e) {
        //     e.preventDefault();
        //     loadContent('pending_restaurant.php');
        // });

        // // Handle click on New Restaurant Owner Request link
        // $('#newRestaurantOwnerRequestLink').click(function(e) {
        //     e.preventDefault();
        //     loadContent('new_restaurant_owner_request.php');
        // });

        // Handle form submission using AJAX
        $(document).on('submit', 'form', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action') || 'footer_settings.php';

            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                success: function(data) {
                    $('#contentArea').html(data);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Changes Saved Successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Form submission error:', status, error);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'error',
                        title: 'Changes Not Saved',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        });

        // Handle close button for dynamic content
        $(document).on("click", ".close", function() {
            $('#Dashboard').show();
            $('#contentArea').empty();
        });
    });
    </script>
</body>
</html>
<?php
}
?>