CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name`			varchar(128) NOT NULL,
	`type`			varchar(32) NOT NULL,
	`code`			varchar(32) NOT NULL,
	`url`			varchar(192) NOT NULL,
	`versions`		blob NOT NULL,
	`remote_hash`	varchar(32) NOT NULL,
	`active`		tinyint NOT NULL DEFAULT 0,
	`updated`		timestamp NULL,
	UNIQUE `code` (`code`),
	INDEX `url` (`url`),
);