-- Database: mysql
CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL auto_increment,
	`hash`			varbinary(16) NOT NULL DEFAULT '',
	`address`		varchar(128) NOT NULL DEFAULT '',
	`sent`			timestamp NOT NULL DEFAULT 0,
	`confirmed`		timestamp NULL,
	`user` 			integer unsigned NOT NULL,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `u` (`hash`,`address`)
);
