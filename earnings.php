<?php
session_start();
if (!isset($_SESSION['user_type'])) {
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

$username = "Admin";
$currentDate = date("F j, Y");

// Get time period from request
$period = $_GET['period'] ?? 'daily';
$periods = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($period, $periods)) $period = 'daily';

$dateRanges = [
    'daily' => [
        'start' => date('Y-m-d 00:00:00'),
        'end' => date('Y-m-d 23:59:59'),
        'display' => date('F j, Y')
    ],
    'weekly' => [
        'start' => date('Y-m-d 00:00:00', strtotime('monday this week')),
        'end' => date('Y-m-d 23:59:59', strtotime('sunday this week')),
        'display' => date('M j', strtotime('monday this week')) . ' - ' . date('M j, Y', strtotime('sunday this week'))
    ],
    'monthly' => [
        'start' => date('Y-m-01 00:00:00'),
        'end' => date('Y-m-t 23:59:59'),
        'display' => date('F Y')
    ],
    'yearly' => [
        'start' => date('Y-01-01 00:00:00'),
        'end' => date('Y-12-31 23:59:59'),
        'display' => date('Y')
    ]
];

$currentRange = $dateRanges[$period];
$startDate = $currentRange['start'];
$endDate = $currentRange['end'];

$summary = [
    'totalSales' => 0.00,
    'totalDiscount' => 0.00,
    'totalTax' => 0.00,
    'totalOrders' => 0,
    'totalItemsSold' => 0,
    'totalRevenue' => 0.00
];

$orders_history = [];
$top_selling_items = [];
$growth = ['sales' => 0, 'orders' => 0];

