CREATE TABLE `{table}` (
	`type`		integer unsigned NOT NULL,
	`id`		integer unsigned NOT NULL,
	`add`		tinyint NOT NULL DEFAULT '0',
	`query`		integer unsigned NULL,
	INDEX q (`query`),
	INDEX tid (`type`,`id`)
);
