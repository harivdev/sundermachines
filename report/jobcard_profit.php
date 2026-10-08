<?php
require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../includes/auth.php");
requireAdmin();

$filter_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$filter_mode = isset($_GET['mode']) ? trim($_GET['mode']) : (isset($_GET['paymentMode']) ? trim($_GET['paymentMode']) : '');
$all_dates = isset($_GET['all_dates']) && $_GET['all_dates'] == '1';

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $export_date_str = $all_dates ? 'All_Dates' : $filter_date;
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Jobcard_Profit_Report_{$export_date_str}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
}

if (!isset($_GET['export'])) {
    include(__DIR__ . "/../includes/header.php");
}


// Build where clause
$where_clauses = [];
if (!$all_dates && !empty($filter_date)) {
    $safe_date = mysqli_real_escape_string($conn, $filter_date);
    $where_clauses[] = "(j.givenDate = '$safe_date' OR (j.givenDate IS NULL AND DATE(j.createdOn) = '$safe_date'))";
}

if (!empty($filter_mode)) {
    $safe_mode = mysqli_real_escape_string($conn, $filter_mode);
    if ($filter_mode === 'Net Banking' || $filter_mode === 'NetBanking') {
        $where_clauses[] = "(j.paymentMode = 'NetBanking' OR j.paymentMode = 'Net Banking' OR j.paymentMode = 'NB')";
    } elseif ($filter_mode === 'Cash') {
        $where_clauses[] = "(j.paymentMode = 'Cash' OR j.paymentMode IS NULL OR j.paymentMode = '')";
    } else {
        $where_clauses[] = "j.paymentMode = '$safe_mode'";
    }
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch job cards with customer info, paymentMode, and spares cost
$query = "
    SELECT
        j.id,
        j.cardNo,
        j.givenDate,
        j.completedDate,
        j.deliveryDate,
        j.createdOn,
        j.jobStatus,
        (j.delivered + 0) AS delivered,
        j.laborCharge,
        j.actualAmountSum,
        j.receivedAmountSum,
        j.paymentMode,
        c.name AS customer_name,
        c.phoneNo1 AS contact_no,
        COALESCE(cost_data.total_cost, 0) AS spares_cost,
        COALESCE(cost_data.spares_billed, 0) AS spares_billed_sum,
        COALESCE(cost_data.spares_count, 0) AS spares_count
    FROM jobcard j
    LEFT JOIN customer c ON j.customer = c.id
    LEFT JOIN (
        SELECT
            COALESCE(ji.jobCard, jcs.jobCardItem) AS jc_id,
            SUM(jcs.quantity * COALESCE(st.actualPricePerQty, 0)) AS total_cost,
            SUM(jcs.totalPrice) AS spares_billed,
            COUNT(jcs.id) AS spares_count
        FROM jobcarditemspares jcs
        LEFT JOIN jobcarditems ji ON jcs.jobCardItem = ji.id
        LEFT JOIN stock st ON jcs.stock = st.id
        WHERE jcs.deleted = 0
        GROUP BY COALESCE(ji.jobCard, jcs.jobCardItem)
    ) cost_data ON j.id = cost_data.jc_id
    $where_sql
    ORDER BY j.id DESC
";

$result = mysqli_query($conn, $query);

$rows = [];
$total_orders = 0;
$total_sales = 0.0;
$total_paid = 0.0;
$total_balance = 0.0;
$total_cost = 0.0;
$total_profit = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $billed = (float)$row['actualAmountSum'];
        $paid = (float)$row['receivedAmountSum'];
        $labor = (float)$row['laborCharge'];
        $spares_billed = (float)$row['spares_billed_sum'];

        // If actualAmountSum is 0 but spares/labor exist, use calculated total
        if ($billed <= 0 && ($labor > 0 || $spares_billed > 0)) {
            $billed = $labor + $spares_billed;
        }

        $balance = $billed - $paid;
        $cost = (float)$row['spares_cost'];
        $profit = $billed - $cost;

        $row['computed_billed'] = $billed;
        $row['computed_paid'] = $paid;
        $row['computed_balance'] = $balance;
        $row['computed_cost'] = $cost;
        $row['computed_profit'] = $profit;

        $rows[] = $row;

        $total_orders++;
        $total_sales += $billed;
        $total_paid += $paid;
        $total_balance += $balance;
        $total_cost += $cost;
        $total_profit += $profit;
    }
}
?>

