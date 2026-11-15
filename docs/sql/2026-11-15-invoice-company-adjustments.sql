-- =============================================================================
-- Invoice commercial adjustments (discount/addition) + company account notes
-- Reviewable SQL only — do NOT run via php artisan migrate.
-- Safe to re-run (information_schema guards). MySQL 8+.
-- =============================================================================

-- invoices.adjustment_type: discount | addition (absolute amount stays in invoices.discount)
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'adjustment_type'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `adjustment_type` VARCHAR(20) NOT NULL DEFAULT ''discount'' COMMENT ''discount or addition on grand total'' AFTER `discount`',
  'SELECT ''invoices.adjustment_type already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- invoices.adjustment_reason
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'adjustment_reason'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `adjustment_reason` VARCHAR(500) NULL DEFAULT NULL COMMENT ''Reason for commercial discount/addition'' AFTER `adjustment_type`',
  'SELECT ''invoices.adjustment_reason already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Company-level credit/debit notes (not tied to a specific invoice)
SET @tbl := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_account_adjustments'
);
SET @sql := IF(@tbl = 0,
  'CREATE TABLE `company_account_adjustments` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `company_id` BIGINT UNSIGNED NOT NULL,
      `type` VARCHAR(20) NOT NULL COMMENT ''discount reduces AR; addition increases AR'',
      `amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
      `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'',
      `reason` VARCHAR(500) NULL DEFAULT NULL,
      `note_number` VARCHAR(50) NULL DEFAULT NULL,
      `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
      `created_at` TIMESTAMP NULL DEFAULT NULL,
      `updated_at` TIMESTAMP NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_caa_company_created` (`company_id`, `created_at`),
      KEY `idx_caa_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT ''company_account_adjustments already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
