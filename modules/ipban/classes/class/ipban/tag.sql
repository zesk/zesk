-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Type -> type
-- COLUMN: Value -> value
CREATE TABLE `{table}` (
	`id`	integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT ,
	`type`	integer unsigned NOT NULL,
	`value`	varchar(64) NOT NULL,
	INDEX `type` (`type`),
	INDEX `value` (`value`)
);
