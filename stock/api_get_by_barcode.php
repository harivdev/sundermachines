<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$barcode = trim($_GET['barcode'] ?? $_POST['barcode'] ?? $_GET['q'] ?? '');

if (empty($barcode)) {
    echo json_encode(['success' => false, 'message' => 'Barcode is required']);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT 
        st.*, 
        s.spareName, 
        s.partNo, 
        s.rackNumber,
        s.picture,
        b.brandName, 
        m.model AS modelName,
        mc.machineName
    FROM stock st
    LEFT JOIN spares s ON st.spare = s.id
    LEFT JOIN brand b ON st.brand = b.id
    LEFT JOIN model m ON st.model = m.id
    LEFT JOIN machine mc ON st.machine = mc.id
    WHERE LOWER(TRIM(st.barCode)) = LOWER(?) 
       OR LOWER(TRIM(st.serialNo)) = LOWER(?)
       OR LOWER(TRIM(s.partNo)) = LOWER(?)
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, "sss", $barcode, $barcode, $barcode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Fallback search with LIKE if exact match yields no row
if (!$row) {
    $likeBarcode = '%' . $barcode . '%';
    $stmtLike = mysqli_prepare($conn, "
        SELECT 
            st.*, 
            s.spareName, 
            s.partNo, 
            s.rackNumber,
            s.picture,
            b.brandName, 
            m.model AS modelName,
            mc.machineName
        FROM stock st
        LEFT JOIN spares s ON st.spare = s.id
        LEFT JOIN brand b ON st.brand = b.id
        LEFT JOIN model m ON st.model = m.id
        LEFT JOIN machine mc ON st.machine = mc.id
        WHERE st.barCode LIKE ? 
           OR st.serialNo LIKE ?
           OR s.partNo LIKE ?
        LIMIT 1
    ");
    if ($stmtLike) {
        mysqli_stmt_bind_param($stmtLike, "sss", $likeBarcode, $likeBarcode, $likeBarcode);
        mysqli_stmt_execute($stmtLike);
        $resLike = mysqli_stmt_get_result($stmtLike);
        $row = mysqli_fetch_assoc($resLike);
    }
}

if ($row) {
    $foundBarcode = !empty($row['barCode']) ? $row['barCode'] : $barcode;
    $foundItemName = !empty($row['spareName']) ? $row['spareName'] : ($row['itemName'] ?? 'N/A');

    $itemData = [
        'id' => $row['id'],
        'barCode' => $foundBarcode,
        'serialNo' => (!empty($row['serialNo']) ? $row['serialNo'] : $foundBarcode),
        'spareName' => $foundItemName,
        'partNo' => (!empty($row['partNo']) ? $row['partNo'] : '-'),
        'rackNumber' => (!empty($row['rackNumber']) ? $row['rackNumber'] : '-'),
        'brandName' => (!empty($row['brandName']) ? $row['brandName'] : '-'),
        'modelName' => (!empty($row['modelName']) ? $row['modelName'] : '-'),
        'machineName' => (!empty($row['machineName']) ? $row['machineName'] : '-'),
        'availableQty' => (int)($row['availableQty'] ?? 0),
        'quantity' => (int)($row['quantity'] ?? 0),
        'sellingPricePerUnit' => (float)($row['sellingPricePerUnit'] ?? 0),
        'selledPricePerUnit' => (float)($row['selledPricePerUnit'] ?? 0),
        'actualPricePerUnit' => (float)($row['actualPricePerUnit'] ?? 0),
        'gstPercentage' => (float)($row['gstPercentage'] ?? 0),
        'selled' => (bool)($row['selled'] ?? false),
        'selledText' => (($row['selled'] ?? 0) ? 'Yes' : 'No'),
        'picture' => (!empty($row['picture']) ? $row['picture'] : 'no-image.png')
    ];

    echo json_encode([
        'success' => true,
        'barcode' => $foundBarcode,
        'item_name' => $foundItemName,
        'message' => 'Barcode Scanned Successfully',
        'data' => $itemData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'barcode' => $barcode,
        'item_name' => null,
        'message' => 'Barcode Not Found'
    ]);
}
exit;
