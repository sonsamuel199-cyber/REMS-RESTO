<?php
// add_category.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['name'])) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

$categoryName = trim($input['name']);
if (strlen($categoryName) < 2) {
    echo json_encode(['success' => false, 'message' => 'Category name must be at least 2 characters']);
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

// Check if category already exists (case-insensitive)
$stmt = $mysqli->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
$stmt->bind_param("s", $categoryName);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Category already exists']);
    $stmt->close();
    $mysqli->close();
    exit();
}
$stmt->close();

// Insert new category
$stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
$stmt->bind_param("s", $categoryName);

if ($stmt->execute()) {
    $newId = $mysqli->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'category' => ['id' => $newId, 'name' => $categoryName]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add category: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>
