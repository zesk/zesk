CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`type`			integer unsigned NOT NULL,
	`order_index`	integer NOT NULL DEFAULT 0,
	`code`			varbinary(16) NOT NULL,
	`title`			text,
	`created`		timestamp NOT NULL DEFAULT 0,
	`query_total`	blob NOT NULL,
	`query_list`	blob NOT NULL,
	`add`			tinyint NOT NULL DEFAULT 1,
	UNIQUE code (`type`,`code`)
);
