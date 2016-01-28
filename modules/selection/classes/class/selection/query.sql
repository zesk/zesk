CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`type`			integer unsigned NOT NULL,
	`code`			varbinary(16) NOT NULL,
	`title`			text,
	`created`		timestamp NOT NULL DEFAULT 0,
	`query_total`	text,
	`query_list`	text,
	UNIQUE code (`type`,`code`)
);
