-- Database: MySQL
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name` varchar(64) NOT NULL,
	`dbname` varchar(64) NOT NULL,
	`created` timestamp NOT NULL DEFAULT 0,
	`elapsed` double NOT NULL DEFAULT 0,
	`data` longblob NOT NULL,
	INDEX dbcreated (dbname, created)
);
