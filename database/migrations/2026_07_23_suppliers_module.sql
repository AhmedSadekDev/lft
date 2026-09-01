-- ============================================================
-- Suppliers Management Module - MySQL DDL
-- Database: cloudtal_leader
-- ============================================================

-- 1) suppliers
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) receipts
-- (created only if it does not already exist)
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `cost` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `payment_source` ENUM('safe', 'representative', 'supplier') NULL DEFAULT NULL,
  `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_invoice_number` VARCHAR(255) NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipts_supplier_invoice_number_index` (`supplier_invoice_number`),
  KEY `receipts_booking_id_foreign` (`booking_id`),
  KEY `receipts_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `receipts_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipts_supplier_id_foreign`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Add supplier fields to receipts (if table already existed without them)
-- Run these only if the columns are missing:

-- ALTER TABLE `receipts`
--   ADD COLUMN `payment_source` ENUM('safe', 'representative', 'supplier') NULL DEFAULT NULL AFTER `cost`,
--   ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `payment_source`,
--   ADD COLUMN `supplier_invoice_number` VARCHAR(255) NULL DEFAULT NULL AFTER `supplier_id`,
--   ADD INDEX `receipts_supplier_invoice_number_index` (`supplier_invoice_number`),
--   ADD CONSTRAINT `receipts_supplier_id_foreign`
--     FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

-- 4) supplier_payments
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `source_type` ENUM('safe', 'representative') NOT NULL,
  `source_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'agents.id when source_type = representative',
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payments_source_id_index` (`source_id`),
  KEY `supplier_payments_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `supplier_payments_supplier_id_foreign`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) suppliers permissions (Spatie)
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('suppliers.index',  'web', NOW(), NOW()),
('suppliers.create', 'web', NOW(), NOW()),
('suppliers.udpate', 'web', NOW(), NOW()),
('suppliers.update', 'web', NOW(), NOW()),
('suppliers.delete', 'web', NOW(), NOW());

-- Assign permissions to Admin role (if role exists)
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
FROM `permissions` p
CROSS JOIN `roles` r
WHERE r.`name` = 'Admin'
  AND r.`guard_name` = 'web'
  AND p.`guard_name` = 'web'
  AND p.`name` IN (
    'suppliers.index',
    'suppliers.create',
    'suppliers.udpate',
    'suppliers.update',
    'suppliers.delete'
  );

-- Optional: mark migrations as ran (if you apply SQL manually instead of artisan migrate)
-- INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
-- ('2026_07_23_000001_create_suppliers_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
-- ('2026_07_23_000002_create_receipts_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
-- ('2026_07_23_000003_add_supplier_fields_to_receipts_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
-- ('2026_07_23_000004_create_supplier_payments_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
-- ('2026_07_23_000005_add_suppliers_permissions', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));
