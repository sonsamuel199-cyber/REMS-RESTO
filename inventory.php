<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Kunin ang lahat ng categories para sa dropdown
$categoriesList = [];
$catResult = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categoriesList[] = $row;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['delete_id'])) {
            $id = $data['delete_id'];
            $stmt = $mysqli->prepare("DELETE FROM inventory WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to delete item: ' . $mysqli->error]);
                }
                $stmt->close();
            }
            exit;
        }
    }
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_item') {
            $name = $_POST['name'];
            $category = $_POST['category'];
            $price = $_POST['price'];
            $description = $_POST['description'];
            $stock = $_POST['stock'];
            $min_stock = $_POST['min_stock'];
            $unit = $_POST['unit'] ?? 'pcs';
            if ($stock == 0) {
                header("Location: inventory.php?status=error&message=Initial stock cannot be 0");
                exit();
            }
            $stmt = $mysqli->prepare("INSERT INTO inventory (name, category, price, description, stock, min_stock, unit_of_measure) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssdsiis", $name, $category, $price, $description, $stock, $min_stock, $unit);
                if ($stmt->execute()) {
                    header("Location: inventory.php?status=added");
                } else {
                    header("Location: inventory.php?status=error&message=Failed to add item");
                }
                $stmt->close();
            } else {
                header("Location: inventory.php?status=error&message=Database error");
            }
            exit();
        }
        
        if ($_POST['action'] === 'edit_item') {
            $id = $_POST['edit_id'];
            $name = $_POST['edit_name'];
            $category = $_POST['edit_category'];
            $price = $_POST['edit_price'];
            $description = $_POST['edit_description'];
            $stock = $_POST['edit_stock'];
            $min_stock = $_POST['edit_min_stock'];
            $unit = $_POST['edit_unit'] ?? 'pcs';
            if ($stock == 0) {
                header("Location: inventory.php?status=error&message=Stock cannot be 0");
                exit();
            }
            $stmt = $mysqli->prepare("UPDATE inventory SET name=?, category=?, price=?, description=?, stock=?, min_stock=?, unit_of_measure=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("ssdsiisi", $name, $category, $price, $description, $stock, $min_stock, $unit, $id);
                if ($stmt->execute()) {
                    header("Location: inventory.php?status=updated");
                } else {
                    header("Location: inventory.php?status=error&message=Failed to update item");
                }
                $stmt->close();
            } else {
                header("Location: inventory.php?status=error&message=Database error");
            }
            exit();
        }
    }
}

function getInventoryItems() {
    global $mysqli;
    $items = [];
    $result = $mysqli->query("SELECT id, name, category, price, description, stock, min_stock, unit_of_measure FROM inventory ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['price'] = (float)$row['price'];
            $row['stock'] = (int)$row['stock'];
            $row['min_stock'] = (int)$row['min_stock'];
            $row['unit_of_measure'] = $row['unit_of_measure'] ?? 'pcs';
            $items[] = $row;
        }
    }
    return $items;
}

$inventoryItems = getInventoryItems();
$mysqli->close();

