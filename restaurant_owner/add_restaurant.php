<?php
session_start();
// Use require for essential files
require("../connection/connect.php"); // Assumes connect.php is one level up
error_reporting(E_ALL);
ini_set('display_errors', 1); // Keep for dev

// --- Check Login ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner' || !isset($_SESSION['owner_id'])) {
    $_SESSION['error'] = '<div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>Please log in as a restaurant owner.</div>';
    header("Location: restaurant_owner_login.php"); // Adjust path if needed
    exit();
}
$owner_id = (int)$_SESSION['owner_id'];

// --- Database Check (for displaying existing requests) ---
$db_error_message = null;
if (!isset($db) || !$db instanceof mysqli) {
    error_log("add_restaurant.php (display part): Database connection failed or object invalid.");
    $db_error_message = "Could not connect to the database to load existing requests.";
}

// --- Configuration ---
$base_url = 'http://localhost/OnlineFood-PHP/OnlineFood-PHP/'; // Adjust! Used for image/doc links
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$base_url_path = parse_url($base_url, PHP_URL_PATH);
$project_base_path = $doc_root . ($base_url_path ? '/' . trim($base_url_path, '/') : '');

// --- Fetch Existing Requests (only if DB connection is ok) ---
$requests = [];
$list_error_msg = null;
if (!$db_error_message && isset($db)) { // Check if DB connection is valid
    $sql_list = "SELECT rr.*, rc.c_name
                 FROM restaurant_requests rr
                 LEFT JOIN res_category rc ON rr.c_id = rc.c_id
                 WHERE rr.owner_id = ?
                 ORDER BY rr.request_date DESC";
    $stmt_list = $db->prepare($sql_list);
    if ($stmt_list) {
        $stmt_list->bind_param("i", $owner_id);
        if ($stmt_list->execute()) {
            $query_list = $stmt_list->get_result();
            if($query_list) {
                $requests = $query_list->fetch_all(MYSQLI_ASSOC);
            } else { $list_error_msg = "Error fetching results: " . $stmt_list->error; }
        } else { $list_error_msg = "Error executing query: " . $stmt_list->error; }
        $stmt_list->close();
    } else { $list_error_msg = "Error preparing statement: " . $db->error; }

    if ($list_error_msg) {
        error_log("Error fetching existing requests for owner $owner_id: $list_error_msg");
    }
} else {
    $list_error_msg = $db_error_message; // Assign DB connection error if it occurred
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Restaurant - Owner Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <!-- Custom Styles -->
    <style>
        /* Existing Styles */
        body { background-color: #f4f7f6; font-family: sans-serif; padding-bottom: 4rem; }
        .container { max-width: 960px; margin-top: 2rem; }
        .card { margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 0.5rem; border: none;}
        .card-header { background-color: #007bff; color: white; font-size: 1.25rem; font-weight: 500; border-radius: 0.5rem 0.5rem 0 0; position: relative;}
        .form-label { font-weight: 600; margin-bottom: 0.3rem; }
        .form-control:focus, .form-select:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
        .error-message { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; display: none; }
        .form-group.has-error .form-control, .form-group.has-error .form-select { border-color: #dc3545; }
        .form-group.has-error .error-message { display: block; }
        .text-danger { color: #dc3545 !important; }
        .btn-dashboard { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.15); border: none; padding: 0.3rem 0.8rem; font-size: 0.9rem; color: white !important;}
        .btn-dashboard:hover { background: rgba(255, 255, 255, 0.25); }
        .img-thumbnail { max-width: 60px; height: auto; padding: 2px; background-color: #fff; border: 1px solid #dee2e6; border-radius: .25rem; }
        .doc-link { font-size: 0.9em; }
        .badge { font-size: 0.75em; padding: 0.4em 0.7em; }
        .status-approved { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-pending { background-color: #ffc107; color: #212529; }
        #loadingIndicator { display: none; }
        /* New Diet Type Styles */
        .diet-type-checkbox { display: flex; align-items: center; margin-bottom: 10px; }
        .diet-type-checkbox input { margin-right: 8px; }
        .diet-type-checkbox .icon { width: 16px; height: 16px; margin-right: 8px; }
        .diet-type-checkbox .icon svg { width: 100%; height: 100%; }
        .diet-type-checkbox.veg { color: #28a745; }
        .diet-type-checkbox.veg .icon svg { fill: #28a745; }
        .diet-type-checkbox.nonveg { color: #dc3545; }
        .diet-type-checkbox.nonveg .icon svg { fill: #dc3545; }
        .diet-type-checkbox.vegan { color: #17a2b8; }
        .diet-type-checkbox.vegan .icon svg { fill: #17a2b8; }
        .diet-type-checkbox.all { color: #6c757d; }
        .diet-type-checkbox.all .icon svg { fill: #6c757d; }
        .diet-type-display .icon { display: inline-block; width: 16px; height: 16px; margin-right: 4px; }
        .diet-type-display .icon svg { width: 100%; height: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card mb-4">
                    <div class="card-header">
                        Add New Restaurant Details
                        <a href="restaurant_owner_dashboard.php" class="btn btn-sm btn-light btn-dashboard" title="Back to Dashboard"><i class="fas fa-arrow-left"></i> Dashboard</a>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h2>Submit Your Restaurant Request</h2>
                            <p class="text-muted">Fill in the details below. Your request will be sent for review.</p>
                        </div>
                        <!-- Placeholder for AJAX Success/Error Messages -->
                        <div id="formMessages"></div>
                        <!-- Restaurant Form -->
                        <form id="restaurantForm" action="process_add_request.php" method="post" enctype="multipart/form-data">
                            <!-- Hidden input for city serviceability -->
                            <input type="hidden" id="is_city_serviceable" value="0">
                            <div class="row g-3">
                                <!-- Restaurant Name -->
                                <div class="col-md-6 form-group" id="res_name-group">
                                    <label for="res_name" class="form-label">Restaurant Name <span class="text-danger">*</span></label>
                                    <input type="text" name="res_name" class="form-control" id="res_name" required>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Category -->
                                <div class="col-md-6 form-group" id="c_name-group">
                                    <label for="c_name" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="c_name" class="form-select" id="c_name" required>
                                        <option value="">-- Select Category --</option>
                                        <?php
                                        if (!$db_error_message && isset($db)) {
                                            $ssql_cat = "SELECT c_id, c_name FROM res_category ORDER BY c_name ASC";
                                            $res_cat = mysqli_query($db, $ssql_cat);
                                            if ($res_cat && mysqli_num_rows($res_cat) > 0) {
                                                while ($row_cat = mysqli_fetch_assoc($res_cat)) {
                                                    echo '<option value="' . (int)$row_cat['c_id'] . '">' . htmlspecialchars($row_cat['c_name']) . '</option>';
                                                }
                                            } else { echo '<option value="" disabled>No categories found</option>'; }
                                        } else { echo '<option value="" disabled>DB Error</option>'; }
                                        ?>
                                    </select>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Email -->
                                <div class="col-md-6 form-group" id="email-group">
                                    <label for="email" class="form-label">Business Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" id="email" required>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Phone -->
                                <div class="col-md-6 form-group" id="phone-group">
                                    <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control" id="phone" pattern="[0-9]{10,15}" title="Enter 10-15 digit phone number" required>
                                    <div class="error-message"></div>
                                </div>
                                <!-- URL -->
                                <div class="col-md-12 form-group" id="url-group">
                                    <label for="url" class="form-label">Website URL (Optional)</label>
                                    <input type="url" name="url" class="form-control" id="url" placeholder="https://www.example.com">
                                    <div class="error-message"></div>
                                </div>
                                <!-- Timings -->
                                <div class="col-md-4 form-group" id="o_hr-group">
                                    <label for="o_hr" class="form-label">Open Hour <span class="text-danger">*</span></label>
                                    <select name="o_hr" class="form-select" id="o_hr" required>
                                        <option value="">Select</option>
                                        <?php foreach (['6am', '7am', '8am', '9am', '10am', '11am', '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm', '8pm', '9pm', '10pm', '11pm', '12am'] as $time) echo "<option value='$time'>$time</option>"; ?>
                                    </select>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-4 form-group" id="c_hr-group">
                                    <label for="c_hr" class="form-label">Close Hour <span class="text-danger">*</span></label>
                                    <select name="c_hr" class="form-select" id="c_hr" required>
                                        <option value="">Select</option>
                                        <?php foreach (['12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm', '8pm', '9pm', '10pm', '11pm', '12am', '1am', '2am', '3am', '4am', '5am'] as $time) echo "<option value='$time'>$time</option>"; ?>
                                    </select>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-4 form-group" id="o_days-group">
                                    <label for="o_days" class="form-label">Open Days <span class="text-danger">*</span></label>
                                    <select name="o_days" class="form-select" id="o_days" required>
                                        <option value="">Select</option>
                                        <?php foreach (['Mon-Fri', 'Mon-Sat', 'Mon-Sun', 'Sat-Sun', '24hr-x7'] as $days) echo "<option value='$days'>$days</option>"; ?>
                                    </select>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Diet Type -->
                                <div class="col-12 form-group" id="diet_type-group">
                                    <label class="form-label">Diet Type <span class="text-danger">*</span></label>
                                    <div class="diet-type-checkbox veg">
                                        <input type="checkbox" name="diet_type[]" value="veg" id="diet_veg">
                                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95.49-7.44-2.44-7.93-6.39C2.58 9.59 5.51 6.1 9.46 5.61c3.95-.49 7.44 2.44 7.93 6.39.49 3.95-2.44 7.44-6.39 7.93zM12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg></span>
                                        <label for="diet_veg">Vegetarian</label>
                                    </div>
                                    <div class="diet-type-checkbox vegan">
                                        <input type="checkbox" name="diet_type[]" value="vegan" id="diet_vegan">
                                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M7 3c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h10c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2H7zm0 2h10v6H7V5zm0 8h10v6H7v-6z"/></svg></span>
                                        <label for="diet_vegan">Vegan</label>
                                    </div>
                                    <div class="diet-type-checkbox nonveg">
                                        <input type="checkbox" name="diet_type[]" value="nonveg" id="diet_nonveg">
                                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg></span>
                                        <label for="diet_nonveg">Non-Vegetarian</label>
                                    </div>
                                    <div class="diet-type-checkbox all">
                                        <input type="checkbox" name="diet_type[]" value="all" id="diet_all" checked>
                                        <span class="icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/></svg></span>
                                        <label for="diet_all">All</label>
                                    </div>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Address -->
                                <div class="col-12 form-group" id="address-group">
                                    <label for="address" class="form-label">Full Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control" id="address" rows="3" required></textarea>
                                    <div class="error-message"></div>
                                </div>
                                <!-- Geocode Button -->
                                <div class="col-12 mb-2">
                                    <button type="button" id="getCoordinates" class="btn btn-sm btn-info"><i class="fas fa-location-crosshairs"></i> Get Coordinates & Verify City</button>
                                    <small class="text-muted ms-2">Enter address first, then click.</small>
                                </div>
                                <!-- Coordinates & City -->
                                <div class="col-md-4 form-group" id="latitude-group">
                                    <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                                    <input type="text" name="latitude" class="form-control" id="latitude" readonly required placeholder="Auto-filled">
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-4 form-group" id="longitude-group">
                                    <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                                    <input type="text" name="longitude" class="form-control" id="longitude" readonly required placeholder="Auto-filled">
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-4 form-group" id="city-group">
                                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control" id="city" readonly required placeholder="Auto-filled">
                                    <div class="error-message"></div>
                                </div>
                                <!-- File Uploads -->
                                <div class="col-md-6 form-group" id="file-group">
                                    <label for="file" class="form-label">Restaurant Image <span class="text-danger">*</span></label>
                                    <input type="file" name="file" class="form-control" id="file" accept="image/jpeg,image/png,image/gif" required>
                                    <small class="form-text text-muted">Max 2MB. JPG, PNG, GIF.</small>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-6 form-group" id="fssai_license-group">
                                    <label for="fssai_license" class="form-label">FSSAI License (PDF) <span class="text-danger">*</span></label>
                                    <input type="file" name="fssai_license" class="form-control" id="fssai_license" accept="application/pdf" required>
                                    <small class="form-text text-muted">Max 5MB. PDF only.</small>
                                    <div class="error-message"></div>
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" disabled>
                                    <span id="loadingIndicator" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span id="submitBtnText"><i class="fas fa-paper-plane"></i> Submit Restaurant Request</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div> <!-- End Add Request Card -->
                <!-- Existing Requests Card -->
                <div class="card">
                    <div class="card-header" style="background-color: #6c757d;">
                        My Previous Restaurant Requests
                    </div>
                    <div class="card-body">
                        <?php if ($list_error_msg): ?>
                            <div class="alert alert-warning">Could not load previous requests: <?php echo htmlspecialchars($list_error_msg); ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover caption-top align-middle">
                                <caption>List of your submitted restaurant addition requests</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>City</th>
                                        <th>Diet Type</th>
                                        <th class="text-center">Image</th>
                                        <th class="text-center">FSSAI</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($requests) && !$list_error_msg) {
                                        echo '<tr><td colspan="9" class="text-center text-muted py-4">No requests found.</td></tr>';
                                    } else {
                                        foreach ($requests as $req) {
                                            $status_class = 'badge ';
                                            switch (strtolower($req['status'] ?? 'pending')) {
                                                case 'approved': $status_class .= 'status-approved'; break;
                                                case 'rejected': $status_class .= 'status-rejected'; break;
                                                default: $status_class .= 'status-pending'; break;
                                            }
                                            // Construct URLs/Paths
                                            $fssai_rel_path = 'admin/Owner_docs/' . rawurlencode(htmlspecialchars($req['fssai_license'] ?? ''));
                                            $image_rel_path = 'admin/Res_img/' . rawurlencode(htmlspecialchars($req['image'] ?? ''));
                                            $fssai_url = !empty($req['fssai_license']) ? rtrim($base_url, '/') . '/' . $fssai_rel_path : null;
                                            $image_url = !empty($req['image']) ? rtrim($base_url, '/') . '/' . $image_rel_path : null;
                                            $fssai_server_path = !empty($req['fssai_license']) ? rtrim($project_base_path, '/') . '/admin/Owner_docs/' . htmlspecialchars($req['fssai_license']) : null;
                                            $image_server_path = !empty($req['image']) ? rtrim($project_base_path, '/') . '/admin/Res_img/' . htmlspecialchars($req['image']) : null;
                                            // Generate links/images
                                            $fssaiLink = ($fssai_url && $fssai_server_path && file_exists($fssai_server_path))
                                                ? '<a href="' . $fssai_url . '" target="_blank" class="doc-link" title="View FSSAI PDF"><i class="fas fa-file-pdf"></i> View</a>'
                                                : '<span class="text-muted fst-italic" title="File missing">N/A</span>';
                                            $imageTag = ($image_url && $image_server_path && file_exists($image_server_path))
                                                ? '<a href="' . $image_url . '" target="_blank" title="View Image"><img src="' . $image_url . '" alt="Restaurant Image" class="img-thumbnail"></a>'
                                                : '<span class="text-muted fst-italic" title="Image missing">No Img</span>';
                                            // Diet Type Display
                                            $diet_type = $req['diet_type'] ?? 'all';
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
                                            echo '<tr>
                                                    <td>' . htmlspecialchars($req['c_name'] ?? 'N/A') . '</td>
                                                    <td>' . htmlspecialchars($req['title'] ?? '') . '</td>
                                                    <td>' . htmlspecialchars($req['address'] ?? '') . '</td>
                                                    <td>' . htmlspecialchars($req['city'] ?? 'N/A') . '</td>
                                                    <td>' . $diet_display . '</td>
                                                    <td class="text-center">' . $imageTag . '</td>
                                                    <td class="text-center">' . $fssaiLink . '</td>
                                                    <td>' . (!empty($req['request_date']) ? date("d M Y, H:i", strtotime($req['request_date'])) : 'N/A') . '</td>
                                                    <td><span class="' . $status_class . '">' . ucfirst(htmlspecialchars($req['status'] ?? 'pending')) . '</span></td>
                                                  </tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- End Existing Requests Card -->
            </div>
        </div>
    </div>
    <!-- JS Includes -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <!-- Custom AJAX and Validation Script -->
    <script>
    $(document).ready(function() {
        // --- Client-side Validation ---
        function validateField(fieldId, groupId, isFile = false, required = true) {
            const input = $('#' + fieldId);
            const group = $('#' + groupId);
            const errorDiv = group.find('.error-message');
            let isValid = true;
            const value = isFile ? (input[0].files && input[0].files.length > 0 ? input[0].files[0].name : '') : (input.val() || '');
            const trimmedValue = typeof value === 'string' ? value.trim() : value;

            group.removeClass('has-error');
            errorDiv.text('');

            if (required && !trimmedValue) {
                group.addClass('has-error'); errorDiv.text('Required.'); isValid = false;
            } else if (trimmedValue) {
                if (input.attr('type') === 'email' && !/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(trimmedValue)) {
                    group.addClass('has-error'); errorDiv.text('Invalid email.'); isValid = false;
                } else if (input.attr('type') === 'url' && !input.prop('required') && trimmedValue && !/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/i.test(trimmedValue)) {
                    group.addClass('has-error'); errorDiv.text('Invalid URL.'); isValid = false;
                } else if (input.attr('pattern') && !new RegExp('^' + input.attr('pattern') + '$').test(trimmedValue)) {
                    group.addClass('has-error'); errorDiv.text(input.attr('title') || 'Invalid format.'); isValid = false;
                } else if (isFile && input[0].files.length > 0) {
                    const file = input[0].files[0];
                    const maxSizeMB = input.attr('id') === 'file' ? 2 : 5;
                    const allowedTypes = input.attr('accept').split(',').map(t => t.trim().toLowerCase());
                    if (file.size > maxSizeMB * 1024 * 1024) {
                        group.addClass('has-error'); errorDiv.text(`Max ${maxSizeMB}MB`); isValid = false;
                    } else {
                        let typeMatch = false;
                        let fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                        let fileMimeType = file.type.toLowerCase();
                        allowedTypes.forEach(type => {
                            if ((type.startsWith('.') && fileExtension === type) ||
                                (type.includes('/') && !type.endsWith('/*') && fileMimeType === type) ||
                                (type.endsWith('/*') && fileMimeType.startsWith(type.slice(0, -1)))) {
                                typeMatch = true;
                            }
                        });
                        if (!typeMatch && allowedTypes.length > 0 && allowedTypes[0] !== '*/*') {
                            let allowedDisplay = allowedTypes.map(t => t.startsWith('.') ? t.toUpperCase().substring(1) : (t.endsWith('/*')?t.split('/')[0].toUpperCase() : t.split('/')[1].toUpperCase())).join(', ');
                            group.addClass('has-error'); errorDiv.text('Allowed: ' + allowedDisplay); isValid = false;
                        }
                    }
                }
            }
            if (input.prop('readonly') && required) {
                if (!trimmedValue) {
                    if (!group.hasClass('has-error')) { group.addClass('has-error'); errorDiv.text('Use "Get Coordinates".'); }
                    isValid = false;
                } else if (fieldId === 'city') {
                    if ($('#is_city_serviceable').val() !== '1') {
                        if (!group.hasClass('has-error')) { group.addClass('has-error'); errorDiv.text('Service unavailable.'); }
                        isValid = false;
                    }
                }
            }
            return isValid;
        }

        // Validate Diet Type
        function validateDietType() {
            const group = $('#diet_type-group');
            const checkboxes = group.find('input[name="diet_type[]"]');
            const errorDiv = group.find('.error-message');
            const isChecked = checkboxes.is(':checked');
            group.removeClass('has-error');
            errorDiv.text('');
            if (!isChecked) {
                group.addClass('has-error');
                errorDiv.text('Please select at least one diet type.');
                return false;
            }
            return true;
        }

        // Validate Entire Form
        function validateForm() {
            let isFormValid = true;
            $('#restaurantForm .form-group').each(function() {
                const group = $(this);
                const inputElement = group.find('input[required], select[required], textarea[required]').first();
                if (inputElement.length) {
                    if (!validateField(inputElement.attr('id'), group.attr('id'), inputElement.attr('type') === 'file', true)) {
                        isFormValid = false;
                    }
                }
                const urlInput = group.find('#url');
                if (urlInput.length && urlInput.val().trim() !== '' && !validateField('url', 'url-group', false, false)) {
                    isFormValid = false;
                }
            });
            if (!validateDietType()) {
                isFormValid = false;
            }
            if ($('#city').prop('required') && ($('#is_city_serviceable').val() !== '1' || !$('#city').val().trim())) {
                isFormValid = false;
            }
            $('#submitBtn').prop('disabled', !isFormValid);
            return isFormValid;
        }

        // Attach Validation Listeners
        $('#restaurantForm').find('input, select, textarea').on('input change blur', function() {
            const inputElement = $(this);
            const group = inputElement.closest('.form-group');
            if (group.length) {
                validateField(inputElement.attr('id'), group.attr('id'), inputElement.attr('type') === 'file', inputElement.prop('required'));
            }
            validateForm();
        });

        // Diet Type Checkbox Logic
        $('#diet_all').on('change', function() {
            if ($(this).is(':checked')) {
                $('#diet_veg, #diet_vegan, #diet_nonveg').prop('checked', false).prop('disabled', true);
            } else {
                $('#diet_veg, #diet_vegan, #diet_nonveg').prop('disabled', false);
            }
            validateDietType();
            validateForm();
        });
        $('#diet_veg, #diet_vegan, #diet_nonveg').on('change', function() {
            if ($('#diet_veg').is(':checked') || $('#diet_vegan').is(':checked') || $('#diet_nonveg').is(':checked')) {
                $('#diet_all').prop('checked', false).prop('disabled', true);
            } else {
                $('#diet_all').prop('disabled', false);
            }
            validateDietType();
            validateForm();
        });

        // Geocoding & City Verification
        $('#getCoordinates').click(function() {
            const addressInput = $('#address');
            const address = addressInput.val().trim();
            const button = $(this);

            $('#is_city_serviceable').val('0');
            $('#latitude').val(''); $('#longitude').val(''); $('#city').val('');
            $('#latitude-group, #longitude-group, #city-group').removeClass('has-error').find('.error-message').text('');
            validateForm();

            if (!validateField('address', 'address-group', false, true)) {
                Swal.fire('Address Required', 'Please enter a valid address first.', 'warning'); addressInput.focus(); return;
            }
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Locating...');

            const geocodeUrl = '../geocode.php';
            $.ajax({
                type: 'POST', url: geocodeUrl, data: { address: address }, dataType: 'json', timeout: 15000,
                success: function(geoResponse) {
                    if (geoResponse?.status === 'success' && geoResponse.latitude && geoResponse.longitude && geoResponse.city) {
                        $('#latitude').val(parseFloat(geoResponse.latitude).toFixed(8));
                        $('#longitude').val(parseFloat(geoResponse.longitude).toFixed(8));
                        $('#city').val(geoResponse.city);
                        button.html('<i class="fas fa-spinner fa-spin"></i> Verifying City...');

                        const checkCityUrl = 'check_city_service.php';
                        $.ajax({
                            type: 'POST', url: checkCityUrl, data: { city_name: geoResponse.city }, dataType: 'json', timeout: 10000,
                            success: function(checkResponse) {
                                if (checkResponse?.serviceable === true) {
                                    $('#is_city_serviceable').val('1');
                                    Swal.fire({ icon: 'success', title: 'Verified!', text: `Area (${geoResponse.city}) is serviceable.`, timer: 2500, showConfirmButton: false });
                                } else {
                                    $('#is_city_serviceable').val('0');
                                    Swal.fire({ icon: 'warning', title: 'Service Unavailable', text: checkResponse?.message || `Service not available in ${geoResponse.city}.`, confirmButtonColor: '#f59e0b' });
                                }
                            },
                            error: function(xhr, status) {
                                $('#is_city_serviceable').val('0');
                                Swal.fire('Error', `Could not verify city (${status}).`, 'error');
                            },
                            complete: function() {
                                button.prop('disabled', false).html('<i class="fas fa-location-crosshairs"></i> Get Coordinates & Verify City');
                                validateForm();
                            }
                        });
                    } else {
                        Swal.fire('Geocoding Failed', geoResponse?.message || 'Could not find location.', 'error');
                        button.prop('disabled', false).html('<i class="fas fa-location-crosshairs"></i> Get Coordinates & Verify City');
                        validateForm();
                    }
                },
                error: function(xhr, status) {
                    Swal.fire('AJAX Error', `Location service error (${status}).`, 'error');
                    button.prop('disabled', false).html('<i class="fas fa-location-crosshairs"></i> Get Coordinates & Verify City');
                    validateForm();
                }
            });
        });

        // AJAX Form Submission
        $('#restaurantForm').submit(function(event) {
            event.preventDefault();

            if (!validateForm()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Submit',
                    text: 'Please fix the errors indicated on the form before submitting.',
                    confirmButtonColor: '#d33'
                });
                $('.form-group.has-error').first().find('input, select, textarea').first().focus();
                return;
            }

            const form = this;
            const formData = new FormData(form);
            const submitButton = $('#submitBtn');
            const loadingIndicator = $('#loadingIndicator');
            const submitBtnText = $('#submitBtnText');
            const formMessages = $('#formMessages');

            formMessages.html('');
            submitButton.prop('disabled', true);
            loadingIndicator.show();
            submitBtnText.text('Submitting...');

            $.ajax({
                type: 'POST',
                url: $(form).attr('action'),
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(response) {
                    if (response && response.status === 'success') {
                        formMessages.html('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            '<strong>Success!</strong> ' + response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                        form.reset();
                        $('#is_city_serviceable').val('0');
                        $('#diet_all').prop('checked', true).trigger('change');
                        validateForm();
                        $('html, body').animate({ scrollTop: 0 }, 'smooth');
                    } else {
                        let errorHtml = '<strong>Error!</strong> ' + (response.message || 'An unknown error occurred.');
                        if (response.errors && Array.isArray(response.errors)) {
                            errorHtml += '<br><ul>';
                            response.errors.forEach(function(err) {
                                errorHtml += '<li>' + $('<div>').text(err).html() + '</li>';
                            });
                            errorHtml += '</ul>';
                        }
                        formMessages.html('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            errorHtml +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                        $('html, body').animate({ scrollTop: 0 }, 'smooth');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'An error occurred while submitting the request.';
                    if (status === 'timeout') {
                        errorMsg = 'The request timed out. Please try again.';
                    } else if (xhr.responseText) {
                        console.error("AJAX Error Response:", xhr.responseText);
                        errorMsg += ` (Status: ${status}, Error: ${error})`;
                    }
                    formMessages.html('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<strong>Submission Failed!</strong> ' + errorMsg +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                    $('html, body').animate({ scrollTop: 0 }, 'smooth');
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                    loadingIndicator.hide();
                    submitBtnText.html('<i class="fas fa-paper-plane"></i> Submit Restaurant Request');
                    validateForm();
                }
            });
        });

        // Initial State
        validateForm();
        if ($('#formMessages div').length) {
            $('html, body').animate({ scrollTop: 0 }, 'smooth');
        }
        // Trigger initial diet type validation
        $('#diet_all').trigger('change');
    });
    </script>
</body>
</html>
<?php
if (isset($db) && $db instanceof mysqli) {
    mysqli_close($db);
}
?>