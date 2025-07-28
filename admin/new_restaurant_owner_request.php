<?php
session_start();
include("../connection/connect.php"); // Go up one level

// --- PHPMailer Inclusion ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Paths relative to this file (new_restaurant_owner_request.php in admin folder)
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
// --- End PHPMailer Inclusion ---

error_reporting(E_ALL); // Show all errors during development
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

$feedback_message = '';
$feedback_type = 'danger'; // Default to error


// --- Email Sending Function (Keep as is) ---
function sendNotificationEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable for testing
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bhojonbarta@gmail.com'; // Your Gmail
        $mail->Password   = 'zyys vops vyua zetu';    // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Recipients
        $mail->setFrom('no-reply@bhojonbarta.com', 'Bhojon Barta Admin'); // Use a relevant from address
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: Could not send notification to {$toEmail}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
// --- End Email Sending Function ---

// --- POST Request Handling (Keep as is) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    $admin_comment_input = trim($_POST['admin_comment'] ?? '');

    $admin_comment_db = $admin_comment_input ?: ($action === 'approve' ? 'Approved via dashboard.' : 'Rejected via dashboard.');

    if ($request_id && ($action === 'approve' || $action === 'reject')) {
        mysqli_begin_transaction($db);
        try {
            // Fetch request details FIRST
            $sql_fetch = "SELECT * FROM restaurant_owner_requests WHERE request_id = ? AND status = 'pending'";
            $stmt_fetch = mysqli_prepare($db, $sql_fetch);
            if (!$stmt_fetch) throw new Exception("DB Prepare Error (Fetch): " . mysqli_error($db));
            mysqli_stmt_bind_param($stmt_fetch, "i", $request_id);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            $request = mysqli_fetch_assoc($result_fetch);
            mysqli_stmt_close($stmt_fetch);

            if (!$request) {
                throw new Exception("Request #{$request_id} not found or already processed.");
            }

            $ownerEmail = $request['email'];
            $ownerName = $request['name'] ?: 'Applicant';

            if ($action === 'approve') {
                // --- Approval Logic (Keep as is, VERIFY PASSWORD HASHING) ---
                $sql_check = "SELECT owner_id FROM restaurant_owners WHERE email = ?";
                $stmt_check = mysqli_prepare($db, $sql_check);
                if (!$stmt_check) throw new Exception("DB Prepare Error (Check Owner): " . mysqli_error($db));
                mysqli_stmt_bind_param($stmt_check, "s", $request['email']);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                $owner_exists = mysqli_stmt_num_rows($stmt_check) > 0;
                mysqli_stmt_close($stmt_check);

                if ($owner_exists) {
                    throw new Exception("Cannot approve: An owner with email '{$request['email']}' already exists.");
                }

                // *** IMPORTANT: Ensure $request['password'] is ALREADY HASHED securely before this point ***
                // If not, hash it here: $hashed_password = password_hash($request['password'], PASSWORD_DEFAULT);
                // And use $hashed_password in the bind_param below.

                $sql_insert = "INSERT INTO restaurant_owners (email, password, bank_account_number, ifsc_code, account_holder_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt_insert = mysqli_prepare($db, $sql_insert);
                if (!$stmt_insert) throw new Exception("DB Prepare Error (Insert Owner): " . mysqli_error($db));

                // Use the password directly from the request (assuming it's pre-hashed)
                 mysqli_stmt_bind_param($stmt_insert, "sssss",
                     $request['email'],
                     $request['password'], // ASSUMES THIS IS HASHED
                     $request['bank_account_number'],
                     $request['ifsc_code'],
                     $request['account_holder_name']
                 );

                if (!mysqli_stmt_execute($stmt_insert)) {
                     if (mysqli_errno($db) == 1062) { throw new Exception("DB Insert Error: Email '{$request['email']}' likely already exists (concurrent request?)."); }
                     else { throw new Exception("DB Insert Error: " . mysqli_stmt_error($stmt_insert)); }
                }
                mysqli_stmt_close($stmt_insert);

                // Update request status
                $sql_update = "UPDATE restaurant_owner_requests SET status = 'approved', admin_comment = ? WHERE request_id = ?";
                $stmt_update = mysqli_prepare($db, $sql_update);
                if (!$stmt_update) throw new Exception("DB Prepare Error (Update Request): " . mysqli_error($db));
                mysqli_stmt_bind_param($stmt_update, "si", $admin_comment_db, $request_id);
                if (!mysqli_stmt_execute($stmt_update)) { throw new Exception("DB Update Error: " . mysqli_stmt_error($stmt_update)); }
                mysqli_stmt_close($stmt_update);

                mysqli_commit($db);

                // Send Approval Email (CHANGE URL)
                 $subject = "Your Bhojon Barta Restaurant Owner Request Approved!";
                 $ownerLoginUrl = 'http://localhost/OnlineFood-PHP/OnlineFood-PHP/restaurant_owner/restaurant_owner_login.php'; // <<<<<--- CHANGE THIS TO YOUR ACTUAL LIVE URL
                 $body = "<p>Dear {$ownerName},</p>".
                         "<p>Congratulations! Your request to become a restaurant owner on Bhojon Barta has been approved.</p>".
                         "<p>You can now log in to your owner dashboard using the email and password you registered with: <a href='{$ownerLoginUrl}'>Owner Login</a></p>".
                         "<p>Best regards,<br>The Bhojon Barta Team</p>";

                if (sendNotificationEmail($ownerEmail, $ownerName, $subject, $body)) {
                     $feedback_message = "Request #{$request_id} approved. Owner account created. Approval email sent.";
                 } else {
                     $feedback_message = "Request #{$request_id} approved. Owner account created. <strong class='text-warning'>Warning: Could not send approval email.</strong>";
                 }
                 $feedback_type = 'success';

            } else { // action === 'reject'
                // --- Rejection Logic (Keep as is) ---
                $sql_delete = "DELETE FROM restaurant_owner_requests WHERE request_id = ? AND status = 'pending'";
                $stmt_delete = mysqli_prepare($db, $sql_delete);
                 if (!$stmt_delete) throw new Exception("DB Prepare Error (Delete Request): " . mysqli_error($db));
                 mysqli_stmt_bind_param($stmt_delete, "i", $request_id);
                 if (!mysqli_stmt_execute($stmt_delete)) { throw new Exception("DB Delete Error (Reject): " . mysqli_stmt_error($stmt_delete)); }
                 $affected_rows = mysqli_stmt_affected_rows($stmt_delete);
                 mysqli_stmt_close($stmt_delete);

                 if ($affected_rows > 0) {
                    mysqli_commit($db);

                    // Send Rejection Email
                    $subject = "Update on Your Bhojon Barta Restaurant Owner Request";
                    $body = "<p>Dear {$ownerName},</p>".
                            "<p>We are writing about your request (ID: #{$request_id}) to become a restaurant owner on Bhojon Barta.</p>".
                            "<p>Unfortunately, your request has been rejected and removed from our system.</p>";
                    if (!empty($admin_comment_input)) {
                       $body .= "<p><strong>Reason/Comment:</strong> " . htmlspecialchars($admin_comment_input) . "</p>";
                    }
                     $body .= "<p>Best regards,<br>The Bhojon Barta Team</p>";

                    if (sendNotificationEmail($ownerEmail, $ownerName, $subject, $body)) {
                        $feedback_message = "Request #{$request_id} rejected and removed successfully. Rejection email sent.";
                    } else {
                        $feedback_message = "Request #{$request_id} rejected and removed successfully. <strong class='text-warning'>Warning: Could not send rejection email.</strong>";
                    }
                    $feedback_type = 'success';
                 } else {
                     throw new Exception("Could not reject/delete request #{$request_id}. Status might have changed or record not found.");
                 }
            }

        } catch (Exception $e) {
            mysqli_rollback($db);
            $feedback_message = "Error processing request #{$request_id}: " . $e->getMessage();
            $feedback_type = 'danger';
            error_log("Admin Action Error for Request ID {$request_id}: " . $e->getMessage());
        }

        // Store feedback message in session
        $_SESSION['feedback_message'] = $feedback_message;
        $_SESSION['feedback_type'] = $feedback_type;

        // Redirect back to the same page
        header("Location: new_restaurant_owner_request.php");
        exit();

    } else {
        // Invalid action or request ID
        $_SESSION['feedback_message'] = "Invalid action or request ID received.";
        $_SESSION['feedback_type'] = 'danger';
        header("Location: new_restaurant_owner_request.php");
        exit();
    }
}

