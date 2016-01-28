-- Database: mysql
CREATE TABLE `{table}` (
	`ip`		integer unsigned NOT NULL PRIMARY KEY,
	`when`		timestamp NOT NULL DEFAULT 0,
	`status`	tinyint DEFAULT 0,
	INDEX `status` (`status`)
);
