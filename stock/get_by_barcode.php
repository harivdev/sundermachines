<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
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
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $barcode, $barcode);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row) {
    // Always use the scanned barcode as the display barcode — NOT the DB's barCode field
    // (DB barCode may be a different identifier than the scanned code)
    $foundBarcode = $barcode;
    $foundItemName = !empty($row['spareName']) ? $row['spareName'] : ($row['itemName'] ?? 'N/A');
    $sellingPrice = (float)($row['sellingPricePerUnit'] ?? $row['sellingPricePerQty'] ?? $row['selledPricePerUnit'] ?? 0);

    $itemData = [
        'id' => $row['id'],
        'stock_id' => $row['id'],
        'barCode' => $foundBarcode,
        'serialNo' => (!empty($row['serialNo']) ? $row['serialNo'] : $foundBarcode),
        'spareName' => $foundItemName,
        'itemName' => $foundItemName,
        'spareId' => $row['spare'] ?? null,
        'spare_id' => $row['spare'] ?? null,
        'partNo' => (!empty($row['partNo']) ? $row['partNo'] : '-'),
        'rackNumber' => (!empty($row['rackNumber']) ? $row['rackNumber'] : '-'),
        'brandName' => (!empty($row['brandName']) ? $row['brandName'] : '-'),
        'modelName' => (!empty($row['modelName']) ? $row['modelName'] : '-'),
        'machineName' => (!empty($row['machineName']) ? $row['machineName'] : '-'),
        'availableQty' => (int)($row['availableQty'] ?? 0),
        'quantity' => (int)($row['quantity'] ?? 0),
        'sellingPrice' => $sellingPrice,
        'sellingPricePerUnit' => $sellingPrice,
        'sellingPricePerQty' => (float)($row['sellingPricePerQty'] ?? $sellingPrice),
        'selledPricePerUnit' => (float)($row['selledPricePerUnit'] ?? $sellingPrice),
        'actualPricePerUnit' => (float)($row['actualPricePerUnit'] ?? $row['actualPricePerQty'] ?? 0),
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

