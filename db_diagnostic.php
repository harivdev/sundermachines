<?php
// Simple diagnostic page to inspect MySQL and credential table availability
header('Content-Type: text/plain; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$port = 3306;

echo "DB Diagnostic\n=================\n";

$conn = @mysqli_connect($host, $user, $pass, null, $port);
if (!$conn) {
    echo "Cannot connect to MySQL on $host:$port — " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "Connected to MySQL server at $host:$port\n";

$dbs = [];
$res = mysqli_query($conn, "SHOW DATABASES");
if ($res) {
    while ($r = mysqli_fetch_row($res)) {
        $dbs[] = $r[0];
    }
}

echo "Databases found: " . count($dbs) . "\n";
foreach ($dbs as $d) {
    echo " - $d" . PHP_EOL;
}

$candidates = ['Sunder', 'billing', 'billing_login'];
echo "\nChecking candidate DBs for 'credential' table...\n";
foreach ($candidates as $db) {
    if (!in_array($db, $dbs)) {
        echo " - $db: NOT FOUND\n";
        continue;
    }
    if (!mysqli_select_db($conn, $db)) {
        echo " - $db: cannot select database\n";
        continue;
    }
    $q = mysqli_query($conn, "SHOW TABLES LIKE 'credential'");
    if ($q && mysqli_num_rows($q) > 0) {
        echo " - $db: credential table EXISTS\n";
        $c = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM credential");
        $cnt = $c ? mysqli_fetch_assoc($c)['cnt'] : 'unknown';
        echo "   rows in credential: $cnt\n";
        $sample = mysqli_query($conn, "SELECT username, role FROM credential LIMIT 5");
        if ($sample && mysqli_num_rows($sample) > 0) {
            echo "   sample rows:\n";
            while ($s = mysqli_fetch_assoc($sample)) {
                echo "     - " . ($s['username'] ?? '<null>') . " (role=" . ($s['role'] ?? 'NULL') . ")\n";
            }
        }
    } else {
        echo " - $db: credential table NOT FOUND\n";
    }
}

echo "\nNote: open this file in browser: /Sunder_billing_new/db_diagnostic.php\n";
?>