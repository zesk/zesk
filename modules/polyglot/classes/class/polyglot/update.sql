CREATE TABLE `{table}` (
	locale			char(5) NOT NULL PRIMARY KEY,
	user			integer unsigned NULL,
	updated			timestamp NOT NULL DEFAULT 0,
	INDEX `updated` ( `updated` )
);
