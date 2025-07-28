<?php
include("connection/connect.php"); // Ensure this path is correct and $db is initialized
error_reporting(0); // For production, consider E_ALL & ini_set('display_errors', 0); error_log() for errors
session_start();

// Redirect if user is not logged in, as recommendations are personalized
if (empty($_SESSION["user_id"])) {
    header("location:login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);

// --- Recommendation System Logic (Copied from index.php logic) ---
function get_user_recommendations_from_python_for_page($current_user_id_param) {
    global $db;

    if (!$current_user_id_param) {
        return [];
    }

    // Path to your python executable and script
    $python_executable = 'python'; // OR full path like 'C:\Users\mukut\AppData\Local\Microsoft\WindowsApps\PythonSoftwareFoundation.Python.3.12_qbz5n2kfra8p0\python.exe';
    $python_script_path = __DIR__ . '/recommendations/generate_recommendations.py';
    $csv_data_file = __DIR__ . '/recommendations/orders_data.csv';

    // Ensure CSV data file exists (It's best to run export_orders.php via a scheduled task)
    if (!file_exists($csv_data_file)) {
        // You could attempt to run export_orders.php here for testing, but it's not ideal for page load.
        // For example: shell_exec("php " . escapeshellarg(__DIR__ . '/recommendations/export_orders.php') . ' 2>&1');
        // if (!file_exists($csv_data_file)) {
        //     error_log("Recommendation (Page): orders_data.csv not found even after attempted export. Ensure export_orders.php runs.");
        //     return [];
        // }
        error_log("Recommendation (Page): orders_data.csv not found. Please run export_orders.php first.");
        return []; // If no CSV, cannot generate recommendations
    }
    
    $command = escapeshellcmd("\"$python_executable\" \"$python_script_path\"");
    $python_output_json = shell_exec($command . ' 2>&1');

    if ($python_output_json === null) {
        error_log("Recommendation (Page): Python script execution failed or produced NO output. Command: $command");
        return [];
    }

    $all_user_recommendations = json_decode($python_output_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Recommendation (Page): Error decoding JSON from Python. JSON Error: " . json_last_error_msg() . ". Python Output: " . $python_output_json);
        return [];
    }
    
    if (isset($all_user_recommendations['error'])) {
        error_log("Recommendation (Page): Python script reported an error: " . $all_user_recommendations['error']);
        return [];
    }
    if (isset($all_user_recommendations['message']) && !isset($all_user_recommendations[0]) && count($all_user_recommendations) === 1) {
        error_log("Recommendation (Page): Python script message: " . $all_user_recommendations['message']);
        return [];
    }

    $recommended_dish_ids_for_user = [];
    if (isset($all_user_recommendations[$current_user_id_param])) {
        $recommended_dish_ids_for_user = $all_user_recommendations[$current_user_id_param];
    } elseif (isset($all_user_recommendations[strval($current_user_id_param)])) {
        $recommended_dish_ids_for_user = $all_user_recommendations[strval($current_user_id_param)];
    }

    if (empty($recommended_dish_ids_for_user) || !is_array($recommended_dish_ids_for_user)) {
        return [];
    }

    $sanitized_dish_ids = array_map('intval', $recommended_dish_ids_for_user);
    $sanitized_dish_ids = array_filter($sanitized_dish_ids, function($id) { return $id > 0; });

    if (empty($sanitized_dish_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($sanitized_dish_ids), '?'));
    // Fetch restaurant's open status along with dish details
    $sql = "SELECT d.d_id, d.title, d.price, d.img, d.slogan, d.rs_id, r.is_open AS restaurant_is_open
            FROM dishes d
            JOIN restaurant r ON d.rs_id = r.rs_id
            WHERE d.d_id IN ($placeholders)";
    
    if (!isset($db) || !($db instanceof PDO)) {
        error_log("Recommendation (Page): DB connection not available.");
        return [];
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($sanitized_dish_ids);
        $detailed_recommended_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $detailed_recommended_dishes;
    } catch (PDOException $e) {
        error_log("Recommendation (Page): DB query failed - " . $e->getMessage());
        return [];
    }
}

// Fetch recommendations for the logged-in user
// Using session caching to avoid re-running Python script too frequently on this page
if (!isset($_SESSION['user_page_recommendations_data']) || !isset($_SESSION['user_page_recommendations_ts']) || (time() - $_SESSION['user_page_recommendations_ts'] > 1800)) { // Cache for 30 mins
    $_SESSION['user_page_recommendations_data'] = get_user_recommendations_from_python_for_page($user_id);
    $_SESSION['user_page_recommendations_ts'] = time();
}
$recommendations = $_SESSION['user_page_recommendations_data'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Personalized food recommendations">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Your Recommendations - Food Delivery</title>
    <!-- Base CSS from your index.php -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <link href="css/custom-styles.css" rel="stylesheet">

    <style>
        /* Styles for this page (can be moved to custom-styles.css) */
        .page-wrapper-recommendations {
            padding-top: 80px; /* Adjust based on your header height */
            padding-bottom: 40px;
            min-height: calc(100vh - 120px); /* Adjust based on header/footer */
        }
        .recommendation-title-container {
            text-align: center;
            margin-bottom: 40px;
        }
        .recommendation-title-container h1 {
            font-size: 2.5rem;
            color: #333;
            font-weight: 600;
        }
        .recommendation-title-container p {
            font-size: 1.1rem;
            color: #666;
        }
        .no-recommendations {
            text-align: center;
            padding: 50px 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .no-recommendations i {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .no-recommendations p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .no-recommendations .btn-primary {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        .no-recommendations .btn-primary:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }

        /* Re-using dish grid styles (ensure these are defined in your main CSS or here) */
        .dish-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px; /* Slightly more gap */
        }
        .single-dish {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        .single-dish:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .dish-wrap { padding: 0; /* Remove padding if logo takes full width */ display: flex; flex-direction: column; height: 100%; }
        .dish-logo img { width: 100%; height: 200px; object-fit: cover; }
        .dish-content { padding: 15px; flex-grow: 1; }
        .dish-content h5 { font-size: 1.15rem; margin-bottom: 8px; }
        .dish-content h5 a { color: #333; text-decoration: none; }
        .dish-content h5 a:hover { color: #e74c3c; }
        .dish-price { font-weight: bold; color: #e74c3c; margin-bottom: 8px; font-size:1.1rem; }
        .dish-description { font-size: 0.9rem; color: #555; margin-bottom: 12px; line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; min-height: 4.5em; /* approx 3 lines */
        }
        .dish-actions { margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding: 0 15px 15px 15px; }

        .quantity-input-group { display: flex; align-items: center; }
        .quantity-btn { background-color: #f0f0f0; border: 1px solid #ccc; color: #333; cursor: pointer; padding: 6px 12px; font-size:0.9rem;}
        .quantity-input { width: 40px; text-align: center; border: 1px solid #ccc; border-left: none; border-right: none; padding: 6px 0; font-size:0.9rem; }
        .add-to-cart-btn { padding: 7px 14px; font-size:0.9rem; }

        .closed-restaurant .dish-actions .add-to-cart-btn,
        .closed-restaurant .dish-actions .quantity-input-group {
            display: none;
        }
        .closed-restaurant .dish-actions .restaurant-status {
            display: inline-block; font-weight:bold; color: #777;
        }
        .status-closed { color: #dc3545; }
    </style>
</head>

<body class="home"> <!-- You might want a different class like "recommendations-page-body" -->
    <?php include('header.php'); ?>

    <div class="page-wrapper page-wrapper-recommendations">
        <div class="container">
            <div class="recommendation-title-container">
                <h1>Our Chef Recommends</h1>
                <p>Handpicked dishes just for you, based on your taste!</p>
            </div>

            <?php if (!empty($recommendations)): ?>
                <div class="dish-grid">
                    <?php foreach ($recommendations as $dish): ?>
                        <?php $is_restaurant_open = $dish['restaurant_is_open'] ?? 1; // Default to open if status unknown ?>
                        <div class="single-dish <?php echo !$is_restaurant_open ? 'closed-restaurant' : ''; ?>">
                            <div class="dish-wrap">
                                <div class="dish-logo">
                                    <a href="dishes.php?res_id=<?php echo $dish['rs_id']; ?>&d_id=<?php echo $dish['d_id']; ?>">
                                        <img src="admin/Res_img/dishes/<?php echo htmlspecialchars($dish['img']); ?>" alt="<?php echo htmlspecialchars($dish['title']); ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="dish-content">
                                    <h5>
                                        <a href="dishes.php?res_id=<?php echo $dish['rs_id']; ?>&d_id=<?php echo $dish['d_id']; ?>">
                                            <?php echo htmlspecialchars($dish['title']); ?>
                                        </a>
                                    </h5>
                                    <div class="dish-price">₹<?php echo number_format(floatval($dish['price']), 2); ?></div>
                                    <div class="dish-description"><?php echo htmlspecialchars($dish['slogan']); ?></div>
                                </div>
                                <div class="dish-actions">
                                   <?php if ($is_restaurant_open): ?>
                                       <div class="quantity-input-group">
                                           <button class="quantity-btn quantity-down" type="button">-</button>
                                           <input class="quantity-input" type="text" name="quantity" value="1" size="2" readonly>
                                           <button class="quantity-btn quantity-up" type="button">+</button>
                                       </div>
                                       <button class="btn theme-btn btn-sm add-to-cart-btn" data-d_id="<?php echo $dish['d_id']; ?>" data-res_id="<?php echo $dish['rs_id']; ?>">Add To Cart</button>
                                   <?php else: ?>
                                       <span class="restaurant-status status-closed">Restaurant Closed</span>
                                   <?php endif; ?>
                               </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-recommendations">
                    <i class="fas fa-utensils"></i>
                    <p>We're still getting to know your taste!</p>
                    <p>Order some delicious food, and we'll have personalized recommendations for you soon.</p>
                    <a href="index.php" class="btn btn-primary btn-lg">Explore Restaurants</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <?php include('chatbot.php'); // If you use it on all pages ?>

    <!-- Scripts from your index.php (jQuery and SweetAlert should already be included in head) -->
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script> <!-- Main theme script -->

    <!-- Custom JS for this page (e.g., add to cart, quantity) -->
    <script>
    $(document).ready(function() {
        // --- Update Cart Count (if user is logged in) ---
        const isLoggedIn = <?php echo json_encode(!empty($_SESSION["user_id"])); ?>;
        const $cartNotificationDot = $('#cart-notification-dot'); // Assuming this ID is in header.php

        function updateCartCount() {
            if (!isLoggedIn || !$cartNotificationDot.length) {
                if($cartNotificationDot.length) $cartNotificationDot.hide();
                return;
            }
            $.ajax({
                url: 'get_cart_count.php', method: 'GET', dataType: 'json',
                success: function(response) {
                    if (response && typeof response.count !== 'undefined') {
                        const count = parseInt(response.count);
                        if (count > 0) $cartNotificationDot.show(); else $cartNotificationDot.hide();
                    } else { $cartNotificationDot.hide(); }
                },
                error: function(xhr) { console.error("Error fetching cart count:", xhr.responseText); if($cartNotificationDot.length) $cartNotificationDot.hide(); }
            });
        }
        if (isLoggedIn) {
            updateCartCount();
        }


        // --- Quantity Buttons ---
        $('body').on('click', '.quantity-up', function(e) {
            e.preventDefault();
            var input = $(this).siblings('.quantity-input');
            input.val(Math.max(1, (parseInt(input.val()) || 0) + 1));
        });

        $('body').on('click', '.quantity-down', function(e) {
            e.preventDefault();
            var input = $(this).siblings('.quantity-input');
            var currentVal = parseInt(input.val()) || 0;
            if (currentVal > 1) { input.val(currentVal - 1); }
        });

        // --- Add to Cart ---
        $('body').on('click', '.add-to-cart-btn', function(e) {
            e.preventDefault();
            var $button = $(this);
            if ($button.closest('.single-dish').hasClass('closed-restaurant')) {
                Swal.fire('Restaurant Closed', 'This restaurant is currently closed and cannot accept orders.', 'warning');
                return;
            }

            var d_id = $button.data('d_id');
            var res_id = $button.data('res_id');
            var quantity = $button.closest('.dish-actions').find('.quantity-input').val();
            var originalText = $button.html();
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');

            $.ajax({
                type: "POST", url: "ajax_add_to_cart.php",
                data: { d_id: d_id, res_id: res_id, quantity: quantity, action: "add" },
                dataType: "json",
                success: function(response) {
                    if (response && response.status === "success") {
                        Swal.fire({ icon: 'success', title: 'Added!', text: response.message || 'Item added to cart.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                        updateCartCount();
                    } else if (response && response.login_required) {
                        Swal.fire({ title: 'Login Required', text: 'Please log in to add items to your cart.', icon: 'warning', confirmButtonText: 'Login' })
                            .then((result) => { if (result.isConfirmed) { window.location.href = 'login.php'; } });
                    } else if (response && response.clear_cart_required) {
                        Swal.fire({
                            title: 'Start New Order?', text: response.message, icon: 'warning',
                            showCancelButton: true, confirmButtonText: 'Yes, start new order!', cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    type: "POST", url: "ajax_add_to_cart.php", data: { action: "clear_cart" }, dataType: "json",
                                    success: function(clearResponse) {
                                        if (clearResponse.status === 'success') {
                                            $button.click(); // Retry adding the item
                                        } else { Swal.fire('Error', 'Could not clear previous cart.', 'error'); }
                                    }, error: function() { Swal.fire('Error', 'Could not clear previous cart.', 'error'); }
                                });
                            }
                        });
                    } else {
                        Swal.fire('Error!', (response ? response.message : null) || 'Could not add item to cart.', 'error');
                    }
                    $button.prop('disabled', false).html(originalText);
                },
                error: function(xhr) {
                    console.error("Add to Cart AJAX Error: ", xhr.responseText);
                    Swal.fire('Error', 'An error occurred while contacting the server.', 'error');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });
    });
    </script>
</body>
</html>