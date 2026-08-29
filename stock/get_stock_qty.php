<?php
require_once("../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$spareId = isset($_GET['spare']) ? intval($_GET['spare']) : 0;
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if ($spareId <= 0 && $barcode === '') {
    echo json_encode(['success' => false, 'qty' => 0]);
    exit;
}

$data = [
    'success' => true,
    'qty' => 0,
    'brand' => null,
    'model' => null,
    'machine' => null,
    'unit' => 1,
    'actualPricePerUnit' => 0,
    'sellingPricePerUnit' => 0,
    'gstPercentage' => 0,
    'warrantyInMonths' => 0,
    'purchaseItem' => null,
    'minQty' => 0,
    'serialNo' => '',
    'picture' => ''
];

// If searching by barcode, find spare ID first
if ($barcode !== '') {
    $stmtB = mysqli_prepare($conn, "SELECT spare, itemName, brand, model, machine, unit, actualPricePerUnit, sellingPricePerUnit, gstPercentage, warrantyInMonths, purchaseItem FROM stock WHERE barCode = ? ORDER BY id DESC LIMIT 1");
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, "s", $barcode);
        mysqli_stmt_execute($stmtB);
        $resB = mysqli_stmt_get_result($stmtB);
        if ($rowB = mysqli_fetch_assoc($resB)) {
            $spareId = intval($rowB['spare']);
            $data['brand'] = $rowB['brand'];
            $data['model'] = $rowB['model'];
            $data['machine'] = $rowB['machine'];
            $data['unit'] = $rowB['unit'];
            $data['actualPricePerUnit'] = floatval($rowB['actualPricePerUnit']);
            $data['sellingPricePerUnit'] = floatval($rowB['sellingPricePerUnit']);
            $data['gstPercentage'] = floatval($rowB['gstPercentage']);
            $data['warrantyInMonths'] = intval($rowB['warrantyInMonths']);
            $data['purchaseItem'] = $rowB['purchaseItem'];
        }
    }
}

if ($spareId > 0) {
    // Get total available stock quantity
    $stmtQty = mysqli_prepare($conn, "SELECT COALESCE(SUM(availableQty), 0) AS totalQty FROM stock WHERE spare = ?");
    if ($stmtQty) {
        mysqli_stmt_bind_param($stmtQty, "i", $spareId);
        mysqli_stmt_execute($stmtQty);
        $resQty = mysqli_stmt_get_result($stmtQty);
        if ($rowQty = mysqli_fetch_assoc($resQty)) {
            $data['qty'] = intval($rowQty['totalQty']);
        }
    }

    // Get latest pricing, brand, model, machine from most recent stock entry if not already set
    $stmtS = mysqli_prepare($conn, "SELECT brand, model, machine, unit, actualPricePerUnit, sellingPricePerUnit, gstPercentage, warrantyInMonths, purchaseItem FROM stock WHERE spare = ? ORDER BY id DESC LIMIT 1");
    if ($stmtS) {
        mysqli_stmt_bind_param($stmtS, "i", $spareId);
        mysqli_stmt_execute($stmtS);
        $resS = mysqli_stmt_get_result($stmtS);
        if ($rowS = mysqli_fetch_assoc($resS)) {
            if (!$data['brand']) $data['brand'] = $rowS['brand'];
            if (!$data['model']) $data['model'] = $rowS['model'];
            if (!$data['machine']) $data['machine'] = $rowS['machine'];
            if (!$data['unit']) $data['unit'] = $rowS['unit'];
            if (!$data['actualPricePerUnit']) $data['actualPricePerUnit'] = floatval($rowS['actualPricePerUnit']);
            if (!$data['sellingPricePerUnit']) $data['sellingPricePerUnit'] = floatval($rowS['sellingPricePerUnit']);
            if (!$data['gstPercentage']) $data['gstPercentage'] = floatval($rowS['gstPercentage']);
            if (!$data['warrantyInMonths']) $data['warrantyInMonths'] = intval($rowS['warrantyInMonths']);
            if (!$data['purchaseItem']) $data['purchaseItem'] = $rowS['purchaseItem'];
        }
    }

    // Get spare picture and details
    $stmtSp = mysqli_prepare($conn, "SELECT id, spareName, partNo, picture, rackNumber FROM spares WHERE id = ?");
    if ($stmtSp) {
        mysqli_stmt_bind_param($stmtSp, "i", $spareId);
        mysqli_stmt_execute($stmtSp);
        $resSp = mysqli_stmt_get_result($stmtSp);
        if ($rowSp = mysqli_fetch_assoc($resSp)) {
            $data['spareId'] = $rowSp['id'];
            $data['spareName'] = $rowSp['spareName'];
            $data['partNo'] = $rowSp['partNo'];
            $data['picture'] = $rowSp['picture'] ? $rowSp['picture'] : 'no-image.png';
            $data['rackNumber'] = $rowSp['rackNumber'];
        }
    }
}

echo json_encode($data);
exit;
?>
