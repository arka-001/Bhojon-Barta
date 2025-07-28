<?php
// test_environment.php

// 1. Enable FULL error reporting. This is the most important part.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Environment Test: Step 1 - Autoloader</h3>";

// 2. Test the 'vendor/autoload.php' path.
// This is the most common point of failure.
$autoloaderPath = __DIR__ . '/vendor/autoload.php';
echo "Checking for autoloader at: " . htmlspecialchars($autoloaderPath) . "<br>";

if (!file_exists($autoloaderPath)) {
    echo "<strong style='color:red;'>FATAL ERROR: The autoloader file was not found at this path.</strong><br>";
    echo "Please ensure the 'vendor' directory (created by Composer) is in the same directory as this test script.<br>";
    exit; // Stop the script here.
}

require_once $autoloaderPath;
echo "<span style='color:green;'>SUCCESS: Autoloader found and included.</span><hr>";


echo "<h3>Environment Test: Step 2 - .env File</h3>";

// 3. Test the '.env' file loading.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "<span style='color:green;'>SUCCESS: The .env file was loaded successfully.</span><hr>";
} catch (\Exception $e) {
    echo "<strong style='color:red;'>FATAL ERROR: Could not load the .env file.</strong><br>";
    echo "Please ensure a file named exactly '.env' exists in the same directory as this script and that the server has permission to read it.<br>";
    echo "Error details: " . $e->getMessage() . "<br>";
    exit; // Stop the script here.
}


echo "<h3>Environment Test: Step 3 - API Key Check</h3>";

// 4. Test if the API key was read from the .env file.
$geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;

if (empty($geminiApiKey)) {
    echo "<strong style='color:red;'>FATAL ERROR: The GEMINI_API_KEY was not found inside your .env file.</strong><br>";
    echo "Please open your .env file and make sure it contains the line: <br><code>GEMINI_API_KEY=\"YOUR_REAL_API_KEY\"</code><br>";
    exit; // Stop the script here.
}

echo "<span style='color:green;'>SUCCESS: GEMINI_API_KEY was found in the .env file.</span><hr>";
echo "<h2>✅ All environment checks passed!</h2>";
echo "<p>Your server environment appears to be correctly set up. The problem might be with the database connection or another part of the main script.</p>";

?>