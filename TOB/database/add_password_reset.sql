-- Migration to add OTP and password reset fields to users table

ALTER TABLE `users` ADD COLUMN `otp` VARCHAR(6) DEFAULT NULL AFTER `password`;
ALTER TABLE `users` ADD COLUMN `otp_expiry` TIMESTAMP NULL DEFAULT NULL AFTER `otp`;
ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(255) DEFAULT NULL AFTER `otp_expiry`;
ALTER TABLE `users` ADD COLUMN `reset_token_expiry` TIMESTAMP NULL DEFAULT NULL AFTER `reset_token`;
ALTER TABLE `users` ADD COLUMN `is_otp_verified` TINYINT(1) DEFAULT 1 AFTER `reset_token_expiry`;

-- Note: is_otp_verified is set to 1 by default for existing users
-- New users will need to verify their OTP during registration (optional based on your requirements)
