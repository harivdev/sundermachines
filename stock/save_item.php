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

$spareName = trim($_POST['spareName'] ?? '');
$partNo = trim($_POST['partNo'] ?? '');
$rackNumber = trim($_POST['rackNumber'] ?? '');
$brand = isset($_POST['brand']) ? intval($_POST['brand']) : null;
$model = isset($_POST['model']) ? intval($_POST['model']) : null;
$machine = isset($_POST['machine']) ? intval($_POST['machine']) : null;
$unit = isset($_POST['unit']) ? intval($_POST['unit']) : 1;
$gstPercentage = isset($_POST['gstPercentage']) ? floatval($_POST['gstPercentage']) : 0;
$warrantyInMonths = isset($_POST['warrantyInMonths']) ? intval($_POST['warrantyInMonths']) : 0;
$minQty = isset($_POST['minQty']) ? intval($_POST['minQty']) : 0;
$purchaseItem = (isset($_POST['purchaseItem']) && intval($_POST['purchaseItem']) > 0) ? intval($_POST['purchaseItem']) : null;
$active = (isset($_POST['active']) && ($_POST['active'] == '1' || $_POST['active'] == 'on')) ? 1 : 1;

if (empty($spareName)) {
    echo json_encode(['success' => false, 'message' => 'Spare Name is required']);
    exit();
}

// Check duplicate Item Name + Part Number
if (!empty($partNo)) {
    $stmtChk = mysqli_prepare($conn, "SELECT id FROM spares WHERE LOWER(spareName) = LOWER(?) AND LOWER(partNo) = LOWER(?)");
    mysqli_stmt_bind_param($stmtChk, "ss", $spareName, $partNo);
} else {
    $stmtChk = mysqli_prepare($conn, "SELECT id FROM spares WHERE LOWER(spareName) = LOWER(?)");
    mysqli_stmt_bind_param($stmtChk, "s", $spareName);
}
mysqli_stmt_execute($stmtChk);
$resChk = mysqli_stmt_get_result($stmtChk);

if ($row = mysqli_fetch_assoc($resChk)) {
    echo json_encode([
        'success' => false,
        'message' => 'An item with this name / part number already exists!'
    ]);
    exit();
}

// Image upload handling for 'picture' or 'image'
$picture = 'no-image.png';
$fileObj = isset($_FILES['picture']) ? $_FILES['picture'] : (isset($_FILES['image']) ? $_FILES['image'] : null);

if ($fileObj && $fileObj['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $fileObj['tmp_name'];
    $fileName = $fileObj['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($fileExt, $allowed)) {
        $uploadDir = '../uploads/stock/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $safePart = !empty($partNo) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $partNo) : 'STK';
        $newFileName = $safePart . '_' . time() . '_1.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        
        if (move_uploaded_file($fileTmp, $destination)) {
            $picture = 'uploads/stock/' . $newFileName;
        }
    }
}

$user = $_SESSION['username'] ?? 'ADMIN';
$stmt = mysqli_prepare($conn, "INSERT INTO spares (spareName, partNo, rackNumber, picture, active, createdBy, createdOn) VALUES (?, ?, ?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, "ssssis", $spareName, $partNo, $rackNumber, $picture, $active, $user);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Spare created successfully',
        'spare' => [
            'id' => $newId,
            'spareName' => $spareName,
            'partNo' => $partNo,
            'rackNumber' => $rackNumber,
            'picture' => $picture,
            'brand' => $brand,
            'model' => $model,
            'machine' => $machine,
            'unit' => $unit,
            'gstPercentage' => $gstPercentage,
            'warrantyInMonths' => $warrantyInMonths,
            'minQty' => $minQty,
            'purchaseItem' => $purchaseItem
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
exit();
?>
