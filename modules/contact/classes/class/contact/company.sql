-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`name` varchar(64) default null,
	`code` varchar(32) not null default '',
	`description` text,
	`logo` integer unsigned default null,
	`tax_id` varchar(64) default null,
	`address` integer unsigned default null,
	`created` datetime default null,
	`modified` datetime default null,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `code` (`code`)
);

