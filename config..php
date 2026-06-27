<?php
// config.php - Central Configuration File
session_start();

// Set default timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'remsresto_db');

// Create database connection with timezone
function getDBConnection() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_errno) {
        die("Database connection failed: " . $mysqli->connect_error);
    }
    
    // Set MySQL timezone to Philippines
    $mysqli->query("SET time_zone = '+08:00'");
    
    return $mysqli;
}

// Get current Philippine time
function getPHTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

// Convert any datetime to Philippine time
function toPHTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    
    $date = new DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Manila'));
    return $date->format($format);
}

// Check if user is logged in
function checkLogin($requiredType = null) {
    if (!isset($_SESSION['user_type'])) {
        header("Location: login.php");
        exit();
    }
    
    if ($requiredType && $_SESSION['user_type'] !== $requiredType) {
        header("Location: login.php");
        exit();
    }
}
?>