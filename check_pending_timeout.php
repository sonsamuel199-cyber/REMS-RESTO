<?php
// check_pending_timeout.php - Background job to handle abandoned pending tickets
// Run this script periodically via Windows Task Scheduler or cron

// ============================================
// FIX 004: PENDING PAYMENT TIMEOUT
// ============================================

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Define timeout period (e.g., 2 hours)
$timeoutMinutes = 120;
$timeoutThreshold = date('Y-m-d H:i:s', strtotime("-$timeoutMinutes minutes"));

// Find tickets that have been pending too long
$stmt = $mysqli->prepare("
    SELECT id, customer_name, table_number, saved_at, items 
    FROM saved_carts 
    WHERE status = 'active' 
    AND saved_at < ? 
    AND is_abandoned = FALSE
");
$stmt->bind_param("s", $timeoutThreshold);
$stmt->execute();
$result = $stmt->get_result();

$abandonedTickets = [];
while ($row = $result->fetch_assoc()) {
    $abandonedTickets[] = $row;
}
$stmt->close();

// Mark abandoned tickets
$processedCount = 0;
if (count($abandonedTickets) > 0) {
    foreach ($abandonedTickets as $ticket) {
        $update = $mysqli->prepare("UPDATE saved_carts SET is_abandoned = TRUE WHERE id = ?");
        $update->bind_param("i", $ticket['id']);
        if ($update->execute()) {
            $processedCount++;
            // Log the abandoned ticket
            error_log("Pending ticket #{$ticket['id']} for Table {$ticket['table_number']} was abandoned at {$ticket['saved_at']}");
        }
        $update->close();
    }
}

$mysqli->close();

// Return count for logging
echo json_encode(['processed' => $processedCount, 'total_found' => count($abandonedTickets)]);
?>