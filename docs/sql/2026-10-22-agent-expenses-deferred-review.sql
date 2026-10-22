-- agent_expenses: deferred wallet debit until dashboard review
-- Reviewable SQL for the legacy database. Do NOT run via artisan migrate.
-- Apply manually after review. Safe to re-run (MySQL 8+ information_schema guards).
--
-- reviewed_at     — when dashboard confirmed the receipt
-- wallet_debited_at — when agents.wallet was actually decremented (null = hold only)
--
-- Backfill: rows already approved / dashboard booking-service receipts were debited at create
-- under the old code path — stamp wallet_debited_at so markReviewed never double-charges.

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_expenses' AND COLUMN_NAME = 'reviewed_at'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `agent_expenses` ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''Dashboard review timestamp'' AFTER `admin_approval`',
  'SELECT ''agent_expenses.reviewed_at already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_expenses' AND COLUMN_NAME = 'wallet_debited_at'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `agent_expenses` ADD COLUMN `wallet_debited_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''When agent wallet was decremented'' AFTER `reviewed_at`',
  'SELECT ''agent_expenses.wallet_debited_at already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Already-confirmed / dashboard receipts: treat historical create-time debit as done.
UPDATE `agent_expenses`
SET `wallet_debited_at` = COALESCE(`wallet_debited_at`, `created_at`),
    `reviewed_at` = COALESCE(`reviewed_at`, CASE WHEN `admin_approval` = 1 THEN `created_at` ELSE NULL END)
WHERE `wallet_debited_at` IS NULL
  AND (
    `admin_approval` = 1
    OR `booking_service_id` IS NOT NULL
  );
