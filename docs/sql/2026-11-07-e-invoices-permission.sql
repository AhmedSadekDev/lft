-- Electronic invoices (ETA) admin page permission
-- After running: php artisan permission:cache-reset
-- OR: php artisan cache:clear

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
VALUES
    ('e_invoices.index', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

SET @permission_e_invoices_index_id = (
    SELECT id FROM permissions WHERE name = 'e_invoices.index' AND guard_name = 'web' LIMIT 1
);

-- Assign to Admin role (adjust role name if different in your DB)
SET @admin_role_id = (
    SELECT id FROM roles WHERE name = 'Admin' AND guard_name = 'web' LIMIT 1
);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT @permission_e_invoices_index_id, @admin_role_id
WHERE @permission_e_invoices_index_id IS NOT NULL
  AND @admin_role_id IS NOT NULL
ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;

-- Optional: assign to a specific user directly (model_has_permissions)
-- Replace 1 with the user id
-- INSERT INTO `model_has_permissions` (`permission_id`, `model_type`, `model_id`)
-- SELECT @permission_e_invoices_index_id, 'App\\Models\\User', 1
-- WHERE @permission_e_invoices_index_id IS NOT NULL
-- ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;
