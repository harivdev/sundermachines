<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: purchase_list.php");
    exit();
}

$purchaseId = intval($_POST['purchaseId'] ?? 0);
if ($purchaseId <= 0) {
    echo "<script>alert('Invalid Purchase Order ID!'); window.history.back();</script>";
    exit();
}

$orderDate = trim($_POST['orderDate'] ?? date('Y-m-d'));
$supplierId = intval($_POST['supplier'] ?? 0);
$orderStatus = trim($_POST['orderStatus'] ?? 'New');

$quoteAmountSum = round(floatval($_POST['quoteAmountSum'] ?? 0));
$actualAmountSum = round(floatval($_POST['actualAmountSum'] ?? 0));
$paidAmountSum = round(floatval($_POST['paidAmountSum'] ?? 0));

$paymentDate = trim($_POST['paymentDate'] ?? date('Y-m-d'));
$paymentMode = trim($_POST['paymentMode'] ?? 'Cash');
$paymentRefNo = trim($_POST['paymentRefNo'] ?? '');
$paymentAmount = round(floatval($_POST['paymentAmount'] ?? 0));

$items = $_POST['items'] ?? [];

// Validation
if ($supplierId <= 0) {
    echo "<script>alert('Please select a valid supplier!'); window.history.back();</script>";
    exit();
}

if (!is_array($items) || count($items) === 0) {
    echo "<script>alert('At least one purchase item is required!'); window.history.back();</script>";
    exit();
}

$user = $_SESSION['username'] ?? 'System Admin';

// Begin MySQL Transaction
mysqli_begin_transaction($conn);

