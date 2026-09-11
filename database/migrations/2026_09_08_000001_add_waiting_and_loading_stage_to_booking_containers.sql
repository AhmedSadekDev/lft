ALTER TABLE `booking_containers`
ADD COLUMN `is_in_loading` TINYINT(1) NOT NULL DEFAULT 0 AFTER `superagent_specification_approved`,
ADD COLUMN `moved_to_loading_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_in_loading`;

ALTER TABLE `booking_container_agents`
ADD COLUMN `is_in_loading` TINYINT(1) NOT NULL DEFAULT 0 AFTER `superagent_specification_approved`,
ADD COLUMN `moved_to_loading_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_in_loading`;

ALTER TABLE `daily_booking_containers`
ADD COLUMN `is_in_loading` TINYINT(1) NOT NULL DEFAULT 0 AFTER `superagent_specification_approved`,
ADD COLUMN `moved_to_loading_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_in_loading`;
