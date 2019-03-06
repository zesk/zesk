CREATE TABLE `{table}` (
	`id`		integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name`		varchar(128) NOT NULL,
	`type`		varchar(64) NULL,
	`target`	integer unsigned NULL,
	`active`	tinyint NOT NULL DEFAULT 0,
	`accessed`	timestamp NULL,
	UNIQUE name (`name`),
	INDEX target (`target`),
);