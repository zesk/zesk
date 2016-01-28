-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: CodeName -> code
-- COLUMN: Name -> name
-- COLUMN: Description -> description

CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`code` varchar(64) not null default '',
	`name` varchar(64) default null,
	`description` text,
	primary key  (`id`),
	unique key `code` (`code`)
);

