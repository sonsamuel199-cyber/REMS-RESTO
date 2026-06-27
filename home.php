<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'cashier') {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    $menuItems = [];
    $taxRate = 0.12;
    $seniorDiscountRate = 0.20;
    $pwdDiscountRate = 0.20;
} else {
    $menuItems = [];
    $result = $mysqli->query("SELECT id, name, category, price, description, stock, min_stock, unit_of_measure FROM inventory WHERE stock > 0 ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['price'] = (float)$row['price'];
            $row['stock'] = (int)$row['stock'];
            $row['min_stock'] = (int)$row['min_stock'];
            $row['unit_of_measure'] = $row['unit_of_measure'] ?? 'pcs';
            $menuItems[] = $row;
        }
    }
    
    $taxRate = 0.12;
    $seniorDiscountRate = 0.20;
    $pwdDiscountRate = 0.20;

    $settingsResult = $mysqli->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('tax_rate', 'senior_discount_rate', 'pwd_discount_rate')");
    if ($settingsResult) {
        while ($row = $settingsResult->fetch_assoc()) {
            if ($row['setting_key'] === 'tax_rate') {
                $taxRate = floatval($row['setting_value']);
            } elseif ($row['setting_key'] === 'senior_discount_rate') {
                $seniorDiscountRate = floatval($row['setting_value']);
            } elseif ($row['setting_key'] === 'pwd_discount_rate') {
                $pwdDiscountRate = floatval($row['setting_value']);
            }
        }
    }
    $mysqli->close();
}

