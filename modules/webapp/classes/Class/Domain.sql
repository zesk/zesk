CREATE TABLE `{table}` (
	`id`		integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name`		varchar(128) NOT NULL,
	`type`		varchar(64) NOT NULL,
	`target`	integer unsigned NOT NULL,
	`active`	tinyint NOT NULL DEFAULT 0,
	UNIQUE name (`name`),
	INDEX target (`target`),
);