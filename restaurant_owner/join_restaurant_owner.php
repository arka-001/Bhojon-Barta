<?php
header('Content-Type: application/json');
include("../connection/connect.php");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Validate inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');
$address = trim($_POST['address'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$city = trim($_POST['city'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);

if (empty($name)) $errors[] = 'Name is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Valid 10-digit phone number is required';
if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
if (empty($address)) $errors[] = 'Address is required';
if (empty($city)) $errors[] = 'City is required';
if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
    $errors[] = 'Valid location coordinates are required';
}
if ($category_id <= 0) $errors[] = 'Restaurant category is required';

// Validate email uniqueness
$sql = "SELECT email FROM restaurant_owners WHERE email = ? UNION SELECT email FROM restaurant_owner_requests WHERE email = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    $errors[] = 'Email is already registered';
}
mysqli_stmt_close($stmt);

// Validate city
$sql = "SELECT city_name FROM delivery_cities WHERE city_name = ? AND is_active = 1";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "s", $city);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) == 0) {
    $errors[] = 'We do not deliver to this city';
}
mysqli_stmt_close($stmt);

// Validate category
$sql = "SELECT c_id FROM res_category WHERE c_id = ?";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) == 0) {
    $errors[] = 'Invalid restaurant category';
}
mysqli_stmt_close($stmt);

// Handle file uploads
$upload_dir = 'C:/xampp/htdocs/OnlineFood-PHP/OnlineFood-PHP/admin/Owner_docs/';
$doc_dir = 'Owner_docs/';
$allowed_image_types = ['image/jpeg', 'image/png'];
$allowed_pdf_types = ['application/pdf'];

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$restaurant_photo = '';
if (isset($_FILES['restaurant_photo']) && $_FILES['restaurant_photo']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['restaurant_photo'];
    $file_type = mime_content_type($file['tmp_name']);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($file_type, $allowed_image_types)) {
        $restaurant_photo = $doc_dir . uniqid('image_') . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . basename($restaurant_photo))) {
            $errors[] = 'Failed to upload restaurant photo';
        }
    } else {
        $errors[] = 'Restaurant photo must be JPG or PNG';
    }
} else {
    $errors[] = 'Restaurant photo is required';
}

$fssai_license = '';
if (isset($_FILES['fssai_license']) && $_FILES['fssai_license']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['fssai_license'];
    $file_type = mime_content_type($file['tmp_name']);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($file_type, $allowed_pdf_types)) {
        $fssai_license = $doc_dir . uniqid('fssai_') . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . basename($fssai_license))) {
            $errors[] = 'Failed to upload FSSAI license';
        }
    } else {
        $errors[] = 'FSSAI license must be a PDF';
    }
} else {
    $errors[] = 'FSSAI license is required';
}

$aadhar_card = '';
if (isset($_FILES['aadhar_card']) && $_FILES['aadhar_card']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['aadhar_card'];
    $file_type = mime_content_type($file['tmp_name']);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($file_type, $allowed_pdf_types)) {
        $aadhar_card = $doc_dir . uniqid('aadhar_') . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . basename($aadhar_card))) {
            $errors[] = 'Failed to upload Aadhar card';
        }
    } else {
        $errors[] = 'Aadhar card must be a PDF';
    }
} else {
    $errors[] = 'Aadhar card is required';
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert into database
$sql = "INSERT INTO restaurant_owner_requests (
    name, email, phone, restaurant_photo, fssai_license, aadhar_card, address, city, latitude, longitude, password, category_id, status, request_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssi",
    $name,
    $email,
    $phone,
    $restaurant_photo,
    $fssai_license,
    $aadhar_card,
    $address,
    $city,
    $latitude,
    $longitude,
    $hashed_password,
    $category_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Request submitted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit request: ' . mysqli_error($db)]);
}

mysqli_stmt_close($stmt);
mysqli_close($db);
?>