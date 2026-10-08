<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $modelName = mysqli_real_escape_string($conn, trim($_POST['modelName'] ?? ''));
    $now = date('Y-m-d H:i:s');
    $user = "System Admin";

    if ($_POST['action'] == 'add' && !empty($modelName)) {
        $check = mysqli_query($conn, "SELECT id FROM model WHERE model = '$modelName'");
        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('Error: This model already exists!'); window.location.href='list.php';</script>";
            exit();
        }
        $sql = "INSERT INTO model (model, createdBy, createdOn, modifiedBy, modifiedOn) VALUES ('$modelName', '$user', '$now', '$user', '$now')";
        mysqli_query($conn, $sql);
    } elseif ($_POST['action'] == 'update' && isset($_POST['id']) && !empty($modelName)) {
        $id = (int)$_POST['id'];
        $check = mysqli_query($conn, "SELECT id FROM model WHERE model = '$modelName' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('Error: Another model with this name already exists!'); window.location.href='list.php';</script>";
            exit();
        }
        $sql = "UPDATE model SET model = '$modelName', modifiedBy = '$user', modifiedOn = '$now' WHERE id = $id";
        mysqli_query($conn, $sql);
    }
    echo "<script>window.location.href='list.php';</script>";
    exit();
}
?>
<?php include("../includes/header.php"); ?>

<?php
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$name = $_GET['name'] ?? '';
if (!empty($name)) {
    $safe = mysqli_real_escape_string($conn, $name);
    $where .= " AND model LIKE '%$safe%'";
}

$countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM model $where");
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$query = "SELECT * FROM model $where ORDER BY id ASC LIMIT $limit OFFSET $offset";
$models = mysqli_query($conn, $query);

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<style>
    .erp-container {
        max-width: 1000px;
        margin: 25px auto;
        padding: 0 15px;
    }

    input:focus {
        outline: none;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }

    button:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }

    button:active {
        transform: translateY(0);
    }

    .filter-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 320px;
        height: 100%;
        background: #ffffff;
        padding: 25px;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
        transform: translateX(100%);
        transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1050;
        box-sizing: border-box;
    }

    .filter-drawer.active {
        transform: translateX(0);
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(3px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1040;
    }

    .overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
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

<div class="page-main-container erp-container" style="padding: 20px;">

    <div class="erp-header-bar">
        <div class="erp-header-title">
            <span style="margin-right: 8px;">📐</span>Manage Models
        </div>

        <div class="erp-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="focusAddModelField()" style="background: #2563eb; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">➕ New Model</button>
            <button type="button" onclick="window.location.href='list.php'" style="background: #475569; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(71, 85, 105, 0.2);">🔄 Refresh</button>
            <button type="button" onclick="openFilter()" style="background: #d97706; color: #ffffff; border: none; font-weight: 600; font-size: 13px; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">🔽 Filter</button>
        </div>
    </div>

    <div class="erp-table-box" style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); width: 100%;">
        <table class="erp-table master-table" style="width: 100%; border-collapse: collapse; min-width: 0;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="width: 45px; padding: 12px 10px; text-align: center; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase;">#</th>
                    <th style="padding: 12px 10px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase;">Model Name</th>
                    <th style="text-align: right; width: 100px; padding: 12px 12px; color: #475569; font-weight: 700; font-size: 13px; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($totalRows > 0) {
                    $i = $offset + 1;
                    while ($row = mysqli_fetch_assoc($models)): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;"
                            onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                            <td style="padding: 10px 10px; color: #64748b; font-size: 13.5px; font-weight: 600; width: 45px; text-align: center;"><?= $i++ ?></td>
                            <td colspan="2" style="padding: 8px 12px;">
                                <form action="list.php" method="POST"
                                    style="display: flex; gap: 8px; width: 100%; align-items: center;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="text" name="modelName" value="<?= htmlspecialchars($row['model']) ?>" required
                                        style="flex: 1; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13.5px; background: #fff; box-sizing: border-box;">
                                    <button type="submit"
                                        style="background: #475569; color: #fff; border: none; padding: 7px 16px; border-radius: 6px; font-weight: 600; font-size: 12.5px; cursor: pointer; flex-shrink: 0; white-space: nowrap;">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile;
                } else { ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 40px; color: #64748b; font-size: 15px; font-weight: 500;">
                            No Models Found
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr id="addModelSection" style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <td style="padding: 10px 10px; color: #2563eb; font-size: 13px; font-weight: 700; width: 45px; text-align: center;">Add</td>
                    <td colspan="2" style="padding: 8px 12px;">
                        <form action="list.php" method="POST" style="display: flex; gap: 8px; width: 100%; align-items: center;">
                            <input type="hidden" name="action" value="add">
                            <input type="text" name="modelName" id="newModelInput" placeholder="New Model Name" required
                                style="flex: 1; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13.5px; background: #fff; box-sizing: border-box; transition: all 0.3s ease;">
                            <button type="submit"
                                style="background: #2563eb; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; flex-shrink: 0; white-space: nowrap; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">Add</button>
                        </form>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

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

<div id="filterDrawer" class="filter-drawer">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
        <h3 style="margin: 0; color: #0f172a; font-size: 17px; font-weight: 700;">Filter Models</h3>
        <button type="button" onclick="closeFilter()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
    </div>

    <form method="GET">
        <div style="margin-bottom: 15px;">
            <label style="font-weight: 700; font-size: 13px; color: #334155;">Model Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="filter-input" placeholder="Search by name...">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 25px;">
            <button type="button" onclick="clearFilter(event, 'list.php')" style="background: #e2e8f0; color: #475569; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; font-size: 13.5px; cursor: pointer;">Clear</button>
            <button type="submit" style="flex: 1; background: #0f172a; color: #FDD017; border: none; padding: 10px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer;">Apply Filter</button>
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
function clearFilter(e, targetUrl) {
    if (e) e.preventDefault();
    const input = document.querySelector('#filterDrawer .filter-input');
    if (input) input.value = '';
    closeFilter();
    if (window.location.search.length > 1) {
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 320);
    }
}

function focusAddModelField() {
    const input = document.getElementById('newModelInput');
    if (input) {
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
            input.focus();
            input.style.borderColor = '#f97316';
            input.style.boxShadow = '0 0 0 4px rgba(249, 115, 22, 0.25)';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('focus') === 'add' || window.location.hash === '#add') {
        focusAddModelField();
    }
});
</script>

<?php include("../includes/footer.php"); ?>
