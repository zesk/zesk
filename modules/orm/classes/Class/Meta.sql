-- Database: MySQL
CREATE TABLE `{table}` (
	`parent`	integer unsigned NOT NULL,
	`name`		varchar(32) NOT NULL,
	`value`		blob NOT NULL,
	INDEX `parent_name` (`parent`,`name`)
);
