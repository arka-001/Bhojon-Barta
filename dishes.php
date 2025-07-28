<?php
date_default_timezone_set('Asia/Kolkata'); // Set time zone to IST
include("connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Validate res_id
if (!isset($_GET['res_id']) || !is_numeric($_GET['res_id'])) {
    echo '<div class="alert alert-danger text-center">Invalid restaurant ID.</div>';
    exit;
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
    <title>Dishes</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .page-wrapper {
            min-height: 100vh;
        }
        .navbar-brand img {
            max-height: 50px;
        }
        .top-links {
            background-color: #fff;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: space-around;
        }
        .link-item {
            text-align: center;
        }
        .link-item span {
            display: block;
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        .link-item a {
            color: #333;
            text-decoration: none;
        }
        .inner-page-hero {
            padding: 50px 0;
            color: #fff;
            text-align: center;
        }
        .profile-img {
            margin-bottom: 20px;
        }
        .image-wrap img {
            max-width: 100%;
            border-radius: 8px;
        }
        .profile-desc h6 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .profile-desc p {
            font-size: 16px;
        }
        .menu-widget {
            margin-top: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .widget-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .food-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .food-item:last-child {
            border-bottom: none;
        }

        /* Styles for the left part of the food item (logo + description) */
        .food-item .row > .col-xs-12.col-sm-12.col-lg-8 { /* Make the parent column a flex container */
            display: flex;
            align-items: flex-start; /* MODIFIED: Align items to the top */
        }

        .rest-logo {
            flex-shrink: 0; /* Prevent logo from shrinking if description is long */
            margin-right: 15px; /* Space between logo and description block */
        }

        .rest-logo img {
            max-width: 80px; 
            border-radius: 8px;
            display: block; /* Good practice for images in flex items */
        }

        .rest-descr {
            flex-grow: 1; /* Allow description block to take remaining horizontal space */
        }
        .rest-descr h6 {
            font-size: 18px;
            font-weight: bold;
            margin-top: 0; /* Ensure no extra top margin */
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .rest-descr p {
            font-size: 14px;
            color: #777;
            margin-top: 0; /* Ensure no extra top margin */
            margin-bottom: 5px; /* Adjust spacing if needed */
        }


        /* Styles for the right part of the food item (price + actions) */
        .item-cart-info { /* This class is ON the .col-lg-3 div */
            display: flex;
            flex-direction: column;
            align-items: flex-end;   /* Align .price-block and .action-block to the right */
            justify-content: space-between; /* Pushes price-block up and action-block down */
        }

        .price-block {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            width: 100%; 
        }
        .price-block .price,
        .price-block .original-price,
        .price-block .discount-badge {
            margin-bottom: 5px;
        }
        .price-block .discount-badge:last-child,
        .price-block .original-price:last-child,
        .price-block .price:last-child {
            margin-bottom: 0;
        }

        .action-block {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            width: 100%; 
        }
        .action-block .quantity-input-group {
            margin-bottom: 10px; 
        }
        .action-block .not-available {
            text-align: right;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #28a745; 
        }
        .original-price {
            font-size: 14px;
            color: #777;
            text-decoration: line-through;
        }
        .discount-badge {
            display: inline-block;
            background-color: #e74c3c;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .quantity-input-group {
            display: inline-flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
            background-color: #eee;
        }
        .quantity-btn {
            background-color: #eee;
            color: #555;
            border: none;
            padding: 5px 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            flex-shrink: 0;
            width: 25px;
            text-align: center;
            line-height: 1;
        }
        .quantity-btn:hover {
            background-color: #ddd;
        }
        .quantity-btn:focus {
            outline: none;
        }
        .quantity-input {
            width: 30px;
            text-align: center;
            border: none;
            padding: 5px;
            box-sizing: border-box;
            background-color: transparent;
        }
        .add-to-cart-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .add-to-cart-btn:hover {
            background-color: #218838;
        }
        .footer {
            background-color: #343a40;
            color: #fff;
            padding: 30px 0;
            margin-top: 50px;
        }
        .payment-options img {
            max-width: 50px;
            margin-right: 5px;
        }
        .bottom-footer {
            border-top: 1px solid #555;
            padding-top: 20px;
        }
        .closed-restaurant {
            opacity: 0.7;
            filter: grayscale(50%);
            cursor: not-allowed;
        }
        .closed-restaurant img,
        .closed-restaurant h6 {
            pointer-events: none;
        }
        .food-item.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        .food-item.disabled .quantity-btn,
        .food-item.disabled .add-to-cart-btn {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .not-available {
            color: #dc3545;
            font-weight: 600;
            font-size: 14px;
        }
        .restaurant-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .status-open {
            background-color: #28a745;
            color: #fff;
        }
        .status-closed {
            background-color: #dc3545;
            color: #fff;
        }
        #search-container {
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        #search-wrapper {
            position: relative;
            border: 1px solid #ccc;
            border-radius: 100px;
            padding: 5px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        #search-wrapper:hover,
        #search-wrapper:focus-within {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(-3px);
        }
        #search-input {
            border: none;
            padding: 8px 10px;
            width: 100%;
            box-sizing: border-box;
            outline: none;
            font-size: 14px;
            color: #555;
            background-color: transparent;
        }
        #search-input::placeholder {
            color: #999;
            transition: color 0.3s ease;
        }
        #search-input:focus::placeholder {
            color: #ccc;
        }
        #search-icon {
            position: absolute;
            right: 15px;
            color: #777;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        #search-icon:hover {
            color: #555;
        }
        .favorite-icon {
            cursor: pointer;
            font-size: 1.2rem;
            color: #000000;
            margin-left: 10px;
            transition: color 0.3s ease;
        }
        .favorite-icon:hover {
            color: #555555;
        }
        .favorite-icon.favorite {
            color: #dc3545;
        }
        .favorite-icon.favorite:hover {
            color: #c82333;
        }
        .diet-type {
            display: inline-flex;
            align-items: center;
            margin-top: 5px;
            font-size: 14px;
            font-weight: 600;
        }
        .diet-type .icon {
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }
        .diet-type .icon svg {
            width: 100%;
            height: 100%;
        }
        .diet-type.veg {
            color: #28a745;
        }
        .diet-type.veg .icon svg {
            fill: #28a745;
        }
        .diet-type.nonveg {
            color: #dc3545;
        }
        .diet-type.nonveg .icon svg {
            fill: #dc3545;
        }
        .diet-type.vegan {
            color: #17a2b8;
        }
        .diet-type.vegan .icon svg {
            fill: #17a2b8;
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
            .links {
                flex-direction: column;
            }
            .link-item {
                margin-bottom: 15px;
            }
            .profile-desc h6 {
                font-size: 24px;
            }
            .profile-desc p {
                font-size: 14px;
            }

            /* Responsive adjustments for food item layout */
            /* .food-item .row > .col-xs-12.col-sm-12.col-lg-8 {
                align-items: flex-start; /* Already set, should be fine for stacked too */
            /* } */

            .item-cart-info {
                align-items: flex-start; 
                margin-top: 15px;
                justify-content: flex-start; 
            }
            .item-cart-info .price-block,
            .item-cart-info .action-block {
                align-items: flex-start; 
                width: auto; 
            }
            .item-cart-info .action-block {
                margin-top: 10px; 
            }
            .item-cart-info .action-block .quantity-input-group {
                margin-bottom: 10px;
            }
             .item-cart-info .action-block .not-available {
                text-align: left;
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
        <div class="top-links">
            <div class="container">
                <ul class="row links">
                    <li class="col-xs-12 col-sm-4 link-item"><span>1</span><a href="restaurants.php">Choose Restaurant</a></li>
                    <li class="col-xs-12 col-sm-4 link-item active"><span>2</span><a href="dishes.php?res_id=<?php echo htmlspecialchars($_GET['res_id']); ?>">Pick Your favorite food</a></li>
                    <li class="col-xs-12 col-sm-4 link-item"><span>3</span><a href="#">Order and Pay</a></li>
                </ul>
            </div>
        </div>
        <?php
        $stmt = $db->prepare("SELECT * FROM restaurant WHERE rs_id = ?");
        $stmt->bind_param('i', $_GET['res_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows == 0) {
            echo '<div class="alert alert-danger text-center">Restaurant not found.</div>';
            $stmt->close();
            exit;
        }
        $rows = $result->fetch_assoc();
        $is_open = $rows['is_open'];
        $status_text = $is_open ? 'Open' : 'Closed';
        $status_class = $is_open ? 'status-open' : 'status-closed';
        $restaurant_class = $is_open ? '' : 'closed-restaurant';
        $stmt->close();
        ?>
        <section class="inner-page-hero bg-image" data-image-src="images/img/restrrr.png">
            <div class="profile">
                <div class="container">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4 profile-img">
                            <div class="image-wrap <?php echo $restaurant_class; ?>">
                                <figure>
                                    <?php
                                    if ($is_open) {
                                        echo '<a href="#"><img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="Restaurant logo"></a>';
                                    } else {
                                        echo '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="Restaurant logo">';
                                    }
                                    ?>
                                </figure>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8 profile-desc">
                            <div class="pull-left right-text white-txt <?php echo $restaurant_class; ?>">
                                <?php
                                if ($is_open) {
                                    echo '<h6><a href="#">' . htmlspecialchars($rows['title']) . '</a></h6>';
                                } else {
                                    echo '<h6>' . htmlspecialchars($rows['title']) . '</h6>';
                                }
                                ?>
                                <p><?php echo htmlspecialchars($rows['address']); ?></p>
                                <small class="restaurant-status <?php echo $status_class; ?>"><?php echo $status_text; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="breadcrumb">
            <div class="container"></div>
        </div>
        <div class="container m-t-30">
            <div id="ajax-message"></div>
            <?php if (!$is_open) : ?>
                <div class="alert alert-warning text-center">
                    This restaurant is currently closed. You cannot add items to the cart at this time.
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-12" id="search-container">
                    <div id="search-wrapper">
                        <input type="text" id="search-input" placeholder="Search Dishes...">
                        <i class="fa fa-search" id="search-icon"></i>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="menu-widget" id="2">
                        <div class="widget-heading">
                            <h3 class="widget-title text-dark">
                                MENU <a class="btn btn-link pull-right" data-toggle="collapse" href="#popular2" aria-expanded="true">
                                    <i class="fa fa-angle-right pull-right"></i>
                                    <i class="fa fa-angle-down pull-right"></i>
                                </a>
                            </h3>
                            <div class="clearfix"></div>
                        </div>
                        <div class="collapse in" id="popular2">
                            <?php
                            $current_time = date('Y-m-d H:i:s'); // Current time in IST
                            $stmt = $db->prepare("SELECT d_id, title, slogan, price, offer_price, offer_start_date, offer_end_date, img, is_available, diet_type FROM dishes WHERE rs_id = ?");
                            $stmt->bind_param("i", $_GET['res_id']);
                            $stmt->execute();
                            $products = $stmt->get_result();
                            if ($products->num_rows > 0) {
                                while ($product = $products->fetch_assoc()) {
                                    $disabled_class = $is_open ? '' : 'disabled';
                                    $is_available = $product['is_available'];
                                    // Offer logic
                                    $is_offer_active = false;
                                    $discount_percentage = 0;
                                    if (
                                        !empty($product['offer_price']) &&
                                        is_numeric($product['offer_price']) &&
                                        $product['offer_price'] > 0 &&
                                        $product['offer_price'] < $product['price']
                                    ) {
                                        // Check date conditions only if dates are set
                                        $date_check_passed = true;
                                        if (!empty($product['offer_start_date']) && $current_time < $product['offer_start_date']) {
                                            $date_check_passed = false;
                                        }
                                        if (!empty($product['offer_end_date']) && $current_time > $product['offer_end_date']) {
                                            $date_check_passed = false;
                                        }
                                        if ($date_check_passed) {
                                            $is_offer_active = true;
                                            $discount_percentage = round((($product['price'] - $product['offer_price']) / $product['price']) * 100);
                                        }
                                    }
                                    // Debug output (remove in production)
                                    /*
                                    echo "<!-- Debug: d_id={$product['d_id']}, offer_price={$product['offer_price']}, price={$product['price']}, ";
                                    echo "start_date={$product['offer_start_date']}, end_date={$product['offer_end_date']}, ";
                                    echo "is_offer_active=" . ($is_offer_active ? 'true' : 'false') . " -->";
                                    */
                                    $is_favorite = false;
                                    if (isset($_SESSION['user_id'])) {
                                        $user_id = intval($_SESSION['user_id']);
                                        $fav_stmt = $db->prepare("SELECT * FROM user_favorite_dishes WHERE u_id = ? AND d_id = ?");
                                        $fav_stmt->bind_param("ii", $user_id, $product['d_id']);
                                        $fav_stmt->execute();
                                        $is_favorite = $fav_stmt->get_result()->num_rows > 0;
                                        $fav_stmt->close();
                                    }
                                    $diet_type_display = '';
                                    $diet_icon = '';
                                    $diet_class = '';
                                    switch ($product['diet_type']) {
                                        case 'veg':
                                            $diet_type_display = 'Vegetarian';
                                            $diet_class = 'veg';
                                            $diet_icon = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95.49-7.44-2.44-7.93-6.39C2.58 9.59 5.51 6.1 9.46 5.61c3.95-.49 7.44 2.44 7.93 6.39.49 3.95-2.44 7.44-6.39 7.93zM12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg>';
                                            break;
                                        case 'nonveg':
                                            $diet_type_display = 'Non-Vegetarian';
                                            $diet_class = 'nonveg';
                                            $diet_icon = '<svg viewBox="0 0 24 24"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg>';
                                            break;
                                        case 'vegan':
                                            $diet_type_display = 'Vegan';
                                            $diet_class = 'vegan';
                                            $diet_icon = '<svg viewBox="0 0 24 24"><path d="M7 3c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h10c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2H7zm0 2h10v6H7V5zm0 8h10v6H7v-6z"/></svg>';
                                            break;
                                        default:
                                            $diet_type_display = '';
                                            $diet_icon = '';
                                            $diet_class = '';
                                    }
                            ?>
                                    <div class="food-item <?php echo $disabled_class; ?>" data-food-name="<?php echo strtolower(htmlspecialchars($product['title'])); ?>">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-12 col-lg-8">
                                                <div class="rest-logo pull-left">
                                                    <a class="restaurant-logo pull-left" href="#">
                                                        <?php echo '<img src="admin/Res_img/dishes/' . htmlspecialchars($product['img']) . '" alt="Food logo">'; ?>
                                                    </a>
                                                </div>
                                                <div class="rest-descr">
                                                    <h6>
                                                        <a href="#"><?php echo htmlspecialchars($product['title']); ?></a>
                                                        <?php if (isset($_SESSION['user_id'])) : ?>
                                                            <i class="fas fa-heart favorite-icon <?php echo $is_favorite ? 'favorite' : ''; ?>" 
                                                               data-type="dish" 
                                                               data-id="<?php echo $product['d_id']; ?>" 
                                                               title="<?php echo $is_favorite ? 'Remove from Favorites' : 'Add to Favorites'; ?>"></i>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p><?php echo htmlspecialchars($product['slogan']); ?></p>
                                                    <?php if ($diet_type_display && $diet_icon): ?>
                                                        <span class="diet-type <?php echo $diet_class; ?>">
                                                            <span class="icon"><?php echo $diet_icon; ?></span>
                                                            <?php echo $diet_type_display; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-12 col-lg-3 pull-right item-cart-info">
                                                <?php if ($is_offer_active) : ?>
                                                    <span class="price pull-left">₹<?php echo number_format($product['offer_price'], 2); ?></span>
                                                    <span class="original-price pull-left">₹<?php echo number_format($product['price'], 2); ?></span>
                                                    <span class="discount-badge pull-left"><?php echo $discount_percentage; ?>% OFF</span>
                                                <?php else : ?>
                                                    <span class="price pull-left">₹<?php echo number_format($product['price'], 2); ?></span>
                                                <?php endif; ?>
                                                <?php if ($is_open && $is_available) : ?>
                                                    <div class="quantity-input-group">
                                                        <button class="quantity-btn quantity-down" type="button">-</button>
                                                        <input class="quantity-input" type="text" name="quantity" value="1" size="2" data-d_id="<?php echo $product['d_id']; ?>" readonly>
                                                        <button class="quantity-btn quantity-up" type="button">+</button>
                                                    </div>
                                                    <button class="btn theme-btn add-to-cart-btn" data-d_id="<?php echo $product['d_id']; ?>" data-res_id="<?php echo $_GET['res_id']; ?>" data-price="<?php echo $is_offer_active ? $product['offer_price'] : $product['price']; ?>">Add To Cart</button>
                                                <?php else : ?>
                                                    <div class="not-available">
                                                        Not Available Right Now
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            } else {
                                echo '<p class="text-center">No dishes available for this restaurant.</p>';
                            }
                            $stmt->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
            const isLoggedInJS = <?php echo json_encode(!empty($_SESSION["user_id"])); ?>;
            const userIdJS = <?php echo json_encode(isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null); ?>;
            var isRestaurantOpen = <?php echo $is_open ? 'true' : 'false'; ?>;

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
                                    userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('City AJAX error:', status, error, xhr.responseText);
                            if (sessionCity) {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                            } else {
                                userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`);
                            }
                        }
                    });
                } else {
                    console.log('Not logged in, using session city or default');
                    if (sessionCity) {
                        userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`);
                    } else {
                        userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Kolkata`);
                    }
                }
            }
            updateUserCityDisplay();

            // Quantity Buttons
            $('.quantity-up').click(function(e) {
                e.preventDefault();
                if (!isRestaurantOpen) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Restaurant Closed',
                        text: 'You cannot modify quantities because the restaurant is closed.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    return;
                }
                var input = $(this).closest('.item-cart-info').find('.quantity-input');
                var currentValue = parseInt(input.val());
                input.val(currentValue + 1);
            });

            $('.quantity-down').click(function(e) {
                e.preventDefault();
                if (!isRestaurantOpen) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Restaurant Closed',
                        text: 'You cannot modify quantities because the restaurant is closed.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    return;
                }
                var input = $(this).closest('.item-cart-info').find('.quantity-input');
                var currentValue = parseInt(input.val());
                if (currentValue > 1) {
                    input.val(currentValue - 1);
                }
            });

            // Add to Cart
            $('.add-to-cart-btn').click(function(e) {
                e.preventDefault();
                if (!isRestaurantOpen) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Restaurant Closed',
                        text: 'You cannot add items to the cart because the restaurant is closed.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    return;
                }
                var d_id = $(this).data('d_id');
                var res_id = $(this).data('res_id');
                var quantity = $(this).closest('.item-cart-info').find('.quantity-input').val();
                var price = $(this).data('price');
                $.ajax({
                    type: "POST",
                    url: "ajax_add_to_cart.php",
                    data: {
                        d_id: d_id,
                        res_id: res_id,
                        quantity: quantity,
                        price: price,
                        action: "add"
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log('Add to cart response:', response);
                        if (response.status === "success") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            updateCartCount();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Cart AJAX error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred. Please try again.'
                        });
                    }
                });
            });

            // Favorite Toggle
            $('.favorite-icon').on('click', function() {
                var $icon = $(this);
                var type = $icon.data('type');
                var id = $icon.data('id');
                var action = $icon.hasClass('favorite') ? 'remove' : 'add';
                $.ajax({
                    url: 'handle_favorites.php',
                    type: 'POST',
                    data: { action: action, type: type, id: id },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Favorite toggle response:', response);
                        if (response.success) {
                            if (action === 'add') {
                                $icon.addClass('favorite').attr('title', 'Remove from Favorites');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Added to Favorites',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                $icon.removeClass('favorite').attr('title', 'Add to Favorites');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Removed from Favorites',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Favorite AJAX error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to process request.'
                        });
                    }
                });
            });

            // Search Dishes
            $('#search-input').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('.food-item').each(function() {
                    var foodName = $(this).data('food-name');
                    if (foodName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
</body>
</html>