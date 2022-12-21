-- COLUMN: msec -> when_msec
CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`when`			timestamp NOT NULL,
	`when_msec`		smallint NOT NULL DEFAULT 0,
	`events`		integer unsigned NULL,
	`server`		integer unsigned NULL,
	`application`	varchar(64) NOT NULL,
	`context`		varchar(62) NULL,
	`type`			varchar(24),
	`fatal`			tinyint unsigned NOT NULL DEFAULT 0,
	`message`		text NOT NULL DEFAULT '',
	`file`			text NULL,
	`line`			integer NULL,
	`data`			blob NULL,
	`backtrace`		blob NULL,
	INDEX when (`when`)
);
