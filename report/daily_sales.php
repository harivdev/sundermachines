<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

// Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Daily_Sales_$date.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}

if (!isset($_GET['export'])) {
    include("../includes/header.php");
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Full detailed daily sales query
$query = "
    SELECT 
        s.id,
        s.orderNo,
        s.orderDate,
        s.orderStatus,
        s.actualAmountSum,
        s.paidAmountSum,
        c.name AS customer_name,
        c.phoneNo1 AS contact_no
    FROM sales s
    LEFT JOIN customer c ON s.customer = c.id
    WHERE s.orderDate = '$filter_date'
    ORDER BY s.id DESC
";
$result = mysqli_query($conn, $query);

// Totals
$total_query = "
    SELECT 
        COUNT(id) AS total_orders,
        SUM(actualAmountSum) AS total_sales,
        SUM(paidAmountSum) AS total_paid
    FROM sales
    WHERE orderDate = '$filter_date'
";
$total_res = mysqli_query($conn, $total_query);
$totals = mysqli_fetch_assoc($total_res);

$total_orders = (int) ($totals['total_orders'] ?? 0);
$total_sales = (float) ($totals['total_sales'] ?? 0);
$total_paid = (float) ($totals['total_paid'] ?? 0);
$total_balance = $total_sales - $total_paid;
?>

<div class="erp-container">

    <?php if (!isset($_GET['export'])): ?>
        <div class="erp-header-bar no-print">
            <div class="erp-header-title">Daily Sales Report</div>
            <div class="erp-header-actions">
                <form method="GET" style="display:flex; align-items:center; gap:8px;">
                    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" class="erp-input" style="width:160px; height:34px;">
                    <button type="submit" class="btn-erp btn-erp-primary btn-erp-sm">Filter</button>
                </form>
                <button type="button" onclick="window.print()" class="btn-erp btn-erp-secondary btn-erp-sm">🖨️ PDF</button>
                <a href="?date=<?= urlencode($filter_date) ?>&export=excel" class="btn-erp btn-erp-success btn-erp-sm">📊 Excel</a>
            </div>
        </div>
    <?php endif; ?>

    <div style="background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 12px 12px; padding:24px;">

        <div style="margin-bottom:20px;">
            <h2 style="margin:0; color:#1e293b; font-size:28px;">Sales Summary</h2>
            <div style="margin-top:8px; color:#64748b; font-size:14px;">
                Date: <strong><?= date('d/m/Y', strtotime($filter_date)) ?></strong>
            </div>
        </div>

        <div
            style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:24px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Orders
                </div>
                <div style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;"><?= $total_orders ?></div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Sales</div>
                <div style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;">
                    ₹<?= number_format(round($total_sales)) ?></div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Collected Amount
                </div>
                <div style="font-size:26px; font-weight:700; color:#16a34a; margin-top:6px;">
                    ₹<?= number_format(round($total_paid)) ?></div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Pending Balance
                </div>
                <div style="font-size:26px; font-weight:700; color:#ef4444; margin-top:6px;">
                    ₹<?= number_format(round($total_balance)) ?></div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; border:1px solid #cbd5e1; table-layout:fixed;">
                <thead>
                    <tr style="background:#d1d5db; font-weight:700;">
                        <th style="border:1px solid #000; padding:8px;">S.No</th>
                        <th style="border:1px solid #000; padding:8px;">Status</th>
                        <th style="border:1px solid #000; padding:8px;">Date</th>
                        <th style="border:1px solid #000; padding:8px;">Order #</th>
                        <th style="border:1px solid #000; padding:8px;">Customer Name</th>
                        <th style="border:1px solid #000; padding:8px;">Contact #</th>
                        <th style="border:1px solid #000; padding:8px;">Billed Amt</th>
                        <th style="border:1px solid #000; padding:8px;">Paid Amt</th>
                        <th style="border:1px solid #000; padding:8px;">Balance</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $i = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $customer_name = !empty($row['customer_name']) ? $row['customer_name'] : 'CASH BILL';
                            $contact_no = !empty($row['contact_no']) ? $row['contact_no'] : '-';
                            $billed_amt = (float) $row['actualAmountSum'];
                            $paid_amt = (float) $row['paidAmountSum'];
                            $balance_amt = $billed_amt - $paid_amt;
                            $status = !empty($row['orderStatus']) ? $row['orderStatus'] : 'New';
                            $row_bg = ($i % 2 == 0) ? '#f9fafb' : '#ffffff';
                            ?>
                            <tr style="background: <?= $row_bg ?>;">
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;"><?= $i++ ?></td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700; color:<?= ($status === 'Invoiced' || $status === 'Completed') ? '#16a34a' : '#f59e0b' ?>;">
                                    <?= htmlspecialchars($status) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;">
                                    <?= !empty($row['orderDate']) ? date('d/m/Y', strtotime($row['orderDate'])) : '-' ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700;">
                                    <?= htmlspecialchars($row['orderNo'] ?: '-') ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:600; word-break:break-word;">
                                    <?= htmlspecialchars($customer_name) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; word-break:break-word;">
                                    <?= htmlspecialchars($contact_no) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700;">
                                    <?= number_format(round($billed_amt), 0) ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:#16a34a;">
                                    <?= number_format(round($paid_amt), 0) ?>
                                </td>
                                <td
                                    style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:#ef4444; min-width:100px;">
                                    <?= number_format(round($balance_amt), 0) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9"
                                style="border:1px solid #cbd5e1; padding:24px; text-align:center; color:#64748b;">
                                No sales found for this date.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr style="background:#f8fafc;">
                        <td colspan="6"
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700;">
                            Grand Total
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700;">
                            <?= number_format(round($total_sales), 0) ?>
                        </td>
                        <td
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700; color:#16a34a;">
                            <?= number_format(round($total_paid), 0) ?>
                        </td>
                        <td
                            style="border:1px solid #94a3b8; padding:10px; text-align:right; font-weight:700; color:#ef4444;">
                            <?= number_format(round($total_balance), 0) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if (!isset($_GET['export'])): ?>
    <?php include("../includes/footer.php"); ?>

    <style>
        @media print {

            .topbar,
            .menu-container,
            .no-print,
            .btn,
            .link {
                display: none !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                font-size: 10px !important;
            }

            .container,
            .content,
            .wrapper,
            .card {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            table {
                width: 100% !important;
                min-width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                page-break-inside: auto;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                font-size: 9px !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
            }

            th:nth-child(1),
            td:nth-child(1) {
                width: 5% !important;
            }

            th:nth-child(2),
            td:nth-child(2) {
                width: 10% !important;
            }

            th:nth-child(3),
            td:nth-child(3) {
                width: 10% !important;
            }

            th:nth-child(4),
            td:nth-child(4) {
                width: 14% !important;
            }

            th:nth-child(5),
            td:nth-child(5) {
                width: 18% !important;
            }

            th:nth-child(6),
            td:nth-child(6) {
                width: 14% !important;
            }

            th:nth-child(7),
            td:nth-child(7) {
                width: 10% !important;
            }

            th:nth-child(8),
            td:nth-child(8) {
                width: 10% !important;
            }

            th:nth-child(9),
            td:nth-child(9) {
                width: 9% !important;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
<?php endif; ?>