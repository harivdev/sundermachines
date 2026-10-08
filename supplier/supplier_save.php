<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_suppliers.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;

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
$modifiedBy = 'System Admin';
$createdBy = 'System Admin';

if ($address_id > 0) {
    $sql_addr = "UPDATE address
                 SET    line1      = '$line1',
                        line2      = '$line2',
                        city       = '$city',
                        zipCode    = '$zipCode',
                        modifiedOn = '$now',
                        modifiedBy = '$modifiedBy'
                 WHERE  id = $address_id";
    $conn->query($sql_addr);

} else {
    $sql_addr = "INSERT INTO address (createdOn, createdBy, modifiedOn, modifiedBy, line1, line2, city, zipCode)
                 VALUES ('$now', '$createdBy', '$now', '$modifiedBy', '$line1', '$line2', '$city', '$zipCode')";
    $conn->query($sql_addr);
    $address_id = $conn->insert_id;
}

if ($id > 0) {
    $sql_supp = "UPDATE supplier
                 SET    name       = '$name',
                        phoneNo1   = '$phoneNo1',
                        phoneNo2   = '$phoneNo2',
                        whatsAppNo = '$whatsAppNo',
                        emailId    = '$emailId',
                        active     = $active,
                        address    = $address_id,
                        modifiedOn = '$now',
                        modifiedBy = '$modifiedBy'
                 WHERE  id = $id";
    $conn->query($sql_supp);

} else {
    $sql_supp = "INSERT INTO supplier
                    (name, phoneNo1, phoneNo2, whatsAppNo, emailId, active, address, createdOn, createdBy, modifiedOn, modifiedBy)
                 VALUES
                    ('$name', '$phoneNo1', '$phoneNo2', '$whatsAppNo', '$emailId',
                     $active, $address_id, '$now', '$createdBy', '$now', '$modifiedBy')";
    $conn->query($sql_supp);
}

header('Location: manage_suppliers.php?saved=1');
exit;
