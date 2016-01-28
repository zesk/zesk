-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: User -> user
-- COLUMN: PreferenceType -> type
-- COLUMN: PreferenceValue -> value
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`user` integer unsigned not null,
	`type` integer unsigned not null,
	`value` text,
	primary key  (`id`),
	unique key `u` (`user`,`type`)
);
