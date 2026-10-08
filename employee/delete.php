<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: list.php");
    exit();
}

$action = $_GET['action'] ?? 'delete';

// Fetch employee details first
$empRes = mysqli_query($conn, "SELECT * FROM employee WHERE id = $id LIMIT 1");
if (!$empRes || mysqli_num_rows($empRes) === 0) {
    $_SESSION['error_msg'] = "Employee not found or already deleted.";
    header("Location: list.php");
    exit();
}

$empData = mysqli_fetch_assoc($empRes);
$empName = trim($empData['name'] ?? 'Employee');
$empUsername = trim($empData['username'] ?? '');
$credentialId = intval($empData['credential'] ?? 0);

if ($action === 'delete') {
    try {
        // Check for dependencies that have foreign keys pointing to employee(id)
        $jcCount = 0;
        $jcCheck = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM jobcard WHERE employee = $id");
        if ($jcCheck && ($row = mysqli_fetch_assoc($jcCheck))) {
            $jcCount = intval($row['cnt']);
        }

        $ojCount = 0;
        $ojCheck = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM onsitejob WHERE employee = $id");
        if ($ojCheck && ($row = mysqli_fetch_assoc($ojCheck))) {
            $ojCount = intval($row['cnt']);
        }

        if ($jcCount > 0 || $ojCount > 0) {
            $refItems = [];
            if ($jcCount > 0) $refItems[] = "$jcCount Job Card(s)";
            if ($ojCount > 0) $refItems[] = "$ojCount Onsite Job(s)";
            $refText = implode(' and ', $refItems);

            // Cannot hard delete due to database integrity & foreign keys!
            // Deactivate them instead so records are preserved and employee is removed from active use
            @mysqli_query($conn, "UPDATE employee SET active = 0 WHERE id = $id");
            if (!empty($empUsername)) {
                $uEsc = mysqli_real_escape_string($conn, $empUsername);
                @mysqli_query($conn, "DELETE FROM employee_auth WHERE username = '$uEsc'");
            }

            $_SESSION['error_msg'] = "Cannot permanently delete '{$empName}' because they are assigned to {$refText}. The employee has been marked as Inactive instead to preserve work history.";
        } else {
            // No foreign key conflicts: clean up auth credentials and permanently delete
            if (!empty($empUsername)) {
                $uEsc = mysqli_real_escape_string($conn, $empUsername);
                @mysqli_query($conn, "DELETE FROM employee_auth WHERE username = '$uEsc'");
                @mysqli_query($conn, "DELETE FROM user WHERE username = '$uEsc'");
            }

            $del = @mysqli_query($conn, "DELETE FROM employee WHERE id = $id");
            if ($del) {
                if ($credentialId > 0) {
                    @mysqli_query($conn, "DELETE FROM credential WHERE id = $credentialId");
                }
                $_SESSION['success_msg'] = "Employee '{$empName}' deleted successfully.";
            } else {
                // If MySQL still rejected deletion due to constraint
                @mysqli_query($conn, "UPDATE employee SET active = 0 WHERE id = $id");
                $_SESSION['error_msg'] = "Could not delete '{$empName}' due to database constraints. Marked as Inactive instead.";
            }
        }
    } catch (Throwable $e) {
        // Prevent HTTP 500 under any circumstances and soft-delete
        @mysqli_query($conn, "UPDATE employee SET active = 0 WHERE id = $id");
        if (!empty($empUsername)) {
            $uEsc = mysqli_real_escape_string($conn, $empUsername);
            @mysqli_query($conn, "DELETE FROM employee_auth WHERE username = '$uEsc'");
        }
        $_SESSION['error_msg'] = "Cannot permanently delete '{$empName}' due to existing records. Marked as Inactive instead.";
    }
} else {
    // Action toggle status (Active / Inactive)
    try {
        $currActive = intval($empData['active'] ?? 1);
        $newActive = ($currActive === 1) ? 0 : 1;
        $updRes = mysqli_query($conn, "UPDATE employee SET active = $newActive WHERE id = $id");
        if ($updRes) {
            $statusLabel = ($newActive === 1) ? "activated" : "deactivated";
            $_SESSION['success_msg'] = "Employee '{$empName}' has been {$statusLabel}.";
        } else {
            $_SESSION['error_msg'] = "Failed to update status.";
        }
    } catch (Throwable $e) {
        $_SESSION['error_msg'] = "Failed to update status: " . $e->getMessage();
    }
}

header("Location: list.php");
exit();
