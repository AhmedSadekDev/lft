-- Electronic invoices (ETA) admin page permission
-- Note: legacy `permissions.id` is NOT auto-increment — set id explicitly.
-- After running: php artisan permission:cache-reset

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`)
SELECT
    (SELECT IFNULL(MAX(id), 0) + 1 FROM `permissions` p),
    'e_invoices.index',
    'web',
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'e_invoices.index' AND `guard_name` = 'web'
);

SET @permission_e_invoices_index_id = (
    SELECT id FROM permissions WHERE name = 'e_invoices.index' AND guard_name = 'web' LIMIT 1
);

SET @admin_role_id = (
    SELECT id FROM roles WHERE name = 'Admin' AND guard_name = 'web' LIMIT 1
);

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT @permission_e_invoices_index_id, @admin_role_id
FROM DUAL
WHERE @permission_e_invoices_index_id IS NOT NULL
  AND @admin_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `role_has_permissions`
      WHERE permission_id = @permission_e_invoices_index_id
        AND role_id = @admin_role_id
  );
