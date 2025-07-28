<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION["db_id"])) {
    header("Location: index.php");
    exit();
}

$db_id = $_SESSION["db_id"];
$order_id = isset($_GET['o_id']) && is_numeric($_GET['o_id']) ? (int)$_GET['o_id'] : null;

if (!$order_id) {
    displayErrorAndRedirect("Invalid Order ID.", "delivery_boy_dashboard.php");
}

$order = fetchOrderDetails($db, $order_id, $db_id);
if (!$order) {
    displayErrorAndRedirect("Order not found or not assigned to you.", "delivery_boy_dashboard.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    handleStatusUpdateRequest($db, $db_id, $order_id, $_POST['new_status']);
}

function displayErrorAndRedirect(string $message, string $redirectUrl, int $delay = 3): void {
    echo "<div class='error-message'>$message Redirecting in $delay seconds...</div>";
    header("refresh:$delay;url=$redirectUrl");
    exit();
}

function fetchOrderDetails(mysqli $db, int $order_id, int $db_id): ?array {
    $sql = "SELECT users_orders.*, users.username, users.phone, users.address 
            FROM users_orders 
            INNER JOIN users ON users_orders.u_id = users.u_id 
            WHERE users_orders.o_id = ? AND users_orders.delivery_boy_id = ?";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        die("Database error: " . mysqli_error($db));
    }
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $db_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $order;
}

function handleStatusUpdateRequest(mysqli $db, int $db_id, int $order_id, string $new_status): void {
    $allowed_statuses = ["closed", "rejected"];
    if (!in_array($new_status, $allowed_statuses)) {
        echo "<script>Swal.fire({icon: 'error', title: 'Invalid Status', text: 'Please select a valid status.'});</script>";
        return;
    }

    mysqli_begin_transaction($db);
    try {
        $updateOrderSql = "UPDATE users_orders SET status = ? WHERE o_id = ? AND delivery_boy_id = ?";
        $updateOrderStmt = mysqli_prepare($db, $updateOrderSql);
        mysqli_stmt_bind_param($updateOrderStmt, "sii", $new_status, $order_id, $db_id);
        $orderSuccess = mysqli_stmt_execute($updateOrderStmt);
        $orderAffectedRows = mysqli_stmt_affected_rows($updateOrderStmt);
        mysqli_stmt_close($updateOrderStmt);

        if (!$orderSuccess || $orderAffectedRows == 0) {
            throw new Exception("Failed to update order status.");
        }

        $updateBoySql = "UPDATE delivery_boy SET current_status = 'available' WHERE db_id = ?";
        $updateBoyStmt = mysqli_prepare($db, $updateBoySql);
        mysqli_stmt_bind_param($updateBoyStmt, "i", $db_id);
        $boySuccess = mysqli_stmt_execute($updateBoyStmt);
        mysqli_stmt_close($updateBoyStmt);

        if (!$boySuccess) {
            throw new Exception("Failed to update delivery boy status.");
        }

        mysqli_commit($db);
        $display_status = $new_status === 'closed' ? 'Delivered' : 'Cancelled';
        echo "<script>Swal.fire({
            icon: 'success',
            title: 'Success!',
            html: 'Order has been marked as <strong>$display_status</strong>. Delivery status updated.',
            showConfirmButton: true,
            timer: 3000,
            timerProgressBar: true,
            didClose: () => { window.location.href='delivery_boy_dashboard.php'; }
        });</script>";
    } catch (Exception $e) {
        mysqli_rollback($db);
        echo "<script>Swal.fire({icon: 'error', title: 'Update Failed', text: '{$e->getMessage()}'});</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh; 
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            padding: 30px; 
            position: relative;
        }
        h1 { 
            color: #2c3e50; 
            text-align: center; 
            margin-bottom: 30px; 
            font-size: 2.2em; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        .order-details { 
            background: #f8f9fa; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            border-left: 5px solid #3498db;
            transition: transform 0.2s ease;
        }
        .order-details:hover {
            transform: translateY(-5px);
        }
        .order-details p { 
            margin: 12px 0; 
            font-size: 1.1em; 
            display: flex; 
            align-items: center; 
        }
        .order-details p i { 
            margin-right: 10px; 
            color: #3498db; 
            width: 20px; 
        }
        .error-message { 
            text-align: center; 
            color: #e74c3c; 
            padding: 20px; 
            background: #ffebee; 
            border-radius: 8px; 
            margin: 20px 0;
        }
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000;
        }
        .modal-content { 
            background: white; 
            width: 90%; 
            max-width: 400px; 
            margin: 20% auto; 
            padding: 25px; 
            border-radius: 10px; 
            position: relative; 
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { 
            from { transform: translateY(-50px); opacity: 0; } 
            to { transform: translateY(0); opacity: 1; } 
        }
        .close { 
            position: absolute; 
            right: 15px; 
            top: 10px; 
            font-size: 24px; 
            color: #7f8c8d; 
            cursor: pointer; 
        }
        .close:hover { color: #e74c3c; }
        label { 
            font-weight: 600; 
            color: #2c3e50; 
            margin-bottom: 10px; 
            display: block; 
        }
        select { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 1em; 
            margin-bottom: 20px; 
            transition: border-color 0.3s;
        }
        select:focus { border-color: #3498db; outline: none; }
        .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            margin: 5px;
        }
        .btn-primary { 
            background: #3498db; 
            color: white; 
            border: none; 
        }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { 
            background: #ecf0f1; 
            color: #2c3e50; 
            border: 2px solid #ddd; 
        }
        .btn-secondary:hover { background: #dfe4ea; }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        @media (max-width: 600px) { 
            .container { padding: 20px; } 
            h1 { font-size: 1.8em; } 
            .order-details p { font-size: 1em; } 
            .button-group { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Order Details</h1>
        <div class="order-details">
            <p><i class="fas fa-hashtag"></i><strong>Order ID:</strong> <?php echo htmlspecialchars($order["o_id"]); ?></p>
            <p><i class="fas fa-user"></i><strong>Customer:</strong> <?php echo htmlspecialchars($order["username"]); ?></p>
            <p><i class="fas fa-phone"></i><strong>Phone:</strong> <?php echo htmlspecialchars($order["phone"]); ?></p>
            <p><i class="fas fa-map-marker-alt"></i><strong>Address:</strong> <?php echo htmlspecialchars($order["address"]); ?></p>
            <p><i class="fas fa-box"></i><strong>Item:</strong> <?php echo htmlspecialchars($order["title"]); ?> (Qty: <?php echo htmlspecialchars($order["quantity"]); ?>)</p>
            <p><i class="fas fa-rupee-sign"></i><strong>Price:</strong> ₹<?php echo number_format($order["price"], 2); ?></p>
            <p><i class="fas fa-info-circle"></i><strong>Status:</strong> <?php echo htmlspecialchars($order["status"] ?: "Pending"); ?></p>
        </div>

        <div class="button-group">
            <button class="btn btn-primary" onclick="document.getElementById('statusModal').style.display='block'">Update Status</button>
            <a href="delivery_boy_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div id="statusModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('statusModal').style.display='none'">×</span>
                <h2>Update Order Status</h2>
                <form method="post">
                    <label for="new_status">New Status:</label>
                    <select name="new_status" id="new_status" required>
                        <option value="" disabled selected>Select status</option>
                        <option value="closed">Delivered</option>
                        <option value="rejected">Cancelled</option>
                    </select>
                    <input type="hidden" name="update_status" value="1">
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($db); ?>