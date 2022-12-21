-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null,
	`bank_country` integer unsigned default NULL,
	`name` varchar(48) default NULL,
	`code` char(3) NOT NULL default '',
	`symbol` varchar(16) default '',
	`format` varchar(32) default 'false',
	`fractional` smallint unsigned default NULL,
	`fractional_units` varchar(32) default NULL,
	`precision` tinyint unsigned NOT NULL default '2',
	PRIMARY KEY  (`id`),
	UNIQUE KEY `codename` (`code`)
);
