<?php
// user_delete.php – Delete an admin user from billing_login.user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

require_once("../config/db.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: manage_users.php");
    exit();
}

// Prevent admin from deleting their own account
$result = $conn_login->query("SELECT username FROM user WHERE id = $id");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['username'] === $_SESSION['username']) {
        header("Location: manage_users.php");
        exit();
    }
}

$conn_login->query("DELETE FROM user WHERE id = $id");

header("Location: manage_users.php?deleted=1");
exit();
