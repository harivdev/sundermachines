<?php
require_once("../config/db.php");
include("../includes/header.php");

// STATS
$stockCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM stock"))['c'];
$spareCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM spares"))['c'];
// Find low-stock items where available quantity is less than or equal to threshold (10)
$lowStockRes = mysqli_query($conn, "SELECT st.*, s.spareName FROM stock st LEFT JOIN spares s ON st.spare = s.id WHERE st.availableQty <= 10 ORDER BY st.availableQty ASC LIMIT 10");
$lowStockCount = $lowStockRes ? mysqli_num_rows($lowStockRes) : 0;
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
                    <a href="../stock/add_stock.php" class="btn-erp btn-erp-sm" style="background:#8b5cf6; color:white;">Add to Stock</a>
                <?php else: ?>
                    <span style="display:inline-block; background:#e2e8f0; color:#475569; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">Admin only</span>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- LOW STOCK ALERT SECTION -->
    <?php if ($lowStockCount > 0): ?>
        <div class="erp-card" style="border-left: 5px solid #f97316;">
            <div style="display:flex; align-items:center; justify-space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                <div>
                    <h3 style="margin:0 0 4px 0; color:#b45309; font-size:18px;">Low Stock Alert</h3>
                    <div style="color:#475569; font-size:13.5px;">There are <strong style="color:#111;"><?= $lowStockCount ?></strong> items with available quantity at or below <strong>10</strong>.</div>
                </div>
                <div>
                    <a href="../stock/list.php" class="btn-erp btn-erp-warning btn-erp-sm">View Inventory</a>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                <?php while ($r = mysqli_fetch_assoc($lowStockRes)): ?>
                    <a href="../stock/edit_stock.php?id=<?= $r['id'] ?>" title="Click to edit stock for <?= htmlspecialchars($r['spareName'] ?? 'Item') ?>" style="display:block; text-decoration:none; padding:12px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; transition: all 0.15s ease-in-out; cursor:pointer;" onmouseover="this.style.background='#ffedd5'; this.style.borderColor='#f97316'; this.style.transform='translateY(-2px)'; var n=this.querySelector('.low-stock-name'); if(n) n.style.textDecoration='underline';" onmouseout="this.style.background='#fff7ed'; this.style.borderColor='#fed7aa'; this.style.transform='translateY(0)'; var n=this.querySelector('.low-stock-name'); if(n) n.style.textDecoration='none';">
                        <div class="low-stock-name" style="font-weight:700; color:#92400e; font-size:13px; line-height:1.3; text-decoration:none;"><?= htmlspecialchars($r['spareName'] ?? 'Item') ?></div>
                        <div style="font-size:12px; color:#475569; margin-top:6px;">Available: <strong style="color:#c2410c;"><?= (int) $r['availableQty'] ?></strong></div>
                        <div style="font-size:12px; color:#475569;">Part #: <?= htmlspecialchars($r['partNo'] ?? '-') ?></div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include("../includes/footer.php"); ?>