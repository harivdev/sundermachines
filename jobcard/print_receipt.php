<?php
header('Content-Type: text/html; charset=utf-8');
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$jobcardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($jobcardId <= 0) {
    echo "Invalid Job Card ID";
    exit();
}

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

$itemQuery = "
    SELECT ji.*, m.machineName AS masterMachineName
    FROM jobcarditems ji
    LEFT JOIN machine m ON ji.machine = m.id
    WHERE (ji.jobCard = $jobcardId OR ji.id = $jobcardId) AND ji.deleted = 0
    ORDER BY ji.id ASC
    LIMIT 1
";
$itemRes = mysqli_query($conn, $itemQuery);
$jcItem = ($itemRes && mysqli_num_rows($itemRes) > 0) ? mysqli_fetch_assoc($itemRes) : [];

$machineName = !empty($jcItem['machineName']) ? $jcItem['machineName'] : (!empty($jcItem['masterMachineName']) ? $jcItem['masterMachineName'] : (!empty($jobcard['mName']) ? $jobcard['mName'] : '—'));
$serialNo = !empty($jcItem['serialNo']) ? $jcItem['serialNo'] : '—';
$workDetails = !empty($jcItem['issueDetails']) ? $jcItem['issueDetails'] : '—';
$remarks = !empty($jcItem['remark']) ? $jcItem['remark'] : '—';

// Fetch Spares
$sQuery = "
    SELECT *
    FROM jobcarditemspares
    WHERE (jobCardItem = $jobcardId OR jobCardItem IN (SELECT id FROM jobcarditems WHERE jobCard = $jobcardId))
      AND deleted = 0
    ORDER BY createdOn ASC, id ASC
";
$sRes = mysqli_query($conn, $sQuery);
$spares = [];
if ($sRes) {
    while ($row = mysqli_fetch_assoc($sRes)) {
        $spares[] = $row;
    }
}

$preparedBy = !empty($_SESSION['username']) ? $_SESSION['username'] : (!empty($_SESSION['user_email']) ? $_SESSION['user_email'] : (!empty($jobcard['createdBy']) ? $jobcard['createdBy'] : 'owner@sundermahines.com'));

$laborCharge = (float)($jobcard['laborCharge'] ?? 0);
$sparesSum = 0;
foreach ($spares as $sp) {
    $sparesSum += (float)($sp['totalPrice'] ?? ((float)$sp['quantity'] * (float)$sp['pricePerQty']));
}
$calculatedTotal = $sparesSum + $laborCharge;
$grandTotal = ((float)($jobcard['actualAmountSum'] ?? 0) > 0) ? (float)$jobcard['actualAmountSum'] : $calculatedTotal;

// Separate Receipt Types:
// 1. "bill" (from edit section with spares, labor, totals)
// 2. "intake" (from job card created with machine, work details, remarks, office use)
$isEditReceipt = false;
if (isset($_GET['type']) && $_GET['type'] === 'intake') {
    $isEditReceipt = false;
} elseif (isset($_GET['from']) && $_GET['from'] === 'edit') {
    $isEditReceipt = true;
} elseif (isset($_GET['type']) && $_GET['type'] === 'bill') {
    $isEditReceipt = true;
} else {
    // Auto-detect: if coming from edit or has spares or amounts
    if (!empty($spares) || $laborCharge > 0 || (float)($jobcard['actualAmountSum'] ?? 0) > 0) {
        $isEditReceipt = true;
    }
}

