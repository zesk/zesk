CREATE TABLE `{table}` (
	`id`		integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`instance`	integer unsigned NOT NULL,
	`name`		varchar(128) NOT NULL,
	`code`		varchar(64) NOT NULL,
	`priority`	tinyint NOT NULL,
	`path`		varchar(64) NOT NULL,
	`data`		blob NOT NULL,
	`errors`	blob NOT NULL,
	`valid`		tinyint NOT NULL DEFAULT 0,
	INDEX instance (`instance`),
	UNIQUE instance_version (`instance`, `code`, `priority`),
);