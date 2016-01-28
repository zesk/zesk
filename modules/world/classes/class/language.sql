-- Database: mysql
CREATE TABLE `{table}` (
	`id` int(11) unsigned not null auto_increment,
	`code` char(2) not null default '',
	`dialect` integer unsigned null,
	`name` varchar(64) null,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `code` (`code`),
	KEY `dialect` (`dialect`)
);
