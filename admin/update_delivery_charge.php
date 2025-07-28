<?php
include("../connection/connect.php");
session_start();
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Function to sanitize inputs
function sanitizeInput($data) {
  global $db;
  return mysqli_real_escape_string($db, trim($data));
}

// Handle form submission for updating delivery charge
if (isset($_POST['update_delivery_charge'])) {
    $delivery_charge_id = intval($_POST['delivery_charge_id']);
    $new_delivery_charge = floatval($_POST['delivery_charge']); // Validate and sanitize input
    $description = sanitizeInput($_POST['description']); // Sanitize the description
    $min_order_value = !empty($_POST['min_order_value']) ? floatval($_POST['min_order_value']) : null;

    //Validate Delivery Charge ID
    if (empty($delivery_charge_id)) {
       $error_message = "Delivery Charge ID is missing. Please update the record";
    } elseif ($new_delivery_charge < 0) {
      $error_message = "Delivery charge cannot be negative.";
    } else {

        //Update the Delivery Charges in the delivary_charges table.
        $sql = "UPDATE delivary_charges SET delivery_charge = ?, description = ?, min_order_value = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
         if ($stmt) {
            $stmt->bind_param("dsdi", $new_delivery_charge, $description, $min_order_value, $delivery_charge_id);
             if ($stmt->execute()) {
                $success_message = "Delivery charge updated successfully!";
            } else {
                $error_message = "Failed to update delivery charge: " . $stmt->error;
            }

            $stmt->close();
          } else {
                $error_message = "Failed to prepare the query " . $db->error;
          }
    }
}

//Handle adding new delivery charge if none exists
if (isset($_POST['add_delivery_charge'])) {
    $new_delivery_charge = floatval($_POST['delivery_charge']); // Validate and sanitize input
    $description = sanitizeInput($_POST['description']); // Sanitize the description
    $min_order_value = !empty($_POST['min_order_value']) ? floatval($_POST['min_order_value']) : null;

     if ($new_delivery_charge < 0) {
      $error_message = "Delivery charge cannot be negative.";
    } else {

        $sql = "INSERT INTO delivary_charges (delivery_charge, description, min_order_value) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        if($stmt){
           $stmt->bind_param("dsd", $new_delivery_charge, $description, $min_order_value);

           if ($stmt->execute()) {
               $success_message = "Delivery charge added successfully!";
           } else {
               $error_message = "Failed to add delivery charge: " . $stmt->error;
           }
           $stmt->close();
        } else {
           $error_message = "Failed to prepare the query " . $db->error;
        }
    }

}

// Fetch the current delivery charge from the delivary_charges table
$sql = "SELECT id, delivery_charge, description, min_order_value FROM delivary_charges ORDER BY id ASC LIMIT 1";
$delivery_charge_query = mysqli_query($db, $sql);

if ($delivery_charge_query && mysqli_num_rows($delivery_charge_query) > 0) {
    $delivery_charge_row = mysqli_fetch_assoc($delivery_charge_query);
    $current_delivery_charge = $delivery_charge_row['delivery_charge'];
    $delivery_charge_id = $delivery_charge_row['id'];
    $delivery_charge_description = $delivery_charge_row['description'];
    $current_min_order_value = $delivery_charge_row['min_order_value'];
} else {
    $current_delivery_charge = 0.00; // Default value if not found
    $delivery_charge_id = null;
    $delivery_charge_description = "";
    $current_min_order_value = null;
}

//Function for fetching threshold
function getFreeDeliveryThreshold($db){
     $sql = "SELECT setting_value FROM settings WHERE setting_name = 'free_delivery_threshold'";
        $threshold_query = mysqli_query($db, $sql);

         if ($threshold_query && mysqli_num_rows($threshold_query) > 0) {
            $threshold_row = mysqli_fetch_assoc($threshold_query);
            return  floatval($threshold_row['setting_value']);
         }
         return 1000;  // default value
}

//Handle form submission for updating delivery threshold
if (isset($_POST['update_delivery_threshold'])) {
  $new_threshold = floatval($_POST['delivery_threshold']); // Validate and sanitize input

  if ($new_threshold < 0) {
     $error_message = "Delivery threshold cannot be negative.";
  } else {

    //Update the Delivery Charges in the settings table.
    $sql = "UPDATE settings SET setting_value = ? WHERE setting_name = 'free_delivery_threshold'";
    $stmt = $db->prepare($sql);
     if ($stmt) {
          $stmt->bind_param("d", $new_threshold);  // d for double

             if ($stmt->execute()) {
                $success_message = "Delivery threshold updated successfully!";
            } else {
                $error_message = "Failed to update delivery threshold: " . $stmt->error;
            }

            $stmt->close();
      } else {
            $error_message = "Failed to prepare the query " . $db->error;
      }
  }
}

