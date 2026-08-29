<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
    exit();
}

// ================= PAGINATION =================
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

// ================= FILTER =================
$where = "WHERE 1=1";

if (!empty($_GET['item'])) {
    $item = mysqli_real_escape_string($conn, $_GET['item']);
    $where .= " AND s.spareName LIKE '%$item%'";
}

if (!empty($_GET['part'])) {
    $part = mysqli_real_escape_string($conn, $_GET['part']);
    $where .= " AND s.partNo LIKE '%$part%'";
}

if (!empty($_GET['barcode'])) {
    $barcode = mysqli_real_escape_string($conn, $_GET['barcode']);
    $where .= " AND st.barCode LIKE '%$barcode%'";
}

// ================= COUNT =================
$countQuery = "SELECT COUNT(*) as total 
FROM stock st 
LEFT JOIN spares s ON st.spare=s.id 
$where";

$countResult = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

// ================= DATA =================
$query = "
SELECT 
    st.*, 
    s.spareName, 
    s.partNo, 
    s.rackNumber,
    b.brandName, 
    m.model as modelName
FROM stock st
LEFT JOIN spares s ON st.spare = s.id
LEFT JOIN brand b ON st.brand = b.id
LEFT JOIN model m ON st.model = m.id
$where
ORDER BY st.id DESC
LIMIT $limit OFFSET $offset
";

$result = mysqli_query($conn, $query);
$rows = [];

while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}

