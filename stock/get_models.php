<?php
require_once("../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$query = "SELECT id, model FROM model ORDER BY model ASC";
$res = mysqli_query($conn, $query);

$models = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $models[] = $row;
    }
}

echo json_encode($models);
exit();
?>
