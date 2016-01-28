-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Name -> name
CREATE TABLE `{table}` (
	`id`	integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT ,
	`name`	varchar(64) NOT NULL,
	UNIQUE `name` (`name`)
);
