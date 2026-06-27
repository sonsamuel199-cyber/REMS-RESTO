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

$successMessage = "";
$errorMessage = "";

// Handle PIN update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pin'])) {
    $role = $_POST['role'];
    $currentPin = $_POST['current_pin'];
    $newPin = $_POST['new_pin'];
    $confirmPin = $_POST['confirm_pin'];

    // Validate inputs
    if (empty($currentPin) || empty($newPin) || empty($confirmPin)) {
        $errorMessage = "All fields are required!";
    } elseif ($newPin !== $confirmPin) {
        $errorMessage = "New PIN and confirmation PIN do not match!";
    } elseif (strlen($newPin) !== 4 || !is_numeric($newPin)) {
        $errorMessage = "PIN must be exactly 4 digits!";
    } else {
        // Check if current PIN is correct
        $stmt = $mysqli->prepare("SELECT pin FROM user_pins WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['pin'] === $currentPin) {
                // Update PIN
                $updateStmt = $mysqli->prepare("UPDATE user_pins SET pin = ? WHERE role = ?");
                $updateStmt->bind_param("ss", $newPin, $role);
                
                if ($updateStmt->execute()) {
                    $successMessage = "PIN updated successfully!";
                } else {
                    $errorMessage = "Failed to update PIN: " . $mysqli->error;
                }
                $updateStmt->close();
            } else {
                $errorMessage = "Current PIN is incorrect!";
            }
        } else {
            $errorMessage = "Role not found!";
        }
        $stmt->close();
    }
}

// Handle Tax and Discount update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rates'])) {
    $taxRate = floatval($_POST['tax_rate']) / 100;
    $seniorDiscountRate = floatval($_POST['senior_discount_rate']) / 100;
    $pwdDiscountRate = floatval($_POST['pwd_discount_rate']) / 100;

    // Validate rates
    if ($taxRate < 0 || $taxRate > 1) {
        $errorMessage = "Tax rate must be between 0% and 100%!";
    } elseif ($seniorDiscountRate < 0 || $seniorDiscountRate > 1) {
        $errorMessage = "Senior discount rate must be between 0% and 100%!";
    } elseif ($pwdDiscountRate < 0 || $pwdDiscountRate > 1) {
        $errorMessage = "PWD discount rate must be between 0% and 100%!";
    } else {
        // Update tax rate
        $stmt = $mysqli->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('tax_rate', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $taxRateStr = strval($taxRate);
        $stmt->bind_param("ss", $taxRateStr, $taxRateStr);
        $stmt->execute();
        $stmt->close();

        // Update senior discount rate
        $stmt = $mysqli->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('senior_discount_rate', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $seniorRateStr = strval($seniorDiscountRate);
        $stmt->bind_param("ss", $seniorRateStr, $seniorRateStr);
        $stmt->execute();
        $stmt->close();

        // Update PWD discount rate
        $stmt = $mysqli->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('pwd_discount_rate', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $pwdRateStr = strval($pwdDiscountRate);
        $stmt->bind_param("ss", $pwdRateStr, $pwdRateStr);
        $stmt->execute();
        $stmt->close();

        $successMessage = "Tax and discount rates updated successfully!";
    }
}

// Get current PINs for display
$menuPin = "";
$inventoryPin = "";

$stmt = $mysqli->prepare("SELECT role, pin FROM user_pins WHERE role IN ('menu', 'inventory')");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['role'] === 'menu') {
        $menuPin = $row['pin'];
    } elseif ($row['role'] === 'inventory') {
        $inventoryPin = $row['pin'];
    }
}
$stmt->close();

// Get current tax and discount rates
$taxRate = 0.12;
$seniorDiscountRate = 0.20;
$pwdDiscountRate = 0.20;

