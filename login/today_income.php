<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Ensure the daily_opening_balance table exists
$tableSql = "
CREATE TABLE IF NOT EXISTS `daily_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `entered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closing_balance` decimal(10,2) DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_type` varchar(20) DEFAULT 'Self',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($conn, $tableSql);

// Add missing columns if they don't exist (for upgrade)
mysqli_query($conn, "ALTER TABLE `daily_opening_balance` ADD COLUMN `closing_balance` decimal(10,2) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE `daily_opening_balance` ADD COLUMN `is_closed` tinyint(1) NOT NULL DEFAULT 0");
mysqli_query($conn, "ALTER TABLE `daily_opening_balance` ADD COLUMN `closed_at` timestamp NULL DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE `daily_opening_balance` ADD COLUMN `close_type` varchar(20) DEFAULT 'Self'");

$today = date('Y-m-d');
$user_name = $_SESSION['username'] ?? 'User';

// Handle opening balance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opening_balance'])) {
    $amount = floatval($_POST['opening_balance']);
    $stmt = $conn->prepare("INSERT INTO daily_opening_balance (`date`, `amount`, `entered_by`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount), entered_by = VALUES(entered_by)");
    $stmt->bind_param("sds", $today, $amount, $user_name);
    $stmt->execute();
    $stmt->close();
    header("Location: today_income.php");
    exit();
}

// Handle close day submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_day'])) {
    $closing_amount = floatval($_POST['closing_amount']);
    $stmt = $conn->prepare("UPDATE daily_opening_balance SET closing_balance = ?, is_closed = 1, closed_at = NOW(), close_type = 'Self' WHERE `date` = ?");
    $stmt->bind_param("ds", $closing_amount, $today);
    $stmt->execute();
    $stmt->close();
    header("Location: today_income.php");
    exit();
}

// Auto-close past days that weren't closed manually
$pastDaysRes = mysqli_query($conn, "SELECT `date`, `amount` FROM daily_opening_balance WHERE is_closed = 0 AND `date` < '$today'");
if ($pastDaysRes && mysqli_num_rows($pastDaysRes) > 0) {
    while ($pastDayRow = mysqli_fetch_assoc($pastDaysRes)) {
        $pDate = $pastDayRow['date'];
        $pOpen = floatval($pastDayRow['amount']);
        
        $pJobRes = mysqli_query($conn, "SELECT SUM(receivedAmountSum) as val FROM jobcard WHERE DATE(givenDate) = '$pDate'");
        $pJob = $pJobRes ? floatval(mysqli_fetch_assoc($pJobRes)['val']) : 0;
        
        $pSalRes = mysqli_query($conn, "SELECT SUM(paidAmountSum) as val FROM sales WHERE DATE(orderDate) = '$pDate'");
        $pSal = $pSalRes ? floatval(mysqli_fetch_assoc($pSalRes)['val']) : 0;
        
        $pPurRes = mysqli_query($conn, "SELECT SUM(paidAmountSum) as val FROM purchase WHERE DATE(orderDate) = '$pDate'");
        $pPur = $pPurRes ? floatval(mysqli_fetch_assoc($pPurRes)['val']) : 0;
        
        $pClose = $pOpen + $pJob + $pSal - $pPur;
        $autoCloseTime = $pDate . ' 23:59:59';
        
        $uStmt = $conn->prepare("UPDATE daily_opening_balance SET closing_balance = ?, is_closed = 1, closed_at = ?, close_type = 'Auto' WHERE `date` = ?");
        $uStmt->bind_param("dss", $pClose, $autoCloseTime, $pDate);
        $uStmt->execute();
        $uStmt->close();
    }
}

// Get today's opening balance
$opening_balance = null;
$is_closed = false;
$closing_balance = null;
$bal_res = mysqli_query($conn, "SELECT amount, is_closed, closing_balance FROM daily_opening_balance WHERE `date` = '$today'");
if ($bal_res && mysqli_num_rows($bal_res) > 0) {
    $row = mysqli_fetch_assoc($bal_res);
    $opening_balance = floatval($row['amount']);
    $is_closed = (bool)$row['is_closed'];
    $closing_balance = $row['closing_balance'] !== null ? floatval($row['closing_balance']) : null;
}

