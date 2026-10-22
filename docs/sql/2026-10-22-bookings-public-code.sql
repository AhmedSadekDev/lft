-- bookings.public_code — random 5-digit customer tracking / invoice middle segment
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 5.7+ / 8+).
--
-- Customer WhatsApp/email track links and public /api/booking/track resolve by this code only.
-- Invoice format becomes: YYYY-{public_code}-### (yearly serial by invoice creation order).
-- booking_number remains the operational / shipping reference for admin.
--
-- If a previous run failed (#1062 / #1064): re-run this whole file.
-- It drops the unique index, rebuilds codes uniquely, then re-adds the index.

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'public_code'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `bookings` ADD COLUMN `public_code` CHAR(5) NULL DEFAULT NULL COMMENT ''Random 5-digit customer tracking code'' AFTER `booking_number`',
  'SELECT ''bookings.public_code already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop unique index so backfill can rewrite without #1062 mid-update.
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'uq_bookings_public_code'
);
SET @sql := IF(@idx > 0,
  'ALTER TABLE `bookings` DROP INDEX `uq_bookings_public_code`',
  'SELECT ''uq_bookings_public_code not present'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Unique-by-construction backfill (MySQL 5.7 compatible — no window functions).
-- Shuffle by MD5(id), assign 10000–99999 via AUTO_INCREMENT rank.
-- Requires fewer than 90_000 bookings (5-digit pool size).
DROP TEMPORARY TABLE IF EXISTS `tmp_booking_public_codes`;
CREATE TEMPORARY TABLE `tmp_booking_public_codes` (
  `rn` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`rn`),
  UNIQUE KEY `tmp_booking_public_codes_id` (`id`)
) ENGINE=InnoDB;

INSERT INTO `tmp_booking_public_codes` (`id`)
SELECT `id` FROM `bookings` ORDER BY MD5(CONCAT('leader-public-', `id`));

UPDATE `bookings` b
INNER JOIN `tmp_booking_public_codes` t ON b.`id` = t.`id`
SET b.`public_code` = LPAD(10000 + ((t.`rn` - 1) % 90000), 5, '0');

DROP TEMPORARY TABLE IF EXISTS `tmp_booking_public_codes`;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'uq_bookings_public_code'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `bookings` ADD UNIQUE INDEX `uq_bookings_public_code` (`public_code`)',
  'SELECT ''uq_bookings_public_code already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sanity check (should return 0 rows):
-- SELECT public_code, COUNT(*) c FROM bookings GROUP BY public_code HAVING c > 1;
