-- Add payment_type and agent_id to booking_services table
-- Run this SQL script to add payment_type and agent_id columns

-- Add payment_type column
ALTER TABLE `booking_services`
ADD COLUMN `payment_type` VARCHAR(255) NULL COMMENT 'vault, bank, agent' AFTER `bank_id`;

-- Add agent_id column
ALTER TABLE `booking_services`
ADD COLUMN `agent_id` BIGINT UNSIGNED NULL AFTER `payment_type`;

-- Add foreign key for agent_id
ALTER TABLE `booking_services`
ADD CONSTRAINT `booking_services_agent_id_foreign`
FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL;

