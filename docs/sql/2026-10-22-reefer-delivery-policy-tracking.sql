-- Reefer outbound v1: delivery_policies.custody_usd + reefer_trip_legs
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after owner review. Safe to re-run (MySQL 8+ information_schema guards).
--
-- custody_usd = USD amount recorded on the policy only (NOT a wallet debit / settlement).
-- reefer_trip_legs = 11 fixed stages for reefer land trips linked to delivery_policies.

-- ---------------------------------------------------------------------------
-- Preflight (read-only checks — review output before applying ALTER/CREATE)
-- ---------------------------------------------------------------------------
SELECT
  (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_policies') AS delivery_policies_exists,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_policies' AND COLUMN_NAME = 'custody_usd') AS custody_usd_exists,
  (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reefer_trip_legs') AS reefer_trip_legs_exists;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_policies' AND COLUMN_NAME = 'custody_usd'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `delivery_policies` ADD COLUMN `custody_usd` DECIMAL(14,2) NULL DEFAULT NULL COMMENT ''USD custody record only — not wallet debit'' AFTER `office_commission`',
  'SELECT ''delivery_policies.custody_usd already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @tbl := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reefer_trip_legs'
);
SET @sql := IF(@tbl = 0,
  'CREATE TABLE `reefer_trip_legs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delivery_policy_id` BIGINT UNSIGNED NOT NULL,
    `booking_container_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `stage` TINYINT UNSIGNED NOT NULL COMMENT ''1-10 timed stages; 11 notes-only'',
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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT ''reefer_trip_legs already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
