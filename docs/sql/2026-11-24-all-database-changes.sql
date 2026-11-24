-- =============================================================================
-- ALL database changes (leader_international / recent features)
-- Reviewable SQL only — do NOT run via artisan migrate.
--
-- No information_schema (shared hosts often deny it — e.g. cloudtal).
-- How to apply in phpMyAdmin:
--   1) Run section by section if needed
--   2) #1060 Duplicate column name  → column already exists → ignore & continue
--   3) #1061 Duplicate key name     → index already exists → ignore & continue
-- After permissions sections: php artisan permission:cache-reset
--
-- Sources merged:
--   2026-10-16 multi-currency / dual AR / password-reset tokens
--   2026-10-18 booking_containers currency / invoice_payments.section
--   2026-10-22 drivers ID/passport / reefer custody_usd + trip legs /
--              bookings.public_code / agent_expenses deferred review
--   2026-11-07 e_invoices permission
--   2026-11-15 invoice + company account adjustments
--   2026-11-22 USD vault + follow-ups + reefer trips module
--   Phase 1 performance indexes + Phase 7 queue tables
-- =============================================================================


-- #############################################################################
-- 1) MULTI-CURRENCY / DUAL AR
-- #############################################################################

ALTER TABLE `invoices`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `invoice_number`;

ALTER TABLE `booking_services`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `price`,
    ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
    ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`;

ALTER TABLE `agent_expenses`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `value`,
    ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
    ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`;

ALTER TABLE `companies`
    ADD COLUMN `opening_balance_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `opening_balance`;

ALTER TABLE `invoices`
    ADD COLUMN `usd_amount` DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `currency`;

ALTER TABLE `invoice_payments`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `value`;

ALTER TABLE `bank_trnsactions`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `amount`;

ALTER TABLE `booking_containers`
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP' AFTER `price`,
    ADD COLUMN `foreign_amount` DECIMAL(14,2) NULL AFTER `currency`,
    ADD COLUMN `exchange_rate` DECIMAL(14,6) NULL AFTER `foreign_amount`;

ALTER TABLE `invoice_payments`
    ADD COLUMN `section` VARCHAR(20) NULL DEFAULT NULL COMMENT 'tax | receipt | additional | usd' AFTER `invoice_id`;

ALTER TABLE `invoice_payments`
    ADD INDEX `idx_invoice_payments_invoice_section` (`invoice_id`, `section`);


-- #############################################################################
-- 2) PASSWORD RESET TOKENS (otps)
-- #############################################################################

ALTER TABLE `otps`
    ADD COLUMN `reset_token_hash` CHAR(64) NULL AFTER `expire_at`,
    ADD COLUMN `reset_token_expires_at` TIMESTAMP NULL AFTER `reset_token_hash`,
    ADD COLUMN `reset_token_used_at` TIMESTAMP NULL AFTER `reset_token_expires_at`;

ALTER TABLE `otps`
    ADD INDEX `otps_reset_token_hash_index` (`reset_token_hash`);


-- #############################################################################
-- 3) DRIVERS IDENTITY
-- #############################################################################

ALTER TABLE `drivers`
    ADD COLUMN `national_id` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Egyptian national ID (optional)' AFTER `phone`;

ALTER TABLE `drivers`
    ADD COLUMN `passport_number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Passport number (optional)' AFTER `national_id`;


-- #############################################################################
-- 4) REEFER OUTBOUND (agent app) — custody_usd + trip legs
-- #############################################################################

ALTER TABLE `delivery_policies`
    ADD COLUMN `custody_usd` DECIMAL(14,2) NULL DEFAULT NULL COMMENT 'USD custody record only — not wallet debit' AFTER `office_commission`;

CREATE TABLE IF NOT EXISTS `reefer_trip_legs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delivery_policy_id` BIGINT UNSIGNED NOT NULL,
    `booking_container_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `stage` TINYINT UNSIGNED NOT NULL COMMENT '1-10 timed stages; 11 notes-only',
    `occurred_at` DATETIME NULL DEFAULT NULL,
    `notes` TEXT NULL,
    `recorded_by_agent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reefer_trip_legs_policy_stage_unique` (`delivery_policy_id`, `stage`),
    KEY `reefer_trip_legs_policy_id_index` (`delivery_policy_id`),
    KEY `reefer_trip_legs_container_id_index` (`booking_container_id`),
    KEY `reefer_trip_legs_agent_id_index` (`recorded_by_agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- 5) BOOKINGS PUBLIC CODE + AGENT EXPENSE DEFERRED REVIEW
-- #############################################################################

ALTER TABLE `bookings`
    ADD COLUMN `public_code` CHAR(5) NULL DEFAULT NULL COMMENT 'Random 5-digit customer tracking code' AFTER `booking_number`;

ALTER TABLE `bookings`
    ADD UNIQUE INDEX `uq_bookings_public_code` (`public_code`);

ALTER TABLE `agent_expenses`
    ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Dashboard review timestamp' AFTER `admin_approval`;

