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
    header("Location: create.php");
    exit;
}

/* ── Error helper ── */
function err($msg) {
    echo "<script>alert(" . json_encode("❌ " . $msg) . "); window.history.back();</script>";
    exit;
}

/* ══════════════════════════════════════
   1. COLLECT POST DATA
══════════════════════════════════════ */
$orderNo     = trim($_POST['orderNo']     ?? '');
$orderStatus = trim($_POST['orderStatus'] ?? 'New');
$orderDate   = trim($_POST['orderDate']   ?? date('Y-m-d'));
$today       = date('Y-m-d');

$existingCustId  = intval($_POST['customerId']      ?? 0);
$customerPhone   = trim($_POST['customerPhone']     ?? '');
$customerName    = trim($_POST['customerName']      ?? '');
$whatsApp        = trim($_POST['customerWhatsApp']  ?? '');
$addressLine1    = trim($_POST['addressLine1']      ?? '');
$city            = trim($_POST['city']              ?? '');

/* ══════════════════════════════════════
   2. VALIDATE
══════════════════════════════════════ */
if (empty($orderNo)) {
    $year = date('Y');
    $maxRes = mysqli_query($conn, "SELECT orderNo FROM sales WHERE orderNo LIKE '$year%' ORDER BY id DESC LIMIT 1");
    $nextNum = 1;
    if ($maxRes && $row = mysqli_fetch_assoc($maxRes)) {
        if (preg_match('/(\d{5})$/', trim($row['orderNo'] ?? ''), $m)) {
            $nextNum = intval($m[1]) + 1;
        }
    }
    $orderNo = $year . sprintf("%05d", $nextNum);
}
if (empty($orderDate))     err("Order date is required");
if (strtotime($orderDate) > strtotime($today)) {
    err("Order date cannot be in the future. Allowed: Today or previous dates only.");
}
if (empty($customerPhone)) err("Customer phone is required");
if (empty($customerName))  err("Customer name is required");
if (empty($_POST['item'])) err("Please add at least one item");

// Make sure at least one item has a name
$hasItem = false;
foreach ($_POST['item'] as $itm) {
    if (trim($itm) !== '') { $hasItem = true; break; }
}
if (!$hasItem) err("Please add at least one item");

/* ══════════════════════════════════════
   3. FIND OR CREATE CUSTOMER
══════════════════════════════════════ */
$customerId = null;

if ($existingCustId > 0) {
    // Verify the customer exists
    $chk = mysqli_prepare($conn, "SELECT id FROM customer WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $existingCustId);
    mysqli_stmt_execute($chk);
    $chkRes = mysqli_stmt_get_result($chk);
    if ($chkRes && mysqli_num_rows($chkRes) > 0) {
        $customerId = $existingCustId;
        // Update customer info
        $upd = mysqli_prepare($conn, "UPDATE customer SET name=?, whatsAppNo=?, phoneNo1=? WHERE id=?");
        mysqli_stmt_bind_param($upd, "sssi", $customerName, $whatsApp, $customerPhone, $customerId);
        mysqli_stmt_execute($upd);
    }
}

if (!$customerId) {
    // Search by phone
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
        // Create new customer
        $ins = mysqli_prepare($conn, "INSERT INTO customer (active, name, phoneNo1, whatsAppNo) VALUES (1,?,?,?)");
        mysqli_stmt_bind_param($ins, "sss", $customerName, $customerPhone, $whatsApp);
        mysqli_stmt_execute($ins);
        $customerId = mysqli_insert_id($conn);
        if (!$customerId) err("Failed to create customer");
    }
}

/* ══════════════════════════════════════
   4. TRANSACTION
══════════════════════════════════════ */
$now       = date('Y-m-d H:i:s.') . str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);
$createdBy = $_SESSION['username'] ?? "System Admin";

mysqli_begin_transaction($conn);

