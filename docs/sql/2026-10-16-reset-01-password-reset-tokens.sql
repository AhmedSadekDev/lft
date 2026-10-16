-- RESET-01: temporary password-reset tokens (P0)
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).
--
-- Flow: Send OTP → Verify OTP (returns reset_token once) → Reset Password (requires reset_token).
-- Backend stores SHA-256(reset_token) only. Plain token is never persisted.
--
-- Frontend contract (Vue / mobile — not in this repo):
--   1. After successful verify-otp / verifyOtp, keep data.reset_token in memory only.
--   2. Send it with reset-password / resetPassword as `reset_token`.
--   3. Do NOT put reset_token in localStorage, sessionStorage, or the URL.
--   4. Requests with only email + password MUST be rejected by the API (legacy hole closed).

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

-- Lookup by hash for one-time consume (nullable; many OTP rows never issue a reset token)
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'otps' AND INDEX_NAME = 'otps_reset_token_hash_index'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `otps` ADD INDEX `otps_reset_token_hash_index` (`reset_token_hash`)',
  'SELECT ''otps_reset_token_hash_index already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
