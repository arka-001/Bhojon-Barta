<?php
session_start();

// Assuming admin login check
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

include("../connection/connect.php");

// Function to geocode an address using LocationIQ (copied from geocode.php for simplicity)
function geocodeAddress($address, $api_key) {
    $address = urlencode($address);
    $url = "https://us1.locationiq.com/v1/autocomplete?key={$api_key}&q={$address}&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return [
            'latitude' => floatval($data[0]['lat']),
            'longitude' => floatval($data[0]['lon']),
        ];
    }
    return null;
}

$api_key = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Your LocationIQ API key

if (isset($_POST['add_city'])) {
    $city_name = mysqli_real_escape_string($db, $_POST['city_name']);
    
    // Verify the city before adding
    $geocode_result = geocodeAddress($city_name, $api_key);
    
    if ($geocode_result) {
        $latitude = $geocode_result['latitude'];
        $longitude = $geocode_result['longitude'];
        
        $stmt = $db->prepare("INSERT INTO delivery_cities (city_name, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $city_name, $latitude, $longitude);
        if ($stmt->execute()) {
            $success = "City added successfully with coordinates!";
        } else {
            $error = "Error adding city: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Could not verify city coordinates. Please check the city name.";
    }
}

if (isset($_POST['toggle_city'])) {
    $city_id = intval($_POST['city_id']);
    $is_active = intval($_POST['is_active']) ? 0 : 1;
    $stmt = $db->prepare("UPDATE delivery_cities SET is_active = ? WHERE city_id = ?");
    $stmt->bind_param("ii", $is_active, $city_id);
    if ($stmt->execute()) {
        $success = "City status updated!";
    } else {
        $error = "Error updating city: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_POST['verify_city'])) {
    $city_id = intval($_POST['city_id']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    
    $stmt = $db->prepare("UPDATE delivery_cities SET latitude = ?, longitude = ? WHERE city_id = ?");
    $stmt->bind_param("ddi", $latitude, $longitude, $city_id);
    if ($stmt->execute()) {
        $success = "City coordinates verified and saved!";
    } else {
        $error = "Error saving coordinates: " . $stmt->error;
    }
    $stmt->close();
}

$cities = [];
$result = mysqli_query($db, "SELECT * FROM delivery_cities ORDER BY city_name");
while ($row = mysqli_fetch_assoc($result)) {
    $cities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Delivery Cities</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; }
        .table { margin-top: 20px; }
        .verify-btn { margin-left: 10px; }
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
                        <b><img src="../images/inc.jpg" alt="homepage" class="dark-logo" /></b>
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav my-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted" href="#" data-toggle="dropdown"
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
                                <li><a id="allOwnersLink" href="all_owners.php">All Owners</a></li>
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
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary">Manage Delivery Cities</h3>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">

                                <?php if (!empty($success)): ?>
                                <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: '<?php echo $success; ?>',
                                });
                                </script>
                                <?php endif; ?>
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <!-- Add City Form -->
                                <form method="post" class="mb-4">
                                    <div class="form-group">
                                        <label for="city_name">Add New City:</label>
                                        <input type="text" class="form-control" id="city_name" name="city_name" required>
                                    </div>
                                    <button type="submit" name="add_city" class="btn btn-primary">Add City</button>
                                </form>

                                <!-- City List -->
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>City Name</th>
                                                <th>Status</th>
                                                <th>Latitude</th>
                                                <th>Longitude</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cities as $city): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($city['city_name']); ?></td>
                                                <td><?php echo $city['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                                <td><?php echo $city['latitude'] ? number_format($city['latitude'], 6) : 'Not Set'; ?></td>
                                                <td><?php echo $city['longitude'] ? number_format($city['longitude'], 6) : 'Not Set'; ?></td>
                                                <td>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="city_id" value="<?php echo $city['city_id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $city['is_active']; ?>">
                                                        <button type="submit" name="toggle_city" class="btn btn-sm <?php echo $city['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                                            <?php echo $city['is_active'] ? 'Disable' : 'Enable'; ?>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-info verify-btn" 
                                                            data-city="<?php echo htmlspecialchars($city['city_name']); ?>" 
                                                            data-city-id="<?php echo $city['city_id']; ?>">Verify</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer"> © 2023 All rights reserved. </footer>
        </div>
    </div>

    <script src="js/lib/jquery/jquery.min.js"></script>
    <script src="js/lib/bootstrap/js/popper.min.js"></script>
    <script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/sidebarmenu.js"></script>
    <script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.verify-btn').on('click', function() {
                const cityName = $(this).data('city');
                const cityId = $(this).data('city-id');
                console.log('Verifying city:', cityName);

                $.ajax({
                    url: '../geocode.php',
                    method: 'POST',
                    data: { address: cityName },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Geocode response:', data);
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'City Verified',
                                html: `City: ${data.city || cityName}<br>Latitude: ${data.latitude}<br>Longitude: ${data.longitude}`,
                                showConfirmButton: true,
                                showCancelButton: true,
                                confirmButtonText: 'Save Coordinates',
                                cancelButtonText: 'Close'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: 'delivery_city.php',
                                        method: 'POST',
                                        data: {
                                            verify_city: true,
                                            city_id: cityId,
                                            latitude: data.latitude,
                                            longitude: data.longitude
                                        },
                                        success: function() {
                                            Swal.fire('Saved!', 'Coordinates have been saved.', 'success').then(() => {
                                                location.reload();
                                            });
                                        },
                                        error: function() {
                                            Swal.fire('Error', 'Failed to save coordinates.', 'error');
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Verification Failed',
                                text: 'Unable to verify city: ' + data.message,
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error verifying city. Please try again.',
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>