$dateTimeRaw = !empty($jobcard['createdOn']) && $jobcard['createdOn'] !== '0000-00-00 00:00:00'
    ? $jobcard['createdOn']
    : (!empty($jobcard['givenDate']) ? $jobcard['givenDate'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s'));
$formattedDateTime = date('d/m/Y h:i A', strtotime($dateTimeRaw));

$rawStatus = trim($jobcard['jobStatus'] ?? 'New');
if ($rawStatus === 'New' || $rawStatus === 'New Job' || empty($rawStatus)) {
    $formattedStatus = 'New (Received for Service)';
} elseif ($rawStatus === 'In Progress' || $rawStatus === 'Progress') {
    $formattedStatus = 'In Progress';
} elseif ($rawStatus === 'Completed' || $rawStatus === 'Job Completed') {
    $formattedStatus = 'Completed (Ready for Delivery)';
} elseif ($rawStatus === 'Delivered' || $rawStatus === 'Job Delivered') {
    $formattedStatus = 'Service Delivered';
} else {
    $formattedStatus = $rawStatus;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $isEditReceipt ? 'Job Card Bill' : 'Job Card Intake' ?> - <?= htmlspecialchars($cleanCardNo) ?></title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                width: 78mm;
                padding: 0;
            }
        }

        .receipt-toolbar {
            background: transparent;
            border: none;
            padding: 6px 0;
            margin-bottom: 12px;
            text-align: center;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
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
            justify-content: flex-start;
            font-size: 10.5px;
            margin-bottom: 2px;
        }

        .field-row {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            font-size: 10.5px;
            margin-bottom: 2.5px;
            line-height: 1.3;
        }

        .field-label {
            font-weight: bold;
            width: 108px;
            flex-shrink: 0;
            color: #000;
        }

        .field-sep {
            width: 10px;
            flex-shrink: 0;
            font-weight: bold;
            text-align: left;
        }

        .field-val {
            flex: 1;
            word-break: break-word;
            color: #000;
        }

        .office-use-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 2px;
            padding: 2px 0;
        }

        table.spares-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            font-size: 10.5px;
        }

        table.spares-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            font-weight: bold;
        }

        table.spares-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .footer-notice {
            text-align: center;
            font-size: 9.5px;
            margin-top: 6px;
        }

        .footer-notice p {
            margin: 2px 0;
        }
    </style>
</head>

