-- =====================================================================================
-- Phase 1 (Performance-Only) - approved secondary indexes for database `leader`
-- =====================================================================================
-- Delivered as explicit SQL, deliberately NOT through the Laravel migration chain.
-- Only ADD INDEX statements. No PK / FK / UNIQUE / column / data change.
-- Every index below is tied to a real captured query, was proven on a scratch copy
-- (EXPLAIN before/after, Handler reads before/after) and was checked against all
-- existing indexes for duplication / left-prefix redundancy (0 findings).
-- Evidence: docs/modernization/evidence/phase-1-performance/index-lab.json,
--           docs/modernization/evidence/phase-1-performance/index-application.json
-- Full rationale per index: docs/modernization/phase-1-performance-report.md (section "Indexes")
--
-- ALGORITHM=INPLACE, LOCK=NONE: online secondary-index build; the statement fails instead of
-- blocking writes if the server cannot honour it.
--
-- Pre-check (must return 0 rows before applying):
--   SELECT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS
--   WHERE TABLE_SCHEMA = 'leader' AND INDEX_NAME IN (
--     'idx_money_transfers_transferer','idx_money_transfers_transfered','idx_money_transfers_delivery_policy',
--     'idx_images_imageable','idx_dpc_policy_container','idx_bookings_booking_number',
--     'idx_booking_services_booking_id','idx_booking_papers_container_type','idx_agent_expenses_delivery_policy_id');
-- =====================================================================================

-- 1. Agent custody / expenses: morph lookup `transferer_type = ? AND transferer_id = ?`
--    (GET /api/agent/fetch_all_expenses, fetch_latest_expenses, fetch_financial_custody)
ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transferer` (`transferer_type`, `transferer_id`), ALGORITHM=INPLACE, LOCK=NONE;

-- 2. Receiver side of the same morph: `transfered_type = ? [AND transfered_id = ?]`
--    (GET /dashboard/financial_custody_superagents)
ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_transfered` (`transfered_type`, `transfered_id`), ALGORITHM=INPLACE, LOCK=NONE;

-- 3. Delivery-policy transfers: `delivery_policy_id IN (...) AND type = ?` / `delivery_policy_id = ? AND type = ?`
--    (GET /api/agent/fetch_delivery_policies, POST delivery_policy_expenses, /dashboard/cars,
--     /dashboard/accounts/cars/financial-position)
ALTER TABLE `money_transfers` ADD INDEX `idx_money_transfers_delivery_policy` (`delivery_policy_id`, `type`), ALGORITHM=INPLACE, LOCK=NONE;

-- 4. Polymorphic images: `imageable_type = ? AND imageable_id IN (...) / = ?`
--    (GET /api/agent/fetch_delivery_policies, GET /api/booking/booking_papers)
ALTER TABLE `images` ADD INDEX `idx_images_imageable` (`imageable_type`, `imageable_id`), ALGORITHM=INPLACE, LOCK=NONE;

-- 5. Delivery-policy <-> container pivot, policy side (whereHas / eager load joins)
--    (GET /api/agent/fetch_delivery_policies, fetch_all_expenses, POST delivery_policy_expenses)
ALTER TABLE `delivery_policy_containers` ADD INDEX `idx_dpc_policy_container` (`delivery_policy_id`, `booking_container_id`), ALGORITHM=INPLACE, LOCK=NONE;

-- 6. Public tracking lookup `booking_number = ?` (non-unique on purpose: no constraint change)
--    (GET /api/booking/track, GET /api/booking/booking_papers)
ALTER TABLE `bookings` ADD INDEX `idx_bookings_booking_number` (`booking_number`), ALGORITHM=INPLACE, LOCK=NONE;

-- 7. Booking services per booking `booking_id = ? / IN (...)` (executed up to 495x per page)
--    (GET /dashboard/companies, /dashboard/accounts/financial-position, /dashboard/accounts/profit-loss)
ALTER TABLE `booking_services` ADD INDEX `idx_booking_services_booking_id` (`booking_id`), ALGORITHM=INPLACE, LOCK=NONE;

-- 8. Container papers by stage `booking_container_id = ? AND type IN (...)`
--    (GET /api/superagent/containers-expenses)
ALTER TABLE `booking_papers` ADD INDEX `idx_booking_papers_container_type` (`booking_container_id`, `type`), ALGORITHM=INPLACE, LOCK=NONE;

-- 9. Delivery-policy driver dues `delivery_policy_id = ? AND voided_at IS NULL`
--    (POST /api/agent/delivery_policy_expenses)
ALTER TABLE `agent_expenses` ADD INDEX `idx_agent_expenses_delivery_policy_id` (`delivery_policy_id`), ALGORITHM=INPLACE, LOCK=NONE;
