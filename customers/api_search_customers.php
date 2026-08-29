<?php
// customers/api_search_customers.php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

$query = trim($_GET['query'] ?? $_GET['q'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$name = trim($_GET['name'] ?? '');
$whatsApp = trim($_GET['whatsApp'] ?? $_GET['wa'] ?? '');

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($query !== '') {
    $likeQ = "%" . $query . "%";
    $whereClauses[] = "(
        c.phoneNo1 LIKE ? OR 
        c.phoneNo2 LIKE ? OR 
        c.whatsAppNo LIKE ? OR 
        c.name LIKE ? OR 
        c.customerId LIKE ? OR 
        c.id = ?
    )";
    $types .= "ssssss";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $query;
}

if ($phone !== '') {
    $whereClauses[] = "(c.phoneNo1 LIKE ? OR c.phoneNo2 LIKE ?)";
    $types .= "ss";
    $params[] = "%" . $phone . "%";
    $params[] = "%" . $phone . "%";
}

if ($name !== '') {
    $whereClauses[] = "c.name LIKE ?";
    $types .= "s";
    $params[] = "%" . $name . "%";
}

if ($whatsApp !== '') {
    $whereClauses[] = "c.whatsAppNo LIKE ?";
    $types .= "s";
    $params[] = "%" . $whatsApp . "%";
}

$where = "WHERE " . implode(" AND ", $whereClauses);

// 1. Calculate total records
$countSql = "SELECT COUNT(*) as total FROM customer c LEFT JOIN address a ON c.address = a.id $where";
$countStmt = mysqli_prepare($conn, $countSql);

if (!empty($params)) {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
}

mysqli_stmt_execute($countStmt);
$countRes = mysqli_stmt_get_result($countStmt);
$totalRow = mysqli_fetch_assoc($countRes);
$totalRecords = $totalRow ? (int)$totalRow['total'] : 0;
$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

// 2. Fetch data with LIMIT and OFFSET
$dataSql = "SELECT c.id, c.customerId, c.name, c.phoneNo1, c.phoneNo2, c.whatsAppNo, c.emailId,
                   c.active, a.id AS address_id, a.line1, a.line2, a.city, a.zipCode
            FROM customer c
            LEFT JOIN address a ON c.address = a.id
            $where
            ORDER BY c.id DESC
            LIMIT ? OFFSET ?";

$dataStmt = mysqli_prepare($conn, $dataSql);
$dataTypes = $types . "ii";
$dataParams = array_merge($params, [$limit, $offset]);

mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($dataStmt);
$res = mysqli_stmt_get_result($dataStmt);

if (!$res) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

$customers = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['active'] = ($row['active'] == 1 || $row['active'] === "\x01" || $row['active'] === '1' || $row['active'] === true) ? 1 : 0;
    
    $addrParts = array_filter([$row['line1'] ?? '', $row['line2'] ?? '', $row['city'] ?? '', $row['zipCode'] ?? ''], function($val) {
        return !empty(trim((string)$val));
    });
    $row['fullAddress'] = !empty($addrParts) ? implode(', ', $addrParts) : '-';
    
    $customers[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $customers,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'count' => count($customers)
    ]
]);
exit;
?>
