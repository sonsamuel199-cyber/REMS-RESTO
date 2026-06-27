<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$customer_name = trim($input['customer_name'] ?? 'Walk-in Customer');
$table_number = (int)($input['table_number'] ?? 0);
$items = json_encode($input['items'] ?? []);
$discount_senior = !empty($input['discount']['senior']) ? 1 : 0;
$discount_pwd = !empty($input['discount']['pwd']) ? 1 : 0;
$subtotal = (float)($input['totals']['subtotal'] ?? 0);
$discount_amount = (float)($input['totals']['discount'] ?? 0);
$tax = (float)($input['totals']['tax'] ?? 0);
$total_amount = (float)($input['totals']['total'] ?? 0);

if ($table_number <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid table number required']);
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

$sql = "INSERT INTO saved_carts (customer_name, table_number, items, discount_senior, discount_pwd, 
        subtotal, discount_amount, tax, total_amount, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $mysqli->error]);
    $mysqli->close();
    exit();
}

$stmt->bind_param("sisiiiddd", $customer_name, $table_number, $items, $discount_senior, $discount_pwd, 
                  $subtotal, $discount_amount, $tax, $total_amount);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'ticket_id' => $mysqli->insert_id,
        'message' => 'Ticket saved successfully!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>