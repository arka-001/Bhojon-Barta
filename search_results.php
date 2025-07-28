<?php
include("connection/connect.php"); // Include database connection

// Get the search term from the GET request
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Sanitize the search term to prevent SQL injection
$search_term = mysqli_real_escape_string($db, $search_term);

// Construct the SQL query
$sql = "(SELECT
            r.rs_id AS id,
            r.title AS name,
            r.address AS address,
            r.image AS image,
            'restaurant' AS type,
            '' AS dish_image,  -- Add empty fields to match the dishes query
            '' AS dish_price
        FROM restaurant r
        WHERE r.title LIKE '%$search_term%' OR r.address LIKE '%$search_term%')

        UNION  -- Combine results

        (SELECT
            d.rs_id AS id,  -- Use the restaurant ID for linking
            d.title AS name,
            res.address AS address,
            res.image AS image,
            'dish' AS type,
            d.img AS dish_image, -- Food item image
            d.price AS dish_price
        FROM dishes d
        JOIN restaurant res ON d.rs_id = res.rs_id
        WHERE d.title LIKE '%$search_term%')

        ORDER BY type ASC, name ASC"; // prioritize by type (dishes first) and then alphabetically.

// Execute the query
$ress = mysqli_query($db, $sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <!-- Include your CSS stylesheets here -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

    <div class="container">
        <h1>Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h1>

        <?php
        if (mysqli_num_rows($ress) > 0) {
            while ($rows = mysqli_fetch_array($ress)) {
                if ($rows['type'] == 'dish') {
                    // Display dish with restaurant info
                    echo '<div class="single-restaurant all">
                            <div class="restaurant-wrap">
                                <div class="restaurant-logo">
                                    <a href="dishes.php?res_id=' . $rows['id'] . '">
                                        <img src="admin/Res_img/' . $rows['dish_image'] . '" alt="Dish logo" style="border-radius:0">
                                    </a>
                                </div>
                                <div class="restaurant-content">
                                    <h5><a href="dishes.php?res_id=' . $rows['id'] . '">' . $rows['name'] . '</a></h5>
                                    <span>' . $rows['address'] . '</span>
                                    <span>Price: $' . $rows['dish_price'] . '</span>
                                </div>
                            </div>
                        </div>';
                } else {
                    // Display restaurant as before
                    echo ' <div class="single-restaurant all">
                            <div class="restaurant-wrap">
                                <div class="restaurant-logo">
                                    <a href="dishes.php?res_id=' . $rows['id'] . '">
                                        <img src="admin/Res_img/' . $rows['image'] . '" alt="Restaurant logo">
                                    </a>
                                </div>
                                <div class="restaurant-content">
                                    <h5><a href="dishes.php?res_id=' . $rows['id'] . '">' . $rows['name'] . '</a></h5>
                                    <span>' . $rows['address'] . '</span>
                                </div>
                            </div>
                        </div>';
                }
            }
        } else {
            echo "<p>No results found for \"".htmlspecialchars($search_term)."\".</p>";
        }
        ?>
    </div>

</body>
</html>