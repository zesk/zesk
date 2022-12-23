-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default '',
	`created` timestamp NOT NULL DEFAULT 0,
	`modified` timestamp NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`)
);
