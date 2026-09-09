<?php
/**
 * Sanruth ERP — WhatsApp Log Database Schema Setup & Migration Tool
 * Location: whatsapp/setup_schema.php
 */

require_once(__DIR__ . '/../config/db.php');

header('Content-Type: text/plain; charset=utf-8');
echo "=== Sanruth ERP WhatsApp Schema Migration ===\n\n";

$sql = "CREATE TABLE IF NOT EXISTS `whatsapp_notification_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_code` VARCHAR(50) NOT NULL,
  `document_id` INT NULL,
  `document_number` VARCHAR(100) NULL,
  `recipient_phone` VARCHAR(30) NOT NULL,
  `template_name` VARCHAR(100) NOT NULL,
  `parameters_json` TEXT NULL,
  `status` ENUM('SENT', 'FAILED', 'SKIPPED') NOT NULL DEFAULT 'SENT',
  `whatsapp_message_id` VARCHAR(100) NULL,
  `error_message` TEXT NULL,
  `attempt_count` INT NOT NULL DEFAULT 1,
  `idempotency_key` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME NULL,
  `last_attempt_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_idempotency` (`idempotency_key`),
  KEY `idx_event_code` (`event_code`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table `whatsapp_notification_log` created/verified successfully.\n";
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

// Add columns minQty, maxQty, reorderLevel to stock table if missing
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM `stock` LIKE 'reorderLevel'");
if ($checkCol && mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conn, "ALTER TABLE `stock` ADD COLUMN `minQty` INT DEFAULT 0, ADD COLUMN `maxQty` INT DEFAULT 0, ADD COLUMN `reorderLevel` INT DEFAULT 0");
    echo "✅ Added `minQty`, `maxQty`, `reorderLevel` columns to `stock` table.\n";
} else {
    echo "✅ Stock reorder columns verified.\n";
}

echo "\nMigration Complete!\n";
