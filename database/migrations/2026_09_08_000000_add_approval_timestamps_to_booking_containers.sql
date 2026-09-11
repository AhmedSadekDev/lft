ALTER TABLE `booking_containers`
ADD COLUMN `specification_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_specification_approved`,
ADD COLUMN `specification_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `specification_completed_at`,
ADD COLUMN `loading_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_loading_approved`,
ADD COLUMN `loading_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `loading_completed_at`,
ADD COLUMN `unloading_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_unloading_approved`,
ADD COLUMN `unloading_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `unloading_completed_at`;

ALTER TABLE `booking_container_agents`
ADD COLUMN `specification_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_specification_approved`,
ADD COLUMN `specification_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `specification_completed_at`,
ADD COLUMN `loading_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_loading_approved`,
ADD COLUMN `loading_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `loading_completed_at`,
ADD COLUMN `unloading_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `superagent_unloading_approved`,
ADD COLUMN `unloading_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `unloading_completed_at`;
