<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();
include("../includes/header.php");

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.name LIKE '%$safeSearch%' OR s.phoneNo1 LIKE '%$safeSearch%' OR s.emailId LIKE '%$safeSearch%' OR a.city LIKE '%$safeSearch%')";
}

$countQuery = "SELECT COUNT(*) AS total FROM supplier s LEFT JOIN address a ON s.address = a.id $where";
$countRes = mysqli_query($conn, $countQuery);
$totalRows = (int)mysqli_fetch_assoc($countRes)['total'];
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$query = "SELECT s.*, a.city, a.line1, a.line2 FROM supplier s LEFT JOIN address a ON s.address = a.id $where ORDER BY s.id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div style="padding: 20px; background: #f8fafc; min-height: calc(100vh - 110px);">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap:wrap; gap:10px;">
        <h3 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;">Supplier List</h3>
        <div style="display:flex; gap:10px;">
            <a href="add.php" style="background: #2563eb; color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                + New Supplier
            </a>
            <button onclick="location.reload()" style="background:#64748b; color:#fff; padding:10px 20px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px;">⟳ Refresh</button>
            <button onclick="document.getElementById('supplierFilter').style.display = document.getElementById('supplierFilter').style.display === 'none' ? 'block' : 'none'" style="background:#10b981; color:#fff; padding:10px 20px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px;">⛃ Filter</button>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div id="supplierFilter" style="display:<?= $search !== '' ? 'block' : 'none' ?>; background:#ffffff; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:15px;">
        <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">Search Supplier</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, contact, city..." style="padding:8px; border:1px solid #cbd5e1; border-radius:5px; font-size:14px; width:250px;">
            </div>
            <div>
                <button type="submit" style="background:#2563eb; color:#fff; padding:8px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer;">Apply</button>
                <a href="list.php" style="background:#f59e0b; color:#fff; padding:8px 16px; border-radius:5px; font-weight:600; text-decoration:none; display:inline-block;">Clear</a>
            </div>
        </form>
    </div>

    <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 25px; border: 1px solid #e2e8f0;">

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 15px; text-align: left; color: #64748b; font-weight: 700; font-size: 13px;">#</th>
                        <th style="padding: 15px; text-align: left; color: #64748b; font-weight: 700; font-size: 13px;">Name</th>
                        <th style="padding: 15px; text-align: left; color: #64748b; font-weight: 700; font-size: 13px;">Contact</th>
                        <th style="padding: 15px; text-align: left; color: #64748b; font-weight: 700; font-size: 13px;">Location</th>
                        <th style="padding: 15px; text-align: left; color: #64748b; font-weight: 700; font-size: 13px;">Status</th>
                        <th style="padding: 15px; text-align: center; color: #64748b; font-weight: 700; font-size: 13px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($totalRows > 0): ?>
                        <?php $i = $offset + 1;
                        while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                                onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                                <td style="padding: 15px; color: #94a3b8; font-size: 14px;"><?= $i++ ?></td>
                                <td style="padding: 15px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                    <?= htmlspecialchars($row['name'] ?? '') ?></td>
                                <td style="padding: 15px;">
                                    <div style="color: #1e293b; font-weight: 500; font-size: 14px;">
                                        <?= htmlspecialchars($row['phoneNo1'] ?? '') ?></div>
                                    <div style="color: #64748b; font-size: 12px;">
                                        <?= htmlspecialchars($row['emailId'] ?? '') ?: '-' ?></div>
                                </td>
                                <td style="padding: 15px; color: #475569; font-size: 14px;">
                                    <?= htmlspecialchars($row['city'] ?? '') ?: '-' ?></td>
                                <td style="padding: 15px;">
                                    <?php if ($row['active']): ?>
                                        <span style="color: #16a34a; font-weight: 700; font-size: 12px;">Active</span>
                                    <?php else: ?>
                                        <span style="color: #ef4444; font-weight: 700; font-size: 12px;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 10px;">
                                        <a href="edit.php?id=<?= $row['id'] ?>" style="color: #2563eb; text-decoration: none;"
                                            title="Edit">✏️</a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" style="color: #ef4444; text-decoration: none;"
                                            title="Delete" onclick="return confirm('Delete this supplier?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 50px; text-align: center; color: #94a3b8;">No suppliers found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; font-size:14px; color:#64748b;">
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
</div>

<?php include("../includes/footer.php"); ?>