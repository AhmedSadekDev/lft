-- Clear permission cache manually by running:
-- php artisan permission:cache-reset
-- OR
-- php artisan cache:clear

-- Insert accounts permissions
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
VALUES
    ('accounts.index', 'web', NOW(), NOW()),
    ('accounts.create', 'web', NOW(), NOW()),
    ('accounts.update', 'web', NOW(), NOW()),
    ('accounts.delete', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Get the permission IDs
SET @permission_index_id = (SELECT id FROM permissions WHERE name = 'accounts.index' AND guard_name = 'web');
SET @permission_create_id = (SELECT id FROM permissions WHERE name = 'accounts.create' AND guard_name = 'web');
SET @permission_update_id = (SELECT id FROM permissions WHERE name = 'accounts.update' AND guard_name = 'web');
SET @permission_delete_id = (SELECT id FROM permissions WHERE name = 'accounts.delete' AND guard_name = 'web');

-- Get Admin role ID
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'Admin' AND guard_name = 'web');

-- Assign permissions to Admin role
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
VALUES
    (@permission_index_id, @admin_role_id),
    (@permission_create_id, @admin_role_id),
    (@permission_update_id, @admin_role_id),
    (@permission_delete_id, @admin_role_id)
ON DUPLICATE KEY UPDATE `permission_id` = `permission_id`;
