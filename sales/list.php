<?php 
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ================= PAGINATION =================
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ================= FILTER =================
$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.orderNo LIKE '%$safeSearch%' OR c.name LIKE '%$safeSearch%')";
}

if ($statusFilter !== '') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND s.orderStatus = '$safeStatus'";
}

// ================= COUNT =================
$countQuery = "SELECT COUNT(*) AS total FROM sales s LEFT JOIN customer c ON s.customer = c.id $where";
$countResult = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// ================= DATA =================
$query = "SELECT s.*, c.name as customer_name 
          FROM sales s 
          LEFT JOIN customer c ON s.customer = c.id 
          $where
          ORDER BY s.id DESC
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);

$today = date('Y-m-d');
$fifteenDaysAgo = date('Y-m-d', strtotime('-15 days'));
?>

<?php include("../includes/header.php"); ?>

<style>
    body {
        background: #f8fafc;
        font-family: 'Inter', system-ui, sans-serif;
    }

    .container-box {
        width: 96%;
        margin: 20px auto;
    }

    .table-box {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    th {
        background: #ffffff;
        font-size: 12.5px;
        font-weight: 700;
        padding: 10px 12px;
        color: #212529;
        border-bottom: 2px solid #dee2e6;
        text-align: left;
    }

    th.th-group-amt {
        background: #e7f5ff;
        text-align: center;
        border-bottom: 1px solid #c0e3ff;
    }

    th.th-amt-sub {
        background: #e7f5ff;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    td.amt-cell {
        background: #e7f5ff;
        color: #084298;
        font-weight: 500;
        text-align: right;
    }

    .order-link {
        color: #0d6efd;
        text-decoration: underline;
        font-weight: 600;
    }

    .action-icon {
        color: #212529;
        text-decoration: none;
        font-size: 16px;
        margin: 0 4px;
        background: none;
        border: none;
        cursor: pointer;
    }

    .action-icon:hover {
        color: #0d6efd;
    }

    .btn-top {
        padding: 7px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-dark { background: #212529; color: #fff; }
    .btn-dark:hover { background: #000; }

    .btn-light-gray { background: #e9ecef; color: #212529; border: 1px solid #ced4da; }
    .btn-light-gray:hover { background: #dee2e6; }

    .pagination {
        margin-top: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13.5px;
        color: #6c757d;
    }

    /* Download Modal */
    .dl-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
        z-index: 3000; align-items: center; justify-content: center; padding: 16px;
    }
    .dl-modal-box {
        background: #fff; width: 100%; max-width: 480px; border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden;
    }
    .dl-modal-header {
        padding: 14px 18px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;
        display: flex; justify-content: space-between; align-items: center;
    }
    .dl-modal-body { padding: 20px 18px; }
    .dl-modal-footer {
        padding: 12px 18px; background: #f8f9fa; border-top: 1px solid #dee2e6;
        display: flex; justify-content: flex-end; gap: 8px;
    }
    .dl-option-label {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px;
        border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; cursor: pointer;
        transition: all 0.15s; font-size: 13.5px; font-weight: 500;
    }
    .dl-option-label:hover { border-color: #0d6efd; background: #f0f7ff; }
    .dl-option-label input[type="radio"] { width: 16px; height: 16px; accent-color: #0d6efd; }
</style>

<div class="erp-container">

    <!-- PAGE HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Sales Orders</div>
        <div class="erp-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="create.php" style="background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                <span style="font-size: 13px;">➕</span> New
            </a>
            <button type="button" onclick="openDownloadModal()" style="background: #16a34a; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">📥 Download</button>
            <button type="button" onclick="location.reload()" style="background: #475569; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2);">🔄 Refresh</button>
            <button type="button" onclick="document.getElementById('salesFilter').style.display = document.getElementById('salesFilter').style.display === 'none' ? 'block' : 'none'" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">🔽 Filter</button>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="salesFilter" class="erp-filter-panel" style="display:<?= ($search !== '' || $statusFilter !== '') ? 'block' : 'none' ?>;">
        <form method="GET" class="erp-filter-form">
            <div>
                <label class="erp-label">Search Order# / Customer</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Order # or customer..." class="erp-input" style="width:220px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div>
                <label class="erp-label">Status</label>
                <select name="status" class="erp-select" style="width:160px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="">-- All --</option>
                    <option value="New" <?= $statusFilter === 'New' ? 'selected' : '' ?>>New</option>
                    <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            <div class="erp-filter-actions-group">
                <button type="submit" class="btn-erp btn-erp-apply">Apply</button>
                <a href="list.php" class="btn-erp btn-erp-clear">Clear</a>
            </div>
        </form>
    </div>

    <!-- TABLE BOX -->
    <div class="table-box">

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width:40px;">#</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Order #</th>
                    <th rowspan="2">Customer</th>
                    <th rowspan="2">Status</th>
                    <th colspan="2" class="th-group-amt">Amount</th>
                    <th rowspan="2" style="text-align:center;">Action</th>
                </tr>
                <tr>
                    <th class="th-amt-sub" style="text-align:right;">Billed</th>
                    <th class="th-amt-sub" style="text-align:right;">Paid</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($rows)):
                    $i = $offset + 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date('d/m/Y', strtotime($row['orderDate'])) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="order-link" title="Click to edit order">
                                    <?= htmlspecialchars($row['orderNo']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['customer_name'] ?: 'CASH BILL') ?></td>
                            <td>
                                <?php
                                $st = htmlspecialchars($row['orderStatus'] ?: 'New');
                                $stBg = '#e0f2fe'; $stFg = '#0369a1';
                                if ($st === 'Invoiced' || $st === 'Completed') {
                                    $stBg = '#dcfce7'; $stFg = '#166534';
                                } else if ($st === 'Pending') {
                                    $stBg = '#fef3c7'; $stFg = '#92400e';
                                }
                                ?>
                                <span style="background:<?= $stBg ?>; color:<?= $stFg ?>; padding:3px 10px; border-radius:12px; font-weight:700; font-size:12px; display:inline-block;">
                                    <?= $st ?>
                                </span>
                            </td>
                            <td class="amt-cell"><?= number_format(round($row['actualAmountSum']), 0) ?></td>
                            <td class="amt-cell"><?= number_format(round($row['paidAmountSum']), 0) ?></td>
                            <td style="text-align:center; white-space:nowrap;">
                                <button type="button" onclick="window.open('print_receipt.php?id=<?= $row['id'] ?>', '_blank')" class="action-icon" title="Print Customer Bill">🖨️</button>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="action-icon" title="Edit Order">✏️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:#6c757d;">No Sales Orders Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <div class="pagination">
            <div>
                <?php 
                $startRecord = $totalRows > 0 ? $offset + 1 : 0;
                $endRecord = min($offset + $limit, $totalRows);
                ?>
                Showing <?= $startRecord ?>–<?= $endRecord ?> of <?= $totalRows ?> records. &nbsp;|&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            </div>

            <div style="display:flex; gap:5px; align-items:center;">
                <?php if ($page <= 1): ?>
                    <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">First</span>
                    <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Previous</span>
                <?php else: ?>
                    <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=1" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">First</a>
                    <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page - 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Previous</a>
                <?php endif; ?>

                <a style="padding:6px 12px; background:#0d6efd; color:#fff; border-radius:5px; text-decoration:none; font-weight:bold;"><?= $page ?></a>

                <?php if ($page >= $totalPages): ?>
                    <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Next</span>
                    <span style="padding:6px 12px; background:#e2e8f0; border-radius:5px; color:#94a3b8; cursor:not-allowed;">Last</span>
                <?php else: ?>
                    <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page + 1 ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Next</a>
                    <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $totalPages ?>" style="padding:6px 12px; background:#e2e8f0; border-radius:5px; text-decoration:none; color:#1e293b;">Last</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- DOWNLOAD REPORT MODAL -->
<div id="downloadModal" class="dl-modal-overlay">
    <div class="dl-modal-box">
        <div class="dl-modal-header">
            <h3 style="margin:0; font-size:16px; font-weight:700; color:#212529;">Download Sales Report</h3>
            <button type="button" onclick="closeDownloadModal()" style="background:none; border:none; font-size:18px; cursor:pointer; color:#6c757d;">✕</button>
        </div>
        <div class="dl-modal-body">
            
            <div style="font-size:13px; font-weight:700; color:#475569; margin-bottom:10px;">Select Date Range:</div>

            <label class="dl-option-label" onclick="selectRangeOption('15')">
                <input type="radio" name="dlRange" value="15" checked onchange="toggleCustomDates()">
                <span>🗓️ <strong>Last 15 Days</strong></span>
            </label>

            <label class="dl-option-label" onclick="selectRangeOption('30')">
                <input type="radio" name="dlRange" value="30" onchange="toggleCustomDates()">
                <span>📅 <strong>Last 30 Days</strong></span>
            </label>

            <label class="dl-option-label" onclick="selectRangeOption('custom')">
                <input type="radio" name="dlRange" value="custom" onchange="toggleCustomDates()">
                <span>📆 <strong>Custom Date Range</strong></span>
            </label>

            <!-- Custom Date Inputs -->
            <div id="customDateContainer" style="display:none; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:12px; margin-bottom:12px;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">From Date</label>
                        <input type="date" id="dlFromDate" value="<?= $fifteenDaysAgo ?>" max="<?= $today ?>" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">To Date</label>
                        <input type="date" id="dlToDate" value="<?= $today ?>" max="<?= $today ?>" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                </div>
            </div>

            <div style="font-size:13px; font-weight:700; color:#475569; margin-top:14px; margin-bottom:10px;">Select Output Format:</div>
            
            <div style="display:flex; gap:16px;">
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; cursor:pointer;">
                    <input type="radio" name="dlFormat" value="pdf" checked style="accent-color:#0d6efd;">
                    📄 PDF / Printable Report
                </label>
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; cursor:pointer;">
                    <input type="radio" name="dlFormat" value="csv" style="accent-color:#0d6efd;">
                    📊 Excel / CSV Sheet
                </label>
            </div>

        </div>
        <div class="dl-modal-footer">
            <button type="button" onclick="closeDownloadModal()" style="background:#6c757d; color:#fff; border:none; padding:7px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">Cancel</button>
            <button type="button" onclick="executeReportDownload()" style="background:#0d6efd; color:#fff; border:none; padding:7px 20px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">Download Report</button>
        </div>
    </div>
</div>

<script>
function openDownloadModal() {
    document.getElementById('downloadModal').style.display = 'flex';
    toggleCustomDates();
}

function closeDownloadModal() {
    document.getElementById('downloadModal').style.display = 'none';
}

function selectRangeOption(val) {
    let radio = document.querySelector(`input[name="dlRange"][value="${val}"]`);
    if (radio) {
        radio.checked = true;
        toggleCustomDates();
    }
}

function toggleCustomDates() {
    let checkedRadio = document.querySelector('input[name="dlRange"]:checked');
    let selRange = checkedRadio ? checkedRadio.value : '15';
    let customWrap = document.getElementById('customDateContainer');
    
    if (selRange === 'custom') {
        customWrap.style.display = 'block';
    } else {
        customWrap.style.display = 'none';
    }
}

function executeReportDownload() {
    let checkedRadio = document.querySelector('input[name="dlRange"]:checked');
    let selRange = checkedRadio ? checkedRadio.value : '15';
    
    let checkedFormat = document.querySelector('input[name="dlFormat"]:checked');
    let selFormat = checkedFormat ? checkedFormat.value : 'pdf';
    
    let fromDate = document.getElementById('dlFromDate').value;
    let toDate = document.getElementById('dlToDate').value;

    let search = "<?= addslashes($search) ?>";
    let status = "<?= addslashes($statusFilter) ?>";

    let url = `download_sales_report.php?range=${selRange}&format=${selFormat}`;

    if (selRange === 'custom') {
        if (!fromDate || !toDate) {
            alert("Please select both From Date and To Date.");
            return;
        }
        if (fromDate > toDate) {
            alert("From Date cannot be after To Date.");
            return;
        }
        url += `&fromDate=${encodeURIComponent(fromDate)}&toDate=${encodeURIComponent(toDate)}`;
    }

    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    if (selFormat === 'csv') {
        window.location.href = url;
    } else {
        window.open(url, '_blank');
    }
    
    closeDownloadModal();
}

document.getElementById('downloadModal').addEventListener('click', function(e) {
    if (e.target === this) closeDownloadModal();
});
</script>

<?php include("../includes/footer.php"); ?>
