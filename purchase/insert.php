<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create.php");
    exit();
}

$orderNo = trim($_POST['orderNo'] ?? '');
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
if (empty($orderNo)) {
    echo "<script>alert('Order Number is required!'); window.history.back();</script>";
    exit();
}

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
    // 1. Insert Master Purchase Record
    $stmtP = mysqli_prepare($conn, "
        INSERT INTO purchase (
            orderNo, orderDate, supplier, orderStatus,
            quoteAmountSum, actualAmountSum, paidAmountSum,
            createdOn, createdBy, modifiedOn, modifiedBy
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), ?)
    ");

    if (!$stmtP) {
        throw new Exception("Failed to prepare purchase insert query: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmtP,
        "ssisdddss",
        $orderNo,
        $orderDate,
        $supplierId,
        $orderStatus,
        $quoteAmountSum,
        $actualAmountSum,
        $paidAmountSum,
        $user,
        $user
    );

    if (!mysqli_stmt_execute($stmtP)) {
        throw new Exception("Failed to insert purchase record: " . mysqli_stmt_error($stmtP));
    }

    $purchaseId = mysqli_insert_id($conn);

    // 2. Insert Purchase Items
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

        // 2b. Automatically Sync Stock Inventory
        $purchasedQty = ($receivedQty > 0) ? $receivedQty : $orderedQty;
        
        $chkStk = mysqli_prepare($conn, "SELECT id, quantity, availableQty FROM stock WHERE spare = ? LIMIT 1");
        if ($chkStk) {
            mysqli_stmt_bind_param($chkStk, "i", $spareId);
            mysqli_stmt_execute($chkStk);
            $resStk = mysqli_stmt_get_result($chkStk);
            
            if ($resStk && $stkRow = mysqli_fetch_assoc($resStk)) {
                // Update existing stock inventory record
                $stkId = $stkRow['id'];
                $updStk = mysqli_prepare($conn, "
                    UPDATE stock SET 
                        quantity = quantity + ?,
                        availableQty = availableQty + ?,
                        actualPricePerUnit = ?,
                        sellingPricePerUnit = ?,
                        gstPercentage = ?
                    WHERE id = ?
                ");
                if ($updStk) {
                    mysqli_stmt_bind_param($updStk, "iiddds", $purchasedQty, $purchasedQty, $priceWOGst, $sellingPrice, $gstPct, $stkId);
                    mysqli_stmt_execute($updStk);
                }
            } else {
                // Create new stock inventory record automatically
                $newStkId = uniqid("STK");
                $newBarcode = (string)rand(10000000, 99999999);
                $insStk = mysqli_prepare($conn, "
                    INSERT INTO stock (
                        id, spare, itemName, barCode, quantity, availableQty,
                        actualPricePerQty, actualPricePerUnit, sellingPricePerQty, sellingPricePerUnit,
                        gstPercentage, brand, model, unit, purchaseItem
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, 1, ?
                    )
                ");
                if ($insStk) {
                    $totPriceWoGst = $priceWOGst * $purchasedQty;
                    $totSellingPrice = $sellingPrice * $purchasedQty;
                    mysqli_stmt_bind_param(
                        $insStk,
                        "sissiidddddiii",
                        $newStkId,
                        $spareId,
                        $itemName,
                        $newBarcode,
                        $purchasedQty,
                        $purchasedQty,
                        $totPriceWoGst,
                        $priceWOGst,
                        $totSellingPrice,
                        $sellingPrice,
                        $gstPct,
                        $brandVal,
                        $modelVal,
                        $purchaseId
                    );
                    mysqli_stmt_execute($insStk);
                }
            }
        }
    }

    // 3. Insert Payment if payment entered
    if ($paymentAmount > 0) {
        $stmtPay = mysqli_prepare($conn, "
            INSERT INTO payment (
                id, purchase, amount, mode, refNo,
                transactionDate, category, inward,
                createdOn, createdBy, modifiedOn, modifiedBy
            ) VALUES (?, ?, ?, ?, ?, ?, 'PURCHASE', 0, NOW(), ?, NOW(), ?)
        ");

        if (!$stmtPay) {
            throw new Exception("Failed to prepare payment query: " . mysqli_error($conn));
        }

        $payId = uniqid("PAY");
        mysqli_stmt_bind_param(
            $stmtPay,
            "sidsssss",
            $payId,
            $purchaseId,
            $paymentAmount,
            $paymentMode,
            $paymentRefNo,
            $paymentDate,
            $user,
            $user
        );

        if (!mysqli_stmt_execute($stmtPay)) {
            throw new Exception("Failed to insert payment record: " . mysqli_stmt_error($stmtPay));
        }
    }

    // Commit Transaction
    mysqli_commit($conn);

    echo "<script>alert('✅ Purchase Order $orderNo created successfully!'); window.location.href='purchase_list.php';</script>";
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('❌ Purchase Creation Failed: {$errMsg}'); window.history.back();</script>";
    exit();
}
?>