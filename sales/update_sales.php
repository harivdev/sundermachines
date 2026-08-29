<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list.php");
    exit;
}

function err($msg) {
    echo "<script>alert(" . json_encode("❌ " . $msg) . "); window.history.back();</script>";
    exit;
}

$salesId = intval($_POST['salesId'] ?? 0);
if ($salesId <= 0) err("Invalid Sales ID");

$orderNo     = trim($_POST['orderNo']     ?? '');
$orderStatus = trim($_POST['orderStatus'] ?? 'New');
$orderDate   = trim($_POST['orderDate']   ?? date('Y-m-d'));
$today       = date('Y-m-d');

$existingCustId  = intval($_POST['customerId']      ?? 0);
$customerPhone   = trim($_POST['customerPhone']     ?? '');
$customerName    = trim($_POST['customerName']      ?? '');
$whatsApp        = trim($_POST['customerWhatsApp']  ?? '');

if (empty($orderNo))       err("Order number is missing");
if (empty($orderDate))     err("Order date is required");
if (strtotime($orderDate) > strtotime($today)) {
    err("Order date cannot be in the future. Allowed: Today or previous dates only.");
}
if (empty($customerPhone)) err("Customer phone is required");
if (empty($customerName))  err("Customer name is required");

$hasItem = false;
if (isset($_POST['item'])) {
    foreach ($_POST['item'] as $itm) {
        if (trim($itm) !== '') { $hasItem = true; break; }
    }
}
if (!$hasItem) err("Please add at least one item");

$customerId = null;

if ($existingCustId > 0) {
    $customerId = $existingCustId;
    $upd = mysqli_prepare($conn, "UPDATE customer SET name=?, whatsAppNo=?, phoneNo1=? WHERE id=?");
    mysqli_stmt_bind_param($upd, "sssi", $customerName, $whatsApp, $customerPhone, $customerId);
    mysqli_stmt_execute($upd);
} else {
    // search by phone
    $stmt = mysqli_prepare($conn, "SELECT id FROM customer WHERE phoneNo1 = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $customerPhone);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $customerId = $row['id'];
        $upd = mysqli_prepare($conn, "UPDATE customer SET name=?, whatsAppNo=? WHERE id=?");
        mysqli_stmt_bind_param($upd, "ssi", $customerName, $whatsApp, $customerId);
        mysqli_stmt_execute($upd);
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO customer (active, name, phoneNo1, whatsAppNo) VALUES (1,?,?,?)");
        mysqli_stmt_bind_param($ins, "sss", $customerName, $customerPhone, $whatsApp);
        mysqli_stmt_execute($ins);
        $customerId = mysqli_insert_id($conn);
        if (!$customerId) err("Failed to create customer");
    }
}

$now       = date('Y-m-d H:i:s.') . str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);
$modifiedBy = $_SESSION['username'] ?? "System Admin";

mysqli_begin_transaction($conn);

try {
    
    // 1. Fetch old items and restock
    $oItems = mysqli_query($conn, "SELECT id, stock, quantity FROM salesitems WHERE sales = $salesId AND deleted = 0");
    while ($old = mysqli_fetch_assoc($oItems)) {
        if (!empty($old['stock'])) {
            $restock = mysqli_prepare($conn, "UPDATE stock SET availableQty = availableQty + ? WHERE id = ?");
            $oldQ = max(1, intval($old['quantity']));
            $oldSid = $old['stock'];
            mysqli_stmt_bind_param($restock, "is", $oldQ, $oldSid);
            mysqli_stmt_execute($restock);
        }
    }

    // 2. Clear old items
    mysqli_query($conn, "DELETE FROM salesitems WHERE sales = $salesId");

    // 3. Insert new items and deduct stock
    $actualSum = 0;
    
    $iStmt = mysqli_prepare($conn, "
        INSERT INTO salesitems
            (createdBy, createdOn, modifiedBy, modifiedOn,
             deleted,
             gstPercentage, gstValue, itemName,
             pricePerQty, quantity, serialNo, totalPrice,
             machine, spare, stock, sales)
        VALUES
            (?, ?, ?, ?,
             b'0',
             ?, ?, ?,
             ?, ?, ?, ?,
             NULL, ?, ?, ?)
    ");

    foreach ($_POST['item'] as $key => $itemName) {
        $itemName = trim($itemName);
        if ($itemName === '') continue;

        $stockId  = trim($_POST['stockId'][$key]  ?? '');
        $spareId  = intval($_POST['spareId'][$key] ?? 0) ?: NULL;
        $serial   = trim($_POST['serial'][$key]   ?? '') ?: NULL;
        $qty      = max(1, intval($_POST['qty'][$key] ?? 1));
        $price    = floatval($_POST['price'][$key] ?? 0);
        $gst      = floatval($_POST['gst'][$key]   ?? 0);

        $subtotal  = $qty * $price;
        $gstValue  = round($subtotal * $gst / 100, 4);
        $rowTotal  = round($subtotal + $gstValue, 4);
        $stockIdVal = ($stockId !== '') ? $stockId : NULL;

        mysqli_stmt_bind_param($iStmt, "ssssddsdisdssi",
            $modifiedBy, $now, $modifiedBy, $now,
            $gst, $gstValue, $itemName,             
            $price, $qty, $serial, $rowTotal,       
            $spareId, $stockIdVal, $salesId         
        );

        if (!mysqli_stmt_execute($iStmt)) {
            throw new Exception("Item insert failed: " . mysqli_stmt_error($iStmt));
        }

        $actualSum += $rowTotal;

        // Deduct stock
        if ($stockIdVal !== null) {
            $dq = mysqli_prepare($conn, "UPDATE stock SET availableQty = GREATEST(0, availableQty - ?) WHERE id = ?");
            mysqli_stmt_bind_param($dq, "is", $qty, $stockIdVal);
            mysqli_stmt_execute($dq);
        }
    }

    // 4. Check existing payment in payment table
    $paidSum = 0;
    $chkP = mysqli_prepare($conn, "SELECT amount FROM payment WHERE sales = ? LIMIT 1");
    mysqli_stmt_bind_param($chkP, "i", $salesId);
    mysqli_stmt_execute($chkP);
    $pRes = mysqli_stmt_get_result($chkP);
    if ($pRow = mysqli_fetch_assoc($pRes)) {
        $paidSum = floatval($pRow['amount']);
        $orderStatus = 'Invoiced';
    }

    // 5. Update Sales Wrapper
    $upd = mysqli_prepare($conn, "
        UPDATE sales 
        SET orderDate = ?, orderStatus = ?, customer = ?, 
            actualAmountSum = ?, paidAmountSum = ?, 
            modifiedBy = ?, modifiedOn = ? 
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($upd, "ssidsssi", 
        $orderDate, $orderStatus, $customerId, 
        $actualSum, $paidSum, 
        $modifiedBy, $now, 
        $salesId
    );
    if (!mysqli_stmt_execute($upd)) {
        throw new Exception("Sales total update failed: " . mysqli_stmt_error($upd));
    }

    mysqli_commit($conn);
    echo "<script>alert('✅ Sales Order #" . addslashes($orderNo) . " updated successfully!'); window.location.href='list.php';</script>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    err($e->getMessage());
}
?>
