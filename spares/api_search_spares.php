<?php
// spares/api_search_spares.php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

$term = trim($_GET['query'] ?? $_GET['term'] ?? $_GET['q'] ?? '');
$termEsc = mysqli_real_escape_string($conn, $term);

$where = "WHERE s.spare IS NOT NULL";
if ($termEsc !== '') {
    $where .= " AND (
        sp.spareName LIKE '%$termEsc%' OR 
        s.itemName LIKE '%$termEsc%' OR 
        sp.partNo LIKE '%$termEsc%' OR 
        s.barCode LIKE '%$termEsc%' OR 
        sp.rackNumber LIKE '%$termEsc%' OR 
        s.id LIKE '%$termEsc%'
    )";
}

$sql = "SELECT 
            s.id AS stock_id,
            sp.id AS spare_id,
            COALESCE(sp.spareName, s.itemName) AS spareName,
            COALESCE(sp.partNo, '-') AS partNo,
            COALESCE(sp.rackNumber, '-') AS rackNumber,
            COALESCE(s.barCode, '-') AS barCode,
            s.availableQty,
            COALESCE(s.sellingPricePerQty, s.sellingPricePerUnit, 0) AS sellingPrice,
            COALESCE(s.gstPercentage, 0) AS gstPercentage,
            sp.picture
        FROM stock s
        LEFT JOIN spares sp ON s.spare = sp.id
        $where
        ORDER BY spareName ASC
        LIMIT 50";

$res = mysqli_query($conn, $sql);
$data = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['availableQty'] = (int)$row['availableQty'];
        $row['sellingPrice'] = (float)$row['sellingPrice'];
        $row['gstPercentage'] = (float)$row['gstPercentage'];
        if (empty($row['picture'])) {
            $row['picture'] = "no-image.png";
        }
        $data[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $data]);
exit;
?>