<body>
    <div class="no-print receipt-toolbar">
        <a href="#" onclick="window.print(); return false;" class="btn-print">🖨️ Print</a>
        <?php 
        $jcWa = !empty($jobcard['whatsAppNo']) ? $jobcard['whatsAppNo'] : (!empty($jobcard['phoneNo1']) ? $jobcard['phoneNo1'] : '');
        if (!empty($jcWa)): 
        ?>
        <a href="#" onclick="sendWhatsAppJobcardReceipt(); return false;" class="btn-whatsapp" id="btnSendWhatsApp">🟢 Send WhatsApp</a>
        <?php else: ?>
        <a href="#" onclick="alert('No WhatsApp number saved for this customer.'); return false;" class="btn-whatsapp" style="opacity:0.5;">🟢 Send WhatsApp</a>
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

    function sendWhatsAppJobcardReceipt() {
        var rawNum = <?= json_encode($jcWa) ?>;
        var waNum = normalizeIndianWhatsApp(rawNum);
        if (!waNum) { alert('Invalid WhatsApp number for this customer.'); return; }

        function cp(hex) {
            try {
                return String.fromCodePoint(hex);
            } catch (e) {
                return String.fromCharCode(hex);
            }
        }

        var icBuilding = cp(0x1F3E2);
        var icReceipt  = cp(0x1F9FE);
        var icClip     = cp(0x1F4CB);
        var icId       = cp(0x1F194);
        var icCal      = cp(0x1F4C5);
        var icRefresh  = cp(0x1F504);
        var icUser     = cp(0x1F464);
        var icPhone    = cp(0x1F4DE);
        var icGear     = cp(0x2699);
        var icNum      = cp(0x1F522);
        var icTools    = cp(0x1F6E0);
        var icMemo     = cp(0x1F4DD);
        var icBolt     = cp(0x1F529);
        var icBullet   = cp(0x1F539);
        var icCard     = cp(0x1F4B3);
        var icMoney    = cp(0x1F4B5);
        var icGreen    = cp(0x1F7E2);
        var icPin      = cp(0x1F4CD);
        var icInbox    = cp(0x1F4E5);
        var icWarn     = cp(0x26A0);
        var icPray     = cp(0x1F64F);
        var icThread   = cp(0x1F9F6);
        var icNeedle   = cp(0x1FAA1);

        var cardNo = <?= json_encode($cleanCardNo) ?>;
        var dateTime = <?= json_encode($formattedDateTime) ?>;
        var custName = <?= json_encode($jobcard['customer_name'] ?? '—') ?>;
        var phone = <?= json_encode($jobcard['phoneNo1'] ?? '—') ?>;
        var jobStatus = <?= json_encode($formattedStatus) ?>;
        var isEdit = <?= json_encode($isEditReceipt) ?>;

        var lines = [];
        lines.push(icBuilding + ' *SUNDER MACHNES WORLD*');

        if (isEdit) {
            lines.push(icReceipt + ' *JOB CARD SERVICE BILL & DELIVERY RECEIPT*');
            lines.push('─────────────────');
            lines.push('*' + icClip + ' JOB CARD DETAILS*');
            lines.push('Job Card Bill No: ' + cardNo);
            lines.push('Date & Time: ' + dateTime);
            lines.push('Status: ' + jobStatus);
            lines.push('');
            lines.push('*' + icUser + ' CUSTOMER DETAILS*');
            lines.push('Customer Name: ' + custName);
            if (phone && phone !== '—') {
                lines.push('Phone Number: ' + phone);
            }
            lines.push('');
            lines.push('*' + icGear + ' MACHINE DETAILS*');
            lines.push('Machine Model: ' + <?= json_encode($machineName) ?>);
            lines.push('Serial No: ' + <?= json_encode($serialNo) ?>);
            lines.push('Work Details: ' + <?= json_encode($workDetails) ?>);
            lines.push('');
            lines.push('*' + icBolt + ' SPARES & LABOUR CHARGES*');
            <?php if (!empty($spares)): ?>
            <?php 
            $sIndex = 1;
            foreach ($spares as $sp): 
                $itemTotal = (float)($sp['totalPrice'] ?? ((float)$sp['quantity'] * (float)$sp['pricePerQty']));
            ?>
            lines.push(<?= json_encode($sIndex++ . '. ' . htmlspecialchars_decode($sp['itemName'])) ?> + ' × ' + <?= json_encode((int)$sp['quantity']) ?> + ' = ₹ ' + <?= json_encode(number_format($itemTotal, 2)) ?>);
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($laborCharge > 0): ?>
            lines.push('Labour Charge: ₹ ' + <?= json_encode(number_format($laborCharge, 2)) ?>);
            <?php endif; ?>
            lines.push('');
            lines.push('*' + icCard + ' PAYMENT SUMMARY*');
            lines.push('Grand Total: ₹ ' + <?= json_encode(number_format($grandTotal, 2)) ?>);
            <?php 
            $paidAmt = (float)($jobcard['receivedAmountSum'] ?? 0);
            $balAmt = max(0, $grandTotal - $paidAmt);
            ?>
            lines.push('Paid Amount: ₹ ' + <?= json_encode(number_format($paidAmt, 2)) ?>);
            <?php if ($balAmt > 0): ?>
            lines.push('Balance Due: ₹ ' + <?= json_encode(number_format($balAmt, 2)) ?>);
            <?php endif; ?>
            lines.push('─────────────────');
            lines.push(icPray + ' *Thank you for your business!*');
        } else {
            lines.push(icClip + ' *JOB CARD INTAKE RECEIPT*');
            lines.push('─────────────────');
            lines.push('*' + icClip + ' JOB CARD DETAILS*');
            lines.push('Job Card No: ' + cardNo);
            lines.push('Date & Time: ' + dateTime);
            lines.push('Status: ' + jobStatus);
            lines.push('');
            lines.push('*' + icUser + ' CUSTOMER DETAILS*');
            lines.push('Customer Name: ' + custName);
            if (phone && phone !== '—') {
                lines.push('Phone Number: ' + phone);
            }
            lines.push('');
            lines.push('*' + icGear + ' MACHINE & SERVICE DETAILS*');
            lines.push('Machine Model: ' + <?= json_encode($machineName) ?>);
            lines.push('Serial No: ' + <?= json_encode($serialNo) ?>);
            lines.push('Work Details: ' + <?= json_encode($workDetails) ?>);
            <?php if (!empty($remarks) && $remarks !== '—'): ?>
            lines.push('Remarks: ' + <?= json_encode($remarks) ?>);
            <?php endif; ?>
            lines.push('─────────────────');
            lines.push('*' + icPin + ' IMPORTANT NOTICE*');
            lines.push('Your machine has been received safely for service.');
            lines.push('Goods cannot be claimed without presenting job card receipt.');
            lines.push('─────────────────');
            lines.push(icPray + ' *Thank you for choosing Sunder Machnes World!*');
        }

        lines.push(icThread + ' *Sunder Machines World* ' + icNeedle);

        var msg = lines.join('\n');
        var url = 'https://api.whatsapp.com/send?phone=' + waNum + '&text=' + encodeURIComponent(msg);
        window.open(url, '_blank');
    }
    </script>

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

    <div class="field-row">
        <span class="field-label">Name</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($jobcard['customer_name'] ?? '—') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Ph. No</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($jobcard['phoneNo1'] ?? '—') ?></span>
    </div>

