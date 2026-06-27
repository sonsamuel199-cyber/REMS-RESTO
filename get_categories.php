<?php
// get_categories.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$result = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode(['success' => true, 'categories' => $categories]);
$mysqli->close();
?>