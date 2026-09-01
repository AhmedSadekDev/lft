-- ============================================================
-- Link booking_services ↔ supplier receipts
-- Database: cloudtal_leader
-- Equivalent to: 2026_07_24_000001_link_booking_services_and_supplier_receipts
-- ============================================================

-- 1) booking_services: supplier fields
ALTER TABLE `booking_services`
  ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `agent_id`,
  ADD COLUMN `supplier_invoice_number` VARCHAR(255) NULL DEFAULT NULL AFTER `supplier_id`;

ALTER TABLE `booking_services`
  ADD CONSTRAINT `booking_services_supplier_id_foreign`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

-- 2) receipts: link to booking_services (one receipt per booking service)
ALTER TABLE `receipts`
  ADD COLUMN `booking_service_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `booking_id`;

ALTER TABLE `receipts`
  ADD UNIQUE KEY `receipts_booking_service_id_unique` (`booking_service_id`),
  ADD CONSTRAINT `receipts_booking_service_id_foreign`
    FOREIGN KEY (`booking_service_id`) REFERENCES `booking_services` (`id`) ON DELETE SET NULL;

-- Optional: mark migration as ran (if you apply SQL manually instead of artisan migrate)
-- INSERT IGNORE INTO `migrations` (`migration`, `batch`)
-- SELECT '2026_07_24_000001_link_booking_services_and_supplier_receipts',
--        COALESCE(MAX(batch), 0) + 1
-- FROM `migrations`;
