ALTER TABLE `private_companies`
ADD COLUMN `phone1` varchar(255) NULL AFTER `logo`,
ADD COLUMN `phone2` varchar(255) NULL AFTER `phone1`,
ADD COLUMN `tel_fax` varchar(255) NULL AFTER `phone2`,
ADD COLUMN `email` varchar(255) NULL AFTER `tel_fax`,
ADD COLUMN `address` text NULL AFTER `email`;
