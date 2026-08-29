<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "Access denied. Please log in.";
    exit();
}

$today = date('Y-m-d');
$range = isset($_GET['range']) ? trim($_GET['range']) : '15';
$format = isset($_GET['format']) ? trim($_GET['format']) : 'pdf';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$fromDate = '';
$toDate = '';
$rangeLabel = '';

if ($range === '15') {
    $fromDate = date('Y-m-d', strtotime('-15 days'));
    $toDate = $today;
    $rangeLabel = "Last 15 Days (" . date('d/m/Y', strtotime($fromDate)) . " to " . date('d/m/Y', strtotime($toDate)) . ")";
} else if ($range === '30') {
    $fromDate = date('Y-m-d', strtotime('-30 days'));
    $toDate = $today;
    $rangeLabel = "Last 30 Days (" . date('d/m/Y', strtotime($fromDate)) . " to " . date('d/m/Y', strtotime($toDate)) . ")";
} else if ($range === 'custom') {
    $rawFrom = !empty($_GET['fromDate']) ? trim($_GET['fromDate']) : date('Y-m-d', strtotime('-15 days'));
    $rawTo = !empty($_GET['toDate']) ? trim($_GET['toDate']) : $today;
    
    $fromDate = date('Y-m-d', strtotime($rawFrom));
    $toDate = date('Y-m-d', strtotime($rawTo));
    $rangeLabel = "Custom Range (" . date('d/m/Y', strtotime($fromDate)) . " to " . date('d/m/Y', strtotime($toDate)) . ")";
} else {
    $rangeLabel = "All Sales Orders";
}

$where = "WHERE 1=1";
if (!empty($fromDate) && !empty($toDate)) {
    $safeFrom = mysqli_real_escape_string($conn, $fromDate);
    $safeTo = mysqli_real_escape_string($conn, $toDate);
    $where .= " AND s.orderDate >= '$safeFrom' AND s.orderDate <= '$safeTo'";
}
if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.orderNo LIKE '%$s%' OR c.name LIKE '%$s%')";
}
if (!empty($statusFilter)) {
    $st = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND s.orderStatus = '$st'";
}

$query = "
    SELECT s.*, c.name AS customer_name, c.phoneNo1 
    FROM sales s 
    LEFT JOIN customer c ON s.customer = c.id 
    $where 
    ORDER BY s.orderDate DESC, s.id DESC
";
$res = mysqli_query($conn, $query);
$sales = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $sales[] = $r;
    }
}

// ── Export CSV Format ──
if ($format === 'csv') {
    $filename = "Sales_Report_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Title info
    fputcsv($output, ['Sunder Billing - Sales Report Summary']);
    fputcsv($output, ['Range:', $rangeLabel]);
    fputcsv($output, ['Generated On:', date('d/m/Y H:i:s')]);
    fputcsv($output, []);

    // Header row
    fputcsv($output, ['S.No', 'Order Date', 'Order No', 'Customer Name', 'Contact Phone', 'Status', 'Billed Amount (INR)', 'Paid Amount (INR)', 'Balance Amount (INR)']);

    $i = 1;
    $totBilled = 0;
    $totPaid = 0;
    $totBal = 0;

    foreach ($sales as $row) {
        $billed = floatval($row['actualAmountSum']);
        $paid = floatval($row['paidAmountSum']);
        $bal = max(0, $billed - $paid);

        $totBilled += $billed;
        $totPaid += $paid;
        $totBal += $bal;

        fputcsv($output, [
            $i++,
            date('d/m/Y', strtotime($row['orderDate'])),
            $row['orderNo'],
            $row['customer_name'] ?: 'CASH BILL',
            $row['phoneNo1'] ?: '-',
            $row['orderStatus'] ?: 'New',
            number_format($billed, 2, '.', ''),
            number_format($paid, 2, '.', ''),
            number_format($bal, 2, '.', '')
        ]);
    }

    // Total summary row
    fputcsv($output, []);
    fputcsv($output, ['', '', '', 'Total Summary', '', '', number_format($totBilled, 2, '.', ''), number_format($totPaid, 2, '.', ''), number_format($totBal, 2, '.', '')]);

    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Summary Report - Sunder ERP</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
            padding: 12px;
            font-size: 12px;
        }
        .hdr {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .hdr h2 {
            font-size: 18px;
            margin: 0;
        }
        .hdr div {
            font-size: 12px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            font-size: 11.5px;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }
        td.center, th.center { text-align: center; }
        td.right, th.right { text-align: right; }
        tfoot td { font-weight: bold; background: #f9f9f9; }
        
        .no-print-bar {
            background: #0d6efd;
            color: #fff;
            padding: 10px 16px;
            margin: -12px -12px 14px -12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-p {
            background: #fff; color: #0d6efd; border: none; padding: 6px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;
        }
        @media print {
            .no-print-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <span>📄 <strong>Sales Report Summary</strong> (<?= htmlspecialchars($rangeLabel) ?>)</span>
        <button class="btn-p" onclick="window.print()">🖨️ Print / Save PDF</button>
    </div>

    <div class="hdr">
        <div>
            <h2>Sunder Billing - Sales Report</h2>
            <div style="margin-top:4px; font-weight:bold; color:#555;"><?= htmlspecialchars($rangeLabel) ?></div>
        </div>
        <div style="text-align:right;">
            <div>Generated: <?= date('d/m/Y H:i') ?></div>
            <div>Total Orders: <?= count($sales) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:35px;">S.No</th>
                <th class="center" style="width:85px;">Date</th>
                <th class="center" style="width:100px;">Order #</th>
                <th>Customer Name</th>
                <th class="center" style="width:80px;">Status</th>
                <th class="right" style="width:90px;">Billed Amt</th>
                <th class="right" style="width:90px;">Paid Amt</th>
                <th class="right" style="width:90px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (count($sales)):
                $i = 1;
                $totBilled = 0; $totPaid = 0; $totBal = 0;
                foreach ($sales as $row):
                    $billed = floatval($row['actualAmountSum']);
                    $paid = floatval($row['paidAmountSum']);
                    $bal = max(0, $billed - $paid);
                    $totBilled += $billed;
                    $totPaid += $paid;
                    $totBal += $bal;
            ?>
                <tr>
                    <td class="center"><?= $i++ ?></td>
                    <td class="center"><?= date('d/m/Y', strtotime($row['orderDate'])) ?></td>
                    <td class="center" style="font-weight:bold;"><?= htmlspecialchars($row['orderNo']) ?></td>
                    <td><?= htmlspecialchars($row['customer_name'] ?: 'CASH BILL') ?></td>
                    <td class="center"><?= htmlspecialchars($row['orderStatus'] ?: 'New') ?></td>
                    <td class="right"><?= number_format($billed, 2) ?></td>
                    <td class="right"><?= number_format($paid, 2) ?></td>
                    <td class="right"><?= number_format($bal, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="right">Total Summary:</td>
                <td class="right"><?= number_format($totBilled, 2) ?></td>
                <td class="right"><?= number_format($totPaid, 2) ?></td>
                <td class="right"><?= number_format($totBal, 2) ?></td>
            </tr>
        </tfoot>
        <?php else: ?>
            <tr>
                <td colspan="8" class="center" style="padding:20px;">No sales orders found for the selected range.</td>
            </tr>
        <?php endif; ?>
    </table>

</body>
</html>
