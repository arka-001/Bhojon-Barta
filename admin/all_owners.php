<?php
session_start();
include("../connection/connect.php"); // Go up one level to connection folder

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

// --- Feedback Message Handling ---
$session_feedback = $_SESSION['feedback_message'] ?? null;
$session_feedback_type = $_SESSION['feedback_type'] ?? 'danger';
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']); // Clear after reading

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Admin panel for viewing all restaurant owners">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png"> <!-- Path to favicon -->
    <title>All Restaurant Owners - Admin</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
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
        .table td, .table th { vertical-align: middle; font-size: 0.9rem; white-space: nowrap; }
        .action-btns .btn { margin: 0 2px; padding: 5px 10px; font-size: 0.8rem;}
        .action-btns .btn i { font-size: 0.9rem; }

        /* FIX: Ensure thead is visible and text is styled correctly */
        #ownersTable thead {
            visibility: visible !important;
            display: table-header-group !important; /* Make sure it's treated as a header */
        }
        #ownersTable thead th { /* Target TH directly */
            background-color: #212529 !important; /* Force dark background */
            color: #ffffff !important;            /* Force WHITE text color */
            border-color: #32383e !important;   /* Matching border */
            text-align: left !important;        /* Ensure text isn't indented off-screen */
        }
        /* Adjust DataTables controls layout */
         #ownersTable_wrapper .row:first-child > div {
            margin-bottom: 10px;
        }

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

        <!-- Header and Sidebar (Remain the same as previous version) -->
        <!-- Start Header -->
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
                                <li><a href="pending_restaurant.php">Pending Restaurant</a></li>
                                <li><a href="new_restaurant_owner_request.php">New Owner Requests</a></li>
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
                                <li><a href="new_delivery_boy_request.php">New Delivery Boy Request</a></li>
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
                        <h3 class="text-themecolor">All Restaurant Owners</h3>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                             <li class="breadcrumb-item"><a href="#">Restaurant</a></li>
                            <li class="breadcrumb-item active">All Owners</li>
                        </ol>
                    </div>
                </div>
                <!-- End Bread crumb -->

                <!-- Start Page Content -->
                <div class="row">
                    <div class="col-12">

                        <!-- Feedback Messages -->
                        <?php if ($session_feedback): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($session_feedback_type); ?> alert-dismissible fade show" role="alert">
                                <?php if($session_feedback_type == 'success'): ?><i class="fas fa-check-circle me-2"></i><?php else: ?><i class="fas fa-exclamation-triangle me-2"></i><?php endif; ?>
                                <?php echo htmlspecialchars($session_feedback); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card card-outline-primary shadow-sm">
                             <div class="card-header bg-primary">
                                <h4 class="m-b-0 text-white">Registered Restaurant Owners List</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive m-t-20">
                                    <table id="ownersTable" class="display nowrap table table-hover table-striped table-bordered" style="width:100%;">
                                        <thead > <!-- Removed table-dark class, styling handled by CSS rule -->
                                            <tr>
                                                <th>Owner ID</th>
                                                <th>Email</th>
                                                <th>Holder Name</th>
                                                <th>Bank Acc No</th>
                                                <th>IFSC</th>
                                                <th>Registered On</th>
                                                <!-- <th>Aadhaar</th> -->
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // PHP logic to fetch and display owners remains the same
                                                $sql_fetch_owners = "SELECT owner_id, email, account_holder_name, bank_account_number, ifsc_code, created_at
                                                                     FROM restaurant_owners
                                                                     ORDER BY owner_id DESC";
                                                $result_owners = mysqli_query($db, $sql_fetch_owners);

                                                if ($result_owners) {
                                                    if (mysqli_num_rows($result_owners) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result_owners)) {
                                                            $account_no = htmlspecialchars($row['bank_account_number'] ?? 'N/A');
                                                            $masked_account = $account_no !== 'N/A' && strlen($account_no) > 4
                                                                ? substr_replace($account_no, str_repeat('*', strlen($account_no) - 4), 2, -2)
                                                                : $account_no;

                                                            echo '<tr>';
                                                            echo '<td>' . htmlspecialchars($row['owner_id']) . '</td>';
                                                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                                            echo '<td>' . htmlspecialchars($row['account_holder_name'] ?? 'N/A') . '</td>';
                                                            echo '<td title="' . htmlspecialchars($row['bank_account_number'] ?? 'N/A') . '">' . $masked_account . '</td>';
                                                            echo '<td>' . htmlspecialchars($row['ifsc_code'] ?? 'N/A') . '</td>';
                                                            echo '<td>' . date("d M Y", strtotime($row['created_at'])) . '</td>';
                                                            // echo '<td>[Aadhaar Link Here]</td>';
                                                            echo '<td class="action-btns">';
                                                            echo '<a href="all_restaurant.php?owner_id=' . $row['owner_id'] . '" class="btn btn-info btn-flat" data-bs-toggle="tooltip" title="View Owner\'s Restaurants"><i class="fa fa-store"></i></a>';
                                                            echo '<a href="update_owner.php?owner_upd=' . $row['owner_id'] . '" class="btn btn-warning btn-flat" data-bs-toggle="tooltip" title="Edit Owner Details"><i class="fa fa-edit"></i></a>';
                                                            echo '<a href="javascript:void(0);" onclick="confirmDelete(\'' . $row['owner_id'] . '\', \'' . htmlspecialchars(addslashes($row['email']), ENT_QUOTES) . '\')" class="btn btn-danger btn-flat" data-bs-toggle="tooltip" title="Delete Owner (Use Caution!)"><i class="fa fa-trash-o"></i></a>';
                                                            echo '</td>';
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="7" class="text-center fst-italic text-muted">No Restaurant Owners Found.</td></tr>';
                                                    }
                                                    mysqli_free_result($result_owners);
                                                } else {
                                                    echo '<tr><td colspan="7" class="text-center text-danger">Error fetching owner data: ' . mysqli_error($db) . '</td></tr>';
                                                    error_log("Error fetching owners: " . mysqli_error($db));
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Page Content -->

            </div>
        </div>
    </div>

    <!-- JS Includes (Remain the same) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>

    <!-- Page Specific JS (Remains the same) -->
    <script>
    $(document).ready(function() {
        // DataTable Initialization remains the same...
        try {
            $('#ownersTable').DataTable({
                dom: 'lBfrtip',
                buttons: [
                    { extend: 'copy', className: 'btn btn-secondary btn-sm' },
                    { extend: 'csv', className: 'btn btn-secondary btn-sm' },
                    { extend: 'excel', className: 'btn btn-secondary btn-sm' },
                    { extend: 'pdf', className: 'btn btn-secondary btn-sm' },
                    { extend: 'print', className: 'btn btn-secondary btn-sm' }
                ],
                scrollX: true,
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    emptyTable: "No restaurant owners found",
                    search: "Search Owners:"
                },
                 initComplete: function(settings, json) {
                    $('.dt-buttons').appendTo('#ownersTable_wrapper .col-md-6:eq(0)');
                    $('#ownersTable_filter').appendTo('#ownersTable_wrapper .col-md-6:eq(1)');
                 }
            });
             $('#ownersTable_wrapper .row:first-child').addClass('align-items-center mb-3');
             $('#ownersTable_wrapper .dt-buttons').addClass('mb-2 mb-md-0');
        } catch (e) {
            console.error("Error initializing DataTable: ", e);
        }

        // Tooltips, sidebar activation, footer link JS remain the same...
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        $('#allOwnersLink').closest('li').addClass('active');
        var parentUl = $('#allOwnersLink').closest('ul.collapse');
        if(parentUl.length) {
             parentUl.addClass('in').attr('aria-expanded', 'true');
             parentUl.closest('li.nav-item').children('a.has-arrow').addClass('active');
        }

         $('#footerSettingsLink').on('click', function(e) {
             e.preventDefault();
             alert('Footer Settings Clicked! Implement navigation.');
        });

    });

    // Delete Confirmation Function (Remains the same)
    function confirmDelete(ownerId, ownerEmail) {
        Swal.fire({
            title: 'Are you sure?',
            html: `Delete owner <strong>${ownerEmail} (ID: ${ownerId})</strong>?<br>This action might orphan restaurants and CANNOT be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete owner!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_owner.php?owner_del=' + ownerId;
            }
        });
    }
    </script>
</body>
</html>
<?php
// Close connection
if (isset($db) && $db) {
    mysqli_close($db);
}
?>