<?php
session_start();
$_SESSION['user_type'] = 'cashier'; // Auto login for testing

// Test database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

echo "<h1>Debug Test</h1>";

// Test 1: Database connection
echo "<h2>1. Database Connection Test:</h2>";
try {
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_errno) {
        echo "❌ FAILED: " . $mysqli->connect_error;
    } else {
        echo "✅ SUCCESS: Database connected!";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage();
}

// Test 2: Check if tables exist
echo "<h2>2. Table Check:</h2>";
try {
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    $result = $mysqli->query("SHOW TABLES LIKE 'orders'");
    echo "Orders table: " . ($result->num_rows > 0 ? "✅ EXISTS" : "❌ MISSING") . "<br>";
    
    $result = $mysqli->query("SHOW TABLES LIKE 'inventory'");
    echo "Inventory table: " . ($result->num_rows > 0 ? "✅ EXISTS" : "❌ MISSING") . "<br>";
    
    $mysqli->close();
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage();
}

// Test 3: Test JSON input
echo "<h2>3. JSON Input Test:</h2>";
$test_data = ['order' => ['test' => 'data']];
echo "Test JSON: " . json_encode($test_data);
?>