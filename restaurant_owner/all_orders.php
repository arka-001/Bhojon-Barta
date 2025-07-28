<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner' || !isset($_SESSION['owner_id'])) {
    header("Location: restaurant_owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$current_restaurant = isset($_GET['rs_id']) ? intval($_GET['rs_id']) : null;

$stmt = $db->prepare("SELECT rs_id, title, city FROM restaurant WHERE owner_id = ? AND rs_id = ?");
$stmt->bind_param("ii", $owner_id, $current_restaurant);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    die("Invalid restaurant ID or access denied.");
}

// Function to normalize city names
function normalize_city($city) {
    return strtolower(trim($city));
}

// Function to assign pending orders to available delivery boys in the same city
function assignPendingOrders($db, $current_restaurant) {
    // Get the restaurant's city
    $stmt = $db->prepare("SELECT city FROM restaurant WHERE rs_id = ?");
    $stmt->bind_param("i", $current_restaurant);
    $stmt->execute();
    $restaurant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$restaurant || empty($restaurant['city'])) {
        error_log("No city found for restaurant ID: $current_restaurant");
        return false;
    }
    $restaurant_city = normalize_city($restaurant['city']);
    $display_city = $restaurant['city'];

    // Get pending orders
    $stmt = $db->prepare("SELECT order_id FROM users_orders WHERE rs_id = ? AND status = 'ready_for_pickup' AND delivery_boy_id IS NULL");
    $stmt->bind_param("i", $current_restaurant);
    $stmt->execute();
    $pending_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($pending_orders)) {
        // Check for available delivery boys in the same city
        $stmt = $db->prepare("SELECT db_id FROM delivery_boy WHERE current_status = 'available' AND LOWER(city) = ? AND db_status = 1 LIMIT 1");
        $stmt->bind_param("s", $restaurant_city);
        $stmt->execute();
        $delivery_boy = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($delivery_boy) {
            $db_id = $delivery_boy['db_id'];
            $order_id = $pending_orders[0]['order_id'];

            // Assign the delivery boy to the order
            $stmt = $db->prepare("UPDATE users_orders SET delivery_boy_id = ?, status = 'assigned' WHERE order_id = ? AND rs_id = ?");
            $stmt->bind_param("isi", $db_id, $order_id, $current_restaurant);
            $stmt->execute();
            $stmt->close();

            // Update delivery boy's status to busy
            $stmt = $db->prepare("UPDATE delivery_boy SET current_status = 'busy' WHERE db_id = ?");
            $stmt->bind_param("i", $db_id);
            $stmt->execute();
            $stmt->close();

            return true;
        } else {
            // Check if any delivery boys exist in the city
            $stmt = $db->prepare("SELECT db_name, db_phone, current_status FROM delivery_boy WHERE LOWER(city) = ?");
            $stmt->bind_param("s", $restaurant_city);
            $stmt->execute();
            $delivery_boys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($delivery_boys)) {
                $_SESSION['no_delivery_boy'] = "No delivery boys registered in $display_city. Order #{$pending_orders[0]['order_id']} will be assigned when a delivery boy is added.";
            } else {
                $delivery_boy_list = array_map(function($boy) {
                    return htmlspecialchars($boy['db_name']) . ': ' . htmlspecialchars($boy['db_phone']) . ' (' . htmlspecialchars($boy['current_status']) . ')';
                }, $delivery_boys);
                $delivery_boy_text = implode(', ', $delivery_boy_list);
                $_SESSION['no_delivery_boy'] = "No delivery boys available in $display_city. All are busy or offline. Registered delivery boys: $delivery_boy_text. Order #{$pending_orders[0]['order_id']} will be automatically assigned when a delivery boy becomes available.";
            }
            error_log("No available delivery boys in city: $restaurant_city for restaurant ID: $current_restaurant");
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $valid_statuses = ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'rejected'];

    $stmt = $db->prepare("SELECT status FROM users_orders WHERE order_id = ? AND rs_id = ?");
    $stmt->bind_param("si", $order_id, $current_restaurant);
    $stmt->execute();
    $current_status = $stmt->get_result()->fetch_assoc()['status'] ?? 'pending';
    $stmt->close();

    $allowed_transitions = [
        'pending' => ['accepted', 'rejected'],
        'accepted' => ['preparing', 'rejected'],
        'preparing' => ['ready_for_pickup', 'rejected'],
        'ready_for_pickup' => ['rejected'],
        'rejected' => []
    ];

    if (in_array($new_status, $valid_statuses) && (in_array($new_status, $allowed_transitions[$current_status] ?? []) || $new_status === $current_status)) {
        $stmt = $db->prepare("UPDATE users_orders SET status = ? WHERE order_id = ? AND rs_id = ?");
        $stmt->bind_param("ssi", $new_status, $order_id, $current_restaurant);
        $stmt->execute();
        $stmt->close();

        if ($new_status === 'ready_for_pickup') {
            // Check for available delivery boys in the restaurant's city
            $stmt = $db->prepare("SELECT COUNT(*) as available_count FROM delivery_boy WHERE current_status = 'available' AND LOWER(city) = (SELECT LOWER(city) FROM restaurant WHERE rs_id = ?) AND db_status = 1");
            $stmt->bind_param("i", $current_restaurant);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($result['available_count'] == 0) {
                // Check all delivery boys in the city
                $stmt = $db->prepare("SELECT db_name, db_phone, current_status FROM delivery_boy WHERE LOWER(city) = (SELECT LOWER(city) FROM restaurant WHERE rs_id = ?)");
                $stmt->bind_param("i", $current_restaurant);
                $stmt->execute();
                $delivery_boys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                $restaurant_city = $restaurant['city'];
                if (empty($delivery_boys)) {
                    $_SESSION['no_delivery_boy'] = "No delivery boys registered in $restaurant_city. Order #$order_id will be assigned when a delivery boy is added.";
                } else {
                    $delivery_boy_list = array_map(function($boy) {
                        return htmlspecialchars($boy['db_name']) . ': ' . htmlspecialchars($boy['db_phone']) . ' (' . htmlspecialchars($boy['current_status']) . ')';
                    }, $delivery_boys);
                    $delivery_boy_text = implode(', ', $delivery_boy_list);
                    $_SESSION['no_delivery_boy'] = "No delivery boys available in $restaurant_city. All are busy or offline. Registered delivery boys: $delivery_boy_text. Order #$order_id will be automatically assigned when a delivery boy becomes available.";
                }
            } else {
                assignPendingOrders($db, $current_restaurant);
            }
        }
    }
    // Check for any pending assignments after status update
    assignPendingOrders($db, $current_restaurant);
    header("Location: all_orders.php?rs_id=$current_restaurant");
    exit();
}

