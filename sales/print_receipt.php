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
        @media print {
            .no-print { display: none !important; }
        }
        .print-toolbar {
            background: transparent !important;
            border: none !important;
            padding: 6px 0 !important;
            margin-bottom: 12px !important;
            text-align: center !important;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-print {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #fef3c7;
            color: #92400e;
            border: 1.5px solid #d97706;
            padding: 8px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(217, 119, 6, 0.15);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-print:hover {
            background: #fde68a;
            color: #78350f;
            border-color: #b45309;
        }
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #25D366;
            color: #fff;
            border: 1.5px solid #128C7E;
            padding: 8px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(37, 211, 102, 0.25);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-whatsapp:hover {
            background: #128C7E;
            color: #fff;
        }
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            width: 78mm;
            margin: 0 auto;
            padding: 4mm 2mm;
            color: #000;
            background: #fff;
            font-size: 11px;
            line-height: 1.35;
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
            font-size: 13.5px;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 1px 0;
            font-size: 9.5px;
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
    <div class="no-print print-toolbar">
        <a href="#" onclick="window.print(); return false;" class="btn-print">🖨️ Print</a>
        <?php 
        $saleWa = !empty($sale['whatsAppNo']) ? $sale['whatsAppNo'] : (!empty($sale['phoneNo1']) ? $sale['phoneNo1'] : '');
        if (!empty($saleWa)): 
        ?>
        <a href="#" onclick="sendWhatsAppReceipt(); return false;" class="btn-whatsapp" id="btnSendWhatsApp">🟢 Send WhatsApp</a>
        <?php else: ?>
        <a href="#" onclick="alert('No WhatsApp or phone number saved for this customer.'); return false;" class="btn-whatsapp" style="opacity:0.5;">🟢 Send WhatsApp</a>
        <?php endif; ?>
    </div>

    <script>
    function normalizeIndianWhatsApp(raw) {
        var digits = String(raw).replace(/\D/g, '');
        if (digits.length === 10) return '91' + digits;
        if (digits.length === 12 && digits.substring(0,2) === '91') return digits;
        if (digits.length === 11 && digits.charAt(0) === '0') return '91' + digits.substring(1);
        if (digits.length === 13 && digits.substring(0,3) === '091') return '91' + digits.substring(3);
        if (digits.length >= 11) return digits;
        return null;
    }

    function sendWhatsAppReceipt() {
        var rawNum = <?= json_encode($saleWa) ?>;
        var waNum = normalizeIndianWhatsApp(rawNum);
        if (!waNum) { alert('Invalid WhatsApp number for this customer.'); return; }

        var orderNo = <?= json_encode($sale['orderNo'] ?? '') ?>;
        var orderDate = <?= json_encode(date('d/m/Y', strtotime($sale['orderDate']))) ?>;
        var custName = <?= json_encode($sale['customer_name'] ?: 'CASH BILL') ?>;

        var lines = [];
        lines.push('🧾 *SUNDER MACHNES WORLD*');
        lines.push('─────────────────');
        lines.push('Order#: ' + orderNo);
        lines.push('Date: ' + orderDate);
        lines.push('Customer: ' + custName);
        lines.push('─────────────────');
        <?php foreach ($items as $itm): ?>
        lines.push(<?= json_encode(htmlspecialchars_decode($itm['itemName'])) ?> + ' × ' + <?= json_encode(intval($itm['quantity'])) ?> + ' = ₹' + <?= json_encode(number_format(round($itm['totalPrice']), 0)) ?>);
        <?php endforeach; ?>
        lines.push('─────────────────');
        lines.push('*Total: ₹' + <?= json_encode(number_format(round($sale['actualAmountSum']), 0)) ?> + '*');
        lines.push('*Paid:  ₹' + <?= json_encode(number_format(round($sale['paidAmountSum']), 0)) ?> + '*');
        <?php
        $balance = round($sale['actualAmountSum'] - $sale['paidAmountSum']);
        if ($balance > 0):
        ?>
        lines.push('*Balance: ₹' + <?= json_encode(number_format($balance, 0)) ?> + '*');
        <?php endif; ?>
        lines.push('─────────────────');
        lines.push('Thank you for your business!');
        lines.push('~ _Sunder Machnes World_');

        var msg = lines.join('\n');
        var url = 'https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank');
    }
    </script>

    <div class="header">
        <div class="sub-title">Sales</div>
        <h2>SUNDER MACHNES WORLD</h2>
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

</body>
</html>

