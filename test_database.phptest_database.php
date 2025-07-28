<?php
// test_database.php

// 1. Enable FULL error reporting.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Database Connection Test</h3>";

// 2. Try to include your connection file.
$connection_file = __DIR__ . '/connection/connect.php';
echo "Attempting to load connection from: " . htmlspecialchars($connection_file) . "<br>";

if (!file_exists($connection_file)) {
    echo "<strong style='color:red;'>FATAL ERROR: The connection file was not found at this path.</strong><br>";
    echo "Please ensure the path is correct.<br>";
    exit;
}

// This is the line that will likely show an error.
require_once $connection_file;

// 3. Check if the connection variable ($db) exists and if the connection was successful.
if (isset($db) && $db) {
    echo "<span style='color:green;'>SUCCESS: The file was included and the connection variable exists.</span><hr>";
    
    echo "Pinging the database to check connection status...<br>";
    if (mysqli_ping($db)) {
        echo "<span style='color:green;'>SUCCESS: Database ping was successful! The connection is active.</span><hr>";
        echo "<h2>✅ Database connection is working correctly!</h2>";
    } else {
        echo "<strong style='color:red;'>ERROR: A connection was established, but the ping failed.</strong><br>";
        echo "This might indicate a timeout or a dropped connection. Error: " . mysqli_error($db) . "<br>";
    }
    
    mysqli_close($db);

} else {
    echo "<strong style='color:red;'>FATAL ERROR: The connection file was loaded, but the database connection failed.</strong><br>";
    echo "Please open your <code>connection/connect.php</code> file and double-check your:<br>";
    echo "<ul><li>Database Hostname (usually 'localhost' for XAMPP)</li><li>Database Username (usually 'root' for XAMPP)</li><li>Database Password (usually empty/blank for a default XAMPP install)</li><li>Database Name (must be 'onlinefoodphp2')</li></ul>";
}

?>