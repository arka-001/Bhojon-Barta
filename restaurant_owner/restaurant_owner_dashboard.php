<?php
session_start();
include("../connection/connect.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'restaurant_owner' || !isset($_SESSION['owner_id'])) {
    header("Location: restaurant_owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];

// Base paths for images
$basePath = $_SERVER['DOCUMENT_ROOT'] . "/OnlineFood-PHP/OnlineFood-PHP/"; // Adjust if your project is in a subfolder
$dishImageDir = $basePath . "admin/Res_img/dishes/";
$requestImageDir = $basePath . "admin/Res_img/";
$dishWebPath = "/OnlineFood-PHP/OnlineFood-PHP/admin/Res_img/dishes/"; // Adjust if your project is in a subfolder
$requestWebPath = "/OnlineFood-PHP/OnlineFood-PHP/admin/Res_img/"; // Adjust if your project is in a subfolder


// Fetch owner's approved restaurants
$stmt_res = $db->prepare("SELECT rs_id, title, is_open FROM restaurant WHERE owner_id = ?");
if (!$stmt_res) die("Database error (Restaurants Prepare): " . $db->error);
$stmt_res->bind_param("i", $owner_id);
$stmt_res->execute();
$result_res = $stmt_res->get_result();
$restaurants = $result_res->fetch_all(MYSQLI_ASSOC);
$stmt_res->close();

// Fetch owner's pending restaurant requests
$stmt_req = $db->prepare("SELECT rr.*, rc.c_name FROM restaurant_requests rr LEFT JOIN res_category rc ON rr.c_id = rc.c_id WHERE rr.owner_id = ? ORDER BY rr.request_date DESC");
if (!$stmt_req) die("Database error (Requests Prepare): " . $db->error);
$stmt_req->bind_param("i", $owner_id);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
$requests = $result_req->fetch_all(MYSQLI_ASSOC);
$stmt_req->close();

// Determine the currently selected restaurant
$valid_rs_ids = array_column($restaurants, 'rs_id');
$current_restaurant_id = (isset($_GET['rs_id']) && in_array($_GET['rs_id'], $valid_rs_ids)) ? intval($_GET['rs_id']) : (!empty($restaurants) ? $restaurants[0]['rs_id'] : null);

// Fetch details of the currently selected restaurant
$current_restaurant_details = null;
if ($current_restaurant_id) {
    foreach ($restaurants as $res) {
        if ($res['rs_id'] == $current_restaurant_id) {
            $current_restaurant_details = $res;
            break;
        }
    }
}

// Fetch dishes for the selected restaurant
$dishes = [];
if ($current_restaurant_id) {
    // Added offer fields to select
    $stmt_dish = $db->prepare("SELECT d_id, title, slogan, price, offer_price, offer_start_date, offer_end_date, img, is_available, diet_type FROM dishes WHERE rs_id = ? ORDER BY title ASC");
    if (!$stmt_dish) die("Database error (Dishes Prepare): " . $db->error);
    $stmt_dish->bind_param("i", $current_restaurant_id);
    $stmt_dish->execute();
    $result_dish = $stmt_dish->get_result();
    $dishes = $result_dish->fetch_all(MYSQLI_ASSOC);
    $stmt_dish->close();
}

// Fetch recent user orders for the selected restaurant
$orders = [];
$error_message = '';
if ($current_restaurant_id) {
    $query_orders = "
        SELECT uo.o_id, uo.order_id, uo.u_id, uo.title, uo.quantity, uo.price, uo.status, uo.date, u.f_name, u.l_name
        FROM users_orders uo
        JOIN users u ON uo.u_id = u.u_id
        WHERE uo.rs_id = ?
        ORDER BY uo.o_id DESC
        LIMIT 5
    ";
    $stmt_order = $db->prepare($query_orders);
    if (!$stmt_order) {
        $error_message = "Prepare failed (Orders): " . $db->error;
    } else {
        $stmt_order->bind_param("i", $current_restaurant_id);
        if (!$stmt_order->execute()) {
            $error_message = "Execute failed (Orders): " . $stmt_order->error;
        } else {
            $result_order = $stmt_order->get_result();
            $orders = $result_order->fetch_all(MYSQLI_ASSOC);
        }
        $stmt_order->close();
    }
}
if ($error_message) {
    error_log("Owner Dashboard Order Fetch Error: " . $error_message);
}

// Handle form submissions for ADD/EDIT/DELETE Dishes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->begin_transaction();

        // ADD DISH
        if (isset($_POST['add_dish'])) {
            $add_rs_id = intval($_POST['rs_id']);
            if (in_array($add_rs_id, $valid_rs_ids)) {
                $img_name = 'default.jpg';
                if (!empty($_FILES['img']['name'])) {
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    $file_ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, $allowed_types) && $_FILES['img']['size'] > 0 && $_FILES['img']['size'] < 2000000) { // 2MB
                        $img_name = uniqid('dish_', true) . '.' . $file_ext;
                        $target_path = $dishImageDir . $img_name;
                        if (!is_dir($dishImageDir)) {
                            if (!mkdir($dishImageDir, 0755, true)) {
                                throw new Exception("Failed to create directory: $dishImageDir");
                            }
                        }
                        if (!is_writable($dishImageDir)) {
                            throw new Exception("Directory is not writable: $dishImageDir");
                        }
                        if (!move_uploaded_file($_FILES['img']['tmp_name'], $target_path)) {
                            throw new Exception("Failed to upload image to: $target_path. Error: " . $_FILES['img']['error']);
                        }
                    } else {
                        throw new Exception("Invalid file type or size. Allowed types: jpg, jpeg, png, gif. Max size: 2MB.");
                    }
                }

                $diet_type = $_POST['diet_type'];
                if (!in_array($diet_type, ['veg', 'nonveg', 'vegan'])) {
                    throw new Exception("Invalid dietary type selected.");
                }

                // Handle offer fields
                $offer_price = !empty($_POST['offer_price']) ? $_POST['offer_price'] : NULL;
                $offer_start_date = !empty($_POST['offer_start_date']) ? date('Y-m-d H:i:s', strtotime($_POST['offer_start_date'])) : NULL;
                $offer_end_date = !empty($_POST['offer_end_date']) ? date('Y-m-d H:i:s', strtotime($_POST['offer_end_date'])) : NULL;

                if ($offer_price !== NULL && $offer_price >= $_POST['price']) {
                    throw new Exception("Offer price must be less than the regular price.");
                }
                if ($offer_start_date && $offer_end_date && $offer_start_date >= $offer_end_date) {
                    throw new Exception("Offer end date must be after offer start date.");
                }


                $stmt_add = $db->prepare("INSERT INTO dishes (rs_id, title, slogan, price, offer_price, offer_start_date, offer_end_date, img, is_available, diet_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
                if (!$stmt_add) throw new Exception("DB Prepare Error (Add Dish): " . $db->error);
                $stmt_add->bind_param("issddssis", $add_rs_id, $_POST['title'], $_POST['description'], $_POST['price'], $offer_price, $offer_start_date, $offer_end_date, $img_name, $diet_type);
                
                if (!$stmt_add->execute()) {
                    throw new Exception("DB Execute Error (Add Dish): " . $stmt_add->error);
                }
                $stmt_add->close();
                $db->commit();
                header("Location: " . $_SERVER['PHP_SELF'] . "?rs_id=" . $add_rs_id . "&status=added");
                exit();
            } else {
                throw new Exception("Permission denied for adding dish to this restaurant.");
            }
        }

        // EDIT DISH
        if (isset($_POST['edit_dish']) && $current_restaurant_id) {
            $d_id_edit = intval($_POST['d_id']);
            $check_stmt = $db->prepare("SELECT d.d_id FROM dishes d WHERE d.d_id = ? AND d.rs_id = ?");
            if (!$check_stmt) throw new Exception("DB Prepare Error (Check Edit): " . $db->error);
            $check_stmt->bind_param("ii", $d_id_edit, $current_restaurant_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_stmt->close();

            if ($check_result->num_rows > 0) {
                $diet_type = $_POST['diet_type'];
                if (!in_array($diet_type, ['veg', 'nonveg', 'vegan'])) {
                    throw new Exception("Invalid dietary type selected.");
                }

                // Handle offer fields for edit
                $offer_price_edit = !empty($_POST['offer_price']) ? $_POST['offer_price'] : NULL;
                $offer_start_date_edit = !empty($_POST['offer_start_date']) ? date('Y-m-d H:i:s', strtotime($_POST['offer_start_date'])) : NULL;
                $offer_end_date_edit = !empty($_POST['offer_end_date']) ? date('Y-m-d H:i:s', strtotime($_POST['offer_end_date'])) : NULL;

                if ($offer_price_edit !== NULL && $offer_price_edit >= $_POST['price']) {
                    throw new Exception("Offer price must be less than the regular price.");
                }
                 if ($offer_start_date_edit && $offer_end_date_edit && $offer_start_date_edit >= $offer_end_date_edit) {
                    throw new Exception("Offer end date must be after offer start date.");
                }

                $stmt_edit = $db->prepare("UPDATE dishes SET title = ?, slogan = ?, price = ?, offer_price = ?, offer_start_date = ?, offer_end_date = ?, diet_type = ? WHERE d_id = ?");
                if (!$stmt_edit) throw new Exception("DB Prepare Error (Edit Dish): " . $db->error);
                $stmt_edit->bind_param("ssdssssi", $_POST['title'], $_POST['description'], $_POST['price'], $offer_price_edit, $offer_start_date_edit, $offer_end_date_edit, $diet_type, $d_id_edit);
                
                if (!$stmt_edit->execute()) {
                    throw new Exception("DB Execute Error (Edit Dish): " . $stmt_edit->error);
                }
                $stmt_edit->close();

                if (!empty($_FILES['img']['name'])) {
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    $file_ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, $allowed_types) && $_FILES['img']['size'] > 0 && $_FILES['img']['size'] < 2000000) {
                        $old_img_stmt = $db->prepare("SELECT img FROM dishes WHERE d_id = ?");
                        if ($old_img_stmt) {
                            $old_img_stmt->bind_param("i", $d_id_edit);
                            $old_img_stmt->execute();
                            $old_img_res = $old_img_stmt->get_result();
                            if ($old_img_row = $old_img_res->fetch_assoc()) {
                                $old_img_path = $dishImageDir . $old_img_row['img'];
                                if (file_exists($old_img_path) && $old_img_row['img'] != 'default.jpg') {
                                    unlink($old_img_path);
                                }
                            }
                            $old_img_stmt->close();
                        }

                        $img_name_edit = uniqid('dish_', true) . '.' . $file_ext;
                        $target_path_edit = $dishImageDir . $img_name_edit;
                        if (!move_uploaded_file($_FILES['img']['tmp_name'], $target_path_edit)) {
                             throw new Exception("Failed to upload updated image. Error: " . $_FILES['img']['error']);
                        }
                        $stmt_img_update = $db->prepare("UPDATE dishes SET img = ? WHERE d_id = ?");
                        if (!$stmt_img_update) throw new Exception("DB Prepare Error (Update Img): " . $db->error);
                        $stmt_img_update->bind_param("si", $img_name_edit, $d_id_edit);
                        if (!$stmt_img_update->execute()) {
                            throw new Exception("DB Execute Error (Update Img): " . $stmt_img_update->error);
                        }
                        $stmt_img_update->close();
                    } else {
                        throw new Exception("Invalid file type or size for updated image.");
                    }
                }
                $db->commit();
                header("Location: " . $_SERVER['PHP_SELF'] . "?rs_id=" . $current_restaurant_id . "&status=edited");
                exit();
            } else {
                throw new Exception("Permission denied or dish not found for editing.");
            }
        }

        // DELETE DISH (No changes needed here for offers, as offers are part of the dish row)
        if (isset($_POST['delete_dish']) && $current_restaurant_id) {
            $d_id_delete = intval($_POST['d_id']);
            $check_stmt = $db->prepare("SELECT d.d_id, d.img FROM dishes d WHERE d.d_id = ? AND d.rs_id = ?");
            if (!$check_stmt) throw new Exception("DB Prepare Error (Check Delete): " . $db->error);
            $check_stmt->bind_param("ii", $d_id_delete, $current_restaurant_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($dish_to_delete = $check_result->fetch_assoc()) {
                $img_path_delete = $dishImageDir . $dish_to_delete['img'];
                if (file_exists($img_path_delete) && $dish_to_delete['img'] != 'default.jpg') {
                    unlink($img_path_delete);
                }
                $check_stmt->close();

                $stmt_delete = $db->prepare("DELETE FROM dishes WHERE d_id = ?");
                if (!$stmt_delete) throw new Exception("DB Prepare Error (Delete Dish): " . $db->error);
                $stmt_delete->bind_param("i", $d_id_delete);
                if (!$stmt_delete->execute()) {
                    throw new Exception("DB Execute Error (Delete Dish): " . $stmt_delete->error);
                }
                $stmt_delete->close();
                $db->commit();
                header("Location: " . $_SERVER['PHP_SELF'] . "?rs_id=" . $current_restaurant_id . "&status=deleted");
                exit();
            } else {
                $check_stmt->close();
                throw new Exception("Permission denied or dish not found for deletion.");
            }
        }
        $db->rollback(); // Should not be reached if commit happens

    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Owner Dashboard Form Submit Error: " . $e->getMessage());
        // Determine which rs_id to redirect to, prioritize POSTed one or current one
        $redirect_rs_id = $current_restaurant_id;
        if(isset($_POST['rs_id'])) $redirect_rs_id = intval($_POST['rs_id']);
        elseif(isset($_POST['d_id'])) { // Try to get rs_id from dish being edited/deleted
            $temp_d_id = intval($_POST['d_id']);
            $temp_stmt = $db->prepare("SELECT rs_id FROM dishes WHERE d_id = ?");
            if($temp_stmt) {
                $temp_stmt->bind_param("i", $temp_d_id);
                $temp_stmt->execute();
                $temp_res = $temp_stmt->get_result();
                if($temp_row = $temp_res->fetch_assoc()) {
                    $redirect_rs_id = $temp_row['rs_id'];
                }
                $temp_stmt->close();
            }
        }
        $redirect_url = $_SERVER['PHP_SELF'];
        if ($redirect_rs_id) {
            $redirect_url .= "?rs_id=" . $redirect_rs_id;
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . "form_error=1";

        header("Location: " . $redirect_url);
        exit();
    }
}

