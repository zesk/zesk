-- Database: mysql
CREATE TABLE `{table}` (
	`id`           integer unsigned NOT NULL auto_increment,
	`mail`         integer unsigned NOT NULL default '0',
	`content_id`   varchar(64) default NULL,
	`content_type` varchar(64) NOT NULL default '',
	`filename`     varchar(64) NOT NULL default '',
	`disposition`  varchar(32) NOT NULL default '',
	`content_data` integer unsigned NOT NULL,
	PRIMARY KEY  (`id`),
	INDEX `content_data` (`content_data`)
);
