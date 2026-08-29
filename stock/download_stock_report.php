<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "Access denied";
    exit();
}

// Fetch Machine Stocks
$mcQuery = "
    SELECT 
        mc.machineName,
        b.brandName,
        m.model AS modelName,
        COALESCE(SUM(st.availableQty), 0) AS totalQty
    FROM stock st
    JOIN machine mc ON st.machine = mc.id
    LEFT JOIN brand b ON st.brand = b.id
    LEFT JOIN model m ON st.model = m.id
    GROUP BY mc.id, mc.machineName, b.brandName, m.model
    ORDER BY mc.machineName ASC
";
$mcRes = mysqli_query($conn, $mcQuery);
$machineStocks = [];
if ($mcRes) {
    while ($r = mysqli_fetch_assoc($mcRes)) {
        $machineStocks[] = $r;
    }
}

// Fetch Spare Stocks
$spQuery = "
    SELECT 
        sp.spareName,
        b.brandName,
        m.model AS modelName,
        sp.rackNumber,
        sp.partNo,
        st.unit,
        COALESCE(SUM(st.availableQty), 0) AS totalQty
    FROM stock st
    JOIN spares sp ON st.spare = sp.id
    LEFT JOIN brand b ON st.brand = b.id
    LEFT JOIN model m ON st.model = m.id
    GROUP BY sp.id, sp.spareName, b.brandName, m.model, sp.rackNumber, sp.partNo, st.unit
    ORDER BY sp.spareName ASC
";
$spRes = mysqli_query($conn, $spQuery);
$spareStocks = [];
if ($spRes) {
    while ($r = mysqli_fetch_assoc($spRes)) {
        $spareStocks[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report - Sunder ERP</title>
    <style>
        body {
            font-family: sans-serif;
            background: #fff;
            color: #000;
            margin: 20px;
            font-size: 13px;
        }
        h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: center;
        }
        th {
            background: #d9d9d9;
            font-weight: bold;
        }
        td.left {
            text-align: left;
        }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <h2>Machine Stocks</h2>
    <table>
        <thead>
            <tr>
                <th style="width:50px;">S.No</th>
                <th>Machine Name</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Available Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($machineStocks)): ?>
                <?php $i = 1; foreach ($machineStocks as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="left"><?= htmlspecialchars($row['machineName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['brandName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['modelName'] ?? '') ?></td>
                        <td><?= number_format($row['totalQty'], 1) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No machine stocks available</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Spare Stocks</h2>
    <table>
        <thead>
            <tr>
                <th style="width:50px;">S.No</th>
                <th>Spare Name</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Rack #</th>
                <th>Part #</th>
                <th>Available Unit</th>
                <th>Available Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($spareStocks)): ?>
                <?php $j = 1; foreach ($spareStocks as $row): ?>
                    <tr>
                        <td><?= $j++ ?></td>
                        <td class="left"><?= htmlspecialchars($row['spareName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['brandName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['modelName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['rackNumber'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['partNo'] ?? '') ?></td>
                        <td><?= number_format($row['unit'] ? $row['unit'] : 1, 1) ?></td>
                        <td><?= number_format($row['totalQty'], 1) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No spare stocks available</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
