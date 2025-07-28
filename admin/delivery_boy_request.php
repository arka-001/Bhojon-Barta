<?php
include "../connection/connect.php"; // Adjusted path relative to api/delivery_boy_request.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// PHPMailer Inclusion
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

// Log execution start
file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - api/delivery_boy_request.php started, Session ID: " . session_id() . "\n", FILE_APPEND);

// Check session
if (empty($_SESSION["adm_id"])) {
    file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Session adm_id missing\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Admin ID: " . $_SESSION["adm_id"] . "\n", FILE_APPEND);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Invalid request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_POST['action']) || !isset($_POST['request_id'])) {
    file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Missing action or request_id\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$request_id = intval($_POST['request_id']);
$action = $_POST['action'];
$admin_comment = !empty($_POST['admin_comment']) ? mysqli_real_escape_string($db, trim($_POST['admin_comment'])) : NULL;

file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Processing request ID: $request_id, Action: $action\n", FILE_APPEND);

// Begin Transaction
mysqli_begin_transaction($db);

$response = ['success' => false, 'message' => ''];

try {
    if ($action === 'approve') {
        // 1. Fetch the request details
        $sql_fetch = "SELECT * FROM delivery_boy_requests WHERE request_id = ? AND status = 'pending'";
        $stmt_fetch = mysqli_prepare($db, $sql_fetch);
        if (!$stmt_fetch) {
            throw new Exception("Prepare failed: " . mysqli_error($db));
        }
        mysqli_stmt_bind_param($stmt_fetch, "i", $request_id);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        $row = mysqli_fetch_assoc($result_fetch);
        mysqli_stmt_close($stmt_fetch);

        if ($row) {
            // Validate required fields
            if (empty($row['db_name']) || empty($row['db_phone']) || empty($row['db_password'])) {
                $response['message'] = "Required fields (name, phone, or password) are missing.";
                file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Validation Error: Missing required fields\n", FILE_APPEND);
                mysqli_rollback($db);
                throw new Exception($response['message']);
            }

            // Set default values for latitude and longitude if NULL
            $latitude = is_null($row['latitude']) ? 0.00000000 : $row['latitude'];
            $longitude = is_null($row['longitude']) ? 0.00000000 : $row['longitude'];

            // Validate file paths
            foreach (['db_photo', 'driving_license_photo', 'aadhaar_pdf'] as $file_field) {
                if (!empty($row[$file_field]) && !file_exists("../../" . $row[$file_field])) {
                    $response['message'] = "File not found: " . $row[$file_field];
                    file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - File Error: $file_field not found\n", FILE_APPEND);
                    mysqli_rollback($db);
                    throw new Exception($response['message']);
                }
            }

            // Ensure password is hashed (if not already)
            $password = $row['db_password'];
            if (!preg_match('/^\$2y\$10\$/', $password)) {
                $password = password_hash($password, PASSWORD_DEFAULT);
                file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Password hashed for request ID: $request_id\n", FILE_APPEND);
            }

            // 2. Insert into delivery_boy table
            $sql_insert = "INSERT INTO delivery_boy (
                db_name, db_phone, db_email, db_address, city, db_photo, db_password, latitude, longitude,
                bank_account_number, ifsc_code, account_holder_name,
                driving_license_number, driving_license_expiry, driving_license_photo, aadhaar_pdf,
                created_at, updated_at, db_status, current_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 1, 'available')";

            $stmt_insert = mysqli_prepare($db, $sql_insert);
            if (!$stmt_insert) {
                throw new Exception("Prepare failed: " . mysqli_error($db));
            }
            mysqli_stmt_bind_param(
                $stmt_insert,
                "ssssssdsssssssss",
                $row['db_name'],
                $row['db_phone'],
                $row['db_email'],
                $row['db_address'],
                $row['city'],
                $row['db_photo'],
                $password,
                $latitude,
                $longitude,
                $row['bank_account_number'],
                $row['ifsc_code'],
                $row['account_holder_name'],
                $row['driving_license_number'],
                $row['driving_license_expiry'],
                $row['driving_license_photo'],
                $row['aadhaar_pdf']
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                $new_db_id = mysqli_insert_id($db);
                mysqli_stmt_close($stmt_insert);
                file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Inserted delivery boy ID: $new_db_id\n", FILE_APPEND);

                // 3. Update the request status
                $sql_update = "UPDATE delivery_boy_requests SET status = 'approved', admin_comment = ? WHERE request_id = ?";
                $stmt_update = mysqli_prepare($db, $sql_update);
                if (!$stmt_update) {
                    throw new Exception("Prepare failed: " . mysqli_error($db));
                }
                mysqli_stmt_bind_param($stmt_update, "si", $admin_comment, $request_id);

                if (mysqli_stmt_execute($stmt_update)) {
                    mysqli_stmt_close($stmt_update);
                    file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Updated request status to approved\n", FILE_APPEND);

                    // 4. Send Approval Email
                    if (!empty($row['db_email']) && filter_var($row['db_email'], FILTER_VALIDATE_EMAIL)) {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->SMTPDebug = 0; // Set to SMTP::DEBUG_SERVER for debugging
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'bhojonbarta@gmail.com'; // Replace with your SMTP email
                            $mail->Password = 'zyys vops vyua zetu'; // Replace with your App Password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port = 465;

                            $mail->setFrom('bhojonbarta@gmail.com', 'Your Food Delivery Service');
                            $mail->addAddress($row['db_email'], $row['db_name']);
                            $mail->isHTML(true);
                            $mail->Subject = 'Your Delivery Boy Application Approved!';
                            $mail->Body = "Dear " . htmlspecialchars($row['db_name']) . ",<br><br>" .
                                          "Congratulations! Your application to become a delivery partner has been approved.<br><br>" .
                                          "You can now log in using your email: <strong>" . htmlspecialchars($row['db_email']) . "</strong><br><br>" .
                                          "Use the password you created during registration. If forgotten, use the 'Forgot Password' option.<br><br>" .
                                          "Best Regards,<br>Your Food Delivery Service Team";
                            $mail->AltBody = "Dear " . htmlspecialchars($row['db_name']) . ",\n\n" .
                                             "Congratulations! Your application to become a delivery partner has been approved.\n\n" .
                                             "You can now log in using your email: " . htmlspecialchars($row['db_email']) . "\n\n" .
                                             "Use the password you created during registration. If forgotten, use the 'Forgot Password' option.\n\n" .
                                             "Best Regards,\nYour Food Delivery Service Team";

                            $mail->send();
                            $response['success'] = true;
                            $response['message'] = "Delivery boy request approved successfully! Confirmation email sent.";
                            file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Email sent to: " . $row['db_email'] . "\n", FILE_APPEND);
                        } catch (Exception $e) {
                            $response['success'] = true; // Still consider success since DB operations succeeded
                            $response['message'] = "Delivery boy request approved successfully, but failed to send confirmation email.";
                            file_put_contents('../logs/email_errors.log', date('Y-m-d H:i:s') . " - Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
                        }
                    } else {
                        $response['success'] = true;
                        $response['message'] = "Delivery boy request approved successfully, but no valid email provided.";
                        file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - No valid email for request ID: $request_id\n", FILE_APPEND);
                    }

                    mysqli_commit($db);
                } else {
                    $response['message'] = "Failed to update request status: " . mysqli_stmt_error($stmt_update);
                    file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Update Error: " . mysqli_stmt_error($stmt_update) . "\n", FILE_APPEND);
                    mysqli_rollback($db);
                    throw new Exception($response['message']);
                }
            } else {
                $response['message'] = "Failed to create delivery boy record: " . mysqli_stmt_error($stmt_insert);
                file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Insert Error: " . mysqli_stmt_error($stmt_insert) . "\n", FILE_APPEND);
                mysqli_rollback($db);
                throw new Exception($response['message']);
            }
        } else {
            $response['message'] = "Request not found or already processed.";
            file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Request not found: $request_id\n", FILE_APPEND);
            mysqli_rollback($db);
            throw new Exception($response['message']);
        }
    } elseif ($action === 'reject') {
        // Update the request status to rejected
        $sql_update = "UPDATE delivery_boy_requests SET status = 'rejected', admin_comment = ? WHERE request_id = ? AND status = 'pending'";
        $stmt_update = mysqli_prepare($db, $sql_update);
        if (!$stmt_update) {
            throw new Exception("Prepare failed: " . mysqli_error($db));
        }
        mysqli_stmt_bind_param($stmt_update, "si", $admin_comment, $request_id);

        if (mysqli_stmt_execute($stmt_update)) {
            if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                $response['success'] = true;
                $response['message'] = "Delivery boy request rejected successfully!";
                file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Rejected request ID: $request_id\n", FILE_APPEND);
                mysqli_commit($db);
            } else {
                $response['message'] = "Request not found or already processed.";
                file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Reject Error: Request not found or processed\n", FILE_APPEND);
                mysqli_rollback($db);
                throw new Exception($response['message']);
            }
        } else {
            $response['message'] = "Failed to reject request: " . mysqli_stmt_error($stmt_update);
            file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Reject Error: " . mysqli_stmt_error($stmt_update) . "\n", FILE_APPEND);
            mysqli_rollback($db);
            throw new Exception($response['message']);
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $response['message'] = "Invalid action specified.";
        file_put_contents('../logs/debug.log', date('Y-m-d H:i:s') . " - Invalid action: $action\n", FILE_APPEND);
        mysqli_rollback($db);
        throw new Exception($response['message']);
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    file_put_contents('../logs/db_errors.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
mysqli_close($db);
exit;
?>