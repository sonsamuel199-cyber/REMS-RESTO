<?php
// delete_category.php (modified with auto-reassign to NULL)
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit();
}

$categoryId = (int)$input['id'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

// Optional: Instead of checking, just set items' category to NULL or a default category
$mysqli->begin_transaction();

// Get category name before deleting
$stmt = $mysqli->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
$categoryName = $result->fetch_assoc()['name'] ?? null;
$stmt->close();

if ($categoryName) {
    // Set all items with this category to NULL (or to a default category like 'Uncategorized')
    $updateStmt = $mysqli->prepare("UPDATE inventory SET category = NULL WHERE category = ?");
    $updateStmt->bind_param("s", $categoryName);
    $updateStmt->execute();
    $updateStmt->close();
}

// Delete the category
$stmt = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully. Items reassigned to NULL.']);
} else {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
}
$stmt->close();
$mysqli->close();
?>