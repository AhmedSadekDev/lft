ALTER TABLE `companies`
ADD COLUMN `private_company_id` bigint(20) UNSIGNED NULL AFTER `taxed`,
ADD CONSTRAINT `companies_private_company_id_foreign`
FOREIGN KEY (`private_company_id`)
REFERENCES `private_companies` (`id`)
ON DELETE SET NULL
ON UPDATE CASCADE;
