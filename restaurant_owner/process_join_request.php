<?php
session_start();
// Corrected include path: Go up one level from restaurant_owner
include("../connection/connect.php");
error_reporting(E_ALL); // Dev
ini_set('display_errors', 1); // Dev

// --- Configuration ---
// IMPORTANT: VERIFY THIS BASE PATH FOR YOUR SERVER SETUP
$base_path = "C:/xampp/htdocs/OnlineFood-PHP/OnlineFood-PHP/"; // Example: CHECK THIS!
$target_dir_name = "admin/Owner_docs/"; // Relative to project root
// Path for DB Storage (Relative from the /admin/ folder, needs ../ from this script)
$target_dir_relative_for_db = $target_dir_name;
// Absolute Path for file operations (calculated from base path)
$target_dir_absolute = rtrim($base_path, '/') . '/' . $target_dir_name;
// --- End Configuration ---


// --- Function to Handle File Uploads ---
// (Use the robust handle_upload function from the previous example, ensuring it uses $target_dir_absolute for mkdir/move and returns a path based on $target_dir_name for DB)
function handle_upload($file_key, $allowed_types, $max_size_mb, $target_dir_abs, $target_dir_rel_base) {
    global $error_message; // Use the global variable

    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES[$file_key];
        $file_name = basename($file["name"]);
        $file_tmp_name = $file["tmp_name"];
        $file_size = $file["size"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_types)) {
            $error_message = "Invalid file type for '{$file_key}'. Allowed: " . implode(', ', $allowed_types);
            return null;
        }

        $max_size_bytes = $max_size_mb * 1024 * 1024;
        if ($file_size > $max_size_bytes) {
            $error_message = "File '{$file_key}' exceeds max size of {$max_size_mb}MB.";
            return null;
        }

        if (!file_exists($target_dir_abs)) {
            if (!mkdir($target_dir_abs, 0755, true)) {
                 if (!is_dir($target_dir_abs)) { // Check again after trying mkdir
                     $error_message = "Failed to create upload directory. Check permissions.";
                     error_log("Failed to create directory: " . $target_dir_abs);
                     return null;
                 }
            }
        }
        if (!is_dir($target_dir_abs) || !is_writable($target_dir_abs)) {
             $error_message = "Upload directory is not writable. Check permissions.";
             error_log("Directory not writable or not a directory: " . $target_dir_abs);
             return null;
        }

        $unique_filename = uniqid(pathinfo($file_name, PATHINFO_FILENAME) . '_', true) . '.' . $file_ext;
        $target_file_absolute = $target_dir_abs . $unique_filename;
        // Construct relative path for DB storage (e.g., "admin/Owner_docs/filename.jpg")
        $target_file_relative_for_db = rtrim($target_dir_rel_base, '/') . '/' . $unique_filename;


        if (move_uploaded_file($file_tmp_name, $target_file_absolute)) {
            return $target_file_relative_for_db; // Return relative path for DB
        } else {
            $error_message = "Failed to move uploaded file '{$file_key}'.";
             $php_upload_errors = [ /* ... error codes map ... */ ];
             $err_code = $file['error'];
             error_log("move_uploaded_file failed for {$file_key}: Target={$target_file_absolute}, Error Code={$err_code}");
            return null;
        }
    } elseif (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other PHP upload errors explicitly
         $php_upload_errors = [
            UPLOAD_ERR_INI_SIZE => "File '{$file_key}' exceeds server upload size limit.",
            UPLOAD_ERR_FORM_SIZE => "File '{$file_key}' exceeds form upload size limit.",
            UPLOAD_ERR_PARTIAL => "File '{$file_key}' was only partially uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Server configuration error: Missing temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Server error: Cannot write file '{$file_key}' to disk.",
            UPLOAD_ERR_EXTENSION => "File upload '{$file_key}' stopped by a PHP extension.",
        ];
        $err_code = $_FILES[$file_key]['error'];
        $error_message = $php_upload_errors[$err_code] ?? "Unknown upload error ({$err_code}) for file '{$file_key}'.";
        error_log("Upload error for {$file_key}: Code " . $err_code);
        return null;
    }
    return null; // No file or optional file
}
// --- End of handle_upload function ---


