<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
    exit();
}

// Delete Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    mysqli_query($conn, "DELETE FROM stock WHERE id = $delete_id");
    
    // Redirect to clear POST data and maintain current query params
    $redirectUrl = 'reorder_level.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirectUrl .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: $redirectUrl");
    exit();
}

// Filter Status
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

// Search
if (!empty($_GET['q'])) {
    $q = mysqli_real_escape_string($conn, $_GET['q']);
    $where .= " AND (s.spareName LIKE '%$q%' OR st.barCode LIKE '%$q%' OR st.itemName LIKE '%$q%')";
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) as total FROM stock st LEFT JOIN spares s ON st.spare=s.id $where";
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
    s.spareName
FROM stock st
LEFT JOIN spares s ON st.spare = s.id
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
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
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

    /* Progress Bar */
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

    /* Pagination */
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

    /* Modal Styles */
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
    <div class="header-actions">
        <div class="page-title">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--brand-gold)"></i> Stock Reorder Monitor
        </div>
        <div class="filter-group">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?filter=optimal" class="filter-btn <?= $filter === 'optimal' ? 'active' : '' ?>">Optimal</a>
            <a href="?filter=reorder" class="filter-btn <?= $filter === 'reorder' ? 'active' : '' ?>">Reorder Required</a>
            <a href="?filter=critical" class="filter-btn <?= $filter === 'critical' ? 'active' : '' ?>">Critical</a>
            <a href="?filter=out" class="filter-btn <?= $filter === 'out' ? 'active' : '' ?>">Out of Stock</a>
        </div>
        <form class="search-box" method="GET">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="q" placeholder="Search item, barcode..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit">Search</button>
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
                        <td colspan="7" style="text-align:center; padding: 40px; color:#64748b;">No stock items match your filter criteria.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): 
                    $avail = (int)$row['availableQty'];
                    $min = (int)$row['minQty'];
                    $max = (int)$row['maxQty'];
                    $reorder = (int)$row['reorderLevel'];

                    // Determine Status
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

                    // Calculate Percentage for Bar
                    $percent = 0;
                    if ($max > 0) {
                        $percent = ($avail / $max) * 100;
                        if ($percent > 100) $percent = 100;
                    } elseif ($avail > 0) {
                        // If no max set, just show 100% if we have stock
                        $percent = 100;
                    }

                    // Suggested Buy
                    $suggestedBuy = 0;
                    if ($max > 0 && $avail < $max) {
                        $suggestedBuy = $max - $avail;
                    }
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600; color:#0f172a; margin-bottom:4px;"><?= htmlspecialchars($row['itemName'] ?? $row['spareName']) ?></div>
                        <div style="font-size:12px; color:#64748b;"><i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($row['barCode']) ?></div>
                    </td>
                    <td><span class="qty-bold"><?= $avail ?></span> units</td>
                    <td>
                        <div style="font-size: 12px; color: #64748b; margin-bottom:4px;">
                            Min: <b><?= $min ?></b> &nbsp;|&nbsp; Reorder: <b><?= $reorder ?></b> &nbsp;|&nbsp; Max: <b><?= $max ?></b>
                        </div>
                        <?php if ($min == 0 && $max == 0 && $reorder == 0): ?>
                            <span style="font-size:11px; color:#ef4444; background:#fee2e2; padding:2px 6px; border-radius:4px;">Action Needed</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                    <td>
                        <?php if ($suggestedBuy > 0): ?>
                            <span class="suggested-qty">+<?= $suggestedBuy ?></span>
                        <?php else: ?>
                            <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <a href="edit_stock.php?id=<?= urlencode($row['id']) ?>" title="Edit" style="color: #64748b; text-decoration: none; font-size: 16px; transition: color 0.2s;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="javascript:void(0)" title="Delete" onclick="openDeleteModal('<?= $row['id'] ?>', '<?= htmlspecialchars(addslashes($row['itemName'] ?? $row['spareName'])) ?>', '<?= htmlspecialchars(addslashes($row['barCode'])) ?>')" style="color: #64748b; text-decoration: none; font-size: 16px; transition: color 0.2s;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#64748b'">
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

<!-- Delete Confirmation Modal -->
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
    
    // Disable buttons
    document.getElementById('btnCancelDelete').disabled = true;
    document.getElementById('btnConfirmDelete').disabled = true;
    document.getElementById('btnConfirmDelete').innerText = 'Deleting...';
    document.getElementById('btnConfirmDelete').style.opacity = '0.7';
    
    // Start animation
    document.getElementById('modalLoadingLine').style.width = '100%';
    
    // Submit after 2 seconds
    setTimeout(function() {
        document.getElementById('deleteForm').submit();
    }, 2000);
}

function openDeleteModal(id, name, barcode) {
    document.getElementById('modalDeleteId').value = id;
    document.getElementById('modalItemName').innerText = name;
    document.getElementById('modalBarcode').innerText = barcode;
    
    // Reset animation and buttons
    document.getElementById('modalLoadingLine').style.width = '0%';
    document.getElementById('modalLoadingLine').style.transition = 'none';
    
    // Force reflow to instantly apply width:0 before re-enabling transition
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
