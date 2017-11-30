-- Database: mysql
CREATE TABLE `{table}` (
	`id` 				integer unsigned NOT NULL auto_increment,
	`code` 				varchar(32) NOT NULL default '',
	`name` 				varchar(252) default NULL,
	`description` 		text,
	`object_class` 		varchar(64) default NULL,
	`data`	 			text default NULL,
	`enabled` 			tinyint unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `code` (`code`)
);
