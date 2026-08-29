<?php
require_once("../config/db.php");

// Security/Session checking could go here if needed via an included header/middleware
// if (!isset($_SESSION['user_id'])) { ... }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<script>alert('Invalid Sales ID!'); window.location.href='list.php';</script>";
    exit;
}

// Ensure the sale exists before deleting
$chk = mysqli_query($conn, "SELECT orderNo FROM sales WHERE id = $id");
if (!$chk || mysqli_num_rows($chk) == 0) {
    echo "<script>alert('Sales order not found!'); window.location.href='list.php';</script>";
    exit;
}
$saleData = mysqli_fetch_assoc($chk);
$orderNo = $saleData['orderNo'];

mysqli_begin_transaction($conn);

try {
    // 1. Fetch related items and Restore Stock Quantity
    $itemsQuery = mysqli_query($conn, "SELECT stock, quantity FROM salesitems WHERE sales = $id AND deleted = 0");
    if ($itemsQuery) {
        while ($item = mysqli_fetch_assoc($itemsQuery)) {
            if (!empty($item['stock']) && intval($item['stock']) > 0) {
                // Restore the stock back to the inventory
                $restoreStmt = mysqli_prepare($conn, "UPDATE stock SET availableQty = availableQty + ? WHERE id = ?");
                $qty = intval($item['quantity']);
                $stockId = $item['stock'];
                mysqli_stmt_bind_param($restoreStmt, "is", $qty, $stockId);
                mysqli_stmt_execute($restoreStmt);
            }
        }
    }

    // 2. Delete Items
    mysqli_query($conn, "DELETE FROM salesitems WHERE sales = $id");

    // 3. Delete Payments (If Table Exists)
    $hasPmtTable = mysqli_query($conn, "SHOW TABLES LIKE 'sales_payments'");
    if ($hasPmtTable && mysqli_num_rows($hasPmtTable) > 0) {
        mysqli_query($conn, "DELETE FROM sales_payments WHERE salesId = $id");
    }

    // 4. Delete the Sale Record Itself
    $delSale = mysqli_prepare($conn, "DELETE FROM sales WHERE id = ?");
    mysqli_stmt_bind_param($delSale, "i", $id);
    if (!mysqli_stmt_execute($delSale)) {
        throw new Exception("Error deleting the sales order wrapper.");
    }
    
    // Unlink physical sales photo files from uploads/sales/
    $salesPhotos = glob(__DIR__ . "/../uploads/sales/{$orderNo}_*");
    if ($salesPhotos) {
        foreach ($salesPhotos as $photoPath) {
            if (file_exists($photoPath)) @unlink($photoPath);
        }
    }

    // Commit if everything worked successfully
    mysqli_commit($conn);
    echo "<script>alert('🗑️ Sales Order #" . addslashes($orderNo) . " deleted successfully (Inventory restocked).'); window.location.href='list.php';</script>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>alert('❌ Error deleting sale: " . addslashes($e->getMessage()) . "'); window.location.href='list.php';</script>";
}
?>
