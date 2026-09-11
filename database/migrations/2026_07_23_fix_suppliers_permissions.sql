-- Fix: create missing Spatie permissions for Suppliers module
-- Run this on the production/local MySQL database

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('suppliers.index',  'web', NOW(), NOW()),
('suppliers.create', 'web', NOW(), NOW()),
('suppliers.udpate', 'web', NOW(), NOW()),
('suppliers.update', 'web', NOW(), NOW()),
('suppliers.delete', 'web', NOW(), NOW());

-- Assign to ALL web roles (covers Admin and any custom admin roles)
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
FROM `permissions` p
CROSS JOIN `roles` r
WHERE r.`guard_name` = 'web'
  AND p.`guard_name` = 'web'
  AND p.`name` IN (
    'suppliers.index',
    'suppliers.create',
    'suppliers.udpate',
    'suppliers.update',
    'suppliers.delete'
  );

-- Clear Spatie permission cache (optional; or run: php artisan permission:cache-reset)
-- DELETE FROM `cache` WHERE `key` LIKE '%spatie.permission.cache%';
