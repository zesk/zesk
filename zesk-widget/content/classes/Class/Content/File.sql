-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: MIMEType -> mime
-- COLUMN: Original -> original
-- COLUMN: Name -> name
-- COLUMN: Content_Data -> data
-- COLUMN: Description -> description
-- COLUMN: User -> user
-- COLUMN: Created -> created
-- COLUMN: Modified -> modified
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL AUTO_INCREMENT,
	`mime` varchar(128) NULL,
	`original` varchar(128) NOT NULL DEFAULT '',
	`name` varchar(128) NOT NULL DEFAULT '',
	`data` integer unsigned NOT NULL,
	`description` longtext,
	`user` integer unsigned DEFAULT NULL,
	`created` datetime DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	INDEX name (`name`),
	INDEX `data` (`data`),
	PRIMARY KEY (`id`)
);