$stmt = $mysqli->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('tax_rate', 'senior_discount_rate', 'pwd_discount_rate')");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['setting_key'] === 'tax_rate') {
        $taxRate = floatval($row['setting_value']);
    } elseif ($row['setting_key'] === 'senior_discount_rate') {
        $seniorDiscountRate = floatval($row['setting_value']);
    } elseif ($row['setting_key'] === 'pwd_discount_rate') {
        $pwdDiscountRate = floatval($row['setting_value']);
    }
}
$stmt->close();

$mysqli->close();

$username = 'Admin';
$currentDate = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REMS RESTO - System Management</title>
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
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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
        
        .pin-card {
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            border-radius: 1rem;
        }
        
        .rate-card {
            background: var(--success-gradient);
            color: white;
            transition: all 0.3s ease;
            border-radius: 1rem;
        }
        
        .discount-card {
            background: var(--warning-gradient);
            color: white;
            transition: all 0.3s ease;
            border-radius: 1rem;
        }
        
        .pin-card:hover, .rate-card:hover, .discount-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        @keyframes pop-in {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .animate-pop-in {
            animation: pop-in 0.3s ease-out;
        }
        
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
        }
        
        .form-input:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            outline: none;
        }
        
        .tab-button {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-radius: 0.75rem;
        }
        
        .tab-button.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-dark);
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
            
            .mobile-hidden {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            .mobile-col {
                flex-direction: column;
            }
            
            .mobile-col > * {
                width: 100%;
            }
            
            .mobile-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .mobile-tabs .tab-button {
                width: 100%;
            }
        }
        
        /* Notification Toast */
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Card Hover Effects */
        .hover-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .hover-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(220, 38, 38, 0.15);
        }
        
        /* Modal Overlay */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
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
                        <h1 class="text-xl sm:text-2xl font-bold">Rem's Resto</h1>
                        <p class="text-red-100 text-xs sm:text-sm">System Management</p>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="text-white text-center sm:text-right">
                        <p class="font-semibold text-sm sm:text-base">Welcome, <?php echo htmlspecialchars($username); ?>!</p>
                        <p class="text-red-100 text-xs sm:text-sm"><?php echo date('M j, Y'); ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="window.location.href='inventory.php'" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-flask"></i>
                            <span class="hidden sm:inline">Inventory</span>
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
        <!-- Tab Navigation -->
        <div class="glass-effect rounded-xl sm:rounded-2xl p-2 mb-4 sm:mb-6 shadow-lg">
            <div class="flex mobile-tabs">
                <button id="tabRates" class="tab-button active flex-1 px-3 sm:px-4 py-2 sm:py-3 font-semibold transition-all duration-300" onclick="showTab('rates')">
                    <i class="fas fa-percentage mr-2"></i>Tax & Discount Rates
                </button>
                <button id="tabPins" class="tab-button flex-1 px-3 sm:px-4 py-2 sm:py-3 font-semibold transition-all duration-300" onclick="showTab('pins')">
                    <i class="fas fa-key mr-2"></i>PIN Management
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 sm:mb-6 animate-pop-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 sm:mb-6 animate-pop-in">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tax & Discount Rates Tab -->
        <div id="ratesTab" class="tab-content">
            <!-- Current Rates Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div class="rate-card p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-xs sm:text-sm font-medium">Current Tax Rate</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo number_format($taxRate * 100, 1); ?>%</p>
                            <p class="text-green-100 text-xs mt-1">Value Added Tax</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                            <i class="fas fa-receipt text-base sm:text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="discount-card p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-xs sm:text-sm font-medium">Senior Discount</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo number_format($seniorDiscountRate * 100, 1); ?>%</p>
                            <p class="text-yellow-100 text-xs mt-1">Senior Citizen</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                            <i class="fas fa-user-friends text-base sm:text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="discount-card p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-xs sm:text-sm font-medium">PWD Discount</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo number_format($pwdDiscountRate * 100, 1); ?>%</p>
                            <p class="text-yellow-100 text-xs mt-1">Person with Disability</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                            <i class="fas fa-wheelchair text-base sm:text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Update Form -->
            <div class="glass-effect rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6 mb-6">
                <div class="flex items-center justify-between mb-4 sm:mb-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">Update Tax & Discount Rates</h2>
                    <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold text-xs sm:text-sm">
                        <i class="fas fa-calculator mr-1"></i>Financial Settings
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="update_rates" value="1">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <!-- Tax Rate -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Tax Rate (%)</label>
                            <div class="relative">
                                <input type="number" name="tax_rate" min="0" max="100" step="0.1" 
                                       value="<?php echo number_format($taxRate * 100, 1); ?>"
                                       class="w-full form-input px-3 sm:px-4 py-2 sm:py-3 pr-10" 
                                       required>
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Current: <?php echo number_format($taxRate * 100, 1); ?>%</p>
                        </div>

                        <!-- Senior Discount Rate -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Senior Discount (%)</label>
                            <div class="relative">
                                <input type="number" name="senior_discount_rate" min="0" max="100" step="0.1" 
                                       value="<?php echo number_format($seniorDiscountRate * 100, 1); ?>"
                                       class="w-full form-input px-3 sm:px-4 py-2 sm:py-3 pr-10" 
                                       required>
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Current: <?php echo number_format($seniorDiscountRate * 100, 1); ?>%</p>
                        </div>

                        <!-- PWD Discount Rate -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">PWD Discount (%)</label>
                            <div class="relative">
                                <input type="number" name="pwd_discount_rate" min="0" max="100" step="0.1" 
                                       value="<?php echo number_format($pwdDiscountRate * 100, 1); ?>"
                                       class="w-full form-input px-3 sm:px-4 py-2 sm:py-3 pr-10" 
                                       required>
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Current: <?php echo number_format($pwdDiscountRate * 100, 1); ?>%</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4 mt-4 sm:mt-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2 sm:mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-1 text-sm sm:text-base">Rate Guidelines:</h4>
                                <ul class="text-blue-700 text-xs sm:text-sm space-y-1">
                                    <li>• Tax rate is typically 12% for VAT-registered businesses</li>
                                    <li>• Senior Citizen and PWD discounts are mandated by law (20%)</li>
                                    <li>• Changes will affect all new transactions immediately</li>
                                    <li>• Existing orders will retain their original rates</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4 sm:mt-6 flex justify-end">
                        <button type="submit" 
                                class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:from-green-600 hover:to-emerald-700 transition-all duration-300 shadow-lg transform hover:scale-105 flex items-center space-x-2 text-sm sm:text-base">
                            <i class="fas fa-save"></i>
                            <span>Update Rates</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PIN Management Tab -->
        <div id="pinsTab" class="tab-content hidden">
            <!-- Current PINs Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div class="pin-card p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-xs sm:text-sm font-medium">Menu System PIN</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo htmlspecialchars($menuPin); ?></p>
                            <p class="text-red-100 text-xs mt-1">Accesses POS/Menu System</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                            <i class="fas fa-utensils text-base sm:text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="pin-card p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-xs sm:text-sm font-medium">Inventory System PIN</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo htmlspecialchars($inventoryPin); ?></p>
                            <p class="text-red-100 text-xs mt-1">Accesses Inventory & Reports</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                            <i class="fas fa-boxes text-base sm:text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIN Update Form -->
            <div class="glass-effect rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4 sm:mb-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800">Update System PINs</h2>
                    <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full font-semibold text-xs sm:text-sm">
                        <i class="fas fa-shield-alt mr-1"></i>Security Settings
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="update_pin" value="1">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <!-- System Selection -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Select System</label>
                            <select name="role" class="w-full form-input px-3 sm:px-4 py-2 sm:py-3" required>
                                <option value="">Select a system...</option>
                                <option value="menu">Menu System (POS)</option>
                                <option value="inventory">Inventory System</option>
                            </select>
                        </div>

                        <!-- Current PIN -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Current PIN</label>
                            <input type="password" name="current_pin" maxlength="4" pattern="[0-9]{4}" 
                                   class="w-full form-input px-3 sm:px-4 py-2 sm:py-3" 
                                   placeholder="Enter current 4-digit PIN" required>
                        </div>

                        <!-- New PIN -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">New PIN</label>
                            <input type="password" name="new_pin" maxlength="4" pattern="[0-9]{4}" 
                                   class="w-full form-input px-3 sm:px-4 py-2 sm:py-3" 
                                   placeholder="Enter new 4-digit PIN" required>
                        </div>

                        <!-- Confirm New PIN -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Confirm New PIN</label>
                            <input type="password" name="confirm_pin" maxlength="4" pattern="[0-9]{4}" 
                                   class="w-full form-input px-3 sm:px-4 py-2 sm:py-3" 
                                   placeholder="Confirm new 4-digit PIN" required>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4 mt-4 sm:mt-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2 sm:mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-1 text-sm sm:text-base">PIN Requirements:</h4>
                                <ul class="text-blue-700 text-xs sm:text-sm space-y-1">
                                    <li>• PIN must be exactly 4 digits (0-9)</li>
                                    <li>• You must know the current PIN to change it</li>
                                    <li>• New PIN and confirmation must match</li>
                                    <li>• Keep your PIN secure and confidential</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-4 sm:mt-6 flex justify-end">
                        <button type="submit" 
                                class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300 shadow-lg transform hover:scale-105 flex items-center space-x-2 text-sm sm:text-base">
                            <i class="fas fa-save"></i>
                            <span>Update PIN</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        
    </main>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40">
        <div class="flex justify-around items-center h-16">
            <a href="home.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-home text-xl"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="inventory.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-boxes text-xl"></i>
                <span class="text-xs mt-1">Inventory</span>
            </a>
            <a href="earnings.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-chart-line text-xl"></i>
                <span class="text-xs mt-1">Earnings</span>
            </a>
            <a href="usermanagement.php" class="flex flex-col items-center justify-center w-full h-full text-red-600">
                <i class="fas fa-cog text-xl"></i>
                <span class="text-xs mt-1">Settings</span>
            </a>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.remove('hidden');
            
            // Add active class to clicked button
            const tabId = 'tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1);
            document.getElementById(tabId).classList.add('active');
            
            // Save active tab to localStorage
            localStorage.setItem('activeTab', tabName);
        }

        // Load active tab from localStorage
        function loadActiveTab() {
            const activeTab = localStorage.getItem('activeTab') || 'rates';
            showTab(activeTab);
        }

        // Add input validation for PIN fields
        function initPinValidation() {
            const pinInputs = document.querySelectorAll('input[type="password"][name*="pin"]');
            
            pinInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Only allow numbers
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limit to 4 digits
                    if (this.value.length > 4) {
                        this.value = this.value.slice(0, 4);
                    }
                });
                
                // Prevent paste of non-numeric values
                input.addEventListener('paste', function(e) {
                    const pasteData = e.clipboardData.getData('text');
                    if (!/^\d+$/.test(pasteData)) {
                        e.preventDefault();
                    }
                });
            });

            // Add input validation for percentage fields
            const percentageInputs = document.querySelectorAll('input[name*="rate"]');
            percentageInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = parseFloat(this.value);
                    if (value < 0) this.value = 0;
                    if (value > 100) this.value = 100;
                });
            });
        }

        // Initialize the application
        function initializeApp() {
            loadActiveTab();
            initPinValidation();
            
            // Add smooth transitions
            document.querySelector('main').style.opacity = '0';
            document.querySelector('main').style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                document.querySelector('main').style.opacity = '1';
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', initializeApp);
        
        // Handle window resize
        window.addEventListener('resize', function() {
            // Adjust layout on resize if needed
        });
    </script>
</body>
</html>