ALTER TABLE `agent_expenses`
    ADD COLUMN `wallet_debited_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When agent wallet was decremented' AFTER `reviewed_at`;


-- #############################################################################
-- 6) INVOICE COMMERCIAL ADJUSTMENTS + COMPANY ACCOUNT NOTES
-- #############################################################################

ALTER TABLE `invoices`
    ADD COLUMN `adjustment_type` VARCHAR(20) NOT NULL DEFAULT 'discount' COMMENT 'discount or addition on grand total' AFTER `discount`;

ALTER TABLE `invoices`
    ADD COLUMN `adjustment_reason` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Reason for commercial discount/addition' AFTER `adjustment_type`;

CREATE TABLE IF NOT EXISTS `company_account_adjustments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL COMMENT 'discount reduces AR; addition increases AR',
    `amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EGP',
    `reason` VARCHAR(500) NULL DEFAULT NULL,
    `note_number` VARCHAR(50) NULL DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_caa_company_created` (`company_id`, `created_at`),
    KEY `idx_caa_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- 7) USD VAULT + FOLLOW-UPS + DASHBOARD REEFER MODULE (2026-11-22)
-- #############################################################################

ALTER TABLE `agents`
    ADD COLUMN `wallet_usd` DECIMAL(14,2) NOT NULL DEFAULT 0
    COMMENT 'USD custody balance (dashboard)'
    AFTER `wallet`;

ALTER TABLE `banks`
    ADD COLUMN `amount_usd` DECIMAL(14,2) NOT NULL DEFAULT 0
    COMMENT 'USD bank book balance'
    AFTER `amount`;

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


-- #############################################################################
-- 8) PERFORMANCE INDEXES (Phase 1)
-- #############################################################################

ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transferer` (`transferer_type`, `transferer_id`);
ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transfered` (`transfered_type`, `transfered_id`);
ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_delivery_policy` (`delivery_policy_id`, `type`);
ALTER TABLE `images` ADD INDEX `idx_images_imageable` (`imageable_type`, `imageable_id`);
ALTER TABLE `delivery_policy_containers` ADD INDEX `idx_dpc_policy_container` (`delivery_policy_id`, `booking_container_id`);
ALTER TABLE `bookings` ADD INDEX `idx_bookings_booking_number` (`booking_number`);
ALTER TABLE `booking_services` ADD INDEX `idx_booking_services_booking_id` (`booking_id`);
ALTER TABLE `booking_papers` ADD INDEX `idx_booking_papers_container_type` (`booking_container_id`, `type`);
ALTER TABLE `agent_expenses` ADD INDEX `idx_agent_expenses_delivery_policy_id` (`delivery_policy_id`);


-- #############################################################################
-- 9) QUEUE TABLES (Phase 7 — only if async workers are used)
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
-- 10) PERMISSIONS
-- #############################################################################

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'e_invoices.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'e_invoices.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'usd_vault.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'usd_vault.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'usd_vault.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'usd_vault.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'follow_ups.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'follow_ups.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'follow_ups.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'follow_ups.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'reefer_trips.index', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'reefer_trips.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'reefer_trips.manage', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'reefer_trips.manage' AND `guard_name` = 'web');

SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Admin' AND guard_name = 'web' LIMIT 1);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @admin_role_id
FROM `permissions` p
WHERE p.guard_name = 'web'
  AND p.name IN (
      'e_invoices.index',
      'usd_vault.index', 'usd_vault.manage',
      'follow_ups.index', 'follow_ups.manage',
      'reefer_trips.index', 'reefer_trips.manage'
  )
  AND @admin_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `role_has_permissions` rh
      WHERE rh.permission_id = p.id AND rh.role_id = @admin_role_id
  );


-- #############################################################################
-- 11) PREFLIGHT (app tables only)
-- #############################################################################

SELECT `currency` FROM `invoices` LIMIT 1;
SELECT `opening_balance_usd` FROM `companies` LIMIT 1;
SELECT `custody_usd` FROM `delivery_policies` LIMIT 1;
SELECT `wallet_usd` FROM `agents` LIMIT 1;
SELECT `amount_usd` FROM `banks` LIMIT 1;
SELECT COUNT(*) AS reefer_trip_legs_rows FROM `reefer_trip_legs`;
SELECT COUNT(*) AS usd_vaults_rows FROM `usd_vaults`;
SELECT COUNT(*) AS container_follow_ups_rows FROM `container_follow_ups`;
SELECT COUNT(*) AS reefer_trips_rows FROM `reefer_trips`;
SELECT COUNT(*) AS company_account_adjustments_rows FROM `company_account_adjustments`;
SELECT `name` FROM `permissions`
WHERE `name` IN (
    'e_invoices.index',
    'usd_vault.index', 'usd_vault.manage',
    'follow_ups.index', 'follow_ups.manage',
    'reefer_trips.index', 'reefer_trips.manage'
);
