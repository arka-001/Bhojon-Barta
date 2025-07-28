<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

if(isset($_GET['confirm_delete'])) {
    $order_id_to_delete = $_GET['confirm_delete'];
    $sql = "DELETE FROM users_orders WHERE o_id = '$order_id_to_delete'";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $_SESSION['success_message'] = "Order deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting order: " . mysqli_error($db);
    }

    header("Location: all_orders.php"); // Redirect back to the page
    exit;
}

// Function to sanitize inputs
function sanitizeInput($data) {
    global $db;
    return mysqli_real_escape_string($db, trim($data));
}

// Handle approval/rejection of status updates
if (isset($_POST['approve_request']) || isset($_POST['reject_request'])) {
    $request_id = sanitizeInput($_POST['request_id']);
    $action = isset($_POST['approve_request']) ? 'approved' : 'rejected';
    $comment = sanitizeInput($_POST['admin_comment']); // Get the admin comment
    $order_id = sanitizeInput($_POST['order_id']); // You'll need to pass order_id as well
    $requested_status = sanitizeInput($_POST['requested_status']);//Get request status

    if (empty($request_id)) {
        $error = "Request ID is required!";
    } else {
        // Update the order_status_requests table
        $updateRequestSql = "UPDATE order_status_requests SET status = '$action', admin_comment = '$comment' WHERE request_id = '$request_id'";
        if (mysqli_query($db, $updateRequestSql)) {
            // If approved, update the users_orders table as well
            if ($action == 'approved') {
                $updateOrderSql = "UPDATE users_orders SET status = '$requested_status' WHERE o_id = '$order_id'";
                if (!mysqli_query($db, $updateOrderSql)) {
                    $error = "Error updating order status: " . mysqli_error($db);
                }
            }
            $_SESSION['success_message'] = "Request " . ucfirst($action) . " successfully!";
            header("Location: all_orders.php"); // Refresh page
            exit;
        } else {
            $error = "Error updating request: " . mysqli_error($db);
        }
    }
}

// Fetch pending order status requests
$requestSql = "SELECT osr.*, uo.title AS order_title, db.db_name AS delivery_boy_name,uo.o_id AS order_id
               FROM order_status_requests osr
               JOIN users_orders uo ON osr.order_id = uo.o_id
               JOIN delivery_boy db ON osr.delivery_boy_id = db.db_id
               WHERE osr.status = 'pending'";
$requestResult = mysqli_query($db, $requestSql);
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>All Orders</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
        .rating-star {
            color: gold; /* Adjust star color as needed */
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
                    <ul class="navbar-nav mr-auto mt-md-0"></ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                    <div class="col-12">
                        <div class="col-lg-12">
                            <div class="card card-outline-primary">
                                <div class="card-header">
                                    <h4 class="m-b-0 text-white">All Orders</h4>
                                </div>
                                <div class="table-responsive m-t-40">
                                 <?php if (isset($_SESSION['success_message'])): ?>
                                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                                        <?php unset($_SESSION['success_message']); ?>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['error_message'])): ?>
                                        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
                                        <?php unset($_SESSION['error_message']); ?>
                                    <?php endif; ?>
                                    
                                    <table id="myTable" class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>User</th>
                                                <th>Title</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Address</th>
                                                <th>Status</th>
                                                <th>Reg-Date</th>
                                                <th>Rating</th> <!-- New Rating Column -->
                                                <th>Review</th>  <!-- New Review Column -->
                                                <th>Action</th>
                                                <th>Status by delivery boy</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT users.*, users_orders.*, order_ratings.rating, order_ratings.review,
                                                    (SELECT requested_status FROM order_status_requests WHERE order_id = users_orders.o_id AND status = 'pending' ORDER BY request_time DESC LIMIT 1) AS delivery_boy_requested_status
                                                    FROM users
                                                    INNER JOIN users_orders ON users.u_id = users_orders.u_id
                                                    LEFT JOIN order_ratings ON users_orders.o_id = order_ratings.o_id";
                                            $query = mysqli_query($db, $sql);

                                            if (!mysqli_num_rows($query) > 0) {
                                                echo '<td colspan="11"><center>No Orders</center></td>';
                                            } else {
                                                while ($rows = mysqli_fetch_array($query)) {
                                                    echo '<tr>
                                                            <td>' . $rows['username'] . '</td>
                                                            <td>' . $rows['title'] . '</td>
                                                            <td>' . $rows['quantity'] . '</td>
                                                            <td>₹' . $rows['price'] . '</td>
                                                            <td>' . $rows['address'] . '</td>';
                                                    $status = $rows['status'];
                                                    if ($status == "" || $status == "NULL") {
                                                        echo '<td><button type="button" class="btn btn-info"><span class="fa fa-bars" aria-hidden="true"></span> Dispatch</button></td>';
                                                    } elseif ($status == "in process") {
                                                        echo '<td><button type="button" class="btn btn-warning"><span class="fa fa-cog fa-spin" aria-hidden="true"></span> On The Way!</button></td>';
                                                    } elseif ($status == "closed") {
                                                        echo '<td><button type="button" class="btn btn-primary"><span class="fa fa-check-circle" aria-hidden="true"></span> Delivered</button></td>';
                                                    } elseif ($status == "rejected") {
                                                        echo '<td><button type="button" class="btn btn-danger"><i class="fa fa-close"></i> Cancelled</button></td>';
                                                    }
                                                    echo '<td>' . $rows['date'] . '</td>';

                                                    // Display Rating Stars
                                                    echo '<td>';
                                                    if ($rows['rating'] != NULL) {
                                                        for ($i = 0; $i < $rows['rating']; $i++) {
                                                            echo '<i class="fa fa-star rating-star"></i>';
                                                        }
                                                    } else {
                                                        echo 'No Rating';
                                                    }
                                                    echo '</td>';

                                                    // Display Review
                                                    echo '<td>' . ($rows['review'] != NULL ? htmlspecialchars($rows['review']) : 'No Review') . '</td>';

                                                    echo       '<td>
                                                                  <button class="btn btn-danger btn-flat btn-addon btn-xs m-b-10 delete-order" data-order-id="' . $rows['o_id'] . '"><i class="fa fa-trash-o" style="font-size:16px"></i></button>
                                                                  <a href="view_order.php?user_upd=' . $rows['o_id'] . '" class="btn btn-info btn-flat btn-addon btn-sm m-b-10 m-l-5"><i class="fa fa-edit"></i></a>
                                                              </td>';

                                                     // Display delivery boy request status

                                                     echo '<td>';
                                                        if ($rows['delivery_boy_requested_status'] != NULL) {
                                                            echo htmlspecialchars($rows['delivery_boy_requested_status']);
                                                        } else {
                                                            echo 'No Request';
                                                        }
                                                        echo '</td>';

                                                    echo       '</tr>';
                                                }
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
    <script src="js/lib/datatables/datatables.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
    <script src="js/lib/datatables/cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="js/lib/datatables/cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script src="js/lib/datatables/cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.delete-order').click(function(e) {
            e.preventDefault();
            var orderId = $(this).data('order-id'); // Get the order ID from the button's data attribute

            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to delete this order?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform deletion via AJAX
                    $.ajax({
                        url: 'delete_order.php', // Create this file
                        type: 'POST',
                        data: {
                            order_id: orderId
                        },
                        success: function(response) {
                            if (response === 'success') {
                                Swal.fire(
                                    'Deleted!',
                                    'The order has been deleted.',
                                    'success'
                                ).then(() => {
                                    location.reload(); // Refresh the page
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'There was an error deleting the order.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'There was an error deleting the order.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>