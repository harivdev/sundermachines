<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = trim($_POST['delete_id']);
    if ($delete_id !== '') {
        $chkStk = mysqli_prepare($conn, "SELECT id, itemName, barCode FROM stock WHERE id = ?");
        mysqli_stmt_bind_param($chkStk, "s", $delete_id);
        mysqli_stmt_execute($chkStk);
        $resStk = mysqli_stmt_get_result($chkStk);
        $stkData = mysqli_fetch_assoc($resStk);

        if ($stkData) {
            // Check if item is linked to salesitems or jobcarditemspares
            $chkSales = mysqli_prepare($conn, "SELECT COUNT(*) FROM salesitems WHERE stock = ?");
            mysqli_stmt_bind_param($chkSales, "s", $delete_id);
            mysqli_stmt_execute($chkSales);
            $resSales = mysqli_stmt_get_result($chkSales);
            $salesCount = (int)mysqli_fetch_row($resSales)[0];

            $chkJc = mysqli_prepare($conn, "SELECT COUNT(*) FROM jobcarditemspares WHERE stock = ?");
            mysqli_stmt_bind_param($chkJc, "s", $delete_id);
            mysqli_stmt_execute($chkJc);
            $resJc = mysqli_stmt_get_result($chkJc);
            $jcCount = (int)mysqli_fetch_row($resJc)[0];

            if ($salesCount > 0 || $jcCount > 0) {
                $refs = [];
                if ($salesCount > 0) $refs[] = "$salesCount sales record(s)";
                if ($jcCount > 0) $refs[] = "$jcCount job card(s)";
                $_SESSION['reorder_error'] = "Cannot delete '" . htmlspecialchars($stkData['itemName']) . "' because it is referenced in " . implode(' and ', $refs) . ". You can update its quantity to 0 instead.";
            } else {
                $stmtDel = mysqli_prepare($conn, "DELETE FROM stock WHERE id = ?");
                if ($stmtDel) {
                    mysqli_stmt_bind_param($stmtDel, "s", $delete_id);
                    if (mysqli_stmt_execute($stmtDel) && mysqli_stmt_affected_rows($stmtDel) > 0) {
                        $_SESSION['reorder_success'] = "Stock item '" . htmlspecialchars($stkData['itemName']) . "' (" . htmlspecialchars($stkData['barCode']) . ") deleted successfully.";
                    } else {
                        $_SESSION['reorder_error'] = "Could not delete stock item. Please try again.";
                    }
                }
            }
        } else {
            $_SESSION['reorder_error'] = "Stock item not found or already deleted.";
        }
    }

    $redirectUrl = 'reorder_level.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirectUrl .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: $redirectUrl");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = "WHERE 1=1";

if ($filter === 'critical') {
    $where .= " AND st.availableQty <= st.minQty AND st.availableQty > 0 AND st.minQty > 0";
} elseif ($filter === 'reorder') {
    $where .= " AND st.availableQty <= st.reorderLevel AND st.availableQty > st.minQty AND st.reorderLevel > 0";
} elseif ($filter === 'out') {
    $where .= " AND st.availableQty <= 0";
} elseif ($filter === 'optimal') {
    $where .= " AND st.availableQty > st.reorderLevel";
}

$rawQ = trim($_GET['q'] ?? '');
if ($rawQ !== '') {
    $safeQ = mysqli_real_escape_string($conn, $rawQ);
    $directCondition = "(s.spareName LIKE '%$safeQ%' OR st.barCode LIKE '%$safeQ%' OR st.itemName LIKE '%$safeQ%' OR s.partNo LIKE '%$safeQ%' OR b.brandName LIKE '%$safeQ%' OR m.model LIKE '%$safeQ%')";

    $words = array_filter(preg_split('/\s+/', $rawQ));
    if (count($words) > 1) {
        $wordConds = [];
        foreach ($words as $w) {
            $escapedW = mysqli_real_escape_string($conn, $w);
            $wordConds[] = "(s.spareName LIKE '%$escapedW%' OR st.barCode LIKE '%$escapedW%' OR st.itemName LIKE '%$escapedW%' OR s.partNo LIKE '%$escapedW%' OR b.brandName LIKE '%$escapedW%' OR m.model LIKE '%$escapedW%')";
        }
        $where .= " AND ($directCondition OR (" . implode(" AND ", $wordConds) . "))";
    } else {
        $where .= " AND $directCondition";
    }
}

