-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Contact -> contact
-- COLUMN: Label -> label
-- COLUMN: Value -> value
-- COLUMN: Created -> created
-- COLUMN: Modified -> modified
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned not null,
	`label` integer unsigned default null,
	`value` date not null default '0000-00-00',
	`created` timestamp not null default 0,
	`modified` timestamp not null default 0,
	primary key  (`id`),
	key contactdates (`contact`,`value`)
);
