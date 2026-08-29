<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$term = trim($_GET['term'] ?? $_GET['query'] ?? $_GET['q'] ?? '');
$termEsc = mysqli_real_escape_string($conn, $term);

$where = "WHERE sp.active = 1";
if ($termEsc !== '') {
    $where .= " AND (sp.spareName LIKE '%$termEsc%' OR sp.partNo LIKE '%$termEsc%')";
}

$query = "
    SELECT 
        sp.id AS spare_id,
        sp.spareName,
        COALESCE(sp.partNo, '-') AS partNo,
        (SELECT b.id FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brand_id,
        (SELECT b.brandName FROM stock st LEFT JOIN brand b ON st.brand = b.id WHERE st.spare = sp.id AND st.brand IS NOT NULL LIMIT 1) AS brandName,
        (SELECT m.id FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS model_id,
        (SELECT m.model FROM stock st LEFT JOIN model m ON st.model = m.id WHERE st.spare = sp.id AND st.model IS NOT NULL LIMIT 1) AS modelName,
        COALESCE((SELECT st.sellingPricePerUnit FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS sellingPrice,
        COALESCE((SELECT st.actualPricePerUnit FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS actualPrice,
        COALESCE((SELECT st.gstPercentage FROM stock st WHERE st.spare = sp.id LIMIT 1), 0) AS gstPercentage,
        COALESCE((SELECT st.barCode FROM stock st WHERE st.spare = sp.id LIMIT 1), '-') AS barCode
    FROM spares sp
    $where
    ORDER BY sp.spareName ASC
    LIMIT 30
";

$res = mysqli_query($conn, $query);
$items = [];

if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $items[] = [
            'spare_id' => (int)$r['spare_id'],
            'spareName' => $r['spareName'] ?? '',
            'partNo' => $r['partNo'] ?? '',
            'brand_id' => $r['brand_id'] ? (int)$r['brand_id'] : null,
            'brandName' => $r['brandName'] ?? '',
            'model_id' => $r['model_id'] ? (int)$r['model_id'] : null,
            'modelName' => $r['modelName'] ?? '',
            'sellingPrice' => (float)$r['sellingPrice'],
            'actualPrice' => (float)$r['actualPrice'],
            'gstPercentage' => (float)$r['gstPercentage'],
            'barCode' => $r['barCode'] ?? ''
        ];
    }
}

echo json_encode(['success' => true, 'items' => $items]);
exit;
