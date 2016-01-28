-- Database: mysql
-- COLUMN: Server -> server
-- COLUMN: Name -> name
-- COLUMN: Value -> value
CREATE TABLE `{table}` (
	`id` integer unsigned not null PRIMARY KEY AUTO_INCREMENT,
	`server` integer unsigned not null,
	`name` varchar(64) not null,
	`value` longtext not null,
	unique `sname` (`server`, `name`)
);
