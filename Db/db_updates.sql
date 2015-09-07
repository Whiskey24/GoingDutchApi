
# Add currency to groups
ALTER TABLE `groups` ADD COLUMN `currency` VARCHAR(6) NOT NULL DEFAULT 'EUR' AFTER `reg_date`;

# Add sort to user groups
ALTER TABLE `users_groups` ADD COLUMN `sort` INT(3) NOT NULL AFTER `group_id`;