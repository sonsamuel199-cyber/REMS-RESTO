<?php
// check_order_date.php - Check if order is from current business day
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
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

$stmt = $mysqli->prepare("SELECT DATE(created_at) as order_date FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$row = $result->fetch_assoc();
$orderDate = $row['order_date'];
$today = date('Y-m-d');

echo json_encode([
    'success' => true,
    'order_date' => $orderDate,
    'today' => $today,
    'requires_override' => ($orderDate !== $today)
]);

$stmt->close();
$mysqli->close();
?>