<?php
// Include database connection
include_once("../connection/connect.php");

// Enable error reporting (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // SET TO 0 IN PRODUCTION
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (empty($_SESSION["adm_id"])) {
    error_log("Admin not logged in, rejecting request in handle_restaurant_requests.php");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Define image paths
$basePath = $_SERVER['DOCUMENT_ROOT'] . "/OnlineFood-PHP/OnlineFood-PHP/";
$sourceImageDir = $basePath . "admin/Res_img/"; // Where pending images are stored
$destImageDir = $basePath . "Res_img/"; // Where approved images should be stored

// Handle AJAX Approve Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    header('Content-Type: application/json');
    error_log("Received AJAX approve request: " . json_encode($_POST));

    if (!isset($_POST['request_id']) || !filter_var($_POST['request_id'], FILTER_VALIDATE_INT)) {
        error_log("Approve action failed: Invalid request ID - " . ($_POST['request_id'] ?? 'unset'));
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        exit();
    }
    $request_id = (int)$_POST['request_id'];

    if (!isset($db) || $db === null || $db->connect_error) {
        error_log("Database connection failed in handle_restaurant_requests.php (Approve AJAX)");
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    $db->begin_transaction();
    $stmt_check = null;
    $stmt_owner = null;
    $stmt_insert = null;
    $stmt_update = null;

    try {
        // 1. Fetch request details
        $stmt_check = $db->prepare("SELECT owner_id, c_id, title, email, phone, url, o_hr, c_hr, o_days, address, city, diet_type, image, fssai_license, latitude, longitude 
                                   FROM restaurant_requests 
                                   WHERE request_id = ? AND status = 'pending' FOR UPDATE");
        if (!$stmt_check) throw new Exception("Prepare failed (check): " . $db->error);
        $stmt_check->bind_param("i", $request_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) throw new Exception("Invalid or already processed request (ID: $request_id).");
        $row = $result_check->fetch_assoc();
        $stmt_check->close();

        // 2. Handle image
        $image_filename = !empty($row['image']) && $row['image'] !== '0' ? basename($row['image']) : null;
        if ($image_filename) {
            $source_image_path = $sourceImageDir . $image_filename;
            $dest_image_path = $destImageDir . $image_filename;
            if (file_exists($source_image_path) && is_file($source_image_path)) {
                // Move image to Res_img/
                if (!copy($source_image_path, $dest_image_path)) {
                    throw new Exception("Failed to copy image from $source_image_path to $dest_image_path.");
                }
                error_log("Image copied from $source_image_path to $dest_image_path for request ID $request_id");
                // Optionally, delete the source image after copying
                // unlink($source_image_path);
            } else {
                error_log("Image file not found: $source_image_path for request ID $request_id");
                $image_filename = null; // Set to null if image is missing
            }
        } else {
            error_log("Invalid or missing image for request ID $request_id: " . ($row['image'] ?? 'unset'));
            $image_filename = null;
        }

        // 3. Fetch bank details from restaurant_owner_requests
        $bank_account_number = null;
        $ifsc_code = null;
        $account_holder_name = null;
        $stmt_owner = $db->prepare("SELECT bank_account_number, ifsc_code, account_holder_name 
                                   FROM restaurant_owner_requests 
                                   WHERE email = ? LIMIT 1");
        if (!$stmt_owner) throw new Exception("Prepare failed (owner check): " . $db->error);
        $stmt_owner->bind_param("s", $row['email']);
        $stmt_owner->execute();
        $result_owner = $stmt_owner->get_result();
        if ($result_owner->num_rows > 0) {
            $owner_data = $result_owner->fetch_assoc();
            $bank_account_number = $owner_data['bank_account_number'];
            $ifsc_code = $owner_data['ifsc_code'];
            $account_holder_name = $owner_data['account_holder_name'];
            error_log("Bank details found for email {$row['email']}: " . json_encode($owner_data));
        } else {
            error_log("No bank details found for email: " . $row['email']);
        }
        $stmt_owner->close();

        // 4. Insert into restaurant table with bank details and diet_type
        $stmt_insert = $db->prepare("INSERT INTO restaurant (owner_id, c_id, title, email, phone, url, o_hr, c_hr, o_days, address, city, diet_type, image, latitude, longitude, fssai_license, bank_account_number, ifsc_code, account_holder_name, date, is_open) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)");
        if (!$stmt_insert) throw new Exception("Insert prepare failed: " . $db->error);
        $stmt_insert->bind_param(
            "iisssssssssssdsssss",
            $row['owner_id'],
            $row['c_id'],
            $row['title'],
            $row['email'],
            $row['phone'],
            $row['url'],
            $row['o_hr'],
            $row['c_hr'],
            $row['o_days'],
            $row['address'],
            $row['city'],
            $row['diet_type'],
            $image_filename,
            $row['latitude'],
            $row['longitude'],
            $row['fssai_license'],
            $bank_account_number,
            $ifsc_code,
            $account_holder_name
        );
        if (!$stmt_insert->execute()) throw new Exception("Error inserting restaurant: " . $stmt_insert->error);
        $stmt_insert->close();

        // 5. Update request status
        $stmt_update = $db->prepare("UPDATE restaurant_requests SET status = 'approved' WHERE request_id = ?");
        if (!$stmt_update) throw new Exception("Update prepare failed: " . $db->error);
        $stmt_update->bind_param("i", $request_id);
        if (!$stmt_update->execute()) throw new Exception("Error updating request status: " . $stmt_update->error);
        $stmt_update->close();

        $db->commit();
        error_log("Successfully approved request ID $request_id with image: " . ($image_filename ?? 'none'));
        echo json_encode(['success' => true, 'message' => 'Restaurant approved successfully.']);
        exit();

    } catch (Exception $e) {
        $db->rollback();
        error_log("Transaction failed for approving request ID $request_id: " . $e->getMessage());
        if ($stmt_check) $stmt_check->close();
        if ($stmt_owner) $stmt_owner->close();
        if ($stmt_insert) $stmt_insert->close();
        if ($stmt_update) $stmt_update->close();
        echo json_encode(['success' => false, 'message' => 'Failed to approve restaurant: ' . $e->getMessage()]);
        exit();
    }
}

// Handle AJAX Reject Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    header('Content-Type: application/json');
    error_log("Received AJAX reject request: " . json_encode($_POST));

    if (!isset($_POST['request_id']) || !filter_var($_POST['request_id'], FILTER_VALIDATE_INT)) {
        error_log("Reject action failed: Invalid request ID - " . ($_POST['request_id'] ?? 'unset'));
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        exit();
    }
    $request_id = (int)$_POST['request_id'];

    if (!isset($db) || $db === null || $db->connect_error) {
        error_log("Database connection failed in handle_restaurant_requests.php (Reject AJAX)");
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    $stmt_reject = $db->prepare("UPDATE restaurant_requests SET status = 'rejected' WHERE request_id = ? AND status = 'pending'");
    if (!$stmt_reject) {
        error_log("Reject prepare failed: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare reject query.']);
        exit();
    }
    $stmt_reject->bind_param("i", $request_id);

    if ($stmt_reject->execute()) {
        $affected_rows = $stmt_reject->affected_rows;
        $stmt_reject->close();
        error_log("Reject request ID $request_id: " . ($affected_rows > 0 ? 'Success' : 'No changes'));
        echo json_encode([
            'success' => true,
            'message' => $affected_rows > 0 ? 'Restaurant request rejected successfully.' : 'Request was already processed or invalid.'
        ]);
        exit();
    } else {
        error_log("Error rejecting request (ID: $request_id): " . $stmt_reject->error);
        $stmt_reject->close();
        echo json_encode(['success' => false, 'message' => 'Failed to reject restaurant.']);
        exit();
    }
}

// Invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit();
?>