// Load up to php
$free_delivery_threshold = getFreeDeliveryThreshold($db);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Update Delivery Charge</title> <!-- Change title-->
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="fix-header">
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <div id="main-wrapper">
        <div class="header">
            <!--Added Header -->
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <span><img src="images/icn.png" alt="homepage" class="dark-logo" /></span>
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
        </div> <!--End Header -->

        <div class="left-sidebar"> <!-- Added Left Sidebar -->
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
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-cutlery"
                                    aria-hidden="true"></i><span class="hide-menu">Menu</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_menu.php">All Menues</a></li>
                                <li><a href="add_menu.php">Add Menu</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="all_orders.php"><i class="fa fa-shopping-cart"
                                    aria-hidden="true"></i><span>Orders</span></a>
                        </li>

                        <!-- Delivery Boy Links -->
                        <li class="nav-item">
                            <!--Here add the class to open it by default-->
                            <a class="has-arrow" href="#" aria-expanded="true"><i class="fa fa-motorcycle"
                                    aria-hidden="true"></i><span class="hide-menu">Delivery Boy</span></a>
                            <ul aria-expanded="true" class="collapse">
                                <li><a href="add_delivery_boy.php">Add Delivery Boy</a></li>
                                <li><a href="all_delivery_boys.php">All Delivery Boys</a></li>
                                <li><a href="assigned_delivary.php">Assign Delivery</a></li>
                            </ul>
                        </li>
                        <!-- Delivery Boy Links End-->
                    </ul>
                </nav>
            </div>
        </div> <!-- End Left Sidebar -->

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <!-- Centering the form -->
                        <div class="card p-30">
                            <div class="card-title">Update Delivery Settings</div>

                            <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>

                              <form method="POST">
                                <div class="form-group">
                                     <label for="delivery_threshold">Free Delivery Threshold (RS):</label>
                                        <input type="number" class="form-control" id="delivery_threshold"
                                           name="delivery_threshold" step="0.01"
                                               value="<?php echo htmlspecialchars($free_delivery_threshold); ?>" required>
                                </div>
                                <button type="submit" name="update_delivery_threshold"
                                           class="btn btn-primary">Update Threshold</button>
                             </form>
                             <?php if ($delivery_charge_id === null): ?>
                                <div class="alert alert-warning">No delivery charge rule found. Add one below.</div>
                                  <form method="POST">
                                        <div class="form-group">
                                            <label for="delivery_charge">Delivery Charge:</label>
                                            <input type="number" class="form-control" id="delivery_charge"
                                                name="delivery_charge" step="0.01" required>
                                        </div>
                                         <div class="form-group">
                                            <label for="min_order_value">Minimum Order Value (Optional):</label>
                                            <input type="number" class="form-control" id="min_order_value"
                                                name="min_order_value" step="0.01">
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Description:</label>
                                            <input type="text" class="form-control" id="description" name="description">
                                        </div>
                                        <button type="submit" name="add_delivery_charge"
                                            class="btn btn-success">Add Delivery Charge</button>
                                    </form>

                            <?php else: ?>

                            <form method="POST">
                                <input type="hidden" name="delivery_charge_id"
                                    value="<?php echo htmlspecialchars($delivery_charge_id); ?>">
                                <div class="form-group">
                                    <label for="delivery_charge">Delivery Charge:</label>
                                    <input type="number" class="form-control" id="delivery_charge"
                                        name="delivery_charge" step="0.01"
                                        value="<?php echo htmlspecialchars($current_delivery_charge); ?>" required>
                                </div>
                                     <div class="form-group">
                                        <label for="min_order_value">Minimum Order Value (Optional):</label>
                                        <input type="number" class="form-control" id="min_order_value"
                                            name="min_order_value" step="0.01"
                                            value="<?php echo htmlspecialchars($current_min_order_value); ?>">
                                    </div>
                                <div class="form-group">
                                    <label for="description">Description:</label>
                                    <input type="text" class="form-control" id="description" name="description"
                                        value="<?php echo htmlspecialchars($delivery_charge_description); ?>">
                                </div>
                                <button type="submit" name="update_delivery_charge"
                                    class="btn btn-primary">Update Charge</button>
                            </form>
                              <?php endif; ?>
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
    <script src="js/custom.min.js"></script>
</body>

</html>