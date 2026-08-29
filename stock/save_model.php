<?php
require_once("../config/db.php");

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$name = trim($_POST['name'] ?? '');
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Model name is required']);
    exit();
}

// Check duplicate model
$stmtChk = mysqli_prepare($conn, "SELECT id, model FROM model WHERE LOWER(model) = LOWER(?)");
mysqli_stmt_bind_param($stmtChk, "s", $name);
mysqli_stmt_execute($stmtChk);
$resChk = mysqli_stmt_get_result($stmtChk);

if ($row = mysqli_fetch_assoc($resChk)) {
    echo json_encode([
        'success' => true,
        'id' => $row['id'],
        'name' => $row['model'],
        'message' => 'Model already exists',
        'alreadyExists' => true
    ]);
    exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO model (model, createdBy, createdOn) VALUES (?, ?, NOW())");
$user = $_SESSION['username'] ?? 'ADMIN';
mysqli_stmt_bind_param($stmt, "ss", $name, $user);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'name' => $name,
        'message' => 'Model created successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save model: ' . mysqli_error($conn)]);
}
exit();
?>