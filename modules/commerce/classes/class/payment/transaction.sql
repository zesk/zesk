-- Database: mysql
CREATE TABLE `{table}` (
	`id` 				integer unsigned NOT NULL AUTO_INCREMENT,
	`payment` 			integer unsigned NOT NULL,
	`batch` 			integer unsigned DEFAULT NULL,
	`description` 		text,
	`price` 			decimal(12,2) unsigned NOT NULL,
	`submitcode` 		varchar(64) DEFAULT NULL,
	`submitted` 		timestamp DEFAULT NULL,
	`authorized` 		tinyint unsigned NOT NULL DEFAULT 0,
	`confirmcode` 		varchar(64) DEFAULT NULL,
	`cleared` 			timestamp DEFAULT NULL,
	`account` 			integer unsigned NOT NULL,
	`invoice` 			integer unsigned DEFAULT NULL,
	`processed`			timestamp DEFAULT NULL,
	`transaction_code` 	varchar(64) DEFAULT NULL,
	`avs_code` 			varchar(4) DEFAULT NULL,
	`parent` 			integer unsigned DEFAULT NULL,
	`data` 				text NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;