// Check if orders table exists
$table_check = $mysqli->query("SHOW TABLES LIKE 'orders'");
if ($table_check->num_rows > 0) {
    // --- SUMMARY (exclude cancelled) ---
    $summary_sql = "
        SELECT 
            COALESCE(SUM(subtotal), 0) AS totalSales,
            COALESCE(SUM(discount), 0) AS totalDiscount,
            COALESCE(SUM(tax), 0) AS totalTax,
            COUNT(id) AS totalOrders,
            COALESCE(SUM(total_amount), 0) AS totalRevenue
        FROM orders 
        WHERE created_at BETWEEN '$startDate' AND '$endDate'
          AND status = 'completed'
    ";
    $summary_result = $mysqli->query($summary_sql);
    if ($summary_result) {
        $row = $summary_result->fetch_assoc();
        $summary['totalSales'] = (float)$row['totalSales'];
        $summary['totalDiscount'] = (float)$row['totalDiscount'];
        $summary['totalTax'] = (float)$row['totalTax'];
        $summary['totalOrders'] = (int)$row['totalOrders'];
        $summary['totalRevenue'] = (float)$row['totalRevenue'];
    }

    // --- Items sold (exclude cancelled) ---
    $items_sold_sql = "
        SELECT COALESCE(SUM(oi.quantity), 0) AS totalItemsSold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN '$startDate' AND '$endDate'
          AND o.status = 'completed'
    ";
    $items_sold_result = $mysqli->query($items_sold_sql);
    if ($items_sold_result) {
        $row = $items_sold_result->fetch_assoc();
        $summary['totalItemsSold'] = (int)$row['totalItemsSold'];
    }

    // --- Order History (show all orders: completed + cancelled, with cancellation details) ---
    $history_sql = "
        SELECT 
            o.id,
            o.total_amount,
            o.created_at,
            o.subtotal,
            o.discount,
            o.tax,
            o.payment_method,
            o.table_number,
            o.senior_discount,
            o.pwd_discount,
            o.status,
            o.cancellation_reason,
            o.cancelled_at,
            o.cancelled_by,
            o.refund_type
        FROM orders o
        WHERE o.created_at BETWEEN '$startDate' AND '$endDate'
        ORDER BY o.created_at DESC
        LIMIT 20
    ";
    $history_result = $mysqli->query($history_sql);
    if ($history_result) {
        while ($row = $history_result->fetch_assoc()) {
            $order_id = $row['id'];
            $items_sql = "SELECT name, price, quantity FROM order_items WHERE order_id = $order_id";
            $items_result = $mysqli->query($items_sql);
            $items = [];
            if ($items_result) {
                while ($item_row = $items_result->fetch_assoc()) {
                    $items[] = [
                        'name' => $item_row['name'],
                        'price' => (float)$item_row['price'],
                        'quantity' => (int)$item_row['quantity']
                    ];
                }
            }
            $discount_type = '';
            if ($row['senior_discount']) $discount_type = 'Senior';
            elseif ($row['pwd_discount']) $discount_type = 'PWD';
            
            $orders_history[] = [
                'id' => $order_id,
                'timestamp' => $row['created_at'],
                'subtotal' => (float)$row['subtotal'],
                'discount' => (float)$row['discount'],
                'tax' => (float)$row['tax'],
                'total' => (float)$row['total_amount'],
                'payment_method' => $row['payment_method'],
                'table_number' => $row['table_number'],
                'discount_type' => $discount_type,
                'items' => $items,
                'status' => $row['status'] ?? 'completed',
                'cancellation_reason' => $row['cancellation_reason'] ?? '',
                'cancelled_at' => $row['cancelled_at'] ?? '',
                'cancelled_by' => $row['cancelled_by'] ?? '',
                'refund_type' => $row['refund_type'] ?? ''
            ];
        }
    }

    // --- Top Selling Items (exclude cancelled) ---
    $top_items_sql = "
        SELECT 
            oi.name, 
            SUM(oi.quantity) AS total_quantity_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN '$startDate' AND '$endDate'
          AND o.status = 'completed'
        GROUP BY oi.name
        ORDER BY total_quantity_sold DESC
        LIMIT 5
    ";
    $top_items_result = $mysqli->query($top_items_sql);
    if ($top_items_result) {
        while ($row = $top_items_result->fetch_assoc()) {
            $top_selling_items[] = [
                'name' => $row['name'],
                'quantity' => (int)$row['total_quantity_sold']
            ];
        }
    }

    // --- Tax rate & growth calculations (exclude cancelled) ---
    $taxRate = 0.12;
    $taxResult = $mysqli->query("SELECT setting_value FROM system_settings WHERE setting_key = 'tax_rate'");
    if ($taxResult && $taxResult->num_rows > 0) {
        $taxRate = floatval($taxResult->fetch_assoc()['setting_value']);
    }

    if ($summary['totalSales'] > 0) {
        // Previous period dates
        if ($period === 'daily') {
            $prevStartDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $prevEndDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
        } elseif ($period === 'weekly') {
            $prevStartDate = date('Y-m-d 00:00:00', strtotime('monday last week'));
            $prevEndDate = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        } elseif ($period === 'monthly') {
            $prevStartDate = date('Y-m-01 00:00:00', strtotime('-1 month'));
            $prevEndDate = date('Y-m-t 23:59:59', strtotime('-1 month'));
        } else {
            $prevYear = date('Y') - 1;
            $prevStartDate = $prevYear . '-01-01 00:00:00';
            $prevEndDate = $prevYear . '-12-31 23:59:59';
        }
        
        $prevSalesSql = "SELECT COALESCE(SUM(subtotal), 0) AS prevSales FROM orders WHERE created_at BETWEEN '$prevStartDate' AND '$prevEndDate' AND status = 'completed'";
        $prevSalesResult = $mysqli->query($prevSalesSql);
        $prevSales = 0;
        if ($prevSalesResult) {
            $prevRow = $prevSalesResult->fetch_assoc();
            $prevSales = (float)$prevRow['prevSales'];
        }
        if ($prevSales > 0) {
            $growth['sales'] = (($summary['totalSales'] - $prevSales) / $prevSales) * 100;
        } elseif ($summary['totalSales'] > 0) {
            $growth['sales'] = 100;
        }
        
        $prevOrdersSql = "SELECT COUNT(id) AS prevOrders FROM orders WHERE created_at BETWEEN '$prevStartDate' AND '$prevEndDate' AND status = 'completed'";
        $prevOrdersResult = $mysqli->query($prevOrdersSql);
        $prevOrders = 0;
        if ($prevOrdersResult) {
            $prevRow = $prevOrdersResult->fetch_assoc();
            $prevOrders = (int)$prevRow['prevOrders'];
        }
        if ($prevOrders > 0) {
            $growth['orders'] = (($summary['totalOrders'] - $prevOrders) / $prevOrders) * 100;
        } elseif ($summary['totalOrders'] > 0) {
            $growth['orders'] = 100;
        }
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REMS RESTO - Earnings Report</title>
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
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 20px 40px rgba(220, 38, 38, 0.08); }
        .header-gradient { background: var(--primary-gradient); box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2); }
        .earnings-card-primary { background: var(--primary-gradient); color: white; border-radius: 1rem; transition: all 0.3s ease; }
        .earnings-card-success { background: var(--success-gradient); color: white; border-radius: 1rem; transition: all 0.3s ease; }
        .earnings-card-warning { background: var(--warning-gradient); color: white; border-radius: 1rem; transition: all 0.3s ease; }
        .earnings-card-info { background: var(--info-gradient); color: white; border-radius: 1rem; transition: all 0.3s ease; }
        .earnings-card-primary:hover, .earnings-card-success:hover, .earnings-card-warning:hover, .earnings-card-info:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); }
        .period-badge { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #fecaca; color: #b91c1c; }
        .period-active { background: var(--primary-gradient); color: white; }
        .order-item { transition: all 0.3s ease; border: 1px solid #e5e7eb; border-left: 4px solid #10b981; }
        .order-item:hover { transform: translateX(5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }
        .order-item.cancelled { border-left-color: #ef4444; background: #fef2f2; opacity: 0.8; }
        .growth-positive { color: #10b981; }
        .growth-negative { color: #ef4444; }
        .progress-bar { height: 8px; background: var(--primary-gradient); border-radius: 4px; transition: width 1s ease-in-out; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 3px; }
        @keyframes pop-in { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        .animate-pop-in { animation: pop-in 0.3s ease-out; }
        .order-details { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .order-details.expanded { max-height: 500px; transition: max-height 0.5s ease-in; }
        .toggle-details { cursor: pointer; transition: color 0.2s ease; }
        .toggle-details:hover { color: #dc2626; }
        .order-summary { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 0.5rem; padding: 0.75rem; margin-top: 0.5rem; }
        .discount-badge { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
        @media (max-width: 768px) { .mobile-stack { flex-direction: column; } .mobile-full { width: 100%; } .mobile-padding { padding: 1rem; } .mobile-text-center { text-align: center; } .mobile-tabs { flex-direction: column; gap: 0.5rem; } .mobile-tabs button { width: 100%; } }
        @media (max-width: 640px) { .mobile-col { flex-direction: column; } .mobile-col > * { width: 100%; } }
        .empty-state { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 1rem; }
        .modal-overlay { background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); }
        .notification-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body class="min-h-screen">

    <header class="header-gradient shadow-2xl">
        <div class="max-w-8xl mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                <div class="flex items-center space-x-3">
                   <div class="bg-white p-2 rounded-2xl shadow-lg transform hover:rotate-12 transition-transform duration-300 w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center overflow-hidden">
                     <img src="images/2be0107a-02ff-48e0-92c4-f109ec040290.png" alt="REMS RESTO Logo" class="w-full h-full object-cover">
                </div>
                    <div class="text-white">
                        <h1 class="text-xl sm:text-2xl font-bold">Rem's Resto</h1>
                        <p class="text-red-100 text-xs sm:text-sm">Earnings Dashboard</p>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="text-white text-center sm:text-right">
                        <p class="font-semibold text-sm sm:text-base">Welcome, <?php echo htmlspecialchars($username); ?>!</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="window.location.href='usermanagement.php'" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-users"></i>
                            <span class="hidden sm:inline">User Management</span>
                        </button>
                        <button onclick="window.location.href='inventory.php'" class="bg-white text-red-600 px-3 sm:px-4 py-1 sm:py-2 rounded-xl font-semibold hover:bg-red-50 transition-all duration-300 shadow-lg flex items-center space-x-2 text-xs sm:text-sm transform hover:scale-105">
                            <i class="fas fa-flask"></i>
                            <span class="hidden sm:inline">Inventory</span>
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

    <main class="max-w-8xl mx-auto p-2 sm:p-4">
        <!-- Period Selector -->
        <div class="glass-effect rounded-xl sm:rounded-2xl p-3 sm:p-4 mb-4 sm:mb-6 shadow-lg">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-3 lg:space-y-0">
                <div class="mb-3 lg:mb-0">
                    <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 mb-1">Earnings Report</h2>
                    <p class="text-gray-600 text-xs sm:text-sm">Multi-period sales performance and analytics</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full lg:w-auto">
                    <div class="glass-effect period-badge rounded-lg px-3 sm:px-4 py-2">
                        <p class="text-sm sm:text-base font-semibold text-gray-800" id="currentDate"><?php echo htmlspecialchars($currentRange['display']); ?></p>
                    </div>
                    <div class="flex bg-gray-100 rounded-lg p-1 mobile-tabs">
                        <button onclick="changePeriod('daily')" class="px-3 py-2 rounded-md font-medium transition-all duration-300 text-xs sm:text-sm <?php echo $period === 'daily' ? 'period-active' : 'text-gray-600 hover:bg-white'; ?>">
                            <i class="fas fa-calendar-day mr-1 sm:mr-2"></i>Daily
                        </button>
                        <button onclick="changePeriod('weekly')" class="px-3 py-2 rounded-md font-medium transition-all duration-300 text-xs sm:text-sm <?php echo $period === 'weekly' ? 'period-active' : 'text-gray-600 hover:bg-white'; ?>">
                            <i class="fas fa-calendar-week mr-1 sm:mr-2"></i>Weekly
                        </button>
                        <button onclick="changePeriod('monthly')" class="px-3 py-2 rounded-md font-medium transition-all duration-300 text-xs sm:text-sm <?php echo $period === 'monthly' ? 'period-active' : 'text-gray-600 hover:bg-white'; ?>">
                            <i class="fas fa-calendar-alt mr-1 sm:mr-2"></i>Monthly
                        </button>
                        <button onclick="changePeriod('yearly')" class="px-3 py-2 rounded-md font-medium transition-all duration-300 text-xs sm:text-sm <?php echo $period === 'yearly' ? 'period-active' : 'text-gray-600 hover:bg-white'; ?>">
                            <i class="fas fa-calendar mr-1 sm:mr-2"></i>Yearly
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="earnings-card-primary p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-xs sm:text-sm font-medium">Total Revenue</p>
                        <p class="text-xl sm:text-2xl font-bold">₱<?php echo number_format($summary['totalRevenue'], 2); ?></p>
                        <div class="flex items-center mt-1">
                            <?php if (isset($growth['sales'])): ?>
                                <span class="text-red-100 text-xs <?php echo $growth['sales'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $growth['sales'] >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                                    <?php echo number_format(abs($growth['sales']), 1); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-money-bill-wave text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="earnings-card-success p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-xs sm:text-sm font-medium">Net Sales</p>
                        <p class="text-xl sm:text-2xl font-bold">₱<?php echo number_format($summary['totalSales'], 2); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="text-green-100 text-xs">Before tax & deductions</span>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-chart-bar text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="earnings-card-warning p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-xs sm:text-sm font-medium">Total Orders</p>
                        <p class="text-xl sm:text-2xl font-bold"><?php echo $summary['totalOrders']; ?></p>
                        mt-1">
                            <?php if (isset($growth['orders'])): ?>
                                <span class="text-yellow-100 text-xs <?php echo $growth['orders'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $growth['orders'] >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                                    <?php echo number_format(abs($growth['orders']), 1); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-shopping-bag text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="earnings-card-info p-4 sm:p-6">
                justify-between">
                    <div>
                        <p class="text-cyan-100 text-xs sm:text-sm font-medium">Items Sold</p>
                        <p class="text-xl sm:text-2xl font-bold"><?php echo $summary['totalItemsSold']; ?></p>
                        <div class="flex items-center mt-1">
                            <span class="text-cyan-100 text-xs">
                                <?php echo $summary['totalOrders'] > 0 ? number_format($summary['totalItemsSold'] / $summary['totalOrders'], 1) : '0'; ?> per order
                            </span>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 sm:p-3">
                        <i class="fas fa-cube text-base sm:text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-4 sm:mb-6">
            <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Tax Collection</h3>
                    <div class="bg-red-100 text-red-800 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-semibold">
                        <?php echo isset($taxRate) ? number_format($taxRate * 100, 1) : '12.0'; ?>% Rate
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-red-600 mb-2">₱<?php echo number_format($summary['totalTax'], 2); ?></p>
                    <p class="text-gray-600 text-sm">Total Tax Collected</p>
                </div>
                <div class="mt-3 sm:mt-4 bg-gray-200 rounded-full h-2">
                    <div class="progress-bar" style="width: <?php echo $summary['totalSales'] > 0 ? min(100, ($summary['totalTax'] / $summary['totalSales']) * 100) : 0; ?>%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-center">Tax to Sales Ratio</p>
            </div>

            <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Average Order</h3>
                    <div class="bg-green-100 text-green-800 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-semibold">AOV</div>
                </div>
                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-green-600 mb-2">
                        ₱<?php echo number_format($summary['totalOrders'] > 0 ? ($summary['totalRevenue'] / $summary['totalOrders']) : 0, 2); ?>
                    </p>
                    <p class="text-gray-600 text-sm">Average Order Value</p>
                </div>
                <div class="mt-3 sm:mt-4 text-center">
                    <p class="text-xs sm:text-sm text-gray-500">
                        <?php echo $summary['totalItemsSold']; ?> items across <?php echo $summary['totalOrders']; ?> orders
                    </p>
                </div>
            </div>

            <div class="glass-effect rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <h3 class="text-base sm:text-lg font-bold text-gray-800">Items per Order</h3>
                    <div class="bg-purple-100 text-purple-800 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-semibold">Efficiency</div>
                </div>
                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-purple-600 mb-2">
                        <?php echo number_format($summary['totalOrders'] > 0 ? ($summary['totalItemsSold'] / $summary['totalOrders']) : 0, 1); ?>
                    </p>
                    <p class="text-gray-600 text-sm">Average Items per Order</p>
                </div>
                <div class="mt-3 sm:mt-4 flex justify-center">
                    <div class="flex space-x-1">
                        <?php
                        $avgItems = $summary['totalOrders'] > 0 ? ($summary['totalItemsSold'] / $summary['totalOrders']) : 0;
                        for ($i = 1; $i <= 5; $i++): 
                            $fillClass = $i <= $avgItems ? 'text-yellow-400' : 'text-gray-300';
                        ?>
                            <i class="fas fa-star <?php echo $fillClass; ?> text-sm sm:text-base"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Order History -->
            <div class="lg:col-span-2">
                <div class="glass-effect rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800"><?php echo ucfirst($period); ?> Order History</h3>
                        <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full font-semibold text-xs sm:text-sm">
                            <i class="fas fa-history mr-1"></i>
                            <?php echo $period === 'daily' ? 'Live Updates' : 'Historical Data'; ?>
                        </div>
                    </div>
                    <div class="space-y-3 sm:space-y-4 overflow-y-auto custom-scrollbar" style="max-height: 500px;">
                        <div id="orderHistoryList">
                            <?php if (count($orders_history) > 0): ?>
                                <?php foreach ($orders_history as $order): ?>
                                    <div class="order-item glass-effect rounded-lg p-3 sm:p-4 hover:shadow-md transition-all duration-300 mb-3 <?php echo ($order['status'] === 'cancelled') ? 'cancelled' : ''; ?>" data-order-id="<?php echo $order['id']; ?>">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <span class="font-bold text-gray-800 text-sm sm:text-base">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    <?php echo (new DateTime($order['timestamp']))->format('M j, Y h:i A'); ?>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <i class="fas fa-table mr-1"></i>
                                                    Table <?php echo $order['table_number']; ?> • 
                                                    <i class="fas fa-credit-card mr-1"></i>
                                                    <?php echo ucfirst($order['payment_method']); ?>
                                                    <?php if (!empty($order['discount_type'])): ?>
                                                        • <span class="discount-badge"><?php echo $order['discount_type']; ?> Discount</span>
                                                    <?php endif; ?>
                                                    <?php if ($order['status'] === 'cancelled'): ?>
                                                        • <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs font-semibold">CANCELLED</span>
                                                    <?php endif; ?>
                                                </p>
                                                <!-- ============================================ -->
                                                <!-- FIX: Show cancellation details if cancelled -->
                                                <!-- ============================================ -->
                                                <?php if ($order['status'] === 'cancelled'): ?>
                                                    <div class="bg-red-50 p-2 rounded-lg mt-1 border border-red-200">
                                                        <p class="text-xs text-red-600">
                                                            <i class="fas fa-user mr-1"></i> Cancelled by: <strong><?php echo htmlspecialchars($order['cancelled_by'] ?? 'Unknown'); ?></strong>
                                                        </p>
                                                        <p class="text-xs text-red-600">
                                                            <i class="fas fa-clock mr-1"></i> Cancelled at: <?php echo $order['cancelled_at'] ? date('M j, Y h:i A', strtotime($order['cancelled_at'])) : 'N/A'; ?>
                                                        </p>
                                                        <p class="text-xs text-red-600">
                                                            <i class="fas fa-comment mr-1"></i> Reason: <?php echo htmlspecialchars($order['cancellation_reason'] ?? 'No reason provided'); ?>
                                                        </p>
                                                        <p class="text-xs text-red-600">
                                                            <i class="fas fa-undo mr-1"></i> Refund Type: <strong><?php echo ucfirst($order['refund_type'] ?? 'restock'); ?></strong>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-lg sm:text-xl font-bold text-green-600 bg-green-50 px-3 sm:px-4 py-1 sm:py-2 rounded-lg">
                                                    ₱<?php echo number_format($order['total'], 2); ?>
                                                </span>
                                                <?php if ($_SESSION['user_type'] === 'admin' && $order['status'] === 'completed'): ?>
                                                    <button onclick="showCancelModal(<?php echo $order['id']; ?>)" 
                                                            class="ml-2 bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600 transition">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="toggle-details text-xs text-blue-500 font-medium mb-2 flex items-center cursor-pointer" 
                                             onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-chevron-down mr-1" id="icon-<?php echo $order['id']; ?>"></i>
                                            View Order Details
                                        </div>
                                        
                                        <div class="order-details" id="details-<?php echo $order['id']; ?>">
                                            <div class="order-summary">
                                                <?php if (!empty($order['items'])): ?>
                                                    <div class="space-y-2 mb-3">
                                                        <?php foreach ($order['items'] as $item): ?>
                                                            <div class="flex justify-between items-center text-xs sm:text-sm text-gray-600 bg-white px-3 py-2 rounded-md">
                                                                <span>
                                                                    <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                                                    <span class="text-gray-400 ml-2">x<?php echo htmlspecialchars($item['quantity']); ?></span>
                                                                </span>
                                                                <span class="font-semibold">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="grid grid-cols-2 gap-2 text-xs sm:text-sm">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-500">Subtotal:</span>
                                                        <span class="font-medium">₱<?php echo number_format($order['subtotal'], 2); ?></span>
                                                    </div>
                                                    <?php if ($order['discount'] > 0): ?>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-500">Discount:</span>
                                                        <span class="font-medium text-green-600">-₱<?php echo number_format($order['discount'], 2); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-500">Tax:</span>
                                                        <span class="font-medium">₱<?php echo number_format($order['tax'], 2); ?></span>
                                                    </div>
                                                    <div class="flex justify-between font-bold">
                                                        <span class="text-gray-700">Total:</span>
                                                        <span class="text-green-600">₱<?php echo number_format($order['total'], 2); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state rounded-lg p-6 sm:p-8 text-center">
                                    <div class="text-4xl sm:text-6xl mb-4 opacity-30 text-red-300">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <p class="text-base sm:text-lg font-medium text-gray-400 mb-2">No orders for this period</p>
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        <?php if ($table_check->num_rows === 0): ?>
                                            Database tables not set up. Please run the SQL setup.
                                        <?php else: ?>
                                            Orders will appear here once processed through the POS system.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Items -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-4 border-b pb-3">Top Selling Items</h3>
                <div id="topSellingItemsList" class="divide-y divide-gray-200">
                    <?php if (count($top_selling_items) > 0): ?>
                        <?php foreach ($top_selling_items as $index => $item): ?>
                            <div class="flex justify-between items-center p-3 hover:bg-gray-50 rounded-lg transition-colors duration-200">
                                <div class="flex items-center space-x-2 sm:space-x-3">
                                    <span class="flex items-center justify-center w-6 h-6 sm:w-8 sm:h-8 bg-red-100 text-red-600 rounded-full font-bold text-xs sm:text-sm">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <span class="font-medium text-gray-700 text-sm sm:text-base truncate"><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                                <span class="font-bold text-red-500 text-sm sm:text-base bg-red-50 px-2 sm:px-3 py-1 rounded-full">
                                    <?php echo htmlspecialchars($item['quantity']); ?> sold
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state rounded-lg p-6 sm:p-8 text-center">
                            <div class="text-4xl sm:text-6xl mb-4 opacity-30 text-gray-300">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <p class="text-base sm:text-lg font-medium text-gray-400">No items sold this period</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-2">Sales data will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 modal-overlay">
        <div class="glass-effect rounded-xl p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Cancel Order</h3>
            <p class="text-gray-600 mb-4">Please provide a reason for cancellation:</p>
            <textarea id="cancelReason" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500" placeholder="e.g., Customer changed mind, Out of stock, etc."></textarea>
            
            <!-- Waste Management Toggle -->
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
            <a href="earnings.php" class="flex flex-col items-center justify-center w-full h-full text-red-600">
                <i class="fas fa-chart-line text-xl"></i>
                <span class="text-xs mt-1">Earnings</span>
            </a>
            <a href="usermanagement.php" class="flex flex-col items-center justify-center w-full h-full text-gray-600 hover:text-red-600 transition-colors">
                <i class="fas fa-cog text-xl"></i>
                <span class="text-xs mt-1">Settings</span>
            </a>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'login.php';
            }
        }

        function changePeriod(period) {
            const url = new URL(window.location.href);
            url.searchParams.set('period', period);
            window.location.href = url.toString();
        }

        function toggleOrderDetails(orderId) {
            const details = document.getElementById('details-' + orderId);
            const icon = document.getElementById('icon-' + orderId);
            if (details.classList.contains('expanded')) {
                details.classList.remove('expanded');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                details.classList.add('expanded');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }

        function showCancelModal(orderId) {
            document.getElementById('cancelOrderId').value = orderId;
            document.getElementById('cancelReason').value = '';
            // Reset refund type
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

        function showNotification(message, type = 'info') {
            document.querySelectorAll('.notification-toast').forEach(n => n.remove());
            const notification = document.createElement('div');
            notification.className = `notification-toast glass-effect p-4 rounded-xl shadow-lg border-l-4 ${type === 'success' ? 'border-green-500' : type === 'error' ? 'border-red-500' : 'border-blue-500'}`;
            notification.innerHTML = `<div class="flex items-center space-x-3"><i class="fas fa-${type === 'success' ? 'check-circle text-green-500' : type === 'error' ? 'exclamation-circle text-red-500' : 'info-circle text-blue-500'}"></i><span class="font-medium text-sm">${escapeHtml(message)}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function initializeApp() {
            console.log('Earnings dashboard loaded');
            const mainContent = document.querySelector('main');
            if (mainContent) {
                mainContent.style.opacity = '0';
                mainContent.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    mainContent.style.opacity = '1';
                }, 100);
            }
            
            document.querySelectorAll('.toggle-details').forEach(toggle => {
                const orderId = toggle.getAttribute('onclick').match(/\d+/)[0];
                const details = document.getElementById('details-' + orderId);
                const icon = document.getElementById('icon-' + orderId);
                if (details && icon) {
                    details.classList.remove('expanded');
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
            
            const periodButtons = document.querySelectorAll('[onclick^="changePeriod"]');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
                    this.disabled = true;
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initializeApp);
        
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 640) {
                document.body.classList.add('mobile-view');
            } else {
                document.body.classList.remove('mobile-view');
            }
        });
        
        if (window.innerWidth <= 640) {
            document.body.classList.add('mobile-view');
        }
    </script>
</body>
</html>