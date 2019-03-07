CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`server`		integer unsigned NOT NULL,
	`repository`	integer unsigned NULL,
	`path`			varchar(128) NOT NULL,
	`code`			varchar(64) NOT NULL,
	`name`			varchar(64) NOT NULL,
	`appversion`	varchar(64) NULL,
	`apptype`		varchar(16) NULL,
	`hash`			varbinary(16) NOT NULL,
	`json`			blob NOT NULL,
	`updated`		timestamp NOT NULL DEFAULT 0,
	`serving`		timestamp NOT NULL DEFAULT 0,
	INDEX repo (`repository`),
	INDEX server (`server`),
	UNIQUE server_path (`server`, `path`),
	INDEX server_code (`server`, `code`),
);