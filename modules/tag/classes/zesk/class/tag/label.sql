-- Database: MySQL
CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`code`			varchar(128) NOT NULL,
	`name`			varchar(128) NOT NULL DEFAULT '',
	`is_internal`	tinyint NOT NULL DEFAULT 0,
	`is_translated`	tinyint NOT NULL DEFAULT 0,
	`owner`			integer unsigned NULL,
	`created`		timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`modified`		timestamp NOT NULL DEFAULT 0,
	`last_used`		timestamp NOT NULL DEFAULT 0,
	UNIQUE `code` (`code`)
	INDEX `owner` (`owner`)
);