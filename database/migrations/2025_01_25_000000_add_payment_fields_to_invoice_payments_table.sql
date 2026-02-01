-- إضافة حقول نوع السداد وبيانات الشيك إلى جدول invoice_payments

ALTER TABLE `invoice_payments`
ADD COLUMN `payment_type` ENUM('bank_transfer', 'check') NULL AFTER `value`,
ADD COLUMN `check_bank_name` VARCHAR(255) NULL AFTER `payment_type`,
ADD COLUMN `check_number` VARCHAR(255) NULL AFTER `check_bank_name`,
ADD COLUMN `check_due_date` DATE NULL AFTER `check_number`,
ADD COLUMN `check_paid_at` TIMESTAMP NULL AFTER `check_due_date`,
ADD COLUMN `notes` TEXT NULL AFTER `check_paid_at`;
