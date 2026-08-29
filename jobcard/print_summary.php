<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$where = "WHERE 1=1";

if (!empty($_GET['status'])) {
    $safeStatus = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND j.jobStatus = '$safeStatus'";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where .= " AND (j.cardNo LIKE '%$search%' OR c.name LIKE '%$search%' OR c.phoneNo1 LIKE '%$search%')";
}

if (!empty($_GET['fromDate'])) {
    $fromDate = mysqli_real_escape_string($conn, $_GET['fromDate']);
    $where .= " AND j.givenDate >= '$fromDate'";
}

if (!empty($_GET['toDate'])) {
    $toDate = mysqli_real_escape_string($conn, $_GET['toDate']);
    $where .= " AND j.givenDate <= '$toDate'";
}

$query = "
    SELECT 
        j.id,
        j.cardNo,
        j.jobStatus,
        j.givenDate,
        j.completed,
        j.completedDate,
        j.delivered,
        j.deliveryDate,
        j.laborCharge,
        j.actualAmountSum,
        j.receivedAmountSum,
        j.modifiedOn,
        c.name AS customerName,
        c.phoneNo1
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    $where
    ORDER BY j.id DESC
";

$res = mysqli_query($conn, $query);
$jobcards = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $jobcards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card Summary</title>
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
        .page-title {
            font-size: 16px;
            font-weight: normal;
            margin: 0 0 15px 0;
            color: #000;
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
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer;">🖨️ Print A4 Summary</button>
    </div>

    <div class="page-title">Job Card Summary</div>

    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 6%;">S.No</th>
                <th style="width: 13%;">Status</th>
                <th style="width: 16%;">Job Card #</th>
                <th style="width: 21%;">Customer Name</th>
                <th style="width: 11%;">Giv Dt</th>
                <th style="width: 11%;">Deliv Dt</th>
                <th style="width: 11%;">Billed Amt</th>
                <th style="width: 11%;">Paid Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($jobcards)):
                $sno = 1;
                foreach ($jobcards as $row):
                    $cleanCardNo = str_replace(['/', ' '], '', $row['cardNo'] ?? '');
                    
                    // Format Status
                    $rawSt = $row['jobStatus'] ?? 'New';
                    if ($rawSt === 'New' || $rawSt === 'New Job') {
                        $statusDisp = 'New Job';
                    } elseif ($rawSt === 'In Progress' || $rawSt === 'Job Progress') {
                        $statusDisp = 'Job Progress';
                    } elseif ($rawSt === 'Completed' || $rawSt === 'Job Completed') {
                        $statusDisp = 'Job Completed';
                    } elseif ($rawSt === 'Delivered' || $rawSt === 'Job Delivered') {
                        $statusDisp = 'Job Delivered';
                    } else {
                        $statusDisp = htmlspecialchars($rawSt);
                    }

                    // Format Given Date
                    $givDt = (!empty($row['givenDate']) && $row['givenDate'] !== '0000-00-00') 
                        ? date('d/m/Y', strtotime($row['givenDate'])) 
                        : '';

                    // Format Delivery / Completion Date
                    $delivDtRaw = !empty($row['deliveryDate']) && $row['deliveryDate'] !== '0000-00-00'
                        ? $row['deliveryDate']
                        : (!empty($row['completedDate']) && $row['completedDate'] !== '0000-00-00' ? $row['completedDate'] : '');
                    
                    if (empty($delivDtRaw) && ($statusDisp === 'Job Completed' || $statusDisp === 'Job Delivered')) {
                        $delivDtRaw = !empty($row['modifiedOn']) ? date('Y-m-d', strtotime($row['modifiedOn'])) : '';
                    }

                    $delivDt = (!empty($delivDtRaw) && $delivDtRaw !== '0000-00-00') 
                        ? date('d/m/Y', strtotime($delivDtRaw)) 
                        : '';

                    $billed = number_format((float)($row['actualAmountSum'] ?? 0), 2, '.', '');
                    $paid = number_format((float)($row['receivedAmountSum'] ?? 0), 2, '.', '');
            ?>
                <tr>
                    <td class="text-center font-bold"><?= $sno++ ?></td>
                    <td class="text-center font-bold"><?= $statusDisp ?></td>
                    <td class="text-center font-bold"><?= htmlspecialchars($cleanCardNo) ?></td>
                    <td class="text-left font-bold"><?= htmlspecialchars(strtoupper($row['customerName'] ?? '')) ?></td>
                    <td class="text-center font-bold"><?= $givDt ?></td>
                    <td class="text-center font-bold"><?= $delivDt ?></td>
                    <td class="text-right font-bold"><?= $billed ?></td>
                    <td class="text-right font-bold"><?= $paid ?></td>
                </tr>
            <?php 
                endforeach;
            else:
            ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">No Job Card records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
