<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "Access denied. Please log in.";
    exit();
}

$salesId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($salesId <= 0) {
    echo "Invalid Sales ID";
    exit();
}

// Fetch Sales Order
$sQuery = "
    SELECT s.*, c.name as customer_name, c.phoneNo1, c.whatsAppNo 
    FROM sales s 
    LEFT JOIN customer c ON s.customer = c.id 
    WHERE s.id = $salesId
";
$sRes = mysqli_query($conn, $sQuery);
if (!$sRes || mysqli_num_rows($sRes) == 0) {
    echo "Sales Order not found";
    exit();
}
$sale = mysqli_fetch_assoc($sRes);

// Fetch Sales Items
$iQuery = "SELECT * FROM salesitems WHERE sales = $salesId AND deleted = 0";
$iRes = mysqli_query($conn, $iQuery);
$items = [];
while ($row = mysqli_fetch_assoc($iRes)) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Bill - <?= htmlspecialchars($sale['orderNo']) ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            width: 78mm;
            margin: 0 auto;
            padding: 5mm 2mm;
            color: #000;
            background: #fff;
            font-size: 11px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header .sub-title {
            font-size: 10px;
            text-align: right;
            margin-bottom: 4px;
        }
        .header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 1px 0;
            font-size: 10px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: 10.5px;
        }
        th {
            border-bottom: 1px dashed #000;
            padding: 3px 0;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 3px 0;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
        }
        @media print {
            body { width: 78mm; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="sub-title">Sales</div>
        <h2>SUNDER MACHINES WORKS</h2>
        <p>4, Sunder Towers, Near Bus Stand.</p>
        <p>Gobi - 638 476</p>
        <p>Ph: 04285-224176 &nbsp; Cell:+91 98433 61326</p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Order #:<?= htmlspecialchars($sale['orderNo']) ?></span>
        <span>Date :<?= date('d/m/Y', strtotime($sale['orderDate'])) ?></span>
    </div>
    <div class="info-row">
        <span>Name &nbsp;:<?= htmlspecialchars($sale['customer_name'] ?: 'CASH BILL') ?></span>
        <span>Phone #:<?= htmlspecialchars($sale['phoneNo1'] ?: '—') ?></span>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width:55%;">Particular</th>
                <th class="text-center" style="width:15%;">Qty</th>
                <th class="text-right" style="width:30%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $itm): ?>
                <tr>
                    <td><?= htmlspecialchars($itm['itemName']) ?></td>
                    <td class="text-center"><?= intval($itm['quantity']) ?></td>
                    <td class="text-right"><?= number_format(round($itm['totalPrice']), 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals-row">
        <span style="width:60%; text-align:right;">Total :</span>
        <span style="width:40%; text-align:right;"><?= number_format(round($sale['actualAmountSum']), 0) ?></span>
    </div>
    <div class="totals-row">
        <span style="width:60%; text-align:right;">Paid :</span>
        <span style="width:40%; text-align:right;"><?= number_format(round($sale['paidAmountSum']), 0) ?></span>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>* Goods Cannot Be Returned *</p>
        <p>* Thank You *</p>
    </div>

    <div class="divider"></div>

    <div class="footer" style="margin-top:4px;">
        <p>* Visit Again *</p>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 400);
        };
    </script>
</body>
</html>
