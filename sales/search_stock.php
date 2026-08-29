<?php
require_once("../config/db.php");

header('Content-Type: application/json');

$term = trim($_GET['term'] ?? '');

if ($term === '') {
    echo json_encode([]);
    exit;
}

$t = mysqli_real_escape_string($conn, $term);

$query = "
    SELECT
        st.id              AS stockId,
        st.barCode,
        st.selledPricePerUnit,
        st.sellingPricePerUnit,
        st.gstPercentage,
        st.availableQty,
        st.serialNo,
        sp.id              AS spareId,
        sp.spareName,
        sp.partNo,
        sp.rackNumber,
        sp.picture,
        'SPR'              AS category
    FROM stock st
    LEFT JOIN spares sp ON st.spare = sp.id
    WHERE st.availableQty > 0
      AND (
            sp.spareName   LIKE '%$t%'
         OR st.barCode     LIKE '%$t%'
         OR sp.partNo      LIKE '%$t%'
         OR sp.rackNumber  LIKE '%$t%'
         OR st.serialNo    LIKE '%$t%'
      )
    ORDER BY sp.spareName ASC
    LIMIT 20
";

$res = mysqli_query($conn, $query);

if (!$res) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

echo json_encode($data);
exit;
?>