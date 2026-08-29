<?php
require_once(__DIR__ . "/../config/db.php");

header('Content-Type: text/plain');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo "Access Denied";
    exit();
}

$prefix = isset($_GET['prefix']) ? strtoupper(trim($_GET['prefix'])) : '';

$attempts = 0;
do {
    $attempts++;
    if ($prefix !== '') {
        $barcode = $prefix . rand(100, 9999);
    } else {
        $barcode = (string)rand(10000000, 99999999);
    }
    $stmt = mysqli_prepare($conn, "SELECT id FROM stock WHERE LOWER(barCode) = LOWER(?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $barcode);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = (mysqli_num_rows($res) > 0);
    } else {
        $exists = false;
    }
} while ($exists && $attempts < 100);

echo $barcode;
exit;
?>
