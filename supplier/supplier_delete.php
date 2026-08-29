<?php
// supplier_delete.php – Delete a supplier and its linked address record
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_suppliers.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: manage_suppliers.php');
    exit;
}

// Fetch linked address ID before deleting
$row = $conn->query("SELECT address FROM supplier WHERE id = $id")->fetch_assoc();
$address_id = $row ? (int) $row['address'] : 0;

// Delete supplier first (FK constraint)
$conn->query("DELETE FROM supplier WHERE id = $id");

// Delete linked address if it exists
if ($address_id > 0) {
    $conn->query("DELETE FROM address WHERE id = $address_id");
}

header('Location: manage_suppliers.php?deleted=1');
exit;