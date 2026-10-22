-- drivers.national_id + drivers.passport_number — optional identity fields
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'drivers' AND COLUMN_NAME = 'national_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `drivers` ADD COLUMN `national_id` VARCHAR(20) NULL DEFAULT NULL COMMENT ''Egyptian national ID (optional)'' AFTER `phone`',
  'SELECT ''drivers.national_id already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'drivers' AND COLUMN_NAME = 'passport_number'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `drivers` ADD COLUMN `passport_number` VARCHAR(50) NULL DEFAULT NULL COMMENT ''Passport number (optional)'' AFTER `national_id`',
  'SELECT ''drivers.passport_number already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
