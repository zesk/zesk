-- Database: mysql
CREATE TABLE `{table}` (
	`id` 		integer unsigned NOT NULL AUTO_INCREMENT,
	`code` 		varchar(64) NOT NULL DEFAULT '',
	`name` 		varchar(64) NOT NULL DEFAULT '',
	`created` 	timestamp NOT NULL,
	`checked` 	timestamp NOT NULL,
	`ignore` 	tinyint NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	UNIQUE code (`code`)
);
