<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Define paths for images and FSSAI license files
$basePath = $_SERVER['DOCUMENT_ROOT'] . "/OnlineFood-PHP/OnlineFood-PHP/";
$imageDir = $basePath . "admin/Res_img/";
$docsDir = $basePath . "admin/Owner_docs/";
$imageWebPath = "/OnlineFood-PHP/OnlineFood-PHP/admin/Res_img/";
$docsWebPath = "/OnlineFood-PHP/OnlineFood-PHP/admin/Owner_docs/";

if (!isset($_GET['res_upd']) || empty($_GET['res_upd'])) {
    die("Error: No restaurant ID provided.");
}

$rs_id = intval($_GET['res_upd']);
$stmt = $db->prepare("SELECT * FROM restaurant WHERE rs_id = ?");
$stmt->bind_param("i", $rs_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    $stmt->close();
    die("Error: Restaurant not found.");
}
$row = $result->fetch_assoc();
$stmt->close();

if (isset($_POST['submit'])) {
    $missing = [];
    if (empty($_POST['c_name'])) $missing[] = 'Category';
    if (empty($_POST['res_name'])) $missing[] = 'Restaurant Name';
    if (empty($_POST['email'])) $missing[] = 'Email';
    if (empty($_POST['phone'])) $missing[] = 'Phone';
    if (empty($_POST['o_hr'])) $missing[] = 'Open Hours';
    if (empty($_POST['c_hr'])) $missing[] = 'Close Hours';
    if (empty($_POST['o_days'])) $missing[] = 'Open Days';
    if (empty($_POST['address'])) $missing[] = 'Address';
    if (empty($_POST['latitude'])) $missing[] = 'Latitude';
    if (empty($_POST['longitude'])) $missing[] = 'Longitude';
    if (empty($_POST['city'])) $missing[] = 'City';

    if (!empty($missing)) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <strong>Missing fields: ' . implode(', ', $missing) . '</strong>
                  </div>';
    } else {
        // Handle image upload
        $image_name = $_POST['existing_image'];
        if (!empty($_FILES['file']['name'])) {
            $fname = $_FILES['file']['name'];
            $temp = $_FILES['file']['tmp_name'];
            $fsize = $_FILES['file']['size'];
            $extension = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'png', 'gif'])) {
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Invalid image extension!</strong> Only PNG, JPG, GIF are accepted.
                          </div>';
            } elseif ($fsize >= 1000000) {
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Max image size is 1MB!</strong> Try a different image.
                          </div>';
            } else {
                $image_name = 'image_' . uniqid() . '.' . $extension;
                $store = $imageDir . basename($image_name);
                if (!move_uploaded_file($temp, $store)) {
                    $error = '<div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                <strong>Failed to upload image!</strong> Please try again.
                              </div>';
                }
            }
        }

        // Handle FSSAI license upload
        $fssai_license = $_POST['existing_fssai_license'];
        if (!empty($_FILES['fssai_file']['name'])) {
            $fssai_fname = $_FILES['fssai_file']['name'];
            $fssai_temp = $_FILES['fssai_file']['tmp_name'];
            $fssai_fsize = $_FILES['fssai_file']['size'];
            $fssai_extension = strtolower(pathinfo($fssai_fname, PATHINFO_EXTENSION));
            if ($fssai_extension !== 'pdf') {
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Invalid FSSAI license extension!</strong> Only PDF is accepted.
                          </div>';
            } elseif ($fssai_fsize >= 2000000) {
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Max FSSAI license size is 2MB!</strong> Try a different file.
                          </div>';
            } else {
                $fssai_license = 'fssai_' . uniqid() . '.' . $fssai_extension;
                $fssai_store = $docsDir . basename($fssai_license);
                if (!move_uploaded_file($fssai_temp, $fssai_store)) {
                    $error = '<div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                <strong>Failed to upload FSSAI license!</strong> Please try again.
                              </div>';
                }
            }
        }

        // Proceed with update if no errors
        if (!isset($error)) {
            $stmt = $db->prepare("UPDATE restaurant SET c_id = ?, title = ?, email = ?, phone = ?, url = ?, o_hr = ?, c_hr = ?, o_days = ?, address = ?, image = ?, latitude = ?, longitude = ?, city = ?, fssai_license = ? WHERE rs_id = ?");
            if (!$stmt) {
                $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Prepare failed:</strong> ' . $db->error . '
                          </div>';
                error_log("Prepare failed: " . $db->error);
            } else {
                //                                                                                                        latitude | longitude | city | fssai    | rs_id
                //                                                                                                          d    |    d      |   s  |   s      |   i
                $stmt->bind_param("issssssssssdssi", $_POST['c_name'], $_POST['res_name'], $_POST['email'], $_POST['phone'], $_POST['url'], $_POST['o_hr'], $_POST['c_hr'], $_POST['o_days'], $_POST['address'], $image_name, $_POST['latitude'], $_POST['longitude'], $_POST['city'], $fssai_license, $rs_id);
                if ($stmt->execute()) {
                    $success = '<div class="alert alert-success alert-dismissible fade show">
                                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                  <strong>Record updated successfully!</strong>
                                </div>';
                } else {
                    $error = '<div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                                <strong>Error updating record:</strong> ' . $stmt->error . '
                              </div>';
                    error_log("SQL Error: " . $stmt->error);
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
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Update Restaurant</title>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
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
                        <li><a href="all_users.php"><span><i class="fa fa-user f-s-20 "></i></span><span>Users</span></a></li>
                        <li><a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-archive f-s-20 color-warning"></i><span class="hide-menu">Restaurant</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_restaurant.php">All Restaurants</a></li>
                                <li><a href="add_category.php">Add Category</a></li>
                                <li><a href="add_restaurant.php">Add Restaurant</a></li>
                                <li><a href="pending_restaurant.php">Pending Restaurants</a></li>
                            </ul>
                        </li>
                        <li><a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-cutlery" aria-hidden="true"></i><span class="hide-menu">Menu</span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li><a href="all_menu.php">All Menus</a></li>
                                <li><a href="add_menu.php">Add Menu</a></li>
                            </ul>
                        </li>
                        <li><a href="all_orders.php"><i class="fa fa-shopping-cart" aria-hidden="true"></i><span>Orders</span></a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="container-fluid">
                <?php echo $error ?? ''; echo $success ?? ''; ?>
                <div class="col-lg-12">
                    <div class="card card-outline-primary">
                        <div class="card-header">
                            <h4 class="m-b-0 text-white">Update Restaurant</h4>
                        </div>
                        <div class="card-body">
                            <form id="restaurantForm" action='' method='post' enctype="multipart/form-data">
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($row['image']); ?>">
                                <input type="hidden" name="existing_fssai_license" value="<?php echo htmlspecialchars($row['fssai_license'] ?? ''); ?>">
                                <div class="form-body">
                                    <hr>
                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group" id="res_name-group">
                                            <label class="control-label">Restaurant Name</label>
                                            <input type="text" name="res_name" value="<?php echo htmlspecialchars($row['title']); ?>" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="email-group">
                                            <label class="control-label">Business E-mail</label>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group" id="phone-group">
                                            <label class="control-label">Phone</label>
                                            <input type="text" name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">Website URL (Optional)</label>
                                            <input type="text" name="url" value="<?php echo htmlspecialchars($row['url']); ?>" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 form-group" id="o_hr-group">
                                            <label class="control-label">Open Hours</label>
                                            <select name="o_hr" class="form-control custom-select" required>
                                                <option value="">--Select your Hours--</option>
                                                <option value="6am" <?php if ($row['o_hr'] == '6am') echo 'selected'; ?>>6am</option>
                                                <option value="7am" <?php if ($row['o_hr'] == '7am') echo 'selected'; ?>>7am</option>
                                                <option value="8am" <?php if ($row['o_hr'] == '8am') echo 'selected'; ?>>8am</option>
                                                <option value="9am" <?php if ($row['o_hr'] == '9am') echo 'selected'; ?>>9am</option>
                                                <option value="10am" <?php if ($row['o_hr'] == '10am') echo 'selected'; ?>>10am</option>
                                                <option value="11am" <?php if ($row['o_hr'] == '11am') echo 'selected'; ?>>11am</option>
                                                <option value="12pm" <?php if ($row['o_hr'] == '12pm') echo 'selected'; ?>>12pm</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="c_hr-group">
                                            <label class="control-label">Close Hours</label>
                                            <select name="c_hr" class="form-control custom-select" required>
                                                <option value="">--Select your Hours--</option>
                                                <option value="3pm" <?php if ($row['c_hr'] == '3pm') echo 'selected'; ?>>3pm</option>
                                                <option value="4pm" <?php if ($row['c_hr'] == '4pm') echo 'selected'; ?>>4pm</option>
                                                <option value="5pm" <?php if ($row['c_hr'] == '5pm') echo 'selected'; ?>>5pm</option>
                                                <option value="6pm" <?php if ($row['c_hr'] == '6pm') echo 'selected'; ?>>6pm</option>
                                                <option value="7pm" <?php if ($row['c_hr'] == '7pm') echo 'selected'; ?>>7pm</option>
                                                <option value="8pm" <?php if ($row['c_hr'] == '8pm') echo 'selected'; ?>>8pm</option>
                                                <option value="9pm" <?php if ($row['c_hr'] == '9pm') echo 'selected'; ?>>9pm</option>
                                                <option value="10pm" <?php if ($row['c_hr'] == '10pm') echo 'selected'; ?>>10pm</option>
                                                <option value="11pm" <?php if ($row['c_hr'] == '11pm') echo 'selected'; ?>>11pm</option>
                                                <option value="12am" <?php if ($row['c_hr'] == '12am') echo 'selected'; ?>>12am</option>
                                                <option value="1am" <?php if ($row['c_hr'] == '1am') echo 'selected'; ?>>1am</option>
                                                <option value="2am" <?php if ($row['c_hr'] == '2am') echo 'selected'; ?>>2am</option>
                                                <option value="3am" <?php if ($row['c_hr'] == '3am') echo 'selected'; ?>>3am</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 form-group" id="o_days-group">
                                            <label class="control-label">Open Days</label>
                                            <select name="o_days" class="form-control custom-select" required>
                                                <option value="">--Select your Days--</option>
                                                <option value="Mon-Tue" <?php if ($row['o_days'] == 'Mon-Tue') echo 'selected'; ?>>Mon-Tue</option>
                                                <option value="Mon-Wed" <?php if ($row['o_days'] == 'Mon-Wed') echo 'selected'; ?>>Mon-Wed</option>
                                                <option value="Mon-Thu" <?php if ($row['o_days'] == 'Mon-Thu') echo 'selected'; ?>>Mon-Thu</option>
                                                <option value="Mon-Fri" <?php if ($row['o_days'] == 'Mon-Fri') echo 'selected'; ?>>Mon-Fri</option>
                                                <option value="Mon-Sat" <?php if ($row['o_days'] == 'Mon-Sat') echo 'selected'; ?>>Mon-Sat</option>
                                                <option value="24hr-x7" <?php if ($row['o_days'] == '24hr-x7') echo 'selected'; ?>>24hr-x7</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">Image</label>
                                            <input type="file" name="file" class="form-control" accept="image/*">
                                            <?php if ($row['image']) { ?>
                                                <p>Current: <img src="<?php echo $imageWebPath . htmlspecialchars($row['image']); ?>" style="max-width: 100px; max-height: 100px;" /></p>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">FSSAI License (PDF)</label>
                                            <input type="file" name="fssai_file" class="form-control" accept="application/pdf">
                                            <?php if ($row['fssai_license'] && file_exists($docsDir . $row['fssai_license'])) { ?>
                                                <p>Current: <a href="<?php echo $docsWebPath . htmlspecialchars($row['fssai_license']); ?>" target="_blank" class="doc-link" rel="noopener noreferrer">View FSSAI</a></p>
                                            <?php } else { ?>
                                                <p>Current: Not Provided</p>
                                            <?php } ?>
                                        </div>
                                        <div class="col-md-6 form-group" id="c_name-group">
                                            <label class="control-label">Select Category</label>
                                            <select name="c_name" class="form-control custom-select" required>
                                                <option value="">--Select Category--</option>
                                                <?php
                                                $stmt = $db->prepare("SELECT * FROM res_category");
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                while ($rows = $res->fetch_assoc()) {
                                                    $selected = ($rows['c_id'] == $row['c_id']) ? 'selected' : '';
                                                    echo '<option value="' . $rows['c_id'] . '" ' . $selected . '>' . htmlspecialchars($rows['c_name']) . '</option>';
                                                }
                                                $stmt->close();
                                                ?>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <h3 class="box-title m-t-40">Restaurant Address</h3>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12 form-group" id="address-group">
                                            <textarea name="address" style="height:100px;" class="form-control" required><?php echo htmlspecialchars($row['address']); ?></textarea>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 form-group" id="latitude-group">
                                            <label class="control-label">Latitude</label>
                                            <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($row['latitude']); ?>" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-4 form-group" id="longitude-group">
                                            <label class="control-label">Longitude</label>
                                            <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($row['longitude']); ?>" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-4 form-group" id="city-group">
                                            <label class="control-label">City</label>
                                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($row['city'] ?? ''); ?>" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <button type="button" id="getCoordinates" class="btn btn-info">Get Coordinates</button>
                                </div>
                                <div class="form-actions">
                                    <input type="submit" name="submit" id="submitBtn" class="btn btn-primary" value="Save">
                                    <a href="all_restaurant.php" class="btn btn-inverse">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <footer class="footer"> © 2022 - Online Food Ordering System</footer>
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
            $(document).ready(function() {
                // Function to validate a single field
                function validateField(fieldId, groupId) {
                    var input = $('#' + fieldId);
                    var value = input.val().trim();
                    var group = $('#' + groupId);
                    var errorDiv = group.find('.error-message');
                    var isValid = true;

                    if (value === '') {
                        group.addClass('has-error');
                        errorDiv.text('This field is required.');
                        isValid = false;
                    } else {
                        group.removeClass('has-error');
                        errorDiv.text('');
                    }
                    console.log(`Validating ${fieldId}:`, { value, isValid });
                    return isValid;
                }

                // Function to validate the entire form
                function validateForm() {
                    var isFormValid = true;
                    isFormValid = validateField('res_name', 'res_name-group') && isFormValid;
                    isFormValid = validateField('email', 'email-group') && isFormValid;
                    isFormValid = validateField('phone', 'phone-group') && isFormValid;
                    isFormValid = validateField('o_hr', 'o_hr-group') && isFormValid;
                    isFormValid = validateField('c_hr', 'c_hr-group') && isFormValid;
                    isFormValid = validateField('o_days', 'o_days-group') && isFormValid;
                    isFormValid = validateField('c_name', 'c_name-group') && isFormValid;
                    isFormValid = validateField('address', 'address-group') && isFormValid;
                    isFormValid = validateField('latitude', 'latitude-group') && isFormValid;
                    isFormValid = validateField('longitude', 'longitude-group') && isFormValid;
                    isFormValid = validateField('city', 'city-group') && isFormValid;
                    console.log('Form validation result:', isFormValid);
                    return isFormValid;
                }

                // Real-time validation on input
                $('input, select, textarea').on('input change', function() {
                    validateForm();
                    $('#submitBtn').prop('disabled', !validateForm());
                });

                // Geocoding with SweetAlert
                $('#getCoordinates').click(function() {
                    var address = $('textarea[name="address"]').val();
                    if (!address.trim()) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Empty Address',
                            text: 'Please enter an address before fetching coordinates.'
                        });
                        console.log('Error: Address is empty');
                        return;
                    }

                    console.log('Initiating geocoding for address:', address);

                    Swal.fire({
                        title: 'Geocoding Address...',
                        text: 'Please wait while we determine the coordinates and city.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        type: 'POST',
                        url: '../geocode.php',
                        data: { address: address },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            console.log('Geocoding response:', response);
                            if (response.status === 'success') {
                                $('#latitude').val(response.latitude);
                                $('#longitude').val(response.longitude);
                                $('#city').val(response.city || '');
                                console.log('Coordinates updated:', {
                                    latitude: response.latitude,
                                    longitude: response.longitude,
                                    city: response.city || 'Unknown'
                                });
                                validateForm();
                                $('#submitBtn').prop('disabled', !validateForm());
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Coordinates and City Retrieved',
                                    text: `Latitude: ${response.latitude}, Longitude: ${response.longitude}, City: ${response.city || 'Unknown'}`
                                });
                            } else {
                                console.error('Geocoding error:', response.message);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Geocoding Failed',
                                    text: response.message
                                });
                                $('#submitBtn').prop('disabled', true);
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            console.error('AJAX error:', status, error, xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'AJAX Error',
                                text: 'An error occurred while fetching coordinates: ' + error
                            });
                            $('#submitBtn').prop('disabled', true);
                        }
                    });
                });

                // Initial form validation
                validateForm();
                $('#submitBtn').prop('disabled', !validateForm());

                // Form submission
                $('#restaurantForm').submit(function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Form',
                            text: 'Please fill all required fields and fetch coordinates.'
                        });
                    }
                });
            });
        </script>
</body>
</html>