<?php
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    echo "<script>alert('Access denied: insufficient privileges'); window.location='../login/dashboard.php';</script>";
    exit();
}

// If accessed directly via GET, redirect to add_stock.php form page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    header("Location: add_stock.php");
    exit();
}

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function respondError($msg, $isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    } else {
        $encodedMsg = urlencode($msg);
        echo "<script>alert(" . json_encode($msg) . "); window.location.href='add_stock.php?error={$encodedMsg}';</script>";
        exit;
    }
}

// Check if multi-stock batch array is submitted
$multiBatch = isset($_POST['multi_items']) ? $_POST['multi_items'] : null;

if ($multiBatch && is_array($multiBatch) && count($multiBatch) > 0) {
    $insertedCount = 0;
    $lastBarcode = '';

    foreach ($multiBatch as $itemData) {
        $spare = trim($itemData['spare'] ?? '');
        $itemName = trim($itemData['itemName'] ?? '');
        $brand = trim($itemData['brand'] ?? '');
        $model = trim($itemData['model'] ?? '');
        $machine = trim($itemData['machine'] ?? '');
        $unit = intval($itemData['unit'] ?? 1);
        $serialNo = trim($itemData['serialNo'] ?? '');
        $barcode = trim($itemData['barCode'] ?? '');
        $purchaseItem = intval($itemData['purchaseItem'] ?? 0);
        $warrantyInMonths = intval($itemData['warrantyInMonths'] ?? 0);
        $selled = isset($itemData['selled']) ? 1 : 0;
        $quantity = intval($itemData['quantity'] ?? 1);

        $actualQty = floatval($itemData['actualPricePerQty'] ?? 0);
        $actualUnit = floatval($itemData['actualPricePerUnit'] ?? 0);
        $sellingQty = floatval($itemData['sellingPricePerQty'] ?? 0);
        $sellingUnit = floatval($itemData['sellingPricePerUnit'] ?? 0);
        $selledUnit = floatval($itemData['selledPricePerUnit'] ?? 0);
        $gstPercentage = floatval($itemData['gstPercentage'] ?? 0);

        if (empty($spare) || empty($itemName)) continue;

        // Generate barcode if empty
        if (empty($barcode)) {
            $attempts = 0;
            do {
                $attempts++;
                $barcode = (string)rand(10000000, 99999999);
                $chk = mysqli_prepare($conn, "SELECT id FROM stock WHERE barCode = ?");
                if ($chk) {
                    mysqli_stmt_bind_param($chk, "s", $barcode);
                    mysqli_stmt_execute($chk);
                    $chkRes = mysqli_stmt_get_result($chk);
                    $exists = ($chkRes && mysqli_num_rows($chkRes) > 0);
                } else {
                    $exists = false;
                }
            } while ($exists && $attempts < 100);
        }

        $id = uniqid("STK");
        $brandVal = ($brand !== '' && $brand !== '0') ? intval($brand) : NULL;
        $modelVal = ($model !== '' && $model !== '0') ? intval($model) : NULL;
        $machineVal = ($machine !== '' && $machine !== '0') ? intval($machine) : NULL;
        $purchaseVal = NULL;
        if ($purchaseItem > 0) {
            $chkPI = mysqli_prepare($conn, "SELECT id FROM purchaseitems WHERE id = ?");
            if ($chkPI) {
                mysqli_stmt_bind_param($chkPI, "i", $purchaseItem);
                mysqli_stmt_execute($chkPI);
                $resPI = mysqli_stmt_get_result($chkPI);
                if ($resPI && mysqli_num_rows($resPI) > 0) {
                    $purchaseVal = $purchaseItem;
                }
            }
        }
        $serialVal = ($serialNo !== '') ? $serialNo : NULL;

        $sql = "INSERT INTO stock (id, spare, itemName, barCode, quantity, availableQty, actualPricePerQty, actualPricePerUnit, sellingPricePerQty, sellingPricePerUnit, selledPricePerUnit, gstPercentage, selled, brand, model, machine, unit, serialNo, warrantyInMonths, purchaseItem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sissiiddddddiiiiisii", $id, $spare, $itemName, $barcode, $quantity, $quantity, $actualQty, $actualUnit, $sellingQty, $sellingUnit, $selledUnit, $gstPercentage, $selled, $brandVal, $modelVal, $machineVal, $unit, $serialVal, $warrantyInMonths, $purchaseVal);
            if (mysqli_stmt_execute($stmt)) {
                $insertedCount++;
                $lastBarcode = $barcode;
            }
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => $insertedCount, 'barcode' => $lastBarcode, 'message' => "$insertedCount stock records added successfully!"]);
        exit;
    } else {
        header("Location: add_stock.php?success=1&count=" . $insertedCount);
        exit;
    }
}

// ================= SINGLE STOCK INSERT =================
$spare = trim($_POST['spare'] ?? '');
$itemName = trim($_POST['itemName'] ?? '');
$brand = trim($_POST['brand'] ?? '');
$model = trim($_POST['model'] ?? '');
$machine = trim($_POST['machine'] ?? '');
$unit = intval($_POST['unit'] ?? 1);
$serialNo = trim($_POST['serialNo'] ?? '');
$barcodeInput = trim($_POST['barCode'] ?? '');
$purchaseItem = intval($_POST['purchaseItem'] ?? 0);
$warrantyInMonths = intval($_POST['warrantyInMonths'] ?? 0);
$selled = isset($_POST['selled']) ? 1 : 0;

// Check mandatory fields
if (empty($brand) || $brand === '0' || empty($model) || $model === '0') {
    respondError('Brand and Model are required. Please fill them before saving stock.', $isAjax);
}

$quantity = intval($_POST['quantity'] ?? 0);
$availableQty = intval($_POST['availableQty'] ?? $quantity);

