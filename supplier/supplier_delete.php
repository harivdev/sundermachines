<?php
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

$row = $conn->query("SELECT address FROM supplier WHERE id = $id")->fetch_assoc();
$address_id = $row ? (int) $row['address'] : 0;

$conn->query("DELETE FROM supplier WHERE id = $id");

if ($address_id > 0) {
    $conn->query("DELETE FROM address WHERE id = $address_id");
}

header('Location: manage_suppliers.php?deleted=1');
exit;
