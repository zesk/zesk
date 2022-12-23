-- Database: MySQL
CREATE TABLE `{table}` (
	`id`				integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`account`			integer unsigned NULL,
	`frequency`			integer unsigned NOT NULL DEFAULT 1,
	`unit`				varchar(8) NOT NULL DEFAULT 'day',
	`weekday`			tinyint unsigned NULL,
	`monthday`			tinyint unsigned NULL,
	`hour`				tinyint unsigned NULL,
);
