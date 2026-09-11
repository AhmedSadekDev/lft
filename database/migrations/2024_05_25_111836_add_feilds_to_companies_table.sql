ALTER TABLE `companies`
ADD COLUMN `opening_balance` DOUBLE(15,2) NOT NULL DEFAULT 0 AFTER `wallet`;
