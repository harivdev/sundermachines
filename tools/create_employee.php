<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'EMPLOYEE');

    if ($username === '' || $password === '') {
        $msg = 'Username and password required.';
    } else {
        $create_auth = "CREATE TABLE IF NOT EXISTS employee_auth (
            id BIGINT NOT NULL AUTO_INCREMENT,
            createdBy VARCHAR(255) DEFAULT NULL,
            createdOn DATETIME DEFAULT CURRENT_TIMESTAMP,
            modifiedBy VARCHAR(255) DEFAULT NULL,
            modifiedOn DATETIME DEFAULT NULL,
            password VARCHAR(255) DEFAULT NULL,
            role VARCHAR(255) DEFAULT NULL,
            username VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $create_cred = "CREATE TABLE IF NOT EXISTS credential (
            id BIGINT NOT NULL AUTO_INCREMENT,
            createdBy VARCHAR(255) DEFAULT NULL,
            createdOn DATETIME DEFAULT CURRENT_TIMESTAMP,
            modifiedBy VARCHAR(255) DEFAULT NULL,
            modifiedOn DATETIME DEFAULT NULL,
            password VARCHAR(255) DEFAULT NULL,
            role VARCHAR(255) DEFAULT NULL,
            username VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        if (!mysqli_query($conn, $create_auth)) {
            $msg = 'Failed to create employee_auth table: ' . mysqli_error($conn);
        } elseif (!mysqli_query($conn, $create_cred)) {
            $msg = 'Failed to create credential table: ' . mysqli_error($conn);
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "INSERT INTO employee_auth (username, password, role, createdBy, createdOn) VALUES (?, ?, ?, 'SYSTEM', NOW())");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $username, $hash, $role);
                if (mysqli_stmt_execute($stmt)) {
                    $stmt2 = mysqli_prepare($conn, "INSERT INTO credential (username, password, role, createdBy, createdOn) VALUES (?, ?, ?, 'SYSTEM', NOW())");
                    if ($stmt2) {
                        mysqli_stmt_bind_param($stmt2, 'sss', $username, $hash, $role);
                        mysqli_stmt_execute($stmt2);
                    }
                    $msg = 'Employee created successfully.';
                } else {
                    $msg = 'Insert failed (employee_auth): ' . mysqli_stmt_error($stmt);
                }
            } else {
                $msg = 'Prepare failed: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Create Employee</title>

    <link rel="icon" type="image/png" href="../img/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../img/logo.png">
</head>

<body style="font-family:Arial,sans-serif;padding:20px;">
    <h2>Create Employee Credential</h2>
    <?php if (!empty($msg)): ?>
        <div style="padding:10px;background:#eef;border:1px solid #bcd;margin-bottom:12px;">
            <?php echo htmlspecialchars($msg); ?>
        </div><?php endif; ?>
    <form method="post">
        <label>Username: <input name="username"></label><br><br>
        <label>Password: <input name="password" type="password"></label><br><br>
        <label>Role: <input name="role" value="EMPLOYEE"></label><br><br>
        <button type="submit">Create</button>
    </form>
    <p>After creating, try Employee login on the main login page.</p>
</body>

</html>
