<?php
session_start();
include("../connection/connect.php"); // Ensure this path is correct

if (!isset($_SESSION["db_id"])) {
    header("Location: index.php"); // Ensure this path is correct
    exit();
}

$db_id = $_SESSION["db_id"];

// Validate database connection
if (!$db || mysqli_connect_errno()) {
    error_log("Database connection failed: " . mysqli_connect_error()); // Log error
    die("Database connection failed. Please try again later."); // User-friendly message
}

// Fetch delivery boy details
$sql_db_details = "SELECT * FROM delivery_boy WHERE db_id = ?";
$stmt_db_details = mysqli_prepare($db, $sql_db_details);
if ($stmt_db_details === false) {
    error_log("Prepare failed for delivery_boy details: " . mysqli_error($db));
    die("Error fetching your details. Please contact support.");
}
mysqli_stmt_bind_param($stmt_db_details, "i", $db_id);
mysqli_stmt_execute($stmt_db_details);
$result_db_details = mysqli_stmt_get_result($stmt_db_details);
$delivery_boy = mysqli_fetch_assoc($result_db_details);
mysqli_stmt_close($stmt_db_details);

if (!$delivery_boy) {
    error_log("Delivery boy not found for db_id: $db_id");
    die("Your delivery boy profile was not found. Please contact support.");
}

