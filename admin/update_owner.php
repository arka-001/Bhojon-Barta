<?php
session_start();
include("../connection/connect.php"); // Go up one level to connection folder
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['adm_id'])) {
    header("Location: index.php");
    exit();
}

$db = $GLOBALS['db']; // Use global connection
if (!$db) {
    die("Database Connection Error. Check connection/connect.php");
}

$owner_id_to_update = null;
$email_val = ''; // Renamed to avoid conflict with $email from DB schema
$account_holder_name_val = '';
$bank_account_number_val = '';
$ifsc_code_val = '';
$password_change_info = "Leave blank to keep current password.";

$error_message = '';
$success_message = ''; // For direct display on page, usually redirects occur

// --- Fetch owner data for editing ---
if (isset($_GET['owner_upd']) && is_numeric($_GET['owner_upd'])) {
    $owner_id_to_update = intval($_GET['owner_upd']);

    $stmt_fetch = $db->prepare("SELECT email, account_holder_name, bank_account_number, ifsc_code FROM restaurant_owners WHERE owner_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $owner_id_to_update);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $owner_data = $result_fetch->fetch_assoc();
            $email_val = htmlspecialchars($owner_data['email']);
            $account_holder_name_val = htmlspecialchars($owner_data['account_holder_name'] ?? '');
            $bank_account_number_val = htmlspecialchars($owner_data['bank_account_number'] ?? '');
            $ifsc_code_val = htmlspecialchars($owner_data['ifsc_code'] ?? '');
        } else {
            $_SESSION['feedback_message'] = "Owner not found (ID: {$owner_id_to_update}).";
            $_SESSION['feedback_type'] = "danger";
            header("Location: all_owners.php");
            exit();
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['feedback_message'] = "Database error preparing to fetch owner: " . $db->error;
        $_SESSION['feedback_type'] = "danger";
        header("Location: all_owners.php");
        exit();
    }
} else {
    $_SESSION['feedback_message'] = "Invalid or missing owner ID for update.";
    $_SESSION['feedback_type'] = "danger";
    header("Location: all_owners.php");
    exit();
}