// Check for messages from redirects
$success_message = '';
$error_message_display = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') $success_message = 'Dish added successfully!';
    if ($_GET['status'] == 'edited') $success_message = 'Dish updated successfully!';
    if ($_GET['status'] == 'deleted') $success_message = 'Dish deleted successfully!';
}
if (isset($_SESSION['error_message'])) {
    $error_message_display = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
} elseif (isset($_GET['form_error'])) {
    $error_message_display = "An error occurred processing your request. Please check your input and try again.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurant Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background: linear-gradient(135deg, #f8f9fa, #e9ecef); font-family: 'Poppins', sans-serif; color: #1e3c72; overflow-x: hidden; }
        .container-fluid { padding: 20px 15px; max-width: 1800px; margin: 0 auto; } /* Increased max-width */
        .sticky-header { position: sticky; top: 0; z-index: 1020; background: linear-gradient(135deg, #2a5298, #1e3c72); padding: 15px 0; border-radius: 0 0 15px 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); color: #fff; animation: slideIn 0.5s ease; }
        @keyframes slideIn { from { transform: translateY(-100%); } to { transform: translateY(0); } }
        .sticky-header h3 { font-weight: 700; font-size: 1.8rem; margin-bottom: 5px; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .welcome-text { font-size: 1rem; opacity: 0.9; margin-bottom: 0; }
        .card { border: none; border-radius: 15px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); background: #fff; transition: transform 0.3s ease, box-shadow 0.3s ease; overflow: hidden; margin-bottom: 25px; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); }
        .card-header { padding: 20px 25px; background: linear-gradient(135deg, #2a5298, #1e3c72); color: #fff; font-size: 1.4rem; font-weight: 600; border-radius: 15px 15px 0 0; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2); }
        .card-header.requests { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .card-header.orders { background: linear-gradient(135deg, #28a745, #218838); }
        .card-body { padding: 25px 30px; background: #fff; }
        .nav-tabs { border-bottom: none; gap: 10px; flex-wrap: wrap; padding: 10px 0; margin-bottom: 15px; }
        .nav-tabs .nav-link { border: none; padding: 10px 20px; color: #2a5298; font-weight: 600; border-radius: 50px; background: #e9ecef; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .nav-tabs .nav-link.active { background: linear-gradient(135deg, #2a5298, #1e3c72); color: #fff; box-shadow: 0 4px 10px rgba(42, 82, 152, 0.2); }
        .nav-tabs .nav-link:hover { background: #dee2e6; transform: translateY(-2px); }
        .btn { transition: all 0.3s ease; }
        .btn-primary { background: linear-gradient(135deg, #2a5298, #1e3c72); border: none; padding: 10px 25px; border-radius: 50px; font-weight: 600; box-shadow: 0 4px 10px rgba(42, 82, 152, 0.2); color: #fff !important; }
        .btn-primary:hover { background: linear-gradient(135deg, #1e3c72, #2a5298); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(42, 82, 152, 0.3); }
        .btn-danger { background: linear-gradient(135deg, #dc3545, #c82333); border: none; padding: 6px 15px; border-radius: 50px; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); color: #fff !important; }
        .btn-danger:hover { background: linear-gradient(135deg, #c82333, #dc3545); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(220, 53, 69, 0.3); }
        .form-label { font-weight: 500; color: #495057; margin-bottom: 0.3rem; font-size: 0.9em; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #ced4da; padding: 10px 12px; font-size: 0.95rem; transition: all 0.3s ease; background: #f8f9fa; box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05); }
        .form-control:focus, .form-select:focus { border-color: #8ab4f8; box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25), inset 0 1px 3px rgba(0, 0, 0, 0.05); background: #fff; }
        .table-responsive { margin-top: 1rem; }
        .table { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border: 1px solid #dee2e6; font-size: 0.85rem; } /* Smaller font for table */
        thead th { background: #f1f3f5; color: #495057; font-weight: 600; padding: 12px 10px; border: none; font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; } /* Adjusted padding */
        .table td { padding: 10px; vertical-align: middle; border-top: 1px solid #e9ecef; transition: background 0.2s ease; } /* Adjusted padding */
        .table tr:hover td { background: #f8f9fa; }
        .status-badge { padding: 5px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background-color: #ffc107; color: #343a40; }
        .status-approved, .status-closed, .status-delivered { background-color: #28a745; color: #fff; }
        .status-rejected, .status-cancelled { background-color: #dc3545; color: #fff; }
        .status-assigned, .status-accepted, .status-preparing, .status-ready_for_pickup, .status-in_process { background-color: #17a2b8; color: #fff; }
        .img-thumbnail { border-radius: 8px; max-width: 60px; height: auto; box-shadow: 0 1px 4px rgba(0,0,0,0.1); } /* Smaller image */
        .price-with-rupee::before { content: "₹"; margin-right: 2px; }
        .form-actions button { margin-top: 3px; padding: 4px 10px; font-size: 0.8rem; } /* Smaller buttons */
        .form-actions .input-group-sm .form-control, .form-actions .input-group-sm .form-select { font-size: 0.8rem; padding: 5px 8px; } /* Smaller inputs in actions */
        .form-actions .input-group-sm .input-group-text { font-size: 0.8rem; padding: 5px 8px; }
        .status-toggle-container { display: flex; align-items: center; gap: 6px; justify-content: start; }
        .toggle-switch { position: relative; display: inline-block; width: 38px; height: 20px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ced4da; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(18px); }
        .status-text { font-weight: 500; font-size: 0.8em; line-height: 1; }
        .status-available, .status-open { color: #28a745; }
        .status-unavailable, .status-closed { color: #dc3545; }
        #add-dish-form .row > div { margin-bottom: 0.8rem; }
        .offer-details { font-size: 0.8em; color: #6c757d; }
        .offer-details strong { color: #e67e22; }
    </style>
</head>
<body>
    <div class="sticky-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h3>Owner Dashboard</h3>
                    <p class="welcome-text mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['owner_email'] ?? $_SESSION['email'] ?? 'Owner'); ?></p>
                </div>
                <div class="col text-end">
                    <a href="add_restaurant.php" class="btn btn-light btn-sm me-2"><i class="fas fa-plus-circle me-1"></i> Request New Restaurant</a>
                    <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message_display)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message_display; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">My Restaurants</div>
            <div class="card-body">
                <?php if (empty($restaurants)): ?>
                    <p class="text-muted text-center">No approved restaurants yet. Submit a request below or contact admin.</p>
                <?php else: ?>
                    <ul class="nav nav-tabs" role="tablist">
                        <?php foreach ($restaurants as $restaurant): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $restaurant['rs_id'] == $current_restaurant_id ? 'active' : ''; ?>" href="?rs_id=<?php echo $restaurant['rs_id']; ?>">
                                    <?php echo htmlspecialchars($restaurant['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($current_restaurant_id && $current_restaurant_details): ?>
                        <div class="restaurant-status-header mt-3">
                            <h5>
                                <?php echo htmlspecialchars($current_restaurant_details['title']); ?> Status:
                                <span class="status-text status-<?php echo $current_restaurant_details['is_open'] ? 'open' : 'closed'; ?>" id="restaurant-status-text-<?php echo $current_restaurant_id; ?>">
                                    <?php echo $current_restaurant_details['is_open'] ? 'Open (Accepting Orders)' : 'Closed (Not Accepting Orders)'; ?>
                                </span>
                            </h5>
                            <div class="status-toggle-container">
                                <label class="toggle-switch">
                                    <input type="checkbox" class="restaurant-status-toggle"
                                           data-id="<?php echo $current_restaurant_id; ?>"
                                           <?php echo $current_restaurant_details['is_open'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="status-text">Toggle Status</span>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Dishes for <?php echo htmlspecialchars($current_restaurant_details['title']); ?></h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">Dish</th>
                                        <th style="width: 15%;">Desc</th>
                                        <th style="width: 8%;">Price</th>
                                        <th style="width: 15%;">Offer</th> <!-- New Offer Column -->
                                        <th style="width: 8%;">Image</th>
                                        <th style="width: 8%;">Diet</th>
                                        <th style="width: 10%;">Available</th>
                                        <th style="width: 26%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dishes)): ?>
                                        <tr><td colspan="8" class="text-center text-muted">No dishes found. Add one below!</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($dishes as $dish): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dish['title']); ?></td>
                                                <td><?php echo htmlspecialchars($dish['slogan']); ?></td>
                                                <td><span class="price-with-rupee"><?php echo number_format($dish['price'], 2); ?></span></td>
                                                <td class="offer-details"> <!-- Offer details display -->
                                                    <?php if (!empty($dish['offer_price']) && $dish['offer_price'] < $dish['price']): ?>
                                                        <strong>Offer: ₹<?php echo number_format($dish['offer_price'], 2); ?></strong><br>
                                                        <?php if (!empty($dish['offer_start_date'])): ?>
                                                            Start: <?php echo date('d M Y H:i', strtotime($dish['offer_start_date'])); ?><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($dish['offer_end_date'])): ?>
                                                            End: <?php echo date('d M Y H:i', strtotime($dish['offer_end_date'])); ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No active offer</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $dish_img_path = $dishImageDir . htmlspecialchars($dish['img']);
                                                    $dish_web_path = $dishWebPath . htmlspecialchars($dish['img']);
                                                    if (file_exists($dish_img_path) && is_file($dish_img_path) && $dish['img'] != 'default.jpg') {
                                                        echo '<img src="' . $dish_web_path . '?t=' . time() . '" class="img-thumbnail" alt="' . htmlspecialchars($dish['title']) . '">';
                                                    } else {
                                                        echo '<span class="text-muted small">No image</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo ucfirst(htmlspecialchars($dish['diet_type'])); ?></td>
                                                <td>
                                                    <div class="status-toggle-container">
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" class="dish-availability-toggle"
                                                                   data-id="<?php echo $dish['d_id']; ?>"
                                                                   <?php echo $dish['is_available'] ? 'checked' : ''; ?>>
                                                            <span class="slider"></span>
                                                        </label>
                                                        <span class="status-text status-<?php echo $dish['is_available'] ? 'available' : 'unavailable'; ?>" id="dish-status-text-<?php echo $dish['d_id']; ?>">
                                                            <?php echo $dish['is_available'] ? 'Available' : 'Unavailable'; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <form method="post" enctype="multipart/form-data" class="form-actions">
                                                        <input type="hidden" name="d_id" value="<?php echo $dish['d_id']; ?>">
                                                        <div class="row gx-1 gy-1">
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                                                    <input type="text" name="title" value="<?php echo htmlspecialchars($dish['title']); ?>" class="form-control" placeholder="Name" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                    <input type="text" name="description" value="<?php echo htmlspecialchars($dish['slogan']); ?>" class="form-control" placeholder="Desc" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text">₹</span>
                                                                    <input type="number" name="price" value="<?php echo htmlspecialchars($dish['price']); ?>" class="form-control" step="0.01" min="0" placeholder="Price" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-leaf"></i></span>
                                                                    <select name="diet_type" class="form-select" required>
                                                                        <option value="veg" <?php echo $dish['diet_type'] == 'veg' ? 'selected' : ''; ?>>Veg</option>
                                                                        <option value="nonveg" <?php echo $dish['diet_type'] == 'nonveg' ? 'selected' : ''; ?>>Non-Veg</option>
                                                                        <option value="vegan" <?php echo $dish['diet_type'] == 'vegan' ? 'selected' : ''; ?>>Vegan</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-12">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-image"></i></span>
                                                                    <input type="file" name="img" class="form-control">
                                                                </div>
                                                            </div>
                                                            <!-- Offer Fields for Edit -->
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text">Offer ₹</span>
                                                                    <input type="number" name="offer_price" value="<?php echo htmlspecialchars($dish['offer_price'] ?? ''); ?>" class="form-control" step="0.01" min="0" placeholder="Offer Price">
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i> Start</span>
                                                                    <input type="datetime-local" name="offer_start_date" value="<?php echo !empty($dish['offer_start_date']) ? date('Y-m-d\TH:i', strtotime($dish['offer_start_date'])) : ''; ?>" class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                 <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i> End</span>
                                                                    <input type="datetime-local" name="offer_end_date" value="<?php echo !empty($dish['offer_end_date']) ? date('Y-m-d\TH:i', strtotime($dish['offer_end_date'])) : ''; ?>" class="form-control">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-1">
                                                            <button type="submit" name="edit_dish" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Update</button>
                                                            <button type="submit" name="delete_dish" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt me-1"></i>Delete</button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mt-5 mb-3 border-top pt-4">Add New Dish to <?php echo htmlspecialchars($current_restaurant_details['title']); ?></h5>
                        <form method="post" enctype="multipart/form-data" class="row g-3" id="add-dish-form">
                            <div class="col-md-6 col-lg-4">
                                <label for="add-title" class="form-label">Dish Name</label>
                                <input type="text" name="title" id="add-title" class="form-control" required>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="add-desc" class="form-label">Description</label>
                                <input type="text" name="description" id="add-desc" class="form-control" required>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label for="add-price" class="form-label">Price (₹)</label>
                                <input type="number" name="price" id="add-price" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label for="add-diet-type" class="form-label">Diet Type</label>
                                <select name="diet_type" id="add-diet-type" class="form-select" required>
                                    <option value="veg">Vegetarian</option>
                                    <option value="nonveg">Non-Vegetarian</option>
                                    <option value="vegan">Vegan</option>
                                </select>
                            </div>
                             <div class="col-md-4 col-lg-3">
                                <label for="add-img" class="form-label">Image</label>
                                <input type="file" name="img" id="add-img" class="form-control">
                            </div>
                            <!-- Offer Fields for Add New Dish -->
                            <div class="col-md-4 col-lg-3">
                                <label for="add-offer-price" class="form-label">Offer Price (₹) <small>(Optional)</small></label>
                                <input type="number" name="offer_price" id="add-offer-price" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="add-offer-start" class="form-label">Offer Start Date <small>(Optional)</small></label>
                                <input type="datetime-local" name="offer_start_date" id="add-offer-start" class="form-control">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="add-offer-end" class="form-label">Offer End Date <small>(Optional)</small></label>
                                <input type="datetime-local" name="offer_end_date" id="add-offer-end" class="form-control">
                            </div>
                           
                            <input type="hidden" name="rs_id" value="<?php echo $current_restaurant_id; ?>">
                            <div class="col-12 text-end">
                                <button type="submit" name="add_dish" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add Dish</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-center text-muted">Select a restaurant from the tabs above to view its details and dishes.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Restaurant Requests and Orders sections remain the same, truncated for brevity -->
        <div class="card mt-4">
            <div class="card-header requests">My Restaurant Requests</div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <p class="text-muted text-center">No Pending Restaurant Requests.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Image</th><th>Date</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['c_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($request['title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                                        <td><?php echo htmlspecialchars($request['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($request['address']); ?></td>
                                        <td>
                                            <?php
                                            $request_img_path = $requestImageDir . htmlspecialchars($request['image']);
                                            $request_web_path = $requestWebPath . htmlspecialchars($request['image']);
                                            if (file_exists($request_img_path) && is_file($request_img_path)) {
                                                echo '<img src="' . $request_web_path . '?t=' . time() . '" class="img-thumbnail" alt="Request Image">';
                                            } else {
                                                echo '<span class="text-muted small">No image</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y H:i', strtotime($request['request_date'])); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header orders">
                Recent Orders for <?php echo $current_restaurant_details ? htmlspecialchars($current_restaurant_details['title']) : 'Selected Restaurant'; ?>
            </div>
            <div class="card-body">
                <?php if (!$current_restaurant_id): ?>
                    <p class="text-muted text-center">Select a restaurant to view recent orders.</p>
                <?php elseif (empty($orders)): ?>
                    <p class="text-muted text-center">No Recent Orders Found for this Restaurant.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                           <thead><tr><th>Order ID</th><th>Customer</th><th>Dish</th><th>Qty</th><th>Price</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['order_id'] ?? $order['o_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['f_name'] . ' ' . $order['l_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['title']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td><span class="price-with-rupee"><?php echo number_format($order['price'], 2); ?></span></td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['status'] ?? 'pending'); ?>"><?php echo htmlspecialchars($order['status'] ?? 'Pending'); ?></span></td>
                                        <td><?php echo date('d M Y H:i', strtotime($order['date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="all_orders.php?rs_id=<?php echo $current_restaurant_id; ?>" class="btn btn-info btn-sm">View All Orders</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        function updateStatus(action, id, newStatus, $toggleElement, $statusTextElement) {
            var originalCheckedState = $toggleElement.prop('checked');
            $toggleElement.prop('disabled', true);

            $.ajax({
                url: 'ajax_update_status.php', // Ensure this file exists and handles these actions
                method: 'POST',
                data: { action: action, id: id, status: newStatus },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var isDish = action === 'toggle_dish_availability';
                        var statusLabel = newStatus === 1 ? (isDish ? 'Available' : 'Open (Accepting Orders)') : (isDish ? 'Unavailable' : 'Closed (Not Accepting Orders)');
                        var statusClass = newStatus === 1 ? (isDish ? 'available' : 'open') : (isDish ? 'unavailable' : 'closed');
                        var removeClass = newStatus === 1 ? (isDish ? 'unavailable' : 'closed') : (isDish ? 'available' : 'open');

                        $statusTextElement.text(statusLabel).removeClass('status-' + removeClass).addClass('status-' + statusClass);
                        Swal.fire({ icon: 'success', title: 'Updated!', text: response.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    } else {
                        $toggleElement.prop('checked', originalCheckedState);
                        Swal.fire('Error', response.message || 'Failed to update status.', 'error');
                    }
                },
                error: function(xhr) {
                    $toggleElement.prop('checked', originalCheckedState);
                    Swal.fire('Error', 'AJAX request failed: ' + xhr.statusText, 'error');
                },
                complete: function() { $toggleElement.prop('disabled', false); }
            });
        }

        $('body').on('change', '.dish-availability-toggle', function() {
            updateStatus('toggle_dish_availability', $(this).data('id'), $(this).is(':checked') ? 1 : 0, $(this), $('#dish-status-text-' + $(this).data('id')));
        });

        $('body').on('change', '.restaurant-status-toggle', function() {
            updateStatus('toggle_restaurant_status', $(this).data('id'), $(this).is(':checked') ? 1 : 0, $(this), $('#restaurant-status-text-' + $(this).data('id')));
        });

        window.setTimeout(function() {
            $(".alert-success, .alert-danger").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 5000);
    });
    </script>
</body>
</html>