// Check and assign any pending orders on page load
assignPendingOrders($db, $current_restaurant);

$stmt = $db->prepare("
    SELECT uo.order_id, uo.u_id, uo.title, uo.quantity, uo.price, uo.status, uo.date, u.f_name, u.l_name, u.phone,
           db.db_name AS delivery_boy_name, db.db_phone AS delivery_boy_phone, om.message
    FROM users_orders uo
    JOIN users u ON uo.u_id = u.u_id
    LEFT JOIN delivery_boy db ON uo.delivery_boy_id = db.db_id
    LEFT JOIN order_messages om ON uo.order_id = om.order_id
    WHERE uo.rs_id = ?
    ORDER BY uo.date DESC
");
$stmt->bind_param("i", $current_restaurant);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Orders - <?php echo htmlspecialchars($restaurant['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
            color: #333;
        }
        .container-fluid {
            padding: 20px;
        }
        .header {
            background: #007bff;
            padding: 15px 30px;
            color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header h2 {
            font-size: 1.8rem;
            margin: 0;
        }
        .header p {
            font-size: 1rem;
            margin: 5px 0 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .card-header {
            background: #28a745;
            color: #fff;
            padding: 15px;
            font-size: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .card-body {
            padding: 20px;
        }
        .table {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        .thead-dark th {
            background: #343a40;
            color: #fff;
            padding: 10px;
        }
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            color: #fff;
            font-size: 0.9rem;
        }
        .status-pending { background: #f39c12; }
        .status-accepted { background: #00b4d8; }
        .status-preparing { background: #e67e22; }
        .status-ready_for_pickup { background: #17a2b8; }
        .status-rejected { background: #dc3545; }
        .status-assigned { background: #6f42c1; }
        .status-delivered { background: #28a745; }
        .status-closed { background: #007bff; }
        .status-null { background: #6c757d; }
        .form-select {
            padding: 8px;
            border-radius: 5px;
        }
        .btn-custom {
            background: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
        }
        .btn-custom:hover {
            background: #0056b3;
            color: #fff;
        }
        .text-muted {
            color: #6c757d;
        }
        .delivery-boy {
            color: #28a745;
            font-weight: bold;
        }
        .message-cell {
            max-width: 200px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h2>All Orders</h2>
                    <p>Manage orders for <?php echo htmlspecialchars($restaurant['title']); ?> (<?php echo htmlspecialchars($restaurant['city']); ?>)</p>
                </div>
                <div class="col text-end">
                    <a href="restaurant_owner_dashboard.php?rs_id=<?php echo $current_restaurant; ?>" class="btn btn-custom">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">Orders Overview</div>
            <div class="card-body">
                <?php if (isset($_SESSION['no_delivery_boy'])): ?>
                    <script>
                        Swal.fire({
                            title: 'Delivery Boy Assignment Issue',
                            html: '<?php echo str_replace(", ", "<br>", htmlspecialchars($_SESSION['no_delivery_boy'])); ?>',
                            icon: 'warning',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'OK'
                        });
                    </script>
                    <?php unset($_SESSION['no_delivery_boy']); ?>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Dish</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Delivery Boy</th>
                                <th>Message</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="11" class="text-center text-muted py-4">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['f_name'] . ' ' . $order['l_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($order['title']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td>₹<?php echo htmlspecialchars($order['price']); ?></td>
                                        <td><span class="status-badge status-<?php echo $order['status'] ?? 'null'; ?>"><?php echo ucfirst(htmlspecialchars($order['status'] ?? 'Pending')); ?></span></td>
                                        <td><?php echo htmlspecialchars($order['date']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($order['delivery_boy_name']) && !empty($order['delivery_boy_phone'])) {
                                                echo '<span class="delivery-boy">' . htmlspecialchars($order['delivery_boy_name']) . ': ' . htmlspecialchars($order['delivery_boy_phone']) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="message-cell"><?php echo htmlspecialchars($order['message'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $current_status = $order['status'] ?? 'pending';
                                            $editable_statuses = ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'rejected'];
                                            if (in_array($current_status, $editable_statuses)):
                                            ?>
                                                <form method="post" class="d-inline status-form" data-order-id="<?php echo $order['order_id']; ?>">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <select name="status" class="form-select" onchange="confirmStatusChange(this)">
                                                        <option value="pending" <?php echo $current_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="accepted" <?php echo $current_status == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                        <option value="preparing" <?php echo $current_status == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                        <option value="ready_for_pickup" <?php echo $current_status == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                                        <option value="rejected" <?php echo $current_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Not editable</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script>
        function confirmStatusChange(selectElement) {
            const newStatus = selectElement.value;
            const form = selectElement.closest('.status-form');
            const orderId = form.dataset.orderId;

            Swal.fire({
                title: 'Confirm Status Update',
                text: `Are you sure you want to change Order #${orderId} to "${newStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Update',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                } else {
                    selectElement.value = '<?php echo $current_status; ?>' || 'pending';
                }
            });
        }

        // Auto-refresh to check for available delivery boys every 10 seconds
        setInterval(function() {
            fetch('all_orders.php?rs_id=<?php echo $current_restaurant; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'check_assignment=1'
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            });
        }, 10000);
    </script>
</body>
</html>
<?php
// Handle AJAX check for assignment
if (isset($_POST['check_assignment'])) {
    assignPendingOrders($db, $current_restaurant);
    exit();
}
?>