-- =============================================================================
-- Branch: origin/devlop_test  vs  origin/main
-- All database-facing DDL introduced on this branch (reviewable SQL only).
-- Do NOT run via artisan migrate. Apply manually after review (MySQL 8+).
-- =============================================================================
--
-- INVENTORY
-- ---------
-- A) Feature schema (columns) — currency + dual AR + password-reset tokens
-- B) Phase 1 performance indexes (ADD INDEX only)
-- C) Phase 6: duplicate of bookings.booking_number index (already in Phase 1)
-- D) Phase 7 queue tables (jobs / failed_jobs / job_batches) — only if queues enabled
-- E) Phase 2 / 3 / 4A — NO DDL (documented no-ops on this branch)
--
-- NOT on this branch vs main (already on main or app-only):
--   - agent_photos booking_* columns migration (already present on main)
--   - shipment workflows / unassign agent / Accept-Language / Firebase soft-fail
--     (no schema change)
--
-- Source files on the branch:
--   docs/sql/2026-10-16-multi-currency-invoicing.sql
--   docs/sql/2026-10-16-dual-currency-ledgers.sql
--   docs/sql/2026-10-16-reset-01-password-reset-tokens.sql
--   docs/modernization/phase-1-performance-indexes.sql
--   docs/modernization/phase-6-performance-indexes.sql
--   docs/modernization/phase-7-queue-infrastructure.sql
-- =============================================================================


-- #############################################################################
-- A) FEATURE SCHEMA
-- #############################################################################

-- A1) invoices.currency
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `invoice_number`',
  'SELECT ''invoices.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A2) booking_services currency fields
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

-- A3) agent_expenses currency fields
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

-- A4) companies.opening_balance_usd
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'opening_balance_usd'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `companies` ADD COLUMN `opening_balance_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `opening_balance`',
  'SELECT ''companies.opening_balance_usd already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A5) invoices.usd_amount
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'usd_amount'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoices` ADD COLUMN `usd_amount` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `currency`',
  'SELECT ''invoices.usd_amount already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A6) invoice_payments.currency
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_payments' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `invoice_payments` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `value`',
  'SELECT ''invoice_payments.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A7) bank_trnsactions.currency
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bank_trnsactions' AND COLUMN_NAME = 'currency'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `bank_trnsactions` ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT ''EGP'' AFTER `amount`',
  'SELECT ''bank_trnsactions.currency already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- A8) otps reset-token columns
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


-- #############################################################################
-- B) PHASE 1 PERFORMANCE INDEXES (safe re-run)
-- #############################################################################

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'money_transfers' AND INDEX_NAME = 'idx_money_transfers_transferer');
SET @sql := IF(@idx = 0, 'ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transferer` (`transferer_type`, `transferer_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_money_transfers_transferer already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'money_transfers' AND INDEX_NAME = 'idx_money_transfers_transfered');
SET @sql := IF(@idx = 0, 'ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transfered` (`transfered_type`, `transfered_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_money_transfers_transfered already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'money_transfers' AND INDEX_NAME = 'idx_money_transfers_delivery_policy');
SET @sql := IF(@idx = 0, 'ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_delivery_policy` (`delivery_policy_id`, `type`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_money_transfers_delivery_policy already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'images' AND INDEX_NAME = 'idx_images_imageable');
SET @sql := IF(@idx = 0, 'ALTER TABLE `images` ADD INDEX `idx_images_imageable` (`imageable_type`, `imageable_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_images_imageable already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_policy_containers' AND INDEX_NAME = 'idx_dpc_policy_container');
SET @sql := IF(@idx = 0, 'ALTER TABLE `delivery_policy_containers` ADD INDEX `idx_dpc_policy_container` (`delivery_policy_id`, `booking_container_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_dpc_policy_container already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_bookings_booking_number');
SET @sql := IF(@idx = 0, 'ALTER TABLE `bookings` ADD INDEX `idx_bookings_booking_number` (`booking_number`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_bookings_booking_number already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_services' AND INDEX_NAME = 'idx_booking_services_booking_id');
SET @sql := IF(@idx = 0, 'ALTER TABLE `booking_services` ADD INDEX `idx_booking_services_booking_id` (`booking_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_booking_services_booking_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_papers' AND INDEX_NAME = 'idx_booking_papers_container_type');
SET @sql := IF(@idx = 0, 'ALTER TABLE `booking_papers` ADD INDEX `idx_booking_papers_container_type` (`booking_container_id`, `type`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_booking_papers_container_type already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_expenses' AND INDEX_NAME = 'idx_agent_expenses_delivery_policy_id');
SET @sql := IF(@idx = 0, 'ALTER TABLE `agent_expenses` ADD INDEX `idx_agent_expenses_delivery_policy_id` (`delivery_policy_id`), ALGORITHM=INPLACE, LOCK=NONE', 'SELECT ''idx_agent_expenses_delivery_policy_id already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- #############################################################################
-- C) PHASE 6 — same bookings index as Phase 1 (already guarded above)
-- #############################################################################
SELECT 'Phase 6 index is idx_bookings_booking_number (covered in section B)' AS phase6_note;


-- #############################################################################
-- D) PHASE 7 QUEUE TABLES (apply only if async queue workers are used)
-- #############################################################################

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- E) PHASE 2 / 3 / 4A — no DDL on this branch
-- #############################################################################
SELECT 'Phase 2/3/4A: zero new indexes (documented no-ops)' AS phase_noop_note;
