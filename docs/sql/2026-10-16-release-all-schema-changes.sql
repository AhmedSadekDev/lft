-- =============================================================================
-- Leader — schema changes for this release (2026-10-16)
-- =============================================================================
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review (MySQL 8+). Safe to re-run (information_schema guards).
--
-- Includes (in order):
--   1) Multi-currency invoicing (EGP + USD line fields)
--   2) Dual-currency company ledgers (separate EGP / USD AR)
--   3) RESET-01 password-reset tokens on otps
--
-- Source files (same content, split):
--   docs/sql/2026-10-16-multi-currency-invoicing.sql
--   docs/sql/2026-10-16-dual-currency-ledgers.sql
--   docs/sql/2026-10-16-reset-01-password-reset-tokens.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Multi-currency / hybrid invoicing
-- -----------------------------------------------------------------------------

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

-- -----------------------------------------------------------------------------
-- 2) Dual-currency company AR (EGP + USD ledgers)
-- -----------------------------------------------------------------------------

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

-- -----------------------------------------------------------------------------
-- 3) RESET-01: temporary password-reset tokens on otps
-- -----------------------------------------------------------------------------

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'otps' AND COLUMN_NAME = 'reset_token_hash'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `otps`
     ADD COLUMN `reset_token_hash` CHAR(64) NULL AFTER `expire_at`,
     ADD COLUMN `reset_token_expires_at` TIMESTAMP NULL AFTER `reset_token_hash`,
     ADD COLUMN `reset_token_used_at` TIMESTAMP NULL AFTER `reset_token_expires_at`',
  'SELECT ''otps.reset_token_* already exist'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'otps' AND INDEX_NAME = 'otps_reset_token_hash_index'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `otps` ADD INDEX `otps_reset_token_hash_index` (`reset_token_hash`)',
  'SELECT ''otps_reset_token_hash_index already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
