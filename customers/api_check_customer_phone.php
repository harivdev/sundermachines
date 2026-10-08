<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? $_GET['phoneNo1'] ?? $_POST['phoneNo1'] ?? '');
$exclude_id = (int)($_GET['exclude_id'] ?? $_POST['exclude_id'] ?? 0);

if ($phone === '') {
    echo json_encode(['exists' => false]);
    exit;
}

// Clean phone (keep digits only)
$cleanDigits = preg_replace('/\D/', '', $phone);
if (strlen($cleanDigits) < 7) {
    echo json_encode(['exists' => false]);
    exit;
}

$safePhone = mysqli_real_escape_string($conn, $phone);
$safeDigits = mysqli_real_escape_string($conn, $cleanDigits);

$wherePhone = "(
    c.phoneNo1 = '$safePhone'
    OR c.phoneNo1 = '$safeDigits'
    OR REPLACE(REPLACE(REPLACE(REPLACE(c.phoneNo1, ' ', ''), '-', ''), '+', ''), '(', '') = '$safeDigits'
";

if (strlen($cleanDigits) >= 10) {
    $last10 = substr($cleanDigits, -10);
    $safeLast10 = mysqli_real_escape_string($conn, $last10);
    $wherePhone .= " OR c.phoneNo1 LIKE '%$safeLast10%'";
}
$wherePhone .= ")";

$whereExclude = ($exclude_id > 0) ? " AND c.id != $exclude_id" : "";

$sql = "SELECT c.id, c.customerId, c.name, c.phoneNo1, c.phoneNo2, c.whatsAppNo, c.emailId,
               c.active, a.id AS address_id, a.line1, a.line2, a.city, a.zipCode,
               a.createdOn, a.modifiedOn
        FROM customer c
        LEFT JOIN address a ON c.address = a.id
        WHERE $wherePhone $whereExclude
        ORDER BY c.id DESC
        LIMIT 1";

$result = mysqli_query($conn, $sql);
if ($result && ($row = mysqli_fetch_assoc($result))) {
    $createdRaw = !empty($row['createdOn']) && $row['createdOn'] !== '0000-00-00 00:00:00' ? $row['createdOn'] : (!empty($row['modifiedOn']) && $row['modifiedOn'] !== '0000-00-00 00:00:00' ? $row['modifiedOn'] : '');
    $createdFormatted = '—';
    if (!empty($createdRaw)) {
        $ts = strtotime($createdRaw);
        if ($ts && $ts > 0) {
            $createdFormatted = date('d/m/Y', $ts);
        }
    }

    echo json_encode([
        'exists' => true,
        'customer' => [
            'id' => (int)$row['id'],
            'customerId' => $row['customerId'] ?? '',
            'name' => $row['name'] ?? '',
            'phoneNo1' => $row['phoneNo1'] ?? '',
            'phoneNo2' => $row['phoneNo2'] ?? '',
            'whatsAppNo' => $row['whatsAppNo'] ?? '',
            'emailId' => $row['emailId'] ?? '',
            'active' => (int)$row['active'],
            'address_id' => (int)($row['address_id'] ?? 0),
            'line1' => $row['line1'] ?? '',
            'line2' => $row['line2'] ?? '',
            'city' => !empty(trim($row['city'] ?? '')) ? trim($row['city']) : '—',
            'zipCode' => $row['zipCode'] ?? '',
            'createdOn' => $createdRaw,
            'createdOnFormatted' => $createdFormatted
        ]
    ]);
    exit;
}

echo json_encode(['exists' => false]);
exit;
