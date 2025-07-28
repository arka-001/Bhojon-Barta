<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
error_reporting(0); // Keep error reporting off as in original code
session_start();
if (!isset($_SESSION["adm_id"])) {
    header("Location:index.php");
    exit();
}

// Define paths for FSSAI license files - KEEPING USER'S ORIGINAL PATHS
$basePath = $_SERVER['DOCUMENT_ROOT'] . "/OnlineFood-PHP/OnlineFood-PHP/"; // Server base path
$docsDir = $basePath . "admin/Owner_docs/"; // Directory for FSSAI files on server
$docsWebPath = "/OnlineFood-PHP/OnlineFood-PHP/admin/Owner_docs/"; // Web accessible path to FSSAI directory

// Base URL for constructing web links
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_subfolder = '/OnlineFood-PHP/OnlineFood-PHP';
$base_url = $protocol . $host . rtrim($project_subfolder, '/') . '/';

$db = $GLOBALS['db'];
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>All Restaurants</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <!-- Original CSS -->
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Updated Styles from Previous Code */
        .card-outline-primary {
            border-color: #007bff;
        }

        .card-header.bg-primary {
            background-color: #007bff !important;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 0.75rem !important;
            font-size: 14px;
        }
        .th.sorting{
            background: black;
}
        }

        .table th {
            background-color:rgb(0, 0, 0);
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .img-responsive {
            max-width: 150px;
            max-height: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 4px;
        }

        .doc-link {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            text-decoration: none;
            color: #007bff;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }

        .doc-link:hover {
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
        }

        .doc-link i {
            margin-right: 3px;
        }

        .doc-missing {
            font-style: italic;
            color: #dc3545;
            font-size: 0.8rem;
        }

        .doc-missing i {
            color: #ffc107;
            margin-right: 3px;
        }

        .bank-details {
            font-size: 0.8em;
            line-height: 1.3;
            white-space: normal !important;
            min-width: 180px;
        }

        .bank-details strong {
            display: inline-block;
            min-width: 55px;
            color: #444;
        }

        .owner-info {
            font-size: 0.8rem;
            color: #6c757d;
            display: block;
        }

        .action-btns .btn {
            margin: 0 2px;
            padding: 6px 12px;
            font-size: 14px;
        }

        #example23 td,
        #example23 th {
            white-space: nowrap;
        }

        #example23 td:nth-child(11),
        #example23 th:nth-child(11) {
            white-space: normal;
        }

        /* Address */
        /* Diet Type Styles */
        .diet-type-display .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 4px;
        }

        .diet-type-display .icon svg {
            width: 100%;
            height: 100%;
        }

        .diet-type-display.veg {
            color: #28a745;
        }

        .diet-type-display.veg .icon svg {
            fill: #28a745;
        }

        .diet-type-display.vegan {
            color: #17a2b8;
        }

        .diet-type-display.vegan .icon svg {
            fill: #17a2b8;
        }

        .diet-type-display.nonveg {
            color: #dc3545;
        }

        .diet-type-display.nonveg .icon svg {
            fill: #dc3545;
        }

        .diet-type-display.all {
            color: #6c757d;
        }

        .diet-type-display.all .icon svg {
            fill: #6c757d;
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
        <!-- Header -->
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
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="images/bookingSystem/user-icn.png" alt="user" class="profile-pic" />
                            </a>
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
                                <li><a id="pendingRestaurantLink" href="pending_restaurant.php">Pending Restaurant</a>
                                </li>
                                <li><a id="newRestaurantOwnerRequestLink" href="new_restaurant_owner_request.php">New
                                        Restaurant Owner Request</a></li>
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
                                <li><a id="newDeliveryBoyRequestLink" href="new_delivery_boy_request.php">New Delivery
                                        Boy Request</a></li>
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

        <!-- Page wrapper -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <!-- Bread crumb -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">All Restaurants</h3>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">All Restaurants</li>
                        </ol>
                    </div>
                </div>
                <!-- End Bread crumb -->
                <div class="row">
                    <div class="col-12">
                        <!-- Feedback Messages -->
                        <?php if (isset($_SESSION['delete_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <?php echo $_SESSION['delete_success'];
                                unset($_SESSION['delete_success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['delete_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <?php echo $_SESSION['delete_error'];
                                unset($_SESSION['delete_error']); ?>
                            </div>
                        <?php endif; ?>
                        <!-- End Feedback Messages -->
                        <div class="card card-outline-primary shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h4 class="m-b-0">All Restaurants Details</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive m-t-40">
                                    <table id="example23"
                                        class="display nowrap table table-hover table-striped table-bordered"
                                        cellspacing="0" width="100%">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Owner (ID/Email)</th>
                                                <th>Owner Bank Details</th>
                                                <th>Category</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Url</th>
                                                <th>Open Hrs</th>
                                                <th>Close Hrs</th>
                                                <th>Open Days</th>
                                                <th>Address</th>
                                                <th>City</th>
                                                <th>Diet Type</th>
                                                <th>Image</th>
                                                <th>FSSAI License</th>
                                                <th>Date Registered</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT r.*, rc.c_name, ro.email as owner_email, ro.account_holder_name, ro.bank_account_number, ro.ifsc_code
                                                    FROM restaurant r
                                                    LEFT JOIN res_category rc ON r.c_id = rc.c_id
                                                    LEFT JOIN restaurant_owners ro ON r.owner_id = ro.owner_id
                                                    ORDER BY r.rs_id DESC";
                                            $query = mysqli_query($db, $sql);

                                            if (!$query) {
                                                echo '<tr><td colspan="17" class="text-center text-danger">Error fetching data: ' . mysqli_error($db) . '</td></tr>';
                                            } elseif (!mysqli_num_rows($query) > 0) {
                                                echo '<tr><td colspan="17" class="text-center">No Approved Restaurants Found</td></tr>';
                                            } else {
                                                while ($rows = mysqli_fetch_array($query)) {
                                                    // FSSAI Path Logic
                                                    $fssai_filename = !empty($rows['fssai_license']) ? basename(htmlspecialchars($rows['fssai_license'])) : '';
                                                    $full_fssai_path_server = $fssai_filename ? rtrim($docsDir, '/') . '/' . $fssai_filename : '';
                                                    $web_fssai_path_link = $fssai_filename ? rtrim($docsWebPath, '/') . '/' . $fssai_filename : '';
                                                    $fssaiDisplay = '<span class="text-muted">N/A</span>';
                                                    if ($fssai_filename) {
                                                        if (file_exists($full_fssai_path_server) && is_file($full_fssai_path_server)) {
                                                            $fssaiDisplay = '<a href="' . htmlspecialchars($web_fssai_path_link) . '" target="_blank" class="doc-link" rel="noopener noreferrer" title="View FSSAI PDF"><i class="fa fa-file-pdf"></i> View</a>';
                                                        } else {
                                                            $fssaiDisplay = '<span class="doc-missing" title="File not found at expected location"><i class="fa fa-exclamation-triangle"></i> Missing</span>';
                                                        }
                                                    }

                                                    // Image Path Logic (Restored from Previous Code)
                                                    $imageUrl = !empty($rows['image']) ? 'Res_img/' . htmlspecialchars($rows['image']) : '';
                                                    $imageDisplay = '<span class="text-muted">No Image</span>';
                                                    if ($imageUrl) {
                                                        $imageDisplay = '<a href="' . $base_url . $imageUrl . '" target="_blank" title="View Full Image"><img src="' . $imageUrl . '" class="img-responsive radius" style="max-width:150px;max-height:100px;" alt="Restaurant Image"/></a>';
                                                    }

                                                    echo '<tr>';
                                                    // Owner Info
                                                    echo '<td>';
                                                    if (!empty($rows['owner_id'])) {
                                                        echo 'ID: ' . htmlspecialchars($rows['owner_id']) . '<span class="owner-info">' . htmlspecialchars($rows['owner_email'] ?? 'Email N/A') . '</span>';
                                                    } else {
                                                        echo '<span class="text-muted">N/A</span>';
                                                    }
                                                    echo '</td>';
                                                    // Bank Details
                                                    echo '<td class="bank-details">';
                                                    if (!empty($rows['owner_id']) && !empty($rows['account_holder_name'])) {
                                                        echo '<strong>Holder:</strong> ' . htmlspecialchars($rows['account_holder_name']) . '<br>';
                                                        echo '<strong>Acc No:</strong> ' . htmlspecialchars($rows['bank_account_number']) . '<br>';
                                                        echo '<strong>IFSC:</strong> ' . htmlspecialchars($rows['ifsc_code']);
                                                    } else {
                                                        echo '<span class="text-muted">N/A</span>';
                                                    }
                                                    echo '</td>';
                                                    // Other Columns
                                                    echo '<td>' . htmlspecialchars($rows['c_name'] ?? 'N/A') . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['title']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['email']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['phone']) . '</td>';
                                                    echo '<td>' . (empty($rows['url']) ? '<span class="text-muted">N/A</span>' : '<a href="' . htmlspecialchars($rows['url']) . '" target="_blank" rel="noopener noreferrer" title="' . htmlspecialchars($rows['url']) . '"><i class="fa fa-link"></i> Link</a>') . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['o_hr']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['c_hr']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['o_days']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['address']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($rows['city'] ?? 'N/A') . '</td>';
                                                    // Diet Type
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
                                                    echo $diet_display;
                                                    echo '</td>';
                                                    // Image
                                                    echo '<td><div class="col-md-3 col-lg-8 m-b-10"><center>' . $imageDisplay . '</center></div></td>';
                                                    // FSSAI License
                                                    echo '<td>' . $fssaiDisplay . '</td>';
                                                    // Date Registered
                                                    echo '<td>' . date("d M Y", strtotime($rows['date'])) . '</td>';
                                                    // Actions
                                                    echo '<td class="action-btns">';
                                                    echo '<a href="javascript:void(0);" onclick="confirmDelete(\'' . $rows['rs_id'] . '\')" class="btn btn-danger btn-flat btn-addon btn-xs m-b-10" title="Delete"><i class="fa fa-trash-o" style="font-size:16px"></i></a>';
                                                    echo '<a href="update_restaurant.php?res_upd=' . $rows['rs_id'] . '" class="btn btn-info btn-flat btn-addon btn-sm m-b-10 m-l-5" title="Edit"><i class="fa fa-edit"></i></a>';
                                                    echo '<a href="all_menu.php?res_id=' . $rows['rs_id'] . '" class="btn btn-warning btn-flat btn-addon btn-sm m-b-10 m-l-5" title="View Menu"><i class="fa fa-cutlery"></i></a>';
                                                    echo '</td>';
                                                    echo '</tr>';
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
            <?php include 'include/footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Includes -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <!-- DataTables -->
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

    <script>
        console.log('all_restaurant.php: Script loaded');

        // Test SweetAlert2 and jQuery
        if (typeof Swal !== 'undefined') {
            console.log('SweetAlert2 is loaded');
            // Uncomment to test popup on page load
            // Swal.fire('Test', 'SweetAlert2 is working!', 'success');
        } else {
            console.error('SweetAlert2 is not loaded.');
            alert('Error: SweetAlert2 is not loaded. Please check the script inclusions.');
        }
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is loaded');
        } else {
            console.error('jQuery is not loaded.');
            alert('Error: jQuery is not loaded. Please check the script inclusions.');
        }

        $(document).ready(function () {
            // Initialize DataTable
            let table;
            if (!$.fn.DataTable.isDataTable('#example23')) {
                console.log('Initializing DataTable');
                table = $('#example23').DataTable({
                    dom: 'lBfrtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    scrollX: true,
                    pageLength: 10,
                    order: [[15, 'desc']], // Order by Date Registered (index 15 with diet_type)
                    language: {
                        emptyTable: "No approved restaurants found",
                        zeroRecords: "No matching restaurants found",
                        info: "Showing _START_ to _END_ of _TOTAL_ restaurants",
                        infoEmpty: "Showing 0 to 0 of 0 restaurants",
                        infoFiltered: "(filtered from _MAX_ total restaurants)",
                        lengthMenu: "Show _MENU_ restaurants",
                        search: "Search restaurants:",
                        paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
                    }
                });
            } else {
                console.log('DataTable already initialized');
                table = $('#example23').DataTable();
            }

            // Delete Confirmation
            window.confirmDelete = function (restaurantId) {
                if (typeof Swal === 'undefined') {
                    console.error('SweetAlert2 is not available.');
                    alert('SweetAlert2 is not available. Deletion cannot proceed.');
                    return;
                }
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Delete restaurant ID " + restaurantId + "? This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Deletion confirmed for restaurant ID:', restaurantId);
                        window.location.href = 'delete_restaurant.php?res_del=' + restaurantId;
                    } else {
                        console.log('Deletion cancelled for restaurant ID:', restaurantId);
                    }
                }).catch(error => {
                    console.error('SweetAlert2 error:', error);
                    alert('SweetAlert2 error: ' + error.message);
                });
            };
        });
    </script>
</body>

</html>
<?php if (isset($db) && $db) {
    mysqli_close($db);
} ?>