$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) as total FROM stock st LEFT JOIN spares s ON st.spare=s.id LEFT JOIN brand b ON st.brand = b.id LEFT JOIN model m ON st.model = m.id $where";
$countResult = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

$query = "
SELECT
    st.id,
    st.barCode,
    st.itemName,
    st.availableQty,
    st.minQty,
    st.maxQty,
    st.reorderLevel,
    s.spareName,
    s.partNo,
    b.brandName,
    m.model as modelName
FROM stock st
LEFT JOIN spares s ON st.spare = s.id
LEFT JOIN brand b ON st.brand = b.id
LEFT JOIN model m ON st.model = m.id
$where
ORDER BY
    CASE
        WHEN st.availableQty <= 0 THEN 1
        WHEN st.minQty > 0 AND st.availableQty <= st.minQty THEN 2
        WHEN st.reorderLevel > 0 AND st.availableQty <= st.reorderLevel THEN 3
        ELSE 4
    END,
    st.id DESC
LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $query);
$rows = [];
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
}

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<?php include("../includes/header.php"); ?>

<style>
    body {
        background: var(--bg-body, #F9F9F8);
    }

    .container-box {
        width: 96%;
        max-width: 1400px;
        margin: 30px auto;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark, #2c3338);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }
    .filter-btn:hover { background: #f8fafc; }
    .filter-btn.active {
        background: var(--brand-gold, #C9A227);
        color: #fff;
        border-color: var(--brand-gold, #C9A227);
    }

    .search-box {
        display: flex;
        gap: 8px;
    }
    .search-box input {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        width: 250px;
        outline: none;
    }
    .search-box button {
        padding: 8px 16px;
        background: #1e293b;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .table-box {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.05);
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    th, td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #f1f5f9;
    }

    th {
        background: #f8fafc;
        font-size: 13px;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    td {
        font-size: 14px;
        color: #334155;
    }

    tr:hover { background: #fcfcfc; }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        gap: 6px;
    }
    .badge-critical { background: #fee2e2; color: #b91c1c; }
    .badge-reorder { background: #fef3c7; color: #b45309; }
    .badge-out { background: #f1f5f9; color: #475569; }
    .badge-optimal { background: #dcfce3; color: #15803d; }

    .health-bar-container {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        margin-top: 6px;
        overflow: hidden;
    }
    .health-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    .fill-critical { background: #ef4444; }
    .fill-reorder { background: #f59e0b; }
    .fill-healthy { background: #10b981; }

    .qty-bold { font-weight: 700; font-size: 16px; }

    .suggested-qty {
        font-weight: 700;
        color: #2563eb;
        background: #eff6ff;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 20px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .page-btn {
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
    }
    .page-btn:hover { background: #f1f5f9; }

    .modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 9999;
        padding: 16px;
    }
    .modal-box {
        background: #fff; padding: 24px; border-radius: 8px; width: 100%; max-width: 400px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .modal-btn {
        padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 14px;
    }
    .btn-cancel { background: #e2e8f0; color: #475569; }
    .btn-delete { background: #dc2626; color: #fff; }
</style>

<div class="container-box">
    <?php if (!empty($_SESSION['reorder_success'])): ?>
        <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500;">
            <i class="fa-solid fa-circle-check" style="font-size:16px;"></i>
            <span><?= htmlspecialchars($_SESSION['reorder_success']) ?></span>
        </div>
        <?php unset($_SESSION['reorder_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['reorder_error'])): ?>
        <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
            <span><?= htmlspecialchars($_SESSION['reorder_error']) ?></span>
        </div>
        <?php unset($_SESSION['reorder_error']); ?>
    <?php endif; ?>

    <div class="header-actions">
        <div class="page-title">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--brand-gold)"></i> Stock Reorder Monitor
        </div>
        <div class="filter-group">
            <?php
            $buildFilterUrl = function($targetFilter) use ($queryParams) {
                $p = $queryParams;
                $p['filter'] = $targetFilter;
                return '?' . http_build_query($p);
            };
            ?>
            <a href="<?= $buildFilterUrl('all') ?>" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="<?= $buildFilterUrl('optimal') ?>" class="filter-btn <?= $filter === 'optimal' ? 'active' : '' ?>">Optimal</a>
            <a href="<?= $buildFilterUrl('reorder') ?>" class="filter-btn <?= $filter === 'reorder' ? 'active' : '' ?>">Reorder Required</a>
            <a href="<?= $buildFilterUrl('critical') ?>" class="filter-btn <?= $filter === 'critical' ? 'active' : '' ?>">Critical</a>
            <a href="<?= $buildFilterUrl('out') ?>" class="filter-btn <?= $filter === 'out' ? 'active' : '' ?>">Out of Stock</a>
        </div>
        <form class="search-box" method="GET" style="display:flex; align-items:center; gap:6px;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="q" placeholder="Search item, barcode, brand..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit">Search</button>
            <?php if (!empty($_GET['q'])): ?>
                <a href="?filter=<?= urlencode($filter) ?>" style="padding: 8px 12px; background: #e2e8f0; color: #475569; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Item & Barcode</th>
                    <th>Current Stock</th>
                    <th>Thresholds (Min/Max/Reorder)</th>
                    <th>Status</th>
                    <th>Suggested Buy</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px; color:#64748b;">No stock items match your filter criteria.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row):
                    $avail = (int)$row['availableQty'];
                    $min = (int)$row['minQty'];
                    $max = (int)$row['maxQty'];
                    $reorder = (int)$row['reorderLevel'];

                    $statusClass = '';
                    $statusText = '';
                    $fillClass = '';

                    if ($avail <= 0) {
                        $statusClass = 'badge-out';
                        $statusText = 'Out of Stock';
                    } elseif ($min == 0 && $max == 0 && $reorder == 0) {
                        $statusClass = 'badge-out';
                        $statusText = 'Not Configured';
                    } elseif ($min > 0 && $avail <= $min) {
                        $statusClass = 'badge-critical';
                        $statusText = 'Critical Level';
                    } elseif ($reorder > 0 && $avail <= $reorder) {
                        $statusClass = 'badge-reorder';
                        $statusText = 'Reorder Required';
                    } else {
                        $statusClass = 'badge-optimal';
                        $statusText = 'Optimal';
                    }

                    $percent = 0;
                    if ($max > 0) {
                        $percent = ($avail / $max) * 100;
                        if ($percent > 100) $percent = 100;
                    } elseif ($avail > 0) {
                        $percent = 100;
                    }

                    $suggestedBuy = 0;
                    if ($max > 0 && $avail < $max) {
                        $suggestedBuy = $max - $avail;
                    }
                    $itemDisplayName = !empty($row['itemName']) ? $row['itemName'] : (!empty($row['spareName']) ? $row['spareName'] : 'Item #' . $row['id']);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600; color:#0f172a; margin-bottom:4px;"><?= htmlspecialchars($itemDisplayName) ?></div>
                        <div style="font-size:12px; color:#000;"><i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($row['barCode'] ?? '') ?></div>
                    </td>
                    <td><span class="qty-bold"><?= $avail ?></span> units</td>
                    <td>
                        <div style="font-size: 12px; color: #000; margin-bottom:4px;">
                            Min: <b><?= $min ?></b> &nbsp;|&nbsp; Reorder: <b><?= $reorder ?></b> &nbsp;|&nbsp; Max: <b><?= $max ?></b>
                        </div>
                    </td>
                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                    <td>
                        <?php if ($suggestedBuy > 0): ?>
                            <span class="suggested-qty">+<?= $suggestedBuy ?></span>
                        <?php else: ?>
                            <span style="color:#000;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <a href="edit_stock.php?id=<?= urlencode($row['id']) ?>" title="Edit" style="color: #000; text-decoration: none; font-size: 16px; transition: color 0.2s;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#000'">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="javascript:void(0)" title="Delete" onclick="openDeleteModal('<?= $row['id'] ?>', '<?= htmlspecialchars(addslashes($itemDisplayName)) ?>', '<?= htmlspecialchars(addslashes($row['barCode'])) ?>')" style="color: #000; text-decoration: none; font-size: 16px; transition: color 0.2s;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#000'">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&<?= $queryString ?>" class="page-btn">&larr; Previous</a>
            <?php else: ?>
                <div style="width: 90px;"></div>
            <?php endif; ?>

            <div style="font-size: 14px; color:#475569;">
                Page <b><?= $page ?></b> of <b><?= $totalPages ?></b> (<?= $totalRows ?> items)
            </div>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&<?= $queryString ?>" class="page-btn">Next &rarr;</a>
            <?php else: ?>
                <div style="width: 90px;"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="margin-top:0; color:#0f172a; font-size:18px;">Confirm Deletion</h3>
        <p style="color:#475569; font-size:14px; margin-bottom:8px;">Are you sure you want to delete this stock item? This action cannot be undone.</p>

        <div style="background:#f8fafc; padding:12px; border-radius:6px; margin-bottom:20px; border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
            <div style="font-weight:600; color:#1e293b; margin-bottom:4px;" id="modalItemName"></div>
            <div style="font-size:12px; color:#64748b;"><i class="fa-solid fa-barcode"></i> <span id="modalBarcode"></span></div>
            <div id="modalLoadingLine" style="position: absolute; bottom: 0; left: 0; height: 3px; background: #dc2626; width: 0%; transition: width 2s linear;"></div>
        </div>

        <form id="deleteForm" method="POST" style="display:flex; justify-content:flex-end; gap:12px; margin:0;" onsubmit="return startDeleteAnimation(event)">
            <input type="hidden" name="delete_id" id="modalDeleteId">
            <button type="button" class="modal-btn btn-cancel" id="btnCancelDelete" onclick="closeDeleteModal()">Cancel</button>
            <button type="submit" class="modal-btn btn-delete" id="btnConfirmDelete">Delete</button>
        </form>
    </div>
</div>

<script>
function startDeleteAnimation(e) {
    e.preventDefault();

    document.getElementById('btnCancelDelete').disabled = true;
    document.getElementById('btnConfirmDelete').disabled = true;
    document.getElementById('btnConfirmDelete').innerText = 'Deleting...';
    document.getElementById('btnConfirmDelete').style.opacity = '0.7';

    document.getElementById('modalLoadingLine').style.width = '100%';

    setTimeout(function() {
        document.getElementById('deleteForm').submit();
    }, 2000);
}

function openDeleteModal(id, name, barcode) {
    document.getElementById('modalDeleteId').value = id;
    document.getElementById('modalItemName').innerText = name;
    document.getElementById('modalBarcode').innerText = barcode;

    document.getElementById('modalLoadingLine').style.width = '0%';
    document.getElementById('modalLoadingLine').style.transition = 'none';

    document.getElementById('modalLoadingLine').offsetHeight;
    document.getElementById('modalLoadingLine').style.transition = 'width 2s linear';

    document.getElementById('btnCancelDelete').disabled = false;
    document.getElementById('btnConfirmDelete').disabled = false;
    document.getElementById('btnConfirmDelete').innerText = 'Delete';
    document.getElementById('btnConfirmDelete').style.opacity = '1';

    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>

</body>
</html>

