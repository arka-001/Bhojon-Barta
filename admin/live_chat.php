<?php
include("../connection/connect.php"); // Use your admin panel's connection include
error_reporting(0); // Common in admin panels, but consider logging errors
session_start();

// Redirect if not logged in (use your admin panel's auth check)
if(empty($_SESSION["adm_id"])) {
    header('location:index.php');
    exit; // Important to stop script execution after redirect
}
else {
    // Include admin header/sidebar if you have them
    // include('header.php');
    // include('sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Live Chat</title>
    <!-- Include your admin panel's CSS -->
    <link href="css/lib/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="css/helper.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!-- Link the chat CSS -->
    <link rel="stylesheet" type="text/css" href="../css/chat_style.css">
    <style>
        /* Adjustments for admin panel layout */
        .admin-chat-container {
            margin-top: 20px; /* Add space below admin navbar */
            height: calc(85vh); /* Adjust height as needed */
        }
         /* Ensure message area scrolls */
        .admin-chat-area .chat-messages {
            height: 100%; /* Let flexbox handle height */
        }
        .admin-user-list { height: 100%; } /* Match container height */
        .page-wrapper { padding-bottom: 0; } /* Remove extra padding if needed */
    </style>
</head>
<body>

<div class="page-wrapper">
    <!-- Maybe include breadcrumbs or header here -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2>Live User Chat</h2>
                <div class="admin-chat-container card">
                    <div class="admin-user-list card-body" id="admin-user-list">
                        <h3>Conversations</h3>
                        <div id="conversation-list-content">
                            <p>Loading users...</p>
                        </div>
                    </div>
                    <div class="admin-chat-area card-body">
                        <div id="admin-chat-header" class="chat-header" style="display: none;">
                            <span>Chat with: <strong id="chatting-with-user">Select a user</strong></span>
                        </div>
                        <div id="admin-chat-messages" class="chat-messages">
                            <p style="text-align: center; color: #888; margin-top: 50px;">Please select a conversation from the left.</p>
                        </div>
                        <div id="admin-chat-input-area" class="chat-input" style="display: none;">
                            <input type="text" id="admin-chat-message-input" placeholder="Type your message..." autocomplete="off">
                            <button id="admin-chat-send-button">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Maybe include footer here -->
</div>

<!-- Include Admin JS files -->
<script src="js/lib/jquery/jquery.min.js"></script>
<script src="js/lib/bootstrap/js/popper.min.js"></script>
<script src="js/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/sidebarmenu.js"></script>
<script src="js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
<script src="js/custom.min.js"></script>

<!-- Include the Admin Chat JS -->
<script src="js/chat_admin.js"></script>

</body>
</html>
<?php
} // End else block for logged-in admin
?>