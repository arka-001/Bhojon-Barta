<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

if(isset($_POST['submit']))
{
    if(empty($_POST['c_name']))
    {
        $error = '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Category name is required!"
                    });
                  </script>';
    }
    else
    {
        $c_name = $_POST['c_name'];

        $check_cat = mysqli_query($db, "SELECT c_name FROM res_category WHERE c_name = '$c_name'");

        if(mysqli_num_rows($check_cat) > 0)
        {
            $error = '<script>
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Category already exists!"
                        });
                      </script>';
        }
        else
        {
            $img_name = $_FILES['image']['name'];
            $img_tmp = $_FILES['image']['tmp_name'];
            $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_loc = "category_images/" . $img_name;

            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif'); // Validate extensions

            if (!in_array(strtolower($img_ext), $allowed_extensions)) {
                $error = '<script>
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed."
                            });
                          </script>';
            }
            else{
               move_uploaded_file($img_tmp, "category_images/" . $img_name);


                $stmt = $db->prepare("INSERT INTO res_category (c_name, image) VALUES (?, ?)");
                $stmt->bind_param("ss", $c_name, $img_name);
                $result = $stmt->execute();

                if ($result) {
                    $success = '<script>
                                Swal.fire({
                                    icon: "success",
                                    title: "Success",
                                    text: "New category added successfully!"
                                });
                              </script>';
                } else {
                    $error = '<script>
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: "Failed to add category. Please try again."
                                });
                              </script>';
                }
                $stmt->close();
            }

        }
    }
}
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Add Category</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
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
                    <ul class="navbar-nav mr-auto mt-md-0">
                    </ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted  " href="#" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false"><img src="images/bookingSystem/user-icn.png"
                                    alt="user" class="profile-pic" /></a>
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
                <div class="row">
                    <div class="col-lg-12">
                        <?php echo $error; ?>
                        <?php echo $success; ?>
                        <div class="card card-outline-primary">
                            <div class="card-header">
                                <h4 class="m-b-0 text-white">Add Restaurant Category</h4>
                            </div>
                            <div class="card-body">
                                <form action='' method='post' enctype="multipart/form-data">
                                    <div class="form-body">
                                        <hr>
                                        <div class="row p-t-20">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Category</label>
                                                    <input type="text" name="c_name" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Category Image</label>
                                                    <input type="file" name="image" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <input type="submit" name="submit" class="btn btn-primary" value="Save">
                                        <a href="add_category.php" class="btn btn-inverse">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Listed Categories</h4>
                                <div class="table-responsive m-t-40">
                                    <table id="myTable" class="table table-bordered table-hover table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Category Name</th>
                                                <th>Image</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $sql="SELECT * FROM res_category order by c_id desc";
                                                $query=mysqli_query($db,$sql);

                                                if(!mysqli_num_rows($query) > 0 )
                                                {
                                                    echo '<td colspan="7"><center>No Categories-Data!</center></td>';
                                                }
                                                else
                                                {
                                                    while($rows=mysqli_fetch_array($query))
                                                    {
                                                        echo ' <tr><td>'.$rows['c_id'].'</td>
                                                                    <td>'.$rows['c_name'].'</td>
                                                                    <td><img src="category_images/'.$rows['image'].'" alt="Category Image" width="50"></td>
                                                                    <td>'.$rows['date'].'</td>
                                                                    <td>
                                                                        <a href="javascript:void(0);" onclick="confirmDelete(\'' . $rows['c_id'] . '\')" class="btn btn-danger btn-flat btn-addon btn-xs m-b-10"><i class="fa fa-trash-o" style="font-size:16px"></i></a>
                                                                        <a href="update_category.php?cat_upd='.$rows['c_id'].'" class="btn btn-info btn-flat btn-addon btn-sm m-b-10 m-l-5"><i class="fa fa-edit"></i></a>
                                                                    </td></tr>';
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

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

    <script>
    function confirmDelete(categoryId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Are you sure you want to delete this category?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_category.php?cat_del=' + categoryId;
            }
        })
    }
    </script>

</body>

</html>