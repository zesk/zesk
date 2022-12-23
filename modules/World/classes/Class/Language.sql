-- Database: mysql
CREATE TABLE `{table}` (
	`id` int(11) unsigned not null auto_increment,
	`code` char(2) not null,
	`dialect` char(2) null,
	`name` varchar(64) null,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `code` (`code`,`dialect`),
	KEY `dialect` (`dialect`)
);
