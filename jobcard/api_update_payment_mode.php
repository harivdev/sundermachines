<?php
require_once(__DIR__ . "/../config/db.php");
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!$data) {
    $data = $_POST;
}

$jobcardId = isset($data['jobcardId']) ? intval($data['jobcardId']) : (isset($data['id']) ? intval($data['id']) : 0);
$mode = trim($data['mode'] ?? $data['paymentMode'] ?? 'Cash');

if ($jobcardId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Job Card ID']);
    exit;
}

// If jobcard is already delivered, do not allow changing payment mode
$chkStatus = mysqli_query($conn, "SELECT jobStatus, (delivered + 0) AS delivered, deliveryDate FROM jobcard WHERE id = $jobcardId LIMIT 1");
if ($chkStatus && $stRow = mysqli_fetch_assoc($chkStatus)) {
    $rawSt = strtolower(trim((string)($stRow['jobStatus'] ?? '')));
    $isDelivered = (
        strpos($rawSt, 'deliver') !== false ||
        (!empty($stRow['delivered']) && ($stRow['delivered'] == 1 || ord((string)$stRow['delivered']) === 1)) ||
        (!empty($stRow['deliveryDate']) && $stRow['deliveryDate'] !== '0000-00-00')
    );
    if ($isDelivered) {
        echo json_encode(['success' => false, 'error' => 'Job Card is already delivered. Payment mode cannot be changed.']);
        exit;
    }
}

if (!in_array($mode, ['Cash', 'Card', 'UPI', 'NetBanking', 'Cheque'])) {
    $mode = 'Cash';
}

$safeMode = mysqli_real_escape_string($conn, $mode);
$user = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'admin');
$now = date('Y-m-d H:i:s');

// Ensure paymentMode column exists on jobcard table
$chkCol = @mysqli_query($conn, "SHOW COLUMNS FROM jobcard LIKE 'paymentMode'");
if ($chkCol && mysqli_num_rows($chkCol) === 0) {
    @mysqli_query($conn, "ALTER TABLE jobcard ADD COLUMN paymentMode VARCHAR(50) DEFAULT 'Cash'");
}

// Update jobcard table
$updJc = mysqli_query($conn, "UPDATE jobcard SET paymentMode = '$safeMode', modifiedBy = '$user', modifiedOn = '$now' WHERE id = $jobcardId");
if (!$updJc) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

// Fetch current paid amount
$jcQuery = mysqli_query($conn, "SELECT receivedAmountSum FROM jobcard WHERE id = $jobcardId LIMIT 1");
$paidAmount = 0;
if ($jcQuery && $row = mysqli_fetch_assoc($jcQuery)) {
    $paidAmount = (float)($row['receivedAmountSum'] ?? 0);
}

// Sync with payment table
$checkPay = mysqli_query($conn, "SELECT id FROM payment WHERE jobCard = $jobcardId LIMIT 1");
if ($checkPay && mysqli_num_rows($checkPay) > 0) {
    $payRow = mysqli_fetch_assoc($checkPay);
    $payId = $payRow['id'];
    @mysqli_query($conn, "UPDATE payment SET mode = '$safeMode', modifiedBy = '$user', modifiedOn = '$now' WHERE id = '$payId'");
} elseif ($paidAmount > 0) {
    $payId = 'PAY' . dechex(time()) . bin2hex(random_bytes(3));
    @mysqli_query($conn, "INSERT INTO payment (id, createdBy, createdOn, modifiedBy, modifiedOn, amount, category, inward, mode, refNo, transactionDate, jobCard) VALUES ('$payId', '$user', '$now', '$user', '$now', $paidAmount, 'JobCard', b'1', '$safeMode', NULL, CURDATE(), $jobcardId)");
}

echo json_encode(['success' => true, 'jobcardId' => $jobcardId, 'mode' => $mode]);