$actualQty = floatval($_POST['actualPricePerQty'] ?? 0);
$actualUnit = floatval($_POST['actualPricePerUnit'] ?? 0);
$sellingQty = floatval($_POST['sellingPricePerQty'] ?? 0);
$sellingUnit = floatval($_POST['sellingPricePerUnit'] ?? 0);
$selledUnit = floatval($_POST['selledPricePerUnit'] ?? 0);
$gstPercentage = floatval($_POST['gstPercentage'] ?? 0);

if (empty($spare)) {
    respondError("❌ Please select a spare / item properly", $isAjax);
}

if (empty($itemName)) {
    respondError("❌ Item name is required", $isAjax);
}

if ($quantity <= 0) {
    respondError("❌ Quantity must be greater than 0", $isAjax);
}

// Validate spare exists
$spareVal = intval($spare);
$stmtSp = mysqli_prepare($conn, "SELECT id FROM spares WHERE id = ?");
mysqli_stmt_bind_param($stmtSp, "i", $spareVal);
mysqli_stmt_execute($stmtSp);
$resSp = mysqli_stmt_get_result($stmtSp);
if (mysqli_num_rows($resSp) == 0) {
    respondError("❌ Invalid spare selected. Please search and select again.", $isAjax);
}

// Duplicate Barcode check if barcode provided manually
if (!empty($barcodeInput)) {
    $chkB = mysqli_prepare($conn, "SELECT id FROM stock WHERE barCode = ?");
    mysqli_stmt_bind_param($chkB, "s", $barcodeInput);
    mysqli_stmt_execute($chkB);
    $resB = mysqli_stmt_get_result($chkB);
    if (mysqli_num_rows($resB) > 0) {
        respondError("❌ Barcode '$barcodeInput' already exists in stock!", $isAjax);
    }
    $barcode = $barcodeInput;
} else {
    $attempts = 0;
    do {
        $attempts++;
        $barcode = (string)rand(10000000, 99999999);
        $chk = mysqli_prepare($conn, "SELECT id FROM stock WHERE barCode = ?");
        if ($chk) {
            mysqli_stmt_bind_param($chk, "s", $barcode);
            mysqli_stmt_execute($chk);
            $chkRes = mysqli_stmt_get_result($chk);
            $exists = ($chkRes && mysqli_num_rows($chkRes) > 0);
        } else {
            $exists = false;
        }
    } while ($exists && $attempts < 100);
}

// Duplicate Serial Number check if provided
if (!empty($serialNo)) {
    $chkS = mysqli_prepare($conn, "SELECT id FROM stock WHERE serialNo = ?");
    mysqli_stmt_bind_param($chkS, "s", $serialNo);
    mysqli_stmt_execute($chkS);
    $resS = mysqli_stmt_get_result($chkS);
    if (mysqli_num_rows($resS) > 0) {
        respondError("❌ Serial Number '$serialNo' already exists in stock!", $isAjax);
    }
}

// Upload stock/spare photo if provided
if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['picture']['tmp_name'];
    $fileName = basename($_FILES['picture']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $allowed)) {
        $uploadDir = '../uploads/stock/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $safeCode = !empty($barcode) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $barcode) : ('STK' . time());
        $newFileName = $safeCode . '_1.' . $ext;
        $relPath = 'uploads/stock/' . $newFileName;
        if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
            $stmtPic = mysqli_prepare($conn, "UPDATE spares SET picture = ? WHERE id = ?");
            if ($stmtPic) {
                mysqli_stmt_bind_param($stmtPic, "si", $relPath, $spareVal);
                mysqli_stmt_execute($stmtPic);
            }
        }
    }
}

$id = uniqid("STK");
$brandVal = ($brand !== '' && $brand !== '0') ? intval($brand) : NULL;
$modelVal = ($model !== '' && $model !== '0') ? intval($model) : NULL;
$machineVal = ($machine !== '' && $machine !== '0') ? intval($machine) : NULL;
$purchaseVal = NULL;
if ($purchaseItem > 0) {
    $chkPI = mysqli_prepare($conn, "SELECT id FROM purchaseitems WHERE id = ?");
    if ($chkPI) {
        mysqli_stmt_bind_param($chkPI, "i", $purchaseItem);
        mysqli_stmt_execute($chkPI);
        $resPI = mysqli_stmt_get_result($chkPI);
        if ($resPI && mysqli_num_rows($resPI) > 0) {
            $purchaseVal = $purchaseItem;
        }
    }
}
$serialVal = ($serialNo !== '') ? $serialNo : NULL;

$sql = "INSERT INTO stock (id, spare, itemName, barCode, quantity, availableQty, actualPricePerQty, actualPricePerUnit, sellingPricePerQty, sellingPricePerUnit, selledPricePerUnit, gstPercentage, selled, brand, model, machine, unit, serialNo, warrantyInMonths, purchaseItem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    respondError("❌ Prepare failed: " . mysqli_error($conn), $isAjax);
}

mysqli_stmt_bind_param(
    $stmt,
    "sissiiddddddiiiiisii",
    $id,
    $spareVal,
    $itemName,
    $barcode,
    $quantity,
    $availableQty,
    $actualQty,
    $actualUnit,
    $sellingQty,
    $sellingUnit,
    $selledUnit,
    $gstPercentage,
    $selled,
    $brandVal,
    $modelVal,
    $machineVal,
    $unit,
    $serialVal,
    $warrantyInMonths,
    $purchaseVal
);

if (mysqli_stmt_execute($stmt)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Stock added successfully!'
        ]);
        exit;
    } else {
        header("Location: list.php?success=1");
        exit;
    }
} else {
    respondError("❌ Database Error: " . mysqli_stmt_error($stmt), $isAjax);
}
?>