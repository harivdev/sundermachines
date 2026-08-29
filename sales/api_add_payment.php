<?php
// sales/api_add_payment.php
require_once(__DIR__ . "/../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please log in to perform this action']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$data = is_array($jsonInput) ? array_merge($_POST, $jsonInput) : $_POST;

$salesId = isset($data['salesId']) ? (int)$data['salesId'] : (isset($data['id']) ? (int)$data['id'] : 0);
$amount = isset($data['amount']) ? (float)$data['amount'] : (isset($data['paymentAmount']) ? (float)$data['paymentAmount'] : 0.0);
$paymentDate = trim($data['paymentDate'] ?? $data['transactionDate'] ?? date('Y-m-d'));
$paymentMode = trim($data['paymentMode'] ?? $data['mode'] ?? 'Cash');
$refNo = trim($data['paymentRef'] ?? $data['refNo'] ?? '');

$today = date('Y-m-d');
$userEmail = $_SESSION['username'] ?? 'System Admin';
$now = date('Y-m-d H:i:s.') . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Validate inputs
if ($salesId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid sales order ID.']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Payment amount must be greater than 0.']);
    exit;
}

if (empty($paymentDate)) {
    echo json_encode(['success' => false, 'error' => 'Payment date is required.']);
    exit;
}

if (strtotime($paymentDate) > strtotime($today)) {
    echo json_encode(['success' => false, 'error' => 'Payment date cannot be in the future.']);
    exit;
}

// Generate UUID for payment.id
$uuid = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// ATOMIC TRANSACTION
mysqli_begin_transaction($conn);

try {
    // 1. Verify sales order exists and lock row
    $chkSale = mysqli_prepare($conn, "SELECT id, actualAmountSum, paidAmountSum, orderStatus FROM sales WHERE id = ? FOR UPDATE");
    mysqli_stmt_bind_param($chkSale, "i", $salesId);
    mysqli_stmt_execute($chkSale);
    $sRes = mysqli_stmt_get_result($chkSale);
    $sale = mysqli_fetch_assoc($sRes);

    if (!$sale) {
        throw new Exception("Sales Order #$salesId not found.");
    }

    $actualTotal = (float)$sale['actualAmountSum'];

    // 2. Check whether payment already exists in payment table
    $chkPmt = mysqli_prepare($conn, "SELECT id, amount, transactionDate, mode, refNo FROM payment WHERE sales = ? LIMIT 1");
    mysqli_stmt_bind_param($chkPmt, "i", $salesId);
    mysqli_stmt_execute($chkPmt);
    $pRes = mysqli_stmt_get_result($chkPmt);
    $existingPmt = mysqli_fetch_assoc($pRes);

    if ($existingPmt) {
        throw new Exception("Payment has already been added for this sales order.");
    }

    // 3. Validate payment amount does not exceed total
    if ($amount > $actualTotal) {
        throw new Exception("Payment amount (₹" . number_format($amount, 2) . ") cannot exceed total sales order amount (₹" . number_format($actualTotal, 2) . ").");
    }

    // 4. Insert exactly one payment
    $insPmt = mysqli_prepare($conn, "
        INSERT INTO payment
            (id, createdBy, createdOn, modifiedBy, modifiedOn,
             amount, category, inward, mode, refNo,
             transactionDate, sales, purchase, jobCard)
        VALUES
            (?, ?, ?, ?, ?,
             ?, 'Sales', b'0', ?, ?,
             ?, ?, NULL, NULL)
    ");
    mysqli_stmt_bind_param(
        $insPmt,
        "sssssdsssi",
        $uuid,
        $userEmail,
        $now,
        $userEmail,
        $now,
        $amount,
        $paymentMode,
        $refNo,
        $paymentDate,
        $salesId
    );

    if (!mysqli_stmt_execute($insPmt)) {
        throw new Exception("Failed to save payment: " . mysqli_stmt_error($insPmt));
    }

    // 5. Update sales order status to 'Invoiced' and set paid amount
    $updSale = mysqli_prepare($conn, "
        UPDATE sales
        SET paidAmountSum = ?,
            orderStatus = 'Invoiced',
            modifiedBy = ?,
            modifiedOn = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($updSale, "dssi", $amount, $userEmail, $now, $salesId);

    if (!mysqli_stmt_execute($updSale)) {
        throw new Exception("Failed to update sales order status: " . mysqli_stmt_error($updSale));
    }

    mysqli_commit($conn);

    $balance = max(0.0, round($actualTotal - $amount, 2));

    echo json_encode([
        'success' => true,
        'message' => 'Payment saved successfully!',
        'salesId' => $salesId,
        'paidAmountSum' => $amount,
        'balance' => $balance,
        'actualAmountSum' => $actualTotal,
        'orderStatus' => 'Invoiced',
        'payment' => [
            'id' => $uuid,
            'amount' => $amount,
            'mode' => $paymentMode,
            'refNo' => $refNo,
            'paymentDate' => $paymentDate
        ]
    ]);
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
