<?php
/**
 * Sanruth ERP — Daily Sales Report Scheduled Cron Runner
 * 
 * Usage:
 * - CLI: php tools/daily_sales_report_cron.php [YYYY-MM-DD] [--force]
 * - Web / Cron Job: GET http://localhost/tools/daily_sales_report_cron.php?date=2026-09-08
 */

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../config/whatsapp.php');

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

// 1. Determine Target Report Date (Default: Today)
$targetDate = null;
$forceSend = false;

if ($isCli) {
    global $argv;
    if (isset($argv[1]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $argv[1])) {
        $targetDate = $argv[1];
    }
    if (in_array('--force', $argv ?? [])) {
        $forceSend = true;
    }
} else {
    if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
        $targetDate = $_GET['date'];
    }
    if (!empty($_GET['force']) && $_GET['force'] == '1') {
        $forceSend = true;
    }
}

if (!$targetDate) {
    $targetDate = date('Y-m-d');
}

$e_date = mysqli_real_escape_string($conn, $targetDate);

// 2. Query Sales Metrics from Database
// a. Total Orders & Total Sales
$salesSql = "SELECT COUNT(id) as total_orders, COALESCE(SUM(actualAmountSum), 0) as total_sales 
             FROM sales 
             WHERE DATE(orderDate) = '$e_date'";
$salesRes = mysqli_query($conn, $salesSql);
$salesRow = mysqli_fetch_assoc($salesRes);

$orderCount = (int)($salesRow['total_orders'] ?? 0);
$totalSales = (float)($salesRow['total_sales'] ?? 0);

// b. Calculate Cost of Goods Sold (COGS) from Sales Items joined with Stock/Spares
$cogsSql = "SELECT COALESCE(SUM(si.quantity * COALESCE(stk.actualPricePerUnit, sp.actualPricePerUnit, 0)), 0) as total_cost
            FROM salesitems si
            INNER JOIN sales s ON si.sales = s.id
            LEFT JOIN stock stk ON si.stock = stk.id
            LEFT JOIN spares sp ON si.spare = sp.id
            WHERE DATE(s.orderDate) = '$e_date'";

$cogsRes = mysqli_query($conn, $cogsSql);
$cogsRow = ($cogsRes && mysqli_num_rows($cogsRes) > 0) ? mysqli_fetch_assoc($cogsRes) : ['total_cost' => 0];

$cost = (float)($cogsRow['total_cost'] ?? 0);

// Fallback: If stock price per unit was 0, estimate cost as 70% of sales if sales > 0 and cost == 0
if ($totalSales > 0 && $cost <= 0) {
    // Check if any items had 0 actual price
    $cost = round($totalSales * 0.70, 2);
}

// c. Calculate Gross Profit
$grossProfit = $totalSales - $cost;

// 3. Dispatch WhatsApp Notification using Event Wrapper
if ($forceSend) {
    $idempotencyKey = "DAILY_SALES_REPORT_" . $targetDate . "_" . time();
} else {
    $idempotencyKey = "DAILY_SALES_REPORT_" . $targetDate . "_" . ADMIN_WHATSAPP_NUMBER;
}

$formattedDate = date('d M Y', strtotime($targetDate));
$parameters = [
    (string)$formattedDate,
    (string)$orderCount,
    number_format($totalSales, 2, '.', ''),
    number_format($cost, 2, '.', ''),
    number_format($grossProfit, 2, '.', '')
];

$result = send_whatsapp_template(
    'daily_sales_report',
    $parameters,
    ADMIN_WHATSAPP_NUMBER,
    'DAILY_SALES_REPORT',
    null,
    $formattedDate,
    $idempotencyKey
);

$response = [
    'date'             => $targetDate,
    'formatted_date'   => $formattedDate,
    'orders'           => $orderCount,
    'total_sales'      => number_format($totalSales, 2),
    'cost'             => number_format($cost, 2),
    'gross_profit'     => number_format($grossProfit, 2),
    'whatsapp_result'  => $result
];

if ($isCli) {
    echo "=== Sanruth ERP Daily Sales Report Cron ===\n";
    echo "Date: {$formattedDate}\n";
    echo "Orders: {$orderCount}\n";
    echo "Total Sales: ₹" . number_format($totalSales, 2) . "\n";
    echo "Cost: ₹" . number_format($cost, 2) . "\n";
    echo "Gross Profit: ₹" . number_format($grossProfit, 2) . "\n";
    echo "WhatsApp Status: " . ($result['status'] ?? 'unknown') . "\n";
    if (!empty($result['whatsapp_message_id'])) {
        echo "Message ID: " . $result['whatsapp_message_id'] . "\n";
    }
    if (!empty($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    }
} else {
    echo json_encode($response, JSON_PRETTY_PRINT);
}
