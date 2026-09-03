<?php
require_once("../config/db.php");

// Determine active filter preset and date range
$preset = isset($_GET['preset']) ? trim($_GET['preset']) : '';
$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($preset) && empty($fromDate) && empty($toDate) && !isset($_GET['preset'])) {
    $preset = 'today';
}

if ($preset === 'today') {
    $fromDate = date('Y-m-d');
    $toDate = date('Y-m-d');
} elseif ($preset === 'yesterday') {
    $fromDate = date('Y-m-d', strtotime('-1 day'));
    $toDate = date('Y-m-d', strtotime('-1 day'));
} elseif ($preset === 'week') {
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    $toDate = date('Y-m-d');
} elseif ($preset === '15days') {
    $fromDate = date('Y-m-d', strtotime('-15 days'));
    $toDate = date('Y-m-d');
} elseif ($preset === 'all') {
    $fromDate = '';
    $toDate = '';
}

// Build WHERE clause
$where = "WHERE jis.deleted = 0";

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (jc1.cardNo LIKE '%$safeSearch%' OR jc2.cardNo LIKE '%$safeSearch%' OR jis.itemName LIKE '%$safeSearch%')";
}

if ($fromDate !== '') {
    $safeFrom = mysqli_real_escape_string($conn, $fromDate);
    $where .= " AND DATE(jis.createdOn) >= '$safeFrom'";
}

if ($toDate !== '') {
    $safeTo = mysqli_real_escape_string($conn, $toDate);
    $where .= " AND DATE(jis.createdOn) <= '$safeTo'";
}

// 1. Calculate Daily No mapping across all records in chronological order
$chronoQuery = "
    SELECT 
        jis.id,
        jis.createdOn
    FROM jobcarditemspares jis
    LEFT JOIN jobcarditems ji ON jis.jobCardItem = ji.id
    LEFT JOIN jobcard jc1 ON ji.jobCard = jc1.id
    LEFT JOIN jobcard jc2 ON jis.jobCardItem = jc2.id
    WHERE jis.deleted = 0
    ORDER BY jis.createdOn ASC, jis.id ASC
";
$chronoResult = mysqli_query($conn, $chronoQuery);
$dailyNoMap = [];
$dateCounters = [];

if ($chronoResult) {
    while ($cRow = mysqli_fetch_assoc($chronoResult)) {
        $dateKey = (!empty($cRow['createdOn']) && $cRow['createdOn'] !== '0000-00-00 00:00:00')
            ? date('Y-m-d', strtotime($cRow['createdOn']))
            : '1970-01-01';

        if (!isset($dateCounters[$dateKey])) {
            $dateCounters[$dateKey] = 1;
        } else {
            $dateCounters[$dateKey]++;
        }
        $dailyNoMap[$cRow['id']] = $dateCounters[$dateKey];
    }
}

// 2. Handle Clean CSV Export (Free of PHP 8.1+ deprecation warnings & HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ini_set('display_errors', 0);

    $exportQuery = "
        SELECT 
            jis.id,
            jis.itemName,
            jis.quantity,
            jis.pricePerQty,
            jis.totalPrice,
            jis.createdOn,
            COALESCE(jc1.cardNo, jc2.cardNo, 'N/A') AS cardNo
        FROM jobcarditemspares jis
        LEFT JOIN jobcarditems ji ON jis.jobCardItem = ji.id
        LEFT JOIN jobcard jc1 ON ji.jobCard = jc1.id
        LEFT JOIN jobcard jc2 ON jis.jobCardItem = jc2.id
        $where
        ORDER BY jis.createdOn DESC, jis.id DESC
    ";
    $exportResult = mysqli_query($conn, $exportQuery);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jobcard_spares_report_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['No', 'Job Card No', 'Spare', 'Quantity', 'Price', 'Total Price', 'Date', 'Time'], ',', '"', "\\");

    if ($exportResult) {
        while ($exRow = mysqli_fetch_assoc($exportResult)) {
            $dNo = $dailyNoMap[$exRow['id']] ?? 1;
            $cOn = !empty($exRow['createdOn']) && $exRow['createdOn'] !== '0000-00-00 00:00:00' ? strtotime($exRow['createdOn']) : false;
            $dDate = $cOn ? date('d-m-Y', $cOn) : '-';
            $dTime = $cOn ? date('h:i A', $cOn) : '-';
            $subTotal = (float)$exRow['totalPrice'] > 0 ? (float)$exRow['totalPrice'] : ((float)$exRow['quantity'] * (float)$exRow['pricePerQty']);

            fputcsv($output, [
                $dNo,
                $exRow['cardNo'],
                $exRow['itemName'],
                $exRow['quantity'],
                number_format(round((float)$exRow['pricePerQty']), 0, '.', ''),
                number_format(round($subTotal), 0, '.', ''),
                $dDate,
                $dTime
            ], ',', '"', "\\");
        }
    }
    fclose($output);
    exit;
}

