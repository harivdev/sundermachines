<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

$filter_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$filter_mode = isset($_GET['mode']) ? trim($_GET['mode']) : (isset($_GET['paymentMode']) ? trim($_GET['paymentMode']) : '');

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $month = $filter_month;
    $year = $filter_year;
    $mode_suffix = !empty($filter_mode) ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $filter_mode) : '';

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Monthly_Sales_Full_Report_$month-{$year}{$mode_suffix}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
}

if (!isset($_GET['export'])) {
    include("../includes/header.php");
}

$where_clauses = [
    "MONTH(s.orderDate) = '$filter_month'",
    "YEAR(s.orderDate) = '$filter_year'"
];

if (!empty($filter_mode)) {
    $safe_mode = mysqli_real_escape_string($conn, $filter_mode);
    if ($filter_mode === 'NetBanking' || $filter_mode === 'Net Banking' || $filter_mode === 'NB') {
        $where_clauses[] = "s.id IN (SELECT sales FROM payment WHERE mode IN ('NetBanking', 'Net Banking', 'NB') AND sales IS NOT NULL)";
    } elseif ($filter_mode === 'Cash') {
        $where_clauses[] = "(s.id IN (SELECT sales FROM payment WHERE mode = 'Cash' AND sales IS NOT NULL) OR s.id NOT IN (SELECT sales FROM payment WHERE sales IS NOT NULL))";
    } else {
        $where_clauses[] = "s.id IN (SELECT sales FROM payment WHERE mode = '$safe_mode' AND sales IS NOT NULL)";
    }
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$query = "
    SELECT
        s.id,
        s.orderNo,
        s.orderDate,
        s.orderStatus,
        s.actualAmountSum,
        s.paidAmountSum,
        c.name AS customer_name,
        c.phoneNo1 AS contact_no
    FROM sales s
    LEFT JOIN customer c ON s.customer = c.id
    $where_sql
    ORDER BY s.orderDate DESC, s.id DESC
";
$result = mysqli_query($conn, $query);

$rows = [];
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
}

// Fetch payment modes for retrieved rows
$paymentModes = [];
if (!empty($rows)) {
    $sIds = array_filter(array_map('intval', array_column($rows, 'id')));
    if (!empty($sIds)) {
        $idList = implode(',', $sIds);
        $pRes = mysqli_query($conn, "SELECT sales, mode FROM payment WHERE sales IN ($idList) AND mode IS NOT NULL AND mode != ''");
        if ($pRes) {
            while ($pRow = mysqli_fetch_assoc($pRes)) {
                $paymentModes[$pRow['sales']] = $pRow['mode'];
            }
        }
    }
}

$total_query = "
    SELECT
        COUNT(s.id) AS total_orders,
        SUM(s.actualAmountSum) AS total_sales,
        SUM(s.paidAmountSum) AS total_paid
    FROM sales s
    $where_sql
";
$total_res = mysqli_query($conn, $total_query);
$totals = ($total_res && $tRow = mysqli_fetch_assoc($total_res)) ? $tRow : [];

$total_orders = (int) ($totals['total_orders'] ?? 0);
$total_sales = (float) ($totals['total_sales'] ?? 0);
$total_paid = (float) ($totals['total_paid'] ?? 0);
$total_balance = $total_sales - $total_paid;
?>

