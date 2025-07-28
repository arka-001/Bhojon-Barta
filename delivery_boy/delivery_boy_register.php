<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Validate required fields
$required_fields = [
    'db_name', 'db_phone', 'db_address', 'city', 'db_password',
    'latitude', 'longitude', 'db_account_number', 'ifsc_code',
    'account_holder_name', 'driving_license_number', 'driving_license_expiry'
];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        echo json_encode($response);
        exit;
    }
}

// Validate file uploads
if (!isset($_FILES['driving_license_photo']) || $_FILES['driving_license_photo']['size'] == 0) {
    $response['message'] = 'Driving license photo is required.';
    echo json_encode($response);
    exit;
}
if (!isset($_FILES['aadhaar_pdf']) || $_FILES['aadhaar_pdf']['size'] == 0) {
    $response['message'] = 'Aadhaar card PDF is required.';
    echo json_encode($response);
    exit;
}

// Sanitize inputs
$db_name = mysqli_real_escape_string($db, $_POST['db_name']);
$db_phone = mysqli_real_escape_string($db, $_POST['db_phone']);
$db_email = !empty($_POST['db_email']) ? mysqli_real_escape_string($db, $_POST['db_email']) : NULL;
$db_address = mysqli_real_escape_string($db, $_POST['db_address']);
$city = mysqli_real_escape_string($db, $_POST['city']);
$db_password = password_hash($_POST['db_password'], PASSWORD_DEFAULT);
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$bank_account_number = mysqli_real_escape_string($db, $_POST['db_account_number']);
$ifsc_code = mysqli_real_escape_string($db, $_POST['ifsc_code']);
$account_holder_name = mysqli_real_escape_string($db, $_POST['account_holder_name']);
$driving_license_number = mysqli_real_escape_string($db, $_POST['driving_license_number']);
$driving_license_expiry = mysqli_real_escape_string($db, $_POST['driving_license_expiry']);

// Define upload directory
$upload_dir = '../admin/delivery_boy_images/';
$absolute_dir = 'C:/xampp/htdocs/OnlineFood-PHP/OnlineFood-PHP/admin/delivery_boy_images/';
if (!is_dir($absolute_dir)) {
    mkdir($absolute_dir, 0755, true);
}

// Handle file uploads
$allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_pdf_ext = ['pdf'];

$db_photo_path = NULL;
$driving_license_photo_path = NULL;
$aadhaar_pdf_path = NULL;

// Profile Photo (optional)
if (!empty($_FILES['db_photo']['name'])) {
    $file_ext = strtolower(pathinfo($_FILES['db_photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_image_ext)) {
        $response['message'] = 'Invalid profile photo format. Allowed: jpg, jpeg, png, gif.';
        echo json_encode($response);
        exit;
    }
    $filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_ext;
    $destination = $absolute_dir . $filename;
    if (move_uploaded_file($_FILES['db_photo']['tmp_name'], $destination)) {
        $db_photo_path = 'admin/delivery_boy_images/' . $filename;
    } else {
        $response['message'] = 'Failed to upload profile photo.';
        echo json_encode($response);
        exit;
    }
}

// Driving License Photo
$file_ext = strtolower(pathinfo($_FILES['driving_license_photo']['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed_image_ext)) {
    $response['message'] = 'Invalid driving license photo format. Allowed: jpg, jpeg, png, gif.';
    echo json_encode($response);
    exit;
}
$filename = 'license_' . time() . '_' . uniqid() . '.' . $file_ext;
$destination = $absolute_dir . $filename;
if (move_uploaded_file($_FILES['driving_license_photo']['tmp_name'], $destination)) {
    $driving_license_photo_path = 'admin/delivery_boy_images/' . $filename;
} else {
    $response['message'] = 'Failed to upload driving license photo.';
    echo json_encode($response);
    exit;
}

// Aadhaar PDF
$file_ext = strtolower(pathinfo($_FILES['aadhaar_pdf']['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed_pdf_ext)) {
    $response['message'] = 'Invalid Aadhaar file format. Allowed: pdf.';
    echo json_encode($response);
    exit;
}
$filename = 'aadhaar_' . time() . '_' . uniqid() . '.' . $file_ext;
$destination = $absolute_dir . $filename;
if (move_uploaded_file($_FILES['aadhaar_pdf']['tmp_name'], $destination)) {
    $aadhaar_pdf_path = 'admin/delivery_boy_images/' . $filename;
} else {
    $response['message'] = 'Failed to upload Aadhaar PDF.';
    echo json_encode($response);
    exit;
}

// Check for duplicate phone number
$sql = "SELECT db_phone FROM delivery_boy_requests WHERE db_phone = '$db_phone' AND status = 'pending'";
$result = mysqli_query($db, $sql);
if (mysqli_num_rows($result) > 0) {
    $response['message'] = 'A pending request with this phone number already exists.';
    echo json_encode($response);
    exit;
}

// Insert into delivery_boy_requests
$sql = "INSERT INTO delivery_boy_requests (
    db_name, db_phone, db_email, db_address, city, db_photo, db_password, latitude, longitude,
    bank_account_number, ifsc_code, account_holder_name,
    driving_license_number, driving_license_expiry, driving_license_photo, aadhaar_pdf,
    request_date, status
) VALUES (
    '$db_name', '$db_phone', " . ($db_email ? "'$db_email'" : "NULL") . ",
    '$db_address', '$city', " . ($db_photo_path ? "'$db_photo_path'" : "NULL") . ",
    '$db_password', $latitude, $longitude,
    '$bank_account_number', '$ifsc_code', '$account_holder_name',
    '$driving_license_number', '$driving_license_expiry', '$driving_license_photo_path', '$aadhaar_pdf_path',
    NOW(), 'pending'
)";
if (mysqli_query($db, $sql)) {
    $response['success'] = true;
    $response['message'] = 'Application submitted successfully.';
} else {
    $response['message'] = 'Database error: ' . mysqli_error($db);
}

echo json_encode($response);
mysqli_close($db);
?>