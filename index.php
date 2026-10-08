<?php
session_start();
require_once("config/db.php");

if (isset($_SESSION['user_id'])) {
    header("Location: login/dashboard.php");
    exit();
}

$error = $_SESSION['login_error'] ?? "";
unset($_SESSION['login_error']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = "Please enter both username/email and password.";
        header("Location: index.php");
        exit();
    } else {
        $usernameSafe = mysqli_real_escape_string($conn_login, $username);

        $tableCheck = mysqli_query($conn_login, "SHOW TABLES LIKE 'user'");
        if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
            $query = "SELECT * FROM user WHERE username = '$usernameSafe' LIMIT 1";
            $result = mysqli_query($conn_login, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (isset($user['password'])) {
                    $matched = false;
                    if (password_verify($password, $user['password'])) {
                        $matched = true;
                        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            @mysqli_query($conn_login, "UPDATE `user` SET password = '" . mysqli_real_escape_string($conn_login, $newHash) . "' WHERE id = " . intval($user['id']));
                        }
                    } elseif ($password === $user['password']) {
                        $matched = true;
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        @mysqli_query($conn_login, "UPDATE `user` SET password = '" . mysqli_real_escape_string($conn_login, $newHash) . "' WHERE id = " . intval($user['id']));
                    }

                    if ($matched) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = strtoupper($user['role'] ?? 'ADMIN');
                        if ($_SESSION['role'] !== 'ADMIN') {
                            $_SESSION['role'] = 'USER';
                        }

                        header("Location: login/dashboard.php");
                        exit();
                    }
                }
            }
        }

        $userTable = null;
        $res1 = mysqli_query($conn, "SHOW TABLES LIKE 'employee_auth'");
        if ($res1 && mysqli_num_rows($res1) > 0) {
            $userTable = 'employee_auth';
        } else {
            $res2 = mysqli_query($conn, "SHOW TABLES LIKE 'credential'");
            if ($res2 && mysqli_num_rows($res2) > 0) {
                $userTable = 'credential';
            }
        }

        if ($userTable) {
            $employeeQuery = "SELECT * FROM {$userTable} WHERE username = '$usernameSafe' LIMIT 1";
            $emailColumn = mysqli_query($conn, "SHOW COLUMNS FROM {$userTable} LIKE 'email'");
            if ($emailColumn && mysqli_num_rows($emailColumn) > 0) {
                $employeeQuery = "SELECT * FROM {$userTable} WHERE username = '$usernameSafe' OR email = '$usernameSafe' LIMIT 1";
            }

            $employeeResult = mysqli_query($conn, $employeeQuery);

            if ($employeeResult && mysqli_num_rows($employeeResult) > 0) {
                $employee = mysqli_fetch_assoc($employeeResult);

                if (isset($employee['password'])) {
                    $empMatched = false;
                    if (password_verify($password, $employee['password'])) {
                        $empMatched = true;
                        if (password_needs_rehash($employee['password'], PASSWORD_BCRYPT)) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            @mysqli_query($conn, "UPDATE `{$userTable}` SET password = '" . mysqli_real_escape_string($conn, $newHash) . "' WHERE id = " . intval($employee['id']));
                        }
                    } elseif ($password === $employee['password']) {
                        $empMatched = true;
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        @mysqli_query($conn, "UPDATE `{$userTable}` SET password = '" . mysqli_real_escape_string($conn, $newHash) . "' WHERE id = " . intval($employee['id']));
                    }

                    if ($empMatched) {
                        $_SESSION['user_id'] = $employee['id'];
                        $_SESSION['username'] = $employee['username'] ?? $employee['email'] ?? $employee['name'];
                        $_SESSION['employee_name'] = $employee['name'] ?? $_SESSION['username'];
                        $_SESSION['role'] = strtoupper($employee['role'] ?? 'EMPLOYEE');
                        if ($_SESSION['role'] !== 'ADMIN') {
                            $_SESSION['role'] = 'USER';
                        }

                        header("Location: login/dashboard.php");
                        exit();
                    }
                }
            }
        }

        $_SESSION['login_error'] = "Invalid username/email or password.";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | SUNDER MACHNES WORLD</title>

    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-gold: #FDD017;
            --primary-gold-hover: #eab308;
            --brand-accent: #d97706;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --text-sub: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #fffdf0 30%, #fef3c7 70%, #fde68a 100%);
            background-attachment: fixed;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 960px;
            min-height: 540px;
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(217, 119, 6, 0.12);
            box-shadow: -16px -16px 40px rgba(0, 0, 0, 0.2), 16px 20px 45px rgba(0, 0, 0, 0.18);
            display: flex;
            overflow: hidden;
            position: relative;
            animation: cardAppear 0.5s ease-out;
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .left-panel {
            flex: 1.1;
            background: linear-gradient(180deg, #fefce8 0%, #fef3c7 100%);
            padding: 42px 40px 32px 40px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 20px;
            position: relative;
            overflow: hidden;
            border-right: 1px solid #fde68a;
        }

        .top-brand {
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 2;
        }

        .top-brand img {
            height: 56px;
            width: auto;
            object-fit: contain;
        }

        .top-brand-title {
            font-size: 1.4rem;
            font-weight: 900;
            color: #78350f;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.15);
        }

        .illustration-container {
            width: 100%;
            max-width: 420px;
            margin: auto;
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .illustration-container img {
            width: 100%;
            height: auto;
            display: block;
        }

        .right-panel {
            flex: 1;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h2 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 13.5px;
            color: var(--text-sub);
            font-weight: 500;
            line-height: 1.5;
        }

        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            font-size: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-dark);
            background: #f9fafb;
            outline: none;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            background: #fffdf9;
            border-color: #c9a771;
            box-shadow: 0 0 0 3px rgba(201, 167, 113, 0.18);
        }

        .form-options {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #475569;
            font-weight: 500;
        }

        .remember-me input {
            accent-color: #d97706;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .btn-signin {
            width: 100%;
            padding: 14px 24px;
            background: var(--primary-gold);
            color: #0f172a;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(253, 208, 23, 0.4);
            transition: all 0.25s ease;
        }

        .btn-signin:hover {
            background: var(--primary-gold-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(253, 208, 23, 0.5);
        }

        .copyright-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 1.15rem;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        @media (max-width: 860px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 420px;
            }

            .left-panel {
                padding: 24px;
                min-height: 200px;
            }

            .illustration-container {
                max-width: 220px;
            }

            .right-panel {
                padding: 32px 24px;
            }

            .form-header h2 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <div class="left-panel">

            <div class="top-brand">
                <img src="img/logo.png" alt="SUNDER MACHINES WORLD Logo">
                <span class="top-brand-title">SUNDER MACHINES WORLD</span>
            </div>

            <div class="illustration-container">
                <img src="img/login_illustration.svg" alt="Sunder Billing Illustration">
            </div>
        </div>

        <div class="right-panel">
            <div class="form-header">
                <h2>Sign In</h2>
                <p>Welcome! Please enter your credentials to access your dashboard.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-box">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label class="form-label">Email / Username</label>
                    <div class="input-box">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" class="form-control" placeholder="Enter username or email"
                            required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-box">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Enter password" required>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" id="showPassCheckbox" onclick="togglePass()">
                        <span>Show Password</span>
                    </label>
                </div>

                <button type="submit" class="btn-signin">Sign In</button>
            </form>

            <div class="copyright-footer">
                &copy; <?= date('Y') ?> Sanruth Softtech
            </div>
        </div>

    </div>

    <script>
        function togglePass() {
            var passInput = document.getElementById("password");
            var check = document.getElementById("showPassCheckbox");
            if (check.checked) {
                passInput.type = "text";
            } else {
                passInput.type = "password";
            }
        }
    </script>

</body>

</html>
