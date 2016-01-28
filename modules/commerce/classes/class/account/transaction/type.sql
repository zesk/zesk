-- Database: mysql
CREATE TABLE `{table}` (
	`id` 					integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`code`	 				varchar(32) NOT NULL DEFAULT '',
	`name` 					varchar(64) NOT NULL DEFAULT '',
	`object_class`	 		varchar(128) NOT NULL DEFAULT '',
	`description` 			text NULL,
	UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB;
