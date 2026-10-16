-- Dual-currency company AR (EGP + USD ledgers)
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).

-- companies.opening_balance_usd: USD opening balance (opening_balance stays EGP)
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'opening_balance_usd'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `companies` ADD COLUMN `opening_balance_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `opening_balance`',
  'SELECT ''companies.opening_balance_usd already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- invoices.usd_amount: USD lines on an EGP/hybrid invoice (0 for pure-USD invoices)
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'usd_amount'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `usd_amount` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `currency`',
  'SELECT ''invoices.usd_amount already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- invoice_payments.currency: payment settles only that currency bucket
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_payments' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoice_payments` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `value`',
  'SELECT ''invoice_payments.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- bank_trnsactions.currency: tag bank movement; USD must not mix into banks.amount (EGP)
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_trnsactions' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `bank_trnsactions` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `amount`',
  'SELECT ''bank_trnsactions.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
