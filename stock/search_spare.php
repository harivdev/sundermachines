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

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if ($term === '' && $barcode === '') {
    echo json_encode([]);
    exit;
}

$data = [];

if ($barcode !== '') {
    $safeBarcode = mysqli_real_escape_string($conn, $barcode);
    $query = "SELECT 
                sp.id,
                sp.spareName,
                sp.partNo,
                sp.rackNumber,
                sp.picture,
                st.barCode,
                st.brand,
                st.model,
                st.machine,
                st.unit,
                st.actualPricePerUnit,
                st.sellingPricePerUnit,
                st.gstPercentage,
                st.warrantyInMonths,
                st.purchaseItem
              FROM stock st
              JOIN spares sp ON st.spare = sp.id
              WHERE st.barCode = '$safeBarcode'
              ORDER BY st.id DESC
              LIMIT 1";
    $res = mysqli_query($conn, $query);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        if (empty($row['picture'])) $row['picture'] = "no-image.png";
        $data[] = $row;
        echo json_encode($data);
        exit;
    }
}

$safeTerm = mysqli_real_escape_string($conn, $term);

$query = "SELECT DISTINCT
            sp.id,
            sp.spareName,
            sp.partNo,
            sp.rackNumber,
            sp.picture,
            st.barCode,
            st.brand,
            st.model,
            st.machine,
            st.unit,
            st.actualPricePerUnit,
            st.sellingPricePerUnit,
            st.gstPercentage,
            st.warrantyInMonths,
            st.purchaseItem
          FROM spares sp
          LEFT JOIN stock st ON sp.id = st.spare
          WHERE sp.spareName LIKE '%$safeTerm%' 
             OR sp.partNo LIKE '%$safeTerm%'
             OR st.barCode LIKE '%$safeTerm%'
          ORDER BY sp.spareName ASC
          LIMIT 15";

$res = mysqli_query($conn, $query);

$seenIds = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if (in_array($row['id'], $seenIds)) continue;
        $seenIds[] = $row['id'];

        if (empty($row['picture'])) {
            $row['picture'] = "no-image.png";
        }
        $data[] = $row;
    }
}

echo json_encode($data);
exit;
?>