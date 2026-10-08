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

$chkStatus = mysqli_query($conn, "SELECT orderStatus FROM purchase WHERE id = $purchaseId LIMIT 1");
if ($chkStatus && $rowSt = mysqli_fetch_assoc($chkStatus)) {
    if (in_array($rowSt['orderStatus'], ['Delivered', 'Received', 'Completed'])) {
        echo "<script>alert('This purchase order is already delivered and cannot be modified.'); window.location.href='purchase_list.php';</script>";
        exit();
    }
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

if ($supplierId <= 0) {
    echo "<script>alert('Please select a valid supplier!'); window.history.back();</script>";
    exit();
}

$validItems = [];
if (is_array($items)) {
    foreach ($items as $item) {
        $spId = intval($item['spare'] ?? 0);
        if ($spId > 0) {
            $validItems[] = $item;
        }
    }
}

if (count($validItems) === 0) {
    echo "<script>alert('❌ Cannot update Purchase Order: No items were selected! Please select at least one item.'); window.history.back();</script>";
    exit();
}
$items = $validItems;

// If order status is Delivered (or legacy Received/Completed), verify and auto-fill received quantities if 0
if ($orderStatus === 'Delivered' || $orderStatus === 'Received' || $orderStatus === 'Completed') {
    $hasReceived = false;
    foreach ($items as $item) {
        if (intval($item['receivedQty'] ?? 0) > 0) {
            $hasReceived = true;
            break;
        }
    }
    if (!$hasReceived) {
        foreach ($items as $k => $item) {
            $items[$k]['receivedQty'] = max(1, intval($item['orderedQty'] ?? 1));
        }
    }
}

$user = $_SESSION['username'] ?? 'System Admin';

mysqli_begin_transaction($conn);

try {
    $stmtOldItems = mysqli_prepare($conn, "SELECT spare, orderedQuantity, receivedQuantity FROM purchaseitems WHERE purchase = ? AND (deleted = 0 OR deleted IS NULL)");
    mysqli_stmt_bind_param($stmtOldItems, "i", $purchaseId);
    mysqli_stmt_execute($stmtOldItems);
    $resOldItems = mysqli_stmt_get_result($stmtOldItems);

    $oldQuantities = [];
    while ($oldRow = mysqli_fetch_assoc($resOldItems)) {
        $spId = intval($oldRow['spare']);
        $oldQty = ($oldRow['receivedQuantity'] > 0) ? intval($oldRow['receivedQuantity']) : 0;
        $oldQuantities[$spId] = ($oldQuantities[$spId] ?? 0) + $oldQty;
    }

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

    $stmtDelOld = mysqli_prepare($conn, "UPDATE purchaseitems SET deleted = 1 WHERE purchase = ?");
    mysqli_stmt_bind_param($stmtDelOld, "i", $purchaseId);
    mysqli_stmt_execute($stmtDelOld);

    // Ensure remarks column exists on purchaseitems if possible
    $chkCol = mysqli_query($conn, "SHOW COLUMNS FROM purchaseitems LIKE 'remarks'");
    $hasRemarksCol = ($chkCol && mysqli_num_rows($chkCol) > 0);
    if (!$hasRemarksCol) {
        @mysqli_query($conn, "ALTER TABLE purchaseitems ADD COLUMN remarks TEXT DEFAULT NULL");
        $chkCol2 = mysqli_query($conn, "SHOW COLUMNS FROM purchaseitems LIKE 'remarks'");
        $hasRemarksCol = ($chkCol2 && mysqli_num_rows($chkCol2) > 0);
    }

    if ($hasRemarksCol) {
        $stmtItem = mysqli_prepare($conn, "
            INSERT INTO purchaseitems (
                purchase, spare, itemName, brand, model,
                orderedQuantity, receivedQuantity,
                sellingPricePerQtyWOGSt, totalPriceWithoutGst,
                gstPercentage, gstValue, totalPriceWithGst,
                remarks,
                deleted, createdOn, createdBy, modifiedOn, modifiedBy
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, NOW(), ?)
        ");
    } else {
        $stmtItem = mysqli_prepare($conn, "
            INSERT INTO purchaseitems (
                purchase, spare, itemName, brand, model,
                orderedQuantity, receivedQuantity,
                sellingPricePerQtyWOGSt, totalPriceWithoutGst,
                gstPercentage, gstValue, totalPriceWithGst,
                deleted, createdOn, createdBy, modifiedOn, modifiedBy
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, NOW(), ?)
        ");
    }

    if (!$stmtItem) {
        throw new Exception("Failed to prepare purchaseitems query: " . mysqli_error($conn));
    }

    $newQuantities = [];

    foreach ($items as $item) {
        $spareId = intval($item['spare'] ?? 0);
        $itemName = trim($item['itemName'] ?? '');
        $brandId = intval($item['brand'] ?? 0);
        $modelId = intval($item['model'] ?? 0);
        $orderedQty = max(1, intval($item['orderedQty'] ?? 1));
        $receivedQty = max(0, intval($item['receivedQty'] ?? 0));
        $remarks = trim($item['remarks'] ?? '');
        $receivedQty = max(0, intval($item['receivedQty'] ?? 0));

        $priceWOGst = floatval($item['priceWOGst'] ?? 0);
        $gstPct = floatval($item['gstPct'] ?? 0);
        $priceWithGst = floatval($item['priceWithGst'] ?? 0);
        $sellingPrice = floatval($item['sellingPrice'] ?? 0);

        if ($spareId <= 0) continue;

        // If itemName is missing, fetch from spares table
        if (empty($itemName)) {
            $spNameStmt = mysqli_prepare($conn, "SELECT spareName FROM spares WHERE id = ?");
            if ($spNameStmt) {
                mysqli_stmt_bind_param($spNameStmt, "i", $spareId);
                mysqli_stmt_execute($spNameStmt);
                $resSp = mysqli_stmt_get_result($spNameStmt);
                if ($rSp = mysqli_fetch_assoc($resSp)) {
                    $itemName = $rSp['spareName'];
                }
                mysqli_stmt_close($spNameStmt);
            }
            if (empty($itemName)) {
                $itemName = "Item #" . $spareId;
            }
        }

        $brandVal = ($brandId > 0) ? $brandId : NULL;
        $modelVal = ($modelId > 0) ? $modelId : NULL;

        $totalWoGst = round($priceWOGst * $orderedQty);
        $gstValue = round(($priceWOGst * $gstPct / 100) * $orderedQty);
        $totalWithGst = round($priceWithGst * $orderedQty);

        if ($hasRemarksCol) {
            mysqli_stmt_bind_param(
                $stmtItem,
                "iisiiiidddddsss",
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
                $remarks,
                $user,
                $user
            );
        } else {
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
        }

        if (!mysqli_stmt_execute($stmtItem)) {
            throw new Exception("Failed to insert purchase item '$itemName': " . mysqli_stmt_error($stmtItem));
        }

        $purchasedQty = ($orderStatus === 'Delivered' || $orderStatus === 'Received' || $orderStatus === 'Completed') ? $receivedQty : 0;
        $newQuantities[$spareId] = ($newQuantities[$spareId] ?? 0) + $purchasedQty;
    }

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

    mysqli_commit($conn);

    $redirectToPrint = !empty($_POST['redirectToPrint']) && $_POST['redirectToPrint'] === '1';
    if ($redirectToPrint) {
        echo "<script>alert('✅ Purchase Order confirmed successfully!'); window.location.href='print_purchase.php?id=" . $purchaseId . "';</script>";
    } else {
        echo "<script>alert('✅ Purchase Order updated successfully!'); window.location.href='purchase_list.php';</script>";
    }
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('❌ Purchase Update Failed: {$errMsg}'); window.history.back();</script>";
    exit();
}
?>

