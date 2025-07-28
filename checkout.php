<?php
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'C:/xampp/htdocs/OnlineFood-PHP/OnlineFood-PHP/php_errors.log');

include("connection/connect.php");
include_once 'product-action.php';

$success = "";
$coupon_message = "";
$delivery_charge = 0.00;
$delivery_error = "";

if (empty($_SESSION["user_id"])) {
    header('location:login.php');
    exit;
} else {
    $user_id = $_SESSION["user_id"];

    $stmt = $db->prepare("SELECT * FROM users WHERE u_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        error_log("User not found for u_id: $user_id");
        echo "Error: User not found.";
        exit;
    }

    $item_total = 0;
    $cart_items = [];
    $discount = 0;
    $grand_total = 0;
    $temp_item_total = 0;

    // Fetch cart items with restaurant names
    $cartStmt = $db->prepare("
        SELECT c.d_id, c.res_id, c.quantity, c.price, d.title, r.title AS restaurant_name
        FROM cart c 
        JOIN dishes d ON c.d_id = d.d_id 
        JOIN restaurant r ON c.res_id = r.rs_id
        WHERE c.u_id = ?
    ");
    $cartStmt->bind_param("i", $user_id);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();

    while ($item = $cartResult->fetch_assoc()) {
        $cart_items[$item['res_id']][$item['d_id']] = [
            'title' => $item['title'],
            'd_id' => $item['d_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'res_id' => $item['res_id'],
            'restaurant_name' => $item['restaurant_name']
        ];
    }
    $cartStmt->close();

    function getFreeDeliveryThreshold(mysqli $db): float {
        $sql = "SELECT setting_value FROM settings WHERE setting_name = 'free_delivery_threshold'";
        $threshold_query = mysqli_query($db, $sql);
        if ($threshold_query && mysqli_num_rows($threshold_query) > 0) {
            $threshold_row = mysqli_fetch_assoc($threshold_query);
            return floatval($threshold_row['setting_value']);
        }
        return 1000.0;
    }

    function getDeliveryCharge(mysqli $db, float $order_total): float {
        $free_delivery_threshold = getFreeDeliveryThreshold($db);
        if ($order_total >= $free_delivery_threshold) {
            return 0.00;
        } else {
            $sql = "SELECT delivery_charge FROM delivary_charges ORDER BY min_order_value DESC LIMIT 1";
            $query = mysqli_query($db, $sql);
            $delivery_charge_data = mysqli_fetch_assoc($query);
            return $delivery_charge_data ? (float)$delivery_charge_data['delivery_charge'] : 0.00;
        }
    }

    function getCouponDetails(mysqli $db, string $coupon_code): ?array {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE coupon_code = ? AND is_active = 1 AND expiration_date > CURDATE()");
        $stmt->bind_param("s", $coupon_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $coupon = $result->fetch_assoc();
        $stmt->close();
        error_log("Coupon check for '$coupon_code': " . ($coupon ? json_encode($coupon) : "Not found"));
        return $coupon;
    }

    function isDeliveryAvailable(mysqli $db, string $city): bool {
        if (empty($city)) {
            error_log("City is empty in isDeliveryAvailable");
            return false;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM delivery_cities WHERE LOWER(city_name) LIKE LOWER(?) AND is_active = 1");
        if (!$stmt) {
            error_log("Prepare failed in isDeliveryAvailable: " . $db->error);
            return false;
        }

        $city_param = "%" . $city . "%";
        $stmt->bind_param("s", $city);
        if (!$stmt->execute()) {
            error_log("Execute failed in isDeliveryAvailable: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        error_log("Checking delivery for city: '$city', Count: $count");
        return $count > 0;
    }

    $free_delivery_threshold = getFreeDeliveryThreshold($db);

    if (!empty($cart_items)) {
        foreach ($cart_items as $res_id => $items) {
            foreach ($items as $item) {
                $item_total += ($item["price"] * $item["quantity"]);
            }
        }

        $temp_item_total = $item_total;

        if (isset($_POST['apply_coupon']) || !empty($_SESSION['applied_coupon'])) {
            $coupon_code = isset($_POST['coupon_code']) ? mysqli_real_escape_string($db, trim($_POST['coupon_code'])) : $_SESSION['applied_coupon'];
            error_log("Applying coupon: '$coupon_code', Item total: $item_total");
            $coupon = getCouponDetails($db, $coupon_code);

            if ($coupon) {
                $discount_type = $coupon['discount_type'];
                $discount_value = floatval($coupon['discount_value']);
                $min_order_value = floatval($coupon['min_order_value']);

                if ($item_total >= $min_order_value) {
                    if ($discount_type == 'percentage') {
                        $discount = ($item_total * $discount_value) / 100;
                    } elseif ($discount_type == 'fixed') {
                        $discount = $discount_value;
                    }

                    $item_total = max(0, $item_total - $discount);
                    $_SESSION['applied_coupon'] = $coupon_code;
                    $coupon_message = "<div class='alert alert-success'>Coupon '$coupon_code' applied! You saved ₹" . number_format($discount, 2) . "</div>";

                    $total_items = 0;
                    foreach ($cart_items as $res_id => $items) {
                        $total_items += count($items);
                    }
                    $discount_per_item = $total_items > 0 ? $discount / $total_items : 0;

                    foreach ($cart_items as $res_id => &$items) {
                        foreach ($items as &$item) {
                            $item_discount = min($item["price"] * $item["quantity"], $discount_per_item);
                            $item["discounted_price"] = $item["price"] * $item["quantity"] - $item_discount;
                        }
                    }
                    unset($items, $item);
                    error_log("Discount applied: $discount, New item total: $item_total");
                } else {
                    $coupon_message = "<div class='alert alert-danger'>This coupon requires a minimum order of ₹" . number_format($min_order_value, 2) . "</div>";
                    unset($_SESSION['applied_coupon']);
                    $discount = 0;
                    error_log("Coupon rejected: Item total ($item_total) < Min order ($min_order_value)");
                }
            } else {
                $coupon_message = "<div class='alert alert-danger'>Invalid or expired coupon code.</div>";
                unset($_SESSION['applied_coupon']);
                $discount = 0;
                error_log("Coupon '$coupon_code' not found or invalid");
            }
        }

        $delivery_charge = getDeliveryCharge($db, $item_total);
        $grand_total = $item_total + $delivery_charge;

        if (isset($_POST['submit_order']) && $_POST['submit_order'] === '1') {
            error_log("Form submitted - Processing order for user_id: $user_id");
            error_log("POST data: " . json_encode($_POST));

            $all_orders_successful = true;
            $first_order = true;

            $new_address = mysqli_real_escape_string($db, $_POST['address']);
            $new_latitude = floatval($_POST['latitude']);
            $new_longitude = floatval($_POST['longitude']);
            $city = mysqli_real_escape_string($db, $_POST['city']);
            $messages = isset($_POST['order_message']) ? $_POST['order_message'] : [];

            if (!isDeliveryAvailable($db, $city)) {
                $delivery_error = "Sorry, no delivery partner available in your location ($city).";
                $all_orders_successful = false;
                error_log("Delivery not available in city: $city");
            }

            error_log("POST Data - Address: $new_address, Latitude: $new_latitude, Longitude: $new_longitude, City: $city");

            if ($all_orders_successful && ($new_address !== $user['address'] || $new_latitude != $user['latitude'] || $new_longitude != $user['longitude'])) {
                $updateStmt = $db->prepare("UPDATE users SET address = ?, latitude = ?, longitude = ? WHERE u_id = ?");
                $updateStmt->bind_param("sddi", $new_address, $new_latitude, $new_longitude, $user_id);
                if ($updateStmt->execute()) {
                    error_log("User details updated successfully: u_id=$user_id, lat=$new_latitude, lon=$new_longitude");
                    $user['address'] = $new_address;
                    $user['latitude'] = $new_latitude;
                    $user['longitude'] = $new_longitude;
                } else {
                    echo "<div class='alert alert-danger'>Error updating user details: " . $updateStmt->error . "</div>";
                    error_log("Error updating user: " . $updateStmt->error);
                    $all_orders_successful = false;
                }
                $updateStmt->close();
            }

            if ($all_orders_successful) {
                $order_ids = [];
                foreach ($cart_items as $res_id => $items) {
                    $order_id = uniqid();
                    $order_ids[] = $order_id;
                    $res_item_total = 0;

                    foreach ($items as $item) {
                        $res_item_total += isset($item["discounted_price"]) ? $item["discounted_price"] : $item["price"] * $item["quantity"];
                    }

                    $res_delivery_charge = $first_order ? $delivery_charge : 0.00;
                    $res_grand_total = $res_item_total + $res_delivery_charge;
                    $first_order = false;

                    foreach ($items as $item) {
                        $subtotal = isset($item["discounted_price"]) ? $item["discounted_price"] : $item["price"] * $item["quantity"];
                        $SQL = "INSERT INTO users_orders (order_id, u_id, title, quantity, price, rs_id, delivery_charge, total_amount, customer_lat, customer_lon, coupon_code) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($SQL);
                        if (!$stmt) {
                            error_log("Prepare failed for order insert: " . $db->error);
                            $all_orders_successful = false;
                            break 2;
                        }
                        $coupon_code = isset($_SESSION['applied_coupon']) ? $_SESSION['applied_coupon'] : null;
                        $stmt->bind_param("sssidiiddds", $order_id, $user_id, $item["title"], $item["quantity"], $subtotal, $item["res_id"], $res_delivery_charge, $res_grand_total, $user['latitude'], $user['longitude'], $coupon_code);
                        if (!$stmt->execute()) {
                            $all_orders_successful = false;
                            echo "Error inserting item for res_id $res_id: " . $stmt->error;
                            error_log("Error inserting order for order_id $order_id: " . $stmt->error);
                            break 2;
                        }
                        $stmt->close();
                    }

                    // Save the message for this restaurant's order
                    if (!empty($messages[$res_id])) {
                        $message = mysqli_real_escape_string($db, trim($messages[$res_id]));
                        $messageStmt = $db->prepare("INSERT INTO order_messages (order_id, u_id, rs_id, message) VALUES (?, ?, ?, ?)");
                        $messageStmt->bind_param("siis", $order_id, $user_id, $res_id, $message);
                        if ($messageStmt->execute()) {
                            error_log("Message saved for order_id: $order_id, rs_id: $res_id");
                        } else {
                            error_log("Error saving message for order_id: $order_id: " . $messageStmt->error);
                        }
                        $messageStmt->close();
                    }
                }

                if ($all_orders_successful) {
                    error_log("Order IDs created: " . implode(", ", $order_ids));

                    // Include invoice.php to send the invoice email
                    require_once 'invoice.php';
                    $email_sent = sendInvoiceEmail($user, $cart_items, $temp_item_total, $delivery_charge, $discount, $order_ids);
                    if ($email_sent) {
                        error_log("Invoice email sent after order processing for user_id: $user_id");
                    } else {
                        error_log("Failed to send invoice email after order processing for user_id: $user_id");
                    }

                    // Clear cart
                    $clearStmt = $db->prepare("DELETE FROM cart WHERE u_id = ?");
                    $clearStmt->bind_param("i", $user_id);
                    if ($clearStmt->execute()) {
                        error_log("Cart cleared successfully for user_id: $user_id");
                    } else {
                        error_log("Error clearing cart: " . $clearStmt->error);
                        echo "<div class='alert alert-danger'>Order processed, but failed to clear cart: " . $clearStmt->error . "</div>";
                    }
                    $clearStmt->close();

                    unset($_SESSION['applied_coupon']);
                    error_log("Order processed successfully for user_id: $user_id, redirecting to your_orders.php");

                    ob_end_clean();
                    header("Location: your_orders.php?success=Order received successfully!");
                    exit;
                } else {
                    echo "<div class='alert alert-danger'>There was an error processing your orders. Please try again.</div>";
                    error_log("Order processing failed for user_id: $user_id");
                }
            } else {
                echo "<div class='alert alert-danger'>Order not processed due to delivery or update failure.</div>";
                error_log("Order not processed due to delivery or update failure for user_id: $user_id");
            }
        }
    } else {
        echo "<p>Your cart is empty.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Checkout</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; color: #333; }
    .site-wrapper { overflow-x: hidden; }
    .page-wrapper { padding-top: 70px; }
    .top-links { background-color: #fff; padding: 20px 0; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
    .top-links .links { padding: 0; margin: 0; list-style: none; display: flex; justify-content: space-around; }
    .top-links .links li { text-align: center; }
    .top-links .links li a { color: #555; text-decoration: none; transition: color 0.3s; }
    .top-links .links li a:hover { color: #e53935; }
    .top-links .links li.active a { color: #e53935; font-weight: bold; }
    .widget { margin-top: 30px; }
    .widget-body { padding: 20px; background-color: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
    .cart-totals { margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; }
    .cart-totals-title h4 { font-size: 1.5rem; margin-bottom: 15px; color: #333; }
    .cart-totals-fields .table { margin-bottom: 0; }
    .cart-totals-fields .table td { border-top: none; padding: 0.75rem; }
    .payment-option { margin-top: 20px; }
    .payment-option ul { list-style: none; padding: 0; }
    .payment-option li { margin-bottom: 10px; }
    .btn-success { background-color: #28a745; border-color: #28a745; transition: background-color 0.3s, border-color 0.3s; }
    .btn-success:hover { background-color: #218838; border-color: #1e7e34; }
    .free-delivery-message { color: green; font-weight: bold; margin-top: 10px; }
    .container>h2 { margin-bottom: 20px; font-size: 2rem; color: #333; }
    .container>p { font-size: 1.1rem; line-height: 1.6; }
    #header .navbar-brand { font-weight: bold; display: block; text-align: left; }
    #header { padding: 5px 0; }
    #header .navbar-nav { display: flex; flex-direction: row; justify-content: flex-end; }
    #header .navbar-nav .nav-item { margin-left: 15px; }
    #header .navbar-nav .nav-link { color: white !important; padding: 0.5rem 0; align-items: center; }
    #header .navbar-nav .nav-link i { margin-right: 5px; }
    #header .navbar-nav .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); }
    .footer { background-color: #333; color: #fff; padding: 20px 0; margin-top: 50px; }
    .bottom-footer { padding: 20px 0; border-top: 1px solid #555; }
    .payment-options h5 { color: #fff; }
    .payment-options ul { list-style: none; padding: 0; }
    .payment-options ul li { display: inline-block; margin-right: 10px; }
    .payment-options ul li a img { width: 50px; height: auto; opacity: 0.7; transition: opacity 0.3s; }
    .payment-options ul li a:hover img { opacity: 1; }
    .address h5 { color: #fff; }
    .additional-info h5 { color: #fff; }
    .user-details { background-color: #fff; padding: 20px; margin-bottom: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
    .user-details h2 { font-size: 2rem; margin-bottom: 15px; color: #333; border-bottom: 2px solid #e53935; padding-bottom: 5px; }
    .user-details p { font-size: 1.1rem; line-height: 1.6; margin-bottom: 10px; }
    .user-details p strong { font-weight: bold; color: #555; }
    .user-details p { margin-bottom: 5px; }
    .user-details h2 { font-size: 1.75rem; }
    .coupon-area { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; }
    .coupon-area h4 { font-size: 1.2rem; margin-bottom: 10px; color: #333; }
    .coupon-area .input-group { margin-bottom: 10px; }
    .discount-display { font-size: 1rem; color: green; margin-top: 5px; }
    .address-field textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; }
    .verify-btn, .location-btn { margin-top: 10px; padding: 8px 15px; font-size: 1rem; margin-right: 10px; }
    .cart-totals-fields .table th, .cart-totals-fields .table td { vertical-align: middle; }
    .verify-address-message { color: #e53935; font-size: 0.9rem; margin-top: 5px; text-align: center; }
    .order-message { margin-top: 15px; }
    .order-message textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; }
    </style>
</head>

<body>
    <div class="site-wrapper">
        <header id="header" class="header-scroll top-header headrom" style="background-color: #222; color: white;">
            <nav class="navbar navbar-dark" style="background-color: #222;">
                <div class="container">
                    <a class="navbar-brand" href="index.php">
                        <span style="font-size: 1.2em;">BHOJON BARTA</span>
                    </a>
                    <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#mainNavbarCollapse">☰</button>
                    <div class="collapse navbar-toggleable-md float-lg-right" id="mainNavbarCollapse">
                        <ul class="nav navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link active" href="index.php">
                                    <i class="fa fa-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="restaurants.php">
                                    <i class="fa fa-cutlery"></i> Restaurants
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="dishes.php">
                                    <i class="fa fa-burger"></i> Dishes
                                </a>
                            </li>
                            <?php if (!empty($_SESSION["user_id"])) {
                                echo '<li class="nav-item"><a class="nav-link active" href="your_orders.php"><i class="fa fa-list-alt"></i> My Orders</a></li>';
                            } ?>
                            <?php
                            if (empty($_SESSION["user_id"])) {
                                echo '<li class="nav-item"><a href="login.php" class="nav-link active"><i class="fa fa-sign-in"></i> Login</a></li>
                                      <li class="nav-item"><a href="registration.php" class="nav-link active"><i class="fa fa-user-plus"></i> Register</a></li>';
                            } else {
                                echo '<li class="nav-item"><a href="logout.php" class="nav-link active"><i class="fa fa-sign-out"></i> Logout</a></li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="page-wrapper">
            <div class="top-links" style="background-color: white;">
                <div class="container">
                    <ul class="row links d-flex">
                        <li class="col-sm-4 link-item"><span>1</span><a href="restaurants.php">Choose Restaurant</a></li>
                        <li class="col-sm-4 link-item"><span>2</span><a href="#">Pick Your favorite food</a></li>
                        <li class="col-sm-4 link-item active"><span>3</span><a href="checkout.php">Order and Pay</a></li>
                    </ul>
                </div>
            </div>

            <div class="container">
                <?php if (!empty($delivery_error)): ?>
                <div class="alert alert-danger"><?php echo $delivery_error; ?></div>
                <?php endif; ?>
            </div>

            <div class="container m-t-30">
                <form action="" method="post" id="orderForm">
                    <div class="user-details">
                        <h2>Your Details:</h2>
                        <?php if ($user) { ?>
                        <p>Name: <?php echo htmlspecialchars($user["f_name"] . " " . $user["l_name"]); ?></p>
                        <p>Email: <?php echo htmlspecialchars($user["email"]); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($user["phone"]); ?></p>
                        <p>Address: 
                            <span class="address-field">
                                <textarea name="address" id="address" rows="3" required><?php echo htmlspecialchars($user["address"]); ?></textarea>
                                <button type="button" id="verifyAddress" class="btn btn-primary verify-btn">Verify Address</button>
                                <button type="button" id="getLocation" class="btn btn-secondary location-btn">Get My Location</button>
                            </span>
                        </p>
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($user["latitude"] ?? '0.00000000'); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($user["longitude"] ?? '0.00000000'); ?>">
                        <input type="hidden" name="city" id="city" value="">
                        <input type="hidden" name="submit_order" id="submit_order" value="">
                        <input type="hidden" name="applied_coupon" id="applied_coupon" value="<?php echo isset($_SESSION['applied_coupon']) ? htmlspecialchars($_SESSION['applied_coupon']) : ''; ?>">
                        <?php } else { ?>
                        <p>Error loading user information.</p>
                        <?php } ?>
                    </div>

                    <div class="widget clearfix">
                        <div class="widget-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="coupon-area">
                                        <h4>Apply Coupon Code</h4>
                                        <?php echo $coupon_message; ?>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="coupon_code" id="coupon_code" placeholder="Enter coupon code" value="<?php echo isset($_SESSION['applied_coupon']) ? htmlspecialchars($_SESSION['applied_coupon']) : ''; ?>">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="submit" name="apply_coupon" value="1">Apply</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="cart-totals margin-b-20">
                                        <div class="cart-totals-title">
                                            <h4>Cart Summary</h4>
                                        </div>
                                        <div class="cart-totals-fields">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Restaurant</th>
                                                        <th>Item</th>
                                                        <th>Quantity</th>
                                                        <th>Price</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cart_items as $res_id => $items) { ?>
                                                        <?php foreach ($items as $item) {
                                                            $subtotal = $item["price"] * $item["quantity"];
                                                            $discounted_subtotal = isset($item["discounted_price"]) ? $item["discounted_price"] : $subtotal;
                                                        ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item["restaurant_name"]); ?></td>
                                                            <td><?php echo htmlspecialchars($item["title"]); ?></td>
                                                            <td><?php echo htmlspecialchars($item["quantity"]); ?></td>
                                                            <td>₹<?php echo number_format($item["price"], 2); ?></td>
                                                            <td>₹<?php echo number_format($discounted_subtotal, 2); ?></td>
                                                        </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td colspan="5">
                                                                <div class="order-message">
                                                                    <label for="order_message_<?php echo $res_id; ?>">Message to <?php echo htmlspecialchars($item["restaurant_name"]); ?>:</label>
                                                                    <textarea name="order_message[<?php echo $res_id; ?>]" id="order_message_<?php echo $res_id; ?>" rows="3" placeholder="Enter any special instructions or message for the restaurant"></textarea>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                    <tr>
                                                        <td colspan="4" class="text-right">Cart Subtotal</td>
                                                        <td>₹<?php echo number_format($temp_item_total, 2); ?></td>
                                                    </tr>
                                                    <?php if ($discount > 0): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-right">Discount</td>
                                                            <td>- ₹<?php echo number_format($discount, 2); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <td colspan="4" class="text-right">Discounted Subtotal</td>
                                                        <td>₹<?php echo number_format($item_total, 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-right">Delivery Charges</td>
                                                        <td>₹<?php echo number_format($delivery_charge, 2); ?></td>
                                                    </tr>
                                                    <?php if ($delivery_charge > 0): ?>
                                                    <tr>
                                                        <td colspan="5">
                                                            <?php
                                                            $remaining = $free_delivery_threshold - $item_total;
                                                            if ($remaining > 0) {
                                                                echo "<p class='text-info'>Add ₹" . number_format($remaining, 2) . " more to get free delivery!</p>";
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <td colspan="4" class="text-color text-right"><strong>Total</strong></td>
                                                        <td class="text-color"><strong>₹<?php echo number_format($grand_total, 2); ?></strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="payment-option">
                                        <ul class="list-unstyled">
                                            <li>
                                                <label class="custom-control custom-radio m-b-20">
                                                    <input name="mod" id="radioStacked1" checked value="COD" type="radio" class="custom-control-input">
                                                    <span class="custom-control-indicator"></span>
                                                    <span class="custom-control-description">Cash on Delivery</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="custom-control custom-radio m-b-10">
                                                    <input name="mod" type="radio" value="paypal" disabled class="custom-control-input">
                                                    <span class="custom-control-indicator"></span>
                                                    <span class="custom-control-description">Paypal <img src="images/paypal.jpg" alt="" width="90"></span>
                                                </label>
                                            </li>
                                        </ul>
                                        <p class="text-xs-center">
                                            <button type="button" class="btn btn-success btn-block" id="orderNowBtn" disabled>Order Now</button>
                                            <p class="verify-address-message">Verify address first</p>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            const addressField = $('#address');
            const latitudeField = $('#latitude');
            const longitudeField = $('#longitude');
            const cityField = $('#city');
            const verifyButton = $('#verifyAddress');
            const locationButton = $('#getLocation');
            const orderForm = $('#orderForm');
            const orderButton = $('#orderNowBtn');
            const couponCodeField = $('#coupon_code');
            const appliedCouponField = $('#applied_coupon');
            let deliveryAvailable = false;
            let deliveryBoyAvailable = false;
            let previousAddress = addressField.val().trim();

            console.log('jQuery loaded:', typeof $ !== 'undefined');
            console.log('SweetAlert2 loaded:', typeof Swal !== 'undefined');

            orderButton.prop('disabled', true);

            function checkDeliveryAvailability(city, isAddressChanged = false) {
                if (!city || city.trim() === '' || city === 'Unknown') {
                    console.log('No valid city provided for delivery check:', city);
                    Swal.fire({
                        icon: 'warning',
                        title: 'City Not Determined',
                        text: 'We couldn’t determine your city. Please enter a more specific address.',
                        confirmButtonColor: '#e53935'
                    });
                    orderButton.prop('disabled', true);
                    deliveryAvailable = false;
                    deliveryBoyAvailable = false;
                    $('.verify-address-message').show();
                    return;
                }
                console.log('Checking delivery availability for:', city);
                $.ajax({
                    url: 'check_delivery.php',
                    method: 'POST',
                    data: { check_delivery: true, city: city },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Delivery check response:', data);
                        if (data.status === 'error') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Verification Error',
                                text: data.message,
                                confirmButtonColor: '#e53935'
                            });
                            orderButton.prop('disabled', true);
                            deliveryAvailable = false;
                            deliveryBoyAvailable = false;
                            $('.verify-address-message').show();
                            return;
                        }
                        deliveryAvailable = data.available;
                        deliveryBoyAvailable = data.delivery_boy_available;

                        if (data.available && data.delivery_boy_available) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Delivery Available',
                                text: `Great news! Delivery is available to ${data.city}.`,
                                confirmButtonColor: '#28a745'
                            });
                            orderButton.prop('disabled', false);
                            $('.verify-address-message').hide();
                        } else {
                            if (isAddressChanged) {
                                if (!data.available) {
                                    let message = `Delivery is not available for your new address (${data.city}).`;
                                    if (data.available_cities && data.available_cities.length > 0) {
                                        message += ` We currently deliver to: ${data.available_cities.join(', ')}.`;
                                    }
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Delivery Not Available',
                                        text: message,
                                        confirmButtonColor: '#e53935'
                                    });
                                } else if (!data.delivery_boy_available) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'No Delivery Boy Available',
                                        text: `No delivery boy available for your new address (${data.city}). Please try again later.`,
                                        confirmButtonColor: '#e53935'
                                    });
                                }
                            } else {
                                if (!data.available) {
                                    let message = `Sorry, no delivery partner is available in your location (${data.city}).`;
                                    if (data.available_cities && data.available_cities.length > 0) {
                                        message += ` We currently deliver to: ${data.available_cities.join(', ')}.`;
                                    }
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'No Delivery Available',
                                        text: message,
                                        confirmButtonColor: '#e53935'
                                    });
                                } else if (!data.delivery_boy_available) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'No Delivery Boy Available',
                                        text: `No delivery boy available now in ${data.city}. Please try again later.`,
                                        confirmButtonColor: '#e53935'
                                    });
                                }
                            }
                            orderButton.prop('disabled', true);
                            $('.verify-address-message').show();
                        }
                        previousAddress = addressField.val().trim();
                    },
                    error: function(xhr, status, error) {
                        console.error('Delivery Check AJAX Error:', { status, error, responseText: xhr.responseText });
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Error',
                            text: 'Unable to verify delivery availability. Check console for details.',
                            confirmButtonColor: '#e53935'
                        });
                        orderButton.prop('disabled', true);
                        deliveryAvailable = false;
                        deliveryBoyAvailable = false;
                        $('.verify-address-message').show();
                    }
                });
            }

            function updateCoordinates(address) {
                console.log('Verifying address:', address);
                if (!address || address.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Address',
                        text: 'Please enter an address to verify.',
                        confirmButtonColor: '#e53935'
                    });
                    orderButton.prop('disabled', true);
                    $('.verify-address-message').show();
                    return;
                }

                const isAddressChanged = address.trim() !== previousAddress;
                console.log('Address changed:', isAddressChanged, 'Previous:', previousAddress, 'New:', address);

                $.ajax({
                    url: 'geocode.php',
                    method: 'POST',
                    data: { address: address },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Geocode response:', data);
                        if (data.status === 'success') {
                            latitudeField.val(data.latitude);
                            longitudeField.val(data.longitude);
                            const city = data.city && data.city.trim() !== '' ? data.city : 'Unknown';
                            cityField.val(city);
                            console.log('City extracted:', city);
                            checkDeliveryAvailability(city, isAddressChanged);
                        } else {
                            latitudeField.val('0.00000000');
                            longitudeField.val('0.00000000');
                            cityField.val('');
                            Swal.fire({
                                icon: 'error',
                                title: 'Verification Failed',
                                text: data.message || 'Unable to verify address.',
                                confirmButtonColor: '#e53935'
                            });
                            orderButton.prop('disabled', true);
                            $('.verify-address-message').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Geocode AJAX Error:', { status, error, responseText: xhr.responseText });
                        Swal.fire({
                            icon: 'error',
                            title: 'Geocode Error',
                            text: 'Unable to verify address. Check console for details.',
                            confirmButtonColor: '#e53935'
                        });
                        orderButton.prop('disabled', true);
                        $('.verify-address-message').show();
                    }
                });
            }

            function reverseGeocode(latitude, longitude) {
                console.log('Reverse geocoding:', { latitude, longitude });
                const isAddressChanged = addressField.val().trim() !== previousAddress;
                $.ajax({
                    url: 'reverse_geocode_checkout.php',
                    method: 'POST',
                    data: { lat: latitude, lon: longitude },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Reverse geocode response:', data);
                        if (data.status === 'success') {
                            addressField.val(data.address);
                            latitudeField.val(latitude);
                            longitudeField.val(longitude);
                            const city = data.city && data.city.trim() !== '' ? data.city : 'Unknown';
                            cityField.val(city);
                            checkDeliveryAvailability(city, isAddressChanged);
                        } else {
                            addressField.val('Unknown Location');
                            latitudeField.val('0.00000000');
                            longitudeField.val('0.00000000');
                            cityField.val('');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Address Not Found',
                                text: data.message || 'Unable to fetch address.',
                                confirmButtonColor: '#e53935'
                            });
                            orderButton.prop('disabled', true);
                            $('.verify-address-message').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Reverse Geocode AJAX Error:', { status, error, responseText: xhr.responseText });
                        Swal.fire({
                            icon: 'warning',
                            title: 'Location Error',
                            text: 'Unable to fetch location. Check console for details.',
                            confirmButtonColor: '#e53935'
                        });
                        orderButton.prop('disabled', true);
                        $('.verify-address-message').show();
                    }
                });
            }

            function getUserLocation() {
                console.log('Requesting user location');
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const latitude = position.coords.latitude.toFixed(8);
                            const longitude = position.coords.longitude.toFixed(8);
                            console.log('Location obtained:', { latitude, longitude });
                            reverseGeocode(latitude, longitude);
                        },
                        function(error) {
                            console.error('Geolocation Error:', error);
                            latitudeField.val('0.00000000');
                            longitudeField.val('0.00000000');
                            cityField.val('');
                            let errorMessage;
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMessage = 'Permission denied. Please allow location access or enter your address manually.';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMessage = 'Location information is unavailable. Please enter your address manually.';
                                    break;
                                case error.TIMEOUT:
                                    errorMessage = 'The request to get your location timed out. Please try again or enter your address manually.';
                                    break;
                                default:
                                    errorMessage = 'An unknown error occurred. Please enter your address manually.';
                                    break;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Location Error',
                                text: errorMessage,
                                confirmButtonColor: '#e53935'
                            });
                            orderButton.prop('disabled', true);
                            $('.verify-address-message').show();
                        }
                    );
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Geolocation Not Supported',
                        text: 'Your browser does not support geolocation. Please enter your address manually.',
                        confirmButtonColor: '#e53935'
                    });
                    orderButton.prop('disabled', true);
                    $('.verify-address-message').show();
                }
            }

            verifyButton.on('click', function() {
                const address = addressField.val().trim();
                console.log('Verify Address clicked');
                updateCoordinates(address);
            });

            locationButton.on('click', function() {
                console.log('Get My Location clicked');
                getUserLocation();
            });

            orderButton.on('click', function(e) {
                console.log('Order Now button clicked');
                e.preventDefault();
                const city = cityField.val().trim();

                if (!city || !deliveryAvailable || !deliveryBoyAvailable) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Place Order',
                        text: !city ? 'Please verify your address.' : (!deliveryAvailable ? 'Delivery is not available in your location.' : 'No delivery boy available now. Please try again later.'),
                        confirmButtonColor: '#e53935'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Confirm Your Order',
                    text: 'Are you sure you want to place this order?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Place Order',
                    cancelButtonText: 'No, Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Order confirmed - submitting form');
                        $('#submit_order').val('1');
                        orderForm.submit();
                    }
                });
            });

            orderForm.on('submit', function(e) {
                console.log('Form submission triggered, submit_order:', $('#submit_order').val());
                const submitOrder = $('#submit_order').val();

                if (submitOrder !== '1') {
                    return;
                }
                Swal.fire({
                    title: 'Processing Order',
                    text: 'Please wait while your order is being processed...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });

            $('button[name="apply_coupon"]').on('click', function(e) {
                console.log('Coupon apply button clicked');
                $('#submit_order').val('');
            });

            if (cityField.val() && latitudeField.val() !== '0.00000000' && longitudeField.val() !== '0.00000000') {
                checkDeliveryAvailability(cityField.val());
            }
        });
    </script>
</body>
</html>