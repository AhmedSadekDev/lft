-- invoice_payments.section — allocate payment to tax/receipt/additional/usd
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_payments' AND COLUMN_NAME = 'section'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoice_payments` ADD COLUMN `section` VARCHAR(20) NULL DEFAULT NULL COMMENT ''tax | receipt | additional | usd'' AFTER `invoice_id`',
  'SELECT ''invoice_payments.section already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_payments' AND INDEX_NAME = 'idx_invoice_payments_invoice_section'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `invoice_payments` ADD INDEX `idx_invoice_payments_invoice_section` (`invoice_id`, `section`)',
  'SELECT ''idx_invoice_payments_invoice_section already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
