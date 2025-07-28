<?php
session_start();
// error_reporting(0); // Keep errors visible during development

include("connection/connect.php"); // Make sure path is correct

// --- SMTP Mailer Function (Keep As Is) ---
function smtp_mailer($to, $subject, $msg) {
    include('smtp/PHPMailerAutoload.php'); // Ensure this path is correct
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Username = "bhojonbarta@gmail.com"; // Replace with your actual email
    $mail->Password = "zyys vops vyua zetu"; // Replace with your actual app password
    $mail->SetFrom("bhojonbarta@gmail.com"); // Replace with your actual email
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress($to);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => false
        )
    );
    return !$mail->Send() ? 'Error: ' . $mail->ErrorInfo : 'Sent';
}


// --- Determine Veg Mode (Reads from DB for logged-in user) ---
$user_veg_mode_enabled = false; // Default to off
$user_is_logged_in = !empty($_SESSION["user_id"]); // This will be used by header.php as well
$user_id = null; // Initialize user_id, also used by header.php if needed there directly

if ($user_is_logged_in) {
    $user_id = intval($_SESSION["user_id"]);
    $stmt_pref = $db->prepare("SELECT is_veg_mode FROM users WHERE u_id = ?");
    if ($stmt_pref) {
        $stmt_pref->bind_param("i", $user_id);
        $stmt_pref->execute();
        $result_pref = $stmt_pref->get_result();
        if ($result_pref && $result_pref->num_rows > 0) {
            $pref_row = $result_pref->fetch_assoc();
            if (isset($pref_row['is_veg_mode'])) {
                $user_veg_mode_enabled = ($pref_row['is_veg_mode'] == 1);
            } else {
                 error_log("Column 'is_veg_mode' not found in users table for user ID: " . $user_id);
            }
        }
        $stmt_pref->close();
    } else {
        error_log("Prepare statement failed for fetching veg mode in restaurants.php: " . $db->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Find the best restaurants near you.">
    <meta name="author" content="YourAppName">
    <link rel="icon" href="images/favicon.ico"> <!-- Add your actual favicon path -->
    <title>Restaurants - YourAppName</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet"> <!-- Consider removing if FA6 is primary -->
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet"> <!-- Your base styles -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- --- Styles for restaurants.php content --- -->
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f5f6fa; color: #333; }
        .top-links { background: linear-gradient(135deg, #ffffff, #f8f9fa); padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 20px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .links { display: flex; justify-content: center; gap: 2rem; list-style: none; padding: 0; margin: 0; }
        .link-item { text-align: center; padding: 1rem; transition: transform 0.3s ease; }
        .link-item:hover { transform: translateY(-5px); }
        .link-item span { display: block; font-size: 1.5rem; color: #007bff; font-weight: 700; margin-bottom: 0.5rem; }
        .link-item a { color: #2c3e50; text-decoration: none; font-weight: 500; }
        .search-container { max-width: 700px; margin: 0 auto 1rem auto; padding: 0 15px; }
        .search-form { position: relative; display: flex; align-items: center; }
        .search-input { width: 100%; padding: 12px 20px 12px 45px; border: none; border-radius: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-size: 16px; transition: all 0.3s ease; }
        .search-input:focus { outline: none; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        .search-btn { position: absolute; left: 5px; background: none; border: none; padding: 10px; color: #007bff; cursor: pointer; transition: color 0.3s ease; }
        .search-btn:hover { color: #0056b3; }
        .restaurant-card { background: #fff; border-radius: 15px; padding: 1.5rem; margin-bottom: 1.5rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; }
        .restaurant-card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .restaurant-card.unavailable { opacity: 0.7; /* filter: grayscale(80%); Consider if this is desired for closed ones */ }
        .rest-logo img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 2px solid #f1f3f5; margin-right: 20px; }
        .rest-info { flex-grow: 1; margin-right: 20px; }
        .rest-descr h5 { color: #2c3e50; font-weight: 600; margin-bottom: 0.5rem; font-size: 1.25rem; }
        .rest-descr span { color: #7f8c8d; font-size: 0.9rem; }
        .btn-view-menu { background: #007bff; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 25px; transition: all 0.3s ease; white-space: nowrap; }
        .btn-view-menu:hover { background: #0056b3; color: #fff; transform: scale(1.05); }
        .service-unavailable { color: #dc3545; font-weight: 500; font-size: 0.9rem; padding: 0.75rem 1.5rem; border-radius: 25px; background: rgba(248, 215, 218, 0.7); white-space: nowrap; border: 1px solid rgba(220, 53, 69, 0.3); }

        .veg-toggle-container { text-align: right; margin-top: -10px; margin-bottom: 1.5rem; padding-right: 15px; }
        .veg-toggle-group { display: inline-flex; align-items: center; background-color: #e9ecef; padding: 6px 12px; border-radius: 15px; cursor: pointer; transition: background-color 0.3s ease; }
        .veg-toggle-group:hover:not(.disabled) { background-color: #d3d9df; }
        .veg-toggle-label { display: flex; align-items: center; margin: 0; cursor: pointer; }
        #veg-toggle { display: none; }
        .veg-toggle-label i.fa-leaf { color: #6c757d; margin-right: 6px; transition: color 0.3s ease; font-size: 0.9em; }
        #veg-toggle:checked + i.fa-leaf { color: #28a745; }
        .veg-toggle-text { font-size: 0.85rem; font-weight: 500; color: #495057; }
        .veg-toggle-group.disabled { cursor: not-allowed; opacity: 0.6; }

        @media (max-width: 768px) {
            .links { flex-direction: column; gap: 1rem; }
            .restaurant-card { flex-direction: column; text-align: center; padding: 1rem; }
            .rest-logo img { margin: 0 auto 1rem; }
            .rest-info { margin-right: 0; margin-bottom: 1rem; }
            .btn-view-menu, .service-unavailable { width: 100%; text-align: center; }
            .veg-toggle-container { text-align: center; margin-top: 0.5rem; padding-right: 0; }
        }
        img { display: inline-block; vertical-align: middle; } /* Basic image error prevention */
    </style>
</head>

<body>
    <?php include 'header.php'; // Include the common header ?>

    <div class="page-wrapper">
        <div class="top-links">
             <div class="container">
                 <ul class="row links">
                     <li class="col-xs-12 col-sm-4 link-item active"><span>1</span><a href="restaurants.php"><i class="fas fa-store"></i> Choose Restaurant</a></li>
                     <li class="col-xs-12 col-sm-4 link-item"><span>2</span><a href="#"><i class="fas fa-shopping-bag"></i> Pick Your favorite food</a></li>
                     <li class="col-xs-12 col-sm-4 link-item"><span>3</span><a href="#"><i class="fas fa-money-bill-wave"></i> Order and Pay Online</a></li>
                 </ul>
             </div>
        </div>

        <div class="container">
            <!-- Search Container -->
            <div class="search-container">
                <form action="restaurants.php" method="GET" class="search-form">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    <input type="text" class="search-input" name="search" placeholder="Search restaurants..." aria-label="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
            </div>
            <!-- End Search Container -->

            <!-- Veg Toggle Container -->
            <div class="veg-toggle-container">
                <div class="veg-toggle-group <?php echo !$user_is_logged_in ? 'disabled' : ''; ?>"
                     title="<?php echo !$user_is_logged_in ? 'Login to use Veg Only mode' : 'Toggle Vegetarian Only Mode'; ?>">
                    <label for="veg-toggle" class="veg-toggle-label">
                        <input type="checkbox" id="veg-toggle" name="veg-toggle"
                               <?php echo $user_veg_mode_enabled ? 'checked' : ''; ?>
                               <?php echo !$user_is_logged_in ? 'disabled' : ''; ?>>
                        <i class="fas fa-leaf"></i>
                        <span class="veg-toggle-text">Veg Only</span>
                    </label>
                </div>
            </div>
            <!-- End Veg Toggle Container -->

            <section class="restaurants-page">
                <div class="row">
                    <div class="col-12" id="restaurants-list">
                        <?php
                        // Fetch user's city
                        $user_city = null;
                         if ($user_is_logged_in && $user_id) {
                             $stmt_city = $db->prepare("SELECT city FROM users WHERE u_id = ? AND city IS NOT NULL");
                             if($stmt_city){
                                $stmt_city->bind_param("i", $user_id);
                                $stmt_city->execute();
                                $result_city = $stmt_city->get_result();
                                if ($result_city && $result_city->num_rows > 0) {
                                    $user_city_data = $result_city->fetch_assoc();
                                    if (!empty($user_city_data['city'])) { $user_city = $user_city_data['city']; }
                                }
                                $stmt_city->close();
                             } else { error_log("Prepare failed for city fetch in restaurants.php: ".$db->error); }
                        } elseif (!empty($_SESSION['selected_city'])) {
                            $user_city = $_SESSION['selected_city'];
                        }

                        // --- Build the main restaurant query ---
                        $query = "SELECT * FROM restaurant";
                        $conditions = [];
                        $params = [];
                        $types = "";

                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $search_term_sql = "%" . trim($_GET['search']) . "%";
                            $conditions[] = "(title LIKE ? OR address LIKE ?)";
                            $params[] = $search_term_sql;
                            $params[] = $search_term_sql;
                            $types .= "ss";
                        }

                        if ($user_is_logged_in && $user_veg_mode_enabled) {
                            $conditions[] = "diet_type = 'veg'";
                        }

                        // If a city is determined, add it to the SQL query for better performance
                        // This is an alternative to filtering in PHP loop, potentially better for large datasets
                        if ($user_city) {
                            $conditions[] = "LOWER(city) = LOWER(?)"; // Case-insensitive city comparison in SQL
                            $params[] = trim($user_city);
                            $types .= "s";
                        }


                        if (!empty($conditions)) {
                            $query .= " WHERE " . implode(" AND ", $conditions);
                        }
                        $query .= " ORDER BY is_open DESC, rs_id DESC"; // Prioritize open restaurants

                        $stmt_main = $db->prepare($query);

                        if ($stmt_main) {
                            if (!empty($params)) {
                                $stmt_main->bind_param($types, ...$params);
                            }
                            $stmt_main->execute();
                            $ress = $stmt_main->get_result();

                            if ($ress) {
                                $has_restaurants_in_query = $ress->num_rows > 0;

                                if ($has_restaurants_in_query) {
                                    while ($rows = $ress->fetch_assoc()) {
                                        // City check is now part of the SQL query if $user_city is set.
                                        // If $user_city was not set, we display all matching restaurants.
                                        // We still need is_open for display logic.
                                        $is_operationally_open = isset($rows['is_open']) && $rows['is_open'] == 1;
                                        $card_class = $is_operationally_open ? '' : ' unavailable';

                                        echo '<div class="restaurant-card' . $card_class . '">';
                                            echo '<div class="rest-logo">';
                                                if ($is_operationally_open) { echo '<a href="dishes.php?res_id=' . $rows['rs_id'] . '">'; }

                                                $image_file = htmlspecialchars($rows['image']);
                                                $image_path = 'admin/Res_img/' . $image_file;
                                                // Using relative path from webroot if `images` is in project root
                                                $default_image_path = 'images/default_res.png'; // Adjust if your structure is different

                                                echo '<img src="' . $image_path . '"
                                                         alt="' . htmlspecialchars($rows['title']) . '"
                                                         onerror="this.onerror=null; this.src=\'' . $default_image_path . '\';">';

                                                if ($is_operationally_open) { echo '</a>'; }
                                            echo '</div>'; // end rest-logo
                                            echo '<div class="rest-info">';
                                                echo '<div class="rest-descr">';
                                                     if ($is_operationally_open) { echo '<h5><a href="dishes.php?res_id=' . $rows['rs_id'] . '">' . htmlspecialchars($rows['title']) . '</a></h5>'; }
                                                     else { echo '<h5>' . htmlspecialchars($rows['title']) . '</h5>'; }
                                                     echo '<span>' . htmlspecialchars($rows['address']) . '</span>';
                                                     if (!empty($rows['city'])) { echo '<br><small>City: ' . htmlspecialchars($rows['city']) . '</small>'; }


                                                     if ($user_is_logged_in && $user_veg_mode_enabled && isset($rows['diet_type']) && $rows['diet_type'] == 'veg') {
                                                        echo '<br><small style="color: green; font-weight: bold;">Type: Veg</small>';
                                                     } elseif (!empty($rows['diet_type'])) {
                                                        echo '<br><small>Type: ' . ucfirst(htmlspecialchars($rows['diet_type'])) . '</small>';
                                                     }
                                                echo '</div>'; // end rest-descr
                                            echo '</div>'; // end rest-info
                                            if ($is_operationally_open) {
                                                echo '<a href="dishes.php?res_id=' . $rows['rs_id'] . '" class="btn btn-view-menu"><i class="fas fa-concierge-bell"></i> View Menu</a>';
                                            } else {
                                                echo '<span class="service-unavailable">Currently Closed</span>';
                                            }
                                        echo '</div>'; // end restaurant-card
                                    }
                                } else { // No restaurants matched the combined SQL query
                                   $message = 'No restaurants found';
                                   if (isset($_GET['search']) && !empty($_GET['search'])) { $message .= ' matching "' . htmlspecialchars($_GET['search']) . '"'; }
                                   if ($user_is_logged_in && $user_veg_mode_enabled) { $message .= ' (Strictly Veg Only)'; }
                                   if ($user_city) { $message .= ' in ' . htmlspecialchars($user_city); }
                                   $message .= '.';
                                   echo '<div class="col-12 text-center mt-3"><p>' . $message . '</p></div>';
                                }
                            } else {
                                echo '<div class="col-12 text-center"><p class="text-danger">Could not retrieve restaurant data.</p></div>';
                                error_log("Error executing main restaurant query: ".$stmt_main->error);
                            }
                            $stmt_main->close();
                        } else {
                             echo '<div class="col-12 text-center"><p class="text-danger">Error preparing restaurant query: ' . $db->error . '</p></div>';
                             error_log("Prepare statement failed for main restaurant query: " . $db->error);
                        }
                        ?>
                    </div>
                </div>
            </section>
        </div> <!-- // container -->
    </div> <!-- // page-wrapper -->

    <?php include 'footer.php'; ?>
     <?php include 'chatbot.php'; ?>

    <!-- --- JS Includes --- -->
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script> <!-- Make sure this is your main custom script or has necessary general functions -->

    <!-- --- JavaScript for Veg Toggle (AJAX Update) and City --- -->
    <script>
        $(document).ready(function() {

            const isLoggedInJS = <?php echo json_encode($user_is_logged_in); ?>; // Renamed to avoid conflict
            const userIdJS = <?php echo json_encode($user_id); ?>; // Renamed to avoid conflict

            // --- Load User City Display (Function for header) ---
            function updateUserCityDisplay() {
                const sessionCity = <?php echo json_encode($_SESSION['selected_city'] ?? null); ?>;
                const userCitySpan = $('#user-city'); // Cache selector

                if (isLoggedInJS && userIdJS) {
                    $.ajax({
                        url: 'get_user_city.php', method: 'POST', data: { user_id: userIdJS }, dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success' && data.city) { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${data.city}`); }
                            else if (sessionCity) { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`); }
                            else { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`); }
                        }, error: function() {
                             if (sessionCity) { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`); }
                             else { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`); }
                        }
                    });
                } else if (sessionCity) { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> ${sessionCity}`); }
                  else { userCitySpan.html(`<i class="fas fa-map-marker-alt"></i> Select City`); }
            }
            updateUserCityDisplay(); // Call on page load

            // --- Veg Toggle Functionality (AJAX Update -> Page Reload) ---
            $('#veg-toggle').on('change', function(e) {
                if (!isLoggedInJS) {
                    e.preventDefault();
                    $(this).prop('checked', !$(this).prop('checked'));
                    Swal.fire('Login Required', 'Please log in to change Veg Only mode.', 'warning');
                    return false;
                }

                const isChecked = $(this).is(':checked');
                const newState = isChecked ? 1 : 0;
                const $toggleInput = $(this);
                const $toggleGroup = $toggleInput.closest('.veg-toggle-group');

                $toggleInput.prop('disabled', true);
                $toggleGroup.addClass('disabled');

                $.ajax({
                    url: 'update_veg_mode.php',
                    method: 'POST',
                    data: { user_id: userIdJS, new_state: newState },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success', title: response.message, toast: true,
                                position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
                            }).then(() => {
                                window.location.reload(); // Reload to apply filter server-side
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Could not update preference.', 'error');
                            $toggleInput.prop('checked', !isChecked);
                            if (isLoggedInJS) { // Only re-enable if still logged in
                                $toggleInput.prop('disabled', false);
                                $toggleGroup.removeClass('disabled');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Veg Mode Update AJAX Error:", status, error, xhr.responseText);
                        Swal.fire('Error', 'An error occurred while saving your preference.', 'error');
                        $toggleInput.prop('checked', !isChecked);
                        if (isLoggedInJS) { // Only re-enable if still logged in
                           $toggleInput.prop('disabled', false);
                           $toggleGroup.removeClass('disabled');
                        }
                    }
                });
            });

            // --- Update Cart Count (if cart dot is in the header) ---
             function updateCartCount() {
                 if (!isLoggedInJS) {
                     $('#cart-notification-dot').hide(); // Ensure dot is hidden if it exists and user not logged in
                     return;
                 }
                 $.ajax({
                     url: 'get_cart_count.php', // Assuming you have this endpoint
                     method: 'GET',
                     dataType: 'json',
                     success: function(response) {
                         const cartDot = $('#cart-notification-dot');
                         if (cartDot.length) { // Check if the element exists
                             if (response && typeof response.count !== 'undefined' && parseInt(response.count) > 0) {
                                 cartDot.show();
                             } else {
                                 cartDot.hide();
                             }
                         }
                     },
                     error: function(xhr, status, error) {
                         console.error("Error fetching cart count for header:", status, error);
                          $('#cart-notification-dot').hide(); // Hide on error
                     }
                 });
             }
             if (isLoggedInJS) { // Only call if logged in and cart dot might be visible
                 updateCartCount();
             }

        });
    </script>
</body>
</html>