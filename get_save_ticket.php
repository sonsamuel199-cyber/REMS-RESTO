<?php
session_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
    exit();
}

$result = $mysqli->query("SELECT id, customer_name, table_number, total_amount, saved_at FROM saved_carts WHERE status = 'active' ORDER BY saved_at DESC");

$tickets = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = [
            'id' => (int)$row['id'],
            'customer_name' => $row['customer_name'],
            'table_number' => (int)$row['table_number'],
            'total_amount' => (float)$row['total_amount'],
            'saved_at' => $row['saved_at']
        ];
    }
    $result->free();
} else {
    echo json_encode(['error' => 'Query failed: ' . $mysqli->error]);
    $mysqli->close();
    exit();
}

$mysqli->close();
echo json_encode($tickets);
exit();
?>