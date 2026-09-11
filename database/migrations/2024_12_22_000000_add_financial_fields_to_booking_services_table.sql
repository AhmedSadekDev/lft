-- Add financial fields to booking_services table
-- Run this SQL script to add vault_id, bank_id, created_by, and updated_by columns

-- Add vault_id column
ALTER TABLE `booking_services`
ADD COLUMN `vault_id` BIGINT UNSIGNED NULL AFTER `price`;

-- Add foreign key for vault_id
ALTER TABLE `booking_services`
ADD CONSTRAINT `booking_services_vault_id_foreign`
FOREIGN KEY (`vault_id`) REFERENCES `vaults` (`id`) ON DELETE SET NULL;

-- Add bank_id column
ALTER TABLE `booking_services`
ADD COLUMN `bank_id` BIGINT UNSIGNED NULL AFTER `vault_id`;

-- Add foreign key for bank_id
ALTER TABLE `booking_services`
ADD CONSTRAINT `booking_services_bank_id_foreign`
FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL;

-- Add created_by column
ALTER TABLE `booking_services`
ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `bank_id`;

-- Add foreign key for created_by
ALTER TABLE `booking_services`
ADD CONSTRAINT `booking_services_created_by_foreign`
FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add updated_by column
ALTER TABLE `booking_services`
ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`;

-- Add foreign key for updated_by
ALTER TABLE `booking_services`
ADD CONSTRAINT `booking_services_updated_by_foreign`
FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

