-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned null,
	`label` integer unsigned null,
	`name` varchar(64) not null default '',
	`unparsed` text,
	`street` text,
	`additional` text,
	`city` varchar(64) not null default '',
	`province` varchar(64) default null,
	`postal_code` varchar(64) default null,
	`country_code` char(2) null,
	`county` integer unsigned null,
	`country` integer unsigned null,
	`latitude` decimal(10,6) null,
	`longitude` decimal(10,6) null,
	`geocoded` timestamp NULL,
	`geocode_data` text,
	`created` timestamp not null default 0,
	`modified` timestamp not null default 0,
	`data` text,
	PRIMARY KEY  (`id`)
);