<div class="erp-container">

    <?php if (!isset($_GET['export'])): ?>
        <div class="erp-header-bar no-print">
            <div class="erp-header-title">Monthly Sales Report</div>
            <div class="erp-header-actions" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <button type="button" onclick="document.getElementById('monthlySalesFilter').style.display = document.getElementById('monthlySalesFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">🔽 Filter</button>
                <button type="button" onclick="window.print()" style="background:#475569; color:#fff; border:none; font-weight:600; font-size:13px; padding:7px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; cursor:pointer; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2);">🖨️ PDF</button>
                <a href="?month=<?= urlencode($filter_month) ?>&year=<?= urlencode($filter_year) ?><?= !empty($filter_mode) ? '&mode=' . urlencode($filter_mode) : '' ?>&export=excel" style="background:#16a34a; color:#fff; border:none; font-weight:600; font-size:13px; padding:7px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">📊 Excel</a>
            </div>
        </div>

        <div id="monthlySalesFilter" class="no-print" style="background:#f8fafc; padding:16px 20px; border:1px solid #cbd5e1; border-radius:8px; margin-bottom:15px; box-sizing:border-box; display:<?= (!empty($filter_mode) || $filter_month != date('m') || $filter_year != date('Y')) ? 'block' : 'none' ?>;">
            <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
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
                    <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Month</label>
                    <select name="month" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; min-width:140px; box-sizing:border-box;">
                        <?php for ($m = 1; $m <= 12; $m++):
                            $mv = sprintf('%02d', $m); ?>
                            <option value="<?= $mv ?>" <?= $mv == $filter_month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Year</label>
                    <select name="year" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; min-width:110px; box-sizing:border-box;">
                        <?php for ($y = 3000; $y >= 2000; $y--): ?>
                            <option value="<?= $y ?>" <?= (int)$y === (int)$filter_year ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="submit" style="background:#2563eb; color:#fff; padding:8px 18px; border:none; border-radius:5px; font-weight:600; font-size:14px; cursor:pointer; height:38px; display:inline-flex; align-items:center; justify-content:center;">Apply</button>
                    <a href="monthly_sales.php" style="background:#f59e0b; color:#fff; padding:8px 18px; border-radius:5px; font-weight:600; font-size:14px; text-decoration:none; height:38px; display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box;">Clear</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="report-main-content" style="background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 12px 12px; padding:24px;">

        <div class="no-print" style="margin-bottom:20px;">
            <h2 style="margin:0; color:#1e293b; font-size:28px;">Sales Summary</h2>
        </div>

        <div class="sales-summary-grid no-print"
            style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:24px;">
            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Orders</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;"><?= $total_orders ?></div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Sales</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;">
                    ₹<?= number_format(round($total_sales)) ?></div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Collected Amount</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#16a34a; margin-top:6px;">
                    ₹<?= number_format(round($total_paid)) ?></div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Pending Balance</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#ef4444; margin-top:6px;">
                    ₹<?= number_format(round($total_balance)) ?></div>
            </div>
        </div>

        <!-- Print Header Only -->
        <div class="print-report-header">
            <h1 class="print-company-name">SUNDER MACHINES</h1>
            <div class="print-report-title">Monthly Sales Report</div>
            <div class="print-report-meta">
                Month: <?= date('F Y', mktime(0, 0, 0, (int)$filter_month, 1, (int)$filter_year)) ?><?= !empty($filter_mode) ? ' | Mode: ' . htmlspecialchars($filter_mode) : '' ?>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; border:1px solid #cbd5e1; white-space:nowrap;">
                <thead>
                    <tr style="background:#d1d5db; font-weight:700;">
                        <th style="border:1px solid #000; padding:8px;">S.No</th>
                        <th style="border:1px solid #000; padding:8px;">Status</th>
                        <th style="border:1px solid #000; padding:8px;">Date</th>
                        <th style="border:1px solid #000; padding:8px;">Order #</th>
                        <th style="border:1px solid #000; padding:8px;">Customer Name</th>
                        <th style="border:1px solid #000; padding:8px;">Contact #</th>
                        <th style="border:1px solid #000; padding:8px;">Mode</th>
                        <th style="border:1px solid #000; padding:8px;">Billed Amt</th>
                        <th style="border:1px solid #000; padding:8px;">Paid Amt</th>
                        <th style="border:1px solid #000; padding:8px;">Balance</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $customer_name = !empty($row['customer_name']) ? $row['customer_name'] : 'CASH BILL';
                            $contact_no = !empty($row['contact_no']) ? $row['contact_no'] : '-';
                            $billed_amt = (float) $row['actualAmountSum'];
                            $paid_amt = (float) $row['paidAmountSum'];
                            $balance_amt = $billed_amt - $paid_amt;
                            $status = !empty($row['orderStatus']) ? $row['orderStatus'] : 'New';
                            $row_bg = ($i % 2 == 0) ? '#f9fafb' : '#ffffff';

                            $pm = trim($paymentModes[$row['id']] ?? $row['paymentMode'] ?? '');
                            if (strcasecmp($pm, 'NetBanking') === 0 || strcasecmp($pm, 'Net Banking') === 0 || strcasecmp($pm, 'NB') === 0) {
                                $displayMode = 'Net Banking';
                            } elseif (!empty($pm)) {
                                $displayMode = htmlspecialchars($pm);
                            } elseif ($paid_amt > 0) {
                                $displayMode = 'Cash';
                            } else {
                                $displayMode = '<span style="color:#94a3b8; font-weight:600;">—</span>';
                            }
                            ?>
                            <tr style="background: <?= $row_bg ?>;">
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;"><?= $i++ ?></td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700; color:<?= ($status === 'Invoiced' || $status === 'Completed') ? '#16a34a' : '#f59e0b' ?>;">
                                    <?= htmlspecialchars($status) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;">
                                    <?= !empty($row['orderDate']) ? date('d/m/Y', strtotime($row['orderDate'])) : '-' ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700;">
                                    <?php if (!isset($_GET['export'])): ?>
                                        <a href="../sales/edit.php?id=<?= (int)$row['id'] ?>" style="color:#2563eb; text-decoration:underline; font-weight:700;" title="Open Sales Order"><?= htmlspecialchars($row['orderNo'] ?: '-') ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($row['orderNo'] ?: '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:600; word-break:break-word;">
                                    <?= htmlspecialchars($customer_name) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; word-break:break-word;">
                                    <?= htmlspecialchars($contact_no) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:600; color:#334155;">
                                    <?= $displayMode ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700;">
                                    <?= number_format(round($billed_amt), 0) ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:#16a34a;">
                                    <?= number_format(round($paid_amt), 0) ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:#ef4444; min-width:100px;">
                                    <?= number_format(round($balance_amt), 0) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10"
                                style="border:1px solid #cbd5e1; padding:24px; text-align:center; color:#64748b;">
                                No sales recorded for this month<?= !empty($filter_mode) ? " with payment mode '" . htmlspecialchars($filter_mode) . "'" : "" ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr style="background:#f8fafc;">
                        <td colspan="7"
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700;">
                            Grand Total
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700;">
                            <?= number_format(round($total_sales), 0) ?>
                        </td>
                        <td
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700; color:#16a34a;">
                            <?= number_format(round($total_paid), 0) ?>
                        </td>
                        <td
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700; color:#ef4444;">
                            <?= number_format(round($total_balance), 0) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if (!isset($_GET['export'])): ?>
    <?php include("../includes/footer.php"); ?>

    <style>
        @media (max-width: 768px) {
            .erp-header-bar {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }
            .erp-header-actions {
                flex-wrap: wrap !important;
                width: 100% !important;
                gap: 8px !important;
            }
            .erp-header-actions form {
                width: 100% !important;
                flex-wrap: wrap !important;
            }
            .erp-header-actions form select,
            .erp-header-actions form input {
                flex: 1 1 120px !important;
            }
            .sales-summary-grid {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
                margin-bottom: 20px !important;
            }
            .sales-stat-card {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 14px 18px !important;
            }
            .sales-stat-label {
                font-size: 13px !important;
                margin-bottom: 0 !important;
                flex-shrink: 0 !important;
            }
            .sales-stat-value {
                font-size: 22px !important;
                margin-top: 0 !important;
                text-align: right !important;
            }
        }

        .print-report-header {
            display: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .print-report-header {
                display: block !important;
                text-align: center !important;
                margin-bottom: 12px !important;
                border-bottom: 1.5px solid #000 !important;
                padding-bottom: 6px !important;
            }

            .print-company-name {
                margin: 0 !important;
                font-size: 20px !important;
                font-weight: 800 !important;
                letter-spacing: 0.5px !important;
                color: #000 !important;
                text-transform: uppercase !important;
            }

            .print-report-title {
                margin: 3px 0 0 0 !important;
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #000 !important;
            }

            .print-report-meta {
                margin: 3px 0 0 0 !important;
                font-size: 11px !important;
                color: #333 !important;
            }

            .topbar,
            .menu-container,
            .navbar,
            nav,
            .no-print,
            #monthlySalesFilter,
            .sales-summary-grid,
            .erp-header-bar,
            .btn,
            .link {
                display: none !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                font-size: 10px !important;
            }

            .report-main-content {
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .erp-container,
            .container,
            .content,
            .wrapper,
            .card {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            table {
                width: 100% !important;
                min-width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                page-break-inside: auto;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 9px !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
            }

            th:nth-child(1),
            td:nth-child(1) {
                width: 4% !important;
            }

            th:nth-child(2),
            td:nth-child(2) {
                width: 9% !important;
            }

            th:nth-child(3),
            td:nth-child(3) {
                width: 9% !important;
            }

            th:nth-child(4),
            td:nth-child(4) {
                width: 13% !important;
            }

            th:nth-child(5),
            td:nth-child(5) {
                width: 17% !important;
            }

            th:nth-child(6),
            td:nth-child(6) {
                width: 12% !important;
            }

            th:nth-child(7),
            td:nth-child(7) {
                width: 9% !important;
            }

            th:nth-child(8),
            td:nth-child(8) {
                width: 9% !important;
            }

            th:nth-child(9),
            td:nth-child(9) {
                width: 9% !important;
            }

            th:nth-child(10),
            td:nth-child(10) {
                width: 9% !important;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
<?php endif; ?>
