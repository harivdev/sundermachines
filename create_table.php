<?php
require_once(__DIR__ . "/config/db.php");

$sql = "
CREATE TABLE IF NOT EXISTS `daily_opening_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `entered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_query($conn, $sql)) {
    echo "Table daily_opening_balance created successfully.";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
