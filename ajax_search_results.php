
<?php
session_start();
include("connection/connect.php"); // Ensure this path is correct
error_reporting(0);

// Get parameters
$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
$search_term = isset($_POST['search']) ? trim(mysqli_real_escape_string($db, $_POST['search'])) : '';
$veg_only = isset($_POST['veg_only']) && $_POST['veg_only'] == 1 ? true : false; // Handle veg_only parameter
$filter_by_category = ($cat_id > 0);
$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : null;

// Fetch user's city if logged in
$user_city = null;
if ($user_id) {
    $user_query = mysqli_query($db, "SELECT city FROM users WHERE u_id = '$user_id' AND city IS NOT NULL");
    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $user_city = mysqli_fetch_assoc($user_query)['city'];
    }
}

$found_dishes = false;
$found_restaurants = false;
$output_html = '';

if (!empty($search_term)) {
    // Fetch Dishes
    $dish_sql = "SELECT d.*, r.title AS restaurant_name, r.rs_id, r.is_open AS restaurant_is_open, r.diet_type AS restaurant_diet_type,
                d.offer_price,
                EXISTS (SELECT 1 FROM user_favorite_dishes uf WHERE uf.u_id = '$user_id' AND uf.d_id = d.d_id) AS is_favorite
                FROM dishes d
                JOIN restaurant r ON d.rs_id = r.rs_id
                WHERE (d.title LIKE '%$search_term%' OR d.slogan LIKE '%$search_term%')";
    if ($filter_by_category) {
        $dish_sql .= " AND r.c_id = '$cat_id'";
    }
    if ($user_city) {
        $user_city_safe = mysqli_real_escape_string($db, $user_city);
        $dish_sql .= " AND r.city = '$user_city_safe'";
    }
    if ($veg_only) {
        $dish_sql .= " AND r.diet_type = 'veg'";
        // If dish-level filtering is desired, uncomment after correcting dish diet_type data:
        // $dish_sql .= " AND d.diet_type IN ('veg', 'vegan')";
    }
    $dish_sql .= " ORDER BY d.d_id DESC LIMIT 9";
    $dish_ress = mysqli_query($db, $dish_sql);
    $found_dishes = mysqli_num_rows($dish_ress) > 0;

    if ($found_dishes) {
        $output_html .= '<h5>Matching Dishes</h5>';
        $output_html .= '<div class="dish-grid mb-4">';
        while ($dish_row = mysqli_fetch_array($dish_ress)) {
            $is_favorite = $dish_row['is_favorite'];
            $restaurant_is_open = $dish_row['restaurant_is_open'];
            $dish_class = $restaurant_is_open ? '' : 'closed-restaurant';

            $output_html .= '<div class="single-dish ' . $dish_class . '" data-diet-type="' . htmlspecialchars($dish_row['restaurant_diet_type']) . '">
                            <div class="dish-wrap">
                                <div class="dish-logo">
                                    <img src="admin/Res_img/dishes/' . htmlspecialchars($dish_row['img']) . '" alt="' . htmlspecialchars($dish_row['title']) . '" loading="lazy">
                                </div>
                                <div class="dish-content">
                                    <h5>' . htmlspecialchars($dish_row['title']);
            if ($user_id && $restaurant_is_open) {
                $output_html .= '<i class="fas fa-heart favorite-icon ' . ($is_favorite ? 'favorite' : '') . '"
                                  data-type="dish"
                                  data-id="' . $dish_row['d_id'] . '"
                                  title="' . ($is_favorite ? 'Remove from Favorites' : 'Add to Favorites') . '"></i>';
            }
            $output_html .= '</h5>
                            <div class="restaurant-name">From: <a href="dishes.php?res_id=' . $dish_row['rs_id'] . '">' . htmlspecialchars($dish_row['restaurant_name']) . '</a></div>';

            // Updated Price Display Logic
            $original_price = number_format((float)$dish_row['price'], 2);
            $offer_price = isset($dish_row['offer_price']) && $dish_row['offer_price'] !== null && (float)$dish_row['offer_price'] < (float)$dish_row['price'] ? number_format((float)$dish_row['offer_price'], 2) : null;
            $discount_percentage = $offer_price ? round(((float)$dish_row['price'] - (float)$dish_row['offer_price']) / (float)$dish_row['price'] * 100) : null;

            $output_html .= '<div class="dish-price-container">';
            if ($offer_price) {
                $output_html .= '<span class="offer-price">₹' . $offer_price . '</span>
                                 <span class="original-price">₹' . $original_price . '</span>
                                 <span class="discount-badge">' . $discount_percentage . '% OFF</span>';
            } else {
                $output_html .= '<span class="offer-price">₹' . $original_price . '</span>';
            }
            $output_html .= '</div>';
            // End Updated Price Display Logic

            $output_html .= '<div class="dish-description">' . htmlspecialchars($dish_row['slogan']) . '</div>
                        </div>'; // End dish-content
            if ($restaurant_is_open && $dish_row['is_available']) {
                $output_html .= '<div class="dish-actions">
                                    <div class="quantity-input-group">
                                        <button class="quantity-btn quantity-down" type="button">-</button>
                                        <input class="quantity-input" type="text" name="quantity" value="1" size="2" readonly>
                                        <button class="quantity-btn quantity-up" type="button">+</button>
                                    </div>
                                    <button class="btn btn-sm add-to-cart-btn" data-d_id="' . $dish_row['d_id'] . '" data-res_id="' . $dish_row['rs_id'] . '">Add To Cart</button>
                                </div>';
            } else {
                $output_html .= '<div class="dish-actions"><span class="restaurant-status status-closed">' . ($dish_row['is_available'] ? 'Restaurant Closed' : 'Dish Unavailable') . '</span></div>';
            }
            $output_html .= '</div></div>'; // End dish-wrap and single-dish
        }
        $output_html .= '</div>'; // End dish-grid
    }

    // Fetch Restaurants (No changes needed here)
    $res_sql = "SELECT r.*, rc.c_name, r.is_open, r.diet_type,
               EXISTS (SELECT 1 FROM user_favorite_restaurants uf WHERE uf.u_id = '$user_id' AND uf.rs_id = r.rs_id) AS is_favorite
               FROM restaurant r
               LEFT JOIN res_category rc ON r.c_id = rc.c_id
               WHERE (r.title LIKE '%$search_term%' OR r.address LIKE '%$search_term%')";
    if ($filter_by_category) {
        $res_sql .= " AND r.c_id = '$cat_id'";
    }
    if ($user_city) {
        $user_city_safe = mysqli_real_escape_string($db, $user_city);
        $res_sql .= " AND r.city = '$user_city_safe'";
    }
    if ($veg_only) {
        $res_sql .= " AND r.diet_type = 'veg'";
    }
    $res_sql .= " ORDER BY r.rs_id DESC LIMIT 9";
    $res_ress = mysqli_query($db, $res_sql);
    $found_restaurants = mysqli_num_rows($res_ress) > 0;

    if ($found_restaurants) {
        if ($found_dishes) $output_html .= '<h5 class="mt-4">Matching Restaurants</h5>';
        $output_html .= '<div class="restaurant-grid">';
        while ($rows = mysqli_fetch_array($res_ress)) {
            $cat_name_class = isset($rows['c_name']) ? str_replace(' ', '-', $rows['c_name']) : '';
            $status_text = $rows['is_open'] ? 'Open' : 'Closed';
            $status_class = $rows['is_open'] ? 'status-open' : 'status-closed';
            $is_closed = !$rows['is_open'];
            $restaurant_class = $is_closed ? 'closed-restaurant' : '';
            $is_favorite = $rows['is_favorite'];

            $output_html .= '<div class="single-restaurant all ' . htmlspecialchars($cat_name_class) . ' ' . $restaurant_class . '" data-diet-type="' . htmlspecialchars($rows['diet_type']) . '">
                            <div class="restaurant-wrap">
                                <div class="restaurant-logo">';
            if ($rows['is_open']) {
                $output_html .= '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">
                                    <img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">
                                 </a>';
            } else {
                $output_html .= '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">';
            }
            $output_html .= '</div>
                            <div class="restaurant-content">';
            if ($rows['is_open']) {
                $output_html .= '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a>';
            } else {
                $output_html .= '<h5>' . htmlspecialchars($rows['title']);
            }
            if ($user_id) {
                $output_html .= '<i class="fas fa-heart favorite-icon ' . ($is_favorite ? 'favorite' : '') . '"
                                  data-type="restaurant"
                                  data-id="' . $rows['rs_id'] . '"
                                  title="' . ($is_favorite ? 'Remove from Favorites' : 'Add to Favorites') . '"></i>';
            }
            $output_html .= '</h5>
                            <span>' . htmlspecialchars($rows['address']) . '</span>
                            <small>Category: ' . htmlspecialchars($rows['c_name'] ?? 'N/A') . '</small>
                            <small class="restaurant-status ' . $status_class . '">' . $status_text . '</small>
                        </div>
                            </div>
                          </div>';
        }
        $output_html .= '</div>';
    }

    if (!$found_dishes && !$found_restaurants) {
        $output_html .= '<div class="col-sm-12 text-center"><p>No results found for "' . htmlspecialchars($search_term) . '"' . ($user_city ? ' in your city' : '') . '. Try searching for a different restaurant or dish.</p></div>';
    }

} else {
    // Default restaurants query filtered by user's city
    $sql = "SELECT r.*, rc.c_name, r.is_open, r.diet_type,
           EXISTS (SELECT 1 FROM user_favorite_restaurants uf WHERE uf.u_id = '$user_id' AND uf.rs_id = r.rs_id) AS is_favorite
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
    if ($veg_only) {
        $where_clauses[] = "r.diet_type = 'veg'";
    }
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql .= " ORDER BY r.rs_id DESC LIMIT 9";
    $ress = mysqli_query($db, $sql);
    $found_restaurants = mysqli_num_rows($ress) > 0;

    if ($found_restaurants) {
        $output_html .= '<div class="restaurant-grid">';
        while ($rows = mysqli_fetch_array($ress)) {
            $cat_name_class = isset($rows['c_name']) ? str_replace(' ', '-', $rows['c_name']) : '';
            $status_text = $rows['is_open'] ? 'Open' : 'Closed';
            $status_class = $rows['is_open'] ? 'status-open' : 'status-closed';
            $is_closed = !$rows['is_open'];
            $restaurant_class = $is_closed ? 'closed-restaurant' : '';
            $is_favorite = $rows['is_favorite'];

            $output_html .= '<div class="single-restaurant all ' . htmlspecialchars($cat_name_class) . ' ' . $restaurant_class . '" data-diet-type="' . htmlspecialchars($rows['diet_type']) . '">
                            <div class="restaurant-wrap">
                                <div class="restaurant-logo">';
            if ($rows['is_open']) {
                $output_html .= '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">
                                    <img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">
                                 </a>';
            } else {
                $output_html .= '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '" loading="lazy">';
            }
            $output_html .= '</div>
                            <div class="restaurant-content">';
            if ($rows['is_open']) {
                $output_html .= '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a>';
            } else {
                $output_html .= '<h5>' . htmlspecialchars($rows['title']);
            }
            if ($user_id) {
                $output_html .= '<i class="fas fa-heart favorite-icon ' . ($is_favorite ? 'favorite' : '') . '"
                                  data-type="restaurant"
                                  data-id="' . $rows['rs_id'] . '"
                                  title="' . ($is_favorite ? 'Remove from Favorites' : 'Add to Favorites') . '"></i>';
            }
            $output_html .= '</h5>
                            <span>' . htmlspecialchars($rows['address']) . '</span>
                            <small>Category: ' . htmlspecialchars($rows['c_name'] ?? 'N/A') . '</small>
                            <small class="restaurant-status ' . $status_class . '">' . $status_text . '</small>
                        </div>
                            </div>
                          </div>';
        }
        $output_html .= '</div>';
    } else {
        $output_html .= '<div class="col-sm-12 text-center"><p>No restaurants found' . ($filter_by_category ? ' in this category' : '') . ($user_city ? ' in your city' : '') . '.</p></div>';
    }

    // Total restaurants count for "View All" button
    $total_sql = "SELECT COUNT(rs_id) as total FROM restaurant";
    $total_where = [];
    if ($user_city) {
        $user_city_safe = mysqli_real_escape_string($db, $user_city);
        $total_where[] = "city = '$user_city_safe'";
    }
    if ($veg_only) {
        $total_where[] = "diet_type = 'veg'";
    }
    if (!empty($total_where)) {
        $total_sql .= " WHERE " . implode(" AND ", $total_where);
    }
    $total_restaurants_query = mysqli_query($db, $total_sql);
    $total_restaurants = mysqli_fetch_assoc($total_restaurants_query)['total'];
    if ($total_restaurants > 9 && !$filter_by_category && empty($search_term)) {
        $output_html .= '<div class="text-center mt-4"><a href="restaurants.php" class="btn btn-primary">View All Restaurants</a></div>';
    }
}

echo $output_html;
?>
