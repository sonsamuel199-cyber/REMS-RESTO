<?php
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['order'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit();
}

$orderData = $data['order'];

try {
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_errno) {
        throw new Exception("Database connection failed");
    }

    $mysqli->begin_transaction();

    // Insert into orders table
    $stmt = $mysqli->prepare("INSERT INTO orders (subtotal, discount, tax, total_amount, table_number, payment_method, amount_received, change_amount, senior_discount, pwd_discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $subtotal = $orderData['totals']['subtotal'];
    $discount = $orderData['totals']['discount'];
    $tax = $orderData['totals']['tax'];
    $total = $orderData['totals']['total'];
    $table_number = $orderData['table_number'];
    $payment_method = $orderData['payment']['method'];
    $amount_received = $orderData['payment']['amount_received'];
    $change = $orderData['payment']['change'];
    $senior_discount = $orderData['discount']['senior'] ? 1 : 0;
    $pwd_discount = $orderData['discount']['pwd'] ? 1 : 0;

    $stmt->bind_param("ddddisddii", 
        $subtotal,
        $discount,
        $tax,
        $total,
        $table_number,
        $payment_method,
        $amount_received,
        $change,
        $senior_discount,
        $pwd_discount
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert order: " . $stmt->error);
    }
    
    $orderId = $mysqli->insert_id;
    $stmt->close();

    // Insert order items and update inventory
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, name, price, quantity) VALUES (?, ?, ?, ?)");
    
    foreach ($orderData['items'] as $item) {
        $stmt->bind_param("isdi", $orderId, $item['name'], $item['price'], $item['quantity']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $stmt->error);
        }
        
        // Update inventory stock
        $updateStmt = $mysqli->prepare("UPDATE inventory SET stock = stock - ? WHERE id = ?");
        $updateStmt->bind_param("ii", $item['quantity'], $item['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
    $stmt->close();

    // Set order status to completed (paid orders are successful)
    $updateStatus = $mysqli->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
    $updateStatus->bind_param("i", $orderId);
    $updateStatus->execute();
    $updateStatus->close();

    // Mark saved ticket as completed if provided
    if (!empty($orderData['saved_ticket_id'])) {
        $ticketId = $orderData['saved_ticket_id'];
        $updateTicket = $mysqli->prepare("UPDATE saved_carts SET status = 'completed' WHERE id = ? AND status = 'active'");
        $updateTicket->bind_param("i", $ticketId);
        if (!$updateTicket->execute()) {
            throw new Exception("Failed to update saved ticket status: " . $updateTicket->error);
        }
        $updateTicket->close();
    }

    $mysqli->commit();
    $mysqli->close();
    
    echo json_encode([
        'success' => true, 
        'order_id' => $orderId, 
        'message' => 'Order processed successfully!'
    ]);

} catch (Exception $e) {
    if (isset($mysqli)) {
        $mysqli->rollback();
        $mysqli->close();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>