// --- Handle Form Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_update_owner'], $_POST['owner_id_hidden']) && is_numeric($_POST['owner_id_hidden'])) {
        $submitted_owner_id = intval($_POST['owner_id_hidden']);
        // Ensure we are updating the correct owner, even if GET param was lost on a bad POST
        $owner_id_to_update = $submitted_owner_id;


        // Sanitize and get new values
        $new_email = trim($_POST['email']);
        $new_password = $_POST['password']; // Don't trim password
        $new_confirm_password = $_POST['confirm_password'];
        
        // Handle potentially empty bank details by setting them to null
        $new_account_holder_name = !empty(trim($_POST['account_holder_name'])) ? trim($_POST['account_holder_name']) : null;
        $new_bank_account_number = !empty(trim($_POST['bank_account_number'])) ? trim($_POST['bank_account_number']) : null;
        $new_ifsc_code = !empty(trim($_POST['ifsc_code'])) ? trim($_POST['ifsc_code']) : null;


        // Basic validation
        if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        }
        elseif (!empty($new_password) && $new_password !== $new_confirm_password) {
            $error_message = "New passwords do not match.";
        }
        elseif (!empty($new_password) && strlen($new_password) < 6) {
             $error_message = "New password must be at least 6 characters long.";
        }
        // Add more specific validation for bank details if required (e.g., length, format)

        if (empty($error_message)) {
            $update_fields_sql = [];
            $bind_types_str = "";
            $bind_params_arr = [];

            // Always include email
            $update_fields_sql[] = "email = ?";
            $bind_types_str .= "s";
            $bind_params_arr[] = &$new_email;

            // Bank details (will bind NULL if variables are null)
            $update_fields_sql[] = "account_holder_name = ?";
            $bind_types_str .= "s";
            $bind_params_arr[] = &$new_account_holder_name;

            $update_fields_sql[] = "bank_account_number = ?";
            $bind_types_str .= "s";
            $bind_params_arr[] = &$new_bank_account_number;
            
            $update_fields_sql[] = "ifsc_code = ?";
            $bind_types_str .= "s";
            $bind_params_arr[] = &$new_ifsc_code;

            // Password update (only if a new password is provided)
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_fields_sql[] = "password = ?";
                $bind_types_str .= "s";
                $bind_params_arr[] = &$hashed_password;
            }
            
            // Add owner_id for WHERE clause
            $bind_types_str .= "i";
            $bind_params_arr[] = &$submitted_owner_id;

            if (!empty($update_fields_sql)) {
                $sql_update_query = "UPDATE restaurant_owners SET " . implode(", ", $update_fields_sql) . " WHERE owner_id = ?";
                
                $stmt_update = $db->prepare($sql_update_query);
                if ($stmt_update) {
                    // Prepend bind_types_str to bind_params_arr for call_user_func_array
                    $params_for_bind = array_merge([$bind_types_str], $bind_params_arr);
                    call_user_func_array([$stmt_update, 'bind_param'], $params_for_bind);

                    if ($stmt_update->execute()) {
                        if ($stmt_update->affected_rows > 0) {
                            $_SESSION['feedback_message'] = "Owner (ID: {$submitted_owner_id}) details updated successfully.";
                            $_SESSION['feedback_type'] = "success";
                        } elseif ($stmt_update->affected_rows === 0 && $stmt_update->errno === 0) {
                            $_SESSION['feedback_message'] = "No changes detected for owner (ID: {$submitted_owner_id}). Details remain the same.";
                            $_SESSION['feedback_type'] = "info";
                        } else { // Should ideally not happen if execute was true and affected_rows is not > 0 or 0
                             $_SESSION['feedback_message'] = "Update executed but status unclear for owner (ID: {$submitted_owner_id}). Error: " . $stmt_update->error;
                             $_SESSION['feedback_type'] = "warning";
                        }
                        header("Location: all_owners.php");
                        exit();
                    } else {
                        if ($db->errno == 1062) { // Duplicate entry for unique key (likely email)
                            $error_message = "Error: The email address '" . htmlspecialchars($new_email) . "' is already registered to another owner.";
                        } else {
                            $error_message = "Database error executing update: " . $stmt_update->error . " (Code: " . $db->errno . ")";
                        }
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Database error preparing update statement: " . $db->error;
                }
            } else {
                 $error_message = "No fields were specified for update."; // Should not happen with this logic
            }
        }

        // If there was an error, repopulate form fields with submitted (failed) data for user correction
        if (!empty($error_message)) {
            $email_val = htmlspecialchars($new_email);
            $account_holder_name_val = htmlspecialchars($new_account_holder_name ?? '');
            $bank_account_number_val = htmlspecialchars($new_bank_account_number ?? '');
            $ifsc_code_val = htmlspecialchars($new_ifsc_code ?? '');
            // Password fields are intentionally not repopulated for security
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Admin panel for updating restaurant owner details">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <title>Update Owner Details - Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <!-- Original Theme CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .card-outline-primary { border-color: #007bff; }
        .card-header.bg-primary { background-color: #007bff !important; color: #fff; }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .form-text { font-size: 0.875em; }
    </style>
</head>

<body class="fix-header fix-sidebar">
    <!-- Preloader -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>

    <!-- Main wrapper -->
    <div id="main-wrapper">
        <!-- Header (Copied from all_owners.php) -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <img src="../images/inc.jpg" alt="Homepage Logo" class="dark-logo main-logo" style="max-height: 40px; width: auto;" />
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav me-auto mt-md-0">
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  " href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                    </ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="../images/bookingSystem/user-icn.png" alt="user" class="profile-pic" /></a>
                             <div class="dropdown-menu dropdown-menu-end animated zoomIn">
                                <ul class="dropdown-user">
                                    <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Left Sidebar (Copied from all_owners.php) -->
        <div class="left-sidebar">
            <div class="scroll-sidebar">
                <nav class="sidebar-nav">
                   <ul id="sidebarnav">
                        <li class="nav-devider"></li>
                        <li class="nav-label">Home</li>
                        <li> <a href="dashboard.php"><i class="fa fa-tachometer"></i><span>Dashboard</span></a></li>
                        <li class="nav-label">Log</li>
                        <li> <a href="all_users.php">  <span><i class="fa fa-user f-s-20 "></i></span><span>Users</span></a></li>
                        <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-archive f-s-20 color-warning"></i><span class="hide-menu">Restaurant</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_restaurant.php">All Restaurants</a></li>
                                <li><a href="add_category.php">Add Category</a></li>
                                <li><a href="add_restaurant.php">Add Restaurant</a></li>
                                <li><a href="pending_restaurant.php">Pending Restaurants</a></li>
                                <li><a href="new_restaurant_owner_request.php">New Owner Requests</a></li>
                                <li><a id="allOwnersLink" href="all_owners.php">All Owners</a></li> <!-- This should be active -->
                            </ul>
                        </li>
                       <li> <a class="has-arrow  " href="#" aria-expanded="false"><i class="fa fa-cutlery" aria-hidden="true"></i><span class="hide-menu">Menu</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_menu.php">All Menus</a></li>
                                <li><a href="add_menu.php">Add Menu</a></li>
                            </ul>
                        </li>
                         <li> <a href="all_orders.php"><i class="fa fa-shopping-cart" aria-hidden="true"></i><span>Orders</span></a></li>
                        <li class="nav-item">
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-motorcycle" aria-hidden="true"></i><span class="hide-menu">Delivery Boy</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="add_delivery_boy.php">Add Delivery Boy</a></li>
                                <li><a href="all_delivery_boys.php">All Delivery Boys</a></li>
                                <li><a href="assigned_delivary.php">Assigned Delivery</a></li>
                                <li><a href="delivery_charges.php">Delivery Charges</a></li>
                                <li><a href="delivery_city.php">Delivery City</a></li>
                                 <li><a href="new_delivery_boy_request.php">New Delivery Boy Request</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="coupons.php"><i class="fa fa-ticket"></i><span>Coupons</span></a>
                        </li>
                        <li><a href="javascript:void(0);" id="footerSettingsLink"><i class="fa fa-cog"></i><span>Footer Settings</span></a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Page wrapper -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <!-- Bread crumb -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">Update Owner Details</h3>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="all_owners.php">All Owners</a></li>
                            <li class="breadcrumb-item active">Update Owner</li>
                        </ol>
                    </div>
                </div>

                <!-- Start Page Content -->
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1 col-sm-12">
                        <div class="card card-outline-primary shadow-sm">
                            <div class="card-header bg-primary">
                                <h4 class="m-b-0 text-white">Edit Owner (ID: <?php echo htmlspecialchars($owner_id_to_update); ?>)</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo $error_message; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="update_owner.php?owner_upd=<?php echo htmlspecialchars($owner_id_to_update); ?>" method="POST" novalidate>
                                    <input type="hidden" name="owner_id_hidden" value="<?php echo htmlspecialchars($owner_id_to_update); ?>">

                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email_val; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-bold">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password" aria-describedby="passwordHelp">
                                        <div id="passwordHelp" class="form-text"><?php echo $password_change_info; ?> Minimum 6 characters.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label fw-bold">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>

                                    <hr class="my-4">
                                    <h5 class="mb-3">Bank Details (Optional - Clear fields to remove)</h5>

                                    <div class="mb-3">
                                        <label for="account_holder_name" class="form-label fw-bold">Account Holder Name</label>
                                        <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" value="<?php echo $account_holder_name_val; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="bank_account_number" class="form-label fw-bold">Bank Account Number</label>
                                        <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="<?php echo $bank_account_number_val; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="ifsc_code" class="form-label fw-bold">IFSC Code</label>
                                        <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" value="<?php echo $ifsc_code_val; ?>">
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <a href="all_owners.php" class="btn btn-secondary me-md-2">Cancel</a>
                                        <button type="submit" name="submit_update_owner" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Owner</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Page Content -->
            </div>
            <!-- Footer -->
            <footer class="footer"> © <?php echo date("Y"); ?> All rights reserved. </footer>
        </div>
    </div>

    <!-- JS Includes -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script>
        $(document).ready(function() {
            // Activate the correct sidebar item
            $('#allOwnersLink').closest('li').addClass('active'); // Make the "All Owners" link active
            var parentUl = $('#allOwnersLink').closest('ul.collapse');
            if (parentUl.length) {
                parentUl.addClass('in').attr('aria-expanded', 'true'); // Expand the parent "Restaurant" menu
                parentUl.closest('li.has-arrow').children('a.has-arrow').addClass('active'); // Make "Restaurant" link active
            }
             $('#footerSettingsLink').on('click', function(e) {
                 e.preventDefault();
                 // Replace with actual navigation or modal for footer settings
                 alert('Footer Settings Clicked! Implement navigation to footer settings page or modal.');
            });
        });
    </script>
</body>
</html>
<?php
if (isset($db) && $db) {
    mysqli_close($db);
}
?>