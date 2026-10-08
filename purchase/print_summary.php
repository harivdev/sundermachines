<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$where = "WHERE 1=1";

if (!empty($_GET['status'])) {
    $safeStatus = mysqli_real_escape_string($conn, $_GET['status']);
    if ($safeStatus === 'Delivered') {
        $where .= " AND p.orderStatus IN ('Delivered', 'Received', 'Completed')";
    } else if ($safeStatus === 'Purchased') {
        $where .= " AND p.orderStatus IN ('Purchased', 'Ordered', 'New')";
    } else {
        $where .= " AND p.orderStatus = '$safeStatus'";
    }
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where .= " AND (p.orderNo LIKE '%$search%' OR s.name LIKE '%$search%' OR s.phoneNo1 LIKE '%$search%')";
}

$query = "
    SELECT
        p.id,
        p.orderNo,
        p.orderDate,
        p.orderStatus,
        p.actualAmountSum,
        p.paidAmountSum,
        p.modifiedOn,
        s.name AS supplierName,
        s.phoneNo1
    FROM purchase p
    LEFT JOIN supplier s ON p.supplier = s.id
    $where
    ORDER BY p.id DESC
";

$res = mysqli_query($conn, $query);
$purchases = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $purchases[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Summary - Sunder Machines</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 10mm;
            font-size: 11px;
            line-height: 1.2;
        }
        .header-box {
            margin-bottom: 15px;
        }
        .company-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 3px 0;
            color: #000;
            letter-spacing: 0.5px;
        }
        .page-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #1e293b;
        }
        .meta-subtitle {
            font-size: 10.5px;
            color: #475569;
        }
        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            font-size: 11px;
        }
        table.summary-table th {
            background: #d1d5db;
            color: #000;
            font-weight: bold;
            text-align: center;
            padding: 6px 8px;
            border: 1px solid #000;
            font-size: 11px;
        }
        table.summary-table td {
            padding: 5px 8px;
            border: 1px solid #000;
            vertical-align: middle;
            font-size: 11px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            🖨️ Print A4 Summary
        </button>
    </div>

    <div class="header-box">
        <div class="company-title">SUNDER MACHINES</div>
        <div class="page-title">Purchase Order Summary</div>
        <div class="meta-subtitle">
            Generated: <?= date('d/m/Y H:i') ?> | Total Records: <?= count($purchases) ?>
            <?php if (!empty($_GET['status'])): ?>
                | Status: <strong><?= htmlspecialchars($_GET['status']) ?></strong>
            <?php endif; ?>
        </div>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 5%;">S.No</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 16%;">Order #</th>
                <th style="width: 24%;">Supplier Name</th>
                <th style="width: 13%;">Contact #</th>
                <th style="width: 10%;">Order Dt</th>
                <th style="width: 10%;">Deliv Dt</th>
                <th style="width: 10%;">Total Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($purchases)):
                $sno = 1;
                $grandTotal = 0;
                foreach ($purchases as $row):
                    $rawSt = $row['orderStatus'] ?? 'Purchased';
                    $isDelivered = in_array($rawSt, ['Delivered', 'Received', 'Completed']);
                    $statusDisp = $isDelivered ? 'Delivered' : 'Purchased';

                    $orderDt = (!empty($row['orderDate']) && $row['orderDate'] !== '0000-00-00')
                        ? date('d/m/Y', strtotime($row['orderDate']))
                        : '-';

                    $delivDtRaw = $isDelivered
                        ? (!empty($row['modifiedOn']) ? $row['modifiedOn'] : $row['orderDate'])
                        : '';
                    $delivDt = (!empty($delivDtRaw) && $delivDtRaw !== '0000-00-00')
                        ? date('d/m/Y', strtotime($delivDtRaw))
                        : '-';

                    $totalAmt = (float)($row['actualAmountSum'] ?? 0);
                    $grandTotal += $totalAmt;
            ?>
                <tr>
                    <td class="text-center font-bold"><?= $sno++ ?></td>
                    <td class="text-center font-bold"><?= $statusDisp ?></td>
                    <td class="text-center font-bold"><?= htmlspecialchars($row['orderNo'] ?? '-') ?></td>
                    <td class="text-left font-bold"><?= htmlspecialchars(strtoupper($row['supplierName'] ?? 'N/A')) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['phoneNo1'] ?? '-') ?></td>
                    <td class="text-center font-bold"><?= $orderDt ?></td>
                    <td class="text-center font-bold"><?= $delivDt ?></td>
                    <td class="text-right font-bold"><?= number_format($totalAmt, 2, '.', '') ?></td>
                </tr>
            <?php
                endforeach;
            ?>
                <tr style="background:#f1f5f9;">
                    <td colspan="7" class="text-right font-bold" style="padding:7px 8px; font-size:11.5px;">Grand Total:</td>
                    <td class="text-right font-bold" style="padding:7px 8px; font-size:11.5px;">₹<?= number_format($grandTotal, 2, '.', '') ?></td>
                </tr>
            <?php
            else:
            ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">No Purchase Order records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