<div class="erp-container">

    <?php if (!isset($_GET['export'])): ?>
        <div class="erp-header-bar no-print">
            <div class="erp-header-title">
                <i class="fa-solid fa-file-invoice-dollar" style="color:#C9A227; margin-right:8px;"></i>
                Jobcard Profit Report
            </div>
            <div class="erp-header-actions" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <button type="button" onclick="document.getElementById('jobcardProfitFilter').style.display = document.getElementById('jobcardProfitFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">🔽 Filter</button>
                <button type="button" onclick="window.print()" style="background:#475569; color:#fff; border:none; font-weight:600; font-size:13px; padding:7px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; cursor:pointer; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2);">🖨️ PDF</button>
                <a href="?date=<?= urlencode($filter_date) ?>&all_dates=<?= $all_dates ? '1' : '0' ?>&mode=<?= urlencode($filter_mode) ?>&export=excel" style="background:#16a34a; color:#fff; border:none; font-weight:600; font-size:13px; padding:7px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">📊 Excel</a>
            </div>
        </div>

        <div id="jobcardProfitFilter" class="no-print" style="background:#f8fafc; padding:16px 20px; border:1px solid #cbd5e1; border-radius:8px; margin-bottom:15px; box-sizing:border-box; display:<?= ($all_dates || !empty($filter_mode) || $filter_date !== date('Y-m-d')) ? 'block' : 'none' ?>;">
            <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Payment Mode</label>
                    <select name="mode" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; min-width:140px; box-sizing:border-box;">
                        <option value="">-- All --</option>
                        <option value="Cash" <?= $filter_mode === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Card" <?= $filter_mode === 'Card' ? 'selected' : '' ?>>Card</option>
                        <option value="UPI" <?= $filter_mode === 'UPI' ? 'selected' : '' ?>>UPI</option>
                        <option value="Net Banking" <?= ($filter_mode === 'Net Banking' || $filter_mode === 'NetBanking') ? 'selected' : '' ?>>Net Banking</option>
                        <option value="Cheque" <?= $filter_mode === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Date</label>
                    <input type="date" name="date" id="profitDateInput" value="<?= htmlspecialchars($filter_date) ?>" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; background:#fff; height:38px; box-sizing:border-box;" <?= $all_dates ? 'disabled' : '' ?>>
                </div>
                <div style="height:38px; display:flex; align-items:center;">
                    <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; font-weight:600; color:#334155; user-select:none; margin:0;">
                        <input type="checkbox" name="all_dates" id="profitAllDatesCheckbox" value="1" <?= $all_dates ? 'checked' : '' ?> onchange="document.getElementById('profitDateInput').disabled = this.checked;" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;">
                        <span>All Dates</span>
                    </label>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="submit" style="background:#2563eb; color:#fff; padding:8px 18px; border:none; border-radius:5px; font-weight:600; font-size:14px; cursor:pointer; height:38px; display:inline-flex; align-items:center; justify-content:center;">Apply</button>
                    <a href="jobcard_profit.php" style="background:#f59e0b; color:#fff; padding:8px 18px; border-radius:5px; font-weight:600; font-size:14px; text-decoration:none; height:38px; display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box;">Clear</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="report-main-content" style="background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 12px 12px; padding:24px;">

        <div class="no-print" style="margin-bottom:20px;">
            <h2 style="margin:0; color:#1e293b; font-size:26px; font-weight:700;">Job Card Profit Summary</h2>
            <div style="margin-top:8px; color:#64748b; font-size:14px;">
                <?php if ($all_dates): ?>
                    Showing <strong>All Dates</strong>
                <?php else: ?>
                    Date: <strong><?= date('d/m/Y', strtotime($filter_date)) ?></strong>
                <?php endif; ?>
                <?php if (!empty($filter_mode)): ?>
                    | Payment Mode: <strong><?= htmlspecialchars($filter_mode) ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stat Cards Grid -->
        <div class="sales-summary-grid no-print" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:24px;">
            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Orders</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;"><?= $total_orders ?></div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Total Sales</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#111827; margin-top:6px;">
                    ₹<?= number_format(round($total_sales)) ?>
                </div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Collected Amount</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#16a34a; margin-top:6px;">
                    ₹<?= number_format(round($total_paid)) ?>
                </div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:700;">Pending Balance</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#ef4444; margin-top:6px;">
                    ₹<?= number_format(round($total_balance)) ?>
                </div>
            </div>

            <div class="sales-stat-card" style="background:#f8fafc; border:1px solid #fed7aa; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#ea580c; font-weight:700;">Total Cost</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#c2410c; margin-top:6px;">
                    ₹<?= number_format(round($total_cost)) ?>
                </div>
            </div>

            <div class="sales-stat-card" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:18px;">
                <div class="sales-stat-label" style="font-size:12px; text-transform:uppercase; color:#16a34a; font-weight:700;">Profit Amount</div>
                <div class="sales-stat-value" style="font-size:26px; font-weight:700; color:#15803d; margin-top:6px;">
                    ₹<?= number_format(round($total_profit)) ?>
                </div>
            </div>
        </div>

        <!-- Print Header Only -->
        <div class="print-report-header">
            <h1 class="print-company-name">SUNDER MACHINES</h1>
            <div class="print-report-title">Jobcard Profit Report</div>
            <div class="print-report-meta">
                <?= $all_dates ? 'All Dates' : ('Date: ' . date('d/m/Y', strtotime($filter_date))) ?><?= !empty($filter_mode) ? ' | Mode: ' . htmlspecialchars($filter_mode) : '' ?>
            </div>
        </div>

        <!-- Table -->
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; border:1px solid #cbd5e1; white-space:nowrap;">
                <thead>
                    <tr style="background:#d1d5db; font-weight:700;">
                        <th style="border:1px solid #000; padding:8px; width:45px; text-align:center;">S.No</th>
                        <th style="border:1px solid #000; padding:8px; width:95px; text-align:center;">Status</th>
                        <th style="border:1px solid #000; padding:8px; width:85px; text-align:center;">Date</th>
                        <th style="border:1px solid #000; padding:8px; width:115px; text-align:center;">Order #</th>
                        <th style="border:1px solid #000; padding:8px; width:160px; text-align:center;">Customer Name</th>
                        <th style="border:1px solid #000; padding:8px; width:105px; text-align:center;">Contact #</th>
                        <th style="border:1px solid #000; padding:8px; width:95px; text-align:center;">Mode</th>
                        <th style="border:1px solid #000; padding:8px; width:90px; text-align:right;">Billed Amt</th>
                        <th style="border:1px solid #000; padding:8px; width:90px; text-align:right;">Paid Amt</th>
                        <th style="border:1px solid #000; padding:8px; width:90px; text-align:right;">Balance</th>
                        <th style="border:1px solid #000; padding:8px; width:90px; text-align:right;">Cost Amt</th>
                        <th style="border:1px solid #000; padding:8px; width:90px; text-align:right;">Profit</th>
                        <?php if (!isset($_GET['export'])): ?>
                            <th class="no-print" style="border:1px solid #000; padding:8px; width:85px; text-align:center;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $customer_name = !empty($row['customer_name']) ? $row['customer_name'] : 'CASH CUSTOMER';
                            $contact_no = !empty($row['contact_no']) ? $row['contact_no'] : '-';
                            $cleanCardNo = str_replace(['/', ' '], '', $row['cardNo'] ?? '');

                            $rawSt = $row['jobStatus'] ?? 'New';
                            $statusBg = '#16a34a';
                            if ($rawSt === 'New' || $rawSt === 'New Job') {
                                $statusBg = '#16a34a';
                            } elseif ($rawSt === 'In Progress' || $rawSt === 'Job Progress') {
                                $statusBg = '#8b5cf6';
                            } elseif ($rawSt === 'Completed' || $rawSt === 'Job Completed') {
                                $statusBg = '#ca8a04';
                            } elseif ($rawSt === 'Delivered' || $rawSt === 'Job Delivered') {
                                $statusBg = '#dc2626';
                            } elseif ($rawSt === 'Cancelled') {
                                $statusBg = '#64748b';
                            }

                            $jobDate = !empty($row['givenDate']) && $row['givenDate'] !== '0000-00-00'
                                ? date('d/m/Y', strtotime($row['givenDate']))
                                : (!empty($row['createdOn']) ? date('d/m/Y', strtotime($row['createdOn'])) : '-');

                            $row_bg = ($i % 2 == 0) ? '#f9fafb' : '#ffffff';
                            ?>
                            <tr style="background: <?= $row_bg ?>;">
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;"><?= $i++ ?></td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700; color:<?= $statusBg ?>;">
                                    <?= htmlspecialchars($rawSt) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center;">
                                    <?= $jobDate ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700;">
                                    <a href="../jobcard/edit.php?id=<?= urlencode($row['id']) ?>" style="color:#2563eb; text-decoration:underline;">
                                        <?= htmlspecialchars($cleanCardNo ?: ('JC#' . $row['id'])) ?>
                                    </a>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:600; word-break:break-word;">
                                    <?= htmlspecialchars($customer_name) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; word-break:break-word;">
                                    <?= htmlspecialchars($contact_no) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:center; font-weight:700; color:#1e293b;">
                                    <?php
                                    $m = trim($row['paymentMode'] ?? '');
                                    if (strcasecmp($m, 'NetBanking') === 0 || strcasecmp($m, 'Net Banking') === 0 || strcasecmp($m, 'NB') === 0) {
                                        echo 'Net Banking';
                                    } elseif (!empty($m)) {
                                        echo htmlspecialchars($m);
                                    } elseif ($row['computed_paid'] > 0 || !empty($row['delivered'])) {
                                        echo 'Cash';
                                    } else {
                                        echo '<span style="color:#94a3b8; font-weight:600;">—</span>';
                                    }
                                    ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700;">
                                    <?= number_format(round($row['computed_billed']), 0) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:#16a34a;">
                                    <?= number_format(round($row['computed_paid']), 0) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:<?= $row['computed_balance'] > 0 ? '#ef4444' : '#64748b' ?>;">
                                    <?= number_format(round($row['computed_balance']), 0) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:600; color:#ea580c;">
                                    <?= number_format(round($row['computed_cost']), 0) ?>
                                </td>
                                <td style="border:1px solid #cbd5e1; padding:8px; text-align:right; font-weight:700; color:<?= $row['computed_profit'] >= 0 ? '#16a34a' : '#ef4444' ?>;">
                                    <?= number_format(round($row['computed_profit']), 0) ?>
                                </td>
                                <?php if (!isset($_GET['export'])): ?>
                                    <td class="no-print" style="border:1px solid #cbd5e1; padding:6px; text-align:center;">
                                        <a href="../jobcard/print_receipt.php?id=<?= $row['id'] ?>&from=edit" target="_blank" class="print-action-link" style="background:transparent; border:none; box-shadow:none; color:#1e293b; text-decoration:none; display:inline-flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; font-size:11.5px; font-weight:700; cursor:pointer; padding:4px;" title="Print Receipt">
                                            <span style="font-size:18px; line-height:1;">🖨️</span>
                                            <span>Print</span>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= isset($_GET['export']) ? '12' : '13' ?>" style="border:1px solid #cbd5e1; padding:24px; text-align:center; color:#64748b;">
                                No sales found for this date.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="7" style="border:1px solid #94a3b8; padding:10px; text-align:right;">
                            Grand Total
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right;">
                            <?= number_format(round($total_sales), 0) ?>
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; color:#16a34a;">
                            <?= number_format(round($total_paid), 0) ?>
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; color:#ef4444;">
                            <?= number_format(round($total_balance), 0) ?>
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; color:#ea580c;">
                            <?= number_format(round($total_cost), 0) ?>
                        </td>
                        <td style="border:1px solid #94a3b8; padding:10px; text-align:right; color:#16a34a;">
                            <?= number_format(round($total_profit), 0) ?>
                        </td>
                        <?php if (!isset($_GET['export'])): ?>
                            <td class="no-print" style="border:1px solid #94a3b8; padding:10px; text-align:center;">-</td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if (!isset($_GET['export'])): ?>

    <?php include(__DIR__ . "/../includes/footer.php"); ?>

    <style>
        .print-action-link:hover {
            color: #2563eb !important;
            transform: scale(1.08);
        }
        .profit-filter-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-field-group.date-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-field-group.date-group .date-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }
        .filter-field-group.date-group .date-input {
            width: 145px;
            height: 34px;
        }
        .filter-field-group.date-group .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }
        .filter-field-group.mode-group .mode-select {
            width: 175px;
            height: 34px;
        }
        .filter-btn-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 768px) {
            .erp-header-bar {
                padding: 14px 16px !important;
                border-radius: 8px 8px 0 0 !important;
            }
            .erp-header-actions {
                width: 100% !important;
                margin-top: 4px !important;
            }
            .profit-filter-form {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                gap: 10px !important;
            }
            .filter-field-group.date-group {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                width: 100% !important;
                gap: 12px !important;
            }
            .filter-field-group.date-group .date-label {
                flex: 1 1 auto !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }
            .filter-field-group.date-group .date-input {
                flex: 1 !important;
                width: 100% !important;
                min-width: 120px !important;
                height: 38px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                padding: 0 8px !important;
                box-sizing: border-box !important;
            }
            .filter-field-group.date-group .checkbox-label {
                flex-shrink: 0 !important;
                white-space: nowrap !important;
            }
            .filter-field-group.mode-group {
                width: 100% !important;
            }
            .filter-field-group.mode-group .mode-select {
                width: 100% !important;
                height: 38px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                font-size: 13.5px !important;
                font-weight: 600 !important;
                box-sizing: border-box !important;
                padding: 0 10px !important;
            }
            .filter-btn-group {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(75px, 1fr)) !important;
                gap: 8px !important;
                width: 100% !important;
            }
            .filter-btn-group .btn-action {
                height: 38px !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                padding: 0 8px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 8px !important;
                box-sizing: border-box !important;
                text-align: center !important;
                width: 100% !important;
            }
            .sales-summary-grid {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
                margin-bottom: 20px !important;
            }
            .sales-stat-card {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 14px 18px !important;
            }
            .sales-stat-label {
                font-size: 13px !important;
                margin-bottom: 0 !important;
                flex-shrink: 0 !important;
            }
            .sales-stat-value {
                font-size: 22px !important;
                margin-top: 0 !important;
                text-align: right !important;
            }
        }

        .print-report-header {
            display: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .print-report-header {
                display: block !important;
                text-align: center !important;
                margin-bottom: 12px !important;
                border-bottom: 1.5px solid #000 !important;
                padding-bottom: 6px !important;
            }

            .print-company-name {
                margin: 0 !important;
                font-size: 20px !important;
                font-weight: 800 !important;
                letter-spacing: 0.5px !important;
                color: #000 !important;
                text-transform: uppercase !important;
            }

            .print-report-title {
                margin: 3px 0 0 0 !important;
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #000 !important;
            }

            .print-report-meta {
                margin: 3px 0 0 0 !important;
                font-size: 11px !important;
                color: #333 !important;
            }

            .report-main-content {
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
            }

            .topbar,
            .menu-container,
            .navbar,
            nav,
            .no-print,
            #jobcardProfitFilter,
            .sales-summary-grid,
            .erp-header-bar,
            .btn,
            .link,
            #profitSlipModal {
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
            .card,
            .erp-container {
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

            th:nth-child(1), td:nth-child(1) { width: 3% !important; }
            th:nth-child(2), td:nth-child(2) { width: 8% !important; }
            th:nth-child(3), td:nth-child(3) { width: 7% !important; }
            th:nth-child(4), td:nth-child(4) { width: 10% !important; }
            th:nth-child(5), td:nth-child(5) { width: 16% !important; }
            th:nth-child(6), td:nth-child(6) { width: 10% !important; }
            th:nth-child(7), td:nth-child(7) { width: 8% !important; }
            th:nth-child(8), td:nth-child(8) { width: 8% !important; }
            th:nth-child(9), td:nth-child(9) { width: 8% !important; }
            th:nth-child(10), td:nth-child(10) { width: 7% !important; }
            th:nth-child(11), td:nth-child(11) { width: 7% !important; }
            th:nth-child(12), td:nth-child(12) { width: 8% !important; }

            @page {
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
<?php endif; ?>
