<?php
date_default_timezone_set('Asia/Kolkata');

// Primary application DB
$conn = mysqli_connect("localhost", "root", "Mysql@12345", "billing", 3306);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

@mysqli_query($conn, "SET time_zone = '+05:30'");

// Global UI Output Filter to convert UI display names to SUNDER MACHINES WORKS
if (!function_exists('sunder_ui_output_filter')) {
    function sunder_ui_output_filter($buffer) {
        static $map = [
            'SANRUTH MACHINES' => 'SUNDER MACHINES WORKS',
            'Sanruth Machines' => 'Sunder Machines Works',
            'sanruth machines' => 'sunder machines works',
            'Sanruth Softtech' => 'Sanruth Softtech',
            'SANRUTH SOFTTECH' => 'Sanruth Softtech',
            'sanruth softtech' => 'Sanruth Softtech',
            'owner@sunder.com' => 'owner@sunder.com',
            'SANRUTH' => 'SUNDER MACHINES WORKS',
            'Sanruth' => 'Sunder Machines Works',
            'sanruth' => 'sunder machines works'
        ];
        return strtr($buffer, $map);
    }

    if (php_sapi_name() !== 'cli' && (!defined('DISABLE_SUNDER_FILTER') || !DISABLE_SUNDER_FILTER)) {
        @ob_start('sunder_ui_output_filter');
    }
}





// Separate connection for login DB if present; fall back to main connection
$conn_login = @mysqli_connect("localhost", "root", "", "billing_login", 3306);
if (!$conn_login) {
    $conn_login = $conn;
}

// Auto-run one-time DB cleanup for fresh installation state
$flagFile = __DIR__ . '/.db_cleaned_v2';
if (!file_exists($flagFile)) {
    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    @mysqli_query($conn, "TRUNCATE TABLE jobcarditems");
    @mysqli_query($conn, "TRUNCATE TABLE jobcard");
    @mysqli_query($conn, "TRUNCATE TABLE salesitems");
    @mysqli_query($conn, "TRUNCATE TABLE sales");
    @mysqli_query($conn, "TRUNCATE TABLE purchaseitems");
    @mysqli_query($conn, "TRUNCATE TABLE purchase");
    @mysqli_query($conn, "DELETE FROM address WHERE id IN (SELECT address FROM customer WHERE address IS NOT NULL)");
    @mysqli_query($conn, "TRUNCATE TABLE customer");
    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    @file_put_contents($flagFile, date('Y-m-d H:i:s'));
}
?>