```html
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Order food online for delivery or takeout">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Home - Food Delivery</title>
    <!-- Base CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <!-- Theme Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link href="css/custom-styles.css" rel="stylesheet">
    <!-- Veg Theme CSS (initially disabled) -->
    <link id="veg-theme-stylesheet" href="css/veg-theme.css" rel="stylesheet" disabled>
    <!-- JS includes -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script src="scripts.js"></script>
    <!-- CSS for elements NOT in header.php, e.g., favorite icons in content -->
    <style>
        .favorite-icon {
            cursor: pointer;
            margin-left: 8px;
            transition: color 0.2s ease-in-out, transform 0.2s ease-in-out;
            font-size: 1.1em;
            vertical-align: middle;
        }
        .favorite-icon.far.fa-heart {
            color: #aaa;
        }
        .favorite-icon.fas.fa-heart.favorite {
            color: #e74c3c;
        }
        .favorite-icon:hover {
            transform: scale(1.2);
        }
        .favorite-icon.fa-spinner {
            color: #007bff;
        }
        /* Slideshow transition for smooth fade */
        .hero {
            transition: background-image 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }
        /* Back to Top Button Styles */
        #back-to-top {
            display: none;
            position: fixed;
            bottom: 26px;
            left: 40px;
            z-index: 1000;
            width: 65;
            height: 65;
            background-color:rgb(255, 162, 0);
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: opacity 0.3s, background-color 0.3s;
        }
        #back-to-top:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body class="home">
    <?php
    include("connection/connect.php"); // Ensure this path is correct
    error_reporting(0);
    session_start();

    // Get initial parameters for the first page load
    $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
    $filter_by_category = ($cat_id > 0);
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null; // $user_id is available for header.php
    ?>
    <?php include('header.php'); // Include the new header file ?>

    <section class="hero bg-image" data-image-src="images/img/pimg.jpg">
        <div class="hero-inner">
            <div class="container text-center hero-text font-white">
                <h1>Order Delivery & Take-Out</h1>
                <div class="banner-form">
                    <div class="location-search-wrapper">
                        <div class="location-input-group">
                            <input type="text" id="delivery-location" placeholder="Enter delivery location" autocomplete="off">
                            <button id="location-toggle-btn" aria-label="Location options toggle">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div id="location-options">
                                <a href="#" id="use-current-location"><i class="fas fa-map-marker-alt"></i> Use my current location</a>
                                <a href="#" id="verify-address"><i class="fas fa-check"></i> Verify this address</a>
                                <a href="#" id="save-address"><i class="fas fa-save"></i> Save this address</a>
                            </div>
                            <div id="address-suggestions" style="
                                max-width: 400px;
                                background: linear-gradient(135deg,rgb(227, 227, 227),rgb(214, 214, 214));
                                border: 1px solid #ffd700;
                                border-radius: 12px;
                                padding: 20px;
                                margin: 20px auto;
                                font-family: 'Segoe UI', Arial, sans-serif;
                                font-size: 15px;
                                color: #f1f1f1;
                                box-shadow: 0 4px 10px rgba(255, 255, 255, 0.7);
                                transition: all 0.3s ease;
                                text-align: left;
                                letter-spacing: 0.5px;
                                line-height: 1.6;
                                backdrop-filter: blur(5px);
                                overflow-y: auto;
                                max-height: 300px;
                                scrollbar-width: thin;
                                scrollbar-color: #ffd700 #2c2c2c;"
                                onmouseover="this.style.boxShadow='0 0 20px 5px rgba(255, 215, 0, 0.8)';"
                                onmouseout="this.style.boxShadow='0 4px 10px rgba(0, 0, 0, 0.7)';">
                            </div>
                        </div>
                        <div class="search-bar">
                            <form method="GET" action="index.php" id="search-form">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="search-input" name="search" placeholder="" data-placeholder="Search Restaurants or Food" value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                                <button type="submit">Search</button>
                            </form>
                        </div>
                        <div class="veg-toggle-group">
                            <label for="veg-toggle" class="veg-toggle-label">
                                <input type="checkbox" id="veg-toggle" name="veg-toggle">
                                <i class="fas fa-leaf"></i>
                                <span class="veg-toggle-text">Veg Only</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="steps">
                    <div class="step-item step1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 483 483" width="50" height="50"></svg>
                        <h4><span style="color:white;">1. </span>Choose Restaurant</h4>
                    </div>
                    <div class="step-item step2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 380.721 380.721"></svg>
                        <h4><span style="color:white;">2. </span>Order Food</h4>
                    </div>
                    <div class="step-item step3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 380.721 380.721"></svg>
                        <h4><span style="color:white;">3. </span>Get your food delivered! And enjoy your meal!</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="category-section">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <h4>Popular Categories</h4>
                </div>
            </div>
            <div class="skeleton-placeholder">
                <div class="skeleton-category-grid">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                    <div class="skeleton-category-item">
                        <div class="skeleton skeleton-category-img"></div>
                        <div class="skeleton skeleton-category-name"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="category-grid real-content">
                <?php
                $cat_query = mysqli_query($db, "SELECT * FROM res_category ORDER BY c_id DESC LIMIT 8");
                if (mysqli_num_rows($cat_query) > 0) {
                    while ($cat_row = mysqli_fetch_array($cat_query)) {
                        echo '<div class="category-item">
                                <a href="index.php?cat_id=' . $cat_row['c_id'] . '#search-results">
                                    <img src="admin/category_images/' . htmlspecialchars($cat_row['image']) . '" alt="' . htmlspecialchars($cat_row['c_name']) . '" loading="lazy">
                                    <div class="category-name">' . htmlspecialchars($cat_row['c_name']) . '</div>
                                </a>
                              </div>';
                    }
                } else {
                    echo '<div class="col-sm-12"><p class="text-center">No Categories Available!</p></div>';
                }
                ?>
            </div>
            <?php
            $total_cat_query = mysqli_query($db, "SELECT COUNT(c_id) as total FROM res_category");
            $total_cat_count = mysqli_fetch_assoc($total_cat_query)['total'];
            if($total_cat_count > 8):
            ?>
                <div class="text-center mt-4 real-content">
                    <a href="restaurants.php" class="btn btn-primary">View All Categories/Restaurants</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="featured-restaurants" id="search-results">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <?php
                    $result_title = 'Featured Restaurants';
                    if (!empty($search_term)) {
                        $result_title = 'Search Results for "' . htmlspecialchars($search_term) . '"';
                        if ($filter_by_category) {
                            $cat_info_res_title = mysqli_query($db, "SELECT c_name FROM res_category WHERE c_id = '$cat_id'");
                            $cat_info_title = mysqli_fetch_assoc($cat_info_res_title);
                            if ($cat_info_title) {
                                $result_title .= ' in category: ' . htmlspecialchars($cat_info_title['c_name']);
                            }
                        }
                    } elseif ($filter_by_category) {
                        $cat_info_res_title = mysqli_query($db, "SELECT c_name FROM res_category WHERE c_id = '$cat_id'");
                        $cat_info_title = mysqli_fetch_assoc($cat_info_res_title);
                        $result_title = 'Restaurants in category: ' . htmlspecialchars($cat_info_title['c_name'] ?? 'Selected');
                    }
                    ?>
                    <h4 id="results-title"><?php echo $result_title; ?></h4>
                </div>
            </div>
            <div class="skeleton-placeholder">
                <?php
                $show_dish_skeleton = !empty($search_term);
                $show_restaurant_skeleton = true;
                $skeleton_count = 6;
                if ($show_dish_skeleton):
                ?>
                <h5>Matching Dishes</h5>
                <div class="skeleton-dish-grid mb-4">
                    <?php for ($i = 0; $i < $skeleton_count; $i++): ?>
                        <div class="skeleton-dish-item">
                            <div class="dish-wrap">
                                <div class="skeleton skeleton-dish-img"></div>
                                <div class="skeleton-dish-content">
                                    <div class="skeleton skeleton-text title"></div>
                                    <div class="skeleton skeleton-text subtitle"></div>
                                    <div class="skeleton skeleton-text price"></div>
                                    <div class="skeleton skeleton-text desc"></div>
                                    <div class="skeleton skeleton-text desc short"></div>
                                </div>
                                <div class="skeleton-dish-actions">
                                    <div class="skeleton skeleton-qty"></div>
                                    <div class="skeleton skeleton-button"></div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php if ($show_restaurant_skeleton): ?>
                    <?php if($show_dish_skeleton): ?><h5 class="mt-4">Matching Restaurants</h5><?php endif; ?>
                    <div class="skeleton-restaurant-grid">
                        <?php for ($i = 0; $i < $skeleton_count; $i++): ?>
                        <div class="skeleton-restaurant-item">
                            <div class="restaurant-wrap">
                                <div class="skeleton skeleton-restaurant-img"></div>
                                <div class="skeleton-restaurant-content">
                                    <div class="skeleton skeleton-text title"></div>
                                    <div class="skeleton skeleton-text address"></div>
                                    <div class="skeleton skeleton-text category"></div>
                                    <div class="skeleton skeleton-text status"></div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="real-content">
                <?php
                $user_city = null;
                if (!empty($_SESSION["user_id"])) {
                    $user_query = mysqli_query($db, "SELECT city FROM users WHERE u_id = '$user_id' AND city IS NOT NULL");
                    if ($user_query && mysqli_num_rows($user_query) > 0) {
                        $user_city = mysqli_fetch_assoc($user_query)['city'];
                    }
                } elseif (!empty($_SESSION['selected_city'])) {
                    $user_city = $_SESSION['selected_city'];
                }
                $found_dishes_initial = false;
                $found_restaurants_initial = false;
                if (!empty($search_term)) {
                    $search_term_safe = mysqli_real_escape_string($db, $search_term);
                    $dish_sql_initial = "SELECT d.*, r.title AS restaurant_name, r.rs_id, r.is_open AS restaurant_is_open,
                                        EXISTS (SELECT 1 FROM user_favorite_dishes uf WHERE uf.u_id = " . ($user_id ? "'$user_id'" : "NULL") . " AND uf.d_id = d.d_id) AS is_favorite
                                        FROM dishes d
                                        JOIN restaurant r ON d.rs_id = r.rs_id
                                        WHERE (d.title LIKE '%$search_term_safe%' OR d.slogan LIKE '%$search_term_safe%')";
                    if ($filter_by_category) {
                        $dish_sql_initial .= " AND r.c_id = '$cat_id'";
                    }
                    if ($user_city) {
                        $user_city_safe = mysqli_real_escape_string($db, $user_city);
                        $dish_sql_initial .= " AND r.city = '$user_city_safe'";
                    }
                    $dish_sql_initial .= " ORDER BY d.d_id DESC LIMIT 9";
                    $dish_ress_initial = mysqli_query($db, $dish_sql_initial);
                    $found_dishes_initial = mysqli_num_rows($dish_ress_initial) > 0;
                    if ($found_dishes_initial) {
                        echo '<h5>Matching Dishes</h5>';
                        echo '<div class="dish-grid mb-4">';
                        while ($dish_row = mysqli_fetch_array($dish_ress_initial)) {
                            $restaurant_is_open = $dish_row['restaurant_is_open'];
                            $dish_class = $restaurant_is_open ? '' : 'closed-restaurant';
                            $icon_classes_dish = $dish_row['is_favorite'] ? 'fas fa-heart favorite' : 'far fa-heart';
                            $favorite_title_dish = $dish_row['is_favorite'] ? 'Remove from Favorites' : 'Add to Favorites';
                            echo '<div class="single-dish ' . $dish_class . '">
                                    <div class="dish-wrap">
                                        <div class="dish-logo">
                                            <img src="admin/Res_img/dishes/' . htmlspecialchars($dish_row['img']) . '" alt="' . htmlspecialchars($dish_row['title']) . '" loading="lazy">
                                        </div>
                                        <div class="dish-content">
                                            <h5>' . htmlspecialchars($dish_row['title']);
                                            if ($user_id && $restaurant_is_open) {
                                                echo '<i class="' . htmlspecialchars($icon_classes_dish) . ' favorite-icon"
                                                       data-type="dish"
                                                       data-id="' . $dish_row['d_id'] . '"
                                                       title="' . htmlspecialchars($favorite_title_dish) . '"></i>';
                                            }
                                       // NEW UPDATED CODE
echo '</h5>
    <div class="restaurant-name">From: <a href="dishes.php?res_id=' . $dish_row['rs_id'] . '">' . htmlspecialchars($dish_row['restaurant_name']) . '</a></div>';

    // Updated Price Display Logic
    $original_price = number_format((float)$dish_row['price'], 2);
    // Note: Ensure the 'offer_price' column is available in your 'dishes' table
    $offer_price = isset($dish_row['offer_price']) && $dish_row['offer_price'] !== null && (float)$dish_row['offer_price'] < (float)$dish_row['price'] ? number_format((float)$dish_row['offer_price'], 2) : null;
    $discount_percentage = $offer_price ? round(((float)$dish_row['price'] - (float)$dish_row['offer_price']) / (float)$dish_row['price'] * 100) : null;

    echo '<div class="dish-price-container">';
    if ($offer_price) {
        echo '<span class="offer-price">₹' . $offer_price . '</span>
              <span class="original-price">₹' . $original_price . '</span>
              <span class="discount-badge">' . $discount_percentage . '% OFF</span>';
    } else {
        echo '<span class="offer-price">₹' . $original_price . '</span>';
    }
    echo '</div>';
    // End Updated Price Display Logic

echo '<div class="dish-description">' . htmlspecialchars($dish_row['slogan']) . '</div>
</div>'; // End dish-content
                            if ($restaurant_is_open) {
                                echo '<div class="dish-actions">
                                          <div class="quantity-input-group">
                                              <button class="quantity-btn quantity-down" type="button">-</button>
                                              <input class="quantity-input" type="text" name="quantity" value="1" size="2" readonly>
                                              <button class="quantity-btn quantity-up" type="button">+</button>
                                          </div>
                                          <button class="btn btn-sm add-to-cart-btn" data-d_id="' . $dish_row['d_id'] . '" data-res_id="' . $dish_row['rs_id'] . '">Add To Cart</button>
                                      </div>';
                            } else {
                                echo '<div class="dish-actions"><span class="restaurant-status status-closed">Restaurant Closed</span></div>';
                            }
                            echo '</div>
                                  </div>';
                        }
                        echo '</div>';
                    }
                    $res_sql_initial = "SELECT r.*, rc.c_name, r.is_open,
                                       EXISTS (SELECT 1 FROM user_favorite_restaurants uf WHERE uf.u_id = " . ($user_id ? "'$user_id'" : "NULL") . " AND uf.rs_id = r.rs_id) AS is_favorite
                                       FROM restaurant r
                                       LEFT JOIN res_category rc ON r.c_id = rc.c_id
                                       WHERE (r.title LIKE '%$search_term_safe%' OR r.address LIKE '%$search_term_safe%')";
                    if ($filter_by_category) {
                        $res_sql_initial .= " AND r.c_id = '$cat_id'";
                    }
                    if ($user_city) {
                        $user_city_safe = mysqli_real_escape_string($db, $user_city);
                        $res_sql_initial .= " AND r.city = '$user_city_safe'";
                    }
                    $res_sql_initial .= " ORDER BY r.rs_id DESC LIMIT 9";
                    $res_ress_initial = mysqli_query($db, $res_sql_initial);
                    $found_restaurants_initial = mysqli_num_rows($res_ress_initial) > 0;
                    if ($found_restaurants_initial) {
                        if ($found_dishes_initial) echo '<h5 class="mt-4">Matching Restaurants</h5>';
                        echo '<div class="restaurant-grid">';
                        while ($rows = mysqli_fetch_array($res_ress_initial)) {
                            $cat_name_class_initial = isset($rows['c_name']) ? str_replace(' ', '-', $rows['c_name']) : '';
                            $status_text = $rows['is_open'] ? 'Open' : 'Closed';
                            $status_class = $rows['is_open'] ? 'status-open' : 'status-closed';
                            $is_closed = !$rows['is_open'];
                            $restaurant_class = $is_closed ? 'closed-restaurant' : '';
                            $icon_classes_res = $rows['is_favorite'] ? 'fas fa-heart favorite' : 'far fa-heart';
                            $favorite_title_res = $rows['is_favorite'] ? 'Remove from Favorites' : 'Add to Favorites';
                            echo '<div class="single-restaurant all ' . htmlspecialchars($cat_name_class_initial) . ' ' . $restaurant_class . '">
                                    <div class="restaurant-wrap">
                                        <div class="restaurant-logo">';
                            if ($rows['is_open']) {
                                echo '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">
                                        <img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">
                                      </a>';
                            } else {
                                echo '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">';
                            }
                            echo '</div>
                                        <div class="restaurant-content">';
                            if ($rows['is_open']) {
                                echo '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a>';
                            } else {
                                echo '<h5>' . htmlspecialchars($rows['title']);
                            }
                            if ($user_id) {
                                echo '<i class="' . htmlspecialchars($icon_classes_res) . ' favorite-icon"
                                       data-type="restaurant"
                                       data-id="' . $rows['rs_id'] . '"
                                       title="' . htmlspecialchars($favorite_title_res) . '"></i>';
                            }
                            echo '</h5>
                                  <span>' . htmlspecialchars($rows['address']) . '</span>
                                  <small>Category: ' . htmlspecialchars($rows['c_name'] ?? 'N/A') . '</small>
                                  <small class="restaurant-status ' . $status_class . '">' . $status_text . '</small>
                                  </div>
                                    </div>
                                  </div>';
                        }
                        echo '</div>';
                    }
                    if (!$found_dishes_initial && !$found_restaurants_initial) {
                        echo '<div class="col-sm-12 text-center"><p>No results found for "' . htmlspecialchars($search_term) . '"' . ($user_city ? ' in your city' : '') . '. Try searching for a different restaurant or dish.</p></div>';
                    }
                } else {
                    $sql_initial = "SELECT r.*, rc.c_name, r.is_open,
                                   EXISTS (SELECT 1 FROM user_favorite_restaurants uf WHERE uf.u_id = " . ($user_id ? "'$user_id'" : "NULL") . " AND uf.rs_id = r.rs_id) AS is_favorite
                                   FROM restaurant r
                                   LEFT JOIN res_category rc ON r.c_id = rc.c_id";
                    $where_clauses = [];
                    if ($filter_by_category) {
                        $where_clauses[] = "r.c_id = '$cat_id'";
                    }
                    if ($user_city) {
                        $user_city_safe = mysqli_real_escape_string($db, $user_city);
                        $where_clauses[] = "r.city = '$user_city_safe'";
                    }
                    if (!empty($where_clauses)) {
                        $sql_initial .= " WHERE " . implode(" AND ", $where_clauses);
                    }
                    $sql_initial .= " ORDER BY r.rs_id DESC LIMIT 9";
                    $ress_initial = mysqli_query($db, $sql_initial);
                    $found_restaurants_initial = mysqli_num_rows($ress_initial) > 0;
                    if ($found_restaurants_initial) {
                        echo '<div class="restaurant-grid">';
                        while ($rows = mysqli_fetch_array($ress_initial)) {
                            $cat_name_class_initial = isset($rows['c_name']) ? str_replace(' ', '-', $rows['c_name']) : '';
                            $status_text = $rows['is_open'] ? 'Open' : 'Closed';
                            $status_class = $rows['is_open'] ? 'status-open' : 'status-closed';
                            $is_closed = !$rows['is_open'];
                            $restaurant_class = $is_closed ? 'closed-restaurant' : '';
                            $icon_classes_res = $rows['is_favorite'] ? 'fas fa-heart favorite' : 'far fa-heart';
                            $favorite_title_res = $rows['is_favorite'] ? 'Remove from Favorites' : 'Add to Favorites';
                            echo '<div class="single-restaurant all ' . htmlspecialchars($cat_name_class_initial) . ' ' . $restaurant_class . '">
                                    <div class="restaurant-wrap">
                                        <div class="restaurant-logo">';
                            if ($rows['is_open']) {
                                echo '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">
                                        <img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">
                                      </a>';
                            } else {
                                echo '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">';
                            }
                            echo '</div>
                                        <div class="restaurant-content">';
                            if ($rows['is_open']) {
                                echo '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a>';
                            } else {
                                echo '<h5>' . htmlspecialchars($rows['title']);
                            }
                            if ($user_id) {
                                echo '<i class="' . htmlspecialchars($icon_classes_res) . ' favorite-icon"
                                       data-type="restaurant"
                                       data-id="' . $rows['rs_id'] . '"
                                       title="' . htmlspecialchars($favorite_title_res) . '"></i>';
                            }
                            echo '</h5>
                                  <span>' . htmlspecialchars($rows['address']) . '</span>
                                  <small>Category: ' . htmlspecialchars($rows['c_name'] ?? 'N/A') . '</small>
                                  <small class="restaurant-status ' . $status_class . '">' . $status_text . '</small>
                                  </div>
                                    </div>
                                  </div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="col-sm-12 text-center"><p>No restaurants found' . ($filter_by_category ? ' in this category' : '') . ($user_city ? ' in your city' : '') . '.</p></div>';
                    }
                    $total_res_count_sql = "SELECT COUNT(rs_id) as total FROM restaurant";
                    $total_where = [];
                    if ($user_city) {
                        $user_city_safe = mysqli_real_escape_string($db, $user_city);
                        $total_where[] = "city = '$user_city_safe'";
                    }
                    if (!empty($total_where)) {
                        $total_res_count_sql .= " WHERE " . implode(" AND ", $total_where);
                    }
                    $total_res_query = mysqli_query($db, $total_res_count_sql);
                    $total_res_count = mysqli_fetch_assoc($total_res_query)['total'];
                    if ($total_res_count > 9 && !$filter_by_category && empty($search_term)) {
                        echo '<div class="text-center mt-4"><a href="restaurants.php" class="btn btn-primary">View All Restaurants</a></div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>
    <?php include 'chatbot.php'; ?>

    <!-- Back to Top Button -->
    <button id="back-to-top" title="Back to Top"><i class="fas fa-chevron-up"></i></button>

    <script src="https://files.bpcontent.cloud/2025/04/04/11/20250404115714-HEAZS4BM.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>

    <script>
        $(document).ready(function() {
            // --- Banner Slideshow ---
            const bannerImages = [
                'images/img/pimg.jpg',
                'images/img/pimg2.jpg',
                'images/img/pimg3.jpg',
                'images/img/pimg4.jpg'
            ];
            let currentBannerIndex = 0;
            const $heroSection = $('.hero.bg-image');

            function changeBannerImage() {
                $heroSection.css('background-image', `url(${bannerImages[currentBannerIndex]})`);
                currentBannerIndex = (currentBannerIndex + 1) % bannerImages.length;
            }

            // Initial image set
            changeBannerImage();

            // Change image every 3 seconds
            setInterval(changeBannerImage, 3000);

            // --- Content Loading ---
            $(window).on('load', function() {
                $('body').addClass('content-loaded');
                handleScroll();
                scrollToResultsIfNeeded();
            });

            // --- Variables ---
            const $searchResultsContainer = $('#search-results .container');
            const $realContentWrapper = $searchResultsContainer.find('.real-content');
            const $skeletonPlaceholder = $searchResultsContainer.find('.skeleton-placeholder');
            const $resultsTitle = $('#results-title');
            const $searchForm = $('#search-form');
            const $searchInput = $searchForm.find('input[name="search"]');
            const $categoryGrid = $('.category-grid');
            const $locationInput = $('#delivery-location');
            const $locationToggleBtn = $('#location-toggle-btn');
            const $locationOptions = $('#location-options');
            const $addressSuggestions = $('#address-suggestions');
            const $verifyAddressLink = $('#verify-address');
            const $saveAddressLink = $('#save-address');
            const $useCurrentLocationLink = $('#use-current-location');
            const $userCityDisplay = $('#user-city');
            const $locationInputGroup = $('.location-input-group');
            const isLoggedIn = <?php echo json_encode(!empty($_SESSION["user_id"])); ?>;
            const userId = <?php echo json_encode($_SESSION["user_id"] ?? null); ?>;
            let typingTimer;
            const doneTypingInterval = 300;
            const $cartNotificationDot = $('#cart-notification-dot');
            const $backToTopBtn = $('#back-to-top');

            // --- Functions ---
            function handleScroll() {
                if ($(window).scrollTop() > 50) {
                    $('#header').addClass('scrolled');
                    $backToTopBtn.fadeIn(200);
                } else {
                    $('#header').removeClass('scrolled');
                    $backToTopBtn.fadeOut(200);
                }
            }

            function scrollToResultsIfNeeded() {
                <?php if (!empty($search_term) || $filter_by_category): ?>
                if ($('#search-results').length) {
                    setTimeout(function() {
                        $('html, body').animate({
                            scrollTop: $('#search-results').offset().top - 100
                        }, 500);
                    }, 150);
                }
                <?php endif; ?>
            }

            function buildUrl(searchTerm, categoryId) {
                let url = 'index.php';
                const params = [];
                if (searchTerm) {
                    params.push('search=' + encodeURIComponent(searchTerm));
                }
                if (categoryId > 0) {
                    params.push('cat_id=' + categoryId);
                }
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                url += '#search-results';
                return url;
            }

            function fetchAddressSuggestions(query) {
                $.ajax({
                    url: 'geocode.php',
                    method: 'POST',
                    data: { autocomplete: true, query: query },
                    dataType: 'json',
                    success: function(data) {
                        $addressSuggestions.empty().hide();
                        if (data.status === 'success' && data.suggestions.length > 0) {
                            data.suggestions.forEach(function(suggestion) {
                                const item = $('<a href="#" class="suggestion-item"></a>')
                                    .text(suggestion.display_name)
                                    .data('lat', suggestion.lat)
                                    .data('lon', suggestion.lon)
                                    .data('city', suggestion.city || 'Unknown');
                                $addressSuggestions.append(item);
                            });
                            $addressSuggestions.slideDown(150);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Autocomplete AJAX Error:', {status: status, error: error});
                        $addressSuggestions.empty().hide();
                    }
                });
            }

            function reverseGeocode(latitude, longitude) {
                $locationInput.val('Fetching address...').prop('disabled', true);
                $.ajax({
                    url: 'reverse_geocode_checkout.php',
                    method: 'POST',
                    data: { lat: latitude, lon: longitude },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            $locationInput
                                .val(data.address)
                                .data('latitude', latitude)
                                .data('longitude', longitude)
                                .data('city', data.city || 'Unknown');
                            updateCityDisplay(data.city || 'Unknown');
                            $locationInputGroup.slideUp();
                            $verifyAddressLink.hide();
                            if (isLoggedIn) $saveAddressLink.show();
                            updateResultsForCity(data.city || 'Unknown', data.address);
                        } else {
                            $locationInput.val('');
                            Swal.fire('Error', 'Unable to fetch address: ' + (data.message || 'Unknown error'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Reverse Geocode AJAX Error:', {status: status, error: error, responseText: xhr.responseText});
                        $locationInput.val('');
                        Swal.fire('Error', 'Unable to fetch location.', 'error');
                    },
                    complete: function() {
                        $locationInput.prop('disabled', false);
                    }
                });
            }

            function geocodeAndProcessAddress(address) {
                $locationInput.prop('disabled', true);
                $.ajax({
                    url: 'geocode.php',
                    method: 'POST',
                    data: { address: address },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            $locationInput
                                .val(address)
                                .data('latitude', data.latitude)
                                .data('longitude', data.longitude)
                                .data('city', data.city || 'Unknown');
                            updateCityDisplay(data.city || 'Unknown');
                            $locationInputGroup.slideUp();
                            $verifyAddressLink.hide();
                            if (isLoggedIn) $saveAddressLink.show();
                            updateResultsForCity(data.city || 'Unknown', address);
                        } else {
                            Swal.fire('Verification Failed', 'Unable to verify address: ' + (data.message || 'Unknown error'), 'error');
                            $verifyAddressLink.hide();
                            $saveAddressLink.hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Geocode AJAX Error:', {status: status, error: error, responseText: xhr.responseText});
                        Swal.fire('Error', 'Unable to verify address due to a technical issue.', 'error');
                    },
                    complete: function() {
                        $locationInput.prop('disabled', false);
                    }
                });
            }

            function saveAddress(address, latitude, longitude, city) {
                const dataToSend = { city: city };
                if (isLoggedIn) {
                    dataToSend.user_id = userId;
                    dataToSend.address = address;
                    dataToSend.latitude = latitude;
                    dataToSend.longitude = longitude;
                }
                $.ajax({
                    url: 'save_address.php',
                    method: 'POST',
                    data: dataToSend,
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            console.log("Address/City saved:", data.message);
                            if (isLoggedIn) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved!',
                                    text: data.message,
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                $saveAddressLink.hide();
                            }
                            loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                        } else {
                            Swal.fire('Error', data.message || 'Could not save address/city.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Save Address/City AJAX Error:', {status: status, error: error, responseText: xhr.responseText});
                        Swal.fire('Error', 'Failed to save address/city due to a technical issue.', 'error');
                    }
                });
            }

            function checkDeliveryAvailability(city, address = null) {
                if (!city || city.trim() === '' || city === 'Unknown') {
                    console.warn('City not determined, skipping delivery availability check.');
                    loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                    return;
                }
                $.ajax({
                    url: 'check_delivery.php',
                    method: 'POST',
                    data: { check_delivery: true, city: city },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            Swal.fire('Verification Error', data.error, 'error');
                            if (isLoggedIn) $saveAddressLink.hide();
                            return;
                        }
                        if (data.available) {
                            console.log(`Delivery available for ${city}`);
                        } else {
                            let message = `Sorry, no delivery partner is available in ${data.city}.`;
                            if (data.available_cities && data.available_cities.length > 0) {
                                message += ` We currently deliver to: ${data.available_cities.join(', ')}.`;
                            }
                            Swal.fire('No Delivery', message, 'warning');
                            if (isLoggedIn) $saveAddressLink.hide();
                        }
                        loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Delivery Check AJAX Error:', {status: status, error: error, responseText: xhr.responseText});
                        Swal.fire('Error', 'Unable to check delivery availability.', 'error');
                        loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                    }
                });
            }

            function updateCityDisplay(city) {
                if (city && city !== 'Unknown') {
                    $userCityDisplay.html(`<i class="fas fa-map-marker-alt"></i> ${city}`);
                } else {
                    $userCityDisplay.html(`<i class="fas fa-map-marker-alt"></i> Select City`);
                }
            }

            function getCurrentCategoryId() {
                const urlParams = new URLSearchParams(window.location.search);
                return parseInt(urlParams.get('cat_id') || '0');
            }

            function updateResultsForCity(city, address = null) {
                const lat = $locationInput.data('latitude');
                const lon = $locationInput.data('longitude');
                saveAddress(address, lat, lon, city);
            }

            function loadUserCity() {
                if (isLoggedIn) {
                    $.ajax({
                        url: 'get_user_city.php',
                        method: 'POST',
                        data: { user_id: userId },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success' && data.city) {
                                updateCityDisplay(data.city);
                                $locationInputGroup.slideUp();
                                loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                            } else {
                                loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Get User City AJAX Error:', {status: status, error: error});
                            loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                        }
                    });
                } else {
                    $.ajax({
                        url: 'get_user_city.php',
                        method: 'POST',
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success' && data.city) {
                                updateCityDisplay(data.city);
                                $locationInputGroup.slideUp();
                            }
                            loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                        },
                        error: function() {
                            loadResults($searchInput.val().trim(), getCurrentCategoryId(), false);
                        }
                    });
                }
            }

            function updateCartCount() {
                if (!isLoggedIn) {
                    $cartNotificationDot.hide();
                    return;
                }
                $.ajax({
                    url: 'get_cart_count.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && typeof response.count !== 'undefined') {
                            const count = parseInt(response.count);
                            if (count > 0) {
                                $cartNotificationDot.show();
                            } else {
                                $cartNotificationDot.hide();
                            }
                        } else {
                            $cartNotificationDot.hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching cart count:", status, error, xhr.responseText);
                        $cartNotificationDot.hide();
                    }
                });
            }

            // --- Event Handlers ---
            $(window).scroll(handleScroll);

            // Back to Top Button Click Handler
            $backToTopBtn.on('click', function() {
                $('html, body').animate({ scrollTop: 0 }, 500);
            });

            $searchForm.on('submit', function(e) {
                e.preventDefault();
                const searchTerm = $searchInput.val().trim();
                loadResults(searchTerm, getCurrentCategoryId(), true);
            });

            $categoryGrid.on('click', '.category-item a', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const urlParams = new URLSearchParams(href.split('?')[1]);
                const categoryId = parseInt(urlParams.get('cat_id') || '0');
                $searchInput.val('');
                loadResults('', categoryId, true);
            });

            $(window).on('popstate', function(event) {
                console.log("Popstate event:", event.originalEvent.state);
                const state = event.originalEvent.state;
                if (state) {
                    $searchInput.val(state.search || '');
                    loadResults(state.search, state.cat_id, false);
                } else {
                    const urlParams = new URLSearchParams(window.location.search);
                    const searchTerm = urlParams.get('search') || '';
                    const catId = parseInt(urlParams.get('cat_id') || '0');
                    $searchInput.val(searchTerm);
                    loadResults(searchTerm, catId, false);
                }
            });

            $('body').on('click', '.favorite-icon', function() {
                if (!isLoggedIn) {
                    Swal.fire({
                        title: 'Login Required',
                        text: 'Please log in to manage your favorites.',
                        icon: 'warning',
                        confirmButtonText: 'Login'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.php';
                        }
                    });
                    return;
                }
                if ($(this).hasClass('disabled')) return;
                var $icon = $(this);
                var type = $icon.data('type');
                var id = $icon.data('id');
                const isCurrentlyFavorite = $icon.hasClass('fas') && $icon.hasClass('favorite');
                var action = isCurrentlyFavorite ? 'remove' : 'add';
                $icon.removeClass('fas far fa-heart favorite').addClass('fas fa-spinner fa-spin disabled');
                $.ajax({
                    url: 'handle_favorites.php',
                    type: 'POST',
                    data: { action: action, type: type, id: id },
                    dataType: 'json',
                    success: function(response) {
                        $icon.removeClass('fas fa-spinner fa-spin');
                        if (response.success) {
                            if (action === 'add') {
                                $icon.addClass('fas fa-heart favorite').attr('title', 'Remove from Favorites');
                            } else {
                                $icon.addClass('far fa-heart').removeClass('favorite').attr('title', 'Add to Favorites');
                            }
                            Swal.fire({
                                icon: 'success',
                                title: (action === 'add' ? 'Added to Favorites' : 'Removed from Favorites'),
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            if (isCurrentlyFavorite) {
                                $icon.addClass('fas fa-heart favorite').attr('title', 'Remove from Favorites');
                            } else {
                                $icon.addClass('far fa-heart').removeClass('favorite').attr('title', 'Add to Favorites');
                            }
                            Swal.fire('Error', response.message || 'Could not update favorites.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $icon.removeClass('fas fa-spinner fa-spin');
                        console.error("Favorite AJAX Error:", status, error, xhr.responseText);
                        if (isCurrentlyFavorite) {
                            $icon.addClass('fas fa-heart favorite').attr('title', 'Remove from Favorites');
                        } else {
                            $icon.addClass('far fa-heart').removeClass('favorite').attr('title', 'Add to Favorites');
                        }
                        Swal.fire('Error', 'Failed to process favorite request. Please try again.', 'error');
                    },
                    complete: function() {
                        $icon.removeClass('disabled');
                    }
                });
            });

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

            $('body').on('click', '.add-to-cart-btn', function(e) {
                e.preventDefault();
                var $button = $(this);
                if ($button.closest('.single-dish, .single-restaurant').hasClass('closed-restaurant')) {
                    Swal.fire('Restaurant Closed', 'This restaurant is currently closed and cannot accept orders.', 'warning');
                    return;
                }
                var d_id = $button.data('d_id');
                var res_id = $button.data('res_id');
                var quantity = $button.closest('.dish-actions').find('.quantity-input').val();
                var originalText = $button.html();
                $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
                $.ajax({
                    type: "POST",
                    url: "ajax_add_to_cart.php",
                    data: { d_id: d_id, res_id: res_id, quantity: quantity, action: "add" },
                    dataType: "json",
                    success: function(response) {
                        if (response && response.status === "success") {
                            Swal.fire({ icon: 'success', title: 'Added!', text: response.message || 'Item added to cart.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                            updateCartCount();
                            $button.prop('disabled', false).html(originalText);
                        } else {
                            let errorIcon = 'error';
                            let errorTitle = 'Error!';
                            if (response && response.login_required) {
                                Swal.fire({ title: 'Login Required', text: 'Please log in to add items to your cart.', icon: 'warning', confirmButtonText: 'Login' })
                                    .then((result) => { if (result.isConfirmed) { window.location.href = 'login.php'; } });
                                $button.prop('disabled', false).html(originalText);
                            } else if (response && response.clear_cart_required) {
                                Swal.fire({
                                    title: 'Start New Order?',
                                    text: response.message,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#28a745',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Yes, start new order!',
                                    cancelButtonText: 'Cancel'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.ajax({
                                            type: "POST", url: "ajax_add_to_cart.php", data: { action: "clear_cart" }, dataType: "json",
                                            success: function(clearResponse) {
                                                if (clearResponse.status === 'success') {
                                                    $button.prop('disabled', false).html(originalText);
                                                    $button.click();
                                                } else {
                                                    Swal.fire('Error', 'Could not clear previous cart.', 'error');
                                                    $button.prop('disabled', false).html(originalText);
                                                }
                                            }, error: function() {
                                                Swal.fire('Error', 'Could not clear previous cart.', 'error');
                                                $button.prop('disabled', false).html(originalText);
                                            }
                                        });
                                    } else {
                                        $button.prop('disabled', false).html(originalText);
                                    }
                                });
                            } else {
                                Swal.fire(errorTitle, (response ? response.message : null) || 'Could not add item to cart.', errorIcon);
                                $button.prop('disabled', false).html(originalText);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Add to Cart AJAX Error: ", status, error, xhr.responseText);
                        Swal.fire('Error', 'An error occurred while contacting the server.', 'error');
                        $button.prop('disabled', false).html(originalText);
                    }
                });
            });

            $locationInput.on('input', function() {
                clearTimeout(typingTimer);
                const query = $(this).val().trim();
                if (query.length < 3) {
                    $addressSuggestions.slideUp(150);
                    return;
                }
                typingTimer = setTimeout(function() {
                    fetchAddressSuggestions(query);
                }, doneTypingInterval);
            });

            $locationInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $addressSuggestions.hide();
                    clearTimeout(typingTimer);
                    const address = $(this).val().trim();
                    if (!address) {
                        Swal.fire('Missing Address', 'Please enter an address.', 'warning');
                        return;
                    }
                    geocodeAndProcessAddress(address);
                }
            });

            $addressSuggestions.on('click', '.suggestion-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const address = $(this).text();
                const lat = $(this).data('lat');
                const lon = $(this).data('lon');
                const city = $(this).data('city');
                $locationInput.val(address)
                    .data('latitude', lat)
                    .data('longitude', lon)
                    .data('city', city);
                $addressSuggestions.slideUp(150);
                $locationOptions.slideUp(200);
                $locationToggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                updateResultsForCity(city, address);
            });

            $locationToggleBtn.on('click', function(e) {
                e.stopPropagation();
                var icon = $(this).find('i');
                $addressSuggestions.hide();
                $locationOptions.slideToggle(200, function() {
                    icon.toggleClass('fa-chevron-down fa-chevron-up');
                });
            });

            $useCurrentLocationLink.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $locationOptions.slideUp(200);
                $locationToggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude.toFixed(8);
                            const lon = position.coords.longitude.toFixed(8);
                            reverseGeocode(lat, lon);
                        },
                        function(error) {
                            console.error("Geolocation error: ", error);
                            let message = 'Could not get your location.';
                            switch(error.code) {
                                case error.PERMISSION_DENIED: message = "Location permission denied by browser."; break;
                                case error.POSITION_UNAVAILABLE: message = "Location information is unavailable."; break;
                                case error.TIMEOUT: message = "The request to get user location timed out."; break;
                            }
                            $locationInput.val('');
                            Swal.fire('Location Error', message, 'error');
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                } else {
                    $locationInput.val('');
                    Swal.fire('Not Supported', 'Geolocation is not supported by your browser.', 'warning');
                }
            });

            $verifyAddressLink.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $locationOptions.slideUp(200);
                $locationToggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                const address = $locationInput.val().trim();
                if (!address) {
                    Swal.fire('Missing Address', 'Please enter an address to verify.', 'warning');
                    return;
                }
                geocodeAndProcessAddress(address);
            });

            $saveAddressLink.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $locationOptions.slideUp(200);
                $locationToggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                if (!isLoggedIn) return;
                const address = $locationInput.val().trim();
                const latitude = $locationInput.data('latitude');
                const longitude = $locationInput.data('longitude');
                const city = $locationInput.data('city');
                if (!address || !latitude || !longitude || !city || city === 'Unknown') {
                    Swal.fire('Verification Needed', 'Address must be successfully verified before saving.', 'warning');
                    if (address) geocodeAndProcessAddress(address);
                    return;
                }
                saveAddress(address, latitude, longitude, city);
            });

            $(document).on('click', function(e) {
                if (!$locationInputGroup.is(e.target) && $locationInputGroup.has(e.target).length === 0 &&
                    !$addressSuggestions.is(e.target) && $addressSuggestions.has(e.target).length === 0) {
                    if ($addressSuggestions.is(':visible')) {
                        $addressSuggestions.slideUp(150);
                    }
                    if ($locationOptions.is(':visible')) {
                        $locationOptions.slideUp(200);
                        $locationToggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                }
            });

            // --- Veg Mode Toggle JavaScript --- //
            const $vegToggle = $('#veg-toggle');
            const $vegThemeStylesheet = $('#veg-theme-stylesheet');

            function toggleVegTheme(isVegOnly) {
                if (isVegOnly) {
                    $vegThemeStylesheet.prop('disabled', false);
                } else {
                    $vegThemeStylesheet.prop('disabled', true);
                }
            }

            const savedVegState = sessionStorage.getItem('vegToggleState') === 'true';
            $vegToggle.prop('checked', savedVegState);
            toggleVegTheme(savedVegState);

            $vegToggle.on('change', function() {
                const isVegOnly = $(this).is(':checked');
                sessionStorage.setItem('vegToggleState', isVegOnly);
                toggleVegTheme(isVegOnly);

                Swal.fire({
                    icon: 'info',
                    title: isVegOnly ? 'Vegetarian Mode On' : 'Showing All Restaurants',
                    text: isVegOnly ? 'Now showing only vegetarian restaurants and dishes.' : 'Now showing all restaurants and dishes.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                if (isLoggedIn) {
                    const newState = isVegOnly ? 1 : 0;
                    $.ajax({
                        url: 'update_veg_mode.php',
                        method: 'POST',
                        data: { user_id: userId, new_state: newState },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status !== 'success') {
                                console.error('Failed to update veg mode on server:', response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Veg Mode AJAX Error (server update):', status, error, xhr.responseText);
                        }
                    });
                }
                loadResults($searchInput.val().trim(), getCurrentCategoryId(), true);
            });

            // --- Animated Search Placeholder --- //
            const $searchInputForAnimation = $('#search-input');
            const foodNames = [
                "Biryani...",
                "Paneer Butter Masala...",
                "Samosas...",
                "Dosa...",
                "Chole Bhature...",
                "Tandoori Chicken...",
                "Pizza...",
                "Noodles..."
            ];
            let wordIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            let typingTimeout;

            const typeEffect = () => {
                const currentWord = foodNames[wordIndex];
                const typingSpeed = isDeleting ? 80 : 150;
                const textToShow = isDeleting ?
                    currentWord.substring(0, charIndex - 1) :
                    currentWord.substring(0, charIndex + 1);
                $searchInputForAnimation.attr('placeholder', textToShow);
                if (isDeleting) {
                    charIndex--;
                } else {
                    charIndex++;
                }
                if (!isDeleting && charIndex === currentWord.length) {
                    isDeleting = true;
                    typingTimeout = setTimeout(typeEffect, 2000);
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    wordIndex = (wordIndex + 1) % foodNames.length;
                    typingTimeout = setTimeout(typeEffect, 500);
                } else {
                    typingTimeout = setTimeout(typeEffect, typingSpeed);
                }
            };

            if ($searchInputForAnimation.val().trim() === '') {
                typeEffect();
                $searchInputForAnimation.on('focus', () => {
                    clearTimeout(typingTimeout);
                    $searchInputForAnimation.attr('placeholder', $searchInputForAnimation.data('placeholder'));
                });
                $searchInputForAnimation.on('blur', () => {
                    if ($searchInputForAnimation.val().trim() === '') {
                        wordIndex = 0;
                        charIndex = 0;
                        isDeleting = false;
                        typeEffect();
                    }
                });
            } else {
                $searchInputForAnimation.attr('placeholder', $searchInputForAnimation.data('placeholder'));
            }

            var loadResults = function(searchTerm = '', categoryId = 0, updateUrl = true) {
                const isVegOnly = $vegToggle.is(':checked');
                $realContentWrapper.css('visibility', 'hidden').css('opacity', 0);
                $skeletonPlaceholder.show();
                let resultTitleText = 'Featured Restaurants';
                let catName = '';
                if (categoryId > 0) {
                    catName = $(`.category-item a[href*="cat_id=${categoryId}"] .category-name`).text().trim();
                }
                if (searchTerm) {
                    resultTitleText = `Search Results for "${searchTerm}"`;
                    if (categoryId > 0 && catName) resultTitleText += ` in category: ${catName}`;
                    else if (categoryId > 0) resultTitleText += ` in selected category`;
                } else if (categoryId > 0 && catName) {
                    resultTitleText = `Restaurants in category: ${catName}`;
                } else if (categoryId > 0) {
                    resultTitleText = 'Restaurants in selected category';
                } else {
                    const currentCity = $userCityDisplay.text().replace('Select City','').replace(/<[^>]*>/g, '').trim();
                    if (currentCity && currentCity !== 'Unknown') resultTitleText = `Featured Restaurants in ${currentCity}`;
                }
                if (isVegOnly) resultTitleText += ' (Vegetarian Only)';
                $resultsTitle.text(resultTitleText);
                $.ajax({
                    url: 'ajax_search_results.php',
                    method: 'POST',
                    data: { search: searchTerm, cat_id: categoryId, veg_only: isVegOnly ? 1 : 0 },
                    dataType: 'html',
                    success: function(response) {
                        $skeletonPlaceholder.hide();
                        $realContentWrapper.html(response);
                        setTimeout(() => $realContentWrapper.css('visibility', 'visible').css('opacity', 1), 50);
                        if (updateUrl && $('#search-results').length) {
                            $('html, body').animate({ scrollTop: $('#search-results').offset().top - 80 }, 500);
                        }
                        if (updateUrl) {
                            const newUrl = buildUrl(searchTerm, categoryId);
                            const state = { search: searchTerm, cat_id: categoryId, title: resultTitleText };
                            if (history.state === null && (searchTerm || categoryId > 0)) {
                                history.replaceState(state, '', newUrl);
                            } else {
                                history.pushState(state, '', newUrl);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Search Error:", status, error, xhr.responseText);
                        $skeletonPlaceholder.hide();
                        $realContentWrapper.html('<div class="col-sm-12 text-center"><p class="text-danger">Error loading results.</p></div>').css('visibility', 'visible').css('opacity', 1);
                        Swal.fire('Error', 'Could not load results. Please check your connection.', 'error');
                    }
                });
            };

            // --- Initializations ---
            loadUserCity();
            if (isLoggedIn) {
                updateCartCount();
            } else {
                $cartNotificationDot.hide();
            }
        });
    </script>
</body>
</html>