$error_message = null;
$form_data = $_POST; // Store for repopulating

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $db = $GLOBALS['db']; // Use global DB connection
    if (!$db) {
         $_SESSION['error_message'] = "Database connection error.";
         header("Location: join_restaurant_owner_form.php"); exit;
    }

    // --- Field Sanitization and Validation ---
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $account_holder_name = trim($_POST['account_holder_name'] ?? '');
    $bank_account_number = trim($_POST['bank_account_number'] ?? '');
    $ifsc_code = trim(strtoupper($_POST['ifsc_code'] ?? ''));

    // Required fields check
    $required_fields = compact('name', 'email', 'phone', 'password', 'category_id', 'address', 'city', 'latitude', 'longitude', 'account_holder_name', 'bank_account_number', 'ifsc_code');
    foreach ($required_fields as $field => $value) {
        if ($value === '' || $value === null || ($field === 'category_id' && !$value)) {
            $error_message = ucfirst(str_replace('_', ' ', $field)) . " is required."; break;
        }
    }
    // Specific validations
    if (!$error_message && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error_message = "Invalid email format.";
    if (!$error_message && !preg_match('/^[0-9]{10,15}$/', $phone)) $error_message = "Invalid phone number (10-15 digits).";
    if (!$error_message && strlen($password) < 6) $error_message = "Password must be at least 6 characters.";
    if (!$error_message && (!is_numeric($latitude) || $latitude == '')) $error_message = "Latitude is required (verify address)."; // Check '' too
    if (!$error_message && (!is_numeric($longitude) || $longitude == '')) $error_message = "Longitude is required (verify address).";
    if (!$error_message && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) $error_message = "Invalid IFSC code format (e.g., ABCD0123456).";
    if (!$error_message && !preg_match('/^[0-9]{5,20}$/', $bank_account_number)) $error_message = "Invalid Bank Account Number (5-20 digits).";

    // --- File Upload Handling ---
    $photo_path = null; $fssai_path = null; $aadhar_path = null;
    if (!$error_message) {
         // Pass the base *name* of the target dir for constructing the relative path for DB
        $photo_path = handle_upload('restaurant_photo', ['jpg', 'jpeg', 'png'], 2, $target_dir_absolute, $target_dir_name);
    }
    if (!$error_message) {
        $fssai_path = handle_upload('fssai_license', ['pdf'], 5, $target_dir_absolute, $target_dir_name);
    }
    if (!$error_message) {
        $aadhar_path = handle_upload('aadhar_card', ['pdf'], 5, $target_dir_absolute, $target_dir_name);
    }


    // --- City Serviceability Server-Side Re-check ---
    if (!$error_message) {
        $isServiceable = false;
        $stmt_city_check = $db->prepare("SELECT city_id FROM delivery_cities WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1");
        if ($stmt_city_check) {
            $cityNameLower = strtolower($city);
            $stmt_city_check->bind_param("s", $cityNameLower);
            $stmt_city_check->execute();
            $stmt_city_check->store_result();
            if ($stmt_city_check->num_rows > 0) $isServiceable = true;
            $stmt_city_check->close();
        } else { $error_message = "DB error: City validation failed."; error_log("Fail prepare city check: ".$db->error); }
        if (!$isServiceable && !$error_message) $error_message = "Service is not available in the provided city ({$city}). Please contact support if this seems incorrect.";
    }


    // --- Database Insertion ---
    if (!$error_message) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        if ($hashed_password === false) {
             $error_message = "Security error: Failed to process password."; error_log("password_hash() failed for: ".$email);
        } else {
            $sql = "INSERT INTO restaurant_owner_requests
                    (name, email, phone, password, restaurant_photo, fssai_license, aadhar_card, address, city, latitude, longitude, category_id,
                     bank_account_number, ifsc_code, account_holder_name, status, request_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

            $stmt = $db->prepare($sql);
            if ($stmt) {
                $lat_float = (float)$latitude; // Cast for binding
                $lon_float = (float)$longitude;
                $stmt->bind_param("sssssssssddisss",
                    $name, $email, $phone, $hashed_password,
                    $photo_path, $fssai_path, $aadhar_path,
                    $address, $city, $lat_float, $lon_float, $category_id,
                    $bank_account_number, $ifsc_code, $account_holder_name
                );

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Your request has been submitted successfully! We will review it shortly.";
                    unset($_SESSION['form_data']);
                    header("Location: join_restaurant_owner_form.php");
                    exit();
                } else {
                    if ($db->errno == 1062) { $error_message = "An account request with this email already exists."; }
                    else { $error_message = "DB error: Failed to submit request."; error_log("DB Execute Error process_join_request: " . $stmt->error . " - Email: " . $email); }
                }
                $stmt->close();
            } else {
                $error_message = "DB error: Could not prepare request."; error_log("DB Prepare Error process_join_request: " . $db->error);
            }
        }
    }

    // --- Error Handling & Redirection ---
    if ($error_message) {
        $_SESSION['error_message'] = $error_message;
        unset($form_data['password']); // Don't send password back
        $_SESSION['form_data'] = $form_data; // Keep other data
        header("Location: join_restaurant_owner_form.php");
        exit();
    }

} else {
    header("Location: join_restaurant_owner_form.php");
    exit();
}
?>