try {

    /* 4a. Insert sales header */
    $stmt = mysqli_prepare($conn, "
        INSERT INTO sales
            (orderNo, orderDate, orderStatus, customer,
             actualAmountSum, paidAmountSum,
             createdBy, createdOn, modifiedBy, modifiedOn)
        VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "ssisssss",
        $orderNo, $orderDate, $orderStatus, $customerId,
        $createdBy, $now, $createdBy, $now
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Sales insert failed: " . mysqli_stmt_error($stmt));
    }
    $salesId = mysqli_insert_id($conn);
    if (!$salesId) throw new Exception("Could not get sales ID");

    /* 4b. Insert sales items */
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

        // Recalculate server-side
        $subtotal  = round($qty * $price);
        $gstValue  = round($subtotal * $gst / 100);
        $rowTotal  = round($subtotal + $gstValue);

        $stockIdVal = ($stockId !== '') ? $stockId : NULL;

        mysqli_stmt_bind_param($iStmt, "ssssddsdisdssi",
            $createdBy, $now, $createdBy, $now,   // s s s s
            $gst, $gstValue, $itemName,             // d d s
            $price, $qty, $serial, $rowTotal,       // d i s d
            $spareId, $stockIdVal, $salesId         // i s i
        );

        if (!mysqli_stmt_execute($iStmt)) {
            throw new Exception("Item insert failed: " . mysqli_stmt_error($iStmt));
        }

        $actualSum += $rowTotal;

        // Deduct stock
        if ($stockId !== '') {
            $dq = mysqli_prepare($conn, "UPDATE stock SET availableQty = GREATEST(0, availableQty - ?) WHERE id = ?");
            mysqli_stmt_bind_param($dq, "is", $qty, $stockId);
            mysqli_stmt_execute($dq);
        }
    }

    /* 4c. Process Payment if added */
    $paidSum = 0;
    $paymentAdded = !empty($_POST['isPaymentAdded']) && $_POST['isPaymentAdded'] == '1';
    $paymentAmount = floatval($_POST['paymentAmountSubmitted'] ?? 0);

    if ($paymentAdded && $paymentAmount > 0) {
        if ($paymentAmount > $actualSum) {
            throw new Exception("Payment amount (₹" . number_format($paymentAmount, 2) . ") cannot exceed total sales order amount (₹" . number_format($actualSum, 2) . ").");
        }
        $paymentDate = trim($_POST['paymentDate'] ?? date('Y-m-d'));
        if (strtotime($paymentDate) > strtotime($today)) {
            throw new Exception("Payment date cannot be in the future.");
        }
        $paymentMode = trim($_POST['paymentMode'] ?? 'Cash');
        $paymentRef  = trim($_POST['paymentRef']  ?? '');

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $pIns = mysqli_prepare($conn, "
            INSERT INTO payment
                (id, createdBy, createdOn, modifiedBy, modifiedOn,
                 amount, category, inward, mode, refNo,
                 transactionDate, sales, purchase, jobCard)
            VALUES
                (?, ?, ?, ?, ?,
                 ?, 'Sales', b'0', ?, ?,
                 ?, ?, NULL, NULL)
        ");
        mysqli_stmt_bind_param(
            $pIns,
            "sssssdsssi",
            $uuid,
            $createdBy,
            $now,
            $createdBy,
            $now,
            $paymentAmount,
            $paymentMode,
            $paymentRef,
            $paymentDate,
            $salesId
        );

        if (!mysqli_stmt_execute($pIns)) {
            throw new Exception("Payment insertion failed: " . mysqli_stmt_error($pIns));
        }

        $paidSum = $paymentAmount;
        $orderStatus = 'Invoiced';
    }

    /* 4d. Update sales totals and final status */
    $upd = mysqli_prepare($conn, "UPDATE sales SET actualAmountSum = ?, paidAmountSum = ?, orderStatus = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, "ddsi", $actualSum, $paidSum, $orderStatus, $salesId);
    if (!mysqli_stmt_execute($upd)) {
        throw new Exception("Sales total update failed: " . mysqli_stmt_error($upd));
    }

    mysqli_commit($conn);
    echo "<script>alert('✅ Sales Order #" . addslashes($orderNo) . " created successfully!'); window.location.href='print_receipt.php?id=$salesId';</script>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    err($e->getMessage());
}
?>