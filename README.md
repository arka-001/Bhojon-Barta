Bhojon Barta - Online Food Ordering System



Project Overview

Bhojon Barta is a comprehensive online food ordering platform developed by Arka Maitra, a student of the Department of Computer Science and Technology (CST). Built with PHP and MySQL, it streamlines the food ordering process, connecting customers, restaurants, delivery personnel, and administrators. The system supports browsing restaurants, ordering food, applying coupons, tracking deliveries, and managing accounts, providing a seamless experience for all users. It includes advanced features like geolocation, ratings, and a chatbot for enhanced user interaction.

Features





Customer Features:





Register, log in, and manage profiles (users table).



Browse restaurants (restaurant) and dishes (dishes) by category (res_category).



Add items to cart (cart), apply coupons (coupons), and place orders (users_orders).



Save favorite restaurants and dishes (user_favorite_restaurants, user_favorite_dishes).



Rate and review orders, restaurants, and delivery personnel (order_ratings, restaurant_ratings, delivery_boy_ratings).



Real-time order tracking with statuses (e.g., pending, delivered) and customer messages (order_messages).



Geolocation-based ordering with latitude/longitude support.



Restaurant Features:





Restaurant owners can submit registration requests (restaurant_owner_requests, restaurant_requests) with FSSAI licenses and bank details.



Manage menus with diet types (veg, non-veg, vegan) and offer prices (dishes).



Process orders and communicate with customers (order_messages).



Delivery Features:





Delivery personnel register with photo, license, and Aadhaar verification (delivery_boy, delivery_boy_requests).



Update availability status (available, busy, offline) and track location (latitude, longitude).



View delivery history (delivery_boy_history) and receive ratings (delivery_boy_ratings).



Admin Features:





Manage users, restaurants, and delivery personnel via the admin panel (admin).



Approve/reject restaurant and delivery requests.



Configure system settings like free delivery thresholds (settings) and footer details (footer_settings).



Monitor order statuses and remarks (remark, order_status_requests).



Additional Features:





Coupon system with fixed/percentage discounts and usage limits (coupons).



Basic chatbot for user queries (chat_messages).



Delivery charge configuration based on order value and location (delivary_charges).



Account deletion logging for transparency (account_deletion_log, deleted_users_log).

Database Structure

The MySQL database (onlinefoodphp2) includes the following key tables:





Users: Stores customer data, including location and veg mode (users, user_backups).



Restaurants: Manages restaurant details, categories, and owner information (restaurant, res_category, restaurant_owners).



Dishes: Contains menu items with prices, offers, and diet types (dishes).



Orders: Tracks orders with statuses, delivery charges, and coupon applications (users_orders).



Delivery: Manages delivery personnel, their history, and ratings (delivery_boy, delivery_boy_history, delivery_boy_ratings).



Coupons: Stores discount codes with conditions like minimum order value (coupons).



Ratings: Records ratings for orders, restaurants, and delivery personnel (order_ratings, restaurant_ratings, delivery_boy_ratings).



Settings: Configures platform-wide settings (settings, footer_settings).



Chat: Logs chatbot interactions with intent detection (chat_messages).

For a complete schema, refer to the SQL dump (onlinefoodphp2.sql) in the repository.

Technologies Used





Backend: PHP



Database: MySQL



Frontend: HTML, CSS, JavaScript (assumed for typical PHP projects)



Security: Bcrypt for password hashing



File Uploads: Supports images (e.g., dish/restaurant photos) and documents (e.g., FSSAI licenses, Aadhaar PDFs)



Geolocation: Latitude/longitude for location-based services

Installation





Clone the Repository:

git clone https://github.com/arka-001/bhojon-barta.git



Set Up MySQL Database:





Create a database named onlinefoodphp2.



Import the SQL dump file (onlinefoodphp2.sql) to initialize tables and sample data.



Configure Database Connection:





Update the database configuration file (e.g., config.php) with your MySQL credentials:

$host = 'localhost';
$dbname = 'onlinefoodphp2';
$username = 'your_username';
$password = 'your_password';



Set Up File Permissions:





Ensure write permissions for upload directories (e.g., admin/delivery_boy_images, admin/Owner_docs).



Run the Application:





Host on a PHP-supported web server (e.g., Apache).



Access via a browser (e.g., http://localhost/bhojon-barta).

Usage





Customers: Sign up, browse menus, add items to cart, apply coupons, and track orders. Leave ratings and reviews post-delivery.



Restaurant Owners: Submit registration requests, manage dishes, and respond to orders.



Delivery Personnel: Register, update availability, accept deliveries, and view history.



Admins: Approve requests, manage platform settings, and monitor activities via the admin panel.

Contributing





Fork the repository.



Create a feature branch (git checkout -b feature-branch).



Commit changes (git commit -m 'Add feature').



Push to the branch (git push origin feature-branch).



Submit a pull request.

License

This project is licensed under the MIT License. See the LICENSE file for details.

Contact

For issues, suggestions, or queries, contact the project maintainer:





Email: arkamaitra001@gmail.com



Mobile/WhatsApp: +91 8670247168

Acknowledgments





Developed by Arka Maitra, a student of the Department of Computer Science and Technology (CST).



Inspired by the need for efficient, user-friendly food ordering systems.

Note: This project is distinct from the commercial "Bhojon" restaurant management software by Bdtask, focusing instead on a student-developed solution tailored for academic and open-source purposes.
