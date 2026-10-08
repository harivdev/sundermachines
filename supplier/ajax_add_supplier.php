<?php
require_once("../config/db.php");
require_once("../includes/auth.php");

// Always return JSON — never redirect for AJAX calls
header('Content-Type: application/json');

// Manual session/auth check instead of requireAdmin() which redirects
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name      = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
$phoneNo1  = mysqli_real_escape_string($conn, trim($_POST['phoneNo1'] ?? ''));
$whatsAppNo= mysqli_real_escape_string($conn, trim($_POST['whatsAppNo'] ?? ''));
$emailId   = mysqli_real_escape_string($conn, trim($_POST['emailId'] ?? ''));
$line1     = mysqli_real_escape_string($conn, trim($_POST['line1'] ?? ''));
$line2     = mysqli_real_escape_string($conn, trim($_POST['line2'] ?? ''));
$city      = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
$zipCode   = mysqli_real_escape_string($conn, trim($_POST['zipCode'] ?? ''));
$active    = isset($_POST['active']) ? 1 : 0;

if (!$name || !$phoneNo1 || !$line1 || !$city || !$zipCode) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

$now       = date('Y-m-d H:i:s');
$createdBy = $_SESSION['username'] ?? 'System Admin';

// Insert address
$addrSQL = "INSERT INTO address (line1, line2, city, zipCode, createdOn, createdBy, modifiedOn, modifiedBy)
            VALUES ('$line1', '$line2', '$city', '$zipCode', '$now', '$createdBy', '$now', '$createdBy')";

if (!mysqli_query($conn, $addrSQL)) {
    echo json_encode(['success' => false, 'message' => 'Error saving address: ' . mysqli_error($conn)]);
    exit;
}
$addressId = mysqli_insert_id($conn);

// Insert supplier
$suppSQL = "INSERT INTO supplier (active, emailId, name, phoneNo1, phoneNo2, whatsAppNo, address, createdOn, createdBy, modifiedOn, modifiedBy)
            VALUES ($active, '$emailId', '$name', '$phoneNo1', '', '$whatsAppNo', $addressId, '$now', '$createdBy', '$now', '$createdBy')";

if (!mysqli_query($conn, $suppSQL)) {
    echo json_encode(['success' => false, 'message' => 'Error saving supplier: ' . mysqli_error($conn)]);
    exit;
}
$supplierId = mysqli_insert_id($conn);

echo json_encode([
    'success'  => true,
    'message'  => 'Supplier added successfully!',
    'supplier' => [
        'id'      => $supplierId,
        'name'    => htmlspecialchars(trim($_POST['name'])),
        'phone'   => htmlspecialchars(trim($_POST['phoneNo1'])),
        'email'   => htmlspecialchars(trim($_POST['emailId'] ?? '')),
        'line1'   => htmlspecialchars(trim($_POST['line1'])),
        'line2'   => htmlspecialchars(trim($_POST['line2'] ?? '')),
        'city'    => htmlspecialchars(trim($_POST['city'])),
        'zipCode' => htmlspecialchars(trim($_POST['zipCode'])),
    ]
]);
exit;