<?php if ($isEditReceipt): ?>
    <!-- ==================== 1. SPARES & BILLING SECTION (FROM EDIT PAGE) ==================== -->
    <div class="divider"></div>

    <table class="spares-table">
        <thead>
            <tr>
                <th style="width: 55%; text-align: left;">Spare Name</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 30%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sNo = 1;
            foreach ($spares as $sp): 
                $itemTotal = (float)($sp['totalPrice'] ?? ((float)$sp['quantity'] * (float)$sp['pricePerQty']));
            ?>
                <tr>
                    <td style="text-align: left;"><?= $sNo++ ?>. <?= htmlspecialchars($sp['itemName']) ?></td>
                    <td style="text-align: center;"><?= (int)$sp['quantity'] ?></td>
                    <td style="text-align: right;"><?= number_format($itemTotal, 2) ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if ($laborCharge > 0): ?>
                <tr>
                    <td colspan="2" style="text-align: left;">Labour Charge</td>
                    <td style="text-align: right;"><?= number_format($laborCharge, 2) ?></td>
                </tr>
            <?php endif; ?>

            <?php if (empty($spares) && $laborCharge <= 0): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #64748b; padding: 6px 0;">No spares or labour added</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 10px; font-weight: bold; padding: 2px 0;">
        <span>Prepared By: <?= htmlspecialchars($preparedBy) ?></span>
        <span style="font-size: 11px;">Total: <?= number_format($grandTotal, 2) ?></span>
    </div>
    <?php if ((float)($jobcard['receivedAmountSum'] ?? 0) > 0): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 10px; font-weight: bold; padding: 2px 0; color: #166534;">
        <span>Mode: <?= htmlspecialchars($jobcard['paymentMode'] ?? 'Cash') ?></span>
        <span>Paid: <?= number_format((float)$jobcard['receivedAmountSum'], 2) ?></span>
    </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ==================== 2. INTAKE SLIP SECTION (FOR JOB CARD CREATED) ==================== -->
    <div class="divider"></div>

    <div class="field-row">
        <span class="field-label">Machine Name</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($machineName) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Serial.Number</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($serialNo) ?></span>
    </div>

    <div class="divider"></div>

    <div class="field-row">
        <span class="field-label">Work Details</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($workDetails) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Remarks</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($remarks) ?></span>
    </div>

    <div class="divider"></div>

    <div class="office-use-title">OFFICE USE</div>

    <div class="divider"></div>

    <div class="field-row">
        <span class="field-label">Job Card Number</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($cleanCardNo) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Machine Name</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($machineName) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Work Details</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($workDetails) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Remarks</span><span class="field-sep">:</span><span
            class="field-val"><?= htmlspecialchars($remarks) ?></span>
    </div>

<?php endif; ?>

    <div class="divider"></div>

    <div class="footer-notice">
        <p>* Goods Cannot Be Returned *</p>
        <p>* Thank You *</p>
        <p>* Visit Again *</p>
    </div>

<?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>
<?php endif; ?>

</body>

</html>