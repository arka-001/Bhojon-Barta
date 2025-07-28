<?php
session_start();
require 'connection/connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1); // Remove in production

if (!isset($_SESSION['user_id'])) {
    error_log("favorites.php: No user_id, redirecting to login.php");
    header('Location: login.php');
    exit;
}

$u_id = intval($_SESSION['user_id']);
error_log("favorites.php: User ID = $u_id");

// Test database connection
if (!$db) {
    error_log("favorites.php: Database connection failed: " . mysqli_connect_error());
    die("Database connection failed.");
}

// Fetch favorite restaurants
$query_rest = "
    SELECT r.rs_id, r.title, r.image, r.address, r.city
    FROM user_favorite_restaurants uf
    JOIN restaurant r ON uf.rs_id = r.rs_id
    WHERE uf.u_id = ?
";
$stmt_rest = mysqli_prepare($db, $query_rest);
if (!$stmt_rest) {
    error_log("favorites.php: Prepare failed for restaurants: " . mysqli_error($db));
    die("Database error: Unable to fetch favorite restaurants.");
}
mysqli_stmt_bind_param($stmt_rest, 'i', $u_id);
mysqli_stmt_execute($stmt_rest);
$result_rest = mysqli_stmt_get_result($stmt_rest);
if (!$result_rest) {
    error_log("favorites.php: Query failed for restaurants: " . mysqli_error($db));
    die("Database error: Unable to fetch favorite restaurants.");
}
$restaurants = [];
while ($row = mysqli_fetch_assoc($result_rest)) {
    $restaurants[] = $row;
}

// Fetch favorite dishes
$query_dish = "
    SELECT d.d_id, d.rs_id, d.title, d.img AS image, r.title AS restaurant_title
    FROM user_favorite_dishes uf
    JOIN dishes d ON uf.d_id = d.d_id
    JOIN restaurant r ON d.rs_id = r.rs_id
    WHERE uf.u_id = ?
