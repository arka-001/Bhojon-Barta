<?php
// footer_settings.php

// **** ENSURE THIS PATH IS CORRECT ****
// It should point to the file containing your $db connection
include("../connection/connect.php");
session_start();

// Check if admin is logged in
if (empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit;
}

// Verify database connection
if (!$db) {
    die("Database connection failed. Please check connection settings.");
}

// Function to update footer settings
function updateFooterSettings($db, $paymentOptions, $address, $phone, $additionalInfo) {
    $stmt = mysqli_prepare($db, "UPDATE footer_settings SET
                                    payment_options = ?,
                                    address = ?,
                                    phone = ?,
                                    additional_info = ?
                                WHERE id = 1");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, "ssss", $paymentOptions, $address, $phone, $additionalInfo);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Function to get footer settings
function getFooterSettings($db) {
    $sql = "SELECT payment_options, address, phone, additional_info FROM footer_settings WHERE id = 1";
    $result = mysqli_query($db, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } elseif ($result && mysqli_num_rows($result) == 0) {
        $insert_sql = "INSERT IGNORE INTO footer_settings (id, payment_options, address, phone, additional_info) VALUES (1, '', '', '', '')";
        $insert_result = mysqli_query($db, $insert_sql);
        if (!$insert_result) {
            return null;
        }
        $result = mysqli_query($db, "SELECT payment_options, address, phone, additional_info FROM footer_settings WHERE id = 1");
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        } else {
            return null;
        }
    } else {
        return null;
    }
}

$update_status = ''; // To store 'success' or 'error' after POST
$initial_load_error = false;

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paymentOptions = $_POST['payment_options'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $additionalInfo = $_POST['additional_info'] ?? '';

    $updateResult = updateFooterSettings($db, $paymentOptions, $address, $phone, $additionalInfo);

    if ($updateResult) {
        $update_status = 'success';
    } else {
        $update_status = 'error';
    }
}

// Load footer settings for display
$footerSettings = getFooterSettings($db);

if (!$footerSettings && $update_status !== 'error') {
    $initial_load_error = true;
    $paymentOptions = '';
    $address = '';
    $phone = '';
    $additionalInfo = '';
} elseif ($footerSettings) {
    $paymentOptions = $footerSettings['payment_options'] ?? '';
    $address = $footerSettings['address'] ?? '';
    $phone = $footerSettings['phone'] ?? '';
    $additionalInfo = $footerSettings['additional_info'] ?? '';
} else {
    $paymentOptions = $_POST['payment_options'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $additionalInfo = $_POST['additional_info'] ?? '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Settings</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { padding: 20px; }
        .card { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="page-wrapper" style="margin-left: 0;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Footer Settings</h4>
                            <h6 class="card-subtitle">Manage website footer information</h6>

                            <?php if ($initial_load_error): ?>
                                <div class='alert alert-danger'>Error loading current footer settings. Default values shown.</div>
                            <?php endif; ?>

                            <form id="footerSettingsForm" method="post" action="footer_settings.php">
                                <div class="form-group">
                                    <label for="payment_options">Payment Options Description:</label>
                                    <textarea class="form-control" id="payment_options" name="payment_options" rows="3"><?php echo htmlspecialchars($paymentOptions); ?></textarea>
                                    <small class="form-text text-muted">Describe the payment methods accepted (e.g., Cash on Delivery, Visa, Mastercard).</small>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address:</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                                    <small class="form-text text-muted">Enter the main contact address.</small>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone:</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                    <small class="form-text text-muted">Enter the main contact phone number.</small>
                                </div>

                                <div class="form-group">
                                    <label for="additional_info">Additional Info:</label>
                                    <textarea class="form-control" id="additional_info" name="additional_info" rows="3"><?php echo htmlspecialchars($additionalInfo); ?></textarea>
                                    <small class="form-text text-muted">Any other relevant footer information (e.g., social media links description, short bio).</small>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="confirmSave()">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer text-center">
            © <?php echo date("Y"); ?> Admin Panel - Footer Settings
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3",
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- SweetAlert Scripts -->
    <script>
        // Function to show SweetAlert2 confirmation before form submission
        function confirmSave() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to save changes to the footer settings?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Save',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('footerSettingsForm').submit();
                }
            });
        }

        // SweetAlert for update status
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($update_status)): ?>
                <?php if ($update_status === 'success'): ?>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Footer settings updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        timer: 2000,
                        timerProgressBar: true
                    });
                <?php elseif ($update_status === 'error'): ?>
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to update footer settings. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>