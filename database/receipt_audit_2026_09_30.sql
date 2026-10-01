-- Read-only audit. Run against the server database before changing any receipts.
-- These dates are explicit; do not substitute CURRENT_DATE on a later day.
SET @receipt_from = '2026-09-30 00:00:00';
SET @receipt_to = '2026-10-02 00:00:00';

-- All receipts in the incident window, including voided ones and missing images.
SELECT e.id, e.created_at, a.name AS agent_name, e.agent_id,
       e.value, s.name AS service_name, e.notes,
       e.booking_id, b.booking_number, e.booking_container_id,
       c.container_no, c.booking_id AS container_booking_id,
       e.type_id, e.booking_service_id, e.delivery_policy_id,
       e.image_agent_expenses, e.voided_at,
       CASE
         WHEN e.voided_at IS NOT NULL THEN 'voided'
         WHEN e.booking_container_id IS NOT NULL AND c.id IS NULL THEN 'container_missing'
         WHEN c.id IS NOT NULL AND NOT (e.booking_id <=> c.booking_id) THEN 'booking_mismatch'
         WHEN e.booking_id IS NULL AND e.booking_container_id IS NULL THEN 'general_or_unlinked'
         WHEN e.booking_service_id IS NOT NULL THEN 'shown_as_booking_service'
         ELSE 'linked'
       END AS link_check
FROM agent_expenses e
LEFT JOIN agents a ON a.id = e.agent_id
LEFT JOIN services s ON s.id = e.service_id
LEFT JOIN bookings b ON b.id = e.booking_id
LEFT JOIN booking_containers c ON c.id = e.booking_container_id
WHERE e.created_at >= @receipt_from AND e.created_at < @receipt_to
ORDER BY e.id;

-- Actual unloading records entered on September 30, independent of images/approval.
SELECT e.agent_id, a.name, COUNT(*) AS receipt_count, SUM(e.value) AS total,
       SUM(e.image_agent_expenses IS NULL OR e.image_agent_expenses = '') AS without_image
FROM agent_expenses e
LEFT JOIN agents a ON a.id = e.agent_id
WHERE e.created_at >= '2026-09-30 00:00:00'
  AND e.created_at < '2026-10-01 00:00:00'
  AND e.type_id = 2 AND e.voided_at IS NULL
GROUP BY e.agent_id, a.name;

-- Match the container/stage query used by the manager application.
-- Local example: 5 receipts, total 1666, container 746, booking ZIMUDMT80039261.
SELECT e.id, b.booking_number, e.booking_container_id, e.type_id,
       s.name AS service_name, e.value, e.image_agent_expenses
FROM agent_expenses e
JOIN bookings b ON b.id = e.booking_id
LEFT JOIN services s ON s.id = e.service_id
WHERE b.booking_number = 'ZIMUDMT80039261'
  AND e.type_id = 2 AND e.voided_at IS NULL
ORDER BY e.id;
