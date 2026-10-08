<?php
/**
 * Public Purchase Order Viewer & PDF Downloader
 * Sunder Machines World ERP
 * Allows suppliers to view and download the exact official Purchase Order PDF.
 */
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$purchaseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$autoDownload = isset($_GET['download']) && $_GET['download'] === '1';

if ($purchaseId <= 0) {
    http_response_code(400);
    echo "<div style='font-family:Arial,sans-serif; text-align:center; padding:60px 20px;'><h2>Invalid Purchase Order ID</h2></div>";
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
    http_response_code(404);
    echo "<div style='font-family:Arial,sans-serif; text-align:center; padding:60px 20px;'><h2>Purchase Order Not Found</h2></div>";
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - <?= htmlspecialchars($purchase['orderNo']) ?></title>
    <meta property="og:title" content="📥 DOWNLOAD PDF FILE - <?= htmlspecialchars($purchase['orderNo']) ?>">
    <meta property="og:description" content="Sunder Machines World - Tap to download the official Purchase Order PDF">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="../img/logo.png">
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

        .action-banner {
            max-width: 860px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .action-banner-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .action-banner-btns {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-download {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-download:hover {
            background: #15803d;
        }

        .btn-print {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-print:hover {
            opacity: 0.95;
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
            margin-bottom: 0;
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

        @media (max-width: 640px) {
            body {
                padding: 10px;
            }

            .invoice-paper {
                padding: 20px 15px;
            }

            .action-banner {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .action-banner-info {
                justify-content: center;
            }

            .action-banner-btns {
                justify-content: center;
            }

            .items-table th,
            .items-table td {
                padding: 8px 6px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="no-print action-banner">
        <div class="action-banner-info">
            <span style="font-size:20px;">📄</span>
            <span>Purchase Order: <strong><?= htmlspecialchars($purchase['orderNo']) ?></strong></span>
        </div>
        <div class="action-banner-btns">
            <button type="button" onclick="downloadPoPdf()" class="btn-action btn-download" id="btnDownload">
                📥 Download PDF
            </button>
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                🖨️ Print
            </button>
        </div>
    </div>

    <div class="invoice-paper" id="invoicePaper">
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
    var isAutoDownload = <?= json_encode($autoDownload) ?>;
    var orderNo = <?= json_encode($purchase['orderNo']) ?>;
    var fileName = 'Purchase_Order_' + orderNo + '.pdf';

    function downloadPoPdf() {
        var btn = document.getElementById('btnDownload');
        if (btn) {
            btn.innerText = '⏳ Generating PDF...';
            btn.disabled = true;
        }

        var element = document.getElementById('invoicePaper');
        var opt = {
            margin: [10, 10, 10, 10],
            filename: fileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        if (typeof html2pdf !== 'undefined') {
            html2pdf().set(opt).from(element).save().then(function() {
                if (btn) {
                    btn.innerText = '✅ Downloaded';
                    setTimeout(function() {
                        btn.innerText = '📥 Download PDF';
                        btn.disabled = false;
                    }, 3000);
                }
            }).catch(function(err) {
                if (btn) {
                    btn.innerText = '📥 Download PDF';
                    btn.disabled = false;
                }
                window.print();
            });
        } else {
            window.print();
        }
    }

    if (isAutoDownload) {
        window.addEventListener('load', function() {
            setTimeout(downloadPoPdf, 600);
        });
    }
    </script>
</body>
</html>
