<?php
require_once("../config/db.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<script>alert('Invalid Sales ID!'); window.location.href='list.php';</script>";
    exit;
}

$chk = mysqli_query($conn, "SELECT orderNo FROM sales WHERE id = $id");
if (!$chk || mysqli_num_rows($chk) == 0) {
    echo "<script>alert('Sales order not found!'); window.location.href='list.php';</script>";
    exit;
}
$saleData = mysqli_fetch_assoc($chk);
$orderNo = $saleData['orderNo'];

mysqli_begin_transaction($conn);

try {
    $itemsQuery = mysqli_query($conn, "SELECT stock, quantity FROM salesitems WHERE sales = $id AND deleted = 0");
    if ($itemsQuery) {
        while ($item = mysqli_fetch_assoc($itemsQuery)) {
            $qty = intval($item['quantity']);
            $stockId = trim($item['stock'] ?? '');
            if (!empty($stockId) && $qty > 0) {
                $restoreStmt = mysqli_prepare($conn, "UPDATE stock SET availableQty = availableQty + ? WHERE id = ?");
                mysqli_stmt_bind_param($restoreStmt, "is", $qty, $stockId);
                mysqli_stmt_execute($restoreStmt);
            }
        }
    }

    mysqli_query($conn, "DELETE FROM salesitems WHERE sales = $id");

    $hasPmtTable = mysqli_query($conn, "SHOW TABLES LIKE 'sales_payments'");
    if ($hasPmtTable && mysqli_num_rows($hasPmtTable) > 0) {
        mysqli_query($conn, "DELETE FROM sales_payments WHERE salesId = $id");
    }

    $delSale = mysqli_prepare($conn, "DELETE FROM sales WHERE id = ?");
    mysqli_stmt_bind_param($delSale, "i", $id);
    if (!mysqli_stmt_execute($delSale)) {
        throw new Exception("Error deleting the sales order wrapper.");
    }

    $salesPhotos = glob(__DIR__ . "/../uploads/sales/{$orderNo}_*");
    if ($salesPhotos) {
        foreach ($salesPhotos as $photoPath) {
            if (file_exists($photoPath)) @unlink($photoPath);
        }
    }

    mysqli_commit($conn);
    echo "<script>alert('🗑️ Sales Order #" . addslashes($orderNo) . " deleted successfully (Inventory restocked).'); window.location.href='list.php';</script>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>alert('❌ Error deleting sale: " . addslashes($e->getMessage()) . "'); window.location.href='list.php';</script>";
}
?>