// Retrieve feedback messages from session
$session_feedback = $_SESSION['feedback_message'] ?? null;
$session_feedback_type = $_SESSION['feedback_type'] ?? 'danger';
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

// --- Fetch Pending Requests for Display (Keep as is) ---
$pending_requests = [];
$fetch_error = null;
$sql_fetch_pending = "SELECT r.*, c.c_name AS category_name
                     FROM restaurant_owner_requests r
                     LEFT JOIN res_category c ON r.category_id = c.c_id
                     WHERE r.status = 'pending'
                     ORDER BY r.request_date DESC";
$result_pending = mysqli_query($db, $sql_fetch_pending);
if ($result_pending) {
    $pending_requests = mysqli_fetch_all($result_pending, MYSQLI_ASSOC);
} else {
    $fetch_error = "Error fetching pending requests: " . mysqli_error($db);
    error_log($fetch_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Admin panel for managing new owner requests">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png"> <!-- Updated path -->
    <title>New Restaurant Owner Requests - Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Original Theme CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <!-- Custom Styles Specific to this Page -->
    <style>
        .card-outline-primary { border-color: #007bff; }
        .card-header.bg-primary { background-color: #007bff !important; color: #fff; }
        .table-responsive { overflow-x: auto; }
        .table td, .table th { vertical-align: middle; font-size: 0.9rem; white-space: nowrap; } /* Start with nowrap */

        /* Allow specific columns to wrap */
        #ownerRequestsTable td:nth-child(2), /* Name */
        #ownerRequestsTable th:nth-child(2),
        #ownerRequestsTable td:nth-child(3), /* Contact */
        #ownerRequestsTable th:nth-child(3),
        #ownerRequestsTable td:nth-child(5), /* Location */
        #ownerRequestsTable th:nth-child(5),
        #ownerRequestsTable td:nth-child(7), /* Docs */
        #ownerRequestsTable th:nth-child(7),
        #ownerRequestsTable td:nth-child(8), /* Bank */
        #ownerRequestsTable th:nth-child(8),
        #ownerRequestsTable td:nth-child(9) { /* Action */
            white-space: normal;
        }
        #ownerRequestsTable th:nth-child(9) { min-width: 250px; } /* Keep action column wide */

        .action-img { width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 5px; }
        .modal-img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        .doc-link { color: #0d6efd; text-decoration: none; display: block; margin-bottom: 3px; }
        .doc-link:hover { text-decoration: underline; }
        .doc-link i { margin-right: 5px; }
        .action-btn { margin-top: 5px; margin-right: 5px; }
        .bank-details { font-size: 0.85em; line-height: 1.4; }
        .bank-details strong { display: inline-block; min-width: 65px; color: #555; }
        .location-details small { display: block; color: #6c757d; }
        .action-form .form-group { margin-bottom: 5px; }
        .action-form textarea { font-size: 0.8rem; }
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

        <!-- Start Header -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- Logo -->
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <!-- Single Logo -->
                        <img src="../images/inc.jpg" alt="Homepage Logo" class="dark-logo main-logo" style="max-height: 40px; width: auto;" />
                    </a>
                </div>
                <!-- End Logo -->
                <div class="navbar-collapse">
                    <!-- toggle and nav items -->
                    <ul class="navbar-nav me-auto mt-md-0">
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  " href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                    </ul>
                    <!-- User profile and search -->
                    <ul class="navbar-nav my-lg-0">
                        <!-- Profile -->
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
        <!-- End Header -->

        <!-- Start Left Sidebar -->
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
                                <li><a href="all_restaurant.php">All Restaurant</a></li>
                                <li><a href="add_category.php">Add Category</a></li>
                                <li><a href="add_restaurant.php">Add Restaurant</a></li>
                                <li><a id="pendingRestaurantLink" href="pending_restaurant.php">Pending Restaurant</a></li>
                                <li><a id="newRestaurantOwnerRequestLink" href="new_restaurant_owner_request.php">New Restaurant Owner Request</a></li> <!-- This Page -->
                                <li><a id="allOwnersLink" href="all_owners.php">All Owners</a></li>
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
                                <li><a id="newDeliveryBoyRequestLink" href="new_delivery_boy_request.php">New Delivery Boy Request</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="coupons.php"><i class="fa fa-ticket"></i><span>Coupons</span></a>
                        </li>
                        <li><span id="footerSettingsLink" class="footer-settings-link"><i class="fa fa-cog"></i> Footer Settings</span></li>
                    </ul>
                </nav>
            </div>
        </div>
        <!-- End Left Sidebar -->

        <!-- Page wrapper -->
        <div class="page-wrapper">
            <!-- Container fluid -->
            <div class="container-fluid">

                 <!-- Bread crumb -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">New Owner Requests</h3>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Restaurant</a></li>
                            <li class="breadcrumb-item active">New Owner Requests</li>
                        </ol>
                    </div>
                </div>
                <!-- End Bread crumb -->

                <!-- Start Page Content -->
                <div class="row">
                    <div class="col-12">

                        <!-- Display Feedback Messages -->
                        <?php if ($session_feedback): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($session_feedback_type); ?> alert-dismissible fade show" role="alert">
                                <?php if($session_feedback_type == 'success'): ?><i class="fas fa-check-circle me-2"></i><?php else: ?><i class="fas fa-exclamation-triangle me-2"></i><?php endif; ?>
                                <?php echo $session_feedback; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($fetch_error): ?>
                             <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($fetch_error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card card-outline-primary shadow-sm">
                             <div class="card-header bg-primary">
                                <h4 class="m-b-0 text-white">Pending Restaurant Owner Requests</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive m-t-20">
                                    <!-- Changed table ID for potential uniqueness -->
                                    <table id="ownerRequestsTable" class="table table-bordered table-hover table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Category</th>
                                                <th>Location</th>
                                                <th>Requested</th>
                                                <th>Docs</th>
                                                <th>Bank Details</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($pending_requests) > 0): ?>
                                                <?php foreach ($pending_requests as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($row['email']); ?><br>
                                                            <small><i class="fa fa-phone me-1 text-secondary"></i><?php echo htmlspecialchars($row['phone']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['category_name'] ?: 'N/A'); ?></td>
                                                        <td class="location-details">
                                                            <?php echo htmlspecialchars($row['city']); ?>
                                                            <small><?php echo htmlspecialchars($row['address']); ?></small>
                                                            <small>(Lat: <?php echo htmlspecialchars($row['latitude']); ?>, Lng: <?php echo htmlspecialchars($row['longitude']); ?>)</small>
                                                        </td>
                                                        <td><?php echo date("d M Y H:i", strtotime($row['request_date'])); ?></td>
                                                        <td>
                                                            <?php $hasDocs = false; ?>
                                                            <?php // Check and display Restaurant Photo ?>
                                                            <?php if (!empty($row['restaurant_photo'])): $hasDocs=true; $doc_path = "../" . ltrim($row['restaurant_photo'], '/'); ?>
                                                                <?php if(file_exists($doc_path)): ?>
                                                                    <a href="#" class="action-img-link" data-bs-toggle="tooltip" title="View Photo"><img src="<?php echo htmlspecialchars($doc_path); ?>" alt="Photo" class="action-img"></a>
                                                                <?php else: echo '<small class="text-danger fst-italic d-block mb-1">Photo Missing</small>'; endif; ?>
                                                            <?php endif; ?>
                                                            <?php // Check and display FSSAI License ?>
                                                            <?php if (!empty($row['fssai_license'])): $hasDocs=true; $doc_path = "../" . ltrim($row['fssai_license'], '/'); ?>
                                                                <?php if(file_exists($doc_path)): ?>
                                                                    <a href="<?php echo htmlspecialchars($doc_path); ?>" target="_blank" class="doc-link text-danger" data-bs-toggle="tooltip" title="View FSSAI PDF"><i class="fas fa-file-pdf"></i> FSSAI</a>
                                                                <?php else: echo '<small class="text-danger fst-italic d-block mb-1">FSSAI Missing</small>'; endif; ?>
                                                            <?php endif; ?>
                                                            <?php // Check and display Aadhar Card ?>
                                                            <?php if (!empty($row['aadhar_card'])): $hasDocs=true; $doc_path = "../" . ltrim($row['aadhar_card'], '/'); ?>
                                                                <?php if(file_exists($doc_path)): ?>
                                                                    <a href="<?php echo htmlspecialchars($doc_path); ?>" target="_blank" class="doc-link text-primary" data-bs-toggle="tooltip" title="View Aadhar PDF"><i class="fas fa-id-card"></i> Aadhar</a>
                                                                <?php else: echo '<small class="text-danger fst-italic d-block mb-1">Aadhar Missing</small>'; endif; ?>
                                                            <?php endif; ?>
                                                            <?php if (!$hasDocs): ?>
                                                                <span class="text-muted fst-italic">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="bank-details">
                                                            <?php if (!empty($row['account_holder_name'])): ?>
                                                                <strong>Holder:</strong> <?php echo htmlspecialchars($row['account_holder_name']); ?><br>
                                                                <strong>Acc No:</strong> <?php echo htmlspecialchars($row['bank_account_number']); ?><br>
                                                                <strong>IFSC:</strong> <?php echo htmlspecialchars($row['ifsc_code']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted fst-italic">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" class="action-form d-inline-block" action="new_restaurant_owner_request.php">
                                                                <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                                <input type="hidden" name="action" class="action-input" value="">
                                                                <div class="form-group">
                                                                    <textarea name="admin_comment" class="form-control form-control-sm" rows="1" placeholder="Optional comment (reason for rejection)"></textarea>
                                                                </div>
                                                                <button type="button" class="btn btn-success btn-sm action-btn approve-btn" data-action="approve" data-bs-toggle="tooltip" title="Approve Owner Account & Send Email">
                                                                    <i class="fas fa-user-check"></i> Approve
                                                                </button>
                                                                <button type="button" class="btn btn-danger btn-sm action-btn reject-btn" data-action="reject" data-bs-toggle="tooltip" title="Reject Request, Remove Record & Send Email">
                                                                    <i class="fas fa-user-times"></i> Reject
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="9" class="text-center fst-italic text-muted">No Pending Requests Found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Page Content -->

            </div>
            <!-- End Container fluid -->

             <!-- FOOTER REMOVED -->

        </div>
        <!-- End Page wrapper -->

    </div>
    <!-- End Main Wrapper -->

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Restaurant Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-img" id="modalImage" alt="Restaurant Photo Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- JS Includes -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Theme Specific JS -->
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>

    <!-- Page Specific JS -->
    <script>
    $(document).ready(function() {
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Show image in modal - Trigger via link now
        $('.action-img-link').click(function(e) {
            e.preventDefault();
            var imgSrc = $(this).find('.action-img').attr('src'); // Get src from child img
            if (imgSrc) {
                $('#modalImage').attr('src', imgSrc);
                var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                imageModal.show();
            }
        });

        // Handle action buttons using SweetAlert2 confirmation
        $('.action-btn').click(function() {
            var button = $(this);
            var action = button.data('action');
            var form = button.closest('form.action-form');
            form.find('input.action-input').val(action);

            var confirmButtonColor = (action === 'approve') ? '#28a745' : '#dc3545';
            var confirmButtonText = (action === 'approve') ? 'Yes, approve!' : 'Yes, reject!';
            var titleText = `Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}`;
            var alertText = (action === 'approve')
                 ? `Approve this owner request? An email notification will be sent.`
                 : `Reject this owner request? The record will be removed and an email sent.`;

            Swal.fire({
                title: titleText,
                text: alertText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                     form.find('.action-btn').prop('disabled', true);
                     button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                     form.submit();
                }
            });
        });

         // Highlight the correct sidebar link
         $('#newRestaurantOwnerRequestLink').closest('li').addClass('active');
         var parentUl = $('#newRestaurantOwnerRequestLink').closest('ul.collapse');
         if(parentUl.length) {
             parentUl.addClass('in').attr('aria-expanded', 'true'); // Ensure dropdown is open
             parentUl.closest('li').children('a.has-arrow').addClass('active'); // Highlight parent dropdown link
         }
          $('#footerSettingsLink').on('click', function(e) { // Keep footer link functionality
             e.preventDefault();
             alert('Footer Settings Clicked! Implement navigation.');
             console.log('Footer settings link clicked.');
        });

    });
    </script>
</body>
</html>
<?php
// Close connection
if (isset($db) && $db) {
    mysqli_close($db);
}
?>