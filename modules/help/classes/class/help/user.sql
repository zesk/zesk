-- Database: mysql
CREATE TABLE `{table}` (
	`help`			integer unsigned NOT NULL,
	`user`			integer unsigned NOT NULL,
	`dismissed`		timestamp NOT NULL DEFAULT 0,
	INDEX hu (`help`,`user`),
	INDEX uh (`user`,`help`)
);
