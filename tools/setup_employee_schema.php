<?php
require_once __DIR__ . '/../config/db.php';

echo "Setting up Employee database schema...\n";

// 1. Ensure employee table exists with base columns
$sqlCreate = "CREATE TABLE IF NOT EXISTS `employee` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `empId` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phoneNo1` varchar(50) DEFAULT NULL,
  `phoneNo2` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `line1` varchar(255) DEFAULT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zipCode` varchar(50) DEFAULT NULL,
  `employmentType` varchar(100) DEFAULT 'Full-Time',
  `designation` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'STAFF',
  `joinedDate` date DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `createdBy` varchar(100) DEFAULT NULL,
  `createdOn` datetime DEFAULT NULL,
  `modifiedBy` varchar(100) DEFAULT NULL,
  `modifiedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sqlCreate)) {
    echo "Employee table created or verified.\n";
} else {
    echo "Error creating employee table: " . mysqli_error($conn) . "\n";
}

// 2. Add columns if table already existed without them
$columnsToAdd = [
    'empId' => "VARCHAR(100) DEFAULT NULL",
    'phoneNo1' => "VARCHAR(50) DEFAULT NULL",
    'phoneNo2' => "VARCHAR(50) DEFAULT NULL",
    'email' => "VARCHAR(255) DEFAULT NULL",
    'line1' => "VARCHAR(255) DEFAULT NULL",
    'line2' => "VARCHAR(255) DEFAULT NULL",
    'city' => "VARCHAR(100) DEFAULT NULL",
    'zipCode' => "VARCHAR(50) DEFAULT NULL",
    'employmentType' => "VARCHAR(100) DEFAULT 'Full-Time'",
    'designation' => "VARCHAR(100) DEFAULT NULL",
    'username' => "VARCHAR(100) DEFAULT NULL",
    'password' => "VARCHAR(255) DEFAULT NULL",
    'role' => "VARCHAR(50) DEFAULT 'STAFF'",
    'joinedDate' => "DATE DEFAULT NULL",
    'dob' => "DATE DEFAULT NULL",
    'gender' => "VARCHAR(20) DEFAULT NULL",
    'active' => "TINYINT(1) NOT NULL DEFAULT '1'",
    'createdBy' => "VARCHAR(100) DEFAULT NULL",
    'createdOn' => "DATETIME DEFAULT NULL",
    'modifiedBy' => "VARCHAR(100) DEFAULT NULL",
    'modifiedOn' => "DATETIME DEFAULT NULL"
];

$existingColsRes = mysqli_query($conn, "SHOW COLUMNS FROM `employee`");
$existingCols = [];
if ($existingColsRes) {
    while ($r = mysqli_fetch_assoc($existingColsRes)) {
        $existingCols[] = strtolower($r['Field']);
    }
}

foreach ($columnsToAdd as $colName => $colDef) {
    if (!in_array(strtolower($colName), $existingCols)) {
        $alterSql = "ALTER TABLE `employee` ADD COLUMN `$colName` $colDef";
        if (mysqli_query($conn, $alterSql)) {
            echo "Added column `$colName` to `employee`.\n";
        } else {
            echo "Error adding column `$colName`: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "Employee schema setup finished successfully.\n";
