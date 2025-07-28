<?php
include("../connection/connect.php");
session_start();

// Redirect if not logged in
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Constants
define('TARGET_DIR', 'delivery_boy_images/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Function to sanitize input data
function sanitize_input($db, $data) {
    return mysqli_real_escape_string($db, trim($data));
}

// Function to validate form data
function validate_form_data($data) {
    $errors = [];

    if (empty($data['db_name'])) {
        $errors[] = 'Name is required.';
    }

    if (empty($data['db_phone'])) {
        $errors[] = 'Phone Number is required.';
    }

    if (empty($data['db_password'])) {
        $errors[] = 'Password is required.';
    }

    if (empty($data['db_address'])) {
        $errors[] = 'Address is required.';
    }

    return $errors;
}

// Function to handle image upload
function upload_image($file) {
    global $error;
    $target_dir = TARGET_DIR;

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($file["error"] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $file["name"];
        $filetype = $file["type"];
        $filesize = $file["size"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!array_key_exists($ext, $allowed)) {
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Please select a valid file format.'});</script>";
            return false;
        }

        if ($filesize > MAX_FILE_SIZE) {
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'File size is larger than the

 allowed limit.'});</script>";
            return false;
        }

        if (!in_array($filetype, $allowed)) {
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Invalid file format.'});</script>";
            return false;
        }

        if (!getimagesize($file["tmp_name"])) {
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'The file is not a valid image.'});</script>";
            return false;
        }

        $target_file = $target_dir . uniqid() . "_" . basename($filename);
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        } else {
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Sorry, there was an error uploading your file.'});</script>";
            return false;
        }
    }

    return "";
}

// Function to insert delivery boy into the database
function insert_delivery_boy($db, $data) {
    global $error, $success;

    $sql = "INSERT INTO delivery_boy (db_name, db_phone, db_email, db_address, city, db_photo, db_password, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($db, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssssd",
            $data['db_name'],
            $data['db_phone'],
            $data['db_email'],
            $data['db_address'],
            $data['city'],
            $data['db_photo'],
            $data['hashed_password'],
            $data['db_latitude'],
            $data['db_longitude']
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "<script>Swal.fire({icon: 'success', title: 'Success', text: 'Delivery boy added successfully!'});</script>";
        } else {
            $db_error = mysqli_error($db);
            $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Error adding delivery boy: " . $db_error . "'});</script>";
            error_log("Database Error: " . $db_error);
        }

        mysqli_stmt_close($stmt);
    } else {
        $db_error = mysqli_error($db);
        $error = "<script>Swal.fire({icon: 'error', title: 'Error', text: 'Error preparing statement: " . $db_error . "'});</script>";
        error_log("Statement Prepare Error: " . $db_error);
    }
}

$image_path = "";

