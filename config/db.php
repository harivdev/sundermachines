<?php
date_default_timezone_set('Asia/Kolkata');

// Load environment configuration from .env file if it exists
if (!function_exists('load_erp_env')) {
    function load_erp_env($path = null)
    {
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if (preg_match('/^([\'"])(.*)\1$/', $val, $m)) {
                $val = $m[2];
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
        return true;
    }
}
load_erp_env();

// Database credentials for InfinityFree
$dbHost = getenv('DB_HOST') ?: 'sql209.infinityfree.com';
$dbPort = intval(getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'if0_42941836';
$dbPass = getenv('DB_PASS') ?: 'SunderMech123';
$dbName = getenv('DB_NAME') ?: 'if0_42941836_sunder';

// Disable default PHP 8 exception throwing for mysqli so errors can be handled cleanly
mysqli_report(MYSQLI_REPORT_OFF);

// Primary application DB
try {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
} catch (Throwable $e) {
    $conn = false;
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

@mysqli_query($conn, "SET time_zone = '+05:30'");

// Global UI Output Filter to convert UI display names to SUNDER MACHNES WORLD
if (!function_exists('sunder_ui_output_filter')) {
    function sunder_ui_output_filter($buffer)
    {
        static $map = [
        'SUNDER MACHINES WORLD' => 'SUNDER MACHNES WORLD',
        'Sunder Machines World' => 'SUNDER MACHNES WORLD',
        'sunder machines world' => 'SUNDER MACHNES WORLD',
        'SANRUTH MACHINES' => 'SUNDER MACHNES WORLD',
        'Sanruth Machines' => 'SUNDER MACHNES WORLD',
        'sanruth machines' => 'sunder machnes world',
        'Sanruth Softtech' => 'Sanruth Softtech',
        'SANRUTH SOFTTECH' => 'Sanruth Softtech',
        'sanruth softtech' => 'Sanruth Softtech',
        'owner@sunder.com' => 'owner@sunder.com',
        'SANRUTH' => 'SUNDER MACHNES WORLD',
        'Sanruth' => 'SUNDER MACHNES WORLD',
        'sanruth' => 'sunder machnes world'
        ];
        return strtr($buffer, $map);
    }

    if (php_sapi_name() !== 'cli' && (!defined('DISABLE_SUNDER_FILTER') || !DISABLE_SUNDER_FILTER)) {
        @ob_start('sunder_ui_output_filter');
    }
}

// ── Hide PHP errors from the browser (log to file instead) ───────────────────
// Users should NEVER see raw PHP Warning/Notice/Error messages on screen.
// All errors are written to php_errors.log in the project root for debugging.
if (php_sapi_name() !== 'cli') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');        // never show errors in browser
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');            // always log errors to file
    ini_set('error_log', __DIR__ . '/../php_errors.log');
}

// All data for this project is stored in sunder_billing only.
// $conn_login is aliased to $conn — no separate billing_login database.
$conn_login = $conn;

// ── Auto-migration: ensure stock table has reorderLevel, minQty & maxQty columns ──
// These columns are used by the dashboard, insert_stock, edit_stock, and reorder monitor.
// Safe to run on every request — only ALTERs if columns are missing.
$_migrationFlag = __DIR__ . '/.stock_cols_v2';
if (!file_exists($_migrationFlag)) {
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM stock LIKE 'reorderLevel'");
    if ($colCheck && mysqli_num_rows($colCheck) === 0) {
        @mysqli_query($conn, "ALTER TABLE stock ADD COLUMN reorderLevel int NOT NULL DEFAULT 0");
    }
    $colCheck2 = mysqli_query($conn, "SHOW COLUMNS FROM stock LIKE 'minQty'");
    if ($colCheck2 && mysqli_num_rows($colCheck2) === 0) {
        @mysqli_query($conn, "ALTER TABLE stock ADD COLUMN minQty int NOT NULL DEFAULT 0");
    }
    $colCheck3 = mysqli_query($conn, "SHOW COLUMNS FROM stock LIKE 'maxQty'");
    if ($colCheck3 && mysqli_num_rows($colCheck3) === 0) {
        @mysqli_query($conn, "ALTER TABLE stock ADD COLUMN maxQty int NOT NULL DEFAULT 0");
    }
    @file_put_contents($_migrationFlag, date('Y-m-d H:i:s'));
}

// ── Auto-migration: ensure employee table has email, username, password, role ──
$_empMigrationFlag = __DIR__ . '/.emp_cols_v1';
if (!file_exists($_empMigrationFlag)) {
    $eCheck = mysqli_query($conn, "SHOW COLUMNS FROM employee LIKE 'email'");
    if ($eCheck && mysqli_num_rows($eCheck) === 0) {
        @mysqli_query($conn, "ALTER TABLE employee 
            ADD COLUMN email varchar(255) DEFAULT NULL,
            ADD COLUMN username varchar(255) DEFAULT NULL,
            ADD COLUMN password varchar(255) DEFAULT NULL,
            ADD COLUMN role varchar(255) DEFAULT 'STAFF'
        ");
    }
    @file_put_contents($_empMigrationFlag, date('Y-m-d H:i:s'));
}

// ── Auto-migration: standardize empId to single pattern EMP0001 ──
$_empIdMigrationFlag = __DIR__ . '/.empid_pattern_v1';
if (!file_exists($_empIdMigrationFlag)) {
    $allEmpRes = @mysqli_query($conn, "SELECT id, empId FROM employee");
    if ($allEmpRes) {
        while ($empRow = mysqli_fetch_assoc($allEmpRes)) {
            $eId = intval($empRow['id']);
            $currEmpId = trim($empRow['empId'] ?? '');
            $num = $eId;
            if (preg_match('/\d+/', $currEmpId, $m)) {
                $num = intval($m[0]);
            }
            $standardEmpId = 'EMP' . str_pad($num, 4, '0', STR_PAD_LEFT);
            if ($currEmpId !== $standardEmpId) {
                @mysqli_query($conn, "UPDATE employee SET empId = '$standardEmpId' WHERE id = $eId");
            }
        }
        @mysqli_query($conn, "UPDATE employee SET designation = NULL WHERE designation IS NOT NULL");
    }
    @file_put_contents($_empIdMigrationFlag, date('Y-m-d H:i:s'));
}
?>