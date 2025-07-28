<?php
session_start();
include("connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$u_id = $_SESSION['user_id'];

// Handle cart updates
if (isset($_POST['update-cart'])) {
    foreach ($_POST['quantity'] as $d_id => $val) {
        $d_id = intval($d_id);
        $val = intval($val);
        if ($val <= 0) {
            $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ? AND d_id = ?");
            $stmt->bind_param('ii', $u_id, $d_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE u_id = ? AND d_id = ?");
            $stmt->bind_param('iii', $val, $u_id, $d_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle item removal
if (isset($_GET["action"]) && $_GET["action"] == "remove" && isset($_GET["id"])) {
    $d_id = intval($_GET["id"]);
    $stmt = $db->prepare("DELETE FROM cart WHERE u_id = ? AND d_id = ?");
    $stmt->bind_param('ii', $u_id, $d_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch cart items with restaurant name
$stmt = $db->prepare("
    SELECT c.d_id, c.res_id, c.quantity, c.price, d.title, r.title AS restaurant_name
    FROM cart c 
    JOIN dishes d ON c.d_id = d.d_id 
    JOIN restaurant r ON c.res_id = r.rs_id
    WHERE c.u_id = ?
");
$stmt->bind_param('i', $u_id);
$stmt->execute();
$cart_items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        /* .page-wrapper {
            margin-top: 80px;
        } */
        .nav-link i {
            margin-right: 5px;
        }
        #cart-notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #dc3545;
            border-radius: 50%;
            display: none;
        }
        @media (max-width: 768px) {
            .navbar-brand img {
                max-width: 150px;
            }
            .table {
                width: 100%;
                overflow-x: auto;
                display: block;
            }
            .table thead {
                display: none;
            }
            .table tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                padding: 10px;
            }
            .table tbody td {
                display: block;
                text-align: left;
                padding: 5px 0;
                border: none;
            }
            .table tbody td:before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                width: 50%;
            }
            .table tbody td:last-child {
                text-align: center;
            }
            .table input[type="number"] {
                width: 70px;
            }
            .btn {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-wrapper">
        <div class="container m-t-30">
            <h1>Your Shopping Cart</h1>

            <form method="post" action="cart.php">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-utensils"></i> Restaurant</th>
                            <th><i class="fas fa-box-open"></i> Item</th>
                            <th><i class="fa fa-indian-rupee"></i> Price</th>
                            <th><i class="fas fa-sort-numeric-up"></i> Quantity</th>
                            <th><i class="fas fa-calculator"></i> Subtotal</th>
                            <th><i class="fas fa-trash-alt"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_price = 0;
                        $first_item_res_id = null;

                        if ($cart_items->num_rows > 0) {
                            while ($item = $cart_items->fetch_assoc()) {
                                if ($first_item_res_id === null) {
                                    $first_item_res_id = $item['res_id'];
                                }
                                $item_price = $item["quantity"] * $item["price"];
                        ?>
                        <tr>
                            <td data-label="Restaurant"><?php echo htmlspecialchars($item["restaurant_name"]); ?></td>
                            <td data-label="Item"><?php echo htmlspecialchars($item["title"]); ?></td>
                            <td data-label="Price">₹<?php echo number_format($item["price"], 2); ?></td>
                            <td data-label="Quantity"><input type="number" name="quantity[<?php echo $item["d_id"]; ?>]" value="<?php echo htmlspecialchars($item["quantity"]); ?>" min="0" class="form-control"></td>
                            <td data-label="Subtotal">₹<?php echo number_format($item_price, 2); ?></td>
                            <td data-label="Action">
                                <a href="javascript:void(0);" onclick="confirmRemove(<?php echo $item["d_id"]; ?>)" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Remove</a>
                            </td>
                        </tr>
                        <?php
                                $total_price += $item_price;
                            }
                        ?>
                        <tr>
                            <td colspan="4" align="right"><strong>Total:</strong></td>
                            <td>₹<?php echo number_format($total_price, 2); ?></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" align="right">
                                <button type="submit" name="update-cart" class="btn btn-info"><i class="fas fa-sync-alt"></i> Update Cart</button>
                                <a href="checkout.php?res_id=<?php echo htmlspecialchars($first_item_res_id); ?>&action=check" class="btn btn-success"><i class="fas fa-check"></i> Checkout</a>
                            </td>
                        </tr>
                        <?php
                        } else {
                        ?>
                        <tr>
                            <td colspan="6">Your cart is empty.</td>
                        </tr>
                        <?php
                        }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
     <?php include 'chatbot.php'; ?>

    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            const isLoggedInJS = <?php echo json_encode(!empty($_SESSION["user_id"])); ?>;
            const userIdJS = <?php echo json_encode($u_id); ?>;

            // Update Cart Count
            function updateCartCount() {
                if (!isLoggedInJS || !userIdJS) {
                    $('#cart-notification-dot').hide();
                    console.log('Not logged in or no user ID, hiding cart dot');
                    return;
                }

                $.ajax({
                    url: 'get_cart_count.php',
                    method: 'GET',
                    data: { user_id: userIdJS },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Cart count response:', response);
                        const cartDot = $('#cart-notification-dot');
                        if (cartDot.length) {
                            if (response && response.count !== undefined && parseInt(response.count) > 0) {
                                cartDot.show();
                                console.log('Showing cart dot, count:', response.count);
                            } else {
                                cartDot.hide();
                                console.log('Hiding cart dot, count:', response.count || 0);
                            }
                        } else {
                            console.error('Cart notification dot element not found');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Cart AJAX error:', status, error, xhr.responseText);
                        $('#cart-notification-dot').hide();
                    }
                });
            }
            updateCartCount();

            // Update User City Display
            function updateUserCityDisplay() {
                const sessionCity = <?php echo json_encode($_SESSION['selected_city'] ?? null); ?>;
                const userCitySpan = $('#user-city');

                if (isLoggedInJS && userIdJS) {
                    $.ajax({
                        url: 'get_user_city.php',
                        method: 'POST',
                        data: { user_id: userIdJS },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success' && data.city) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${data.city}`);
                            } else if (sessionCity) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                            } else {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('City AJAX error:', status, error);
                            if (sessionCity) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                            } else {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`);
                            }
                        }
                    });
                } else if (sessionCity) {
                    userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                } else {
                    userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`);
                }
            }
            updateUserCityDisplay();

            // Confirm Remove Item
            window.confirmRemove = function(d_id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to remove this item from your cart?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'cart.php?action=remove&id=' + d_id;
                        updateCartCount(); // Update dot after removal
                    }
                });
            };
        });
    </script>
</body>
</html>