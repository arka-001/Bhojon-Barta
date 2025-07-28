<?php
// Include database connection (relative path from admin folder)
include_once("../connection/connect.php");

// Enable error reporting (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // SET TO 0 IN PRODUCTION
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if admin is not logged in
if (empty($_SESSION["adm_id"])) {
    error_log("Admin not logged in, redirecting from pending_restaurant.php");
    header("Location: index.php");
    exit();
}

// Path definitions (Ensure these are correct for your setup)
$scriptDir = __DIR__;
$projectRootWeb = "/OnlineFood-PHP/OnlineFood-PHP"; // Adjust if needed for web paths
$imageDir = $scriptDir . "/Res_img/";         // Server path for images
$docsDir = $scriptDir . "/Owner_docs/";      // Server path for docs
$imageWebPath = $projectRootWeb . "/admin/Res_img/"; // Web path for images
$docsWebPath = $projectRootWeb . "/admin/Owner_docs/";   // Web path for docs
$debug = true; // Set to false in production

$db = $GLOBALS['db']; // Use the connection from connect.php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Admin panel for managing pending restaurant requests">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png"> <!-- FIX: Path to favicon -->
    <title>Pending Restaurant Requests - Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">

    <!-- Original Theme CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <!-- Custom Styles Specific to this Page -->
    <style>
        .card-outline-primary {
            border-color: #007bff;
        }
        .card-header.bg-primary {
            background-color: #007bff !important;
            color: #fff; /* Ensure text is white on primary background */
        }
        .table-responsive {
             overflow-x: auto; /* Ensure responsiveness */
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 8px !important; /* Adjusted padding for consistency */
            font-size: 14px;
            white-space: nowrap; /* Prevent wrapping initially */
        }
        /* Allow wrapping for specific columns */
        .table td:nth-child(10), /* Address */
        .table th:nth-child(10) {
            white-space: normal;
            min-width: 200px; /* Give address column some space */
        }

        /* FIX: Removed direct background from th. Let .thead-dark handle it */
        /* .table th {
            background-color:rgb(0, 0, 0);
            color: #fff;
        } */

        /* FIX: Add rule to ensure thead is visible */
        #requestsTable thead {
            visibility: visible !important;
            display: table-header-group !important; /* Ensure correct display type */
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .img-thumbnail {
            cursor: pointer;
            transition: transform 0.2s;
            border-radius: 4px;
            max-width: 50px; /* Keep thumbnail small */
            max-height: 50px;
            height: auto;
            display: inline-block; /* Adjust display */
        }
        .img-thumbnail:hover {
            transform: scale(1.1);
        }
        .doc-link {
            text-decoration: underline;
            color: #007bff; /* Match primary color */
        }
        .doc-link:hover {
            color: #0056b3;
        }
        .btn-group .btn {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px; /* Consistent border radius */
        }
        .btn-group .btn i {
            margin-right: 5px;
        }
        #requestsTable_wrapper {
            margin-top: 15px;
        }
        /* Adjust spacing for DataTable controls if needed */
        #requestsTable_wrapper .row > div {
            margin-bottom: 10px;
        }
        .dataTables_length, .dataTables_filter {
            margin-bottom: 10px;
        }
        .dt-buttons { /* Style datatable buttons container */
           margin-bottom: 10px;
        }
        .dataTables_paginate {
            margin-top: 10px;
        }
        .btn-success, .btn-danger {
            transition: background-color 0.2s;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        /* Diet Type Styles */
        .diet-type-display .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 4px;
            vertical-align: middle; /* Align icon better */
        }
        .diet-type-display .icon svg {
            width: 100%;
            height: 100%;
        }
        .diet-type-display.veg { color: #28a745; }
        .diet-type-display.veg .icon svg { fill: #28a745; }
        .diet-type-display.vegan { color: #17a2b8; }
        .diet-type-display.vegan .icon svg { fill: #17a2b8; }
        .diet-type-display.nonveg { color: #dc3545; }
        .diet-type-display.nonveg .icon svg { fill: #dc3545; }
        .diet-type-display.all { color: #6c757d; }
        .diet-type-display.all .icon svg { fill: #6c757d; }
    </style>
</head>

<body class="fix-header fix-sidebar">
    <!-- Preloader - style you can find in spinners.css -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>

    <!-- Main wrapper - style you can find in pages.scss -->
    <div id="main-wrapper">

        <!-- Start Header -->
        <div class="header">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- Logo -->
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php">
                        <!-- FIX: Corrected image paths assuming images are in ../images/ -->
                        <b><img src="../images/inc.jpg" alt="Logo Icon" class="dark-logo" /></b>
                        
                    </a>
                </div>
                <!-- End Logo -->
                <div class="navbar-collapse">
                    <!-- toggle and nav items -->
                    <ul class="navbar-nav me-auto mt-md-0">
                        <!-- This is -->
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  " href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                    </ul>
                    <!-- User profile and search -->
                    <ul class="navbar-nav my-lg-0">
                        <!-- Profile -->
                        <li class="nav-item dropdown">
                             <!-- FIX: Corrected user icon path assuming images are in ../images/ -->
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
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar">
                <!-- Sidebar navigation-->
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
                                <li><a id="pendingRestaurantLink" href="pending_restaurant.php">Pending Restaurant</a></li> <!-- Current page -->
                                <li><a id="newRestaurantOwnerRequestLink" href="new_restaurant_owner_request.php">New Restaurant Owner Request</a></li>
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
                         <!-- Delivery Boy Menu with Submenu -->
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
                        <!-- End Delivery Boy Menu -->
                        <li class="nav-item">
                            <a href="coupons.php"><i class="fa fa-ticket"></i><span>Coupons</span></a>
                        </li>
                         <!-- Footer Settings Link (Still present in sidebar if needed) -->
                        <li><span id="footerSettingsLink" class="footer-settings-link"><i class="fa fa-cog"></i> Footer Settings</span></li>

                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </div>
        <!-- End Left Sidebar -->

        <!-- Page wrapper -->
        <div class="page-wrapper">
            <!-- Container fluid -->
            <div class="container-fluid">

                 <!-- Bread crumb -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">Pending Restaurant Requests</h3>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Restaurant</a></li>
                            <li class="breadcrumb-item active">Pending Requests</li>
                        </ol>
                    </div>
                </div>
                <!-- End Bread crumb -->

                <!-- Start Page Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline-primary shadow-sm">
                            <div class="card-header bg-primary">
                                <h4 class="m-b-0 text-white">Pending Restaurant Requests</h4> <!-- Added text-white back for contrast -->
                            </div>
                            <div class="card-body">
                                <div class="table-responsive m-t-20">
                                    <table id="requestsTable" class="display nowrap table table-hover table-striped table-bordered" style="width:100%;">
                                        <thead class="thead-dark"> <!-- Keep thead-dark for styling -->
                                            <tr>
                                                <th>Owner Email</th>
                                                <th>Category</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Url</th>
                                                <th>Open Hrs</th>
                                                <th>Close Hrs</th>
                                                <th>Open Days</th>
                                                <th>Address</th>
                                                <th>Diet Type</th>
                                                <th>Image</th>
                                                <th>FSSAI License</th>
                                                <th>Request Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Ensure DB connection is valid before query
                                            if (!isset($db) || $db === null || $db->connect_error) {
                                                error_log("Database connection failed before displaying table in pending_restaurant.php");
                                                echo '<tr><td colspan="15" class="text-center text-danger">Database connection error. Please check logs.</td></tr>';
                                            } else {
                                                // Fetching pending requests
                                                $sql = "SELECT rr.*, ro.email AS owner_email, rc.c_name AS category_name
                                                        FROM restaurant_requests rr
                                                        LEFT JOIN restaurant_owners ro ON rr.owner_id = ro.owner_id
                                                        LEFT JOIN res_category rc ON rr.c_id = rc.c_id
                                                        WHERE rr.status = 'pending'
                                                        ORDER BY rr.request_date DESC";
                                                $query = $db->query($sql);

                                                if (!$query) {
                                                    error_log("Pending requests query failed: " . $db->error);
                                                    echo '<tr><td colspan="15" class="text-center text-danger">Error fetching data. Please check logs.</td></tr>';
                                                } elseif ($query->num_rows == 0) {
                                                    echo '<tr><td colspan="15" class="text-center">No Pending Requests Found</td></tr>';
                                                } else {
                                                    // Loop through results
                                                    while ($rows = $query->fetch_assoc()) {
                                                        echo '<tr data-request-id="' . $rows['request_id'] . '">';
                                                        echo '<td>' . htmlspecialchars($rows['owner_email'] ?? 'N/A') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['category_name'] ?? 'N/A') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['title'] ?? '') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['email'] ?? '') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['phone'] ?? '') . '</td>';
                                                        echo '<td>' . (empty($rows['url']) ? 'N/A' : '<a href="' . htmlspecialchars($rows['url']) . '" target="_blank" rel="noopener noreferrer" title="' . htmlspecialchars($rows['url']) . '">' . htmlspecialchars(substr($rows['url'], 0, 25)) . (strlen($rows['url']) > 25 ? '...' : '') . '</a>') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['o_hr'] ?? '') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['c_hr'] ?? '') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['o_days'] ?? '') . '</td>';
                                                        echo '<td>' . htmlspecialchars($rows['address'] ?? '') . '</td>'; // Already set to wrap in CSS

                                                        // Diet Type Column
                                                        echo '<td>';
                                                        $diet_type = $rows['diet_type'] ?? 'all';
                                                        $diet_display = '';
                                                        if ($diet_type === 'all') {
                                                            $diet_display = '<span class="diet-type-display all"><span class="icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/></svg></span>All</span>';
                                                        } else {
                                                            $diet_types = explode(',', $diet_type);
                                                            foreach ($diet_types as $type) {
                                                                $type = trim($type);
                                                                if ($type === 'veg') {
                                                                    $diet_display .= '<span class="diet-type-display veg"><span class="icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95.49-7.44-2.44-7.93-6.39C2.58 9.59 5.51 6.1 9.46 5.61c3.95-.49 7.44 2.44 7.93 6.39.49 3.95-2.44 7.44-6.39 7.93zM12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg></span>Veg</span> ';
                                                                } elseif ($type === 'vegan') {
                                                                    $diet_display .= '<span class="diet-type-display vegan"><span class="icon"><svg viewBox="0 0 24 24"><path d="M7 3c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h10c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2H7zm0 2h10v6H7V5zm0 8h10v6H7v-6z"/></svg></span>Vegan</span> ';
                                                                } elseif ($type === 'nonveg') {
                                                                    $diet_display .= '<span class="diet-type-display nonveg"><span class="icon"><svg viewBox="0 0 24 24"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg></span>Non-Veg</span> ';
                                                                }
                                                            }
                                                        }
                                                        echo trim($diet_display); // Trim trailing space
                                                        echo '</td>';

                                                        // Image Column with Modal Preview
                                                        echo '<td>';
                                                        $image_file = !empty($rows['image']) ? basename(htmlspecialchars($rows['image'])) : '';
                                                        if (!empty($image_file)) {
                                                            $full_image_path = rtrim($imageDir, '/') . '/' . $image_file;
                                                            $web_image_path = rtrim($imageWebPath, '/') . '/' . $image_file;
                                                            if (file_exists($full_image_path) && is_file($full_image_path)) {
                                                                $timestamp = filemtime($full_image_path); // Cache busting
                                                                echo '<a href="#" class="image-preview" data-image="' . $web_image_path . '?' . $timestamp . '"><img src="' . $web_image_path . '?' . $timestamp . '" class="img-thumbnail" alt="Restaurant Image"/></a>';
                                                            } else {
                                                                if ($debug) error_log("Image file not found: " . $full_image_path);
                                                                echo '<span class="text-muted">Missing</span>';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">None</span>';
                                                        }
                                                        echo '</td>';

                                                        // FSSAI License Column
                                                        echo '<td>';
                                                        $fssai_file = !empty($rows['fssai_license']) ? basename(htmlspecialchars($rows['fssai_license'])) : '';
                                                        if (!empty($fssai_file)) {
                                                            $full_fssai_path = rtrim($docsDir, '/') . '/' . $fssai_file;
                                                            $web_fssai_path = rtrim($docsWebPath, '/') . '/' . $fssai_file;
                                                            if (file_exists($full_fssai_path) && is_file($full_fssai_path)) {
                                                                echo '<a href="' . $web_fssai_path . '" target="_blank" class="doc-link" rel="noopener noreferrer" title="View FSSAI PDF"><i class="fa fa-file-pdf"></i> View</a>';
                                                            } else {
                                                                if ($debug) error_log("FSSAI file not found: " . $full_fssai_path);
                                                                echo '<span class="text-warning">Missing</span>';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        echo '</td>';

                                                        // Request Date
                                                        echo '<td>' . (!empty($rows['request_date']) ? date('Y-m-d H:i', strtotime($rows['request_date'])) : 'N/A') . '</td>';

                                                        // Action Buttons
                                                        echo '<td>
                                                                <div class="btn-group" role="group" aria-label="Request Actions">
                                                                    <button class="btn btn-success btn-sm approve-btn" data-request-id="' . $rows['request_id'] . '" title="Approve Request"><i class="fa fa-check"></i> Approve</button>
                                                                    <button class="btn btn-danger btn-sm reject-btn" data-request-id="' . $rows['request_id'] . '" title="Reject Request"><i class="fa fa-times"></i> Reject</button>
                                                                </div>
                                                              </td>';
                                                        echo '</tr>';
                                                    } // end while
                                                } // end else
                                                // Free result set
                                                if (isset($query) && $query instanceof mysqli_result) {
                                                    $query->free();
                                                }
                                            } // end else (DB connection valid)
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End PAge Content -->

            </div>
            <!-- End Container fluid -->

            <!-- FOOTER HAS BEEN REMOVED -->

        </div>
        <!-- End Page wrapper -->

    </div>
    <!-- End Main Wrapper -->

    <!-- Modal for Image Preview (Bootstrap 5) -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" class="img-fluid" alt="Preview" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- All Jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme Specific JS -->
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

    <!-- Page Specific JS -->
    <script>
    // Console logs and library checks remain the same...

    $(document).ready(function() {
        // Initialize DataTable
        let table;
        try {
            if (!$.fn.DataTable.isDataTable('#requestsTable')) {
                console.log('Initializing DataTable for #requestsTable');
                table = $('#requestsTable').DataTable({
                    // Keep the existing DataTables options
                    dom: 'lBfrtip', // Layout definition: Length, Buttons, Filter, Table, Info, Pagination
                    buttons: [
                        { extend: 'copy', className: 'btn btn-secondary btn-sm' },
                        { extend: 'csv', className: 'btn btn-secondary btn-sm' },
                        { extend: 'excel', className: 'btn btn-secondary btn-sm' },
                        { extend: 'pdf', className: 'btn btn-secondary btn-sm' },
                        { extend: 'print', className: 'btn btn-secondary btn-sm' }
                    ],
                    scrollX: true, // Enable horizontal scrolling
                    pageLength: 10,
                    order: [[13, 'desc']], // Order by Request Date (index 13) descending
                    language: {
                        emptyTable: "No pending restaurant requests found",
                        zeroRecords: "No matching requests found",
                        info: "Showing _START_ to _END_ of _TOTAL_ requests",
                        infoEmpty: "Showing 0 to 0 of 0 requests",
                        infoFiltered: "(filtered from _MAX_ total requests)",
                        lengthMenu: "Show _MENU_ requests",
                        search: "Search requests:",
                        paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
                    },
                    // Fix button container placement with Bootstrap 5 structure
                    initComplete: function(settings, json) {
                        $('.dt-buttons').appendTo('#requestsTable_wrapper .col-md-6:eq(0)');
                        $('#requestsTable_filter').appendTo('#requestsTable_wrapper .col-md-6:eq(1)');
                        $('#requestsTable_length').appendTo('#requestsTable_wrapper .col-sm-12:eq(0)'); // Adjust length menu position if needed
                    }
                });
                 // Ensure DataTables controls align better in Bootstrap 5 grid
                $('#requestsTable_wrapper .row:first-child').addClass('align-items-center mb-3'); // Align vertically and add margin
                $('#requestsTable_wrapper .dt-buttons').addClass('mb-2 mb-md-0'); // Adjust button margin

            } else {
                console.log('DataTable #requestsTable already initialized');
                table = $('#requestsTable').DataTable();
            }
        } catch (e) {
            console.error("Error initializing DataTable: ", e);
            alert("Could not initialize the data table. Check console for errors.");
        }

        // Image Preview, Approve, Reject, and other JS logic remains the same...
         // Image Preview Modal Trigger
        $('#requestsTable tbody').on('click', '.image-preview', function(e) {
            e.preventDefault();
            console.log('Image preview clicked');
            const imageSrc = $(this).data('image');
            if(imageSrc) {
                $('#previewImage').attr('src', imageSrc);
                var imageModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                imageModal.show();
            } else {
                 console.warn("Image source not found for preview.");
            }
        });

        // Approve Button Action
        $('#requestsTable tbody').on('click', '.approve-btn', function(e) {
            e.preventDefault();
            const requestId = $(this).data('request-id');
            const row = $(this).closest('tr');
            console.log('Approve button clicked, request_id:', requestId);

            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not available.');
                alert('Error: Approval functionality is unavailable. Please reload.');
                return;
            }

            Swal.fire({
                title: 'Confirm Approval',
                text: 'Approve this restaurant request? It will be added with owner and bank details.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745', // Green
                cancelButtonColor: '#6c757d', // Grey
                confirmButtonText: '<i class="fa fa-check"></i> Yes, Approve!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Approval confirmed, sending AJAX for request_id:', requestId);
                    $.ajax({
                        url: 'handle_restaurant_requests.php', // Ensure this path is correct
                        type: 'POST',
                        data: { action: 'approve', request_id: requestId },
                        dataType: 'json',
                        beforeSend: function() { Swal.showLoading(); },
                        success: function(response) {
                            Swal.close();
                            console.log('AJAX response:', response);
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Approved!', text: response.message, timer: 2500, showConfirmButton: false });
                                table.row(row).remove().draw(false);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Approval Error', text: response.message || 'An unknown error occurred during approval.' });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            console.error('AJAX error:', status, error, xhr.responseText);
                            Swal.fire({ icon: 'error', title: 'AJAX Error', text: 'Failed to process approval request. Status: ' + status + ', Error: ' + (xhr.responseText || error) });
                        }
                    });
                } else {
                    console.log('Approval cancelled for request_id:', requestId);
                }
            });
        });

        // Reject Button Action
        $('#requestsTable tbody').on('click', '.reject-btn', function(e) {
            e.preventDefault();
            const requestId = $(this).data('request-id');
            const row = $(this).closest('tr');
            console.log('Reject button clicked, request_id:', requestId);

            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not available.');
                alert('Error: Rejection functionality is unavailable. Please reload.');
                return;
            }

            Swal.fire({
                title: 'Confirm Rejection',
                text: 'Are you sure you want to reject this request?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545', // Red
                cancelButtonColor: '#6c757d', // Grey
                confirmButtonText: '<i class="fa fa-times"></i> Yes, Reject!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Rejection confirmed, sending AJAX for request_id:', requestId);
                    $.ajax({
                        url: 'handle_restaurant_requests.php', // Ensure this path is correct
                        type: 'POST',
                        data: { action: 'reject', request_id: requestId },
                        dataType: 'json',
                         beforeSend: function() { Swal.showLoading(); },
                        success: function(response) {
                            Swal.close();
                            console.log('AJAX response:', response);
                            if (response.success) {
                                Swal.fire({ icon: 'info', title: 'Rejected', text: response.message, timer: 2500, showConfirmButton: false });
                                table.row(row).remove().draw(false);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Rejection Error', text: response.message || 'An unknown error occurred during rejection.' });
                            }
                        },
                        error: function(xhr, status, error) {
                             Swal.close();
                            console.error('AJAX error:', status, error, xhr.responseText);
                            Swal.fire({ icon: 'error', title: 'AJAX Error', text: 'Failed to process rejection request. Status: ' + status + ', Error: ' + (xhr.responseText || error) });
                        }
                    });
                } else {
                    console.log('Rejection cancelled for request_id:', requestId);
                }
            });
        });

        // Sidebar link active state
        $('#pendingRestaurantLink').closest('li').addClass('active');
        var parentUl = $('#pendingRestaurantLink').closest('ul.collapse');
        if (parentUl.length) {
            parentUl.addClass('in').attr('aria-expanded', 'true');
            parentUl.closest('li.nav-item').children('a.has-arrow').addClass('active');
        }

        // Footer Settings Link
        $('#footerSettingsLink').on('click', function(e) {
             e.preventDefault();
             alert('Footer Settings Clicked! Implement navigation.');
             console.log('Footer settings link clicked.');
        });

    }); // End document ready
    </script>

</body>
</html>
<?php
// Close database connection
if (isset($db) && $db instanceof mysqli) {
    $db->close();
    if ($debug) error_log("Database connection closed in pending_restaurant.php");
}
?>