$username = 'Cashier';
$currentDate = date('Y-m-d');
$tableNumber = isset($_GET['table']) ? intval($_GET['table']) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REMS RESTO - POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        :root { --primary-gradient: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); --primary-dark: #b91c1c; --primary-light: #f87171; --secondary-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; overflow-x: hidden; }
        @media (max-width: 768px) { body { padding-bottom: 80px; } }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 20px 40px rgba(220, 38, 38, 0.08); }
        .header-gradient { background: var(--primary-gradient); box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2); }
        .btn-primary { background: var(--primary-gradient); color: white; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 600; }
        .btn-primary:hover { background: var(--secondary-gradient); transform: translateY(-2px); box-shadow: 0 15px 30px rgba(220, 38, 38, 0.3); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .category-btn { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 2px solid #e5e7eb; background: white; white-space: nowrap; }
        .category-btn.active { background: var(--primary-gradient); color: white; border-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(220, 38, 38, 0.2); }
        .menu-item-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #e5e7eb; background: white; position: relative; overflow: hidden; }
        .menu-item-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px rgba(220, 38, 38, 0.15); border-color: var(--primary-light); }
        .menu-item-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); transform: scaleX(0); transition: transform 0.3s ease; }
        .menu-item-card:hover::before { transform: scaleX(1); }
        .cart-item { animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid var(--primary-dark); background: white; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        .stock-badge { position: absolute; top: 12px; right: 12px; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10; }
        .stock-high { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stock-medium { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stock-low { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-top: 2px solid #fee2e2; box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1); z-index: 1000; display: none; }
        @media (max-width: 768px) { .mobile-bottom-nav { display: flex; } .mobile-hide { display: none; } .mobile-full-height { height: calc(100vh - 80px); overflow-y: auto; } }
        @keyframes pop-in { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        .animate-pop-in { animation: pop-in 0.3s ease-out; }
        .receipt-content { width: 80mm; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.3; background: white; color: black; padding: 15px; }
        .loading-spinner { border: 3px solid #f3f3f3; border-top: 3px solid var(--primary-dark); border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media print { body * { visibility: hidden; } #receiptPrint, #receiptPrint * { visibility: visible; } #receiptPrint { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }
        .notification-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideIn 0.3s ease-out; }
        @media (max-width: 640px) { .notification-toast { top: 10px; right: 10px; left: 10px; } }
        .sticky-summary { position: sticky; top: 20px; }
        .modal-overlay { background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="min-h-screen">

    <!-- Header -->
    <header class="header-gradient shadow-2xl no-print">
        <div class="max-w-8xl mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                <div class="flex items-center space-x-3">
                    <div class="bg-white p-2 rounded-2xl shadow-lg transform hover:rotate-12 transition-transform duration-300 w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center overflow-hidden">
                        <img src="images/2be0107a-02ff-48e0-92c4-f109ec040290.png" alt="REMS RESTO Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl sm:text-2xl font-bold">Rem's Resto</h1>
                        <p class="text-red-100 text-xs sm:text-sm">Premium Dining Experience</p>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="text-white text-center sm:text-right">
                        <p class="font-semibold text-sm sm:text-base">Welcome, <?php echo htmlspecialchars($username); ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex items-center space-x-1 bg-white bg-opacity-20 text-white px-3 sm:px-4 py-1 sm:py-2 rounded-full font-medium text-xs sm:text-sm backdrop-blur-sm">
                            <i class="fas fa-table"></i>
                            <span>Table: <span id="tableNumberDisplay"><?php echo $tableNumber; ?></span></span>
                        </div>
                        <button onclick="logout()" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Category Navigation -->
    <div class="lg:hidden bg-white border-b border-gray-200 no-print">
        <div id="mobileCategoryFilters" class="flex overflow-x-auto px-4 py-2 space-x-2"></div>
    </div>

    <!-- Main Content -->
    <main class="max-w-8xl mx-auto p-2 sm:p-4 no-print">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <!-- Menu Section -->
            <div class="lg:col-span-3">
                <div class="glass-effect rounded-2xl sm:rounded-3xl shadow-xl overflow-hidden h-full flex flex-col">
                    <!-- Desktop Header -->
                    <div class="hidden lg:block">
                        <div class="header-gradient p-6 text-white">
                            <div class="flex flex-col sm:flex-row justify-between items-start space-y-4 sm:space-y-0">
                                <div>
                                    <h1 class="text-2xl sm:text-3xl font-bold mb-2">Menu</h1>
                                    <p class="text-red-100 text-lg">Discover our delicious offerings</p>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="bg-white bg-opacity-20 rounded-2xl p-4 backdrop-blur-sm">
                                        <div class="text-center">
                                            <p class="text-sm opacity-90">Items in Cart</p>
                                            <p class="text-2xl font-bold" id="cartCount">0</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="changeTable(-1)" class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center hover:bg-opacity-30 transition-all">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="bg-white bg-opacity-20 rounded-xl px-4 py-2">
                                            <span class="font-bold" id="desktopTableDisplay">Table <?php echo $tableNumber; ?></span>
                                        </div>
                                        <button onclick="changeTable(1)" class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center hover:bg-opacity-30 transition-all">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Categories -->
                        <div class="p-6 border-b border-gray-100">
                            <div id="desktopCategoryFilters" class="flex flex-wrap gap-3"></div>
                        </div>
                    </div>

                    <!-- Menu Grid -->
                    <div class="p-4 sm:p-6 overflow-y-auto flex-grow custom-scrollbar">
                        <div id="menuGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6"></div>
                    </div>
                </div>
            </div>

            <!-- Cart Section -->
            <div class="lg:col-span-1 sticky-summary">
                <div class="glass-effect rounded-2xl sm:rounded-3xl shadow-xl h-full flex flex-col">
                    <div class="header-gradient p-4 sm:p-6 text-white rounded-t-2xl sm:rounded-t-3xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl sm:text-2xl font-bold">Order Summary</h2>
                                <p class="text-red-100 text-sm">Table #<span id="tableNumber"><?php echo $tableNumber; ?></span></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-2 sm:p-3 rounded-xl backdrop-blur-sm">
                                <i class="fas fa-receipt text-lg sm:text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex-grow p-4 sm:p-6 overflow-y-auto custom-scrollbar" style="max-height: 50vh;">
                        <div id="cartItems" class="space-y-3">
                            <div class="text-center text-gray-500 py-8 sm:py-12">
                                <div class="text-4xl sm:text-6xl mb-4 sm:mb-6 opacity-30">
                                    <i class="fas fa-shopping-basket"></i>
                                </div>
                                <p class="text-base sm:text-lg font-medium text-gray-400">Your cart is empty</p>
                                <p class="text-xs sm:text-sm mt-2 text-gray-400">Add items from our menu</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 border-t border-gray-100 space-y-4 sm:space-y-6">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" id="seniorDiscount" class="w-4 h-4 text-green-600" onchange="toggleDiscount('senior')">
                                <label for="seniorDiscount" class="text-sm font-medium text-gray-700">Senior Citizen (<?php echo number_format($seniorDiscountRate * 100, 1); ?>%)</label>
                            </div>
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" id="pwdDiscount" class="w-4 h-4 text-green-600" onchange="toggleDiscount('pwd')">
                                <label for="pwdDiscount" class="text-sm font-medium text-gray-700">PWD (<?php echo number_format($pwdDiscountRate * 100, 1); ?>%)</label>
                            </div>
                        </div>

                        <div class="space-y-2 sm:space-y-3">
                            <div class="flex justify-between items-center text-gray-600">
                                <span class="font-medium text-sm sm:text-base">Subtotal:</span>
                                <span id="subtotal" class="font-semibold">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600">
                                <span class="font-medium text-sm sm:text-base">Discount:</span>
                                <span id="discountAmount" class="font-semibold text-green-600">-₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600">
                                <span class="font-medium text-sm sm:text-base">VAT (<?php echo number_format($taxRate * 100, 1); ?>%):</span>
                                <span id="tax" class="font-semibold">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-lg sm:text-xl font-bold text-gray-800 pt-3 border-t border-gray-200">
                                <span>Total:</span>
                                <span id="total" class="text-red-600">₱0.00</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <button id="checkoutBtn" class="w-full btn-primary text-white py-3 sm:py-4 rounded-xl font-bold text-base sm:text-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg transform hover:scale-105 disabled:hover:scale-100 flex items-center justify-center space-x-2" disabled onclick="showPaymentModal()">
                                <i class="fas fa-credit-card"></i>
                                <span>Process Payment</span>
                            </button>
                            
                            <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                <button class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-2 sm:py-3 rounded-xl font-semibold hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105" onclick="showGcashModal()">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span class="text-sm sm:text-base">GCash</span>
                                </button>
                                <button class="bg-gradient-to-r from-gray-500 to-gray-600 text-white py-2 sm:py-3 rounded-xl font-semibold hover:from-gray-600 hover:to-gray-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105" onclick="clearCart()">
                                    <i class="fas fa-trash"></i>
                                    <span class="text-sm sm:text-base">Clear</span>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 sm:gap-3 mt-2">
                                <button id="saveTicketBtn" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2 sm:py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105">
                                    <i class="fas fa-save"></i>
                                    <span class="text-sm sm:text-base">Save Ticket</span>
                                </button>
                                <button id="loadTicketsBtn" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white py-2 sm:py-3 rounded-xl font-semibold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg flex items-center justify-center space-x-2 transform hover:scale-105">
                                    <i class="fas fa-list"></i>
                                    <span class="text-sm sm:text-base">Load Tickets</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <div class="flex justify-around items-center h-16">
            <button class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors" onclick="window.location.href='home.php'">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs">Home</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors" onclick="showPaymentModal()">
                <div class="relative">
                    <i class="fas fa-shopping-cart text-xl mb-1"></i>
                    <span id="mobileCartCount" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                </div>
                <span class="text-xs">Cart</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors" onclick="showGcashModal()">
                <i class="fas fa-qrcode text-xl mb-1"></i>
                <span class="text-xs">GCash</span>
            </button>
            <button class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors" onclick="logout()">
                <i class="fas fa-sign-out-alt text-xl mb-1"></i>
                <span class="text-xs">Logout</span>
            </button>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 no-print">
        <div class="glass-effect rounded-2xl sm:rounded-3xl p-6 sm:p-8 max-w-md w-full mx-4 text-center transform animate-pop-in">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4 rounded-2xl inline-flex mb-6">
                <i class="fas fa-cash text-white text-3xl sm:text-4xl"></i>
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4">Cash Payment</h3>
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg mb-6 border-2 border-green-200">
                <div class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Total Amount:</div>
                <div class="text-3xl sm:text-4xl font-bold text-green-600 mb-4" id="paymentTotal">₱0.00</div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-left text-sm sm:text-base">Amount Received:</label>
                        <input type="number" id="amountReceived" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-xl sm:text-2xl font-bold text-center focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="0.00" oninput="calculateChange()">
                    </div>
                    <div class="bg-gray-50 p-3 sm:p-4 rounded-xl">
                        <div class="text-base sm:text-lg font-semibold text-gray-700 mb-1">Change:</div>
                        <div class="text-2xl sm:text-3xl font-bold text-blue-600" id="changeAmount">₱0.00</div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                <button class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all duration-300 shadow-lg transform hover:scale-105" onclick="processCashPayment()">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
                <button class="flex-1 bg-gray-300 text-gray-700 px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-300" onclick="closePaymentModal()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- GCash Modal -->
    <div id="gcashModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 no-print">
        <div class="glass-effect rounded-2xl sm:rounded-3xl p-6 sm:p-8 max-w-md w-full mx-4 text-center transform animate-pop-in">
            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 p-4 rounded-2xl inline-flex mb-6">
                <i class="fas fa-qrcode text-white text-3xl sm:text-4xl"></i>
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4">GCash Payment</h3>
            <p class="mb-6 text-gray-600 text-base sm:text-lg">Scan the QR code to pay</p>
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg mb-6 inline-block border-2 border-cyan-200">
                <div class="w-40 sm:w-48 h-40 sm:h-48 bg-gray-100 flex items-center justify-center mx-auto">
                    <i class="fas fa-qrcode text-4xl sm:text-6xl text-gray-400"></i>
                </div>
            </div>
            <p class="mb-6 text-gray-700 text-lg sm:text-xl">Amount: <span id="gcashAmount" class="font-bold text-red-600 text-2xl sm:text-3xl">₱0.00</span></p>
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                <button class="flex-1 bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 shadow-lg transform hover:scale-105" onclick="processGcashPayment()">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
                <button class="flex-1 bg-gray-300 text-gray-700 px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold hover:bg-gray-400 transition-all duration-300" onclick="closeGcashModal()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- Save Ticket Modal -->
    <div id="saveTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-2xl p-6 max-w-md w-full mx-4 transform animate-pop-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Save Ticket</h3>
                <button class="close-save-modal text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1 text-sm">Customer Name (Optional)</label>
                    <input type="text" id="customerName" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" placeholder="e.g., John Doe">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1 text-sm">Table Number <span class="text-red-500">*</span></label>
                    <input type="number" id="saveTableNumber" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" placeholder="Enter table number" required>
                </div>
            </div>
            <div class="flex space-x-3 mt-6">
                <button id="confirmSaveTicket" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">Save</button>
                <button class="close-save-modal flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Tickets List Modal -->
    <div id="ticketsListModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto transform animate-pop-in">
            <div class="flex justify-between items-center mb-4 sticky top-0 bg-white bg-opacity-95 pb-2">
                <h3 class="text-xl font-bold text-gray-800">Saved Tickets</h3>
                <button class="close-tickets-modal text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="ticketsListContainer" class="space-y-3"></div>
            <button class="close-tickets-modal w-full mt-4 bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">Close</button>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Cancel Order</h3>
            <p class="text-gray-600 mb-4">Please provide a reason for cancellation:</p>
            <textarea id="cancelReason" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500" placeholder="e.g., Customer changed mind, Out of stock, etc."></textarea>
            
            <!-- ============================================ -->
            <!-- FIX 005: WASTE MANAGEMENT TOGGLE IN MODAL -->
            <!-- ============================================ -->
            <div class="mt-4">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">Refund Type:</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="refund_type" value="restock" checked class="mr-2 text-green-600">
                        <span class="text-sm">Return to Stock</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="refund_type" value="waste" class="mr-2 text-red-600">
                        <span class="text-sm">Mark as Waste</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Choose "Mark as Waste" if ingredients are spoiled or unusable.</p>
            </div>
            
            <input type="hidden" id="cancelOrderId">
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeCancelModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
                <button onclick="confirmCancelOrder()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Confirm Cancellation</button>
            </div>
        </div>
    </div>

    <div id="receiptPrint" class="hidden"></div>

    <script>
        // Set the menu items from PHP
        const menuItems = <?php echo json_encode($menuItems); ?>;
        
        // POS State Variables
        let cart = [];
        let currentCategory = 'all';
        const TAX_RATE = <?php echo $taxRate; ?>;
        const SENIOR_DISCOUNT_RATE = <?php echo $seniorDiscountRate; ?>;
        const PWD_DISCOUNT_RATE = <?php echo $pwdDiscountRate; ?>;
        
        let discount = { senior: false, pwd: false };
        let currentTableNumber = <?php echo $tableNumber; ?>;
        let currentLoadedTicketId = null;

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // ==================== CATEGORY LOADING ====================
        let categories = [];

        async function loadCategories() {
            try {
                const response = await fetch('get_categories.php');
                const data = await response.json();
                if (data.success) {
                    categories = data.categories;
                    renderCategoryFilters();
                } else {
                    console.error('Failed to load categories');
                }
            } catch (err) {
                console.error('Error loading categories:', err);
            }
        }

        function renderCategoryFilters() {
            const desktopContainer = document.getElementById('desktopCategoryFilters');
            const mobileContainer = document.getElementById('mobileCategoryFilters');
            
            if (!desktopContainer && !mobileContainer) return;
            
            let buttonsHtml = `<button class="category-btn active px-5 py-3 rounded-xl text-gray-700 font-semibold transition-all duration-300" data-category="all" onclick="filterCategory('all')">
                                    <i class="fas fa-th-large mr-2"></i>All Items
                                </button>`;
            categories.forEach(cat => {
                buttonsHtml += `<button class="category-btn px-5 py-3 rounded-xl text-gray-700 font-semibold transition-all duration-300" data-category="${cat.name}" onclick="filterCategory('${cat.name}')">
                                    <i class="fas fa-tag mr-2"></i>${escapeHtml(cat.name)}
                                </button>`;
            });
            
            if (desktopContainer) desktopContainer.innerHTML = buttonsHtml;
            if (mobileContainer) mobileContainer.innerHTML = buttonsHtml.replace(/px-5 py-3/g, 'px-4 py-2 text-sm');
            
            document.querySelectorAll('.category-btn').forEach(btn => {
                if (btn.getAttribute('data-category') === currentCategory) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // ============================================
        // FIX 001: DISPLAY UNIT OF MEASUREMENT
        // ============================================
        function getStockStatus(item) {
            if (item.stock === 0) return 'out';
            if (item.stock <= item.min_stock) return 'low';
            if (item.stock <= item.min_stock * 2) return 'medium'; 
            return 'high';
        }

        function getStockClass(status) {
            switch (status) {
                case 'out': return 'stock-out';
                case 'low': return 'stock-low';
                case 'medium': return 'stock-medium';
                default: return 'stock-high';
            }
        }

        function displayMenuItems(items) {
            const menuGrid = document.getElementById('menuGrid');
            if (!menuGrid) return;
            menuGrid.innerHTML = '';
            
            if (items.length === 0) {
                menuGrid.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="text-6xl mb-4 opacity-30 text-red-300">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <p class="text-xl font-medium text-gray-400">No items available</p>
                        <p class="text-sm mt-2 text-gray-400">Please check inventory</p>
                    </div>`;
                return;
            }
            
            items.forEach(item => {
                const status = getStockStatus(item);
                const statusClass = getStockClass(status);
                const isDisabled = item.stock === 0;
                const unit = item.unit_of_measure || 'pcs';

                const div = document.createElement('div');
                div.className = `menu-item-card rounded-xl p-4 sm:p-6 relative flex flex-col h-full ${isDisabled ? 'opacity-60' : 'cursor-pointer'}`;
                div.setAttribute('data-category', item.category);
                div.innerHTML = `
                    <div class="stock-badge ${statusClass}">
                        <i class="fas fa-box mr-1"></i>${item.stock} ${unit}
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">${escapeHtml(item.name)}</h3>
                        <p class="text-xs sm:text-sm text-red-600 font-semibold mb-3 bg-red-50 px-2 sm:px-3 py-1 rounded-full inline-block">
                            ${item.category.charAt(0).toUpperCase() + item.category.slice(1)}
                        </p>
                        <p class="price-tag text-white font-bold text-xl sm:text-2xl mb-3 px-3 sm:px-4 py-1 sm:py-2 rounded-lg bg-gradient-to-r from-red-500 to-red-600 inline-block">₱${item.price.toFixed(2)}</p>
                        <p class="text-gray-600 text-xs sm:text-sm mb-4 leading-relaxed">${escapeHtml(item.description)}</p>
                    </div>
                    <button 
                        class="w-full btn-primary text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold mt-auto transition-all duration-300 ${
                            isDisabled 
                                ? 'opacity-50 cursor-not-allowed bg-gray-400 hover:bg-gray-400 hover:transform-none' 
                                : 'hover:shadow-lg transform hover:scale-105'
                        }"
                        ${isDisabled ? 'disabled' : `onclick="addToCart(${item.id})"`}
                    >
                        ${isDisabled ? 
                            '<i class="fas fa-times mr-2"></i>Out of Stock' : 
                            '<i class="fas fa-plus mr-2"></i>Add to Order'
                        }
                    </button>
                `;
                menuGrid.appendChild(div);
            });
        }

        function filterCategory(category) {
            currentCategory = category;
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.querySelector(`.category-btn[data-category="${category}"]`);
            if (activeBtn) activeBtn.classList.add('active');

            const filteredItems = category === 'all' ? menuItems : menuItems.filter(item => item.category === category);
            displayMenuItems(filteredItems);
        }

        // ==================== CART FUNCTIONS ====================
        function addToCart(itemId) {
            const item = menuItems.find(i => i.id === itemId);
            if (!item) return;
            if (item.stock === 0) {
                showNotification('This item is out of stock!', 'error');
                return;
            }
            
            const existingItem = cart.find(i => i.id === itemId);
            
            if (existingItem) {
                if (existingItem.quantity >= item.stock) {
                    showNotification('Not enough stock available!', 'error');
                    return;
                }
                existingItem.quantity += 1;
            } else {
                const fullItem = menuItems.find(i => i.id === itemId);
                cart.push({ ...fullItem, quantity: 1 });
            }
            updateCartDisplay();
            showNotification(`Added ${item.name} to cart! 🍽️`, 'success');
        }

        function updateQuantity(itemId, change) {
            const cartItem = cart.find(i => i.id === itemId);
            const menuItem = menuItems.find(i => i.id === itemId);
            if (!cartItem || !menuItem) return;

            const newQuantity = cartItem.quantity + change;

            if (newQuantity <= 0) {
                removeFromCart(itemId);
            } else if (newQuantity > menuItem.stock) {
                showNotification('Not enough stock available!', 'error');
            } else {
                cartItem.quantity = newQuantity;
                updateCartDisplay();
            }
        }

        function removeFromCart(itemId) {
            const item = cart.find(i => i.id === itemId);
            cart = cart.filter(item => item.id !== itemId);
            updateCartDisplay();
            if (item) showNotification(`Removed ${item.name} from cart`, 'info');
        }

        function updateCartDisplay() {
            const cartItemsDiv = document.getElementById('cartItems');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const cartCount = document.getElementById('cartCount');
            const mobileCartCount = document.getElementById('mobileCartCount');
            
            if (!cartItemsDiv) return;
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = `
                    <div class="text-center text-gray-500 py-8 sm:py-12">
                        <div class="text-4xl sm:text-6xl mb-4 sm:mb-6 opacity-30">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <p class="text-base sm:text-lg font-medium text-gray-400">Your cart is empty</p>
                        <p class="text-xs sm:text-sm mt-2 text-gray-400">Add items from our menu</p>
                    </div>`;
                if (checkoutBtn) checkoutBtn.disabled = true;
                if (cartCount) cartCount.textContent = '0';
                if (mobileCartCount) mobileCartCount.textContent = '0';
            } else {
                cartItemsDiv.innerHTML = cart.map(item => `
                    <div class="cart-item bg-white rounded-lg p-3 sm:p-4 border-l-4 border-red-500 hover:shadow-md transition-all duration-300">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-semibold text-gray-800 text-sm sm:text-base truncate">${escapeHtml(item.name)}</h4>
                            <button class="text-red-500 hover:text-red-700 transition-colors text-sm" onclick="removeFromCart(${item.id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <button class="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors text-gray-600" onclick="updateQuantity(${item.id}, -1)">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-bold text-gray-800 text-sm sm:text-base">${item.quantity}</span>
                                <button class="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors text-gray-600" onclick="updateQuantity(${item.id}, 1)">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-bold text-red-600 text-sm sm:text-base">₱${(item.price * item.quantity).toFixed(2)}</span>
                        </div>
                    </div>`).join('');
                if (checkoutBtn) checkoutBtn.disabled = false;
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                if (cartCount) cartCount.textContent = totalItems;
                if (mobileCartCount) mobileCartCount.textContent = totalItems;
            }
            updateTotals();
        }

        // ============================================
        // FIX: TAX CALCULATION (VAT IS ALREADY INCLUDED IN PRICE)
        // ============================================
        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            let discountAmount = 0;
            if (discount.senior) {
                discountAmount += subtotal * SENIOR_DISCOUNT_RATE;
            }
            if (discount.pwd) {
                discountAmount += subtotal * PWD_DISCOUNT_RATE;
            }
            
            const afterDiscount = subtotal - discountAmount;
            // VAT is already included in the price, so we compute tax as the VAT portion
            const tax = subtotal * TAX_RATE; // Fixed: VAT portion of the price
            const total = afterDiscount;
            
            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('discountAmount').textContent = `-₱${discountAmount.toFixed(2)}`;
            document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
            
            document.getElementById('paymentTotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('gcashAmount').textContent = `₱${total.toFixed(2)}`;
        }

        function toggleDiscount(type) {
            if (type === 'senior') {
                discount.senior = document.getElementById('seniorDiscount').checked;
                if (discount.senior) {
                    discount.pwd = false;
                    document.getElementById('pwdDiscount').checked = false;
                }
            } else if (type === 'pwd') {
                discount.pwd = document.getElementById('pwdDiscount').checked;
                if (discount.pwd) {
                    discount.senior = false;
                    document.getElementById('seniorDiscount').checked = false;
                }
            }
            updateTotals();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                discount.senior = false;
                discount.pwd = false;
                document.getElementById('seniorDiscount').checked = false;
                document.getElementById('pwdDiscount').checked = false;
                updateCartDisplay();
                currentLoadedTicketId = null;
                showNotification('Cart cleared', 'info');
            }
        }

        // ==================== TABLE FUNCTIONS ====================
        function changeTable(direction) {
            currentTableNumber += direction;
            if (currentTableNumber < 1) currentTableNumber = 1;
            if (currentTableNumber > 20) currentTableNumber = 20;
            
            document.getElementById('tableNumber').textContent = currentTableNumber;
            document.getElementById('tableNumberDisplay').textContent = currentTableNumber;
            document.getElementById('desktopTableDisplay').textContent = "Table " + currentTableNumber;
            
            const url = new URL(window.location.href);
            url.searchParams.set('table', currentTableNumber);
            window.history.replaceState({}, '', url);
            
            showNotification(`Switched to Table ${currentTableNumber}`, 'info');
        }

        // ==================== PAYMENT FUNCTIONS ====================
        function showPaymentModal() { if (cart.length === 0) return; document.getElementById('paymentModal').classList.remove('hidden'); document.getElementById('amountReceived').value = ''; document.getElementById('changeAmount').textContent = '₱0.00'; document.getElementById('amountReceived').focus(); }
        function closePaymentModal() { document.getElementById('paymentModal').classList.add('hidden'); }
        function calculateChange() { const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0; const total = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0; const change = amountReceived - total; document.getElementById('changeAmount').textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`; document.getElementById('changeAmount').className = `text-2xl sm:text-3xl font-bold ${change >= 0 ? 'text-blue-600' : 'text-red-600'}`; }
        function showGcashModal() { if (cart.length === 0) return; document.getElementById('gcashModal').classList.remove('hidden'); }
        function closeGcashModal() { document.getElementById('gcashModal').classList.add('hidden'); }
        function processCashPayment() { const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0; const total = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0; if (amountReceived < total) { showNotification('Insufficient payment amount!', 'error'); return; } processOrder('cash', amountReceived); }
        function processGcashPayment() { processOrder('gcash', 0); }

        function processOrder(paymentMethod, amountReceived = 0) {
            if (cart.length === 0) { showNotification('Cart is empty!', 'error'); return; }

            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = discount.senior ? subtotal * SENIOR_DISCOUNT_RATE : discount.pwd ? subtotal * PWD_DISCOUNT_RATE : 0;
            const afterDiscount = subtotal - discountAmount;
            const tax = subtotal * TAX_RATE; // Fixed: VAT portion
            const total = afterDiscount;

            const orderData = {
                items: cart.map(i => ({ id: i.id, name: i.name, price: i.price, quantity: i.quantity })),
                totals: { subtotal, discount: discountAmount, tax, total },
                payment: { method: paymentMethod, amount_received: amountReceived, change: amountReceived - total },
                discount: { senior: discount.senior, pwd: discount.pwd },
                table_number: currentTableNumber,
                cashier_name: '<?php echo htmlspecialchars($username); ?>',
                tax_rate: TAX_RATE,
                saved_ticket_id: currentLoadedTicketId || null
            };
            
            showNotification('Processing order...', 'info');
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) checkoutBtn.disabled = true;
            
            fetch('process_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: orderData })
            })
            .then(response => response.json())
            .then(data => {
                if (checkoutBtn) checkoutBtn.disabled = false;
                if (data.success) {
                    showNotification(`Order #${data.order_id} processed successfully!`, 'success');
                    generateReceipt(data.order_id, orderData);
                    
                    orderData.items.forEach(cartItem => {
                        const menuItem = menuItems.find(i => i.id === cartItem.id);
                        if (menuItem) {
                            menuItem.stock -= cartItem.quantity;
                            if (menuItem.stock < 0) menuItem.stock = 0;
                        }
                    });
                    
                    cart = [];
                    discount.senior = false;
                    discount.pwd = false;
                    document.getElementById('seniorDiscount').checked = false;
                    document.getElementById('pwdDiscount').checked = false;
                    updateCartDisplay();
                    filterCategory(currentCategory);
                    currentLoadedTicketId = null;
                    closePaymentModal();
                    closeGcashModal();
                } else {
                    showNotification('Order failed: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                if (checkoutBtn) checkoutBtn.disabled = false;
                showNotification('Payment error: ' + error.message, 'error');
            });
        }

        // ============================================
        // FIX 006: GENERATE RECEIPT WITH CUSTOM SETTINGS
        // ============================================
        function generateReceipt(orderId, orderData) {
            const receiptDiv = document.getElementById('receiptPrint');
            
            // Fetch receipt settings from PHP via AJAX
            fetch('get_receipt_settings.php')
                .then(response => response.json())
                .then(settings => {
                    const header = settings.receipt_header || 'REM\'S RESTO';
                    const footer = settings.receipt_footer || 'Thank you for dining with us!';
                    const taxReg = settings.receipt_tax_reg || '';
                    const contact = settings.receipt_contact || '';
                    const status = orderData.status || 'completed';
                    
                    receiptDiv.innerHTML = `
                        <div class="receipt-content">
                            <div style="text-align:center;border-bottom:1px dashed #ccc;padding-bottom:10px;">
                                <div style="font-size:16px;font-weight:bold;">${escapeHtml(header)}</div>
                                ${taxReg ? `<div style="font-size:10px;">${escapeHtml(taxReg)}</div>` : ''}
                                ${contact ? `<div style="font-size:10px;">${escapeHtml(contact)}</div>` : ''}
                                <div style="font-size:10px;margin-top:5px;">${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
                                <div style="font-size:10px;">Order #${String(orderId).padStart(6, '0')}</div>
                                <div style="font-size:10px;">Table: ${orderData.table_number}</div>
                            </div>
                            <div style="padding:10px 0;border-bottom:1px dashed #ccc;">
                                ${orderData.items.map(item => `
                                    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                                        <span>${escapeHtml(item.name)} x ${item.quantity}</span>
                                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div style="padding:10px 0;">
                                <div style="display:flex;justify-content:space-between;font-size:12px;">
                                    <span>Subtotal</span>
                                    <span>₱${orderData.totals.subtotal.toFixed(2)}</span>
                                </div>
                                ${orderData.totals.discount > 0 ? `
                                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#666;">
                                        <span>Discount</span>
                                        <span>-₱${orderData.totals.discount.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                <div style="display:flex;justify-content:space-between;font-size:12px;">
                                    <span>Tax (${(orderData.tax_rate * 100).toFixed(1)}%)</span>
                                    <span>₱${orderData.totals.tax.toFixed(2)}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold;border-top:1px dashed #ccc;padding-top:5px;margin-top:5px;">
                                    <span>TOTAL</span>
                                    <span>₱${orderData.totals.total.toFixed(2)}</span>
                                </div>
                                ${orderData.payment.method === 'cash' ? `
                                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#666;">
                                        <span>Amount Received</span>
                                        <span>₱${orderData.payment.amount_received.toFixed(2)}</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#666;">
                                        <span>Change</span>
                                        <span>₱${orderData.payment.change.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                <div style="font-size:10px;color:#666;margin-top:5px;">Payment: ${orderData.payment.method.toUpperCase()}</div>
                            </div>
                            <div style="text-align:center;font-size:10px;border-top:1px dashed #ccc;padding-top:10px;">
                                ${escapeHtml(footer)}
                                ${status === 'cancelled' ? '<br><strong>** CANCELLED **</strong>' : ''}
                            </div>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        receiptDiv.classList.remove('hidden');
                        window.print();
                        setTimeout(() => receiptDiv.classList.add('hidden'), 500);
                    }, 500);
                })
                .catch(() => {
                    // Fallback receipt if settings can't be loaded
                    receiptDiv.innerHTML = `
                        <div class="receipt-content">
                            <div style="text-align:center;border-bottom:1px dashed #ccc;padding-bottom:10px;">
                                <div style="font-size:16px;font-weight:bold;">REM'S RESTO</div>
                                <div style="font-size:10px;">Order #${String(orderId).padStart(6, '0')}</div>
                            </div>
                            <div style="padding:10px 0;">
                                ${orderData.items.map(item => `
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span>${escapeHtml(item.name)} x ${item.quantity}</span>
                                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                                    </div>
                                `).join('')}
                                <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold;border-top:1px dashed #ccc;padding-top:5px;margin-top:5px;">
                                    <span>TOTAL</span>
                                    <span>₱${orderData.totals.total.toFixed(2)}</span>
                                </div>
                            </div>
                            <div style="text-align:center;font-size:10px;border-top:1px dashed #ccc;padding-top:10px;">
                                Thank you for dining with us!
                            </div>
                        </div>
                    `;
                    setTimeout(() => {
                        receiptDiv.classList.remove('hidden');
                        window.print();
                        setTimeout(() => receiptDiv.classList.add('hidden'), 500);
                    }, 500);
                });
        }

        // ============================================
        // FIX 002 + 005: UPDATED CANCEL ORDER FUNCTIONS
        // ============================================
        function showCancelModal(orderId) {
            document.getElementById('cancelOrderId').value = orderId;
            document.getElementById('cancelReason').value = '';
            // Reset refund type to default
            document.querySelector('input[name="refund_type"][value="restock"]').checked = true;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        async function confirmCancelOrder() {
            const orderId = document.getElementById('cancelOrderId').value;
            const reason = document.getElementById('cancelReason').value.trim();
            const refundType = document.querySelector('input[name="refund_type"]:checked')?.value || 'restock';
            
            if (!reason) {
                showNotification('Please enter a cancellation reason', 'error');
                return;
            }
            
            const confirmBtn = document.querySelector('#cancelModal button:last-child');
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            confirmBtn.disabled = true;
            
            try {
                // Check if order is from previous business day
                const checkResponse = await fetch('check_order_date.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });
                const checkData = await checkResponse.json();
                
                let requiresOverride = false;
                if (checkData.requires_override) {
                    const overrideConfirm = confirm(
                        'This order is from a previous business day.\n' +
                        'Admin override required. Continue?'
                    );
                    if (!overrideConfirm) {
                        closeCancelModal();
                        showNotification('Cancellation cancelled', 'info');
                        return;
                    }
                    requiresOverride = true;
                }
                
                // Proceed with cancellation
                const response = await fetch('cancel_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        order_id: orderId, 
                        reason: reason,
                        refund_type: refundType,
                        admin_override: requiresOverride
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeCancelModal();
                    location.reload();
                } else if (data.requires_override) {
                    // Retry with override
                    const retryResponse = await fetch('cancel_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            order_id: orderId, 
                            reason: reason,
                            refund_type: refundType,
                            admin_override: true
                        })
                    });
                    const retryData = await retryResponse.json();
                    if (retryData.success) {
                        showNotification(retryData.message, 'success');
                        closeCancelModal();
                        location.reload();
                    } else {
                        showNotification('Error: ' + retryData.message, 'error');
                    }
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            } catch (err) {
                showNotification('Network error: ' + err.message, 'error');
            } finally {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }
        }

        // ==================== NOTIFICATIONS ====================
        function showNotification(message, type = 'info') {
            document.querySelectorAll('.notification-toast').forEach(n => n.remove());
            const notification = document.createElement('div');
            notification.className = `notification-toast glass-effect p-4 rounded-xl shadow-lg border-l-4 ${type === 'success' ? 'border-green-500' : type === 'error' ? 'border-red-500' : 'border-blue-500'}`;
            notification.innerHTML = `<div class="flex items-center space-x-3"><i class="fas fa-${type === 'success' ? 'check-circle text-green-500' : type === 'error' ? 'exclamation-circle text-red-500' : 'info-circle text-blue-500'}"></i><span class="font-medium text-sm">${escapeHtml(message)}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        function logout() { if (confirm('Are you sure you want to logout?')) window.location.href = 'logout.php'; }
        
        // ==================== SAVE / LOAD TICKETS ====================
        const saveTicketModal = document.getElementById('saveTicketModal');
        const ticketsListModal = document.getElementById('ticketsListModal');
        const customerNameInput = document.getElementById('customerName');
        const saveTableNumberInput = document.getElementById('saveTableNumber');
        const confirmSaveBtn = document.getElementById('confirmSaveTicket');
        const loadTicketsBtn = document.getElementById('loadTicketsBtn');
        const saveTicketBtn = document.getElementById('saveTicketBtn');
        
        function closeAllModals() { if (saveTicketModal) saveTicketModal.classList.add('hidden'); if (ticketsListModal) ticketsListModal.classList.add('hidden'); }
        document.querySelectorAll('.close-save-modal, .close-tickets-modal').forEach(btn => btn.addEventListener('click', closeAllModals));
        
        if (saveTicketBtn) saveTicketBtn.addEventListener('click', () => { if (cart.length === 0) { showNotification('Cart is empty', 'error'); return; } if (saveTableNumberInput) saveTableNumberInput.value = currentTableNumber; if (customerNameInput) customerNameInput.value = ''; if (saveTicketModal) saveTicketModal.classList.remove('hidden'); });
        
        if (confirmSaveBtn) confirmSaveBtn.addEventListener('click', () => {
            const tableNumber = parseInt(saveTableNumberInput.value);
            const customerName = customerNameInput.value.trim();
            if (isNaN(tableNumber) || tableNumber <= 0) { showNotification('Valid table number required', 'error'); return; }
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            let discountAmount = 0;
            if (discount.senior) discountAmount += subtotal * SENIOR_DISCOUNT_RATE;
            if (discount.pwd) discountAmount += subtotal * PWD_DISCOUNT_RATE;
            const afterDiscount = subtotal - discountAmount;
            const tax = subtotal * TAX_RATE; // Fixed
            const total = afterDiscount;
            const saveData = { customer_name: customerName, table_number: tableNumber, items: cart.map(item => ({ id: item.id, name: item.name, price: item.price, quantity: item.quantity })), discount: { senior: discount.senior, pwd: discount.pwd }, totals: { subtotal, discount: discountAmount, tax, total } };
            confirmSaveBtn.disabled = true;
            confirmSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            fetch('save_ticket.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(saveData) })
                .then(res => res.json())
                .then(data => { if (data.success) { showNotification(`Ticket #${data.ticket_id} saved!`, 'success'); cart = []; discount.senior = false; discount.pwd = false; document.getElementById('seniorDiscount').checked = false; document.getElementById('pwdDiscount').checked = false; updateCartDisplay(); closeAllModals(); currentLoadedTicketId = null; } else { showNotification('Error: ' + (data.message || 'Unknown'), 'error'); } })
                .catch(err => showNotification('Network error', 'error'))
                .finally(() => { confirmSaveBtn.disabled = false; confirmSaveBtn.innerHTML = 'Save'; });
        });
        
        if (loadTicketsBtn) loadTicketsBtn.addEventListener('click', () => {
            const container = document.getElementById('ticketsListContainer');
            if (container) container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading tickets...</div>';
            if (ticketsListModal) ticketsListModal.classList.remove('hidden');
            fetch('get_save_ticket.php')
                .then(response => response.json())
                .then(data => {
                    if (!container) return;
                    if (data.error) { container.innerHTML = `<div class="text-center text-red-500 py-4">${escapeHtml(data.error)}</div>`; return; }
                    const tickets = Array.isArray(data) ? data : [];
                    if (tickets.length === 0) container.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-inbox text-4xl mb-2 opacity-50"></i><p>No saved tickets</p></div>';
                    else container.innerHTML = tickets.map(ticket => `<div class="border border-gray-200 rounded-xl p-4 hover:shadow-md"><div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3"><div><p class="font-bold text-gray-800">Ticket #${ticket.id}</p><p class="text-sm text-gray-600">Table ${ticket.table_number} • ${escapeHtml(ticket.customer_name) || 'Guest'}</p><p class="text-xs text-gray-500">${new Date(ticket.saved_at).toLocaleString()}</p></div><div class="text-right"><p class="text-xl font-bold text-green-600">₱${parseFloat(ticket.total_amount).toFixed(2)}</p><button class="load-ticket-btn mt-2 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700" data-id="${ticket.id}"><i class="fas fa-download mr-1"></i>Load</button></div></div></div>`).join('');
                    document.querySelectorAll('.load-ticket-btn').forEach(btn => btn.addEventListener('click', (e) => loadTicketById(btn.getAttribute('data-id'))));
                })
                .catch(err => { if (container) container.innerHTML = `<div class="text-center text-red-500 py-4">Failed to load tickets</div>`; });
        });
        
        function loadTicketById(ticketId) {
            showNotification('Loading ticket...', 'info');
            fetch(`load_ticket.php?id=${ticketId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        cart = data.items;
                        discount.senior = data.discount_senior;
                        discount.pwd = data.discount_pwd;
                        document.getElementById('seniorDiscount').checked = discount.senior;
                        document.getElementById('pwdDiscount').checked = discount.pwd;
                        updateCartDisplay();
                        if (data.table_number) {
                            currentTableNumber = data.table_number;
                            document.getElementById('tableNumber').textContent = currentTableNumber;
                            document.getElementById('tableNumberDisplay').textContent = currentTableNumber;
                            document.getElementById('desktopTableDisplay').textContent = "Table " + currentTableNumber;
                            const url = new URL(window.location.href);
                            url.searchParams.set('table', currentTableNumber);
                            window.history.replaceState({}, '', url);
                        }
                        currentLoadedTicketId = ticketId;
                        showNotification('Ticket loaded!', 'success');
                        closeAllModals();
                    } else showNotification('Ticket not found', 'error');
                })
                .catch(err => showNotification('Error loading ticket', 'error'));
        }

        // ==================== INITIALIZATION ====================
        async function initializeApp() {
            await loadCategories();
            displayMenuItems(menuItems);
            updateCartDisplay();
            if (window.innerWidth <= 768) document.body.classList.add('mobile-full-height');
            window.addEventListener('resize', function() { if (window.innerWidth <= 768) document.body.classList.add('mobile-full-height'); else document.body.classList.remove('mobile-full-height'); });
            console.log('POS System with dynamic categories initialized');
        }

        document.addEventListener('DOMContentLoaded', initializeApp);
    </script>
</body>
</html>