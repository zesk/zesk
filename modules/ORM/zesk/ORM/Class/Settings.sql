-- Database: mysql
-- COLUMN: Value -> value
-- COLUMN: Name -> name
-- COLUMN: Modified -> modified

CREATE TABLE `{table}` (
	`name` varchar(128) not null primary key,
	`value` blob,
	`modified` timestamp not null
);