// Function to assign a pending order
function assignPendingOrder($db_conn, $current_db_id) { // Renamed parameters for clarity
    $assign_sql = "SELECT o_id, rs_id FROM users_orders WHERE status = 'ready_for_pickup' AND delivery_boy_id IS NULL ORDER BY date DESC LIMIT 1"; // Added ORDER BY
    $assign_stmt = mysqli_prepare($db_conn, $assign_sql);
    if ($assign_stmt === false) {
        error_log("Prepare failed for assignPendingOrder select: " . mysqli_error($db_conn));
        return false;
    }
    mysqli_stmt_execute($assign_stmt);
    $pending_order_result = mysqli_stmt_get_result($assign_stmt);
    $pending_order = mysqli_fetch_assoc($pending_order_result);
    mysqli_stmt_close($assign_stmt);

    if ($pending_order) {
        $order_id = $pending_order['o_id'];
        $rs_id = $pending_order['rs_id'];

        // Begin transaction
        mysqli_begin_transaction($db_conn);

        try {
            $update_order_sql = "UPDATE users_orders SET delivery_boy_id = ?, status = 'assigned' WHERE o_id = ? AND rs_id = ? AND delivery_boy_id IS NULL AND status = 'ready_for_pickup'"; // Add more conditions for safety
            $update_order_stmt = mysqli_prepare($db_conn, $update_order_sql);
            if ($update_order_stmt === false) throw new Exception("Prepare failed for update_order: " . mysqli_error($db_conn));
            mysqli_stmt_bind_param($update_order_stmt, "iii", $current_db_id, $order_id, $rs_id);
            mysqli_stmt_execute($update_order_stmt);
            $affected_rows = mysqli_stmt_affected_rows($update_order_stmt);
            mysqli_stmt_close($update_order_stmt);

            if ($affected_rows > 0) { // Check if the order was actually assigned by this operation
                $update_busy_sql = "UPDATE delivery_boy SET current_status = 'busy' WHERE db_id = ?";
                $update_busy_stmt = mysqli_prepare($db_conn, $update_busy_sql);
                if ($update_busy_stmt === false) throw new Exception("Prepare failed for update_busy: " . mysqli_error($db_conn));
                mysqli_stmt_bind_param($update_busy_stmt, "i", $current_db_id);
                mysqli_stmt_execute($update_busy_stmt);
                mysqli_stmt_close($update_busy_stmt);

                mysqli_commit($db_conn);

                // Notify WebSocket server of new order (Consider moving this to an async task if it's slow)
                // $ws_notification_url = "http://localhost:8080/notify_new_order.php?db_id=$current_db_id&order_id=$order_id";
                // @file_get_contents($ws_notification_url); // Use @ to suppress errors if server not reachable
                // A better way is for the WebSocket server to query this or have a dedicated notification endpoint

                return true;
            } else {
                // Order was likely picked up by another delivery boy in the meantime
                mysqli_rollback($db_conn);
                return false;
            }
        } catch (Exception $e) {
            mysqli_rollback($db_conn);
            error_log("Error in assignPendingOrder transaction: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

// Handle toggle account status
if (isset($_POST['toggle_account'])) {
    $new_status = $delivery_boy['current_status'] === 'available' ? 'offline' : 'available';
    $update_sql = "UPDATE delivery_boy SET current_status = ? WHERE db_id = ?";
    $update_stmt = mysqli_prepare($db, $update_sql);
    if ($update_stmt === false) {
        error_log("Prepare failed for toggle_account: " . mysqli_error($db));
        // Potentially set an error message for the user
    } else {
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $db_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        $delivery_boy['current_status'] = $new_status; // Update local variable

        if ($new_status === 'available') {
            assignPendingOrder($db, $db_id); // Attempt to assign an order
        }
        // Redirect to avoid form resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch active orders
$ordersSql = "SELECT uo.*, u.username, u.address,
              (SELECT latitude FROM users WHERE u_id = uo.u_id) as user_lat,
              (SELECT longitude FROM users WHERE u_id = uo.u_id) as user_lon,
              r.title as rest_title, r.address as rest_address,
              (SELECT latitude FROM restaurant WHERE rs_id = uo.rs_id) as rest_lat,
              (SELECT longitude FROM restaurant WHERE rs_id = uo.rs_id) as rest_lon
              FROM users_orders uo
              LEFT JOIN users u ON uo.u_id = u.u_id
              LEFT JOIN restaurant r ON uo.rs_id = r.rs_id
              WHERE uo.delivery_boy_id = ? AND uo.status NOT IN ('closed', 'rejected', 'delivered') ORDER BY uo.date DESC"; // 'delivered' might be same as 'closed'
$ordersStmt = mysqli_prepare($db, $ordersSql);
if ($ordersStmt === false) {
    error_log("Prepare failed for fetching active orders: " . mysqli_error($db) . " | Query: $ordersSql");
    die("Error fetching active orders. Please try again.");
}
mysqli_stmt_bind_param($ordersStmt, "i", $db_id);
mysqli_stmt_execute($ordersStmt);
$orders_result = mysqli_stmt_get_result($ordersStmt);
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);
mysqli_stmt_close($ordersStmt);

// Handle status update from "Mark Delivered" form submission
if (isset($_POST['status_update']) && isset($_POST['order_id']) && $_POST['status'] === 'closed') {
    $order_id_to_close = $_POST['order_id'];
    // Begin transaction
    mysqli_begin_transaction($db);
    try {
        $update_order_sql = "UPDATE users_orders SET status = 'closed' WHERE o_id = ? AND delivery_boy_id = ? AND status = 'in_transit'";
        $update_order_stmt = mysqli_prepare($db, $update_order_sql);
        if ($update_order_stmt === false) throw new Exception("Prepare failed for mark_delivered order: " . mysqli_error($db));
        mysqli_stmt_bind_param($update_order_stmt, "ii", $order_id_to_close, $db_id);
        mysqli_stmt_execute($update_order_stmt);
        mysqli_stmt_close($update_order_stmt);

        $update_status_sql = "UPDATE delivery_boy SET current_status = 'available' WHERE db_id = ?";
        $update_status_stmt = mysqli_prepare($db, $update_status_sql);
        if ($update_status_stmt === false) throw new Exception("Prepare failed for mark_delivered status: " . mysqli_error($db));
        mysqli_stmt_bind_param($update_status_stmt, "i", $db_id);
        mysqli_stmt_execute($update_status_stmt);
        mysqli_stmt_close($update_status_stmt);

        mysqli_commit($db);
        // Attempt to assign a new order immediately
        assignPendingOrder($db, $db_id);
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($db);
        error_log("Error in Mark Delivered transaction: " . $e->getMessage());
        // Set a session flash message for error
        $_SESSION['error_message'] = "Could not mark order as delivered. Please try again.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Refresh delivery boy status after potential updates (ensure $delivery_boy is current)
$sql_refresh_db = "SELECT * FROM delivery_boy WHERE db_id = ?";
$stmt_refresh_db = mysqli_prepare($db, $sql_refresh_db);
if ($stmt_refresh_db === false) {
    error_log("Prepare failed for delivery_boy refresh: " . mysqli_error($db));
} else {
    mysqli_stmt_bind_param($stmt_refresh_db, "i", $db_id);
    mysqli_stmt_execute($stmt_refresh_db);
    $result_refresh_db = mysqli_stmt_get_result($stmt_refresh_db);
    $refreshed_delivery_boy = mysqli_fetch_assoc($result_refresh_db);
    if ($refreshed_delivery_boy) {
        $delivery_boy = $refreshed_delivery_boy;
    }
    mysqli_stmt_close($stmt_refresh_db);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - <?php echo htmlspecialchars($delivery_boy['db_name'] ?? 'Delivery'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Using maps.gomaps.pro API. Ensure KEY is correct for THIS service. -->
    <!-- <script src="https://maps.gomaps.pro/maps/api/js?key=AlzaSywN3aQoKj_Sva4ifm5kswhAwUjvkqFgdBA"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #3498db; /* Blue */
            --secondary-color: #2ecc71; /* Green */
            --accent-color: #f39c12; /* Orange */
            --background-color: #f4f6f8; /* Lighter gray */
            --text-color: #333333;
            --light-text-color: #555555; /* Slightly darker for better readability */
            --card-background: #ffffff;
            --border-color: #dee2e6; /* Bootstrap's default border color */
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.075); /* Softer shadow */
            --border-radius: 0.5rem; /* Slightly larger radius */
            --font-family: 'Poppins', sans-serif;
        }
        body {
            font-family: var(--font-family);
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 0; /* Increased padding */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 500; /* Slightly less bold for navbar items */
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem; /* Increased padding */
            text-align: center;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem; /* Added margin */
        }
        .order-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .order-header {
            background-color: #f8f9fa; /* Lighter header for cards */
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }
        .order-body {
            padding: 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .order-info { flex: 1; min-width: 280px; }
        .order-info p { margin: 0.5rem 0; line-height: 1.7; color: var(--light-text-color); }
        .order-info strong { color: var(--text-color); }
        .map-section { flex: 1; min-width: 320px; }
        .map-container { height: 220px; border-radius: var(--border-radius); overflow: hidden; margin-bottom: 0.75rem; border: 1px solid var(--border-color);}
        .distance-info { font-size: 0.9rem; color: var(--light-text-color); font-weight: 500; text-align: center; margin-top: 0.5rem;}
        .status-badge { padding: 0.4em 0.8em; border-radius: 1rem; font-size: 0.85em; font-weight: 500; }
        .btn-custom { border-radius: 1.5rem; padding: 0.6rem 1.5rem; font-weight: 500; }
        .loader { border: 5px solid #f3f3f3; border-top: 5px solid var(--primary-color); border-radius: 50%; width: 30px; height: 30px; animation: spin 1.5s linear infinite; margin: 20px auto; display: none; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loading .loader { display: block; }
        .location-label { color: #6c757d; font-size: 0.85em; font-weight: 500; display: block; margin-bottom: 0.2rem; }
        .order-actions { width: 100%; margin-top: 1rem; } /* Ensure actions take full width */
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Bhojon Barta Delivery</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav ms-auto">
                    <a href="delivery_boy_history.php" class="nav-link me-3">Order History</a>
                    <a href="logout.php" class="btn btn-outline-light btn-custom">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                htmlspecialchars($_SESSION['error_message']) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
                htmlspecialchars($_SESSION['success_message']) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
            unset($_SESSION['success_message']);
        }
        ?>
        <div class="row mb-4">
            <div class="col-md-10 mx-auto">
                <div class="welcome-card">
                    <h2>Welcome, <?php echo htmlspecialchars($delivery_boy['db_name']); ?>!</h2>
                    <p class="mb-1">Your Current Status:</p>
                    <p>
                        <span class="status-badge fs-5 <?php echo $delivery_boy['current_status'] === 'available' ? 'bg-success' : ($delivery_boy['current_status'] === 'busy' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                            <?php echo ucfirst($delivery_boy['current_status']); ?>
                        </span>
                    </p>
                     <div class="mt-3">
                        <form method="post" style="display: inline-block; margin-right: 10px;">
                            <button type="submit" name="toggle_account"
                                    class="btn btn-lg <?php echo $delivery_boy['current_status'] === 'available' ? 'btn-danger' : 'btn-success'; ?>">
                                <?php echo $delivery_boy['current_status'] === 'available' ? '<i class="fas fa-times-circle"></i> Go Offline' : '<i class="fas fa-check-circle"></i> Go Online'; ?>
                            </button>
                        </form>
                        <button id="enableNotifications" class="btn btn-lg btn-info"><i class="fas fa-bell"></i> Notifications</button>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="text-center mb-4" style="color: var(--text-color); font-weight: 600;">Your Active Orders</h3>
        <div id="orderContainer">
            <div class="loader"></div> <!-- Loader for entire order section -->
            <?php if (empty($orders)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle"></i> No active orders assigned to you at the moment.
                    <?php if($delivery_boy['current_status'] === 'available'): ?>
                        You're online and ready for new assignments!
                    <?php elseif($delivery_boy['current_status'] === 'offline'): ?>
                        Go online to receive new orders.
                    <?php endif; ?>
                </div>
            <?php else: foreach ($orders as $order): ?>
                <div class="order-card" id="order_<?php echo $order['o_id']; ?>">
                    <div class="order-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['o_id']); ?></h5>
                        <span class="status-badge <?php
                            switch ($order['status']) {
                                case 'assigned': echo 'bg-info text-dark'; break;
                                case 'in_transit': echo 'bg-primary'; break;
                                default: echo 'bg-secondary'; break;
                            }
                        ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                    </div>
                    <div class="order-body">
                        <div class="order-info">
                            <p><strong>Item:</strong> <?php echo htmlspecialchars($order['title']); ?> (Qty: <?php echo htmlspecialchars($order['quantity']); ?>)</p>
                            <p><strong>Total Price:</strong> ₹<?php echo number_format($order['price'], 2); ?></p>
                            <hr>
                            <p><span class="location-label"><i class="fas fa-store"></i> Pickup From:</span> <?php echo htmlspecialchars($order['rest_title'] ?? 'N/A'); ?><br>
                               <small><?php echo htmlspecialchars($order['rest_address'] ?? 'Address not available'); ?></small></p>
                            <p><span class="location-label"><i class="fas fa-user"></i> Deliver To:</span> <?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?><br>
                               <small><?php echo htmlspecialchars($order['address'] ?? 'Address not available'); ?></small></p>
                            <a href="view_order_delivery_boy.php?o_id=<?php echo $order['o_id']; ?>" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-eye"></i> View Full Details</a>
                        </div>
                        <div class="map-section">
                            <div class="map-container" id="pickup_map_<?php echo $order['o_id']; ?>">Loading pickup map...</div>
                            <p class="distance-info" id="pickup_distance_<?php echo $order['o_id']; ?>"></p>
                            <div class="map-container" id="delivery_map_<?php echo $order['o_id']; ?>">Loading delivery map...</div>
                            <p class="distance-info" id="delivery_distance_<?php echo $order['o_id']; ?>"></p>
                        </div>
                        <div class="order-actions text-center">
                            <?php if ($order['status'] === 'assigned'): ?>
                                <button class="btn btn-success btn-custom me-2" onclick="updateStatus(<?php echo $order['o_id']; ?>, 'in_transit')"><i class="fas fa-check"></i> Accept Order</button>
                                <button class="btn btn-danger btn-custom" onclick="updateStatus(<?php echo $order['o_id']; ?>, 'rejected')"><i class="fas fa-times"></i> Reject Order</button>
                            <?php elseif ($order['status'] === 'in_transit'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['o_id']; ?>">
                                    <input type="hidden" name="status" value="closed"> <!-- 'closed' or 'delivered' -->
                                    <input type="hidden" name="status_update" value="1">
                                    <button type="submit" class="btn btn-primary btn-custom"><i class="fas fa-truck-loading"></i> Mark Delivered</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <audio id="buzzerSound" preload="auto">
        <source src="/OnlineFood-PHP/sounds/buzzer.mp3" type="audio/mpeg"> <!-- Verify this path -->
        Your browser does not support the audio element.
    </audio>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript - Placed at the end of the body -->
    <script>
        // Declare global variables that will be initialized later
        let pickupMaps = {}, deliveryMaps = {}, pickupMarkers = {}, deliveryMarkers = {};
        let pickupRenderers = {}, deliveryRenderers = {};
        let ws;
        let directionsService;

        // Variables initialized from PHP
        const dbId = <?php echo json_encode($db_id); ?>;
        let currentOrderIds = <?php echo json_encode(array_column($orders, 'o_id')); ?>;
        // const gomapsApiKey = "AlzaSy_ZZIUzey6h4dUkRVDxLYlH5iUYF__W5qi"; // Not directly used by google.maps object if API script loads it

        function initializeWebSocket() {
            // Ensure WebSocket server URL is correct (ws:// or wss:// for secure)
            ws = new WebSocket('ws://localhost:8080'); // Replace localhost if server is elsewhere

            ws.onopen = () => {
                console.log('WebSocket connected');
                if (dbId) {
                    ws.send(JSON.stringify({ type: 'init', db_id: dbId }));
                } else {
                    console.error("dbId not available for WebSocket init. Ensure PHP passes it correctly.");
                }
            };

            ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log("WebSocket message received:", data);

                    if (data.type === 'location_update' && data.order_id) {
                        const orderIdForUpdate = Array.isArray(data.order_id) ? data.order_id[0] : data.order_id; // Handle single or array
                        if (pickupMarkers[orderIdForUpdate] || deliveryMarkers[orderIdForUpdate]) { // Check if map elements for this order exist
                            const newPos = {lat: parseFloat(data.latitude), lng: parseFloat(data.longitude)};
                            if(pickupMarkers[orderIdForUpdate]) pickupMarkers[orderIdForUpdate].setPosition(newPos);
                            if(deliveryMarkers[orderIdForUpdate]) deliveryMarkers[orderIdForUpdate].setPosition(newPos);

                            // Example of how to get rest_lat/lon for the specific order from JavaScript data if needed
                            // This requires $orders to be accessible or passed to JS in a structured way.
                            // For simplicity, assuming this comes with location_update or is handled by server.
                            // If not, you might need to fetch order details or have them in JS.
                            const currentOrderData = <?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>.find(o => o.o_id == orderIdForUpdate);

                            if (currentOrderData && currentOrderData.rest_lat && currentOrderData.rest_lon) {
                                 updatePickupRoute(orderIdForUpdate, newPos, {lat: parseFloat(currentOrderData.rest_lat), lng: parseFloat(currentOrderData.rest_lon)});
                            }
                            if (currentOrderData && currentOrderData.user_lat && currentOrderData.user_lon) {
                                updateDeliveryRoute(orderIdForUpdate, newPos, {lat: parseFloat(currentOrderData.user_lat), lng: parseFloat(currentOrderData.user_lon)});
                            }
                        }
                    } else if (data.type === 'new_order' && data.db_id === dbId) {
                        if (!currentOrderIds.includes(data.order_id)) {
                            showNotification(data.order_id);
                            currentOrderIds.push(data.order_id);
                            Swal.fire({
                                icon: 'info',
                                title: 'New Order Assigned!',
                                text: `Order #${data.order_id} has been assigned to you. The page will reload.`,
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        }
                    }
                } catch (e) {
                    console.error("Error processing WebSocket message:", e, "Raw data:", event.data);
                }
            };

            ws.onerror = (error) => {
                console.error('WebSocket Error:', error);
                // Could inform user or attempt reconnect
            };

            ws.onclose = (event) => {
                console.log('WebSocket disconnected. Code:', event.code, 'Reason:', event.reason);
                // Optional: implement robust reconnection logic here if needed
                // setTimeout(initializeWebSocket, 5000); // Simple reconnect attempt
            };
        }

        function initPageMapsAndLogic() {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.warn('GoMaps.pro API not loaded yet. Retrying in 250ms...');
                setTimeout(initPageMapsAndLogic, 250);
                return;
            }
            console.info('GoMaps.pro API detected. Initializing maps and page logic.');
            document.getElementById('orderContainer').classList.remove('loading'); // Hide main loader

            try {
                directionsService = new google.maps.DirectionsService();
            } catch (e) {
                console.error("Failed to initialize DirectionsService:", e);
                Swal.fire('Map Error', 'Could not initialize map routing service. Please refresh.', 'error');
                return; // Stop further map initialization if this fails
            }

            const ordersData = <?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const deliveryBoyLocation = {
                lat: <?php echo $delivery_boy['latitude'] ?: 'null'; ?>,
                lng: <?php echo $delivery_boy['longitude'] ?: 'null'; ?>
            };

            ordersData.forEach(order => {
                try {
                    const orderId = order.o_id;
                    const restLocation = { lat: parseFloat(order.rest_lat), lng: parseFloat(order.rest_lon) };
                    const userLocation = { lat: parseFloat(order.user_lat), lng: parseFloat(order.user_lon) };

                    // Pickup Map
                    if (document.getElementById(`pickup_map_${orderId}`) && !isNaN(restLocation.lat) && !isNaN(restLocation.lng)) {
                        pickupMaps[orderId] = new google.maps.Map(document.getElementById(`pickup_map_${orderId}`), {
                            zoom: 13, center: restLocation, mapTypeControl: false, streetViewControl: false
                        });
                        if (deliveryBoyLocation.lat !== null && deliveryBoyLocation.lng !== null) {
                            pickupMarkers[orderId] = new google.maps.Marker({ position: deliveryBoyLocation, map: pickupMaps[orderId], title: 'Your Location', icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png' });
                        }
                        new google.maps.Marker({ position: restLocation, map: pickupMaps[orderId], title: `Pickup: ${order.rest_title || 'Restaurant'}`, icon: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png' });
                        pickupRenderers[orderId] = new google.maps.DirectionsRenderer({ map: pickupMaps[orderId], suppressMarkers: true, polylineOptions: { strokeColor: '#FF0000', strokeWeight: 5 } });
                        if (deliveryBoyLocation.lat !== null && deliveryBoyLocation.lng !== null) {
                            updatePickupRoute(orderId, deliveryBoyLocation, restLocation);
                        } else {
                            document.getElementById(`pickup_distance_${orderId}`).textContent = 'Your location needed for pickup route.';
                        }
                    } else if(document.getElementById(`pickup_map_${orderId}`)) {
                        document.getElementById(`pickup_map_${orderId}`).innerHTML = '<p class="text-center p-3 text-danger">Pickup location data missing or invalid.</p>';
                    }

                    // Delivery Map
                    if (document.getElementById(`delivery_map_${orderId}`) && !isNaN(userLocation.lat) && !isNaN(userLocation.lng)) {
                        deliveryMaps[orderId] = new google.maps.Map(document.getElementById(`delivery_map_${orderId}`), {
                            zoom: 13, center: userLocation, mapTypeControl: false, streetViewControl: false
                        });
                        if (deliveryBoyLocation.lat !== null && deliveryBoyLocation.lng !== null) {
                            deliveryMarkers[orderId] = new google.maps.Marker({ position: deliveryBoyLocation, map: deliveryMaps[orderId], title: 'Your Location', icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png' });
                        }
                        new google.maps.Marker({ position: userLocation, map: deliveryMaps[orderId], title: `Customer: ${order.username || 'Customer'}`, icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png' });
                        deliveryRenderers[orderId] = new google.maps.DirectionsRenderer({ map: deliveryMaps[orderId], suppressMarkers: true, polylineOptions: { strokeColor: '#00B3FD', strokeWeight: 5 } });
                        if (deliveryBoyLocation.lat !== null && deliveryBoyLocation.lng !== null) {
                            updateDeliveryRoute(orderId, deliveryBoyLocation, userLocation);
                        } else {
                             document.getElementById(`delivery_distance_${orderId}`).textContent = 'Your location needed for delivery route.';
                        }
                    } else if(document.getElementById(`delivery_map_${orderId}`)) {
                        document.getElementById(`delivery_map_${orderId}`).innerHTML = '<p class="text-center p-3 text-danger">Customer location data missing or invalid.</p>';
                    }
                } catch (e) {
                    console.error(`Error initializing map for order ${order.o_id}:`, e);
                    if(document.getElementById(`pickup_map_${order.o_id}`)) document.getElementById(`pickup_map_${order.o_id}`).innerHTML = `<p class="text-danger p-3">Error: ${e.message}</p>`;
                    if(document.getElementById(`delivery_map_${order.o_id}`)) document.getElementById(`delivery_map_${order.o_id}`).innerHTML = `<p class="text-danger p-3">Error: ${e.message}</p>`;
                }
            });

            initializeWebSocket();
            setInterval(updateLocation, 6000); // Update location every 6 seconds
            setInterval(checkForNewOrders, 12000); // Check for new orders every 12 seconds

            const enableNotificationsButton = document.getElementById('enableNotifications');
            if (enableNotificationsButton) {
                enableNotificationsButton.addEventListener('click', requestNotificationPermission);
            }
            if ('Notification' in window && Notification.permission === "denied") {
                 /* ... (Swal for denied notifications) ... */
            }
            console.info("Page logic and maps fully initialized.");
        }

        function updatePickupRoute(orderId, origin, destination) {
            if (!directionsService) { console.error("DirectionsService not ready for pickup route."); return; }
            if (!pickupRenderers[orderId]) { console.warn(`Pickup renderer for order ${orderId} not found.`); return; }
            if (!origin || isNaN(origin.lat) || isNaN(origin.lng) || !destination || isNaN(destination.lat) || isNaN(destination.lng)) {
                console.warn('Invalid coordinates for pickup route:', {orderId, origin, destination});
                if(document.getElementById(`pickup_distance_${orderId}`)) document.getElementById(`pickup_distance_${orderId}`).textContent = 'Route to pickup: Invalid coordinates.';
                return;
            }
            directionsService.route({ origin, destination, travelMode: google.maps.TravelMode.DRIVING }, (result, status) => {
                const distEl = document.getElementById(`pickup_distance_${orderId}`);
                if (status === google.maps.DirectionsStatus.OK && result && result.routes && result.routes.length > 0) {
                    pickupRenderers[orderId].setDirections(result);
                    if(distEl) distEl.textContent = `To Pickup: ${result.routes[0].legs[0].distance.text} (ETA: ${result.routes[0].legs[0].duration.text})`;
                    if(pickupMaps[orderId]) pickupMaps[orderId].fitBounds(result.routes[0].bounds);
                } else {
                    console.error(`Pickup route failed (Order ${orderId}): ${status}`);
                    if(distEl) distEl.textContent = `Route to pickup: ${status}`;
                }
            });
        }

        function updateDeliveryRoute(orderId, origin, destination) {
            if (!directionsService) { console.error("DirectionsService not ready for delivery route."); return; }
            if (!deliveryRenderers[orderId]) { console.warn(`Delivery renderer for order ${orderId} not found.`); return; }
            if (!origin || isNaN(origin.lat) || isNaN(origin.lng) || !destination || isNaN(destination.lat) || isNaN(destination.lng)) {
                console.warn('Invalid coordinates for delivery route:', {orderId, origin, destination});
                if(document.getElementById(`delivery_distance_${orderId}`)) document.getElementById(`delivery_distance_${orderId}`).textContent = 'Route to customer: Invalid coordinates.';
                return;
            }
            directionsService.route({ origin, destination, travelMode: google.maps.TravelMode.DRIVING }, (result, status) => {
                const distEl = document.getElementById(`delivery_distance_${orderId}`);
                if (status === google.maps.DirectionsStatus.OK && result && result.routes && result.routes.length > 0) {
                    deliveryRenderers[orderId].setDirections(result);
                    if(distEl) distEl.textContent = `To Customer: ${result.routes[0].legs[0].distance.text} (ETA: ${result.routes[0].legs[0].duration.text})`;
                    if(deliveryMaps[orderId]) deliveryMaps[orderId].fitBounds(result.routes[0].bounds);
                } else {
                    console.error(`Delivery route failed (Order ${orderId}): ${status}`);
                    if(distEl) distEl.textContent = `Route to customer: ${status}`;
                }
            });
        }

        function updateLocation() {
            if (!navigator.geolocation) { console.warn("Geolocation not supported."); return; }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const newPos = { lat: position.coords.latitude, lng: position.coords.longitude };
                    console.log("Current location:", newPos);

                    if (ws && ws.readyState === WebSocket.OPEN && dbId) {
                        // Send a single location update for the delivery boy,
                        // the server can then decide how to use this (e.g., update all active orders for this db_id)
                        ws.send(JSON.stringify({
                            type: 'location_update', db_id: dbId,
                            latitude: newPos.lat, longitude: newPos.lng,
                            // Optionally send current_order_ids if server needs to know which orders are being handled by this db_id
                            // active_order_ids: currentOrderIds
                        }));
                    }

                    fetch(`update_db_location.php?lat=${newPos.lat}&lon=${newPos.lng}`, { method: 'GET', credentials: 'include' })
                        .then(response => response.ok ? response.json() : Promise.reject(response))
                        .then(data => data.success ? console.log('DB location updated.') : console.error('DB location update failed:', data.message))
                        .catch(err => console.error('Fetch error for DB location update:', err));

                    const ordersData = <?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    ordersData.forEach(order => {
                        const orderId = order.o_id;
                        const restLocation = { lat: parseFloat(order.rest_lat), lng: parseFloat(order.rest_lon) };
                        const userLocation = { lat: parseFloat(order.user_lat), lng: parseFloat(order.user_lon) };

                        if (pickupMarkers[orderId]) pickupMarkers[orderId].setPosition(newPos);
                        if (deliveryMarkers[orderId]) deliveryMarkers[orderId].setPosition(newPos);
                        if (!isNaN(restLocation.lat) && !isNaN(restLocation.lng)) updatePickupRoute(orderId, newPos, restLocation);
                        if (!isNaN(userLocation.lat) && !isNaN(userLocation.lng)) updateDeliveryRoute(orderId, newPos, userLocation);
                    });
                },
                (error) => { console.error('Geolocation error:', error.message, `(Code: ${error.code})`); },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function updateStatus(orderId, status) {
            console.log(`Updating order ${orderId} to status '${status}' for db_id ${dbId}`);
            document.getElementById('orderContainer').classList.add('loading');
            fetch('update_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({order_id: orderId, status: status, db_id: dbId})
            })
            .then(response => response.ok ? response.json() : response.text().then(text => Promise.reject(`Server error: ${response.status} - ${text}`)))
            .then(data => {
                document.getElementById('orderContainer').classList.remove('loading');
                if (data.success) {
                    Swal.fire('Success!', `Order #${orderId} status updated to ${status}.`, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Update Failed', data.message || 'Could not update order status.', 'error');
                }
            })
            .catch(error => {
                document.getElementById('orderContainer').classList.remove('loading');
                console.error('Fetch error during status update:', error);
                Swal.fire('Error', `An error occurred: ${error}`, 'error');
            });
        }

        function requestNotificationPermission() {
            if (!('Notification' in window)) {
                Swal.fire('Not Supported', 'This browser does not support desktop notification.', 'warning');
                return;
            }
            if (Notification.permission === 'granted') {
                 new Notification("Notifications Enabled!", { body: "You will receive new order alerts."});
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification("Notifications Enabled!", { body: "You will receive new order alerts."});
                    } else {
                        Swal.fire('Permission Denied', 'You will not receive notifications.', 'info');
                    }
                });
            } else { // permission === 'denied'
                 Swal.fire('Notifications Blocked', 'Please enable notifications in your browser settings.', 'warning');
            }
        }

        function showNotification(orderId) {
            const buzzer = document.getElementById('buzzerSound');
            if (buzzer) buzzer.play().catch(e => console.warn("Buzzer play error:", e));

            if (Notification.permission === 'granted') {
                new Notification("New Order Assigned!", {
                    body: `Order #${orderId} is ready for you. Please check your dashboard.`,
                    icon: '/OnlineFood-PHP/images/notification_icon.png' // Path to an icon
                });
            }
        }

        function checkForNewOrders() {
            if (!dbId) { console.warn("dbId not available, skipping new order check."); return; }
            fetch('get_assigned_orders.php', { // Ensure this PHP script exists and returns JSON
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({db_id: dbId})
            })
            .then(response => response.ok ? response.json() : Promise.reject('Network error checking orders.'))
            .then(data => {
                if (data.success && data.order_ids && data.order_ids.length > 0) {
                    data.order_ids.forEach(newOrderId => {
                        if (!currentOrderIds.includes(newOrderId)) {
                            showNotification(newOrderId);
                            currentOrderIds.push(newOrderId); // Add to current list
                            // Potentially add order dynamically or prompt for reload
                            Swal.fire({
                                title: 'New Order!',
                                text: `Order #${newOrderId} has been assigned. Reloading page.`,
                                icon: 'info',
                                timer: 3000,
                                willClose: () => { location.reload(); }
                            });
                        }
                    });
                } else if (!data.success) {
                    console.warn("Checking new orders failed on server:", data.message);
                }
            })
            .catch(error => console.error('Error checking for new orders:', error));
        }

        // Initialize the page logic when the DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('orderContainer').classList.add('loading'); // Show loader initially
                initPageMapsAndLogic();
            });
        } else {
            document.getElementById('orderContainer').classList.add('loading');
            initPageMapsAndLogic();
        }
    </script>
</body>
</html>