$username = 'Admin';
$currentDate = date('Y-m-d');
$statusMessage = "";
$statusType = "";
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added': $statusMessage = "Item added successfully!"; $statusType = "success"; break;
        case 'updated': $statusMessage = "Item updated successfully!"; $statusType = "success"; break;
        case 'error': $statusMessage = isset($_GET['message']) ? $_GET['message'] : "An error occurred!"; $statusType = "error"; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REMS RESTO - Inventory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-gradient: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            --primary-dark: #b91c1c;
            --primary-light: #f87171;
            --secondary-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.08);
        }
        
        .header-gradient {
            background: var(--primary-gradient);
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2);
        }
        
        .stats-card {
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            border-radius: 1rem;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);
        }
        
        .stats-card.success {
            background: var(--success-gradient);
        }
        
        .stats-card.warning {
            background: var(--warning-gradient);
        }
        
        .stats-card.danger {
            background: var(--danger-gradient);
        }
        
        .stock-low { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            font-weight: 600;
            border: 1px solid #fbbf24;
        }
        
        .stock-ok { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #34d399;
        }
        
        .stock-out { 
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }
        
        .table-row {
            transition: all 0.3s ease;
        }
        
        .table-row:hover {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            transform: translateX(5px);
            border-left: 4px solid var(--primary-dark);
        }
        
        @keyframes pop-in {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .animate-pop-in {
            animation: pop-in 0.3s ease-out;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .mobile-stack {
                flex-direction: column;
            }
            .mobile-full {
                width: 100%;
            }
            .mobile-padding {
                padding: 1rem;
            }
            .mobile-text-center {
                text-align: center;
            }
            .mobile-table {
                font-size: 0.875rem;
            }
            .mobile-table th,
            .mobile-table td {
                padding: 0.5rem;
            }
            .mobile-hidden {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            .mobile-col {
                flex-direction: column;
                align-items: stretch;
            }
            .mobile-col > * {
                width: 100%;
            }
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 3px;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .category-appetizers { background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%); color: #4f46e5; }
        .category-mains { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); color: #dc2626; }
        .category-desserts { background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%); color: #92400e; }
        .category-beverages { background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%); color: #065f46; }
        
        .action-btn {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .btn-edit { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; }
        .btn-delete { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        
        .search-input {
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .empty-state {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px dashed #cbd5e1;
        }

        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid #fee2e2;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        @media (max-width: 768px) {
            .mobile-bottom-nav { display: flex; }
            body { padding-bottom: 80px; }
        }
    </style>
</head>
<body class="min-h-screen">

    <!-- Header -->
    <header class="header-gradient shadow-2xl">
        <div class="max-w-8xl mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                <div class="flex items-center space-x-3">
                    <div class="bg-white p-2 rounded-2xl shadow-lg transform hover:rotate-12 transition-transform duration-300 w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center overflow-hidden">
                        <img src="images/2be0107a-02ff-48e0-92c4-f109ec040290.png" alt="REMS RESTO Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl sm:text-2xl font-bold">REMS RESTO</h1>
                        <p class="text-red-100 text-xs sm:text-sm">Inventory Management</p>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="text-white text-center sm:text-right">
                        <p class="font-semibold text-sm sm:text-base">Welcome, <?php echo htmlspecialchars($username); ?>!</p>
                        <p class="text-red-100 text-xs sm:text-sm"><?php echo date('M j, Y'); ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="window.location.href='usermanagement.php'" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-users"></i>
                            <span class="hidden sm:inline">User Management</span>
                        </button>
                        <button onclick="window.location.href='earnings.php'" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-chart-line"></i>
                            <span class="hidden sm:inline">Earnings</span>
                        </button>
                        <button onclick="logout()" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-8xl mx-auto p-2 sm:p-4">
        <!-- Status Messages -->
        <?php if ($statusMessage): ?>
            <div class="<?php echo $statusType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'; ?> border px-4 py-3 rounded-xl mb-4 sm:mb-6 animate-pop-in">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $statusType === 'error' ? 'exclamation-circle' : 'check-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($statusMessage); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="stats-card p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-xs sm:text-sm font-medium">Total Items</p>
                        <p class="text-xl sm:text-2xl font-bold"><?php echo count($inventoryItems); ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-box-open text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="stats-card warning p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-xs sm:text-sm font-medium">Low Stock</p>
                        <p class="text-xl sm:text-2xl font-bold" id="lowStockCount">0</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-exclamation-triangle text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="stats-card success p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-xs sm:text-sm font-medium">In Stock</p>
                        <p class="text-xl sm:text-2xl font-bold" id="inStockCount">0</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-check-circle text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>
            
            <div class="stats-card danger p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-xs sm:text-sm font-medium">Out of Stock</p>
                        <p class="text-xl sm:text-2xl font-bold" id="outOfStockCount">0</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-times-circle text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Actions -->
        <div class="glass-effect rounded-xl sm:rounded-2xl shadow-lg p-4 mb-4 sm:mb-6">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-4 lg:space-y-0">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1">Menu Inventory</h1>
                    <p class="text-gray-600 text-xs sm:text-sm">Manage your menu items and stock levels</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full lg:w-auto">
                    <div class="relative flex-1 sm:flex-none">
                        <input type="text" id="searchInput" placeholder="Search items..." class="w-full sm:w-64 search-input rounded-lg px-4 py-2 text-sm focus:outline-none">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105"
                            onclick="showAddItemModal()">
                        <i class="fas fa-plus"></i>
                        <span class="text-sm">Add Item</span>
                    </button>
                    <button class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105"
                            onclick="showAddCategoryModal()">
                        <i class="fas fa-tag"></i>
                        <span class="text-sm">Add Category</span>
                    </button>
                    <button class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105"
                            onclick="showManageCategoriesModal()">
                        <i class="fas fa-edit"></i>
                        <span class="text-sm">Delete Categories</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="glass-effect rounded-xl sm:rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full mobile-table">
                    <thead class="bg-gradient-to-r from-red-50 to-red-100">
                        <tr>
                            <th class="py-3 px-4 text-left text-xs sm:text-sm font-semibold text-red-700 uppercase tracking-wider">Item Details</th>
                            <th class="py-3 px-4 text-left text-xs sm:text-sm font-semibold text-red-700 uppercase tracking-wider mobile-hidden">Category</th>
                            <th class="py-3 px-4 text-left text-xs sm:text-sm font-semibold text-red-700 uppercase tracking-wider">Price</th>
                            <th class="py-3 px-4 text-left text-xs sm:text-sm font-semibold text-red-700 uppercase tracking-wider">Stock Status</th>
                            <th class="py-3 px-4 text-right text-xs sm:text-sm font-semibold text-red-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody" class="divide-y divide-gray-200">
                        <!-- Inventory items will be rendered here by JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="hidden empty-state rounded-xl p-8 sm:p-12 text-center m-4">
                <div class="text-4xl sm:text-6xl mb-4 opacity-30 text-red-300">
                    <i class="fas fa-box-open"></i>
                </div>
                <p class="text-lg sm:text-xl font-medium text-gray-400 mb-2">No items found</p>
                <p class="text-sm sm:text-base text-gray-500 mb-6">Add your first item to get started</p>
                <button onclick="showAddItemModal()" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-lg inline-flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add New Item</span>
                </button>
            </div>
        </div>
    </main>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <div class="flex justify-around items-center h-16">
            <a href="home.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-home text-xl"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="inventory.php" class="flex flex-col items-center justify-center w-full h-full text-red-600">
                <i class="fas fa-boxes text-xl"></i>
                <span class="text-xs mt-1">Inventory</span>
            </a>
            <a href="earnings.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-chart-line text-xl"></i>
                <span class="text-xs mt-1">Earnings</span>
            </a>
            <a href="usermanagement.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-cog text-xl"></i>
                <span class="text-xs mt-1">Settings</span>
            </a>
        </div>
    </div>
    
    <!-- Add New Item Modal -->
    <div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-2xl w-full mx-4 shadow-2xl transform animate-pop-in max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6 border-b pb-3 sm:pb-4">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800">Add New Menu Item</h3>
                <button onclick="closeAddItemModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="addItemForm" method="POST" action="inventory.php" onsubmit="return validateStock()">
                <input type="hidden" name="action" value="add_item">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Item Name</label>
                        <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Category</label>
                        <select name="category" id="addItemCategorySelect" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                            <option value="">Loading categories...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Price (₱)</label>
                        <input type="number" name="price" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Initial Stock</label>
                        <input type="number" name="stock" min="1" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                        <p class="text-xs text-gray-500 mt-1">Stock must be at least 1</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Minimum Stock Alert</label>
                        <input type="number" name="min_stock" min="1" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" value="5" required>
                    </div>
                    <!-- Unit of Measure -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Unit of Measure</label>
                        <select name="unit" id="addItemUnit" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="g">Grams (g)</option>
                            <option value="ml">Milliliters (ml)</option>
                            <option value="L">Liters (L)</option>
                            <option value="cup">Cups</option>
                            <option value="tbsp">Tablespoons (tbsp)</option>
                            <option value="tsp">Teaspoons (tsp)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the unit for this item</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Description</label>
                        <textarea name="description" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" rows="3" required></textarea>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 sm:mt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white py-3 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                    <button type="button" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300"
                            onclick="closeAddItemModal()">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-2xl w-full mx-4 shadow-2xl transform animate-pop-in max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6 border-b pb-3 sm:pb-4">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800">Edit Menu Item</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="editItemForm" method="POST" action="inventory.php" onsubmit="return validateEditStock()">
                <input type="hidden" name="action" value="edit_item">
                <input type="hidden" name="edit_id" id="editItemId">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Item Name</label>
                        <input type="text" name="edit_name" id="editItemName" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Category</label>
                        <select name="edit_category" id="editItemCategorySelect" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                            <option value="">Loading categories...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Price (₱)</label>
                        <input type="number" name="edit_price" id="editItemPrice" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Stock</label>
                        <input type="number" name="edit_stock" id="editItemStock" min="1" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                        <p class="text-xs text-gray-500 mt-1">Stock must be at least 1</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Minimum Stock Alert</label>
                        <input type="number" name="edit_min_stock" id="editItemMinStock" min="1" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" required>
                    </div>
                    <!-- Unit of Measure (Edit) -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Unit of Measure</label>
                        <select name="edit_unit" id="editItemUnit" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="g">Grams (g)</option>
                            <option value="ml">Milliliters (ml)</option>
                            <option value="L">Liters (L)</option>
                            <option value="cup">Cups</option>
                            <option value="tbsp">Tablespoons (tbsp)</option>
                            <option value="tsp">Teaspoons (tsp)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the unit for this item</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Description</label>
                        <textarea name="edit_description" id="editItemDescription" class="w-full border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm" rows="3" required></textarea>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4 sm:mt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-lg font-semibold hover:from-green-600 hover:to-emerald-700 transition-all duration-300 shadow-lg">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button type="button" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300"
                            onclick="closeEditModal()">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-md w-full mx-4 text-center transform animate-pop-in">
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-4 rounded-2xl inline-flex mb-4">
                <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">Confirm Delete</h3>
            <p class="text-gray-600 mb-4" id="deleteConfirmMessage">Are you sure you want to delete this item?</p>
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                <button onclick="confirmDelete()" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white py-2 sm:py-3 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
                <button onclick="cancelDelete()" class="flex-1 bg-gray-300 text-gray-700 py-2 sm:py-3 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-md w-full mx-4 shadow-2xl transform animate-pop-in">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800">Add New Category</h3>
                <button onclick="closeAddCategoryModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="addCategoryForm">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Category Name</label>
                    <input type="text" id="newCategoryName" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., Rice Bowls, Pasta, Sizzling" required>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg">
                        <i class="fas fa-save mr-2"></i>Save Category
                    </button>
                    <button type="button" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300" onclick="closeAddCategoryModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Categories Modal (Delete Category) -->
    <div id="manageCategoriesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-2xl w-full mx-4 shadow-2xl transform animate-pop-in max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800">Delete Categories</h3>
                <button onclick="closeManageCategoriesModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="categoriesListContainer" class="space-y-2">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading categories...</div>
            </div>
            <div class="mt-4 flex justify-end">
                <button onclick="closeManageCategoriesModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <script>
        let inventoryItems = <?php echo json_encode($inventoryItems); ?>;
        let itemToDelete = null;
        let categoriesList = [];

        function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }
        function formatPrice(price) { return price.toFixed(2); }
        function getStockClass(stock, minStock) { if (stock === 0) return 'stock-out'; if (stock <= minStock) return 'stock-low'; return 'stock-ok'; }
        function getStockText(stock, minStock) { if (stock === 0) return 'Out of Stock'; if (stock <= minStock) return 'Low Stock'; return 'In Stock'; }
        function getCategoryClass(category) { return `category-${category.toLowerCase()}`; }

        function updateInventoryStats() {
            const lowStockCount = inventoryItems.filter(item => item.stock > 0 && item.stock <= item.min_stock).length;
            const outOfStockCount = inventoryItems.filter(item => item.stock === 0).length;
            const inStockCount = inventoryItems.filter(item => item.stock > item.min_stock).length;
            document.getElementById('lowStockCount').textContent = lowStockCount;
            document.getElementById('outOfStockCount').textContent = outOfStockCount;
            document.getElementById('inStockCount').textContent = inStockCount;
        }

        function updateInventoryTable() {
            const tableBody = document.getElementById('inventoryTableBody');
            const emptyState = document.getElementById('emptyState');
            const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
            tableBody.innerHTML = '';
            let filteredItems = inventoryItems;
            if (searchTerm) filteredItems = inventoryItems.filter(item => item.name.toLowerCase().includes(searchTerm) || item.description.toLowerCase().includes(searchTerm) || item.category.toLowerCase().includes(searchTerm));
            if (filteredItems.length === 0) { tableBody.innerHTML = ''; if (emptyState) emptyState.classList.remove('hidden'); return; }
            if (emptyState) emptyState.classList.add('hidden');
            filteredItems.forEach(item => {
                const stockClass = getStockClass(item.stock, item.min_stock);
                const stockText = getStockText(item.stock, item.min_stock);
                const categoryClass = getCategoryClass(item.category);
                const unit = item.unit_of_measure || 'pcs';
                const row = document.createElement('tr'); row.className = 'table-row transition-all duration-300';
                row.innerHTML = `<td class="py-3 px-4"><div class="flex items-center space-x-3"><div class="bg-gradient-to-r from-red-100 to-red-50 p-2 sm:p-3 rounded-lg"><i class="fas fa-utensils text-red-600 text-base"></i></div><div><div class="font-semibold text-gray-900 text-sm sm:text-base">${escapeHtml(item.name)}</div><div class="text-xs text-gray-500 mt-1 max-w-xs truncate">${escapeHtml(item.description)}</div></div></div></td><td class="py-3 px-4 mobile-hidden"><span class="category-badge ${categoryClass}">${escapeHtml(item.category.charAt(0).toUpperCase() + item.category.slice(1))}</span></td><td class="py-3 px-4 font-bold text-gray-900 text-sm sm:text-base">₱${formatPrice(item.price)}</td><td class="py-3 px-4"><span class="${stockClass} px-2 sm:px-3 py-1 sm:py-2 rounded-full text-xs font-semibold inline-block">${stockText} • ${item.stock} ${unit}</span></td><td class="py-3 px-4 text-right text-sm font-medium space-x-1 sm:space-x-2"><button class="action-btn btn-edit" onclick="showEditModal(${item.id})"><i class="fas fa-edit mr-1"></i>Edit</button><button class="action-btn btn-delete" onclick="showDeleteModal(${item.id}, '${escapeHtml(item.name).replace(/'/g, "\\'")}')"><i class="fas fa-trash mr-1"></i>Delete</button></td>`;
                tableBody.appendChild(row);
            });
        }

        document.getElementById('searchInput')?.addEventListener('input', function() { updateInventoryTable(); });

        // Add/Edit Item Modal functions
        function showAddItemModal() { document.getElementById('addItemModal').classList.remove('hidden'); document.getElementById('addItemForm').reset(); }
        function closeAddItemModal() { document.getElementById('addItemModal').classList.add('hidden'); }
        function showEditModal(id) { const item = inventoryItems.find(i => i.id === id); if (!item) return; document.getElementById('editItemId').value = item.id; document.getElementById('editItemName').value = item.name; document.getElementById('editItemCategorySelect').value = item.category; document.getElementById('editItemPrice').value = item.price.toFixed(2); document.getElementById('editItemStock').value = item.stock; document.getElementById('editItemMinStock').value = item.min_stock; document.getElementById('editItemDescription').value = item.description; document.getElementById('editItemUnit').value = item.unit_of_measure || 'pcs'; document.getElementById('editItemModal').classList.remove('hidden'); }
        function closeEditModal() { document.getElementById('editItemModal').classList.add('hidden'); }
        function showDeleteModal(id, name) { itemToDelete = id; document.getElementById('deleteConfirmMessage').textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`; document.getElementById('deleteConfirmModal').classList.remove('hidden'); }
        function cancelDelete() { itemToDelete = null; document.getElementById('deleteConfirmModal').classList.add('hidden'); }
        function confirmDelete() { if (!itemToDelete) return; const itemName = inventoryItems.find(i => i.id === itemToDelete)?.name || 'Item'; fetch('inventory.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ delete_id: itemToDelete }) }).then(response => response.json()).then(data => { if (data.success) { showNotification(`"${itemName}" deleted successfully`, 'success'); inventoryItems = inventoryItems.filter(item => item.id !== itemToDelete); updateInventoryTable(); updateInventoryStats(); } else { showNotification('Error deleting item: ' + data.message, 'error'); } }).catch(error => { showNotification("An error occurred during deletion.", 'error'); }).finally(() => { cancelDelete(); }); }
        function validateStock() { const stockInput = document.querySelector('input[name="stock"]'); if (parseInt(stockInput.value) < 1) { alert('Initial stock cannot be 0 or negative.'); stockInput.focus(); return false; } return true; }
        function validateEditStock() { const stockInput = document.querySelector('input[name="edit_stock"]'); if (parseInt(stockInput.value) < 1) { alert('Stock cannot be 0 or negative.'); stockInput.focus(); return false; } return true; }
        function showNotification(message, type = 'info') { const notification = document.createElement('div'); notification.className = `fixed top-4 right-4 glass-effect p-4 rounded-xl shadow-lg transform animate-pop-in z-50 ${type === 'success' ? 'border-l-4 border-green-500' : type === 'error' ? 'border-l-4 border-red-500' : 'border-l-4 border-blue-500'}`; notification.innerHTML = `<div class="flex items-center space-x-3"><i class="fas fa-${type === 'success' ? 'check-circle text-green-500' : type === 'error' ? 'exclamation-circle text-red-500' : 'info-circle text-blue-500'} text-lg"></i><span class="font-semibold text-sm">${message}</span></div>`; document.body.appendChild(notification); setTimeout(() => notification.remove(), 3000); }

        // ==================== ADD CATEGORY ====================
        function showAddCategoryModal() { document.getElementById('addCategoryModal').classList.remove('hidden'); document.getElementById('newCategoryName').value = ''; }
        function closeAddCategoryModal() { document.getElementById('addCategoryModal').classList.add('hidden'); }
        document.getElementById('addCategoryForm')?.addEventListener('submit', async function(e) { e.preventDefault(); const name = document.getElementById('newCategoryName').value.trim(); if (!name) return showNotification('Please enter category name', 'error'); const submitBtn = this.querySelector('button[type="submit"]'); const originalText = submitBtn.innerHTML; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...'; submitBtn.disabled = true; try { const response = await fetch('add_category.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name }) }); const data = await response.json(); if (data.success) { showNotification('Category added successfully!', 'success'); closeAddCategoryModal(); await refreshCategoryDropdowns(); } else { showNotification('Error: ' + data.message, 'error'); } } catch (err) { showNotification('Network error: ' + err.message, 'error'); } finally { submitBtn.innerHTML = originalText; submitBtn.disabled = false; } });

        // ==================== REFRESH CATEGORY DROPDOWNS ====================
        async function refreshCategoryDropdowns() {
            try { const response = await fetch('get_categories.php'); const data = await response.json(); if (data.success) { const addSelect = document.getElementById('addItemCategorySelect'); if (addSelect) addSelect.innerHTML = '<option value="">Select Category</option>' + data.categories.map(cat => `<option value="${cat.name}">${escapeHtml(cat.name)}</option>`).join(''); const editSelect = document.getElementById('editItemCategorySelect'); if (editSelect) { const currentVal = editSelect.value; editSelect.innerHTML = '<option value="">Select Category</option>' + data.categories.map(cat => `<option value="${cat.name}">${escapeHtml(cat.name)}</option>`).join(''); if (currentVal && data.categories.some(c => c.name === currentVal)) editSelect.value = currentVal; } } } catch (err) { console.error(err); } }

        // ==================== DELETE CATEGORIES ====================
        async function showManageCategoriesModal() {
            const modal = document.getElementById('manageCategoriesModal');
            const container = document.getElementById('categoriesListContainer');
            if (!modal || !container) return;
            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading categories...</div>';
            try { const response = await fetch('get_categories.php'); const data = await response.json(); if (data.success && data.categories.length > 0) { container.innerHTML = data.categories.map(cat => `<div class="flex justify-between items-center border-b border-gray-100 py-3"><span class="font-medium text-gray-800">${escapeHtml(cat.name)}</span><button onclick="deleteCategory(${cat.id}, '${escapeHtml(cat.name).replace(/'/g, "\\'")}')" class="bg-red-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-600 transition"><i class="fas fa-trash mr-1"></i> Delete</button></div>`).join(''); } else { container.innerHTML = '<div class="text-center text-gray-500 py-4">No categories found. Add one first.</div>'; } } catch (err) { container.innerHTML = '<div class="text-center text-red-500 py-4">Failed to load categories</div>'; }
        }
        function closeManageCategoriesModal() { document.getElementById('manageCategoriesModal').classList.add('hidden'); }
        async function deleteCategory(categoryId, categoryName) {
            if (!confirm(`Are you sure you want to delete category "${categoryName}"? This will only work if no menu items use this category.`)) return;
            try { const response = await fetch('delete_category.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: categoryId }) }); const data = await response.json(); if (data.success) { showNotification(`Category "${categoryName}" deleted successfully!`, 'success'); await showManageCategoriesModal(); await refreshCategoryDropdowns(); } else { showNotification('Error: ' + data.message, 'error'); } } catch (err) { showNotification('Network error: ' + err.message, 'error'); }
        }

        function logout() { if (window.confirm('Are you sure you want to logout?')) window.location.href = 'logout.php'; }
        
        function initializeApp() {
            refreshCategoryDropdowns();
            updateInventoryTable();
            updateInventoryStats();
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { closeAddItemModal(); closeEditModal(); cancelDelete(); closeAddCategoryModal(); closeManageCategoriesModal(); } });
        }
        document.addEventListener('DOMContentLoaded', initializeApp);
    </script>
</body>
</html>