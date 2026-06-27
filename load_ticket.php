<?php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $mysqli->connect_error]);
    exit();
}

$stmt = $mysqli->prepare("SELECT customer_name, table_number, items, discount_senior, discount_pwd, 
                                 subtotal, discount_amount, tax, total_amount 
                          FROM saved_carts 
                          WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $items = json_decode($row['items'], true);
    if (!is_array($items)) $items = [];
    
    echo json_encode([
        'success' => true,
        'customer_name' => $row['customer_name'],
        'table_number' => (int)$row['table_number'],
        'items' => $items,
        'discount_senior' => (bool)$row['discount_senior'],
        'discount_pwd' => (bool)$row['discount_pwd'],
        'totals' => [
            'subtotal' => (float)$row['subtotal'],
            'discount' => (float)$row['discount_amount'],
            'tax' => (float)$row['tax'],
            'total' => (float)$row['total_amount']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ticket not found or already closed']);
}

$stmt->close();
$mysqli->close();
?>