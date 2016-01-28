-- Database: mysql
-- RENAME: ID -> id
-- RENAME: Account -> account
-- RENAME: CodeName -> code
-- RENAME: Name -> name
-- RENAME: IsActive -> enabled
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL AUTO_INCREMENT,
	`account` integer unsigned DEFAULT NULL,
	`code` varchar(32) DEFAULT NULL,
	`name` varchar(64) DEFAULT NULL,
	`enabled` tinyint DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB;

