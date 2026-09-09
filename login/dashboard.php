<?php
require_once("../config/db.php");
include("../includes/header.php");

// STATS
$stockCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM stock"))['c'];
$spareCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM spares"))['c'];
$totalAlerts = (int)mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as c FROM stock 
    WHERE (reorderLevel > 0 AND availableQty <= reorderLevel) 
       OR (minQty > 0 AND availableQty <= minQty) 
       OR (availableQty <= 0)
"))['c'];

$alertFilter = $_GET['alert_filter'] ?? 'all';
$alertWhere = "";
if ($alertFilter === 'out_of_stock') {
    $alertWhere = " AND (st.availableQty <= 0) ";
} elseif ($alertFilter === 'critical') {
    $alertWhere = " AND (st.minQty > 0 AND st.availableQty <= st.minQty AND st.availableQty > 0) ";
} else {
    // all
    $alertWhere = " AND ((st.reorderLevel > 0 AND st.availableQty <= st.reorderLevel) 
       OR (st.minQty > 0 AND st.availableQty <= st.minQty) 
       OR (st.availableQty <= 0)) ";
}

$filteredAlertsCount = (int)mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as c FROM stock st WHERE 1=1 $alertWhere
"))['c'];

$alertLimit = 10;
$alertPage = isset($_GET['alert_page']) ? max(1, (int)$_GET['alert_page']) : 1;
$alertOffset = ($alertPage - 1) * $alertLimit;
$alertTotalPages = ceil($filteredAlertsCount / $alertLimit) ?: 1;

