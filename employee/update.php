<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error_msg'] = "Invalid Employee ID.";
        header("Location: list.php");
        exit();
    }

    $empId = mysqli_real_escape_string($conn, trim($_POST['empId'] ?? ''));
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $phoneNo1 = mysqli_real_escape_string($conn, trim($_POST['phoneNo1'] ?? ''));
    $phoneNo2 = mysqli_real_escape_string($conn, trim($_POST['phoneNo2'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    
    $line1 = mysqli_real_escape_string($conn, trim($_POST['line1'] ?? ''));
    $line2 = mysqli_real_escape_string($conn, trim($_POST['line2'] ?? ''));
    $city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    $zipCode = mysqli_real_escape_string($conn, trim($_POST['zipCode'] ?? ''));
    
    $employmentType = mysqli_real_escape_string($conn, trim($_POST['employmentType'] ?? 'Full-Time'));
    $designation = mysqli_real_escape_string($conn, trim($_POST['designation'] ?? ''));
    $joinedDate = !empty($_POST['joinedDate']) ? $_POST['joinedDate'] : NULL;
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $gender = mysqli_real_escape_string($conn, trim($_POST['gender'] ?? ''));
    
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $rawPassword = $_POST['password'] ?? '';
    $role = mysqli_real_escape_string($conn, trim($_POST['role'] ?? 'STAFF'));
    if ($role === 'Other' || !empty($_POST['customRole'])) {
        $customRoleVal = mysqli_real_escape_string($conn, trim($_POST['customRole'] ?? ''));
        if (!empty($customRoleVal)) {
            $role = strtoupper($customRoleVal);
        }
    }
    $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
    
    $modifiedBy = $_SESSION['username'] ?? 'SYSTEM';
    $now = date('Y-m-d H:i:s');

    // Build update query depending on whether password was changed
    if (!empty($rawPassword)) {
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "
            UPDATE employee SET 
                empId = ?, name = ?, phoneNo1 = ?, phoneNo2 = ?, email = ?,
                line1 = ?, line2 = ?, city = ?, zipCode = ?,
                employmentType = ?, designation = ?, joinedDate = ?, dob = ?, gender = ?,
                username = ?, password = ?, role = ?, active = ?,
                modifiedBy = ?, modifiedOn = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssssssssissi",
            $empId, $name, $phoneNo1, $phoneNo2, $email,
            $line1, $line2, $city, $zipCode,
            $employmentType, $designation, $joinedDate, $dob, $gender,
            $username, $hashedPassword, $role, $active,
            $modifiedBy, $now, $id
        );
    } else {
        $stmt = mysqli_prepare($conn, "
            UPDATE employee SET 
                empId = ?, name = ?, phoneNo1 = ?, phoneNo2 = ?, email = ?,
                line1 = ?, line2 = ?, city = ?, zipCode = ?,
                employmentType = ?, designation = ?, joinedDate = ?, dob = ?, gender = ?,
                username = ?, role = ?, active = ?,
                modifiedBy = ?, modifiedOn = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssssssssssissi",
            $empId, $name, $phoneNo1, $phoneNo2, $email,
            $line1, $line2, $city, $zipCode,
            $employmentType, $designation, $joinedDate, $dob, $gender,
            $username, $role, $active,
            $modifiedBy, $now, $id
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        // Sync with legacy employee_auth if present
        if (!empty($username)) {
            if (!empty($rawPassword)) {
                $passHash = password_hash($rawPassword, PASSWORD_DEFAULT);
                @mysqli_query($conn, "
                    INSERT INTO employee_auth (username, password, role, createdBy, createdOn) 
                    VALUES ('$username', '$passHash', '$role', '$modifiedBy', '$now')
                    ON DUPLICATE KEY UPDATE password='$passHash', role='$role'
                ");
            } else {
                @mysqli_query($conn, "
                    UPDATE employee_auth SET role='$role' WHERE username='$username'
                ");
            }
        }

        $_SESSION['success_msg'] = "Employee '$name' updated successfully!";
        header("Location: list.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Failed to update employee: " . mysqli_stmt_error($stmt);
        header("Location: edit.php?id=" . $id);
        exit();
    }
}
