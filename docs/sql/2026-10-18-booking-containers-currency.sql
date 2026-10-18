-- booking_containers multi-currency (USD trip price like receipts)
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).
--
-- Semantics (same as InvoiceCurrency::normalizeLine):
--   currency = EGP → price is EGP AR; foreign_amount / exchange_rate null
--   currency = USD → foreign_amount is USD AR; price is optional internal EGP book when rate set

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_containers' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `booking_containers`
     ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `price`,
     ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
     ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`',
  'SELECT ''booking_containers.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
