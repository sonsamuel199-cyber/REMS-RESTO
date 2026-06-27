<?php
// receipt_settings.php - Admin interface for receipt customization
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_receipt_settings'])) {
    $settings = [
        'receipt_header' => $_POST['receipt_header'] ?? 'REM\'S RESTO',
        'receipt_footer' => $_POST['receipt_footer'] ?? 'Thank you for dining with us!',
        'receipt_tax_reg' => $_POST['receipt_tax_reg'] ?? '',
        'receipt_contact' => $_POST['receipt_contact'] ?? ''
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $mysqli->prepare("INSERT INTO receipt_settings (setting_key, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    $successMessage = "Receipt settings updated successfully!";
}

// Get current settings
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM receipt_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$mysqli->close();

// Set defaults
$defaults = [
    'receipt_header' => 'REM\'S RESTO',
    'receipt_footer' => 'Thank you for dining with us!',
    'receipt_tax_reg' => '',
    'receipt_contact' => ''
];

foreach ($defaults as $key => $default) {
    if (!isset($settings[$key])) {
        $settings[$key] = $default;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Settings - REMS RESTO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 20px 40px rgba(220, 38, 38, 0.08); }
        .header-gradient { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2); }
        @keyframes pop-in { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        .animate-pop-in { animation: pop-in 0.3s ease-out; }
    </style>
</head>
<body class="min-h-screen">
    <header class="header-gradient shadow-2xl">
        <div class="max-w-8xl mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                <div class="flex items-center space-x-3">
                    <div class="bg-white p-2 rounded-2xl shadow-lg w-14 h-14 flex items-center justify-center overflow-hidden">
                        <img src="images/2be0107a-02ff-48e0-92c4-f109ec040290.png" alt="REMS RESTO Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl sm:text-2xl font-bold">Rem's Resto</h1>
                        <p class="text-red-100 text-xs sm:text-sm">Receipt Customization</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="window.location.href='inventory.php'" class="bg-white text-red-600 px-4 py-2 rounded-xl font-semibold hover:bg-red-50 transition shadow-lg">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
                    </button>
                    <button onclick="logout()" class="bg-white text-red-600 px-4 py-2 rounded-xl font-semibold hover:bg-red-50 transition shadow-lg">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4">
        <div class="glass-effect rounded-2xl shadow-xl p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-receipt text-red-600 mr-2"></i>
                Receipt Customization Settings
            </h1>
            
            <?php if (isset($successMessage)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 animate-pop-in">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Receipt Header</label>
                    <input type="text" name="receipt_header" 
                           value="<?php echo htmlspecialchars($settings['receipt_header']); ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-gray-500 mt-1">This appears at the top of every receipt</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Receipt Footer</label>
                    <input type="text" name="receipt_footer" 
                           value="<?php echo htmlspecialchars($settings['receipt_footer']); ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-gray-500 mt-1">This appears at the bottom of every receipt</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Tax Registration Number (TIN)</label>
                    <input type="text" name="receipt_tax_reg" 
                           value="<?php echo htmlspecialchars($settings['receipt_tax_reg']); ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-gray-500 mt-1">Business tax identification number</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Contact Information</label>
                    <input type="text" name="receipt_contact" 
                           value="<?php echo htmlspecialchars($settings['receipt_contact']); ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500">
                    <p class="text-xs text-gray-500 mt-1">Phone number, email, or address</p>
                </div>
                
                <div class="pt-4 border-t border-gray-200">
                    <button type="submit" name="update_receipt_settings" 
                            class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition shadow-lg transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                    <a href="inventory.php" class="ml-3 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Inventory
                    </a>
                </div>
            </form>
            
            <!-- Preview -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-3">Receipt Preview:</h3>
                <div class="bg-gray-50 p-4 rounded-lg font-mono text-sm border border-gray-200 max-w-xs">
                    <div class="text-center border-b border-dashed border-gray-300 pb-2">
                        <strong><?php echo htmlspecialchars($settings['receipt_header']); ?></strong>
                        <?php if (!empty($settings['receipt_tax_reg'])): ?>
                            <br><small><?php echo htmlspecialchars($settings['receipt_tax_reg']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($settings['receipt_contact'])): ?>
                            <br><small><?php echo htmlspecialchars($settings['receipt_contact']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="py-3 text-gray-400 text-center">[Order Items]</div>
                    <div class="text-center border-t border-dashed border-gray-300 pt-2">
                        <?php echo htmlspecialchars($settings['receipt_footer']); ?>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">This is how your receipt will look when printed.</p>
            </div>
        </div>
    </main>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>