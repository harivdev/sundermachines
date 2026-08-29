<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$jobcardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($jobcardId <= 0) {
    echo "Invalid Job Card ID";
    exit();
}

// Fetch Job Card Details
$jcQuery = "
    SELECT 
        j.*, 
        c.name AS customer_name, 
        c.phoneNo1, 
        c.whatsAppNo 
    FROM jobcard j 
    LEFT JOIN customer c ON j.customer = c.id 
    WHERE j.id = $jobcardId
    LIMIT 1
";
$jcRes = mysqli_query($conn, $jcQuery);
if (!$jcRes || mysqli_num_rows($jcRes) == 0) {
    echo "Job Card not found";
    exit();
}
$jobcard = mysqli_fetch_assoc($jcRes);

$cleanCardNo = str_replace(['/', ' '], '', $jobcard['cardNo'] ?? '');

// Fetch Job Card Spares
$sQuery = "
    SELECT * 
    FROM jobcarditemspares 
    WHERE (jobCardItem = $jobcardId OR jobCardItem IN (SELECT id FROM jobcarditems WHERE jobCard = $jobcardId))
      AND deleted = 0
    ORDER BY createdOn ASC
";
$sRes = mysqli_query($conn, $sQuery);
$spares = [];
if ($sRes) {
    while ($row = mysqli_fetch_assoc($sRes)) {
        $spares[] = $row;
    }
}

// Format Prepared By
$preparedBy = !empty($_SESSION['username']) ? $_SESSION['username'] : (!empty($_SESSION['user_email']) ? $_SESSION['user_email'] : ($jobcard['createdBy'] ?? 'owner@Sunder.com'));

// Format Date & Time
$dateTimeRaw = !empty($jobcard['createdOn']) && $jobcard['createdOn'] !== '0000-00-00 00:00:00' 
    ? $jobcard['createdOn'] 
    : (!empty($jobcard['givenDate']) ? $jobcard['givenDate'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s'));
$formattedDateTime = date('d/m/Y h:i A', strtotime($dateTimeRaw));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card - <?= htmlspecialchars($cleanCardNo) ?></title>
    <style>
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
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 6px;
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
            font-size: 10px;
        }
        .divider {
            border-top: 1px dotted #000;
            margin: 5px 0;
        }
        .info-row {
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .info-row span.label {
            display: inline-block;
            width: 70px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: 10.5px;
        }
        th {
            border-bottom: 1px dotted #000;
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
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 2px;
        }
        .footer-notice {
            text-align: center;
            font-size: 10px;
            margin-top: 6px;
        }
        .footer-notice p {
            margin: 2px 0;
        }
        @media print {
            body { width: 78mm; padding: 2mm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>SUNDER MACHINES WORKS</h2>
        <p>4, Sunder Towers, Near Bus Stand.</p>
        <p>Gobi - 638 476</p>
        <p>Ph: 04285-224176 &nbsp; Cell:+91 98433 61326</p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <strong>Job #: <?= htmlspecialchars($cleanCardNo) ?></strong>
    </div>
    <div class="info-row">
        Date & Time: <?= htmlspecialchars($formattedDateTime) ?>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span class="label">Name</span>: <?= htmlspecialchars($jobcard['customer_name'] ?? '—') ?>
    </div>
    <div class="info-row">
        <span class="label">Ph. No</span>: <?= htmlspecialchars($jobcard['phoneNo1'] ?? '—') ?>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 55%;">Spare Name</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 30%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($spares as $sp): ?>
                <tr>
                    <td><?= htmlspecialchars(strtoupper($sp['itemName'])) ?></td>
                    <td class="text-center"><?= (int)$sp['quantity'] ?></td>
                    <td class="text-right"><?= number_format((float)$sp['totalPrice'], 2, '.', '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ((float)($jobcard['laborCharge'] ?? 0) > 0): ?>
                <tr>
                    <td>LABOUR</td>
                    <td class="text-center">1</td>
                    <td class="text-right"><?= number_format((float)$jobcard['laborCharge'], 2, '.', '') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals-table">
        <tr>
            <td style="vertical-align: top; font-size: 10px;">
                Prepared By :<?= htmlspecialchars($preparedBy) ?>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <span style="font-weight: normal;">Total:</span><br>
                <span style="font-size: 12px; font-weight: bold;"><?= number_format((float)($jobcard['actualAmountSum'] ?? 0), 2, '.', '') ?></span>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer-notice">
        <p>* Goods Cannot Be Returned *</p>
        <p>* Thank You *</p>
        <p>* Visit Again *</p>
    </div>

</body>
</html>
