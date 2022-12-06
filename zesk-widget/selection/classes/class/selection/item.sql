CREATE TABLE `{table}` (
	`type`		integer unsigned NOT NULL,
	`id`		integer unsigned NOT NULL,
	`add`		tinyint NOT NULL DEFAULT '0',
	`query`		integer unsigned NULL,
	UNIQUE tid (`type`,`id`),
	INDEX q (`query`)
);
