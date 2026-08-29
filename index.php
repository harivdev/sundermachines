<?php
session_start();
require_once("config/db.php");

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: login/dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter both username/email and password.";
    } else {
        $usernameSafe = mysqli_real_escape_string($conn_login, $username);

        // Try admin login first using the main admin table.
        $tableCheck = mysqli_query($conn_login, "SHOW TABLES LIKE 'user'");
        if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
            $query = "SELECT * FROM user WHERE username = '$usernameSafe' LIMIT 1";
            $result = mysqli_query($conn_login, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                // Support both hashed and plain-text passwords for admin users.
                if ((isset($user['password']) && password_verify($password, $user['password'])) || $password === $user['password']) {
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

        // Fall back to employee credentials if admin login did not match.
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

                if (password_verify($password, $employee['password']) || $password === $employee['password']) {
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

        $error = "Invalid username/email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sunder Billing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FDD017;
            --primary-hover: #eab308;
            --brand-accent: #FDD017;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            --glass-bg: rgba(255, 255, 255, 0.96);
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-gradient);
            background-attachment: fixed;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(253, 208, 23, 0.15);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from {
                transform: translate(-10%, -10%);
            }

            to {
                transform: translate(10%, 10%);
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: var(--glass-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: transparent;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
            border: none;
            padding: 0;
            overflow: hidden;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.15);
            border-radius: 20px;
        }

        .brand-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .brand-accent {
            color: var(--brand-accent);
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            padding-left: 44px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .input-field:focus {
            outline: none;
            border: 2px solid #d97706;
            background: #fff;
            box-shadow: none;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 18px;
        }

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fecaca;
            display:
                <?php echo $error ? 'block' : 'none'; ?>
            ;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: #FDD017;
            color: #0f172a;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 14px rgba(253, 208, 23, 0.4);
        }

        .login-btn:hover {
            background: #eab308;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(253, 208, 23, 0.5);
        }

        .show-pass-container {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .show-pass-container label {
            font-size: 13px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="blob"></div>

    <div class="login-container">
    
    
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-container"><img src="img/logo.png" alt="Sunder Machines Logo"></div>
                <h1 class="brand-name"><span class="brand-accent">Sunder</span> Billing</h1>
                <p class="subtitle">Please enter your credentials</p>
            </div>

            <div class="error-message">
                <?php echo $error; ?>
            </div>

            <div style="margin-bottom: 20px; text-align: center; color: var(--text-muted); font-size: 14px;">
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username / Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" name="username" class="input-field" placeholder="Username or Email" required
                            autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="input-field" placeholder="Password"
                            required>
                    </div>
                    <div class="show-pass-container">
                        <input type="checkbox" id="showPass" onclick="togglePass()">
                        <label for="showPass">Show Password</label>
                    </div>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <script>
                function togglePass() {
                    var x = document.getElementById("password");
                    x.type = x.type === "password" ? "text" : "password";
                }
            </script>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> Sanruth Softtech
            </div>
        </div>
    </div>
</body>

</html>