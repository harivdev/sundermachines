<?php
// Simple local debug helper to inspect the `user` table entry for a username.
// Usage: http://localhost/Sunder_billing_new/tools/check_admin_user.php?username=admin

require_once(dirname(__DIR__) . '/config/db.php');

header('Content-Type: text/plain; charset=utf-8');

echo "Checking DB connections...\n";
if (!$conn) {
    echo "Primary DB connection failed.\n";
    exit(1);
}

if (!$conn_login) {
    echo "Login DB connection not available; using primary DB.\n";
}

$username = trim($_GET['username'] ?? 'admin');
$safe = mysqli_real_escape_string($conn_login, $username);

echo "Looking for user: {$username}\n\n";

$res = @mysqli_query($conn_login, "SELECT id, username, password, role FROM `user` WHERE username = '$safe' LIMIT 1");
if (!$res) {
    echo "Query failed: " . mysqli_error($conn_login) . "\n";
    exit(1);
}

if (mysqli_num_rows($res) === 0) {
    echo "No matching user found.\n";
    exit(0);
}

$row = mysqli_fetch_assoc($res);
echo "User row:\n";
print_r($row);

// Quick guidance
if (isset($row['password'])) {
    $pw = $row['password'];
    if (strpos($pw, '$2y$') === 0 || strpos($pw, '$2a$') === 0) {
        echo "\nPassword looks bcrypt-hashed. Login needs password_verify().\n";
    } elseif (strlen($pw) > 30) {
        echo "\nPassword is long; might be hashed.\n";
    } else {
        echo "\nPassword appears plaintext. Use the exact password shown.\n";
    }
}

echo "\nIf you still see 'Invalid username/email or password', try username=admin and the shown password.\n";

?>