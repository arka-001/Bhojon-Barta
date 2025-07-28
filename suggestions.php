<?php
session_start();
require("connection/connect.php"); // MySQLi connection ($db)

if (!isset($_SESSION['u_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['u_id']);

// Trigger recommendation generation if needed
$python_path = 'C:\\xampp\\htdocs\\OnlineFood-PHP\\OnlineFood-PHP\\reco_env\\Scripts\\python.exe';
$python_script = __DIR__ . '\\recommend.py';
$recommendations_file = __DIR__ . '\\recommendations\\recommendations.json';
$csv_file = __DIR__ . '\\recommendations\\user_orders.csv';

// Check if CSVs and recommendations are fresh
if (!file_exists($recommendations_file) || !file_exists($csv_file) || filemtime($csv_file) > filemtime($recommendations_file)) {
    $command = "\"$python_path\" \"$python_script\" 2>&1";
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        error_log("Python script error: " . implode("\n", $output));
    }
}

$recommendations = [];
if (file_exists($recommendations_file)) {
    $recommendations = json_decode(file_get_contents($recommendations_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON parse error: " . json_last_error_msg());
    }
}

$dishes = [];
if (isset($recommendations[$user_id]) && is_array($recommendations[$user_id])) {
    $rec_dish_ids = array_map('intval', $recommendations[$user_id]);
    $rec_dish_ids_str = implode(',', $rec_dish_ids);
    $sql = "SELECT d_id, title, slogan, price, img, diet_type, rs_id 
            FROM dishes 
            WHERE d_id IN ($rec_dish_ids_str) AND is_available = 1";
    $result = mysqli_query($db, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dishes[] = $row;
        }
        mysqli_free_result($result);
    } else {
        error_log("SQL error: " . mysqli_error($db));
    }
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $d_id = intval($_POST['d_id']);
    $quantity = max(1, intval($_POST['quantity']));
    $price = floatval($_POST['price']);
    $res_id = intval($_POST['res_id']);
    
    $sql = "INSERT INTO cart (u_id, d_id, res_id, quantity, price) 
            VALUES ($user_id, $d_id, $res_id, $quantity, $price)
            ON DUPLICATE KEY UPDATE quantity = quantity + $quantity";
    if (mysqli_query($db, $sql)) {
        $success_message = "Item added to cart!";
    } else {
        error_log("Cart insert error: " . mysqli_error($db));
        $error_message = "Failed to add item to cart.";
    }
}

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalized Suggestions - Bhojon Barta</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/custom-styles.css" rel="stylesheet">
    <style>
        .page-wrapper-recommendations {
            padding-top: 80px;
            padding-bottom: 40px;
            min-height: calc(100vh - 120px);
            background: #f8f9fa; /* Light grey background */
        }
        .recommendation-title-container {
            text-align: center;
            margin-bottom: 40px;
        }
        .recommendation-title-container h1 {
            font-size: 2.5rem;
            color: #333; /* Dark grey */
            font-weight: 600;
            color: #007bff; /* Blue */
        }
        .recommendation-title-container p {
            font-size: 1.1rem;
            color: #666; /* Medium grey */
        }
        .no-recommendations {
            text-align: center;
            padding: 2 50px;
            background: #fff; /* White */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .no-recommendations i {
            font-size: 3rem;
            color: #007bff; /* Blue */
            margin-bottom: 20px;
        }
        .no-recommendations p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .no-recommendations .btn-primary {
            background: #007bff; /* Blue */
            border: none0;
        }
        .no-recommendations .btn-primary:hover {
            background: #0056b3; /* Darker blue */
        }

        .dish-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill-columns, minmax(280px, 1fr));
            gap: 25px;
        }
        .single-dish {
            background: #fff;
            border: 8px;1px solid #ddd;
            border-radius: none0;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .single-dish:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .dish-logo img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .dish-content {
            padding: 15px;
            flex-grow: 1;
        }
        .dish-content h5 {
            font-size: 1.15rem;
            margin-bottom: 8px;
        }
        .dish-content h5 a {
            color: #007bff; /* Blue */
            text-decoration: none;
        }
        .dish-content h5 a:hover {
            color: #dc3545; /* Red for hover */
        }
        .dish-price {
            font-weight: bold;
            color: #28a745; /* Green */
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        .dish-description {
            font-size: 0.95rem;
            color: #555; /* Dark grey */
            margin-bottom: 12px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-heightclamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 4.5em;
        }
        .dish-actions {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px 15px 15px;
        }
        .quantity-input-group {
            display: flex;
            align-items: center;
        }
        .quantity-btn {
            background: #f0f0f0; /* Light grey */
            border: 1px solid #ccc;
            color: #333; /* Dark grey */
            cursor: pointer;
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        .quantity-input {
            width: 40px;
            text-align: center;
            border: 1px solid #ccc;
            border-left: none;
            border-right: none;
            padding: 6px 0;
            font-size: 0.9rem;
        }
        .add-to-cart-btn {
            padding: 7px 14px;
            font-size: 0.9rem;
            background: #007bff; /* Blue */
            border: none;
        }
        .add-to-cart-btn:hover {
            background: #0056b3; /* Darker blue */
        }
        .success-message, .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body class="home">
    <?php include('header.php'); ?>

    <div class="page-wrapper page-wrapper-recommendations">
        <div class="container">
            <div class="recommendation-title-container">
                <h1>Our Chef Recommends</h1>
                <p>Handpicked dishes based on your orders, favorites, and cart!</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (empty($dishes)): ?>
                <div class="no-recommendations">
                    <i class="fas fa-utensils"></i>
                    <p>We're still learning your taste!</p>
                    <p>Order, favorite, or add dishes to your cart to get personalized suggestions.</p>
                    <a href="index.php" class="btn btn-primary btn-lg">Explore Dishes</a>
                </div>
            <?php else: ?>
                <div class="dish-grid">
                    <?php foreach ($dishes as $dish): ?>
                        <div class="single-dish">
                            <div class="dish-wrap">
                                <div class="dish-logo">
                                    <a href="dishes.php?res_id=<?php echo $dish['rs_id']; ?>&d_id=<?php echo $dish['d_id']; ?>">
                                        <img src="admin/Res_img/dishes/<?php echo htmlspecialchars($dish['img']); ?>" 
                                             alt="<?php echo htmlspecialchars($dish['title']); ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="dish-content">
                                    <h5>
                                        <a href="dishes.php?res_id=<?php echo $dish['rs_id']; ?>&d_id=<?php echo $dish['d_id']; ?>">
                                            <?php echo htmlspecialchars($dish['title']); ?>
                                        </a>
                                    </h5>
                                    <div class="dish-price">$<?php echo number_format($dish['price'], 2); ?></div>
                                    <div class="dish-description"><?php echo htmlspecialchars($dish['slogan']); ?></div>
                                    <span class="diet-icon diet-<?php echo htmlspecialchars($dish['diet_type']); ?>">
                                        <?php echo ucfirst($dish['diet_type']); ?>
                                    </span>
                                </div>
                                <div class="dish-actions">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="d_id" value="<?php echo $dish['d_id']; ?>">
                                        <input type="hidden" name="price" value="<?php echo $dish['price']; ?>">
                                        <input type="hidden" name="res_id" value="<?php echo $dish['rs_id']; ?>">
                                        <div class="quantity-input-group">
                                            <button class="quantity-btn quantity-down" type="button">-</button>
                                            <input class="quantity-input" type="text" name="quantity" value="1" readonly>
                                            <button class="quantity-btn quantity-up" type="button">+</button>
                                        </div>
                                        <button type="submit" name="add_to_cart" class="btn add-to-cart-btn ms-2">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script src="js/jquery.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/bootstrap-slider.min.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/headroom.js"></script>
    <script src="js/foodpicky.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Quantity buttons
            $('.quantity-up').click(function() {
                var input = $(this).siblings('.quantity-input');
                input.val(Math.max(1, parseInt(input.val()) + 1));
            });
            $('.quantity-down').click(function() {
                var input = $(this).siblings('.quantity-input');
                var val = parseInt(input.val());
                if (val > 1) input.val(val - 1);
            });

            // Animation on scroll
            const dishes = $('.single-dish');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'slideUp 0.5s ease-out forwards';
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });
            dishes.each(function() { observer.observe(this); });

            // Auto-dismiss alerts
            setTimeout(() => {
                $('.success-message, .error-message').fadeOut();
            }, 3000);
        });
    </script>
    <style>
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .diet-icon {
            font-size: 0.9rem;
            padding: 2px 8px;
            border-radius: 10px;
            color: #fff;
            display: inline-block;
            margin-top: 5px;
        }
        .diet-veg { background: #28a745; }
        .diet-nonveg { background: #dc3545; }
        .diet-vegan { background: #007bff; }
    </style>
</body>
</html>