";
$stmt_dish = mysqli_prepare($db, $query_dish);
if (!$stmt_dish) {
    error_log("favorites.php: Prepare failed for dishes: " . mysqli_error($db));
    die("Database error: Unable to fetch favorite dishes.");
}
mysqli_stmt_bind_param($stmt_dish, 'i', $u_id);
mysqli_stmt_execute($stmt_dish);
$result_dish = mysqli_stmt_get_result($stmt_dish);
if (!$result_dish) {
    error_log("favorites.php: Query failed for dishes: " . mysqli_error($db));
    die("Database error: Unable to fetch favorite dishes.");
}
$dishes = [];
while ($row = mysqli_fetch_assoc($result_dish)) {
    $dishes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Favorites</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, #f0f4f8, #e9ecef);
            color: #2d3436;
            line-height: 1.6;
        }
        /* .container {
            padding: 25px 15px;
            max-width: 1140px;
            margin: 0 auto; */
        }
        h2 {
            margin-bottom: 30px;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 2rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        .restaurant-grid, .dish-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin: 60px 0;
            animation: fadeIn 0.5s ease-in;
        }
        .single-restaurant, .single-dish {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.5s ease-in;
            
        }
        .single-restaurant:hover, .single-dish:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .restaurant-logo, .dish-logo {
            width: 90px;
            height: 90px;
            margin-right: 20px;
            position: relative;
            overflow: hidden;
            border-radius: 50%;
        }
        .restaurant-logo img, .dish-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #eee;
            transition: transform 0.3s ease;
        }
        .restaurant-logo:hover img, .dish-logo:hover img {
            transform: scale(1.1);
        }
        .restaurant-logo::after, .dish-logo::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 50%;
        }
        .restaurant-logo:hover::after, .dish-logo:hover::after {
            opacity: 1;
        }
        .restaurant-content, .dish-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .restaurant-content h5, .dish-content h5 {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .restaurant-content h5 a, .dish-content h5 a {
            color: #1a1a1a;
            text-decoration: none;
        }
        .restaurant-content h5 a:hover {
            color: #007bff;
        }
        .restaurant-details, .restaurant-name {
            color: #636e72;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .restaurant-details i, .restaurant-name i {
            color: #007bff;
            margin-right: 5px;
        }
        .favorite-icon {
            cursor: pointer;
            font-size: 1.2rem;
            color: #e03131;
            transition: color 0.3s ease, transform 0.2s ease;
        }
        .favorite-icon:hover {
            color: #c0392b;
        }
        .view-menu-btn {
            background: linear-gradient(90deg, #007bff, #00c4ff);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .view-menu-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .text-center {
            color: #636e72;
            font-size: 1rem;
            margin: 20px 0;
            text-align: center;
        }
        .swal2-custom-popup {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .swal2-custom-title {
            font-weight: 600;
            color: #1a1a1a;
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .restaurant-grid, .dish-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .single-restaurant, .single-dish {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            .restaurant-logo, .dish-logo {
                margin: 0 auto 20px;
                width: 90px;
                height: 90px;
            }
            .restaurant-content h5, .dish-content h5 {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
            .view-menu-btn, .favorite-icon {
                padding: 10px;
                font-size: 1rem;
            }
            .view-menu-btn {
                width: 100%;
                padding: 12px;
            }
        }
    </style>
 <?php include 'header.php'; ?>
</head>
<body>
   
    <div class="container">
        <h2>My Favorite Restaurants</h2>
        <?php if (empty($restaurants)): ?>
            <p class="text-center">You have no favorite restaurants yet.</p>
        <?php else: ?>
            <div class="restaurant-grid">
                <?php foreach ($restaurants as $item): ?>
                    <div class="single-restaurant">
                        <div class="restaurant-logo">
                            <img src="admin/Res_img/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 onerror="this.src='images/default.jpg'" 
                                 loading="lazy">
                        </div>
                        <div class="restaurant-content">
                            <h5>
                                <a href="dishes.php?res_id=<?php echo $item['rs_id']; ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                                <div>
                                    <i class="fas fa-heart favorite-icon favorite" 
                                       data-type="restaurant" 
                                       data-id="<?php echo $item['rs_id']; ?>" 
                                       title="Remove from Favorites"
                                       role="button"
                                       aria-label="Remove <?php echo htmlspecialchars($item['title']); ?> from favorites"
                                       tabindex="0"></i>
                                    <a href="dishes.php?res_id=<?php echo $item['rs_id']; ?>" 
                                       class="view-menu-btn" 
                                       role="button"
                                       aria-label="View menu for <?php echo htmlspecialchars($item['title']); ?>">
                                        View Menu
                                    </a>
                                </div>
                            </h5>
                            <div class="restaurant-details">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['address']); ?></span><br>
                                <span><i class="fas fa-city"></i> <?php echo htmlspecialchars($item['city'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2>My Favorite Dishes</h2>
        <?php if (empty($dishes)): ?>
            <p class="text-center">You have no favorite dishes yet.</p>
        <?php else: ?>
            <div class="dish-grid">
                <?php foreach ($dishes as $item): ?>
                    <div class="single-dish">
                        <div class="dish-logo">
                            <img src="admin/Res_img/dishes/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 onerror GirlsApp="0" src="images/default.jpg" 
                                 loading="lazy">
                        </div>
                        <div class="dish-content">
                            <h5>
                                <span><?php echo htmlspecialchars($item['title']); ?></span>
                                <div>
                                    <i class="fas fa-heart favorite-icon favorite" 
                                       data-type="dish" 
                                       data-id="<?php echo $item['d_id']; ?>" 
                                       title="Remove from Favorites"
                                       role="button"
                                       aria-label="Remove <?php echo htmlspecialchars($item['title']); ?> from favorites"
                                       tabindex="0"></i>
                                    <a href="dishes.php?res_id=<?php echo $item['rs_id']; ?>" 
                                       class="view-menu-btn" 
                                       role="button"
                                       aria-label="View menu for <?php echo htmlspecialchars($item['restaurant_title']); ?>">
                                        View Menu
                                    </a>
                                </div>
                            </h5>
                            <div class="restaurant-name">
                                <i class="fas fa-utensils"></i> <?php echo htmlspecialchars($item['restaurant_title']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
     <?php include 'chatbot.php'; ?>

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

            // Favorite Removal Logic
            $('.favorite-icon').on('click', function() {
                var $icon = $(this);
                var type = $icon.data('type');
                var id = $icon.data('id');
                var title = $icon.closest('.single-' + type).find('h5 a, h5 span').text().trim();

                console.log('Favorite icon clicked: type=' + type + ', id=' + id + ', title=' + title);

                Swal.fire({
                    title: 'Remove Favorite',
                    text: `Are you sure you want to remove "${title}" from your favorites?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        popup: 'swal2-custom-popup',
                        title: 'swal2-custom-title'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('User confirmed removal for ' + type + ' with id=' + id);

                        $icon.css('transform', 'scale(1.3)').css('transition', 'transform 0.2s ease');
                        setTimeout(() => $icon.css('transform', 'scale(1)'), 200);

                        $.ajax({
                            url: 'handle_favorites.php',
                            type: 'POST',
                            data: { action: 'remove', type: type, id: id },
                            dataType: 'json',
                            beforeSend: function() {
                                $icon.addClass('fa-spin');
                                console.log('Sending AJAX request to handle_favorites.php');
                            },
                            success: function(response) {
                                $icon.removeClass('fa-spin');
                                console.log('AJAX response: ', response);
                                if (response.success) {
                                    $icon.closest('.single-' + type).fadeOut(300, function() {
                                        $(this).remove();
                                        var gridClass = type === 'restaurant' ? '.restaurant-grid' : '.dish-grid';
                                        if ($(gridClass).children().length === 0) {
                                            $(gridClass).replaceWith('<p class="text-center">You have no favorite ' + type + 's yet.</p>');
                                        }
                                    });
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Removed from Favorites',
                                        text: `"${title}" has been removed from your favorites.`,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        customClass: {
                                            popup: 'swal2-custom-popup',
                                            title: 'swal2-custom-title'
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message,
                                        customClass: {
                                            popup: 'swal2-custom-popup',
                                            title: 'swal2-custom-title'
                                        }
                                    });
                                    console.error('Removal failed: ' + response.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                $icon.removeClass('fa-spin');
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to process request. Please try again.',
                                    customClass: {
                                        popup: 'swal2-custom-popup',
                                        title: 'swal2-custom-title'
                                    }
                                });
                                console.error('AJAX error: ', status, error);
                                console.log('Response text: ', xhr.responseText);
                            }
                        });
                    } else {
                        console.log('User canceled removal');
                    }
                });
            });

            // Keyboard navigation for favorite icons
            $('.favorite-icon').on('keypress', function(e) {
                if (e.which === 13 || e.which === 32) {
                    console.log('Favorite icon keypress: Enter or Space');
                    $(this).trigger('click');
                }
            });
        });
    </script>
</body>
</html>
<?php
mysqli_stmt_close($stmt_rest);
mysqli_stmt_close($stmt_dish);
mysqli_close($db);
?>