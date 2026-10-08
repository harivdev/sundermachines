<?php
require_once("../config/db.php");
require_once("../config/whatsapp.php");
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

if (empty($orderNo)) {
    echo "<script>alert('Order Number is required!'); window.history.back();</script>";
    exit();
}

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
    echo "<script>alert('❌ Cannot save Purchase Order: No items were selected! Please select at least one item.'); window.history.back();</script>";
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

    foreach ($items as $item) {
        $spareId = intval($item['spare'] ?? 0);
        $itemName = trim($item['itemName'] ?? '');
        $brandId = intval($item['brand'] ?? 0);
        $modelId = intval($item['model'] ?? 0);
        $orderedQty = max(1, intval($item['orderedQty'] ?? 1));
        $receivedQty = max(0, intval($item['receivedQty'] ?? 0));
        $remarks = trim($item['remarks'] ?? '');

        $priceWOGst = floatval($item['priceWOGst'] ?? 0);
        $gstPct = floatval($item['gstPct'] ?? 0);
        $priceWithGst = floatval($item['priceWithGst'] ?? 0);
        $sellingPrice = floatval($item['sellingPrice'] ?? 0);

        if ($spareId <= 0) continue;

        // If itemName is missing from client, fetch from spares table
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

        // Only update stock if goods have been delivered/received!
        if (($orderStatus === 'Delivered' || $orderStatus === 'Received' || $orderStatus === 'Completed') && $receivedQty > 0) {
            $purchasedQty = $receivedQty;

            $chkStk = mysqli_prepare($conn, "SELECT id, quantity, availableQty FROM stock WHERE spare = ? LIMIT 1");
            if ($chkStk) {
                mysqli_stmt_bind_param($chkStk, "i", $spareId);
                mysqli_stmt_execute($chkStk);
                $resStk = mysqli_stmt_get_result($chkStk);

                if ($resStk && $stkRow = mysqli_fetch_assoc($resStk)) {
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
    }

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

    mysqli_commit($conn);

    $supplierName = 'Supplier #' . $supplierId;
    $suppStmt = mysqli_prepare($conn, "SELECT name FROM supplier WHERE id = ? LIMIT 1");
    if ($suppStmt) {
        $sidInt = intval($supplierId);
        mysqli_stmt_bind_param($suppStmt, "i", $sidInt);
        mysqli_stmt_execute($suppStmt);
        $suppRes = mysqli_stmt_get_result($suppStmt);
        if ($suppRes && $suppRow = mysqli_fetch_assoc($suppRes)) {
            $supplierName = $suppRow['name'];
        }
        mysqli_stmt_close($suppStmt);
    }

    $prodLines = [];
    if (is_array($items)) {
        $idx = 1;
        foreach ($items as $item) {
            $iName = trim($item['itemName'] ?? '');
            if (!empty($iName)) {
                $prodLines[] = (count($items) > 1 ? "$idx. " : "") . $iName;
                $idx++;
            }
            if ($idx > 5) break;
        }
    }
    $productSummary = !empty($prodLines) ? implode(", ", $prodLines) : "Purchase Items";

    try {
        send_purchase_notification($orderNo, $supplierName, $productSummary, $actualAmountSum, $purchaseId);
    } catch (Throwable $tn) {
        error_log("WhatsApp purchase notification error: " . $tn->getMessage());
    }

    $redirectToPrint = !empty($_POST['redirectToPrint']) && $_POST['redirectToPrint'] === '1';
    if ($redirectToPrint) {
        echo "<script>alert('✅ Purchase Order $orderNo confirmed successfully!'); window.location.href='print_purchase.php?id=" . $purchaseId . "';</script>";
    } else {
        echo "<script>alert('✅ Purchase Order $orderNo saved successfully!'); window.location.href='purchase_list.php';</script>";
    }
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $errMsg = addslashes($e->getMessage());
    echo "<script>alert('❌ Purchase Creation Failed: {$errMsg}'); window.history.back();</script>";
    exit();
}
?>
