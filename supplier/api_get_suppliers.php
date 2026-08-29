<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$query = "
    SELECT 
        s.id,
        s.name,
        s.phoneNo1,
        s.phoneNo2,
        s.whatsAppNo,
        s.emailId,
        a.line1,
        a.line2,
        a.city,
        a.zipCode
    FROM supplier s
    LEFT JOIN address a ON s.address = a.id
    WHERE s.active = 1
    ORDER BY s.name ASC
";

$res = mysqli_query($conn, $query);
$suppliers = [];

if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $suppliers[] = [
            'id' => (int)$r['id'],
            'name' => $r['name'] ?? '',
            'phone' => $r['phoneNo1'] ?? $r['whatsAppNo'] ?? '',
            'email' => $r['emailId'] ?? '',
            'line1' => $r['line1'] ?? '',
            'line2' => $r['line2'] ?? '',
            'city' => $r['city'] ?? '',
            'zipCode' => $r['zipCode'] ?? ''
        ];
    }
}

echo json_encode(['success' => true, 'suppliers' => $suppliers]);
exit;
