<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

$purchaseId = intval($_GET['id'] ?? 0);
if ($purchaseId <= 0) {
    header("Location: purchase_list.php");
    exit();
}

// Begin MySQL Transaction
mysqli_begin_transaction($conn);

try {
    // 1. Fetch Purchased Items & Quantities to Reverse Stock
    $stmtItems = mysqli_prepare($conn, "SELECT spare, orderedQuantity, receivedQuantity FROM purchaseitems WHERE purchase = ? AND (deleted = 0 OR deleted IS NULL)");
    mysqli_stmt_bind_param($stmtItems, "i", $purchaseId);
    mysqli_stmt_execute($stmtItems);
    $resItems = mysqli_stmt_get_result($stmtItems);

    while ($row = mysqli_fetch_assoc($resItems)) {
        $spId = intval($row['spare']);
        $purchasedQty = ($row['receivedQuantity'] > 0) ? intval($row['receivedQuantity']) : intval($row['orderedQuantity']);

        if ($spId > 0 && $purchasedQty > 0) {
            $revStk = mysqli_prepare($conn, "
                UPDATE stock SET 
                    quantity = GREATEST(0, quantity - ?),
                    availableQty = GREATEST(0, availableQty - ?)
                WHERE spare = ?
            ");
            if ($revStk) {
                mysqli_stmt_bind_param($revStk, "iii", $purchasedQty, $purchasedQty, $spId);
                mysqli_stmt_execute($revStk);
            }
        }
    }

    // 2. Mark Purchase Items as Deleted
    $stmtDelItems = mysqli_prepare($conn, "UPDATE purchaseitems SET deleted = 1 WHERE purchase = ?");
    mysqli_stmt_bind_param($stmtDelItems, "i", $purchaseId);
    mysqli_stmt_execute($stmtDelItems);

    // 3. Delete Master Purchase Record
    $stmtDelP = mysqli_prepare($conn, "DELETE FROM purchase WHERE id = ?");
    mysqli_stmt_bind_param($stmtDelP, "i", $purchaseId);
    mysqli_stmt_execute($stmtDelP);

    // Unlink physical purchase photo files from uploads/purchase/
    $pPhotos = glob(__DIR__ . "/../uploads/purchase/*_{$purchaseId}_*");
    $pPhotos2 = glob(__DIR__ . "/../uploads/purchase/P{$purchaseId}_*");
    $allP = array_merge($pPhotos ?: [], $pPhotos2 ?: []);
    foreach ($allP as $photoPath) {
        if (file_exists($photoPath)) @unlink($photoPath);
    }

    // Commit Transaction
    mysqli_commit($conn);

    echo "<script>alert('🗑️ Purchase Order deleted and stock quantities reversed successfully!'); window.location.href='purchase_list.php';</script>";
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('❌ Purchase Deletion Failed: {$errMsg}'); window.location.href='purchase_list.php';</script>";
    exit();
}
?>
