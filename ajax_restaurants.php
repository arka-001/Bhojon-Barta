<?php
session_start();
include("connection/connect.php");
error_reporting(0);

// Get parameters
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$veg_only = isset($_POST['veg_only']) && $_POST['veg_only'] == 1;
$user_city = isset($_POST['city']) ? trim($_POST['city']) : '';
$output = '';

// Sanitize inputs
$search_safe = mysqli_real_escape_string($db, $search);
$user_city_safe = mysqli_real_escape_string($db, $user_city);

// Build query
$query = "SELECT r.*, rc.c_name 
          FROM restaurant r 
          LEFT JOIN res_category rc ON r.c_id = rc.c_id";

// Initialize where clauses
$where_clauses = [];

// Search filter
if (!empty($search_safe)) {
    $where_clauses[] = "(r.title LIKE '%$search_safe%' OR r.address LIKE '%$search_safe%')";
}

// City filter
if (!empty($user_city_safe)) {
    $where_clauses[] = "LOWER(r.city) = LOWER('$user_city_safe')";
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY r.rs_id DESC";
$ress = mysqli_query($db, $query);
$has_restaurants = mysqli_num_rows($ress) > 0;

if ($has_restaurants) {
    while ($rows = mysqli_fetch_array($ress)) {
        $is_available = true;
        $is_veg = $rows['diet_type'] === 'veg';
        // City check (already filtered in query, but kept for consistency)
        if ($user_city) {
            $is_available = strtolower(trim($rows['city'])) === strtolower(trim($user_city));
        }
        // Apply non-veg class in veg mode
        $card_class = $is_available ? ($veg_only && !$is_veg ? ' non-veg' : '') : ' unavailable';
        $output .= '<div class="restaurant-card' . $card_class . '" data-diet-type="' . htmlspecialchars($rows['diet_type']) . '">';
        $output .= '<div class="rest-logo">';
        if ($is_available && (!$veg_only || $is_veg)) {
            $output .= '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">';
        }
        $output .= '<img src="admin/Res_img/' . htmlspecialchars($rows['image']) . '" alt="' . htmlspecialchars($rows['title']) . '">';
        if ($is_available && (!$veg_only || $is_veg)) {
            $output .= '</a>';
        }
        $output .= '</div>';
        $output .= '<div class="rest-info">';
        $output .= '<div class="rest-descr">';
        if ($is_available && (!$veg_only || $is_veg)) {
            $output .= '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a></h5>';
        } else {
            $output .= '<h5>' . htmlspecialchars($rows['title']) . '</h5>';
        }
        $output .= '<span>' . htmlspecialchars($rows['address']) . '</span>';
        // Add non-veg notice in veg mode
        if ($veg_only && !$is_veg) {
            $output .= '<div class="non-veg-notice">This is not a fully vegetarian restaurant.</div>';
        }
        $output .= '</div>';
        $output .= '</div>';
        if ($is_available) {
            if (!$veg_only || $is_veg) {
                $output .= '<a href="dishes.php?res_id=' . $rows['rs_id'] . '" class="btn btn-view-menu"><i class="fas fa-concierge-bell"></i> View Menu</a>';
            } else {
                $output .= '<span class="non-veg-notice-btn">Non-Vegetarian Restaurant</span>';
            }
        } else {
            $output .= '<span class="service-unavailable">Service Not Available</span>';
        }
        $output .= '</div>';
    }
} else {
    $message = 'No restaurants found';
    if ($search) {
        $message .= ' for "' . htmlspecialchars($search) . '"';
    }
    if ($user_city) {
        $message .= ' in ' . htmlspecialchars($user_city);
    } else {
        $message .= '. Please select a city.';
    }
    $output .= '<div class="col-12 text-center"><p>' . $message . '</p></div>';
}

echo $output;
?>