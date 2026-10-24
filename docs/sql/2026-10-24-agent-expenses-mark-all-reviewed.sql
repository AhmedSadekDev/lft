-- Mark ALL existing agent app receipts as reviewed / settled (مستحقة).
-- Purpose: clear pending holds so spendable balance resets; new receipts from tomorrow
-- follow deferred-debit (hold until dashboard review).
--
-- Reviewable SQL. Do NOT run via artisan migrate.
-- Does NOT touch agents.wallet — assumes historical create already debited (old path)
-- or you accept treating current wallet as source of truth without a second debit.
--
-- Requires columns from docs/sql/2026-10-22-agent-expenses-deferred-review.sql
-- (reviewed_at, wallet_debited_at). Apply that file first if missing.
--
-- Safe to re-run: only updates rows still pending (admin_approval=0/NULL or wallet_debited_at NULL).

-- ---------------------------------------------------------------------------
-- 0) Preview (read-only) — how many rows will be marked
-- ---------------------------------------------------------------------------
SELECT
  COUNT(*) AS pending_rows,
  COALESCE(SUM(`value`), 0) AS pending_value_sum
FROM `agent_expenses`
WHERE `voided_at` IS NULL
  AND `delivery_policy_id` IS NULL
  AND (
    `wallet_debited_at` IS NULL
    OR `admin_approval` = 0
    OR `admin_approval` IS NULL
  );

-- ---------------------------------------------------------------------------
-- 1) Mark as reviewed + already-debited (no wallet decrement)
-- ---------------------------------------------------------------------------
UPDATE `agent_expenses`
SET
  `admin_approval` = 1,
  `reviewed_at` = COALESCE(`reviewed_at`, COALESCE(`created_at`, NOW())),
  `wallet_debited_at` = COALESCE(`wallet_debited_at`, COALESCE(`created_at`, NOW())),
  `updated_at` = NOW()
WHERE `voided_at` IS NULL
  AND `delivery_policy_id` IS NULL
  AND (
    `wallet_debited_at` IS NULL
    OR `admin_approval` = 0
    OR `admin_approval` IS NULL
  );

-- ---------------------------------------------------------------------------
-- 2) Verify — should be 0 pending holds for wallet
-- ---------------------------------------------------------------------------
SELECT
  COUNT(*) AS remaining_holds,
  COALESCE(SUM(`value`), 0) AS remaining_hold_value
FROM `agent_expenses`
WHERE `voided_at` IS NULL
  AND `delivery_policy_id` IS NULL
  AND `booking_service_id` IS NULL
  AND `wallet_debited_at` IS NULL;
