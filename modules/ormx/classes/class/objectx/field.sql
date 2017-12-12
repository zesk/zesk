CREATE TABLE `{table}` (
	`id` integer unsigned AUTO_INCREMENT NOT NULL,
	`class` varchar(64) NOT NULL,
	`active` tinyint NOT NULL DEFAULT 0,
	`column` varchar(32) NOT NULL,
	`name` varchar(32) NOT NULL,
	`help` text NULL,
	`type` varchar(32) NOT NULL,
	`order_index` integer NOT NULL DEFAULT 0,
	`data` text,
	PRIMARY KEY (`id`),
	UNIQUE (`class`,`column`),
	KEY `class` (`class`,`order_index`),
	KEY `active` (`active`),
	KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
