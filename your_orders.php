<?php
session_start();
include("connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine User Login Status
$user_is_logged_in = !empty($_SESSION["user_id"]);
$user_id = $user_is_logged_in ? intval($_SESSION["user_id"]) : null;

// Redirect if not logged in
if (!$user_is_logged_in) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="My Orders">
    <meta name="author" content="YourAppName">
    <link rel="icon" href="images/favicon.ico">
    <title>My Orders</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/animsition.min.css">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6fa;
            color: #333;
        }
        .page-wrapper {
            /* margin-top: 80px; */
            min-height: 100vh;
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
        .inner-page-hero {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            padding: 50px 0;
            text-align: center;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .inner-page-hero h1 {
            font-weight: 600;
            color: #2c3e50;
        }
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 2rem;
        }
        .table thead th {
            background-color: #2c3e50;
            color: white;
            border: none;
            font-weight: 500;
        }
        .table tbody td {
            border: none;
            vertical-align: middle;
        }
        .table-bordered {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .btn {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .footer {
            background-color: #2c3e50;
            color: rgba(255, 255, 255, 0.7);
            padding: 30px 0;
            border-radius: 20px 20px 0 0;
        }
        .footer h5 {
            color: white;
            font-weight: 600;
        }
        .footer a {
            color: rgba(255, 255, 255, 0.7);
        }
        .footer a:hover {
            color: rgba(255, 255, 255, 1);
            text-decoration: none;
        }
        .swal2-popup {
            font-family: 'Poppins', sans-serif;
        }
        .rating {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        .star, .restaurant-star, .delivery-boy-star {
            cursor: pointer;
            margin: 5px;
            color: #6c757d;
            transition: color 0.3s ease;
        }
        .star.selected, .restaurant-star.selected, .delivery-boy-star.selected {
            color: #ff8f00;
        }
        .swal2-textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
        }
        #rating-value, #restaurant-rating-value, #delivery-boy-rating-value {
            text-align: center;
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        #restaurant-rating, #delivery-boy-rating {
            margin-top: 20px;
        }
        .badge-secondary { background-color: #6c757d; }
        .badge-primary { background-color: #007bff; }
        .badge-warning { background-color: #ffc107; }
        .badge-success { background-color: #28a745; }
        .badge-info { background-color: #17a2b8; }
        .badge-danger { background-color: #dc3545; }
        .status-updated {
            animation: highlight 1.5s ease-in-out;
        }
        @keyframes highlight {
            0% { background-color: transparent; }
            50% { background-color: rgba(40, 167, 69, 0.3); }
            100% { background-color: transparent; }
        }
        .status-tick {
            display: inline-block;
            margin-left: 5px;
            color: #28a745;
            animation: tickAnimation 0.5s ease-in-out;
        }
        @keyframes tickAnimation {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.9rem;
            }
            .btn {
                padding: 0.5rem 1rem;
            }
            .inner-page-hero {
                padding: 30px 0;
            }
        }
    </style>
</head>
<body>
    <?php
    if (file_exists('header.php')) {
        include 'header.php';
    } else {
        echo '<div class="alert alert-danger text-center">Error: header.php not found.</div>';
    }
    ?>

    <div class="page-wrapper">
        <div class="inner-page-hero">
            <div class="container">
                <h1>My Orders</h1>
            </div>
        </div>
        <section class="restaurants-page">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th><i class="fas fa-box"></i> Item</th>
                                        <th><i class="fas fa-utensils"></i> Restaurant</th>
                                        <th><i class="fas fa-sort-numeric-up"></i> Quantity</th>
                                        <th>₹ Price</th>
                                        <th><i class="fas fa-exclamation-triangle"></i> Status</th>
                                        <th><i class="fas fa-calendar-alt"></i> Date</th>
                                        <th><i class="fas fa-truck"></i> Rider Details</th>
                                        <th><i class="fas fa-cog"></i> Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_res = mysqli_query($db, "SELECT uo.*, db.db_name AS rider_name, db.db_phone AS rider_phone, db.db_id AS delivery_boy_id, r.rs_id AS restaurant_id, r.title AS restaurant_name 
                                                                    FROM users_orders uo 
                                                                    LEFT JOIN delivery_boy db ON uo.delivery_boy_id = db.db_id 
                                                                    LEFT JOIN restaurant r ON uo.rs_id = r.rs_id 
                                                                    WHERE uo.u_id='$user_id'");
                                    if (mysqli_num_rows($query_res) == 0) {
                                        echo '<tr><td colspan="8"><center>You have No orders Placed yet.</center></td></tr>';
                                    } else {
                                        while ($row = mysqli_fetch_array($query_res)) {
                                            $order_id = $row['o_id'];
                                            $status = $row['status'];
                                            $delivery_boy_id = $row['delivery_boy_id'];
                                            $restaurant_id = $row['restaurant_id'];
                                            $restaurant_name = $row['restaurant_name'];
                                            $rated_check = mysqli_query($db, "SELECT * FROM restaurant_ratings WHERE rs_id = '$restaurant_id' AND u_id = '$user_id' AND rating_date >= '{$row['date']}'");
                                            $is_rated = mysqli_num_rows($rated_check) > 0;
                                    ?>
                                            <tr data-delivery-boy-id="<?php echo $delivery_boy_id; ?>" data-restaurant-id="<?php echo $restaurant_id; ?>">
                                                <td data-column="Item"><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td data-column="Restaurant"><?php echo htmlspecialchars($restaurant_name); ?></td>
                                                <td data-column="Quantity"><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                <td data-column="price">₹<?php echo htmlspecialchars($row['price']); ?></td>
                                                <td data-column="status">
                                                    <span class="badge status-badge status-<?php echo strtolower($status); ?>" data-order-id="<?php echo $order_id; ?>" data-current-status="<?php echo strtolower($status); ?>">
                                                        <?php
                                                        switch ($status) {
                                                            case "":
                                                            case "NULL":
                                                                echo '<i class="fas fa-bars"></i> Dispatch';
                                                                break;
                                                            case "pending":
                                                                echo '<i class="fas fa-clock"></i> Pending';
                                                                break;
                                                            case "accepted":
                                                                echo '<i class="fas fa-check"></i> Accepted';
                                                                break;
                                                            case "preparing":
                                                                echo '<i class="fas fa-utensils"></i> Preparing';
                                                                break;
                                                            case "ready_for_pickup":
                                                                echo '<i class="fas fa-box-open"></i> Ready for Pickup';
                                                                break;
                                                            case "assigned":
                                                                echo '<i class="fas fa-truck"></i> Assigned to Rider';
                                                                break;
                                                            case "in process":
                                                                echo '<i class="fas fa-cog fa-spin"></i> On The Way!';
                                                                break;
                                                            case "delivered":
                                                            case "closed":
                                                                echo '<i class="fas fa-check-circle"></i> Delivered';
                                                                break;
                                                            case "rejected":
                                                                echo '<i class="fa fa-times-circle"></i> Cancelled';
                                                                break;
                                                            default:
                                                                echo '<i class="fas fa-question"></i> Unknown';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td data-column="Date"><?php echo htmlspecialchars($row['date']); ?></td>
                                                <td data-column="Rider Details">
                                                    <?php
                                                    if ($row['rider_name'] != NULL && $row['rider_phone'] != NULL) {
                                                        echo "Name: " . htmlspecialchars($row['rider_name']) . "<br>";
                                                        echo "Phone: " . htmlspecialchars($row['rider_phone']);
                                                    } else {
                                                        echo "Not Assigned Yet";
                                                    }
                                                    ?>
                                                </td>
                                                <td data-column="Action">
                                                    <?php if (($status == 'closed' || $status == 'delivered') && !$is_rated) { ?>
                                                        <button class="btn btn-primary btn-sm rate-order"
                                                                data-order-id="<?php echo $order_id; ?>" 
                                                                data-delivery-boy-id="<?php echo $delivery_boy_id; ?>"
                                                                data-restaurant-id="<?php echo $restaurant_id; ?>">
                                                            <i class="fa fa-star"></i> Rate
                                                        </button>
                                                    <?php } elseif ($is_rated) { ?>
                                                        <span class="text-muted">Rated</span>
                                                    <?php } elseif (in_array($status, ['pending', 'accepted', 'preparing', 'ready_for_pickup'])) { ?>
                                                        <a href="delete_orders.php?order_del=<?php echo $row['o_id']; ?>"
                                                           class="btn btn-danger btn-sm delete-order">
                                                            <i class="fa fa-trash"></i> Cancel
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-muted">No actions available</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        if (file_exists('footer.php')) {
            include 'footer.php';
        } else {
            echo '<div class="alert alert-danger text-center">Error: footer.php not found.</div>';
        }
        ?>
         <?php include 'chatbot.php'; ?>
    </div>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            const isLoggedInJS = <?php echo json_encode($user_is_logged_in); ?>;
            const userIdJS = <?php echo json_encode($user_id); ?>;

            // Update User City Display
            function updateUserCityDisplay() {
                const sessionCity = <?php echo json_encode($_SESSION['selected_city'] ?? null); ?>;
                const userCitySpan = $('#user-city');

                if (!userCitySpan.length) {
                    console.error('User city element (#user-city) not found in header.php');
                    return;
                }

                if (isLoggedInJS && userIdJS) {
                    $.ajax({
                        url: 'get_user_city.php',
                        method: 'POST',
                        data: { user_id: userIdJS },
                        dataType: 'json',
                        success: function(data) {
                            console.log('City AJAX response:', data);
                            if (data.status === 'success' && data.city) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${data.city}`);
                            } else {
                                console.warn('No city in response, falling back to session or default');
                                if (sessionCity) {
                                    userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                                } else {
                                    userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`); // Default city
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('City AJAX error:', status, error, xhr.responseText);
                            if (sessionCity) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                            } else {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`); // Default city
                            }
                        }
                    });
                } else {
                    console.log('Not logged in, using session city or default');
                    if (sessionCity) {
                        userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                    } else {
                        userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`); // Default city
                    }
                }
            }
            updateUserCityDisplay();

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
                            if (response && typeof response.count !== 'undefined' && parseInt(response.count) > 0) {
                                cartDot.show();
                                console.log('Showing cart dot, count:', response.count);
                            } else {
                                cartDot.hide();
                                console.log('Hiding cart dot, count:', response.count || 0);
                            }
                        } else {
                            console.error('Cart notification dot element not found in header.php');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Cart AJAX error:', status, error, xhr.responseText);
                        $('#cart-notification-dot').hide();
                    }
                });
            }
            updateCartCount();

            // Delete Order Confirmation
            $('.delete-order').click(function(e) {
                e.preventDefault();
                var deleteUrl = $(this).attr('href');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to cancel this order?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, cancel it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = deleteUrl;
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: 'Your order has been cancelled.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        });
                    }
                });
            });

            // Rate Order Functionality
            $('.rate-order').click(function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                var deliveryBoyId = $(this).data('delivery-boy-id');
                var restaurantId = $(this).data('restaurant-id');
                Swal.fire({
                    title: 'Rate Your Experience',
                    html: `
                        <div id="restaurant-rating">
                            <h4>Rate Restaurant</h4>
                            <div class="rating">
                                <i class="fa fa-star fa-2x restaurant-star" data-rating="1"></i>
                                <i class="fa fa-star fa-2x restaurant-star" data-rating="2"></i>
                                <i class="fa fa-star fa-2x restaurant-star" data-rating="3"></i>
                                <i class="fa fa-star fa-2x restaurant-star" data-rating="4"></i>
                                <i class="fa fa-star fa-2x restaurant-star" data-rating="5"></i>
                            </div>
                            <p id="restaurant-rating-value">0 Stars</p>
                            <input type="hidden" id="selected-restaurant-rating" name="restaurant_rating" value="0">
                            <textarea id="restaurant-review-text" class="swal2-textarea" placeholder="Write your restaurant review here..."></textarea>
                        </div>
                        <div id="order-rating">
                            <h4>Rate Order (Optional)</h4>
                            <div class="rating">
                                <i class="fa fa-star fa-2x star" data-rating="1"></i>
                                <i class="fa fa-star fa-2x star" data-rating="2"></i>
                                <i class="fa fa-star fa-2x star" data-rating="3"></i>
                                <i class="fa fa-star fa-2x star" data-rating="4"></i>
                                <i class="fa fa-star fa-2x star" data-rating="5"></i>
                            </div>
                            <p id="rating-value">0 Stars</p>
                            <input type="hidden" id="selected-rating" name="rating" value="0">
                            <textarea id="review-text" class="swal2-textarea" placeholder="Write your order review here..."></textarea>
                        </div>
                        <div id="delivery-boy-rating">
                            <h4>Rate Delivery Boy${deliveryBoyId ? '' : ' (Not Applicable)'}</h4>
                            <div class="rating">
                                <i class="fa fa-star fa-2x delivery-boy-star" data-rating="1"></i>
                                <i class="fa fa-star fa-2x delivery-boy-star" data-rating="2"></i>
                                <i class="fa fa-star fa-2x delivery-boy-star" data-rating="3"></i>
                                <i class="fa fa-star fa-2x delivery-boy-star" data-rating="4"></i>
                                <i class="fa fa-star fa-2x delivery-boy-star" data-rating="5"></i>
                            </div>
                            <p id="delivery-boy-rating-value">0 Stars</p>
                            <input type="hidden" id="selected-delivery-boy-rating" name="delivery_boy_rating" value="0">
                        </div>
                    `,
                    confirmButtonText: 'Submit Rating',
                    confirmButtonColor: '#007bff',
                    focusConfirm: false,
                    preConfirm: () => {
                        const restaurantRating = document.getElementById('selected-restaurant-rating').value;
                        const restaurantReview = document.getElementById('restaurant-review-text').value;
                        const rating = document.getElementById('selected-rating').value;
                        const reviewText = document.getElementById('review-text').value;
                        const deliveryBoyRating = document.getElementById('selected-delivery-boy-rating').value;
                        if (restaurantRating === '0') {
                            Swal.showValidationMessage('Please select a rating for the restaurant.');
                            return false;
                        }
                        if (deliveryBoyId && deliveryBoyRating === '0') {
                            Swal.showValidationMessage('Please select a rating for the delivery boy.');
                            return false;
                        }
                        return {
                            restaurantRating: restaurantRating,
                            restaurantReview: restaurantReview,
                            rating: rating,
                            reviewText: reviewText,
                            deliveryBoyRating: deliveryBoyRating
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const restaurantRating = result.value.restaurantRating;
                        const restaurantReview = result.value.restaurantReview;
                        const rating = result.value.rating;
                        const reviewText = result.value.reviewText;
                        const deliveryBoyRating = result.value.deliveryBoyRating;
                        $.ajax({
                            url: 'submit_rating.php',
                            type: 'POST',
                            data: {
                                order_id: orderId,
                                rating: rating,
                                review: reviewText,
                                delivery_boy_id: deliveryBoyId,
                                delivery_boy_rating: deliveryBoyRating,
                                restaurant_id: restaurantId,
                                restaurant_rating: restaurantRating,
                                restaurant_review: restaurantReview
                            },
                            success: function(response) {
                                console.log('Rating submission response:', response);
                                if (response === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Thank you!',
                                        text: 'Your ratings have been submitted.',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Rating AJAX error:', status, error, xhr.responseText);
                                Swal.fire('Error', 'An error occurred while submitting your ratings.', 'error');
                            }
                        });
                    }
                });
                $(document).on('click', '.restaurant-star', function() {
                    var ratingValue = $(this).data('rating');
                    $('#selected-restaurant-rating').val(ratingValue);
                    $('#restaurant-rating-value').text(ratingValue + ' Stars');
                    $('.restaurant-star').removeClass('selected');
                    $(this).prevAll().addBack().addClass('selected');
                });
                $(document).on('click', '.star', function() {
                    var ratingValue = $(this).data('rating');
                    $('#selected-rating').val(ratingValue);
                    $('#rating-value').text(ratingValue + ' Stars');
                    $('.star').removeClass('selected');
                    $(this).prevAll().addBack().addBack().addClass('selected');
                });
                $(document).on('click', '.delivery-boy-star', function() {
                    var ratingValue = $(this).data('rating');
                    $('#selected-delivery-boy-rating').val(ratingValue);
                    $('#delivery-boy-rating-value').text(ratingValue + ' Stars');
                    $('.delivery-boy-star').removeClass('selected');
                    $(this).prevAll().addBack().addClass('selected');
                });
            });

            // Poll Order Statuses
            function pollOrderStatus() {
                $('.status-badge').each(function() {
                    var badge = $(this);
                    var orderId = badge.data('order-id');
                    var currentStatus = badge.data('current-status');
                    $.ajax({
                        url: 'get_order_status.php',
                        type: 'GET',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(data) {
                            console.log('Order status response for order ' + orderId + ':', data);
                            if (data.status && data.status.toLowerCase() !== currentStatus) {
                                var newStatus = data.status.toLowerCase();
                                var badgeClass = '';
                                var badgeText = '';
                                switch (newStatus) {
                                    case '':
                                    case 'null':
                                        badgeClass = 'badge-info';
                                        badgeText = '<i class="fas fa-bars"></i> Dispatch';
                                        break;
                                    case 'pending':
                                        badgeClass = 'badge-secondary';
                                        badgeText = '<i class="fas fa-clock"></i> Pending';
                                        break;
                                    case 'accepted':
                                        badgeClass = 'badge-primary';
                                        badgeText = '<i class="fas fa-check"></i> Accepted';
                                        break;
                                    case 'preparing':
                                        badgeClass = 'badge-warning';
                                        badgeText = '<i class="fas fa-utensils"></i> Preparing';
                                        break;
                                    case 'ready_for_pickup':
                                        badgeClass = 'badge-success';
                                        badgeText = '<i class="fas fa-box-open"></i> Ready for Pickup';
                                        break;
                                    case 'assigned':
                                        badgeClass = 'badge-info';
                                        badgeText = '<i class="fas fa-truck"></i> Assigned to Rider';
                                        break;
                                    case 'in process':
                                        badgeClass = 'badge-warning';
                                        badgeText = '<i class="fas fa-cog fa-spin"></i> On The Way!';
                                        break;
                                    case 'delivered':
                                    case 'closed':
                                        badgeClass = 'badge-success';
                                        badgeText = '<i class="fas fa-check-circle"></i> Delivered <i class="fas fa-check status-tick"></i>';
                                        break;
                                    case 'rejected':
                                        badgeClass = 'badge-danger';
                                        badgeText = '<i class="fas fa-times-circle"></i> Cancelled';
                                        break;
                                    default:
                                        badgeClass = 'badge-secondary';
                                        badgeText = '<i class="fas fa-question"></i> Unknown';
                                }
                                badge.removeClass().addClass('badge status-badge status-' + newStatus + ' ' + badgeClass);
                                badge.html(badgeText);
                                badge.data('current-status', newStatus);
                                badge.closest('td').addClass('status-updated');
                                var actionCell = badge.closest('tr').find('td[data-column="Action"]');
                                if (newStatus === 'delivered' || newStatus === 'closed') {
                                    $.ajax({
                                        url: 'check_rating.php',
                                        type: 'POST',
                                        data: { order_id: orderId, user_id: userIdJS },
                                        success: function(response) {
                                            console.log('Check rating response for order ' + orderId + ':', response);
                                            if (response === 'rated') {
                                                actionCell.html('<span class="text-muted">Rated</span>');
                                            } else {
                                                actionCell.html('<button class="btn btn-primary btn-sm rate-order" data-order-id="' + orderId + '" data-delivery-boy-id="' + badge.closest('tr').data('delivery-boy-id') + '" data-restaurant-id="' + badge.closest('tr').data('restaurant-id') + '"><i class="fa fa-star"></i> Rate</button>');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Check rating AJAX error:', status, error, xhr.responseText);
                                        }
                                    });
                                } else if (['pending', 'accepted', 'preparing', 'ready_for_pickup'].includes(newStatus)) {
                                    actionCell.html('<a href="delete_orders.php?order_del=' + orderId + '" class="btn btn-danger btn-sm delete-order"><i class="fa fa-trash"></i> Cancel</a>');
                                } else {
                                    actionCell.html('<span class="text-muted">No actions available</span>');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Status polling error for order ' + orderId + ':', status, error, xhr.responseText);
                        }
                    });
                });
            }
            setInterval(pollOrderStatus, 5000);
            pollOrderStatus();
        });
    </script>
</body>
</html>