try {
    // 1. Fetch Existing Purchased Quantities to Calculate Difference for Stock
    $stmtOldItems = mysqli_prepare($conn, "SELECT spare, orderedQuantity, receivedQuantity FROM purchaseitems WHERE purchase = ? AND (deleted = 0 OR deleted IS NULL)");
    mysqli_stmt_bind_param($stmtOldItems, "i", $purchaseId);
    mysqli_stmt_execute($stmtOldItems);
    $resOldItems = mysqli_stmt_get_result($stmtOldItems);
    
    $oldQuantities = [];
    while ($oldRow = mysqli_fetch_assoc($resOldItems)) {
        $spId = intval($oldRow['spare']);
        $oldQty = ($oldRow['receivedQuantity'] > 0) ? intval($oldRow['receivedQuantity']) : intval($oldRow['orderedQuantity']);
        $oldQuantities[$spId] = ($oldQuantities[$spId] ?? 0) + $oldQty;
    }

    // 2. Update Master Purchase Record with Audit Trail
    $stmtP = mysqli_prepare($conn, "
        UPDATE purchase SET
            supplier = ?,
            orderDate = ?,
            orderStatus = ?,
            quoteAmountSum = ?,
            actualAmountSum = ?,
            paidAmountSum = ?,
            modifiedOn = NOW(),
            modifiedBy = ?
        WHERE id = ?
    ");

    if (!$stmtP) {
        throw new Exception("Failed to prepare purchase update query: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmtP,
        "issdddsi",
        $supplierId,
        $orderDate,
        $orderStatus,
        $quoteAmountSum,
        $actualAmountSum,
        $paidAmountSum,
        $user,
        $purchaseId
    );

    if (!mysqli_stmt_execute($stmtP)) {
        throw new Exception("Failed to update purchase record: " . mysqli_stmt_error($stmtP));
    }

    // 3. Mark Previous Items as Deleted / Replace
    $stmtDelOld = mysqli_prepare($conn, "UPDATE purchaseitems SET deleted = 1 WHERE purchase = ?");
    mysqli_stmt_bind_param($stmtDelOld, "i", $purchaseId);
    mysqli_stmt_execute($stmtDelOld);

    // 4. Insert Updated Purchase Items & Track New Quantities
    $stmtItem = mysqli_prepare($conn, "
        INSERT INTO purchaseitems (
            purchase, spare, itemName, brand, model,
            orderedQuantity, receivedQuantity,
            sellingPricePerQtyWOGSt, totalPriceWithoutGst,
            gstPercentage, gstValue, totalPriceWithGst,
            deleted, createdOn, createdBy, modifiedOn, modifiedBy
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, NOW(), ?)
    ");

    if (!$stmtItem) {
        throw new Exception("Failed to prepare purchaseitems query: " . mysqli_error($conn));
    }

    $newQuantities = [];

    foreach ($items as $item) {
        $spareId = intval($item['spare'] ?? 0);
        $itemName = trim($item['itemName'] ?? '');
        $brandId = intval($item['brand'] ?? 0);
        $modelId = intval($item['model'] ?? 0);
        $orderedQty = intval($item['orderedQty'] ?? 1);
        $receivedQty = intval($item['receivedQty'] ?? 0);

        $priceWOGst = floatval($item['priceWOGst'] ?? 0);
        $gstPct = floatval($item['gstPct'] ?? 0);
        $priceWithGst = floatval($item['priceWithGst'] ?? 0);
        $sellingPrice = floatval($item['sellingPrice'] ?? 0);

        if ($spareId <= 0 || empty($itemName)) continue;

        $brandVal = ($brandId > 0) ? $brandId : NULL;
        $modelVal = ($modelId > 0) ? $modelId : NULL;

        $totalWoGst = round($priceWOGst * $orderedQty);
        $gstValue = round(($priceWOGst * $gstPct / 100) * $orderedQty);
        $totalWithGst = round($priceWithGst * $orderedQty);

        mysqli_stmt_bind_param(
            $stmtItem,
            "iisiiiidddddss",
            $purchaseId,
            $spareId,
            $itemName,
            $brandVal,
            $modelVal,
            $orderedQty,
            $receivedQty,
            $priceWOGst,
            $totalWoGst,
            $gstPct,
            $gstValue,
            $totalWithGst,
            $user,
            $user
        );

        if (!mysqli_stmt_execute($stmtItem)) {
            throw new Exception("Failed to insert purchase item '$itemName': " . mysqli_stmt_error($stmtItem));
        }

        $purchasedQty = ($receivedQty > 0) ? $receivedQty : $orderedQty;
        $newQuantities[$spareId] = ($newQuantities[$spareId] ?? 0) + $purchasedQty;
    }

    // 5. Calculate Quantity Differences and Auto-Sync Stock Inventory
    $allSpares = array_unique(array_merge(array_keys($oldQuantities), array_keys($newQuantities)));

    foreach ($allSpares as $spId) {
        $oldQ = $oldQuantities[$spId] ?? 0;
        $newQ = $newQuantities[$spId] ?? 0;
        $deltaQty = $newQ - $oldQ;

        if ($deltaQty != 0) {
            $updStk = mysqli_prepare($conn, "
                UPDATE stock SET 
                    quantity = GREATEST(0, quantity + ?),
                    availableQty = GREATEST(0, availableQty + ?)
                WHERE spare = ?
            ");
            if ($updStk) {
                mysqli_stmt_bind_param($updStk, "iii", $deltaQty, $deltaQty, $spId);
                mysqli_stmt_execute($updStk);
            }
        }
    }

    // 6. Update or Insert Payment Record
    if ($paymentAmount > 0) {
        $chkPay = mysqli_prepare($conn, "SELECT id FROM payment WHERE purchase = ? ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($chkPay, "i", $purchaseId);
        mysqli_stmt_execute($chkPay);
        $resPay = mysqli_stmt_get_result($chkPay);

        if ($resPay && $payRow = mysqli_fetch_assoc($resPay)) {
            $existingPayId = $payRow['id'];
            $updPay = mysqli_prepare($conn, "
                UPDATE payment SET
                    amount = ?,
                    mode = ?,
                    refNo = ?,
                    transactionDate = ?,
                    modifiedOn = NOW(),
                    modifiedBy = ?
                WHERE id = ?
            ");
            if ($updPay) {
                mysqli_stmt_bind_param($updPay, "dsssss", $paymentAmount, $paymentMode, $paymentRefNo, $paymentDate, $user, $existingPayId);
                mysqli_stmt_execute($updPay);
            }
        } else {
            $insPay = mysqli_prepare($conn, "
                INSERT INTO payment (
                    id, purchase, amount, mode, refNo,
                    transactionDate, category, inward,
                    createdOn, createdBy, modifiedOn, modifiedBy
                ) VALUES (?, ?, ?, ?, ?, ?, 'PURCHASE', 0, NOW(), ?, NOW(), ?)
            ");
            if ($insPay) {
                $payId = uniqid("PAY");
                mysqli_stmt_bind_param($insPay, "sidsssss", $payId, $purchaseId, $paymentAmount, $paymentMode, $paymentRefNo, $paymentDate, $user, $user);
                mysqli_stmt_execute($insPay);
            }
        }
    }

    // Commit Transaction
    mysqli_commit($conn);

    echo "<script>alert('✅ Purchase Order updated successfully!'); window.location.href='purchase_list.php';</script>";
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('❌ Purchase Update Failed: {$errMsg}'); window.history.back();</script>";
    exit();
}
?>
