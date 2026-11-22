-- =============================================================================
-- USD wallets + follow-up log + reefer module (dashboard)
-- Reviewable SQL only — do NOT run via artisan migrate.
-- Re-runnable via information_schema guards.
-- After permissions: php artisan permission:cache-reset
-- =============================================================================

-- ---------------------------------------------------------------------------
-- A) Agent USD custody balance
-- ---------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'agents'
      AND COLUMN_NAME = 'wallet_usd'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `agents` ADD COLUMN `wallet_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT ''USD custody balance (dashboard)'' AFTER `wallet`',
    'SELECT ''agents.wallet_usd already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- B) Bank USD book balance (isolated from banks.amount EGP)
-- ---------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'banks'
      AND COLUMN_NAME = 'amount_usd'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `banks` ADD COLUMN `amount_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT ''USD bank book balance'' AFTER `amount`',
    'SELECT ''banks.amount_usd already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- C) USD vault + ledger
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usd_vaults` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL DEFAULT 'خزنة الدولار',
    `opening_balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usd_vault_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usd_vault_id` BIGINT UNSIGNED NOT NULL,
    `direction` VARCHAR(8) NOT NULL COMMENT 'in|out',
    `category` VARCHAR(40) NOT NULL,
    `amount` DECIMAL(14,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `occurred_at` DATETIME NOT NULL,
    `reference` VARCHAR(191) NULL DEFAULT NULL,
    `description` TEXT NULL,
    `agent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `bank_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `delivery_policy_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reversal_of_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `idempotency_key` VARCHAR(64) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `usd_vault_tx_idempotency_unique` (`idempotency_key`),
    KEY `usd_vault_tx_vault_idx` (`usd_vault_id`),
    KEY `usd_vault_tx_agent_idx` (`agent_id`),
    KEY `usd_vault_tx_occurred_idx` (`occurred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usd_vaults` (`name`, `opening_balance`, `balance`, `is_active`, `created_at`, `updated_at`)
SELECT 'خزنة الدولار', 0.00, 0.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `usd_vaults` LIMIT 1);

-- ---------------------------------------------------------------------------
-- D) Container / trip follow-up status log (dashboard)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `container_follow_ups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_container_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `delivery_policy_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reefer_trip_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status_text` VARCHAR(255) NOT NULL,
    `occurred_at` DATETIME NOT NULL,
    `recorded_at` DATETIME NOT NULL,
    `notes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `cfu_container_idx` (`booking_container_id`),
    KEY `cfu_policy_idx` (`delivery_policy_id`),
    KEY `cfu_reefer_trip_idx` (`reefer_trip_id`),
    KEY `cfu_occurred_idx` (`occurred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- E) Reefer module (separate from booking_containers cycle)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reefer_shipments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permit_number` VARCHAR(191) NOT NULL COMMENT 'رقم إذن الشحن',
    `destination_country` VARCHAR(191) NULL DEFAULT NULL,
    `factory_name` VARCHAR(191) NULL DEFAULT NULL,
    `planned_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reefer_shipments_permit_idx` (`permit_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reefer_trips` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reefer_shipment_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `trip_date` DATE NULL DEFAULT NULL,
    `loading_site` VARCHAR(191) NULL DEFAULT NULL,
    `destination_country` VARCHAR(191) NULL DEFAULT NULL,
    `factory_name` VARCHAR(191) NULL DEFAULT NULL,
    `driver_name` VARCHAR(191) NULL DEFAULT NULL,
    `driver_passport` VARCHAR(191) NULL DEFAULT NULL,
    `driver_phone` VARCHAR(64) NULL DEFAULT NULL,
    `driver_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `car_number` VARCHAR(191) NULL DEFAULT NULL,
    `trailer_number` VARCHAR(191) NULL DEFAULT NULL,
    `car_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `foreign_trailer` VARCHAR(191) NULL DEFAULT NULL,
    `data_logger_1` VARCHAR(191) NULL DEFAULT NULL,
    `data_logger_2` VARCHAR(191) NULL DEFAULT NULL,
    `certificate_no` VARCHAR(191) NULL DEFAULT NULL,
    `bol_number` VARCHAR(191) NULL DEFAULT NULL,
    `custody_egp` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `custody_usd` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `agent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `delivery_policy_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `current_status` VARCHAR(255) NULL DEFAULT NULL,
    `exit_port_at` DATETIME NULL DEFAULT NULL,
    `departure_port` VARCHAR(191) NULL DEFAULT NULL,
    `arrive_departure_port_at` DATETIME NULL DEFAULT NULL,
    `ferry_booking_at` DATETIME NULL DEFAULT NULL,
    `ferry_boarding_at` DATETIME NULL DEFAULT NULL,
    `arrive_saudi_at` DATETIME NULL DEFAULT NULL,
    `exit_saudi_at` DATETIME NULL DEFAULT NULL,
    `arrive_final_customs_at` DATETIME NULL DEFAULT NULL,
    `exit_final_customs_at` DATETIME NULL DEFAULT NULL,
    `arrive_warehouse_at` DATETIME NULL DEFAULT NULL,
    `cleared_at` DATETIME NULL DEFAULT NULL,
    `notes` TEXT NULL,
    `disbursed_note` VARCHAR(191) NULL DEFAULT NULL,
    `invoice_number` VARCHAR(191) NULL DEFAULT NULL,
    `invoice_date` DATE NULL DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reefer_trips_shipment_idx` (`reefer_shipment_id`),
    KEY `reefer_trips_status_idx` (`current_status`),
    KEY `reefer_trips_date_idx` (`trip_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- F) Permissions
-- ---------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'usd_vault.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'usd_vault.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'usd_vault.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'usd_vault.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'follow_ups.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'follow_ups.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'follow_ups.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'follow_ups.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'reefer_trips.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'reefer_trips.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p), 'reefer_trips.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'reefer_trips.manage' AND `guard_name` = 'web');

SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Admin' AND guard_name = 'web' LIMIT 1);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @admin_role_id
FROM `permissions` p
WHERE p.guard_name = 'web'
  AND p.name IN (
      'usd_vault.index', 'usd_vault.manage',
      'follow_ups.index', 'follow_ups.manage',
      'reefer_trips.index', 'reefer_trips.manage'
  )
  AND @admin_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `role_has_permissions` rh
      WHERE rh.permission_id = p.id AND rh.role_id = @admin_role_id
  );

-- Preflight
SELECT 'agents.wallet_usd' AS col, COUNT(*) AS present
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agents' AND COLUMN_NAME = 'wallet_usd'
UNION ALL
SELECT 'banks.amount_usd', COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banks' AND COLUMN_NAME = 'amount_usd'
UNION ALL
SELECT 'usd_vaults', COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usd_vaults'
UNION ALL
SELECT 'container_follow_ups', COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'container_follow_ups'
UNION ALL
SELECT 'reefer_trips', COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reefer_trips';
