<?php
// footer.php

// Include the database connection
include("connection/connect.php"); // Adjust path if needed

// Function to get footer settings from the database
function getFooterSettings($db) {
    $sql = "SELECT payment_options, address, phone, additional_info FROM footer_settings WHERE id = 1";
    $result = mysqli_query($db, $sql);

    if (!$result) {
        error_log("MySQL error in getFooterSettings: " . mysqli_error($db));
        return null;
    }

    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Get the footer settings
$footerSettings = getFooterSettings($db);

// Check if footer settings were retrieved successfully
if (!$footerSettings) {
    error_log("Failed to retrieve footer settings: " . mysqli_error($db));
    echo "<p>Sorry, footer information is currently unavailable.</p>";
    return;
}

// Assign values to variables with defaults
$paymentOptions = $footerSettings['payment_options'] ?? '';
$address = $footerSettings['address'] ?? '';
$phone = $footerSettings['phone'] ?? '';
$additionalInfo = $footerSettings['additional_info'] ?? '';
?>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Payment Options</h5>
                <p><?php echo nl2br(htmlspecialchars($paymentOptions)); ?></p>
            </div>
            <div class="col-md-4">
                <h5>Address</h5>
                <p><?php echo nl2br(htmlspecialchars($address)); ?></p>
                <h5>Phone: <?php echo htmlspecialchars($phone); ?></h5>
            </div>
            <div class="col-md-4">
                <h5>Additional Information</h5>
                <p><?php echo nl2br(htmlspecialchars($additionalInfo)); ?></p>
            </div>
        </div>
        <hr style="border-color: rgba(255, 255, 255, 0.2);">
        <div class="row">
            <div class="col-md-12 text-center">
                <p>© <?php echo date("Y"); ?> Food Delivery. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>