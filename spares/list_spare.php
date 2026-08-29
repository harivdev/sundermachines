<?php require_once("../config/db.php"); ?>
<?php require_once("../includes/auth.php"); ?>
<?php requireLogin(); ?>
<?php include("../includes/header.php"); ?>

<?php
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (spareName LIKE '%$safeSearch%' OR partNo LIKE '%$safeSearch%' OR rackNumber LIKE '%$safeSearch%')";
}

$countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM spares $where");
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$res = mysqli_query($conn, "SELECT * FROM spares $where ORDER BY id DESC LIMIT $limit OFFSET $offset");

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div class="erp-container">

    <!-- HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Spares</div>
        <div class="erp-header-actions">
            <?php if (isAdmin()): ?>
                <a href="add_spare.php" class="btn-erp btn-erp-new">
                    <span style="background: #ffffff; color: #1e293b; padding: 1px 6px; border-radius: 4px; font-size: 11px;">+</span> New
                </a>
            <?php endif; ?>
            <button type="button" onclick="location.reload()" class="btn-erp btn-erp-secondary">🔄 Refresh</button>
            <button type="button" onclick="document.getElementById('filterPanel').style.display = document.getElementById('filterPanel').style.display === 'none' ? 'block' : 'none'" class="btn-erp btn-erp-secondary">🔽 Filter</button>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="filterPanel" class="erp-filter-panel" style="display:<?= $search !== '' ? 'block' : 'none' ?>;">
        <form method="GET" class="erp-filter-form">
            <div>
                <label class="erp-label">Search Spare / Part / Rack</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, part#, rack#..." class="erp-input" style="width:250px;">
            </div>
            <div>
                <button type="submit" class="btn-erp btn-erp-primary">Apply</button>
                <a href="list_spare.php" class="btn-erp btn-erp-warning">Clear</a>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="erp-table-box">
        <table class="erp-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Spare Name</th>
                    <th>Part #</th>
                    <th>Rack #</th>
                    <th>Active</th>
                    <th style="text-align:center; width:100px;">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($totalRows > 0) {
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($res)) {
                        $isActive = isset($row['active']) ? (int)$row['active'] : 1;
                ?>
                <tr>
                    <td style="font-weight:600; text-align:center;"><?= $i++ ?></td>

                    <td>
                        <a href="edit_spare.php?id=<?= $row['id'] ?>" style="color:#2563eb; text-decoration:underline; font-weight:700;">
                            <?= htmlspecialchars($row['spareName']) ?>
                        </a>
                    </td>

                    <td><?= htmlspecialchars($row['partNo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['rackNumber'] ?? '-') ?></td>

                    <td>
                        <span class="erp-badge <?= $isActive ? 'erp-badge-completed' : 'erp-badge-new' ?>">
                            <?= $isActive ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>

                    <td style="text-align:center;">
                        <a href="edit_spare.php?id=<?= $row['id'] ?>" class="btn-erp btn-erp-warning btn-erp-sm">
                            ✏️ Edit
                        </a>
                    </td>
                </tr>
                <?php } } else { ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No spares available</td>
                </tr>
                <?php } ?>
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