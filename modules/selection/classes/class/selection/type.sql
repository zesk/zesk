-- Database: MySQL
CREATE TABLE `{table}` (
	`id`		integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`code`		varchar(32) NOT NULL,
	`class`		varchar(64) NOT NULL,
	`user`		integer unsigned NOT NULL,
	`created`	timestamp NOT NULL DEFAULT 0,
	INDEX u (`user`)
);
