-- Database: mysql
CREATE TABLE `{table}`(
	`id` integer unsigned NOT NULL auto_increment,
	`slug` varchar(128) NOT NULL,
	`parent` integer unsigned NULL,
	`headline` varchar(128) NOT NULL,
	`title` varchar(128) NOT NULL,
	`byline` varchar(128) NOT NULL,
	`summary` text,
	`body` text,
	`more_link` varchar(128) NULL,
	`date_display` timestamp NULL,
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	`publish_start` timestamp NULL,
	`publish_end` timestamp NULL,
	`is_active` enum('false','true') default 'true',
	`order_index` integer default '0',
	PRIMARY KEY (`id`),
	KEY `parent` (`parent`),
	KEY `pp` (`publish_start`,`publish_end`)
);
