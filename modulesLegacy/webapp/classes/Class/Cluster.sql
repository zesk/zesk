CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name`			varchar(128) NOT NULL,
	`code`			varchar(64) NOT NULL,
	`sitecode`		varchar(64) NOT NULL,
	`min_members`	tinyint NOT NULL default 1,
	`max_members`	tinyint NULL,
	`active`		timestamp NOT NULL,
	UNIQUE code (`code`),
	INDEX active (`active`),
);