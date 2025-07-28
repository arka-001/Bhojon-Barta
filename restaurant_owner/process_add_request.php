<?php
session_start();
// Use require for essential files
require("../connection/connect.php"); // Assumes connect.php is one level up

// Set header to return JSON
header('Content-Type: application/json');

// --- Basic Security & Setup ---
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Check if logged in as owner
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner' || !isset($_SESSION['owner_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in again.']);
    exit;
}
$owner_id = (int)$_SESSION['owner_id'];

// Check DB Connection (essential)
if (!isset($db) || !$db instanceof mysqli) {
    error_log("process_add_request.php: Database connection failed or object invalid.");
    echo json_encode(['status' => 'error', 'message' => 'Server Error: Database connection failed.']);
    exit;
}

// --- Directory Configuration (Relative to THIS script) ---
$uploadDirImagesRelative = "../admin/Res_img/";
$uploadDirDocsRelative = "../admin/Owner_docs/";
$scriptDir = __DIR__;
$basePathImages = $scriptDir . '/' . $uploadDirImagesRelative;
$basePathDocs = $scriptDir . '/' . $uploadDirDocsRelative;
$uploadDirImagesAbsolute = realpath(dirname($basePathImages)) . '/' . basename($basePathImages);
$uploadDirDocsAbsolute = realpath(dirname($basePathDocs)) . '/' . basename($basePathDocs);

// Function to check/create directory
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0775, true)) {
            error_log("process_add_request.php: Failed to create directory: " . $path);
            return false;
        }
    }
    if (!is_writable($path)) {
        error_log("process_add_request.php: Directory not writable: " . $path);
        return false;
    }
    return true;
}

// Check directories *before* processing files
$imageDirOk = ensureDirectoryExists($uploadDirImagesAbsolute);
$docsDirOk = ensureDirectoryExists($uploadDirDocsAbsolute);
if (!$imageDirOk || !$docsDirOk) {
    echo json_encode(['status' => 'error', 'message' => 'Server configuration error: Upload directories not accessible.']);
    exit;
}

// --- Validation ---
$errors = [];
$required_fields = ['c_name', 'res_name', 'email', 'phone', 'o_hr', 'c_hr', 'o_days', 'address', 'latitude', 'longitude', 'city'];

foreach ($required_fields as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        $errors[] = "Field '" . ucwords(str_replace('_', ' ', $field)) . "' is required.";
    }
}

// Diet Type Validation
$diet_types = isset($_POST['diet_type']) && is_array($_POST['diet_type']) ? $_POST['diet_type'] : [];
$valid_diet_types = ['veg', 'vegan', 'nonveg', 'all'];
$diet_types = array_intersect($diet_types, $valid_diet_types);
if (empty($diet_types)) {
    $errors[] = "At least one diet type is required.";
} else {
    if (in_array('all', $diet_types) && count($diet_types) === 1) {
        $diet_type = 'all';
    } else {
        // Exclude 'all' if other options are selected
        $diet_types = array_diff($diet_types, ['all']);
        $diet_type = !empty($diet_types) ? implode(',', $diet_types) : 'all';
    }
}

// File presence check
if (!isset($_FILES['file']) || $_FILES['file']['error'] == UPLOAD_ERR_NO_FILE) {
    $errors[] = "Restaurant Image is required.";
}
if (!isset($_FILES['fssai_license']) || $_FILES['fssai_license']['error'] == UPLOAD_ERR_NO_FILE) {
    $errors[] = "FSSAI License is required.";
}

// Format Validations (if basic requirements met)
if (empty($errors)) {
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid Email format.'; }
    if (!preg_match('/^[0-9]{10,15}$/', $_POST['phone'])) { $errors[] = 'Invalid Phone format (10-15 digits).'; }
    $latitude_posted = $_POST['latitude']; $longitude_posted = $_POST['longitude'];
    if (!is_numeric($latitude_posted) || !is_numeric($longitude_posted)) { $errors[] = 'Invalid Coordinates (must be numeric).'; }
    elseif ($latitude_posted < -90 || $latitude_posted > 90 || $longitude_posted < -180 || $longitude_posted > 180) { $errors[] = 'Coordinates out of range.'; }
    if (!empty(trim($_POST['url'] ?? '')) && !filter_var(trim($_POST['url']), FILTER_VALIDATE_URL)) { $errors[] = 'Invalid Website URL format.'; }

    // Server-Side City Serviceability Check
    $city_name_posted = trim($_POST['city']);
    $city_check_sql_server = "SELECT city_id FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1";
    $stmt_city_server = $db->prepare($city_check_sql_server);
    if ($stmt_city_server) {
        $city_lower_server = strtolower($city_name_posted);
        $stmt_city_server->bind_param("s", $city_lower_server);
        if ($stmt_city_server->execute()) {
            $stmt_city_server->store_result();
            if ($stmt_city_server->num_rows == 0) {
                $errors[] = 'Service is not available in the provided city (' . htmlspecialchars($city_name_posted) . ').';
            }
        } else { $errors[] = "DB error during city verification."; error_log("process_add_request: City check execute error: ".$stmt_city_server->error); }
        $stmt_city_server->close();
    } else { $errors[] = "DB error preparing city verification."; error_log("process_add_request: City check prepare error: ".$db->error); }
}

// --- File Upload Processing (Only if no prior validation errors) ---
$imageNewName = null;
$fssaiNewName = null;
$uploadedFilesAbsPathsForCleanup = [];
$uploadErrorMessages = [
    UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
    UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
    UPLOAD_ERR_PARTIAL => 'File only partially uploaded.',
    UPLOAD_ERR_NO_FILE => 'No file uploaded.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.'
];

