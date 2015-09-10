
# Add currency to groups
ALTER TABLE `groups` ADD COLUMN `currency` VARCHAR(6) NOT NULL DEFAULT 'EUR' AFTER `reg_date`;

# Add sort to user groups
ALTER TABLE `users_groups` ADD COLUMN `sort` INT(3) NOT NULL AFTER `group_id`;

# Add updated, firstname and lastname column to users
ALTER TABLE `users` ADD COLUMN `updated` INT(11) NOT NULL DEFAULT '0' AFTER `last_login`;
ALTER TABLE `users`ADD COLUMN `firstName` VARCHAR(100) NOT NULL AFTER `realname`,
ADD COLUMN `lastName` VARCHAR(100) NOT NULL AFTER `firstName`;

# Add categories table
CREATE TABLE `categories` (
  `cid` INT NOT NULL,
  `group_id` INT NOT NULL,
  `title`, `sort` VARCHAR(50) NOT NULL,
  `presents` INT NOT NULL DEFAULT '0',
  `inactive` TINYINT NOT NULL DEFAULT '0',
  `can_delete` TINYINT NOT NULL DEFAULT '0',
  `sort` INT NOT NULL DEFAULT '1'
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB
;
ALTER TABLE `categories` ADD PRIMARY KEY (`cid`, `group_id`);


# Add timezoneoffset to expenses table
ALTER TABLE `expenses` ADD COLUMN `timezoneoffset` SMALLINT NOT NULL DEFAULT '0' AFTER `currency`;


# Copy existing expense types over as categories
INSERT INTO categories` (`expense_type_id`, `description`) VALUES
  (1, 'food/drinks', 1),
  (2, 'tickets', 2),
  (3, 'presents'),
  (4, 'games'),
  (5, 'payment'),
  (6, 'beer', 6);


INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 1, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 1, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 1, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 1, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 1, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 1, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 2, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 2, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 2, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 2, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 2, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 2, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 3, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 3, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 3, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 3, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 3, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 3, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 4, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 4, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 4, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 4, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 4, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 4, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 5, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 5, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 5, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 5, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 5, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 5, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 6, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 6, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 6, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 6, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 6, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 6, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 7, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 7, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 7, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 7, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 7, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 7, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 8, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 8, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 8, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 8, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 8, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 8, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 9, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 9, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 9, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 9, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 9, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 9, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 10, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 10, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 10, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 10, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 10, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 10, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 11, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 11, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 11, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 11, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 11, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 11, 'beer', 6);

INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 12, 'food/drinks', 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 12, 'tickets', 2);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 12, 'presents', 3, 1);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 12, 'games', 4);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 12, 'payment', 5);
INSERT INTO `goingdutch`.`categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 12, 'beer', 6);
