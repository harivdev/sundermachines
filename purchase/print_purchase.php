<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$purchaseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($purchaseId <= 0) {
    echo "Invalid Purchase ID";
    exit();
}

$pQuery = "
    SELECT p.*, s.name as supplier_name, s.phoneNo1, a.line1, a.city
    FROM purchase p
    LEFT JOIN supplier s ON p.supplier = s.id
    LEFT JOIN address a ON s.address = a.id
    WHERE p.id = $purchaseId
";
$pRes = mysqli_query($conn, $pQuery);
if (!$pRes || mysqli_num_rows($pRes) == 0) {
    echo "Purchase Order not found";
    exit();
}
$purchase = mysqli_fetch_assoc($pRes);

$iQuery = "
    SELECT pi.*, 
           COALESCE(NULLIF(pi.itemName, ''), sp.spareName, 'Item') AS displayItemName,
           COALESCE(b.brandName, (SELECT b2.brandName FROM stock st LEFT JOIN brand b2 ON st.brand = b2.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1), '-') AS displayBrand,
           COALESCE(m.model, (SELECT m2.model FROM stock st LEFT JOIN model m2 ON st.model = m2.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1), '-') AS displayModel
    FROM purchaseitems pi 
    LEFT JOIN spares sp ON pi.spare = sp.id 
    LEFT JOIN brand b ON pi.brand = b.id 
    LEFT JOIN model m ON pi.model = m.id 
    WHERE pi.purchase = $purchaseId AND (pi.deleted = 0 OR pi.deleted IS NULL)
    ORDER BY pi.id ASC
";
$iRes = mysqli_query($conn, $iQuery);
$items = [];
while ($row = mysqli_fetch_assoc($iRes)) {
    $items[] = $row;
}

$isDelivered = in_array($purchase['orderStatus'], ['Delivered', 'Received', 'Completed']);
$deliveryDateDisplay = $isDelivered 
    ? (!empty($purchase['modifiedOn']) ? date('d M Y', strtotime($purchase['modifiedOn'])) : date('d M Y', strtotime($purchase['orderDate'])))
    : date('d M Y', strtotime($purchase['orderDate']));