if (empty($errors)) {
    // Process restaurant image file
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $imageFile = $_FILES['file'];
        $imageExt = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
        $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageExt, $allowedImageExt)) { $errors[] = 'Invalid image type. Allowed: jpg, jpeg, png, gif.'; }
        elseif ($imageFile['size'] > 2 * 1024 * 1024) { $errors[] = 'Image too large (Max 2MB).'; }
        else {
            $imageNewName = 'res_' . $owner_id . '_' . time() . '_' . uniqid() . '.' . $imageExt;
            $imageStoreAbs = rtrim($uploadDirImagesAbsolute, '/') . '/' . $imageNewName;
            if (move_uploaded_file($imageFile['tmp_name'], $imageStoreAbs)) {
                $uploadedFilesAbsPathsForCleanup[] = $imageStoreAbs;
                error_log("process_add_request: Image uploaded successfully: " . $imageStoreAbs);
            } else {
                $errors[] = 'Failed to save restaurant image.';
                error_log("process_add_request: move_uploaded_file FAILED for image: " . $imageStoreAbs);
                $imageNewName = null;
            }
        }
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Restaurant Image upload failed: ' . ($uploadErrorMessages[$_FILES['file']['error']] ?? 'Unknown error');
    }

    // Process FSSAI file
    if (empty($errors) && isset($_FILES['fssai_license']) && $_FILES['fssai_license']['error'] === UPLOAD_ERR_OK) {
        $fssaiFile = $_FILES['fssai_license'];
        $fssaiExt = strtolower(pathinfo($fssaiFile['name'], PATHINFO_EXTENSION));
        if ($fssaiExt !== 'pdf') { $errors[] = 'FSSAI License must be a PDF.'; }
        elseif ($fssaiFile['size'] > 5 * 1024 * 1024) { $errors[] = 'FSSAI PDF too large (Max 5MB).'; }
        elseif (mime_content_type($fssaiFile['tmp_name']) !== 'application/pdf') { $errors[] = 'Invalid FSSAI file (not a PDF).'; }
        else {
            $fssaiNewName = 'fssai_' . $owner_id . '_' . time() . '_' . uniqid() . '.' . $fssaiExt;
            $fssaiStoreAbs = rtrim($uploadDirDocsAbsolute, '/') . '/' . $fssaiNewName;
            if (move_uploaded_file($fssaiFile['tmp_name'], $fssaiStoreAbs)) {
                $uploadedFilesAbsPathsForCleanup[] = $fssaiStoreAbs;
                error_log("process_add_request: FSSAI uploaded successfully: " . $fssaiStoreAbs);
            } else {
                $errors[] = 'Failed to save FSSAI license.';
                error_log("process_add_request: move_uploaded_file FAILED for FSSAI: " . $fssaiStoreAbs);
                $fssaiNewName = null;
            }
        }
    } elseif (isset($_FILES['fssai_license']) && $_FILES['fssai_license']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'FSSAI License upload failed: ' . ($uploadErrorMessages[$_FILES['fssai_license']['error']] ?? 'Unknown error');
    }
}

// --- Final Check and Database Insertion ---
if (!empty($errors)) {
    // Clean up any files that were successfully uploaded
    foreach ($uploadedFilesAbsPathsForCleanup as $file) {
        if (file_exists($file)) { @unlink($file); }
    }
    echo json_encode(['status' => 'error', 'message' => 'Validation failed. Please fix the following:', 'errors' => $errors]);
    exit;
}

// If we reach here, validation passed and files are uploaded.
$res_name = trim($_POST['res_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$url = trim($_POST['url'] ?? '');
$o_hr = trim($_POST['o_hr']);
$c_hr = trim($_POST['c_hr']);
$o_days = trim($_POST['o_days']);
$address = trim($_POST['address']);
$c_id = filter_input(INPUT_POST, 'c_name', FILTER_VALIDATE_INT);
$latitude_db = $_POST['latitude'];
$longitude_db = $_POST['longitude'];
$city_db = trim($_POST['city']);

// Prepare INSERT statement (added diet_type)
$sql = "INSERT INTO restaurant_requests
        (owner_id, c_id, title, email, phone, url, o_hr, c_hr, o_days, address,
         image, fssai_license, latitude, longitude, city, diet_type, status, request_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
$stmt = $db->prepare($sql);

if (!$stmt) {
    error_log("process_add_request: SQL Prepare failed: " . $db->error);
    // Clean up uploaded files on DB error
    foreach ($uploadedFilesAbsPathsForCleanup as $file) { if (file_exists($file)) { @unlink($file); } }
    echo json_encode(['status' => 'error', 'message' => 'Database error preparing request.']);
    exit;
}

// Bind parameters (added diet_type)
$stmt->bind_param("iissssssssssddss",
    $owner_id, $c_id, $res_name, $email, $phone, $url, $o_hr, $c_hr,
    $o_days, $address, $imageNewName, $fssaiNewName, $latitude_db, $longitude_db, $city_db, $diet_type
);

// Execute
if ($stmt->execute()) {
    error_log("process_add_request: Request submitted successfully for owner $owner_id, diet_type: $diet_type");
    echo json_encode(['status' => 'success', 'message' => 'Restaurant request submitted successfully! Awaiting approval.']);
} else {
    error_log("process_add_request: SQL Execute failed: " . $stmt->error);
    // Clean up uploaded files on DB error
    foreach ($uploadedFilesAbsPathsForCleanup as $file) { if (file_exists($file)) { @unlink($file); } }
    // Check for duplicate entry error specifically
    if ($stmt->errno == 1062) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: A similar request might already exist.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error saving request. Please try again.']);
    }
}

$stmt->close();
$db->close();
exit;
?>