// Standard Page Request Header Inclusion
include("../includes/header.php");

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 3. Count total rows for pagination
$countQuery = "
    SELECT COUNT(jis.id) AS total
    FROM jobcarditemspares jis
    LEFT JOIN jobcarditems ji ON jis.jobCardItem = ji.id
    LEFT JOIN jobcard jc1 ON ji.jobCard = jc1.id
    LEFT JOIN jobcard jc2 ON jis.jobCardItem = jc2.id
    $where
";
$countResult = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// 4. Main Query for page records (Sorted Latest First)
$query = "
    SELECT 
        jis.id,
        jis.itemName,
        jis.quantity,
        jis.pricePerQty,
        jis.totalPrice,
        jis.createdOn,
        COALESCE(jc1.cardNo, jc2.cardNo, 'N/A') AS cardNo,
        COALESCE(jc1.id, jc2.id) AS jobCardId
    FROM jobcarditemspares jis
    LEFT JOIN jobcarditems ji ON jis.jobCardItem = ji.id
    LEFT JOIN jobcard jc1 ON ji.jobCard = jc1.id
    LEFT JOIN jobcard jc2 ON jis.jobCardItem = jc2.id
    $where
    ORDER BY jis.createdOn DESC, jis.id DESC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching job card spares: " . mysqli_error($conn));
}

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<style>
    .btn-preset {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.15s;
    }
    .btn-preset:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .btn-preset.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
        font-weight: 600;
    }
    .btn-pg {
        background: #e2e8f0;
        color: #475569;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
    }
    .btn-pg:hover:not(.disabled) {
        background: #cbd5e1;
        color: #0f172a;
    }
    .btn-pg.active {
        background: #2563eb;
        color: #ffffff;
        font-weight: 700;
    }
    .btn-pg.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>

