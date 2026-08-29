<?php
require_once("../config/db.php");

header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? '');

if ($phone === '') {
    echo json_encode([]);
    exit;
}

$p = mysqli_real_escape_string($conn, $phone);

$query = "
    SELECT
        id, name, phoneNo1, whatsApp,
        addressLine1, addressLine2, city,
        active, role
    FROM customer
    WHERE phoneNo1 LIKE '%$p%'
       OR name     LIKE '%$p%'
    ORDER BY name ASC
    LIMIT 15
";

$res = mysqli_query($conn, $query);

if (!$res) {
    echo json_encode(['error' => mysqli_error($conn)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    // Cast active to bool for JS
    $row['active'] = (bool)$row['active'];
    $data[] = $row;
}

echo json_encode($data);
exit;
?>