// ---------------------------------------------------------
// Filter Variables
$filter_date = isset($_GET['date']) ? $_GET['date'] : $today;
$filter_mode = isset($_GET['mode']) ? trim($_GET['mode']) : '';
$all_dates = isset($_GET['all_dates']) && $_GET['all_dates'] == '1';
$filter_work = isset($_GET['work']) ? trim($_GET['work']) : '';

// ---------------------------------------------------------
// Build WHERE Clauses for each type
$jobcard_where = [];
$sales_where = [];
$purchase_where = [];

if (!$all_dates) {
    $safe_date = mysqli_real_escape_string($conn, $filter_date);
    $jobcard_where[] = "j.givenDate = '$safe_date'";
    $sales_where[] = "s.orderDate = '$safe_date'";
    $purchase_where[] = "pu.orderDate = '$safe_date'";
} else {
    $jobcard_where[] = "1=1";
    $sales_where[] = "1=1";
    $purchase_where[] = "1=1";
}

if (!empty($filter_mode)) {
    $safe_mode = mysqli_real_escape_string($conn, $filter_mode);
    if ($filter_mode === 'NetBanking' || $filter_mode === 'Net Banking' || $filter_mode === 'NB') {
        $jobcard_where[] = "j.id IN (SELECT jobCard FROM payment WHERE mode IN ('NetBanking', 'Net Banking', 'NB') AND jobCard IS NOT NULL)";
        $sales_where[] = "s.id IN (SELECT sales FROM payment WHERE mode IN ('NetBanking', 'Net Banking', 'NB') AND sales IS NOT NULL)";
        $purchase_where[] = "pu.id IN (SELECT purchase FROM payment WHERE mode IN ('NetBanking', 'Net Banking', 'NB') AND purchase IS NOT NULL)";
    } elseif ($filter_mode === 'Cash') {
        $jobcard_where[] = "(j.id IN (SELECT jobCard FROM payment WHERE mode = 'Cash' AND jobCard IS NOT NULL) OR j.id NOT IN (SELECT jobCard FROM payment WHERE jobCard IS NOT NULL))";
        $sales_where[] = "(s.id IN (SELECT sales FROM payment WHERE mode = 'Cash' AND sales IS NOT NULL) OR s.id NOT IN (SELECT sales FROM payment WHERE sales IS NOT NULL))";
        $purchase_where[] = "(pu.id IN (SELECT purchase FROM payment WHERE mode = 'Cash' AND purchase IS NOT NULL) OR pu.id NOT IN (SELECT purchase FROM payment WHERE purchase IS NOT NULL))";
    } else {
        $jobcard_where[] = "j.id IN (SELECT jobCard FROM payment WHERE mode = '$safe_mode' AND jobCard IS NOT NULL)";
        $sales_where[] = "s.id IN (SELECT sales FROM payment WHERE mode = '$safe_mode' AND sales IS NOT NULL)";
        $purchase_where[] = "pu.id IN (SELECT purchase FROM payment WHERE mode = '$safe_mode' AND purchase IS NOT NULL)";
    }
}

$jc_where_sql = implode(" AND ", $jobcard_where);
$s_where_sql = implode(" AND ", $sales_where);
$pu_where_sql = implode(" AND ", $purchase_where);

// Queries
$q_jobcard = "
    SELECT 
        'Jobcard' as type,
        j.id as record_id,
        j.givenDate as trans_date,
        j.cardNo as ref_no,
        c.name as party_name,
        (SELECT mode FROM payment p WHERE p.jobCard = j.id ORDER BY id DESC LIMIT 1) as mode,
        j.actualAmountSum as billed,
        j.receivedAmountSum as paid
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    WHERE $jc_where_sql
";

$q_sales = "
    SELECT 
        'Sales' as type,
        s.id as record_id,
        s.orderDate as trans_date,
        s.orderNo as ref_no,
        c.name as party_name,
        (SELECT mode FROM payment p WHERE p.sales = s.id ORDER BY id DESC LIMIT 1) as mode,
        s.actualAmountSum as billed,
        s.paidAmountSum as paid
    FROM sales s
    LEFT JOIN customer c ON s.customer = c.id
    WHERE $s_where_sql