<div class="page-main-container erp-container" style="padding: 20px; background: #f8fafc; min-height: calc(100vh - 110px);">

    <!-- HEADER BAR -->
    <div class="list-header-bar" style="background: #ffffff; display: flex; align-items: center; justify-content: space-between; border-radius: 8px 8px 0 0; padding: 15px 20px; border: 1px solid #e2e8f0; border-bottom: none; flex-wrap: wrap; gap: 10px;">
        <div class="list-header-title" style="color: #1e293b; font-weight: 700; font-size: 20px; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Job Card Spares
        </div>
        <div class="list-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="openDownloadModal()" style="background: #16a34a; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">
                <span>📥</span> Download CSV
            </button>
            <a href="javascript:void(0)" onclick="location.reload()" style="background: #475569; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2);">
                <span>🔄</span> Refresh
            </a>
            <a href="javascript:void(0)" onclick="document.getElementById('sparesFilter').style.display = document.getElementById('sparesFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">
                <span>🔽</span> Filter
            </a>
        </div>
    </div>

    <!-- PRESET BUTTONS BAR -->
    <div style="background: #ffffff; padding: 10px 20px; border: 1px solid #e2e8f0; border-bottom: none; display: flex; gap: 10px; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #64748b; white-space: nowrap;">Quick Date:</span>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1; align-items: center;">
            <a href="spares_list.php?preset=today<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn-preset <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
            <a href="spares_list.php?preset=yesterday<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn-preset <?= $preset === 'yesterday' ? 'active' : '' ?>">Yesterday</a>
            <a href="spares_list.php?preset=week<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn-preset <?= $preset === 'week' ? 'active' : '' ?>">Last Week</a>
            <a href="spares_list.php?preset=15days<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn-preset <?= $preset === '15days' ? 'active' : '' ?>">15 Days</a>
            <a href="spares_list.php?preset=all<?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="btn-preset <?= $preset === 'all' ? 'active' : '' ?>">All Records</a>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="sparesFilter" style="display:<?= (!empty($search) || !empty($_GET['from_date']) || !empty($_GET['to_date'])) ? 'block' : 'none' ?>; background:#ffffff; padding:15px 20px; border:1px solid #e2e8f0; border-bottom:none;">
        <form method="GET" action="spares_list.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="preset" value="custom">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px;">Search (Job Card / Spare)</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Job Card No or Spare Name..." class="form-control" style="height: 38px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px;">
            </div>
            <div style="width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px;">From Date</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="form-control" style="height: 38px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px;">
            </div>
            <div style="width: 150px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 4px;">To Date</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="form-control" style="height: 38px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px;">
            </div>
            <div>
                <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 0 16px; height: 38px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Apply Date Filter
                </button>
                <a href="spares_list.php?preset=today" style="background: #e2e8f0; color: #475569; text-decoration: none; padding: 10px 14px; height: 38px; border-radius: 6px; font-weight: 600; display: inline-block; box-sizing: border-box; line-height: 18px; margin-left: 5px;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- TABLE CONTAINER -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; min-width: 1000px; white-space: nowrap;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                    <th style="padding: 12px 16px; text-align: center; width: 70px;">No</th>
                    <th style="padding: 12px 16px; width: 140px;">Job Card No</th>
                    <th style="padding: 12px 16px;">Spare</th>
                    <th style="padding: 12px 16px; text-align: right; width: 100px;">Quantity</th>
                    <th style="padding: 12px 16px; text-align: right; width: 120px;">Price</th>
                    <th style="padding: 12px 16px; text-align: center; width: 120px;">Date</th>
                    <th style="padding: 12px 16px; text-align: center; width: 120px;">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        $dNo = $dailyNoMap[$row['id']] ?? 1;
                        $cOn = !empty($row['createdOn']) && $row['createdOn'] !== '0000-00-00 00:00:00' ? strtotime($row['createdOn']) : false;
                        $displayDate = $cOn ? date('d-m-Y', $cOn) : '-';
                        $displayTime = $cOn ? date('h:i A', $cOn) : '-';
                        $price = (float)$row['pricePerQty'];
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #2563eb;">
                                <?= (int)$dNo ?>
                            </td>
                            <td style="padding: 12px 16px; font-weight: 600; color: #0f172a;">
                                <?php if (!empty($row['jobCardId'])): ?>
                                    <a href="edit.php?id=<?= (int)$row['jobCardId'] ?>" style="color: #0284c7; text-decoration: none;">
                                        <?= htmlspecialchars($row['cardNo']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['cardNo']) ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 16px; color: #1e293b; font-weight: 500;">
                                <?= htmlspecialchars($row['itemName']) ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #0f172a;">
                                <?= (int)$row['quantity'] ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: right; color: #059669; font-weight: 600;">
                                <?= number_format(round($price), 0) ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center; color: #475569;">
                                <?= htmlspecialchars($displayDate) ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center; color: #475569;">
                                <?= htmlspecialchars($displayTime) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 35px; text-align: center; color: #94a3b8; font-size: 15px;">
                            No spare usage records found for the selected filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ENHANCED PAGINATION BAR -->
    <div class="list-pagination-bar" style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding: 0 4px; flex-wrap: wrap; gap: 10px;">
        <div class="pagination-info" style="color: #64748b; font-size: 14px; font-weight: 400;">
            Showing <?= $totalRows > 0 ? ($offset + 1) : 0 ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> records &nbsp;|&nbsp; Page <?= $page ?> of <?= $totalPages ?>
        </div>
        <div class="pagination-buttons" style="display: flex; gap: 6px; align-items: center;">
            <!-- FIRST BUTTON -->
            <a href="spares_list.php?page=1<?= !empty($queryString) ? '&' . $queryString : '' ?>" class="btn-pg <?= $page <= 1 ? 'disabled' : '' ?>">First</a>

            <!-- PREVIOUS BUTTON -->
            <a href="spares_list.php?page=<?= max(1, $page - 1) ?><?= !empty($queryString) ? '&' . $queryString : '' ?>" class="btn-pg <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>

            <!-- NUMERIC PAGE BUTTONS -->
            <?php
            $startP = max(1, $page - 2);
            $endP = min($totalPages, $page + 2);
            for ($p = $startP; $p <= $endP; $p++):
            ?>
                <a href="spares_list.php?page=<?= $p ?><?= !empty($queryString) ? '&' . $queryString : '' ?>" class="btn-pg <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <!-- NEXT BUTTON -->
            <a href="spares_list.php?page=<?= min($totalPages, $page + 1) ?><?= !empty($queryString) ? '&' . $queryString : '' ?>" class="btn-pg <?= $page >= $totalPages ? 'disabled' : '' ?>">Next</a>

            <!-- LAST BUTTON -->
            <a href="spares_list.php?page=<?= $totalPages ?><?= !empty($queryString) ? '&' . $queryString : '' ?>" class="btn-pg <?= $page >= $totalPages ? 'disabled' : '' ?>">Last</a>
        </div>
    </div>

