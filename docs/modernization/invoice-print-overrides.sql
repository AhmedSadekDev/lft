-- Invoice-only print overrides (does not affect agent wallet / posted custody).
-- Reviewable DDL — do NOT run via php artisan migrate.
-- Apply manually on the operational legacy database after review.

-- Before:
-- SHOW COLUMNS FROM invoices LIKE 'print_overrides';

ALTER TABLE invoices
  ADD COLUMN print_overrides JSON NULL
  COMMENT 'Invoice-only line overrides; does not affect agent wallet'
  AFTER discount;

-- After:
-- SHOW COLUMNS FROM invoices LIKE 'print_overrides';
--
-- Expected JSON shape:
-- {
--   "agent_expenses": { "123": { "value": 50.00, "hidden": false } },
--   "booking_services": { "78": { "value": 100.00, "hidden": true } }
-- }
