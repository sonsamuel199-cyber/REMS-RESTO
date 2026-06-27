<?php
// cancel_order.php - Enhanced with all fixes
session_start();
header('Content-Type: application/json');

// Only admin (inventory role) can cancel
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? 0;
$reason = trim($input['reason'] ?? '');
$refundType = $input['refund_type'] ?? 'restock'; // 'restock' or 'waste'
$adminOverride = $input['admin_override'] ?? false;

if (!$orderId || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Order ID and reason are required']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// ============================================
// FIX 002: BUSINESS DAY VALIDATION
// ============================================

// Check if order is from current business day
$dateCheck = $mysqli->prepare("SELECT DATE(created_at) as order_date, status FROM orders WHERE id = ?");
$dateCheck->bind_param("i", $orderId);
$dateCheck->execute();
$dateResult = $dateCheck->get_result();
if ($dateResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}
$orderData = $dateResult->fetch_assoc();
$orderDate = $orderData['order_date'];
$orderStatus = $orderData['status'];
$dateCheck->close();

if ($orderStatus === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Order already cancelled']);
    exit();
}

$today = date('Y-m-d');

// Ensure business day exists
$businessCheck = $mysqli->prepare("SELECT id FROM business_days WHERE business_date = ?");
$businessCheck->bind_param("s", $today);
$businessCheck->execute();
$businessResult = $businessCheck->get_result();
if ($businessResult->num_rows === 0) {
    $insertBusiness = $mysqli->prepare("INSERT INTO business_days (business_date) VALUES (?)");
    $insertBusiness->bind_param("s", $today);
    $insertBusiness->execute();
    $insertBusiness->close();
}
$businessCheck->close();

if ($orderDate !== $today && !$adminOverride) {
    echo json_encode([
        'success' => false, 
        'message' => 'This order is from a previous business day. Admin override required.',
        'requires_override' => true
    ]);
    exit();
}

$mysqli->begin_transaction();

try {
    // 1. Check if order exists and is not already cancelled (re-check with lock)
    $checkStmt = $mysqli->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE");
    $checkStmt->bind_param("i", $orderId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($result->num_rows === 0) throw new Exception("Order not found");
    $row = $result->fetch_assoc();
    if ($row['status'] === 'cancelled') throw new Exception("Order already cancelled");
    $checkStmt->close();

    // 2. Get order items to restore stock or log waste
    $itemsStmt = $mysqli->prepare("SELECT item_id, quantity, name FROM order_items WHERE order_id = ?");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $items = $itemsResult->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

    // ============================================
    // FIX 005: WASTE MANAGEMENT LOGIC
    // ============================================
    $username = $_SESSION['username'] ?? 'Administrator';
    
    if ($refundType === 'restock') {
        // Restore stock for each item
        $updateStock = $mysqli->prepare("UPDATE inventory SET stock = stock + ? WHERE id = ?");
        foreach ($items as $item) {
            $updateStock->bind_param("ii", $item['quantity'], $item['item_id']);
            if (!$updateStock->execute()) {
                throw new Exception("Failed to restore stock for item ID {$item['item_id']}");
            }
        }
        $updateStock->close();
    } else {
        // Log as waste instead of restoring stock
        $wasteStmt = $mysqli->prepare("INSERT INTO waste_log (order_id, item_id, quantity, reason, recorded_by) 
                                       VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $wasteStmt->bind_param("iiiss", $orderId, $item['item_id'], $item['quantity'], $reason, $username);
            if (!$wasteStmt->execute()) {
                throw new Exception("Failed to log waste for item ID {$item['item_id']}");
            }
        }
        $wasteStmt->close();
    }

    // ============================================
    // FIX 001: UPDATE ORDER WITH ALL NEW COLUMNS
    // ============================================
    $cancelStmt = $mysqli->prepare("UPDATE orders SET 
        status = 'cancelled', 
        cancelled_at = NOW(), 
        cancellation_reason = ?, 
        cancelled_by = ?,
        refund_type = ?
        WHERE id = ?");
    
    $cancelStmt->bind_param("sssi", $reason, $username, $refundType, $orderId);
    if (!$cancelStmt->execute()) {
        throw new Exception("Failed to update order status");
    }
    $cancelStmt->close();

    $mysqli->commit();
    
    $message = "Order #$orderId cancelled successfully. ";
    $message .= ($refundType === 'restock') ? "Stock restored." : "Items marked as waste.";
    
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$mysqli->close();
?>