</div>

<!-- INTERACTIVE DOWNLOAD REPORT MODAL -->
<div id="downloadModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(3px); align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span>📥</span> Download Job Card Spares Report
            </div>
            <button type="button" onclick="closeDownloadModal()" style="background: transparent; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form method="GET" action="spares_list.php" style="padding: 20px;">
            <input type="hidden" name="export" value="csv">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>

            <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 12px;">Which date range report do you want to download?</label>
            
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="today" checked onclick="toggleModalCustomDates(false)">
                    <span>🟢 Today (<?= date('d-m-Y') ?>)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="yesterday" onclick="toggleModalCustomDates(false)">
                    <span>🟡 Yesterday (<?= date('d-m-Y', strtotime('-1 day')) ?>)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="week" onclick="toggleModalCustomDates(false)">
                    <span>🔵 Last Week (Last 7 Days)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="15days" onclick="toggleModalCustomDates(false)">
                    <span>🟣 15 Days</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="all" onclick="toggleModalCustomDates(false)">
                    <span>⚪ All Records</span>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e293b; cursor: pointer; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                    <input type="radio" name="preset" value="custom" id="radioModalCustom" onclick="toggleModalCustomDates(true)">
                    <span>📅 Date Wise (Custom Date Range)</span>
                </label>
            </div>

            <div id="modalCustomDateSection" style="display: none; background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #cbd5e1;">
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px;">From Date</label>
                        <input type="date" name="from_date" value="<?= date('Y-m-d') ?>" class="form-control" style="height: 36px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px;">To Date</label>
                        <input type="date" name="to_date" value="<?= date('Y-m-d') ?>" class="form-control" style="height: 36px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" onclick="closeDownloadModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px;">Cancel</button>
                <button type="submit" onclick="setTimeout(closeDownloadModal, 500)" style="background: #16a34a; color: #ffffff; border: none; padding: 9px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                    <span>📥</span> Download CSV
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDownloadModal() {
    document.getElementById('downloadModal').style.display = 'flex';
}
function closeDownloadModal() {
    document.getElementById('downloadModal').style.display = 'none';
}
function toggleModalCustomDates(show) {
    document.getElementById('modalCustomDateSection').style.display = show ? 'block' : 'none';
}
</script>

<?php include("../includes/footer.php"); ?>
