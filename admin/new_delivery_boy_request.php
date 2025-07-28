<?php
include("../connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// PHPMailer Inclusion
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Define log paths
$log_dir = dirname(__FILE__) . '/logs/';
$debug_log = $log_dir . 'debug.log';
$db_errors_log = $log_dir . 'db_errors.log';
$email_errors_log = $log_dir . 'email_errors.log';

// Ensure log directory exists
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Created logs directory\n", FILE_APPEND);
}

// Log execution start
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - new_delivery_boy_request.php started, Session ID: " . session_id() . ", Request Method: {$_SERVER['REQUEST_METHOD']}\n", FILE_APPEND);

// Check session
if (empty($_SESSION["adm_id"])) {
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Session adm_id missing\n", FILE_APPEND);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit;
    } else {
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Redirecting to index.php\n", FILE_APPEND);
        header('location:index.php');
        exit;
    }
}

file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Admin ID: " . $_SESSION["adm_id"] . "\n", FILE_APPEND);

// Handle AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $admin_comment = !empty($_POST['admin_comment']) ? mysqli_real_escape_string($db, trim($_POST['admin_comment'])) : NULL;

    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Processing request ID: $request_id, Action: $action\n", FILE_APPEND);

    // Begin Transaction
    mysqli_begin_transaction($db);

    $response = ['success' => false, 'message' => ''];

    try {
        if ($action === 'approve') {
            // 1. Fetch the request details
            $sql_fetch = "SELECT * FROM delivery_boy_requests WHERE request_id = ? AND status = 'pending'";
            $stmt_fetch = mysqli_prepare($db, $sql_fetch);
            if (!$stmt_fetch) {
                $error = "Prepare failed: " . mysqli_error($db);
                file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Fetch Prepare Error: $error\n", FILE_APPEND);
                throw new Exception($error);
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
                    file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Validation Error: Missing required fields\n", FILE_APPEND);
                    mysqli_rollback($db);
                    throw new Exception($response['message']);
                }

                // Set default values for latitude and longitude if NULL
                $latitude = is_null($row['latitude']) ? 0.00000000 : $row['latitude'];
                $longitude = is_null($row['longitude']) ? 0.00000000 : $row['longitude'];

                // Validate file paths
                foreach (['db_photo', 'driving_license_photo', 'aadhaar_pdf'] as $file_field) {
                    if (!empty($row[$file_field]) && !file_exists("../" . $row[$file_field])) {
                        $response['message'] = "File not found: " . $row[$file_field];
                        file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - File Error: $file_field not found\n", FILE_APPEND);
                        mysqli_rollback($db);
                        throw new Exception($response['message']);
                    }
                }

                // Ensure password is hashed (if not already)
                $password = $row['db_password'];
                if (!preg_match('/^\$2y\$10\$/', $password)) {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Password hashed for request ID: $request_id\n", FILE_APPEND);
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
                    $error = "Prepare failed: " . mysqli_error($db);
                    file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Insert Prepare Error: $error\n", FILE_APPEND);
                    throw new Exception($error);
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
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Inserted delivery boy ID: $new_db_id\n", FILE_APPEND);

                    // 3. Update the request status
                    $sql_update = "UPDATE delivery_boy_requests SET status = 'approved', admin_comment = ? WHERE request_id = ?";
                    $stmt_update = mysqli_prepare($db, $sql_update);
                    if (!$stmt_update) {
                        $error = "Prepare failed: " . mysqli_error($db);
                        file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Update Prepare Error: $error\n", FILE_APPEND);
                        throw new Exception($error);
                    }
                    mysqli_stmt_bind_param($stmt_update, "si", $admin_comment, $request_id);

                    if (mysqli_stmt_execute($stmt_update)) {
                        mysqli_stmt_close($stmt_update);
                        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Updated request status to approved\n", FILE_APPEND);

                        // 4. Send Approval Email
                        if (!empty($row['db_email']) && filter_var($row['db_email'], FILTER_VALIDATE_EMAIL)) {
                            $mail = new PHPMailer(true);
                            try {
                                $mail->SMTPDebug = 0; // Set to SMTP::DEBUG_SERVER for debugging
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = 'bhojonbarta@gmail.com';
                                $mail->Password = 'zyys vops vyua zetu';
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
                                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Email sent to: " . $row['db_email'] . "\n", FILE_APPEND);
                            } catch (Exception $e) {
                                $response['success'] = true;
                                $response['message'] = "Delivery boy request approved successfully, but failed to send confirmation email.";
                                file_put_contents($email_errors_log, date('Y-m-d H:i:s') . " - Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
                            }
                        } else {
                            $response['success'] = true;
                            $response['message'] = "Delivery boy request approved successfully, but no valid email provided.";
                            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - No valid email for request ID: $request_id\n", FILE_APPEND);
                        }

                        mysqli_commit($db);
                    } else {
                        $response['message'] = "Failed to update request status: " . mysqli_stmt_error($stmt_update);
                        file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Update Error: " . mysqli_stmt_error($stmt_update) . "\n", FILE_APPEND);
                        mysqli_rollback($db);
                        throw new Exception($response['message']);
                    }
                } else {
                    $response['message'] = "Failed to create delivery boy record: " . mysqli_stmt_error($stmt_insert);
                    file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Insert Error: " . mysqli_stmt_error($stmt_insert) . "\n", FILE_APPEND);
                    mysqli_rollback($db);
                    throw new Exception($response['message']);
                }
            } else {
                $response['message'] = "Request not found or already processed.";
                file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Request not found: $request_id\n", FILE_APPEND);
                mysqli_rollback($db);
                throw new Exception($response['message']);
            }
        } elseif ($action === 'reject') {
            // Update the request status to rejected
            $sql_update = "UPDATE delivery_boy_requests SET status = 'rejected', admin_comment = ? WHERE request_id = ? AND status = 'pending'";
            $stmt_update = mysqli_prepare($db, $sql_update);
            if (!$stmt_update) {
                $error = "Prepare failed: " . mysqli_error($db);
                file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Reject Prepare Error: $error\n", FILE_APPEND);
                throw new Exception($error);
            }
            mysqli_stmt_bind_param($stmt_update, "si", $admin_comment, $request_id);

            if (mysqli_stmt_execute($stmt_update)) {
                if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                    $response['success'] = true;
                    $response['message'] = "Delivery boy request rejected successfully!";
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Rejected request ID: $request_id\n", FILE_APPEND);
                    mysqli_commit($db);
                } else {
                    $response['message'] = "Request not found or already processed.";
                    file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Reject Error: Request not found or processed\n", FILE_APPEND);
                    mysqli_rollback($db);
                    throw new Exception($response['message']);
                }
            } else {
                $response['message'] = "Failed to reject request: " . mysqli_stmt_error($stmt_update);
                file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Reject Error: " . mysqli_stmt_error($stmt_update) . "\n", FILE_APPEND);
                mysqli_rollback($db);
                throw new Exception($response['message']);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $response['message'] = "Invalid action specified.";
            file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Invalid action: $action\n", FILE_APPEND);
            mysqli_rollback($db);
            throw new Exception($response['message']);
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch pending requests for display
$sql_fetch_pending = "SELECT * FROM delivery_boy_requests WHERE status = 'pending' ORDER BY request_date DESC";
$result_pending = mysqli_query($db, $sql_fetch_pending);
if (!$result_pending) {
    file_put_contents($db_errors_log, date('Y-m-d H:i:s') . " - Query Error: " . mysqli_error($db) . "\n", FILE_APPEND);
}
?>

<div class="container-fluid">
    <div class="card card-outline-primary">
        <div class="card-header">
            <h4 class="m-b-0 text-white">New Delivery Boy Requests</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive m-t-40">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Profile Photo</th>
                            <th>Bank Account</th>
                            <th>IFSC Code</th>
                            <th>Account Holder</th>
                            <th>License Number</th>
                            <th>License Expiry</th>
                            <th>License Photo</th>
                            <th>Aadhaar PDF</th>
                            <th>Location</th>
                            <th>Request Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result_pending) > 0) {
                            while ($row = mysqli_fetch_assoc($result_pending)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['db_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['db_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['db_email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['db_address']); ?></td>
                                    <td><?php echo htmlspecialchars($row['city']); ?></td>
                                    <td>
                                        <?php if ($row['db_photo'] && file_exists("../" . $row['db_photo'])) { ?>
                                            <img src="../<?php echo htmlspecialchars($row['db_photo']); ?>" alt="Profile" class="action-img" data-img-src="../<?php echo htmlspecialchars($row['db_photo']); ?>">
                                        <?php } else { ?>
                                            No Photo
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['bank_account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ifsc_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['account_holder_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['driving_license_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['driving_license_expiry']); ?></td>
                                    <td>
                                        <?php if ($row['driving_license_photo'] && file_exists("../" . $row['driving_license_photo'])) { ?>
                                            <img src="../<?php echo htmlspecialchars($row['driving_license_photo']); ?>" alt="License" class="action-img" data-img-src="../<?php echo htmlspecialchars($row['driving_license_photo']); ?>">
                                        <?php } else { ?>
                                            No Photo
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($row['aadhaar_pdf'] && file_exists("../" . $row['aadhaar_pdf'])) { ?>
                                            <a href="../<?php echo htmlspecialchars($row['aadhaar_pdf']); ?>" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-file-pdf"></i> View</a>
                                        <?php } else { ?>
                                            No PDF
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="https://www.google.com/maps?q=<?php echo htmlspecialchars($row['latitude']); ?>,<?php echo htmlspecialchars($row['longitude']); ?>" target="_blank">View Map</a>
                                    </td>
                                    <td><?php echo date("Y-m-d H:i", strtotime($row['request_date'])); ?></td>
                                    <td>
                                        <form class="action-form">
                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                            <div class="form-group">
                                                <textarea name="admin_comment" class="form-control form-control-sm" placeholder="Optional comment"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm action-btn" data-action="approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm action-btn" data-action="reject">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                            <input type="hidden" name="action" value="">
                                        </form>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else { ?>
                            <tr><td colspan="16"><center>No Pending Requests Found.</center></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageModal" class="modal">
    <span class="close-modal">×</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="Popup Image">
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    table th, table td {
        white-space: nowrap;
    }
    .action-img {
        width: 50px;
        height: auto;
        transition: transform 0.2s ease;
        cursor: pointer;
    }
    .action-img:hover {
        transform: scale(1.5);
    }
    .form-group {
        margin-bottom: 5px;
    }
    .btn-sm {
        margin-top: 5px;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
    }
    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .modal-content img {
        width: 100%;
        height: auto;
    }
    .close-modal {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
    }
    .close-modal:hover,
    .close-modal:focus {
        color: #bbb;
        text-decoration: none;
        cursor: pointer;
    }
</style>

<script src="js/lib/jquery/jquery.min.js"></script>
<script src="js/lib/bootstrap/js/popper.min.js"></script>
<script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    $(document).ready(function() {
        $('.action-btn').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            var action = $(this).data('action');
            var actionText = action.charAt(0).toUpperCase() + action.slice(1);

            // Set the action value
            form.find('input[name="action"]').val(action);

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you really want to ${action} this request?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Yes, ${actionText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form via AJAX
                    $.ajax({
                        url: 'new_delivery_boy_request.php', // Direct URL
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        success: function(response) {
                            Swal.fire({
                                title: response.success ? 'Success!' : 'Error!',
                                text: response.message,
                                icon: response.success ? 'success' : 'error',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                if (response.success) {
                                    // Remove the row from the table
                                    form.closest('tr').remove();
                                    // Check if table is empty
                                    if ($('tbody tr').length === 0) {
                                        $('tbody').append('<tr><td colspan="16"><center>No Pending Requests Found.</center></td></tr>');
                                    }
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error, xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while processing the request: ' + (xhr.responseText || error),
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });

        // Image modal handling
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("modalImage");
        var span = document.getElementsByClassName("close-modal")[0];

        $('.action-img').on('click', function() {
            modal.style.display = "block";
            modalImg.src = $(this).data('img-src');
        });

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
</script>
<?php mysqli_close($db); ?>