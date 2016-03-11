-- Database: mysql
CREATE TABLE `{table}` (
	`id` 			integer unsigned NOT NULL auto_increment,
	`parent` 		integer unsigned NULL,
	`hash` 			varchar(32) NOT NULL,
	`name` 			varchar(128) NULL,
	`width` 		integer unsigned NULL,
	`height` 		integer unsigned NULL,
	`url` 			text NOT NULL default '',
	`filepath` 		varchar(128) NOT NULL default '',
	`description` 	text NOT NULL default '',
	`created` 		timestamp NOT NULL DEFAULT 0,
	`modified` 		timestamp NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `hash` (`hash`)
);
