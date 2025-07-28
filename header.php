<?php
// header.php
// Assumes session_start() and $db connection are handled in the including file
// $user_id is assumed to be set via $_SESSION['user_id']
$currentPage = basename($_SERVER['PHP_SELF']); // Get the current page filename
?>
<style>
    /* Styles specific to the header */
    .cart-dot {
        position: absolute;
        top: 8px; /* Adjust to vertically align with "Cart" text or icon */
        left: 50px; /* Fine-tune based on "Cart" text/icon */
        width: 8px;
        height: 8px;
        background-color: red;
        border-radius: 50%;
        z-index: 10;
    }

    #user-city {
        color: #ffffff; /* White for city text */
        font-size: 16px;
        font-weight: 400;
        margin-left: 20px;
        display: inline-flex;
        align-items: center;
        transition: color 0.2s ease-out;
    }

    #user-city i {
        color: #007bff; /* Blue for location icon */
        margin-right: 5px;
    }

    #user-city:hover {
        color: #ffd700; /* Gold on hover for text */
    }

    #user-city:hover i {
        color: #007bff; /* Keep icon blue on hover */
    }

    #mainNavbarCollapse .navbar-nav .nav-item .nav-link {
        position: relative; /* For cart dot positioning */
        transition: transform 0.2s ease-out, color 0.2s ease-out;
    }

    /* Header Nav Link Hover Effect */
    #mainNavbarCollapse .navbar-nav .nav-item .nav-link:hover,
    #mainNavbarCollapse .navbar-nav .nav-item .nav-link:focus {
        transform: translateY(-2px);
        color: #ffd700 !important;
    }

    /* Style for the active navigation link */
    #mainNavbarCollapse .navbar-nav .nav-item .nav-link.active {
        color: #ffd700 !important;
    }

    /* Ensure navbar is thick */
    .navbar {
        padding: 15px 0; /* Increase padding for height */
    }

    .navbar-brand img {
        max-height: 60px; /* Larger logo for thick header */
    }

    @media (max-width: 991px) {
        #user-city {
            margin-left: 10px;
            font-size: 14px;
        }
        #user-city i {
            font-size: 14px; /* Slightly smaller icon on mobile */
        }
        .navbar-brand img {
            max-height: 50px;
        }
    }
</style>

<header id="header" class="header-scroll top-header headrom">
    <nav class="navbar navbar-dark">
        <div class="container">
            <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#mainNavbarCollapse">☰</button>
            <a class="navbar-brand" href="index.php"> <img class="img-rounded" src="images/inc.jpg" alt="Home"> </a>
            <span id="user-city"><i class="fas fa-map-marker-alt"></i> Select City</span>
            <div class="collapse navbar-toggleable-md float-lg-right" id="mainNavbarCollapse">
                <ul class="nav navbar-nav">
                    <li class="nav-item"> <a class="nav-link <?php if($currentPage == 'index.php') echo 'active'; ?>" href="index.php"><i class="fas fa-home"></i> Home</a> </li>
                    <li class="nav-item"> <a class="nav-link <?php if($currentPage == 'restaurants.php') echo 'active'; ?>" href="restaurants.php"><i class="fas fa-utensils"></i> Restaurants</a> </li>
                    <?php
                    if (empty($_SESSION["user_id"])) {
                        echo '<li class="nav-item"><a href="login.php" class="nav-link '.(($currentPage == 'login.php') ? 'active' : '').'"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                              <li class="nav-item"><a href="registration.php" class="nav-link '.(($currentPage == 'registration.php') ? 'active' : '').'"><i class="fas fa-user-plus"></i> Register</a></li>';
                    } else {
                        echo '<li class="nav-item"><a href="your_orders.php" class="nav-link '.(($currentPage == 'your_orders.php') ? 'active' : '').'"><i class="fas fa-list-alt"></i> My Orders</a></li>
                              <li class="nav-item"><a href="favorites.php" class="nav-link '.(($currentPage == 'favorites.php') ? 'active' : '').'"><i class="fas fa-heart"></i> Favorites</a></li>';
                        if (file_exists("cart.php")) {
                            echo '<li class="nav-item">
                                    <a href="cart.php" class="nav-link '.(($currentPage == 'cart.php') ? 'active' : '').'">
                                        <i class="fa fa-shopping-cart"></i> Cart
                                        <span id="cart-notification-dot" class="cart-dot" style="display:none;"></span>
                                    </a>
                                  </li>';
                        }
                        echo '<li class="nav-item"><a href="editprofile.php" class="nav-link '.(($currentPage == 'editprofile.php') ? 'active' : '').' profile-container"><i class="fas fa-user-edit profile-icon"></i> Edit Profile</a></li>
                              <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
</header>