if (isset($_POST['submit'])) {
    // Sanitize all inputs
    $db_name = sanitize_input($db, $_POST['db_name']);
    $db_phone = sanitize_input($db, $_POST['db_phone']);
    $db_email = sanitize_input($db, $_POST['db_email']);
    $db_address = sanitize_input($db, $_POST['db_address']);
    $db_city = sanitize_input($db, $_POST['db_city']);
    $db_latitude = sanitize_input($db, $_POST['db_latitude']);
    $db_longitude = sanitize_input($db, $_POST['db_longitude']);
    $db_password = $_POST['db_password'];

    // Validate the form data
    $form_data = [
        'db_name' => $db_name,
        'db_phone' => $db_phone,
        'db_address' => $db_address,
        'db_password' => $db_password,
    ];

    $errors = validate_form_data($form_data);

    if (!empty($errors)) {
        $error_messages = implode("<br>", $errors);
        $error = "<script>Swal.fire({icon: 'error', title: 'Error', html: '" . $error_messages . "'});</script>";
    } else {
        // Hash the password
        $hashed_password = password_hash($db_password, PASSWORD_DEFAULT);

        // Handle image upload
        $image_path = upload_image($_FILES["db_photo"]);

        if ($image_path === false && !empty($error)) {
            return;
        }

        // Prepare data for database insertion
        $db_data = [
            'db_name' => $db_name,
            'db_phone' => $db_phone,
            'db_email' => $db_email,
            'db_address' => $db_address,
            'city' => $db_city,
            'db_photo' => $image_path,
            'hashed_password' => $hashed_password,
            'db_latitude' => $db_latitude,
            'db_longitude' => $db_longitude,
        ];

        // Insert the delivery boy into the database
        insert_delivery_boy($db, $db_data);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Add Delivery Boy</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
        .circle-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 0.2em;
        }
        .form-group.has-error input,
        .form-group.has-error textarea,
        .form-group.has-error select {
            border-color: red;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <div class="col-12">
                        <div class="col-lg-12">
                            <div class="card card-outline-primary">
                                <div class="card-header">
                                    <h4 class="m-b-0 text-white">Add New Delivery Boy</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($error)) echo $error; ?>
                                    <?php if (isset($success)) echo $success; ?>
                                    <form id="addDeliveryBoyForm" action="add_delivery_boy.php" method="post" enctype="multipart/form-data">
                                        <div class="form-group" id="name-group">
                                            <label for="db_name">Name:</label>
                                            <input type="text" class="form-control" id="db_name" name="db_name" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="form-group" id="phone-group">
                                            <label for="db_phone">Phone Number:</label>
                                            <input type="text" class="form-control" id="db_phone" name="db_phone" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="form-group">
                                            <label for="db_email">Email Address:</label>
                                            <input type="email" class="form-control" id="db_email" name="db_email">
                                        </div>
                                        <div class="form-group" id="address-group">
                                            <label for="db_address">Address:</label>
                                            <input type="text" class="form-control" id="db_address" name="db_address" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="form-group">
                                            <label for="display_city">City:</label>
                                            <input type="text" class="form-control" id="display_city" disabled>
                                            <input type="hidden" id="db_city" name="db_city">
                                        </div>
                                        <div class="form-group" id="password-group">
                                            <label for="db_password">Password:</label>
                                            <input type="password" class="form-control" id="db_password" name="db_password" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="form-group">
                                            <label for="db_photo">Photo:</label>
                                            <input type="file" class="form-control-file" id="db_photo" name="db_photo" accept="image/*" onchange="previewImage(this)">
                                        </div>
                                        <div class="form-group">
                                            <img id="imagePreview" src="#" alt="Image preview" class="circle-img" style="display:none;">
                                        </div>
                                        <input type="hidden" id="db_latitude" name="db_latitude">
                                        <input type="hidden" id="db_longitude" name="db_longitude">
                                        <div class="form-group">
                                            <label for="display_latitude">Latitude:</label>
                                            <input type="text" class="form-control" id="display_latitude" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label for="display_longitude">Longitude:</label>
                                            <input type="text" class="form-control" id="display_longitude" disabled>
                                        </div>
                                        <button type="button" id="getCoordinates" class="btn btn-info">Get Coordinates</button>
                                        <button type="submit" name="submit" class="btn btn-primary" disabled>Add Delivery Boy</button>
                                    </form>
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
        function previewImage(input) {
            var imagePreview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                imagePreview.src = '#';
                imagePreview.style.display = 'none';
            }
        }

        $(document).ready(function() {
            var form = $('#addDeliveryBoyForm');
            $('button[name="submit"]').prop('disabled', true);

            function validateField(fieldId, groupID) {
                var input = $('#' + fieldId);
                var value = input.val().trim();
                var group = $('#' + groupID);
                var errorDiv = group.find('.error-message');
                var is_valid = true;

                if (value === '') {
                    group.addClass('has-error');
                    errorDiv.text(fieldId.replace('db_', '').toUpperCase() + ' is required.');
                    is_valid = false;
                } else {
                    group.removeClass('has-error');
                    errorDiv.text('');
                }
                return is_valid;
            }

            function validateForm() {
                var isFormValid = true;
                isFormValid = validateField('db_name', 'name-group') && isFormValid;
                isFormValid = validateField('db_phone', 'phone-group') && isFormValid;
                isFormValid = validateField('db_password', 'password-group') && isFormValid;
                isFormValid = validateField('db_address', 'address-group') && isFormValid;
                return isFormValid;
            }

            $('#db_name').on('input', function() {
                validateField('db_name', 'name-group');
            });
            $('#db_phone').on('input', function() {
                validateField('db_phone', 'phone-group');
            });
            $('#db_password').on('input', function() {
                validateField('db_password', 'password-group');
            });
            $('#db_address').on('input', function() {
                validateField('db_address', 'address-group');
            });

            $('#getCoordinates').click(function() {
                var address = $('#db_address').val();

                Swal.fire({
                    title: 'Geocoding Address...',
                    html: 'Please wait while we determine the coordinates and city.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: 'geocode.php',
                    data: { address: address },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();

                        if (response.status === 'success') {
                            $('#db_latitude').val(response.latitude);
                            $('#db_longitude').val(response.longitude);
                            $('#display_latitude').val(response.latitude);
                            $('#display_longitude').val(response.longitude);
                            $('#display_city').val(response.city);
                            $('#db_city').val(response.city);

                            if (validateForm()) {
                                $('button[name="submit"]').prop('disabled', false);
                            } else {
                                $('button[name="submit"]').prop('disabled', true);
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Coordinates and City Retrieved',
                                text: 'Coordinates and city have been successfully retrieved. You can now submit the form.',
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Geocoding Error',
                                text: response.message,
                            });
                            $('button[name="submit"]').prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('AJAX Error:', error);
                        console.error('Status Code:', xhr.status);
                        console.error('Response Text:', xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'AJAX Error',
                            text: 'An error occurred while trying to geocode the address. Check the console for details.',
                        });
                        $('button[name="submit"]').prop('disabled', true);
                    }
                });
            });

            form.submit(function(e) {
                if ($('button[name="submit"]').prop('disabled')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill out all required fields and get the coordinates and city before submitting the form.',
                    });
                }
            });

            $('input').keyup(function() {
                if (validateForm() && $('#db_latitude').val() !== '' && $('#db_longitude').val() !== '' && $('#db_city').val() !== '') {
                    $('button[name="submit"]').prop('disabled', false);
                } else {
                    $('button[name="submit"]').prop('disabled', true);
                }
            });

            validateForm();
        });
    </script>
</body>
</html>