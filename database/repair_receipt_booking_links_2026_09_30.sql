-- Narrow repair ONLY for a missing booking_id when the receipt already has
-- a valid container and no dashboard-service/delivery-policy ownership.
-- Local audit on 2026-10-01 found ZERO such rows. This is not a fix for
-- missing images or receipts with an unknown container.
-- Never recreate receipts or debit wallets to repair display links.

START TRANSACTION;

SELECT e.id, e.agent_id, e.value, e.booking_id AS old_booking_id,
       c.booking_id AS proposed_booking_id, e.booking_container_id
FROM agent_expenses e
JOIN booking_containers c ON c.id = e.booking_container_id
JOIN bookings b ON b.id = c.booking_id
WHERE e.created_at >= '2026-09-30 00:00:00'
  AND e.created_at < '2026-10-02 00:00:00'
  AND e.booking_id IS NULL
  AND e.booking_service_id IS NULL
  AND e.delivery_policy_id IS NULL
  AND e.voided_at IS NULL
FOR UPDATE;

UPDATE agent_expenses e
JOIN booking_containers c ON c.id = e.booking_container_id
JOIN bookings b ON b.id = c.booking_id
SET e.booking_id = c.booking_id,
    e.version = COALESCE(e.version, 1) + 1,
    e.updated_at = CURRENT_TIMESTAMP
WHERE e.created_at >= '2026-09-30 00:00:00'
  AND e.created_at < '2026-10-02 00:00:00'
  AND e.booking_id IS NULL
  AND e.booking_service_id IS NULL
  AND e.delivery_policy_id IS NULL
  AND e.voided_at IS NULL;

SELECT ROW_COUNT() AS repaired_links;

-- Dry run by default. After reviewing the SELECT above, rerun this script
-- with COMMIT instead of ROLLBACK if the proposed links are the intended ones.
ROLLBACK;
