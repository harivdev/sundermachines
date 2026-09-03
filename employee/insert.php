<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
    
    $createdBy = $_SESSION['username'] ?? 'SYSTEM';
    $now = date('Y-m-d H:i:s');
    
    // Auto-generate empId if empty
    if (empty($empId)) {
        $resCount = mysqli_query($conn, "SELECT MAX(id) as maxId FROM employee");
        $rowC = mysqli_fetch_assoc($resCount);
        $nextNum = intval($rowC['maxId'] ?? 0) + 1;
        $empId = 'EMP' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    // Password Hashing if provided
    $hashedPassword = !empty($rawPassword) ? password_hash($rawPassword, PASSWORD_DEFAULT) : '';

    $stmt = mysqli_prepare($conn, "
        INSERT INTO employee (
            empId, name, phoneNo1, phoneNo2, email,
            line1, line2, city, zipCode,
            employmentType, designation, joinedDate, dob, gender,
            username, password, role, active,
            createdBy, createdOn, modifiedBy, modifiedOn
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ");

    $joinedDateVal = $joinedDate;
    $dobVal = $dob;

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssssssssissss",
        $empId, $name, $phoneNo1, $phoneNo2, $email,
        $line1, $line2, $city, $zipCode,
        $employmentType, $designation, $joinedDateVal, $dobVal, $gender,
        $username, $hashedPassword, $role, $active,
        $createdBy, $now, $createdBy, $now
    );

    if (mysqli_stmt_execute($stmt)) {
        // Also insert into legacy/secondary employee_auth table if username & password present
        if (!empty($username) && !empty($rawPassword)) {
            @mysqli_query($conn, "
                INSERT INTO employee_auth (username, password, role, createdBy, createdOn) 
                VALUES ('$username', '$hashedPassword', '$role', '$createdBy', '$now')
                ON DUPLICATE KEY UPDATE password='$hashedPassword', role='$role'
            ");
        }
        
        $_SESSION['success_msg'] = "Employee '$name' ($empId) created successfully!";
        header("Location: list.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Failed to create employee: " . mysqli_stmt_error($stmt);
        header("Location: add.php");
        exit();
    }
}
