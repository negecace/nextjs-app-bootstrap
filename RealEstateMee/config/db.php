<?php
// Database configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'realestatemeedb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create a new PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
