<?php
session_start();
include("../connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if (isset($_POST['submit'])) {
    // Required fields
    $required_fields = ['c_name', 'res_name', 'email', 'password', 'phone', 'o_hr', 'c_hr', 'o_days', 'address', 'latitude', 'longitude', 'city', 'diet_type'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = str_replace('_', ' ', ucwords($field));
        }
    }
    if (empty($_FILES['file']['name'])) {
        $missing_fields[] = 'Restaurant Image';
    }
    if (empty($_FILES['fssai_license']['name'])) {
        $missing_fields[] = 'FSSAI License';
    }

    if (!empty($missing_fields)) {
        $error = '<div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong>Missing fields: ' . implode(', ', $missing_fields) . '</strong> (Website URL is optional)
        </div>';
    } else {
        // Validate diet_type
        $valid_diet_types = ['veg', 'nonveg', 'vegan', 'all'];
        if (!in_array($_POST['diet_type'], $valid_diet_types)) {
            $error = '<div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <strong>Invalid diet type selected!</strong>
            </div>';
        }

        if (empty($error)) {
            // Directory setup
            $uploadDirImages = "Res_img/";
            $uploadDirDocs = "Owner_docs/";
            $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif'];
            $allowedFssaiExt = ['pdf'];

            // Create directories if they don't exist
            foreach ([$uploadDirImages, $uploadDirDocs] as $dir) {
                $fullDir = realpath(dirname(__FILE__) . '/' . $dir) ?: dirname(__FILE__) . '/' . $dir;
                if (!file_exists($fullDir)) {
                    if (!mkdir($fullDir, 0777, true)) {
                        $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Failed to create directory: ' . basename($dir) . '</strong>
                        </div>';
                        error_log("Failed to create directory: $fullDir");
                        break;
                    }
                    error_log("Created directory: $fullDir");
                }
                if (!is_writable($fullDir)) {
                    if (!chmod($fullDir, 0777)) {
                        $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Directory (' . basename($dir) . ') is not writable!</strong>
                        </div>';
                        error_log("Directory not writable: $fullDir");
                        break;
                    }
                }
            }

            if (empty($error)) {
                // Restaurant image processing
                $imageFile = $_FILES['file'];
                $imageName = $imageFile['name'];
                $imageTemp = $imageFile['tmp_name'];
                $imageSize = $imageFile['size'];
                $imageError = $imageFile['error'];
                $imageExt = strtolower(trim(pathinfo($imageName, PATHINFO_EXTENSION)));
                $imageNewName = 'image_' . uniqid() . '.' . $imageExt;
                $imageStore = rtrim($uploadDirImages, '/') . '/' . basename($imageNewName);

                // FSSAI license processing
                $fssaiFile = $_FILES['fssai_license'];
                $fssaiName = $fssaiFile['name'];
                $fssaiTemp = $fssaiFile['tmp_name'];
                $fssaiSize = $fssaiFile['size'];
                $fssaiError = $fssaiFile['error'];
                $fssaiExt = strtolower(trim(pathinfo($fssaiName, PATHINFO_EXTENSION)));
                $fssaiNewName = 'fssai_' . uniqid() . '.' . $fssaiExt;
                $fssaiStore = rtrim($uploadDirDocs, '/') . '/' . basename($fssaiNewName);

                // Log file details
                error_log("Image: Name=$imageName, Temp=$imageTemp, Size=$imageSize, Error=$imageError, Ext=$imageExt, NewName=$imageNewName");
                error_log("FSSAI: Name=$fssaiName, Temp=$fssaiTemp, Size=$fssaiSize, Error=$fssaiError, Ext=$fssaiExt, NewName=$fssaiNewName");

                // File validation
                $errors = [];
                if ($imageError !== UPLOAD_ERR_OK) {
                    $errors[] = 'Image upload error: ' . ($imageError === UPLOAD_ERR_INI_SIZE ? 'File too large' : 'Unknown error');
                } elseif (!in_array($imageExt, $allowedImageExt)) {
                    $errors[] = 'Invalid image extension! Only JPG, JPEG, PNG, GIF allowed. Detected: ' . $imageExt;
                } elseif ($imageSize > 1000000) {
                    $errors[] = 'Image too large! Max 1MB.';
                } elseif (!is_uploaded_file($imageTemp)) {
                    $errors[] = 'Invalid image upload!';
                } elseif (getimagesize($imageTemp) === false) {
                    $errors[] = 'Invalid image file!';
                }

                if ($fssaiError !== UPLOAD_ERR_OK) {
                    $errors[] = 'FSSAI upload error: ' . ($fssaiError === UPLOAD_ERR_INI_SIZE ? 'File too large' : 'Unknown error');
                } elseif (!in_array($fssaiExt, $allowedFssaiExt)) {
                    $errors[] = 'Invalid FSSAI extension! Only PDF allowed. Detected: ' . $fssaiExt;
                } elseif ($fssaiSize > 5000000) {
                    $errors[] = 'FSSAI file too large! Max 5MB.';
                } elseif (!is_uploaded_file($fssaiTemp)) {
                    $errors[] = 'Invalid FSSAI upload!';
                }

                if (!empty($errors)) {
                    $error = '<div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Validation Errors:</strong><br>' . implode('<br>', $errors) . '
                    </div>';
                } else {
                    // Move uploaded files
                    $uploadedFiles = [];
                    if (!move_uploaded_file($imageTemp, $imageStore)) {
                        $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Failed to upload image!</strong> Check permissions.
                        </div>';
                    } else {
                        $uploadedFiles[] = $imageStore;
                        error_log("Image uploaded: $imageStore");
                    }

                    if (empty($error) && !move_uploaded_file($fssaiTemp, $fssaiStore)) {
                        foreach ($uploadedFiles as $file) @unlink($file);
                        $error = '<div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Failed to upload FSSAI license!</strong> Check permissions.
                        </div>';
                    } else {
                        $uploadedFiles[] = $fssaiStore;
                        error_log("FSSAI uploaded: $fssaiStore");
                    }

                    if (empty($error)) {
                        // Prepare data
                        $res_name = mysqli_real_escape_string($db, $_POST['res_name']);
                        $email = mysqli_real_escape_string($db, $_POST['email']);
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $phone = mysqli_real_escape_string($db, $_POST['phone']);
                        $url = isset($_POST['url']) ? mysqli_real_escape_string($db, $_POST['url']) : '';
                        $o_hr = mysqli_real_escape_string($db, $_POST['o_hr']);
                        $c_hr = mysqli_real_escape_string($db, $_POST['c_hr']);
                        $o_days = mysqli_real_escape_string($db, $_POST['o_days']);
                        $address = mysqli_real_escape_string($db, $_POST['address']);
                        $c_id = (int)$_POST['c_name'];
                        $latitude = (float)$_POST['latitude'];
                        $longitude = (float)$_POST['longitude'];
                        $city = mysqli_real_escape_string($db, $_POST['city']);
                        $diet_type = mysqli_real_escape_string($db, $_POST['diet_type']);

                        // Insert into restaurant_owners
                        $owner_sql = "INSERT INTO restaurant_owners (email, password) VALUES (?, ?)";
                        $owner_stmt = $db->prepare($owner_sql);
                        $owner_stmt->bind_param("ss", $email, $password);
                        if (!$owner_stmt->execute()) {
                            foreach ($uploadedFiles as $file) @unlink($file);
                            $error = '<div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Failed to add owner: ' . htmlspecialchars($owner_stmt->error) . '</strong>
                            </div>';
                            error_log("Owner insert failed: " . $owner_stmt->error);
                        } else {
                            $owner_id = $db->insert_id;

                            // Insert into restaurant
                            $sql = "INSERT INTO restaurant (c_id, title, email, phone, url, o_hr, c_hr, o_days, address, image, fssai_license, owner_id, latitude, longitude, city, diet_type) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $db->prepare($sql);
                            $stmt->bind_param("issssssssisisdss", $c_id, $res_name, $email, $phone, $url, $o_hr, $c_hr, $o_days, $address, $imageNewName, $fssaiNewName, $owner_id, $latitude, $longitude, $city, $diet_type);

                            if ($stmt->execute()) {
                                $success = '<div class="alert alert-success alert-dismissible fade show">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Restaurant added successfully!</strong>
                                </div>';
                                $_SESSION['success_message'] = "New Restaurant Added Successfully. What would you like to do next?";
                                echo "<script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        openConfirmationModal();
                                    });
                                </script>";
                            } else {
                                foreach ($uploadedFiles as $file) @unlink($file);
                                $error = '<div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Failed to add restaurant: ' . htmlspecialchars($stmt->error) . '</strong>
                                </div>';
                                error_log("Restaurant insert failed: " . $stmt->error);
                            }
                            $stmt->close();
                        }
                        $owner_stmt->close();
                    }
                }
            }
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
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <title>Add Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
        }
        .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 0.2em;
        }
        .form-group.has-error .form-control,
        .form-group.has-error .custom-select {
            border-color: red;
        }
        .preloader {
            display: none;
        }
        .card-header {
            background-color: #007bff;
            color: white;
        }
        .sidebar-nav .nav-label {
            font-weight: bold;
            color: #333;
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
                    <ul class="navbar-nav me-auto mt-md-0"></ul>
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                <?php echo $error; ?>
                <?php echo $success; ?>
                <div class="col-lg-12">
                    <div class="card card-outline-primary">
                        <div class="card-header">
                            <h4 class="m-b-0 text-white">Add Restaurant</h4>
                        </div>
                        <div class="card-body">
                            <form id="restaurantForm" action="" method="post" enctype="multipart/form-data">
                                <div class="form-body">
                                    <hr>
                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group" id="res_name-group">
                                            <label class="control-label">Restaurant Name</label>
                                            <input type="text" name="res_name" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="email-group">
                                            <label class="control-label">Business E-mail</label>
                                            <input type="email" name="email" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group" id="password-group">
                                            <label class="control-label">Password</label>
                                            <input type="password" name="password" class="form-control" required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="phone-group">
                                            <label class="control-label">Phone</label>
                                            <input type="text" name="phone" class="form-control" pattern="[0-9]{10,15}" title="Enter a valid phone number" required>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">Website URL (Optional)</label>
                                            <input type="url" name="url" class="form-control">
                                        </div>
                                        <div class="col-md-6 form-group" id="diet_type-group">
                                            <label class="control-label">Diet Type</label>
                                            <select name="diet_type" class="form-control custom-select" required>
                                                <option value="">--Select Diet Type--</option>
                                                <option value="veg">Veg</option>
                                                <option value="nonveg">Non-Veg</option>
                                                <option value="vegan">Vegan</option>
                                                <option value="all">All</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <div class="row p-t-20">
                                        <div class="col-md-6 form-group" id="o_hr-group">
                                            <label class="control-label">Open Hours</label>
                                            <select name="o_hr" class="form-control custom-select" required>
                                                <option value="">--Select your Hours--</option>
                                                <option value="6am">6am</option>
                                                <option value="7am">7am</option>
                                                <option value="8am">8am</option>
                                                <option value="9am">9am</option>
                                                <option value="10am">10am</option>
                                                <option value="11am">11am</option>
                                                <option value="12pm">12pm</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="c_hr-group">
                                            <label class="control-label">Close Hours</label>
                                            <select name="c_hr" class="form-control custom-select" required>
                                                <option value="">--Select your Hours--</option>
                                                <option value="3pm">3pm</option>
                                                <option value="4pm">4pm</option>
                                                <option value="5pm">5pm</option>
                                                <option value="6pm">6pm</option>
                                                <option value="7pm">7pm</option>
                                                <option value="8pm">8pm</option>
                                                <option value="9pm">9pm</option>
                                                <option value="10pm">10pm</option>
                                                <option value="11pm">11pm</option>
                                                <option value="12am">12am</option>
                                                <option value="1am">1am</option>
                                                <option value="2am">2am</option>
                                                <option value="3am">3am</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 form-group" id="o_days-group">
                                            <label class="control-label">Open Days</label>
                                            <select name="o_days" class="form-control custom-select" required>
                                                <option value="">--Select your Days--</option>
                                                <option value="Mon-Tue">Mon-Tue</option>
                                                <option value="Mon-Wed">Mon-Wed</option>
                                                <option value="Mon-Thu">Mon-Thu</option>
                                                <option value="Mon-Fri">Mon-Fri</option>
                                                <option value="Mon-Sat">Mon-Sat</option>
                                                <option value="24hr-x7">24hr-x7</option>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="c_name-group">
                                            <label class="control-label">Select Category</label>
                                            <select name="c_name" class="form-control custom-select" required>
                                                <option value="">--Select Category--</option>
                                                <?php
                                                $ssql = "SELECT * FROM res_category ORDER BY c_name ASC";
                                                $res = mysqli_query($db, $ssql);
                                                while ($row = mysqli_fetch_array($res)) {
                                                    echo '<option value="' . $row['c_id'] . '">' . htmlspecialchars($row['c_name']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 form-group" id="file-group">
                                            <label class="control-label">Image</label>
                                            <input type="file" name="file" class="form-control" accept="image/jpeg,image/png,image/gif" required>
                                            <small class="text-muted">Max 1MB. JPG, PNG, GIF.</small>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-6 form-group" id="fssai_license-group">
                                            <label class="control-label">FSSAI License</label>
                                            <input type="file" name="fssai_license" class="form-control" accept="application/pdf" required>
                                            <small class="text-muted">Max 5MB. PDF only.</small>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>

                                    <h3 class="box-title m-t-40">Restaurant Address</h3>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12 form-group" id="address-group">
                                            <textarea name="address" style="height:100px;" class="form-control" required></textarea>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 form-group" id="latitude-group">
                                            <label class="control-label">Latitude</label>
                                            <input type="text" id="latitude" name="latitude" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-4 form-group" id="longitude-group">
                                            <label class="control-label">Longitude</label>
                                            <input type="text" id="longitude" name="longitude" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                        <div class="col-md-4 form-group" id="city-group">
                                            <label class="control-label">City</label>
                                            <input type="text" id="city" name="city" class="form-control" readonly required>
                                            <div class="error-message"></div>
                                        </div>
                                    </div>
                                    <button type="button" id="getCoordinates" class="btn btn-info">Get Coordinates</button>
                                </div>
                                <div class="form-actions mt-3">
                                    <input type="submit" name="submit" id="submitBtn" class="btn btn-primary" value="Save" disabled>
                                    <a href="add_restaurant.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php include 'footer.php'; ?>
            </div>
        </div>

        <div id="confirmationModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeConfirmationModal()">×</span>
                <p><?php echo isset($_SESSION['success_message']) ? htmlspecialchars($_SESSION['success_message']) : ''; ?></p>
                <button class="btn btn-success" onclick="addAnotherRestaurant()">Add Another Restaurant</button>
                <button class="btn btn-secondary" onclick="goToAllRestaurants()">Go To All Restaurants</button>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/jquery.slimscroll.js"></script>
        <script src="js/sidebarmenu.js"></script>
        <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
        <script src="js/custom.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

        <script>
            function openConfirmationModal() {
                var modal = document.getElementById("confirmationModal");
                modal.style.display = "block";
            }
            function closeConfirmationModal() {
                var modal = document.getElementById("confirmationModal");
                modal.style.display = "none";
            }
            function addAnotherRestaurant() {
                closeConfirmationModal();
                window.location.href = "add_restaurant.php";
            }
            function goToAllRestaurants() {
                closeConfirmationModal();
                window.location.href = "all_restaurant.php";
            }
            window.onclick = function(event) {
                var modal = document.getElementById("confirmationModal");
                if (event.target == modal) {
                    closeConfirmationModal();
                }
            }

            $(document).ready(function() {
                function validateField(fieldId, groupId, isFile = false) {
                    var input = $('#' + fieldId);
                    var group = $('#' + groupId);
                    var errorDiv = group.find('.error-message');
                    var isValid = true;

                    group.removeClass('has-error');
                    errorDiv.text('');

                    if (isFile) {
                        if (!input[0].files || !input[0].files.length) {
                            group.addClass('has-error');
                            errorDiv.text('Please select a file.');
                            isValid = false;
                        }
                    } else {
                        var value = input.val().trim();
                        if (!value) {
                            group.addClass('has-error');
                            errorDiv.text('This field is required.');
                            isValid = false;
                        } else if (fieldId === 'email') {
                            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                            if (!emailPattern.test(value)) {
                                group.addClass('has-error');
                                errorDiv.text('Please enter a valid email.');
                                isValid = false;
                            }
                        } else if (fieldId === 'phone') {
                            var phonePattern = /^[0-9]{10,15}$/;
                            if (!phonePattern.test(value)) {
                                group.addClass('has-error');
                                errorDiv.text('Enter a valid phone number (10-15 digits).');
                                isValid = false;
                            }
                        } else if (fieldId === 'latitude' || fieldId === 'longitude') {
                            var numPattern = /^-?\d+(\.\d+)?$/;
                            if (!numPattern.test(value)) {
                                group.addClass('has-error');
                                errorDiv.text('Enter a valid number.');
                                isValid = false;
                            }
                        } else if (fieldId === 'diet_type') {
                            var validDietTypes = ['veg', 'nonveg', 'vegan', 'all'];
                            if (!validDietTypes.includes(value)) {
                                group.addClass('has-error');
                                errorDiv.text('Please select a valid diet type.');
                                isValid = false;
                            }
                        }
                    }
                    return isValid;
                }

                function validateForm() {
                    var isFormValid = true;
                    var fields = [
                        { id: 'res_name', group: 'res_name-group' },
                        { id: 'email', group: 'email-group' },
                        { id: 'password', group: 'password-group' },
                        { id: 'phone', group: 'phone-group' },
                        { id: 'diet_type', group: 'diet_type-group' },
                        { id: 'o_hr', group: 'o_hr-group' },
                        { id: 'c_hr', group: 'c_hr-group' },
                        { id: 'o_days', group: 'o_days-group' },
                        { id: 'c_name', group: 'c_name-group' },
                        { id: 'address', group: 'address-group' },
                        { id: 'latitude', group: 'latitude-group' },
                        { id: 'longitude', group: 'longitude-group' },
                        { id: 'city', group: 'city-group' },
                        { id: 'file', group: 'file-group', isFile: true },
                        { id: 'fssai_license', group: 'fssai_license-group', isFile: true }
                    ];

                    fields.forEach(function(field) {
                        if (!validateField(field.id, field.group, field.isFile)) {
                            isFormValid = false;
                        }
                    });

                    $('#submitBtn').prop('disabled', !isFormValid);
                    return isFormValid;
                }

                $('input, select, textarea').on('input change blur', function() {
                    validateForm();
                });

                $('#getCoordinates').click(function() {
                    var address = $('textarea[name="address"]').val();
                    if (!address.trim()) {
                        validateField('address', 'address-group');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Empty Address',
                            text: 'Please enter an address before fetching coordinates.'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Fetching Coordinates...',
                        text: 'Please wait while we geocode the address.',
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
                            if (response.status === 'success') {
                                $('#latitude').val(response.latitude || '');
                                $('#longitude').val(response.longitude || '');
                                $('#city').val(response.city || 'Unknown');
                                validateForm();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Geocoding Failed',
                                    text: response.message || 'Could not find coordinates.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'AJAX Error',
                                text: 'An error occurred while fetching coordinates: ' . error
                            });
                        }
                    });
                });

                $('#restaurantForm').submit(function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Form',
                            text: 'Please fill all required fields and fetch coordinates.'
                        });
                    } else {
                        $('#submitBtn').prop('disabled', true).val('Submitting...');
                    }
                });

                validateForm();
            });
        </script>
</body>
</html>
<?php unset($_SESSION['success_message']); ?>