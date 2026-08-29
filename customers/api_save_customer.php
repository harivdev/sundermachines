<?php
// customers/api_save_customer.php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Support both JSON body and standard $_POST
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$data = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$id = isset($data['id']) ? (int)$data['id'] : 0;
$address_id = isset($data['address_id']) ? (int)$data['address_id'] : 0;

$name = trim($data['name'] ?? '');
$phoneNo1 = trim($data['phoneNo1'] ?? '');

if (empty($name) || empty($phoneNo1)) {
    echo json_encode(['success' => false, 'error' => 'Customer Name and Primary Phone Number are required.']);
    exit;
}

$customerId = trim($data['customerId'] ?? '');
$phoneNo2 = trim($data['phoneNo2'] ?? '');
$whatsAppNo = trim($data['whatsAppNo'] ?? '');
$emailId = trim($data['emailId'] ?? '');
$active = isset($data['active']) && ($data['active'] === '0' || $data['active'] === 0 || $data['active'] === false) ? 0 : 1;

$line1 = trim($data['line1'] ?? '');
$line2 = trim($data['line2'] ?? '');
$city = trim($data['city'] ?? '');
$zipCode = trim($data['zipCode'] ?? '');

$now = date('Y-m-d H:i:s');

// Auto-generate customerId if creating new and none provided
if ($id === 0 && empty($customerId)) {
    $last_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT customerId FROM customer WHERE customerId LIKE 'C%' ORDER BY id DESC LIMIT 1"));
    $next_num = 1;
    if ($last_row && preg_match('/(\d+)$/', $last_row['customerId'], $m)) {
        $next_num = (int)$m[1] + 1;
    }
    $customerId = 'C' . str_pad($next_num, 7, '0', STR_PAD_LEFT);
}

// Escape strings
$e_customerId = mysqli_real_escape_string($conn, $customerId);
$e_name = mysqli_real_escape_string($conn, $name);
$e_phoneNo1 = mysqli_real_escape_string($conn, $phoneNo1);
$e_phoneNo2 = mysqli_real_escape_string($conn, $phoneNo2);
$e_whatsAppNo = mysqli_real_escape_string($conn, $whatsAppNo);
$e_emailId = mysqli_real_escape_string($conn, $emailId);

$e_line1 = mysqli_real_escape_string($conn, $line1);
$e_line2 = mysqli_real_escape_string($conn, $line2);
$e_city = mysqli_real_escape_string($conn, $city);
$e_zipCode = mysqli_real_escape_string($conn, $zipCode);

// 1. UPSERT Address
if ($address_id > 0) {
    $sql_addr = "UPDATE address
                 SET line1 = '$e_line1', line2 = '$e_line2', city = '$e_city', zipCode = '$e_zipCode', modifiedOn = '$now'
                 WHERE id = $address_id";
    mysqli_query($conn, $sql_addr);
} else {
    $sql_addr = "INSERT INTO address (createdOn, modifiedOn, line1, line2, city, zipCode)
                 VALUES ('$now', '$now', '$e_line1', '$e_line2', '$e_city', '$e_zipCode')";
    if (mysqli_query($conn, $sql_addr)) {
        $address_id = mysqli_insert_id($conn);
    }
}

$address_val = ($address_id > 0) ? $address_id : "NULL";

// 2. UPSERT Customer
if ($id > 0) {
    $sql_cust = "UPDATE customer
                 SET customerId = '$e_customerId',
                     name = '$e_name',
                     phoneNo1 = '$e_phoneNo1',
                     phoneNo2 = '$e_phoneNo2',
                     whatsAppNo = '$e_whatsAppNo',
                     emailId = '$e_emailId',
                     active = $active,
                     address = $address_val
                 WHERE id = $id";
    if (!mysqli_query($conn, $sql_cust)) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
} else {
    $sql_cust = "INSERT INTO customer (customerId, name, phoneNo1, phoneNo2, whatsAppNo, emailId, active, address)
                 VALUES ('$e_customerId', '$e_name', '$e_phoneNo1', '$e_phoneNo2', '$e_whatsAppNo', '$e_emailId', $active, $address_val)";
    if (!mysqli_query($conn, $sql_cust)) {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
    $id = mysqli_insert_id($conn);
}

// Fetch saved customer details
$fetch_sql = "SELECT c.id, c.customerId, c.name, c.phoneNo1, c.phoneNo2, c.whatsAppNo, c.emailId,
                     c.active, a.id AS address_id, a.line1, a.line2, a.city, a.zipCode
              FROM customer c
              LEFT JOIN address a ON c.address = a.id
              WHERE c.id = $id
              LIMIT 1";
$saved_res = mysqli_query($conn, $fetch_sql);
$saved_cust = mysqli_fetch_assoc($saved_res);
if ($saved_cust) {
    $saved_cust['active'] = ($saved_cust['active'] == 1 || $saved_cust['active'] === "\x01" || $saved_cust['active'] === '1') ? 1 : 0;
    $addrParts = array_filter([$saved_cust['line1'], $saved_cust['line2'], $saved_cust['city'], $saved_cust['zipCode']], function($val) {
        return !empty(trim($val ?? ''));
    });
    $saved_cust['fullAddress'] = !empty($addrParts) ? implode(', ', $addrParts) : '-';
}

echo json_encode([
    'success' => true,
    'message' => 'Customer saved successfully',
    'customer' => $saved_cust
]);
exit;
?>