// ================= QUERY STRING =================
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
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
        font-size: 12px;
        font-weight: 700;
        padding: 10px 12px;
        color: #212529;
        border-bottom: 2px solid #dee2e6;
        text-align: left;
    }

    th.th-group-price {
        background: #d1e7dd;
        text-align: center;
        border-bottom: 1px solid #badbcc;
    }

    th.th-price-sub {
        background: #d1e7dd;
    }

    th.th-qty {
        background: #d0e1fd;
    }

    td {
        padding: 10px 12px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    td.qty-cell {
        background: #d0e1fd;
        font-weight: 600;
        color: #084298;
    }

    td.price-cell {
        background: #d1e7dd;
        color: #0f5132;
    }

    .barcode-cell {
        text-align: center;
    }

    .barcode-cell svg {
        max-width: 100px;
        height: 28px;
        display: block;
        margin: 0 auto;
    }

    .barcode-cell span {
        font-family: monospace;
        font-size: 11px;
        font-weight: 700;
        display: block;
        margin-top: 1px;
    }

    .item-link {
        color: #0d6efd;
        text-decoration: underline;
        font-weight: 600;
    }

    .edit-btn {
        color: #212529;
        text-decoration: none;
        font-size: 16px;
    }

    .edit-btn:hover {
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
</style>

<div class="erp-container">

    <!-- PAGE HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Stocks</div>
        <div class="erp-header-actions">
            <a href="add_stock.php" class="btn-erp btn-erp-new">
                <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New
            </a>
            <button type="button" onclick="window.open('download_stock_report.php', '_blank')" class="btn-erp btn-erp-secondary">📥 Download</button>
            <button type="button" onclick="location.reload()" class="btn-erp btn-erp-secondary">🔄 Refresh</button>
            <button type="button" onclick="document.getElementById('filterDrawer').style.display = document.getElementById('filterDrawer').style.display === 'none' ? 'block' : 'none'" class="btn-erp btn-erp-secondary">🔽 Filter</button>
        </div>
    </div>

    <!-- FILTER FORM -->
    <div id="filterDrawer" class="erp-filter-panel" style="display:<?= (!empty($_GET['item']) || !empty($_GET['part']) || !empty($_GET['barcode'])) ? 'block' : 'none' ?>;">
        <form method="GET" class="erp-filter-form">
            <div>
                <label class="erp-label">Item Name</label>
                <input type="text" name="item" value="<?= htmlspecialchars($_GET['item'] ?? '') ?>" class="erp-input" style="width:180px;">
            </div>
            <div>
                <label class="erp-label">Part #</label>
                <input type="text" name="part" value="<?= htmlspecialchars($_GET['part'] ?? '') ?>" class="erp-input" style="width:140px;">
            </div>
            <div>
                <label class="erp-label">Barcode</label>
                <input type="text" name="barcode" value="<?= htmlspecialchars($_GET['barcode'] ?? '') ?>" class="erp-input" style="width:140px;">
            </div>
            <div>
                <button type="submit" class="btn-erp btn-erp-primary">Apply</button>
                <a href="list.php" class="btn-erp btn-erp-warning">Clear</a>
            </div>
        </form>
    </div>

    <!-- TABLE BOX -->
    <div class="erp-table-box">

        <table>
            <thead>
                <tr>
                    <th rowspan="2">#</th>
                    <th rowspan="2">Barcode</th>
                    <!-- <th rowspan="2">Serial No</th> -->
                    <th rowspan="2">Item Name</th>
                    <th rowspan="2">Part #</th>
                    <th rowspan="2">Rack #</th>
                    <th rowspan="2">Brand</th>
                    <th rowspan="2">Model</th>
                    <th rowspan="2" class="th-qty">Avil. Qty</th>
                    <th colspan="2" class="th-group-price">Price</th>
                    <th rowspan="2">GST %</th>
                    <th rowspan="2">Selled</th>
                    <th rowspan="2" style="text-align:center;">Action</th>
                </tr>
                <tr>
                    <th class="th-price-sub">Selling</th>
                    <th class="th-price-sub">Selled</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($rows)):
                    $i = $offset + 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $i++ ?></td>

                            <td class="barcode-cell">
                                <svg id="barcode_<?= md5($row['id']) ?>"></svg>
                                <span><?= htmlspecialchars($row['barCode'] ?? '') ?></span>
                            </td>

                            <!-- <td><?= htmlspecialchars($row['serialNo'] ?? '') ?></td> -->

                            <td>
                                <a href="edit_stock.php?id=<?= urlencode($row['id']) ?>" class="item-link">
                                    <?= htmlspecialchars($row['spareName'] ?? '') ?>
                                </a>
                            </td>

                            <td><?= htmlspecialchars($row['partNo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['rackNumber'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['brandName'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['modelName'] ?? '') ?></td>

                            <td class="qty-cell"><?= (int) $row['availableQty'] ?></td>

                            <td class="price-cell"><?= number_format(round($row['sellingPricePerUnit']), 0) ?></td>
                            <td class="price-cell"><?= number_format(round($row['selledPricePerUnit']), 0) ?></td>

                            <td><?= number_format($row['gstPercentage'] ?? 0, 2) ?></td>
                            <td><?= ($row['selled']) ? 'Yes' : 'No' ?></td>

                            <td style="text-align:center;">
                                <a href="edit_stock.php?id=<?= urlencode($row['id']) ?>" class="edit-btn" title="Edit">
                                    ✏️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="14" style="text-align:center; padding:30px; color:#6c757d;">No Data Found</td>
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

<!-- BARCODE SCANNER MODAL -->
<style>
    .barcode-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 16px;
    }

    .barcode-modal-card {
        background: #ffffff;
        width: 100%;
        max-width: 580px;
        border-radius: 14px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }

    .barcode-modal-header {
        padding: 16px 20px;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .barcode-modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .barcode-modal-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 24px;
        cursor: pointer;
        line-height: 1;
        padding: 0;
    }

    .barcode-modal-close:hover {
        color: #ffffff;
    }

    .barcode-modal-body {
        padding: 20px;
        overflow-y: auto;
    }

    .scanner-controls-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
        background: #f1f5f9;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .camera-select-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 200px;
    }

    .camera-btn-wrap {
        display: flex;
        gap: 8px;
    }

    .scanner-view-box {
        position: relative;
        width: 100%;
        max-width: 520px;
        height: 300px;
        margin: 0 auto 14px auto;
        background: #000000;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #cameraPreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: #000;
    }

    .scan-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .scan-frame {
        position: relative;
        width: 80%;
        max-width: 320px;
        height: 160px;
        border: 1.5px dashed rgba(255, 255, 255, 0.4);
        box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.45);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scan-corner {
        position: absolute;
        width: 22px;
        height: 22px;
        border-color: #3b82f6;
        border-style: solid;
    }

    .scan-corner.top-left { top: -2px; left: -2px; border-width: 4px 0 0 4px; border-top-left-radius: 4px; }
    .scan-corner.top-right { top: -2px; right: -2px; border-width: 4px 4px 0 0; border-top-right-radius: 4px; }
    .scan-corner.bottom-left { bottom: -2px; left: -2px; border-width: 0 0 4px 4px; border-bottom-left-radius: 4px; }
    .scan-corner.bottom-right { bottom: -2px; right: -2px; border-width: 0 4px 4px 0; border-bottom-right-radius: 4px; }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 2px;
        background: #3b82f6;
        box-shadow: 0 0 10px #3b82f6;
        animation: scanAnimation 2.2s infinite ease-in-out;
    }

    @keyframes scanAnimation {
        0% { top: 6%; opacity: 0.8; }
        50% { top: 90%; opacity: 1; }
        100% { top: 6%; opacity: 0.8; }
    }

    .scan-text {
        position: absolute;
        bottom: 8px;
        font-size: 11px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 1.5px;
        background: rgba(0, 0, 0, 0.65);
        padding: 3px 10px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .camera-status-info {
        font-size: 13px;
        text-align: center;
        color: #475569;
        margin: 10px 0 14px 0;
        padding: 8px 12px;
        background: #f8fafc;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    .btn-scanner-back {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        border: none;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-scanner-back:hover {
        background: rgba(255, 255, 255, 0.28);
    }

    .manual-search-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 14px;
        border-radius: 10px;
        margin-top: 15px;
    }

    .manual-search-title {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .manual-search-form {
        display: flex;
        gap: 8px;
    }

    .manual-search-input {
        flex: 1;
        padding: 9px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        font-family: monospace;
    }

    .manual-search-btn {
        background: #2563eb;
        color: #fff;
        border: none;
        padding: 9px 16px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }

    .manual-search-btn:hover {
        background: #1d4ed8;
    }

    .alert-status {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .alert-success-bg {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .alert-danger-bg {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 16px;
        border-radius: 10px;
    }

    .detail-item {
        font-size: 13px;
    }

    .detail-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        display: block;
        margin-bottom: 2px;
    }

    .detail-value {
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }

    .barcode-display-box {
        grid-column: span 2;
        text-align: center;
        background: #fff;
        padding: 12px;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        margin-bottom: 4px;
    }

    .barcode-display-box svg {
        max-width: 220px;
        height: 48px;
    }

    .modal-actions {
        padding: 16px 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>

<!-- PAGE SVG BARCODE RENDERING -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        <?php foreach ($rows as $row): ?>
            if ("<?= addslashes($row['barCode'] ?? '') ?>") {
                try {
                    JsBarcode("#barcode_<?= md5($row['id']) ?>", "<?= addslashes($row['barCode']) ?>", {
                        format: "CODE128",
                        width: 1.2,
                        height: 24,
                        displayValue: false
                    });
                } catch(e) {}
            }
        <?php endforeach; ?>
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function printCurrentScannedLabel() {
        if (!currentScannedData || !currentScannedData.barCode) return;
        printSingleBarcodeLabel(currentScannedData.barCode, currentScannedData.spareName, currentScannedData.partNo);
    }

    function printSingleBarcodeLabel(barcode, spareName, partNo) {
        let w = window.open('', '_blank');
        let html = `
        <html>
        <head>
            <title>Print Barcode - ${barcode}</title>
            <style>
                @page { size: auto; margin: 0; }
                body { font-family: 'Courier New', monospace, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #fff; }
                .label-card { border: 2px dashed #000; padding: 15px 20px; text-align: center; width: 260px; border-radius: 8px; }
                .company { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
                .barcode-svg { max-width: 100%; height: 50px; }
                .barcode-num { font-size: 16px; font-weight: bold; letter-spacing: 2px; margin-top: 4px; }
                .item-name { font-size: 13px; font-weight: bold; margin-top: 8px; text-transform: uppercase; word-wrap: break-word; }
                .part-no { font-size: 12px; color: #444; margin-top: 3px; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="label-card">
                <div class="company">* Sunder BILLING *</div>
                <svg id="labelSvg"></svg>
                <div class="barcode-num">${barcode}</div>
                <div class="item-name">${spareName || ''}</div>
                <div class="part-no">Part #: ${partNo || '-'}</div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
            <script>
                window.onload = function() {
                    try {
                        JsBarcode("#labelSvg", "${barcode}", {
                            format: "CODE128",
                            width: 1.8,
                            height: 48,
                            displayValue: false
                        });
                    } catch(e) {}
                    setTimeout(function() { window.print(); }, 500);
                }
            <\/script>
        </body>
        </html>
        `;
        w.document.write(html);
        w.document.close();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Function to generate and print 4-column Barcode Sticker Labels (Image 4)
    function printAllBarcodeLabels() {
        let items = [
            <?php foreach ($rows as $r): ?>
                { code: "<?= addslashes($r['barCode']) ?>", price: "<?= number_format($r['sellingPricePerUnit'], 0) ?>" },
            <?php endforeach; ?>
        ];

        if (!items.length) { alert("No stock records to print!"); return; }

        let w = window.open('', '_blank');
        let labelHtml = `<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 10px; font-family: sans-serif;">`;
        
        items.forEach((item, idx) => {
            if (!item.code) return;
            labelHtml += `
                <div style="border: 1px solid #ccc; padding: 6px; text-align: center; border-radius: 4px; background: #fff;">
                    <div style="font-size: 11px; font-weight: bold;">* Sunder *</div>
                    <svg id="lblSvg_${idx}"></svg>
                    <div style="font-size: 10px; font-family: monospace; font-weight: bold; margin-top: 2px;">
                        ${item.code} &nbsp;&nbsp;&nbsp; Rs: ${item.price}
                    </div>
                </div>
            `;
        });

        labelHtml += `</div>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
        <script>
            window.onload = function() {
                itemsData = ${JSON.stringify(items)};
                itemsData.forEach((item, idx) => {
                    if (item.code) {
                        try {
                            JsBarcode("#lblSvg_" + idx, item.code, { format: "CODE128", width: 1.2, height: 32, displayValue: false });
                        } catch(e) {}
                    }
                });
                setTimeout(() => { window.print(); }, 600);
            }
        <\/script>`;

        w.document.write("<html><head><title>Barcode Sticker Sheet</title></head><body style='margin:0; padding:0;'>" + labelHtml + "</body></html>");
        w.document.close();
    }
</script>

<?php include("../includes/footer.php"); ?>