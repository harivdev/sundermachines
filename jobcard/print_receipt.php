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
        @media print {
            .no-print { display: none !important; }
            body { width: 78mm; padding: 0; }
        }
        .whatsapp-toolbar {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            margin-bottom: 12px;
            border-radius: 6px;
            text-align: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .btn-wa {
            display: inline-block;
            background: #25D366;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
        }
        .btn-print {
            display: inline-block;
            background: #475569;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            margin-right: 5px;
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
            font-size: 9.5px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-bottom: 1.5px;
        }
        .info-row .label {
            font-weight: bold;
            width: 75px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: 10px;
        }
        th {
            border-bottom: 1px dashed #000;
            border-top: 1px dashed #000;
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
        .footer {
            text-align: center;
            font-size: 9.5px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<?php
$jcPhoneRaw = !empty($jobcard['whatsAppNo']) ? $jobcard['whatsAppNo'] : ($jobcard['phoneNo1'] ?? '');
$jcPhoneClean = preg_replace('/[^0-9]/', '', $jcPhoneRaw);
if (strlen($jcPhoneClean) === 10) {
    $jcPhoneClean = '91' . $jcPhoneClean;
}

$jcMessage = "Hello " . ($jobcard['customer_name'] ?: 'Customer') . ",\n\n" .
             "Your Job Card at *SUNDER MACHINES WORLD* has been created!\n" .
             "🛠️ *Job Card No:* " . $cleanCardNo . "\n" .
             "📅 *Date:* " . $formattedDateTime . "\n" .
             "⚙️ *Machine:* " . ($jobcard['mName'] ?? 'Machine') . "\n\n" .
             "Thank you for choosing us!";
$jcWaUrl = "https://api.whatsapp.com/send?phone=" . urlencode($jcPhoneClean) . "&text=" . urlencode($jcMessage);
?>

    <div class="no-print whatsapp-toolbar">
        <a href="#" onclick="window.print(); return false;" class="btn-print">🖨️ Print Job Card</a>
        <a href="<?= $jcWaUrl ?>" target="_blank" class="btn-wa">📲 Share Job Card on WhatsApp</a>
    </div>

    <div class="header">
        <h2>SUNDER MACHNES WORLD</h2>
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
