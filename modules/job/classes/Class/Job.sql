CREATE TABLE `{table}` (
	id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	user integer unsigned NULL,
	name varchar(64) NOT NULL,
	code varchar(64) NOT NULL,
	created timestamp NOT NULL DEFAULT 0,
	start timestamp NULL,
	priority tinyint NOT NULL DEFAULT 0,
	server integer unsigned NULL,
	pid integer unsigned NULL,
	completed timestamp NULL,
	updated timestamp NULL,
	duration integer NOT NULL DEFAULT 0,
	died integer unsigned NOT NULL DEFAULT 0,
	last_exit tinyint unsigned NOT NULL DEFAULT 0,
	progress double NULL,
	hook varchar(128),
	hook_args blob NOT NULL DEFAULT '',
	data blob NULL,
	status text NOT NULL DEFAULT '',
	UNIQUE code (code),
	INDEX start (start),
	INDEX spc (start,pid,completed),
	INDEX sp (server,pid)
);
