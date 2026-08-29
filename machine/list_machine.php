<?php require_once("../config/db.php"); ?>
<?php require_once("../includes/auth.php"); ?>
<?php requireAdmin(); ?>
<?php include("../includes/header.php"); ?>

<?php
// ================= PAGINATION =================
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ================= FILTER =================
$where = "WHERE 1=1";

$name = $_GET['name'] ?? '';

if (!empty($name)) {
    $safe = mysqli_real_escape_string($conn, $name);
    $where .= " AND machineName LIKE '%$safe%'";
}

// ================= COUNT =================
$countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM machine $where");
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// ================= DATA =================
$query = "SELECT * FROM machine $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$res = mysqli_query($conn, $query);

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div class="erp-container">

    <!-- HEADER -->
    <div class="erp-header-bar">
        <div class="erp-header-title">Manage Machines</div>

        <div class="erp-header-actions">
            <a href="add_machine.php" class="btn-erp btn-erp-new">+ New</a>
            <button onclick="location.reload()" class="btn-erp btn-erp-secondary">⟳ Refresh</button>
            <button onclick="openFilter()" class="btn-erp btn-erp-primary">⛃ Filter</button>
        </div>
    </div>

    <!-- TABLE -->
    <div class="erp-table-box">
        <table class="erp-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Name</th>
                    <th>Active</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($totalRows > 0) {
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($res)) {
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>

                            <td>
                                <a href="edit_machine.php?id=<?= $row['id'] ?>" class="link">
                                    <?= htmlspecialchars($row['machineName']) ?>
                                </a>
                            </td>

                            <td>
                                <?= $row['active'] ? '<span class="yes">Yes</span>' : '<span class="no">No</span>' ?>
                            </td>

                            <td style="text-align:center;">
                                <a href="edit_machine.php?id=<?= $row['id'] ?>" class="edit-btn">✏️</a>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="4" class="no-data">No Data Found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination" style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; font-size:14px; color:#64748b;">
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

            <a style="padding:6px 12px; background:#2563eb; color:#fff; border-radius:5px; text-decoration:none; font-weight:bold;"><?= $page ?></a>

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

<!-- FILTER DRAWER -->
<div id="filterDrawer" class="filter-drawer">
    <h3>Filter</h3>

    <form method="GET">

        <label>Machine Name</label>
        <input type="text" name="name" value="<?= $name ?>" class="input">

        <br>

        <button class="btn blue">Apply</button>
        <a href="list_machine.php" class="btn yellow">Clear</a>
        <button type="button" onclick="closeFilter()" class="btn gray">Cancel</button>

    </form>
</div>

<div id="overlay" class="overlay" onclick="closeFilter()"></div>

<style>
    body {
        background: #f1f5f9;
        font-family: sans-serif;
    }

    .container {
        width: 95%;
        margin: auto;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        color: #fff;
        text-decoration: none;
    }

    .green {
        background: #10b981;
    }

    .gray {
        background: #64748b;
    }

    .blue {
        background: #2563eb;
    }

    .yellow {
        background: #f59e0b;
    }

    .table-box {
        overflow: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f8fafc;
        padding: 12px;
        font-size: 12px;
        text-transform: uppercase;
        color: #64748b;
    }

    td {
        padding: 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    .link {
        color: #2563eb;
        text-decoration: underline;
    }

    .yes {
        color: #16a34a;
        font-weight: 600;
    }

    .no {
        color: #dc2626;
        font-weight: 600;
    }

    .edit-btn {
        font-size: 18px;
        text-decoration: none;
    }

    .no-data {
        text-align: center;
        padding: 30px;
        color: #64748b;
    }

    /* FILTER DRAWER */
    .filter-drawer {
        position: fixed;
        top: 0;
        right: -300px;
        width: 280px;
        height: 100%;
        background: #fff;
        padding: 20px;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
        transition: 0.3s;
        z-index: 1000;
    }

    .filter-drawer.active {
        right: 0;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        display: none;
    }

    .overlay.active {
        display: block;
    }

    .input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        margin-bottom: 10px;
    }
</style>

<script>
    function openFilter() {
        document.getElementById("filterDrawer").classList.add("active");
        document.getElementById("overlay").classList.add("active");
    }
    function closeFilter() {
        document.getElementById("filterDrawer").classList.remove("active");
        document.getElementById("overlay").classList.remove("active");
    }
</script>

<?php include("../includes/footer.php"); ?>