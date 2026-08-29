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
    $where .= " AND p.orderStatus = '$safeStatus'";
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

    <!-- HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Purchase Orders</div>
        <div class="erp-header-actions">
            <a href="create.php" class="btn-erp btn-erp-new">
                <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New Purchase
            </a>
            <a href="javascript:void(0)" onclick="location.reload()" class="btn-erp btn-erp-secondary">
                🔄 Refresh
            </a>
            <a href="javascript:void(0)" onclick="document.getElementById('purchaseFilter').style.display = document.getElementById('purchaseFilter').style.display === 'none' ? 'block' : 'none'" class="btn-erp btn-erp-secondary">
                🔽 Filter
            </a>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="purchaseFilter" class="erp-filter-panel" style="display:<?= ($search !== '' || $statusFilter !== '') ? 'block' : 'none' ?>;">
        <form method="GET" class="erp-filter-form">
            <div>
                <label class="erp-label">Search Order# / Supplier</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Order # or supplier..." class="erp-input" style="width:220px;">
            </div>
            <div>
                <label class="erp-label">Status</label>
                <select name="status" class="erp-select" style="width:160px;">
                    <option value="">-- All --</option>
                    <option value="New" <?= $statusFilter === 'New' ? 'selected' : '' ?>>New</option>
                    <option value="Ordered" <?= $statusFilter === 'Ordered' ? 'selected' : '' ?>>Ordered</option>
                    <option value="Received" <?= $statusFilter === 'Received' ? 'selected' : '' ?>>Received</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn-erp btn-erp-primary">Apply</button>
                <a href="purchase_list.php" class="btn-erp btn-erp-warning">Clear</a>
            </div>
        </form>
    </div>

    <!-- MAIN TABLE -->
    <div class="erp-table-box">
        <table class="erp-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Order No</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th style="text-align:center; width:100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($totalRows > 0):
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($res)):
                        $st = htmlspecialchars($row['orderStatus'] ?? 'New');
                        $badgeClass = 'erp-badge-info';
                        if ($st === 'Completed' || $st === 'Received') $badgeClass = 'erp-badge-completed';
                        else if ($st === 'Ordered') $badgeClass = 'erp-badge-pending';
                        else if ($st === 'New') $badgeClass = 'erp-badge-new';
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
                        <span class="erp-badge <?= $badgeClass ?>"><?= $st ?></span>
                    </td>
                    <td style="font-weight:700;">₹<?= number_format(round((float)$row['actualAmountSum'])) ?></td>
                    <td style="color:#16a34a; font-weight:700;">₹<?= number_format(round((float)$row['paidAmountSum'])) ?></td>
                    <td style="text-align:center;">
                        <a href="edit_purchase.php?id=<?= $row['id'] ?>" class="btn-erp btn-erp-warning btn-erp-sm">
                            ✏️ Edit
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:30px; color:#64748b;">
                        No purchases found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
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