";

$q_purchase = "
    SELECT 
        'Purchase' as type,
        pu.id as record_id,
        pu.orderDate as trans_date,
        pu.orderNo as ref_no,
        sup.name as party_name,
        (SELECT mode FROM payment p WHERE p.purchase = pu.id ORDER BY id DESC LIMIT 1) as mode,
        pu.actualAmountSum as billed,
        pu.paidAmountSum as paid
    FROM purchase pu
    LEFT JOIN supplier sup ON pu.supplier = sup.id
    WHERE $pu_where_sql
";

$union_queries = [];
if (empty($filter_work) || $filter_work === 'Jobcard') {
    $union_queries[] = $q_jobcard;
}
if (empty($filter_work) || $filter_work === 'Sales') {
    $union_queries[] = $q_sales;
}
if (empty($filter_work) || $filter_work === 'Purchases') {
    $union_queries[] = $q_purchase;
}

$query = implode(" UNION ALL ", $union_queries) . " ORDER BY trans_date DESC, record_id DESC";

$res = mysqli_query($conn, $query);
$transactions = [];
$totalTransactions = 0;
$totalBilled = 0;
$totalPaid = 0;

$totalJobcardPaid = 0;
$totalSalesPaid = 0;
$totalPurchasePaid = 0;

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $transactions[] = $row;
        $totalTransactions++;
        $totalBilled += (float)$row['billed'];
        
        if ($row['type'] === 'Purchase') {
            $totalPaid -= (float)$row['paid'];
            $totalPurchasePaid += (float)$row['paid'];
        } elseif ($row['type'] === 'Sales') {
            $totalPaid += (float)$row['paid'];
            $totalSalesPaid += (float)$row['paid'];
        } else {
            $totalPaid += (float)$row['paid'];
            $totalJobcardPaid += (float)$row['paid'];
        }
    }
}
$current_closing_balance = ($opening_balance ?: 0) + $totalPaid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Today Income - Sunder ERP</title>
    <style>
        .page-content {
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
            margin: auto;
        }
        .erp-header-bar {
            display: block;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            flex-wrap: wrap;
            background: transparent;
        }
        .card {
            padding: 10px 15px;
            flex: 1;
            min-width: 250px;
            border-left: 4px solid #C9A227;
            box-sizing: border-box;
            background: transparent;
        }
        @media (max-width: 600px) {
            .summary-cards { gap: 15px; }
            .card { min-width: 100%; }
        }
        .card-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #24231F;
        }
        .balance-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .balance-form input[type="number"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 150px;
            font-size: 16px;
        }
        .balance-form button {
            background: #C9A227;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .balance-form button:hover {
            background: #9A7618;
        }
        .table-responsive {
            overflow-x: auto;
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            min-width: 800px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .type-jobcard { background: #e3f2fd; color: #1565c0; }
        .type-sales { background: #e8f5e9; color: #2e7d32; }
        .type-purchase { background: #ffebee; color: #c62828; }
        
        .action-btn {
            text-decoration: none;
            color: #0d6efd;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #0d6efd;
            font-size: 13px;
        }
        .action-btn:hover {
            background: #0d6efd;
            color: white;
        }
        .close-day-form {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .close-day-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .close-day-btn:hover {
            background: #c82333;
        }
        /* Modal Styles */
        .close-day-modal {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0;
            top: 0;
            width: 100vw; 
            height: 100vh; 
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.6); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; 
            padding: 0;
            border: 1px solid #888;
            width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }
        .modal-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        .close-modal {
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .modal-row.total {
            border-top: 2px solid #eee;
            padding-top: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #16a34a;
        }
        .modal-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
            text-align: right;
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>

    <div class="page-content">
        <div class="erp-header-bar no-print">
            <div style="padding:16px 20px; width:100%; box-sizing:border-box; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div class="erp-header-title" style="font-size:24px; font-weight:bold; margin: 0; line-height: 1.2;">Income Transactions</div>
                <button type="button" onclick="document.getElementById('dailySalesFilter').style.display = document.getElementById('dailySalesFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2); white-space: nowrap;" title="Toggle Filters">
                    <i class="fa-solid fa-sliders"></i> Filter
                </button>
            </div>
            
            <div id="dailySalesFilter" style="background:#fff; padding:16px 20px; border-top:1px solid #eee; box-sizing:border-box; width:100%; display:<?= ($all_dates || !empty($filter_mode) || !empty($filter_work) || $filter_date !== $today) ? 'block' : 'none' ?>;">
                <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap; justify-content:flex-start;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Work Type</label>
                        <select name="work" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; min-width:140px; box-sizing:border-box;">
                            <option value="">-- All --</option>
                            <option value="Jobcard" <?= $filter_work === 'Jobcard' ? 'selected' : '' ?>>Jobcard</option>
                            <option value="Sales" <?= $filter_work === 'Sales' ? 'selected' : '' ?>>Sales</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Payment Mode</label>
                        <select name="mode" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; min-width:140px; box-sizing:border-box;">
                            <option value="">-- All --</option>
                            <option value="Cash" <?= $filter_mode === 'Cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="Card" <?= $filter_mode === 'Card' ? 'selected' : '' ?>>Card</option>
                            <option value="UPI" <?= $filter_mode === 'UPI' ? 'selected' : '' ?>>UPI</option>
                            <option value="NetBanking" <?= ($filter_mode === 'NetBanking' || $filter_mode === 'Net Banking') ? 'selected' : '' ?>>NetBanking</option>
                            <option value="Cheque" <?= $filter_mode === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Date</label>
                        <input type="date" name="date" id="dailyDateInput" value="<?= htmlspecialchars($filter_date) ?>" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; box-sizing:border-box;" <?= $all_dates ? 'disabled' : '' ?>>
                    </div>
                    <div style="height:38px; display:flex; align-items:center;">
                        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; font-weight:600; color:#334155; user-select:none; margin:0;">
                            <input type="checkbox" name="all_dates" id="allDatesCheckbox" value="1" <?= $all_dates ? 'checked' : '' ?> onchange="document.getElementById('dailyDateInput').disabled = this.checked;" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;">
                            <span>All Days</span>
                        </label>
                    </div>
                    <div style="display:flex; gap:8px; align-items:flex-end;">
                        <button type="submit" style="background:#2563eb; color:#fff; padding:8px 18px; border:none; border-radius:5px; font-weight:600; font-size:14px; cursor:pointer; height:38px;">Apply</button>
                        <a href="today_income.php" style="background:#f59e0b; color:#fff; padding:8px 18px; border-radius:5px; font-weight:600; font-size:14px; text-decoration:none; height:38px; display:inline-flex; align-items:center; box-sizing:border-box;">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="summary-cards">
            <div class="card" style="min-height: 80px; position: relative;">
                <div class="card-title">Opening Balance (<?= htmlspecialchars($filter_date) ?>)</div>
                <div class="card-value" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="white-space: nowrap;">
                    <?php if ($opening_balance === null && !$all_dates && $filter_date === $today): ?>
                        <form method="POST" class="balance-form">
                            <input type="number" step="0.01" name="opening_balance" required placeholder="Enter Balance">
                            <button type="submit">Save</button>
                        </form>
                    <?php else: ?>
                        ₹ <?php echo number_format($opening_balance ?: 0, 2); ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Total Transactions Filtered</div>
                <div class="card-value"><?php echo $totalTransactions; ?></div>
            </div>
        </div>
        
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>ID / Ref No</th>
                    <th>Party Name</th>
                    <th>Mode</th>
                    <th>Amount Billed</th>
                    <th>Amount Paid</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px;">No transactions found for selected filters.</td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($transactions as $t): 
                        $typeClass = strtolower($t['type']);
                        $printUrl = "#";
                        if ($t['type'] === 'Jobcard') {
                            $printUrl = "../jobcard/print_receipt.php?id=" . $t['record_id'] . "&type=intake";
                        } elseif ($t['type'] === 'Sales') {
                            $printUrl = "../sales/print_receipt.php?id=" . $t['record_id'];
                        } elseif ($t['type'] === 'Purchase') {
                            $printUrl = "../purchase/purchase_print.php?id=" . $t['record_id'];
                        }
                    ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><span class="type-badge type-<?php echo $typeClass; ?>"><?php echo htmlspecialchars($t['type']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($t['trans_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($t['ref_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($t['party_name'] ?: 'CASH'); ?></td>
                            <td><?php echo htmlspecialchars($t['mode'] ?: 'Cash'); ?></td>
                            <td>₹ <?php echo number_format((float)$t['billed'], 2); ?></td>
                            <td>₹ <?php echo number_format((float)$t['paid'], 2); ?></td>
                            <td>
                                <button type="button" onclick="window.open('<?php echo $printUrl; ?>', '_blank')" style="background:#0d6efd; color:#ffffff; padding:5px 12px; font-size:12.5px; border:none; border-radius:4px; font-weight:600; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.1);">Print</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;">
                    <td colspan="7" style="border-top:1px solid #cbd5e1; padding:12px 15px; text-align:right; font-weight:700;">
                        Total Closing Balance
                    </td>
                    <td colspan="2" style="border-top:1px solid #cbd5e1; padding:12px 15px; text-align:left; font-weight:700; font-size:18px; color:#16a34a;">
                        ₹ <?= number_format($current_closing_balance, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        </div> <!-- end table-responsive -->

        <?php if ($filter_date === $today && !$all_dates): ?>
            <?php if (!$is_closed): ?>
                <div class="close-day-form">
                    <h3 style="margin-top: 0; color: #333;">End of Day</h3>
                    <p style="color: #666; margin-bottom: 20px;">Closing today's income will lock the balance and mark the day as completed.</p>
                    <button type="button" class="close-day-btn" id="openCloseModalBtn">Close Today Income</button>
                </div>
            <?php else: ?>
                <div class="close-day-form" style="border-color: #c3e6cb; background: #d4edda;">
                    <h3 style="margin-top: 0; color: #155724;">Day Closed</h3>
                    <p style="color: #155724; margin-bottom: 0;">Today's income was closed with a balance of <strong>₹ <?= number_format($closing_balance ?: 0, 2) ?></strong></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- The Modal -->
    <div id="closeIncomeModal" class="close-day-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Daywise Closing Income</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 15px; text-align: center; background: #f1f5f9; padding: 8px; border-radius: 4px;">
                    <i class="fa-solid fa-clock"></i> Note: Income automatically closes at <strong>12:00 AM (IST)</strong> if not closed manually.
                </div>
                <div class="modal-row">
                    <span>Opening Balance:</span>
                    <span>₹ <?= number_format($opening_balance ?: 0, 2) ?></span>
                </div>
                <div class="modal-row">
                    <span>Total Jobcards Income:</span>
                    <span>+ ₹ <?= number_format($totalJobcardPaid, 2) ?></span>
                </div>
                <div class="modal-row">
                    <span>Total Sales Income:</span>
                    <span>+ ₹ <?= number_format($totalSalesPaid, 2) ?></span>
                </div>
                <div class="modal-row">
                    <span>Total Purchases Paid:</span>
                    <span style="color:#dc3545;">- ₹ <?= number_format($totalPurchasePaid, 2) ?></span>
                </div>
                <div class="modal-row total">
                    <span>Final Closing Balance:</span>
                    <span>₹ <?= number_format($current_closing_balance, 2) ?></span>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" id="closeDayForm">
                    <input type="hidden" name="closing_amount" value="<?= $current_closing_balance ?>">
                    <button type="button" class="close-modal" style="font-size: 14px; background: none; border: none; padding: 10px; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="close_day" style="background: #16a34a; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">Confirm Close</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        var modal = document.getElementById("closeIncomeModal");
        var btn = document.getElementById("openCloseModalBtn");
        var spans = document.getElementsByClassName("close-modal");

        if (btn) {
            btn.onclick = function() {
                modal.style.display = "block";
            }
        }

        for (var i = 0; i < spans.length; i++) {
            spans[i].onclick = function() {
                modal.style.display = "none";
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