// Public PO viewing link for WhatsApp (does NOT require login)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
    (strpos($host, 'sundermachines123.site.je') !== false)
);
$protocol = $isHttps ? 'https' : 'http';
$baseDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$publicPoUrl = "{$protocol}://{$host}{$baseDir}/view_order.php?id={$purchaseId}";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Order - <?= htmlspecialchars($purchase['orderNo']) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f8fafc;
        }

        .invoice-paper {
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        @page {
            size: auto;
            margin: 0mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .invoice-paper {
                max-width: 100% !important;
                padding: 12mm 15mm !important;
                margin: 0 auto !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }

        .print-toolbar {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.15s ease;
        }

        .btn-print:hover {
            opacity: 0.95;
        }

        .btn-whatsapp {
            background: #16a34a;
        }

        .btn-back {
            background: #64748b;
        }

        /* WhatsApp Hover Dropdown */
        .whatsapp-dropdown {
            position: relative;
            display: inline-block;
        }

        .whatsapp-dropdown .btn-whatsapp {
            background: #16a34a;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .whatsapp-dropdown:hover .btn-whatsapp {
            background: #15803d;
        }

        .whatsapp-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 4px;
            background: #ffffff;
            min-width: 180px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            overflow: hidden;
            z-index: 1000;
        }

        .whatsapp-dropdown:hover .whatsapp-menu,
        .whatsapp-dropdown:focus-within .whatsapp-menu {
            display: block;
        }

        .whatsapp-menu-item {
            width: 100%;
            display: block;
            padding: 11px 18px;
            color: #1e293b;
            text-align: left;
            background: #ffffff;
            border: none;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .whatsapp-menu-item:last-child {
            border-bottom: none;
        }

        .whatsapp-menu-item:hover {
            background: #f0fdf4;
            color: #16a34a;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .po-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ccc;
            padding: 10px 12px;
            text-align: left;
            font-size: 14px;
        }

        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            margin-top: 15px;
            background: #fbfbfb;
        }

        .summary-box td {
            padding: 12px 18px;
            font-size: 15px;
        }
    </style>
</head>

<body>
    <div class="no-print print-toolbar">
        <button onclick="window.print()" class="btn-print">🖨️ Print Purchase Order</button>
        <div class="whatsapp-dropdown">
            <button type="button" class="btn-print btn-whatsapp">
                Send to WhatsApp
                <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
            </button>
            <div class="whatsapp-menu">
                <button type="button" onclick="sendWhatsAppTextMessage()" class="whatsapp-menu-item">
                    Send as Message
                </button>
                <button type="button" onclick="sendWhatsAppPdfOption()" class="whatsapp-menu-item">
                    Send as PDF
                </button>
            </div>
        </div>
        <button onclick="window.history.back()" class="btn-print btn-back">⬅️ Back</button>
    </div>

    <div class="invoice-paper">
        <div class="header">
        <h1>SUNDER MACHNES WORLD</h1>
        <p>4, Sunder Towers, Near Bus Stand, Gobi - 638 476</p>
        <p>Ph: 04285-224176 | Cell: +91 98433 61326</p>
    </div>

    <div class="po-title">PURCHASE ORDER</div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Supplier:</strong><br>
                <?= htmlspecialchars($purchase['supplier_name']) ?><br>
                <?= htmlspecialchars($purchase['line1']) ?><br>
                <?= htmlspecialchars($purchase['city']) ?><br>
                Phone: <?= htmlspecialchars($purchase['phoneNo1'] ?: 'N/A') ?>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table style="width: auto; margin-left: auto; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 2px 5px 2px 0;"><strong>Order No:</strong></td>
                        <td style="padding: 2px 0;"><?= htmlspecialchars($purchase['orderNo']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 5px 2px 0;"><strong>Date:</strong></td>
                        <td style="padding: 2px 0;"><?= date('d M Y', strtotime($purchase['orderDate'])) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 5px 2px 0;"><strong>Status:</strong></td>
                        <td style="padding: 2px 0;"><?= htmlspecialchars($purchase['orderStatus']) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table" style="margin-bottom: 0;">
        <thead>
            <tr>
                <th class="text-center" style="width: 6%;">S.No</th>
                <th style="width: 34%;">Item Name</th>
                <th style="width: 18%;">Brand</th>
                <th style="width: 18%;">Model</th>
                <th class="text-center" style="width: 10%;">Quantity</th>
                <th style="width: 14%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($items as $itm): 
                $dItemName = !empty($itm['displayItemName']) ? $itm['displayItemName'] : (!empty($itm['spareName']) ? $itm['spareName'] : 'Item');
                $dBrand = !empty($itm['displayBrand']) ? $itm['displayBrand'] : '-';
                $dModel = !empty($itm['displayModel']) ? $itm['displayModel'] : '-';
                $dRemarks = !empty($itm['remarks']) ? $itm['remarks'] : '-';
            ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($dItemName) ?></td>
                    <td><?= htmlspecialchars($dBrand) ?></td>
                    <td><?= htmlspecialchars($dModel) ?></td>
                    <td class="text-center" style="font-weight: 700;"><?= intval($itm['orderedQuantity']) ?></td>
                    <td><?= htmlspecialchars($dRemarks) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="summary-box">
        <tr>
            <td style="width: 50%;">
                <strong>Total Items:</strong> <?= count($items) ?>
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Delivery Date:</strong> <?= $deliveryDateDisplay ?>
            </td>
        </tr>
    </table>
    </div> <!-- /.invoice-paper -->

    <script>
    var lastWhatsAppUrl = '';

    function getSupplierWaNum() {
        var phone = <?= json_encode($purchase['phoneNo1'] ?? '') ?>;
        var waNum = (phone || '').replace(/[^0-9]/g, '');
        if (waNum.length === 10) waNum = '91' + waNum;
        if (!waNum) {
            alert('Supplier phone number is not available.');
            return null;
        }
        return waNum;
    }

    function sendWhatsAppTextMessage() {
        var waNum = getSupplierWaNum();
        if (!waNum) return;

        var lines = [];
        lines.push('*SUNDER MACHINES WORLD*');
        lines.push('4, Sunder Towers, Near Bus Stand, Gobi - 638 476');
        lines.push('Ph: 04285-224176 | Cell: +91 98433 61326');
        lines.push('──────────────────────────────');
        lines.push('*PURCHASE ORDER*');
        lines.push('Order No: ' + <?= json_encode($purchase['orderNo']) ?>);
        lines.push('Date: ' + <?= json_encode(date('d M Y', strtotime($purchase['orderDate']))) ?>);
        lines.push('Delivery Date: ' + <?= json_encode($deliveryDateDisplay) ?>);
        lines.push('Supplier: ' + <?= json_encode($purchase['supplier_name']) ?>);
        lines.push('──────────────────────────────');
        lines.push('*ORDER ITEMS:*');

        <?php foreach ($items as $idx => $itm): 
            $dItem = !empty($itm['displayItemName']) ? $itm['displayItemName'] : (!empty($itm['spareName']) ? $itm['spareName'] : 'Item');
            $dBr = !empty($itm['displayBrand']) ? $itm['displayBrand'] : '-';
            $dMo = !empty($itm['displayModel']) ? $itm['displayModel'] : '-';
            $dQty = intval($itm['orderedQuantity']);
            $dRem = !empty($itm['remarks']) ? $itm['remarks'] : '-';
        ?>
        lines.push('<?= ($idx + 1) ?>. *<?= addslashes($dItem) ?>*');
        lines.push('   Brand: <?= addslashes($dBr) ?> | Model: <?= addslashes($dMo) ?>');
        lines.push('   Quantity: <?= $dQty ?> | Remarks: <?= addslashes($dRem) ?>');
        <?php endforeach; ?>

        lines.push('──────────────────────────────');
        lines.push('──────────────────────────────');
        lines.push('*Total Items:* <?= count($items) ?>');
        lines.push('*Delivery Date:* ' + <?= json_encode($deliveryDateDisplay) ?>);
        lines.push('──────────────────────────────');
        lines.push('📥 *DOWNLOAD PDF FILE:*');
        lines.push('👉 ' + <?= json_encode($publicPoUrl) ?>);
        lines.push('──────────────────────────────');

        var msg = lines.join('\n');
        var url = 'https://api.whatsapp.com/send?phone=' + waNum + '&text=' + encodeURIComponent(msg);
        lastWhatsAppUrl = url;
        window.open(url, '_blank');
    }

    function showToast(msg) {
        var toast = document.getElementById('poShareToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'poShareToast';
            toast.style.cssText = 'position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#0f172a; color:#fff; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; box-shadow:0 10px 25px rgba(0,0,0,0.3); z-index:99999; display:flex; align-items:center; gap:8px; transition:all 0.3s ease; text-align:center; max-width:90%;';
            document.body.appendChild(toast);
        }
        toast.innerText = msg;
        toast.style.opacity = '1';
        toast.style.display = 'flex';
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.style.display = 'none'; }, 300);
        }, 5000);
    }

    function sendWhatsAppPdfOption() {
        var waNum = getSupplierWaNum();
        if (!waNum) return;

        var orderNo = <?= json_encode($purchase['orderNo']) ?>;
        var fileName = 'Purchase_Order_' + orderNo + '.pdf';

        showToast('⏳ Generating Purchase Order PDF...');

        var element = document.querySelector('.invoice-paper');
        var opt = {
            margin: [10, 10, 10, 10],
            filename: fileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // WhatsApp message with direct public PDF viewer & downloader link
        var lines = [];
        lines.push('*SUNDER MACHINES WORLD*');
        lines.push('*PURCHASE ORDER: ' + orderNo + '*');
        lines.push('Date: ' + <?= json_encode(date('d M Y', strtotime($purchase['orderDate']))) ?>);
        lines.push('Delivery Date: ' + <?= json_encode($deliveryDateDisplay) ?>);
        lines.push('Supplier: ' + <?= json_encode($purchase['supplier_name']) ?>);
        lines.push('Total Items: ' + <?= count($items) ?>);
        lines.push('──────────────────────────────');
        lines.push('📥 *DOWNLOAD PDF FILE:*');
        lines.push('👉 ' + <?= json_encode($publicPoUrl) ?>);
        lines.push('──────────────────────────────');

        var waUrl = 'https://api.whatsapp.com/send?phone=' + waNum + '&text=' + encodeURIComponent(lines.join('\n'));
        lastWhatsAppUrl = waUrl;

        if (typeof html2pdf !== 'undefined') {
            try {
                var worker = html2pdf().set(opt).from(element);

                // 1. Download the exact PDF directly to the browser
                worker.save().then(function() {
                    window.open(waUrl, '_blank');
                    showToast('📥 ' + fileName + ' downloaded. Ready to send in WhatsApp chat!');
                }).catch(function(err) {
                    window.open(waUrl, '_blank');
                });

                // 2. Native Share API (attaches PDF directly on Mobile & supported systems)
                worker.output('blob').then(function(pdfBlob) {
                    if (pdfBlob && navigator.canShare) {
                        var shareFile = new File([pdfBlob], fileName, { type: 'application/pdf' });
                        if (navigator.canShare({ files: [shareFile] })) {
                            navigator.share({
                                files: [shareFile],
                                title: 'Purchase Order - ' + orderNo,
                                text: 'Purchase Order: ' + orderNo
                            }).catch(function() {});
                        }
                    }
                }).catch(function() {});

            } catch (err) {
                window.open(waUrl, '_blank');
            }
        } else {
            window.open(waUrl, '_blank');
        }
    }

    function downloadPdfDirect() {
        var orderNo = <?= json_encode($purchase['orderNo']) ?>;
        var fileName = 'Purchase_Order_' + orderNo + '.pdf';
        var element = document.querySelector('.invoice-paper');
        var opt = {
            margin: [10, 10, 10, 10],
            filename: fileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        showToast('⏳ Generating Purchase Order PDF...');
        if (typeof html2pdf !== 'undefined') {
            html2pdf().set(opt).from(element).save().then(function() {
                showToast('✅ Downloaded: ' + fileName);
            }).catch(function(err) {
                window.print();
            });
        } else {
            window.print();
        }
    }

    function shareOnWhatsApp() {
        sendWhatsAppTextMessage();
    }
    </script>
</body>

</html>