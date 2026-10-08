<?php
require_once __DIR__ . "/config/db.php";

echo "--- FROM USER TABLE ---\n";
$res = $conn_login->query("SELECT * FROM user WHERE UPPER(role) = 'ADMIN'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn_login->error . "\n";
}

echo "--- FROM EMPLOYEE TABLE ---\n";
$res2 = $conn->query("SELECT * FROM employee WHERE UPPER(role) = 'ADMIN'");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "--- FROM EMPLOYEE_AUTH TABLE ---\n";
$res3 = $conn->query("SELECT * FROM employee_auth WHERE UPPER(role) = 'ADMIN'");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "--- FROM CREDENTIAL TABLE ---\n";
$res4 = $conn->query("SELECT * FROM credential WHERE UPPER(role) = 'ADMIN'");
if ($res4) {
    while ($row = $res4->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
