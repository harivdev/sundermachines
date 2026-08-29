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

<style>
    .erp-container {
        max-width: 1100px;
        margin: 25px auto;
        padding: 0 15px;
    }

    .badge-active {
        background: #10b981;
        color: #ffffff;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.2);
    }

    .badge-inactive {
        background: #ef4444;
        color: #ffffff;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(239, 68, 68, 0.2);
    }

    .btn-edit-action {
        background: #f59e0b;
        color: #ffffff;
        padding: 7px 16px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px rgba(245, 158, 11, 0.25);
    }

    .btn-edit-action:hover {
        background: #d97706;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(217, 119, 6, 0.35);
    }

    .machine-table-cell-link {
        color: #1e293b;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }

    .machine-table-cell-link:hover {
        color: #d97706;
    }

    /* FILTER DRAWER STYLING */
    .filter-drawer {
        position: fixed;
        top: 0;
        right: -320px;
        width: 300px;
        height: 100%;
        background: #ffffff;
        padding: 25px;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
        transition: right 0.3s ease-in-out;
        z-index: 1050;
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
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(2px);
        display: none;
        z-index: 1040;
    }

    .overlay.active {
        display: block;
    }

    .filter-input {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        margin-top: 6px;
        margin-bottom: 20px;
        box-sizing: border-box;
    }

    .filter-input:focus {
        outline: none;
        border-color: #d97706;
    }
</style>

<div class="erp-container">

    <!-- HEADER BAR -->
    <div class="erp-header-bar">
        <div class="erp-header-title">
            <span style="margin-right: 8px;">⚙️</span>Manage Machines
        </div>

        <div class="erp-header-actions">
            <a href="add_machine.php" class="btn-erp btn-erp-new">+ New Machine</a>
            <button type="button" onclick="location.reload()" class="btn-erp btn-erp-secondary">⟳ Refresh</button>
            <button type="button" onclick="openFilter()" class="btn-erp btn-erp-primary">⛃ Filter</button>
        </div>
    </div>

    <!-- TABLE BOX -->
    <div class="erp-table-box" style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <table class="erp-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="width: 60px; padding: 14px 18px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase;">#</th>
                    <th style="padding: 14px 18px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase;">Machine Name</th>
                    <th style="padding: 14px 18px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase; text-align: center; width: 120px;">Active</th>
                    <th style="padding: 14px 18px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase; text-align: center; width: 140px;">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($totalRows > 0) {
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($res)) {
                        $isActive = (ord($row['active'] ?? 1) == 1 || $row['active'] == 1);
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                            <td style="padding: 14px 18px; color: #64748b; font-size: 14px; font-weight: 600;"><?= $i++ ?></td>

                            <td style="padding: 14px 18px; font-size: 14.5px;">
                                <a href="edit_machine.php?id=<?= $row['id'] ?>" class="machine-table-cell-link">
                                    <?= htmlspecialchars($row['machineName']) ?>
                                </a>
                            </td>

                            <td style="padding: 14px 18px; text-align: center;">
                                <?php if ($isActive): ?>
                                    <span class="badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px 18px; text-align: center;">
                                <a href="edit_machine.php?id=<?= $row['id'] ?>" class="btn-edit-action">
                                    <span>✏️</span> Edit
                                </a>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #64748b; font-size: 15px; font-weight: 500;">
                            No Machines Found
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; font-size: 14px; color: #64748b;">
        <div>
            <?php 
            $startRecord = $totalRows > 0 ? $offset + 1 : 0;
            $endRecord = min($offset + $limit, $totalRows);
            ?>
            Showing <strong><?= $startRecord ?>–<?= $endRecord ?></strong> of <strong><?= $totalRows ?></strong> records &nbsp;|&nbsp; Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong>
        </div>

        <div style="display: flex; gap: 6px; align-items: center;">
            <?php if ($page <= 1): ?>
                <span style="padding: 7px 14px; background: #e2e8f0; border-radius: 6px; color: #94a3b8; cursor: not-allowed; font-weight: 600; font-size: 13px;">First</span>
                <span style="padding: 7px 14px; background: #e2e8f0; border-radius: 6px; color: #94a3b8; cursor: not-allowed; font-weight: 600; font-size: 13px;">Previous</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=1" style="padding: 7px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 13px;">First</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page - 1 ?>" style="padding: 7px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 13px;">Previous</a>
            <?php endif; ?>

            <span style="padding: 7px 14px; background: #0f172a; color: #FDD017; border-radius: 6px; font-weight: 700; font-size: 13px;"><?= $page ?></span>

            <?php if ($page >= $totalPages): ?>
                <span style="padding: 7px 14px; background: #e2e8f0; border-radius: 6px; color: #94a3b8; cursor: not-allowed; font-weight: 600; font-size: 13px;">Next</span>
                <span style="padding: 7px 14px; background: #e2e8f0; border-radius: 6px; color: #94a3b8; cursor: not-allowed; font-weight: 600; font-size: 13px;">Last</span>
            <?php else: ?>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $page + 1 ?>" style="padding: 7px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 13px;">Next</a>
                <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $totalPages ?>" style="padding: 7px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 13px;">Last</a>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- FILTER DRAWER -->
<div id="filterDrawer" class="filter-drawer">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; color: #0f172a; font-size: 17px; font-weight: 700;">Filter Machines</h3>
        <button type="button" onclick="closeFilter()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
    </div>

    <form method="GET">
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 700; font-size: 13px; color: #334155;">Machine Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="filter-input" placeholder="Search by name...">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 25px;">
            <button type="submit" style="flex: 1; background: #0f172a; color: #FDD017; border: none; padding: 10px; border-radius: 6px; font-weight: 700; cursor: pointer;">Apply Filter</button>
            <a href="list_machine.php" style="background: #e2e8f0; color: #475569; padding: 10px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">Clear</a>
        </div>
    </form>
</div>

<div id="overlay" class="overlay" onclick="closeFilter()"></div>

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