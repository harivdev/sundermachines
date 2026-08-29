<?php
require_once(__DIR__ . "/config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$status = "info";

try {
    // 1. Disable Foreign Key Constraints for Safe Truncation
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

    // 2. Clear Job Card Module Data
    mysqli_query($conn, "TRUNCATE TABLE jobcarditems");
    mysqli_query($conn, "TRUNCATE TABLE jobcard");

    // 3. Clear Sales Module Data
    mysqli_query($conn, "TRUNCATE TABLE salesitems");
    mysqli_query($conn, "TRUNCATE TABLE sales");

    // 4. Clear Purchase Module Data
    mysqli_query($conn, "TRUNCATE TABLE purchaseitems");
    mysqli_query($conn, "TRUNCATE TABLE purchase");

    // 5. Clear Customer Address Records
    mysqli_query($conn, "DELETE FROM address WHERE id IN (SELECT address FROM customer WHERE address IS NOT NULL)");

    // 6. Clear Customer Module Data
    mysqli_query($conn, "TRUNCATE TABLE customer");

    // 7. Re-enable Foreign Key Constraints
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    // 8. Clear Session Draft Variables
    unset($_SESSION['draft_jobcard_no']);
    unset($_SESSION['draft_jobcard_ym']);

    $message = "ERP Dataset successfully reset to fresh state! All old Job Cards, Sales, Purchase, and Customer records have been removed.";
    $status = "success";
} catch (Exception $e) {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    $message = "Error resetting ERP dataset: " . $e->getMessage();
    $status = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP Dataset Reset - Fresh Installation</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 12px; padding: 32px; max-width: 520px; width: 100%; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); text-align: center; border: 1px solid #e2e8f0; }
        .icon { font-size: 48px; margin-bottom: 12px; }
        h2 { margin: 0 0 12px 0; font-size: 22px; font-weight: 700; color: #0f172a; }
        p { margin: 0 0 24px 0; font-size: 14px; color: #475569; line-height: 1.6; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 14px; margin: 4px; }
        .btn-secondary { background: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><?= $status === 'success' ? '✨' : '❌' ?></div>
        <h2><?= $status === 'success' ? 'ERP Dataset Reset Complete' : 'Reset Error' ?></h2>
        <p><?= htmlspecialchars($message) ?></p>
        <div>
            <a href="login/dashboard.php" class="btn">Go to Dashboard</a>
            <a href="jobcard/create.php" class="btn btn-secondary">New Job Card</a>
            <a href="sales/create.php" class="btn btn-secondary">New Sales</a>
        </div>
    </div>
</body>
</html>
