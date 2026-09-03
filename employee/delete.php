<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: list.php");
    exit();
}

$action = $_GET['action'] ?? 'toggle';

if ($action === 'delete') {
    $stmt = mysqli_prepare($conn, "DELETE FROM employee WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Employee deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete employee.";
    }
} else {
    // Toggle active status
    $stmt = mysqli_prepare($conn, "UPDATE employee SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Employee status updated.";
    } else {
        $_SESSION['error_msg'] = "Failed to update status.";
    }
}

header("Location: list.php");
exit();
