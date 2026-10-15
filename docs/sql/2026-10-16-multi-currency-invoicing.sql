-- Multi-currency / hybrid invoicing (EGP + USD)
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review on a maintenance window.
-- Safe to re-run: each statement checks information_schema first (MySQL 8+).

-- invoices.currency: EGP (default / hybrid) or USD (pure international invoice)
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `invoice_number`',
  'SELECT ''invoices.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- booking_services: original foreign amount + rate; `price` stays the EGP book amount
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_services' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `booking_services`
     ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `price`,
     ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
     ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`',
  'SELECT ''booking_services.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- agent_expenses: mirror fields when a dashboard receipt is linked to an agent
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_expenses' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `agent_expenses`
     ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `value`,
     ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
     ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`',
  'SELECT ''agent_expenses.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
