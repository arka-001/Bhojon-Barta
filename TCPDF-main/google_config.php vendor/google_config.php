<?php
// TCPDF-main/google_config.php

// --- IMPORTANT: Add this file to your .gitignore file! ---

define('GOOGLE_CLIENT_ID', '1068721017091-58fq679teevpt5aouhqffsnegvhdar01.apps.googleusercontent.com'); // YOUR Client ID
define('GOOGLE_CLIENT_SECRET', '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'); // YOUR Client Secret - KEEP THIS SECRET!

// --- VERY IMPORTANT ---
// MAKE SURE this exact URI is added to the "Authorized redirect URIs" in your Google Cloud Console.
define('GOOGLE_REDIRECT_URI', 'http://localhost/OnlineFood-PHP/google_callback.php');

// Include your existing database connection details
// Ensure this path is correct relative to google_config.php
require_once __DIR__ . '/connection/connect.php';

// Make sure connect.php defines the $db variable in the scope where it's included,
// or modify the callback script to establish the connection itself.
// Assuming connect.php makes $db available globally or in the inclusion scope.

?>