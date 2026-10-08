<?php require_once("../config/db.php"); ?>
<?php require_once("../includes/auth.php"); ?>
<?php requireAdmin(); ?>
<?php include("../includes/header.php"); ?>

<?php
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.orderNo LIKE '%$safeSearch%' OR s.name LIKE '%$safeSearch%')";
}

if ($statusFilter !== '') {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    if ($safeStatus === 'Delivered') {
        $where .= " AND p.orderStatus IN ('Delivered', 'Received', 'Completed')";
    } else if ($safeStatus === 'Purchased') {
        $where .= " AND p.orderStatus IN ('Purchased', 'Ordered', 'New')";
    } else {
        $where .= " AND p.orderStatus = '$safeStatus'";
    }
}

$countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM purchase p LEFT JOIN supplier s ON p.supplier = s.id $where");
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$res = mysqli_query($conn, "SELECT p.*, s.name as supplierName FROM purchase p LEFT JOIN supplier s ON p.supplier = s.id $where ORDER BY p.id DESC LIMIT $limit OFFSET $offset");

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div class="erp-container">

    <div class="erp-header-bar">
        <div class="erp-header-title">Purchase Orders</div>
        <div class="erp-header-actions">
            <a href="create.php" class="btn-erp btn-erp-new">
                <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New Purchase
            </a>
            <a href="javascript:void(0)" onclick="location.reload()" class="btn-erp btn-erp-secondary">
                🔄 Refresh
            </a>
            <a href="print_summary.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn-erp" style="background:#16a34a; color:#ffffff; text-decoration:none; font-weight:600; font-size:13px; padding:7px 14px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(22,163,74,0.2); transition:all 0.15s ease;">
                <span style="font-size:13px;">📄</span> Print A4 Summary
            </a>
            <a href="javascript:void(0)" onclick="document.getElementById('purchaseFilter').style.display = document.getElementById('purchaseFilter').style.display === 'none' ? 'block' : 'none'" class="btn-erp btn-erp-secondary">
                🔽 Filter
            </a>
        </div>
    </div>

    <div id="purchaseFilter" class="erp-filter-panel" style="display:<?= ($search !== '' || $statusFilter !== '') ? 'block' : 'none' ?>;">
        <form method="GET" class="erp-filter-form">
            <div>
                <label class="erp-label">Search Order# / Supplier</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Order # or supplier..." class="erp-input" style="width:220px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div>
                <label class="erp-label">Status</label>
                <select name="status" class="erp-select" style="width:160px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="">-- All --</option>
                    <option value="Purchased" <?= $statusFilter === 'Purchased' ? 'selected' : '' ?>>Purchased</option>
                    <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            <div class="erp-filter-actions-group">
                <button type="submit" class="btn-erp btn-erp-apply">Apply</button>
                <a href="purchase_list.php" class="btn-erp btn-erp-clear">Clear</a>
            </div>
        </form>
    </div>

    <div class="erp-table-box">
        <table class="erp-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Order No</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th style="text-align:center; width:100px; padding-right:24px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($totalRows > 0):
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($res)):
                        $rawSt = $row['orderStatus'] ?? 'Purchased';
                        $displayStatus = in_array($rawSt, ['Delivered', 'Received', 'Completed']) ? 'Delivered' : 'Purchased';
                        $badgeClass = ($displayStatus === 'Delivered') ? 'erp-badge-completed' : 'erp-badge-new';
                ?>
                <tr>
                    <td style="font-weight:600; text-align:center;"><?= $i++ ?></td>
                    <td>
                        <a href="edit_purchase.php?id=<?= $row['id'] ?>" style="color:#2563eb; text-decoration:underline; font-weight:700;">
                            <?= htmlspecialchars($row['orderNo'] ?? '') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['orderDate'] ?? '-') ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($row['supplierName'] ?? 'N/A') ?></td>
                    <td>
                        <span class="erp-badge <?= $badgeClass ?>"><?= $displayStatus ?></span>
                    </td>
                    <td style="text-align:center; padding-right:24px;">
                        <a href="print_purchase.php?id=<?= $row['id'] ?>" target="_blank" style="color:#1e293b; font-weight:600; text-decoration:none; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                            🖨️ Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#64748b;">
                        No purchases found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="erp-pagination">
        <div>
            <?php
            $startRecord = $totalRows > 0 ? $offset + 1 : 0;
            $endRecord = min($offset + $limit, $totalRows);
            ?>
            Showing <?= $startRecord ?>–<?= $endRecord ?> of <?= $totalRows ?> records. &nbsp;|&nbsp; Page <?= $page ?> of <?= $totalPages ?>
        </div>

        <div class="erp-pagination-controls">
            <?php if ($page <= 1): ?>
                <span class="erp-pagination-btn disabled">First</span>
                <span class="erp-pagination-btn disabled">Previous</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=1" class="erp-pagination-btn">First</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page - 1 ?>" class="erp-pagination-btn">Previous</a>
            <?php endif; ?>

            <a class="erp-pagination-btn active"><?= $page ?></a>

            <?php if ($page >= $totalPages): ?>
                <span class="erp-pagination-btn disabled">Next</span>
                <span class="erp-pagination-btn disabled">Last</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page + 1 ?>" class="erp-pagination-btn">Next</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $totalPages ?>" class="erp-pagination-btn">Last</a>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include("../includes/footer.php"); ?>