$lowStockRes = mysqli_query($conn, "
    SELECT st.*, s.spareName 
    FROM stock st 
    LEFT JOIN spares s ON st.spare = s.id 
    WHERE 1=1 $alertWhere
    ORDER BY st.availableQty ASC 
    LIMIT $alertLimit OFFSET $alertOffset
");
?>

<div class="erp-container">

    <!-- KPI CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px;">

        <!-- CARD 1 -->
        <div class="erp-card" style="margin-bottom: 0; border-left: 5px solid #2563eb;">
            <h4 style="color:#64748b; font-size:12px; text-transform:uppercase; margin-bottom: 5px; font-weight:700;">Total Stock Entries</h4>
            <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($stockCount) ?></div>
            <div style="margin-top: 15px;">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                    <a href="../stock/list.php" class="btn-erp btn-erp-primary btn-erp-sm">Manage Inventory &rarr;</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- CARD 2 -->
        <div class="erp-card" style="margin-bottom: 0; border-left: 5px solid #16a34a;">
            <h4 style="color:#64748b; font-size:12px; text-transform:uppercase; margin-bottom: 5px; font-weight:700;">Spare Part Types</h4>
            <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($spareCount) ?></div>
            <div style="margin-top: 15px;">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                    <a href="../spares/list_spare.php" class="btn-erp btn-erp-success btn-erp-sm">Browse Master Data &rarr;</a>
                <?php else: ?>
                    <span style="color:#64748b; font-size: 13px; font-weight: 600;">Admin only</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- CARD 3 -->
        <div class="erp-card" style="margin-bottom: 0; border-left: 5px solid #8b5cf6;">
            <h4 style="color:#64748b; font-size:12px; text-transform:uppercase; margin-bottom: 5px; font-weight:700;">Daily Action</h4>
            <div style="font-size:18px; font-weight:700; color:#1e293b; margin-top:5px;">Ready to Bill</div>
            <div style="margin-top: 15px;">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                    <a href="../stock/add_stock.php" class="btn-erp btn-erp-sm" style="background:#8b5cf6; color:white; border: 1px solid #8b5cf6;">Add to Stock &rarr;</a>
                <?php else: ?>
                    <span style="display:inline-block; background:#e2e8f0; color:#475569; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">Admin only</span>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- LOW STOCK ALERT SECTION -->
    <?php if ($totalAlerts > 0): ?>
        <div class="erp-card" style="border-left: 5px solid #ef4444; background: #fef2f2;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap: 12px;">
                    <div style="background:#fee2e2; color:#b91c1c; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 style="margin:0 0 4px 0; color:#b91c1c; font-size:18px;">Action Required</h3>
                        <div style="color:#7f1d1d; font-size:13.5px;">Items that have fallen below their configured levels.</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap: wrap;">
                    <select onchange="window.location.href='?alert_filter='+this.value" style="height: 36px; padding: 0 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; font-weight: 600; color: #475569; background: #fff; cursor: pointer; outline: none; min-width: 140px;">
                        <option value="all" <?= $alertFilter === 'all' ? 'selected' : '' ?>>All Alerts</option>
                        <option value="out_of_stock" <?= $alertFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="critical" <?= $alertFilter === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                    <a href="../stock/reorder_level.php" class="btn-erp btn-erp-sm" style="background:#b91c1c; color:#fff; border-color:#b91c1c; height: 36px; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; padding: 0 16px; box-sizing: border-box;">Monitor Stock Levels &rarr;</a>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px;">
                <?php if (mysqli_num_rows($lowStockRes) == 0): ?>
                    <div style="padding: 20px; color:#991b1b; font-size: 14px; font-weight: 500;">No items found for this filter.</div>
                <?php endif; ?>
                <?php while ($r = mysqli_fetch_assoc($lowStockRes)): 
                    $avail = (int)$r['availableQty'];
                    $min = (int)$r['minQty'];
                    $reorder = (int)$r['reorderLevel'];
                    
                    $isOut = ($avail <= 0);
                    $isCritical = ($min > 0 && $avail <= $min) && !$isOut;
                    
                    if ($isOut) {
                        $cardBg = '#f1f5f9';
                        $cardBorder = '#cbd5e1';
                        $textColor = '#475569';
                        $statusTag = 'OUT OF STOCK';
                        $tagBg = '#64748b';
                    } elseif ($isCritical) {
                        $cardBg = '#fee2e2';
                        $cardBorder = '#fca5a5';
                        $textColor = '#991b1b';
                        $statusTag = 'CRITICAL';
                        $tagBg = '#f87171';
                    } else {
                        $cardBg = '#fef3c7';
                        $cardBorder = '#fde68a';
                        $textColor = '#92400e';
                        $statusTag = 'REORDER';
                        $tagBg = '#fbbf24';
                    }
                ?>
                    <a href="../stock/reorder_level.php?q=<?= urlencode($r['spareName'] ?? $r['itemName'] ?? '') ?>" title="View in Reorder Monitor" style="display:block; text-decoration:none; padding:12px; border-radius:8px; background:<?= $cardBg ?>; border:1px solid <?= $cardBorder ?>; transition: all 0.2s ease-in-out; cursor:pointer;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.05)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 6px;">
                            <div class="low-stock-name" style="font-weight:700; color:<?= $textColor ?>; font-size:13px; line-height:1.3; text-decoration:none; max-width:75%;"><?= htmlspecialchars($r['spareName'] ?? $r['itemName']) ?></div>
                            <span style="font-size:9px; font-weight:800; background:<?= $tagBg ?>; color:#fff; padding:2px 6px; border-radius:4px; white-space:nowrap;"><?= $statusTag ?></span>
                        </div>
                        <div style="font-size:12px; color:<?= $textColor ?>; margin-top:4px;">Available: <strong><?= $avail ?></strong> (Min: <?= $min ?: '-' ?>)</div>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php if ($alertTotalPages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #fca5a5;">
                    <?php if ($alertPage > 1): ?>
                        <a href="?alert_filter=<?= $alertFilter ?>&alert_page=<?= $alertPage - 1 ?>" class="btn-erp btn-erp-sm" style="background:#fff; color:#b91c1c; border:1px solid #fca5a5;">&larr; Previous</a>
                    <?php else: ?>
                        <div style="width: 80px;"></div>
                    <?php endif; ?>
                    
                    <div style="font-size: 13px; color:#991b1b; font-weight:600;">
                        Page <?= $alertPage ?> of <?= $alertTotalPages ?>
                    </div>

                    <?php if ($alertPage < $alertTotalPages): ?>
                        <a href="?alert_filter=<?= $alertFilter ?>&alert_page=<?= $alertPage + 1 ?>" class="btn-erp btn-erp-sm" style="background:#fff; color:#b91c1c; border:1px solid #fca5a5;">Next &rarr;</a>
                    <?php else: ?>
                        <div style="width: 80px;"></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>

<?php include("../includes/footer.php"); ?>