<?php
// customer_save.php – Insert or Update customer + address (plain mysqli queries)
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_customers.php');
    exit;
}

// ── Read and sanitize POST values ──
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;

$customerId = $conn->real_escape_string(trim($_POST['customerId'] ?? ''));
$name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
$phoneNo1 = $conn->real_escape_string(trim($_POST['phoneNo1'] ?? ''));
$phoneNo2 = $conn->real_escape_string(trim($_POST['phoneNo2'] ?? ''));
$whatsAppNo = $conn->real_escape_string(trim($_POST['whatsAppNo'] ?? ''));
$emailId = $conn->real_escape_string(trim($_POST['emailId'] ?? ''));
$active = isset($_POST['active']) ? 1 : 0;

$line1 = $conn->real_escape_string(trim($_POST['line1'] ?? ''));
$line2 = $conn->real_escape_string(trim($_POST['line2'] ?? ''));
$city = $conn->real_escape_string(trim($_POST['city'] ?? ''));
$zipCode = $conn->real_escape_string(trim($_POST['zipCode'] ?? ''));

$now = date('Y-m-d H:i:s');

// ══════════════════════════════════════════
//  UPSERT ADDRESS
//  (address table HAS createdOn, modifiedOn)
// ══════════════════════════════════════════
if ($address_id > 0) {
    // Update existing address
    $sql_addr = "UPDATE address
                 SET    line1      = '$line1',
                        line2      = '$line2',
                        city       = '$city',
                        zipCode    = '$zipCode',
                        modifiedOn = '$now'
                 WHERE  id = $address_id";
    $conn->query($sql_addr);

} else {
    // Insert new address
    $sql_addr = "INSERT INTO address (createdOn, modifiedOn, line1, line2, city, zipCode)
                 VALUES ('$now', '$now', '$line1', '$line2', '$city', '$zipCode')";
    $conn->query($sql_addr);
    $address_id = $conn->insert_id;
}

// ══════════════════════════════════════════
//  UPSERT CUSTOMER
//  (customer table does NOT have modifiedOn)
// ══════════════════════════════════════════
if ($id > 0) {
    // Update existing customer
    $sql_cust = "UPDATE customer
                 SET    customerId = '$customerId',
                        name       = '$name',
                        phoneNo1   = '$phoneNo1',
                        phoneNo2   = '$phoneNo2',
                        whatsAppNo = '$whatsAppNo',
                        emailId    = '$emailId',
                        active     = $active,
                        address    = $address_id
                 WHERE  id = $id";
    $conn->query($sql_cust);

} else {
    // Insert new customer
    $sql_cust = "INSERT INTO customer
                    (customerId, name, phoneNo1, phoneNo2, whatsAppNo, emailId, active, address)
                 VALUES
                    ('$customerId', '$name', '$phoneNo1', '$phoneNo2', '$whatsAppNo',
                     '$emailId', $active, $address_id)";
    $conn->query($sql_cust);
}

// Redirect back with success flag
header('Location: manage_customers.php?saved=1');
exit;