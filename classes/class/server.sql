-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Name -> name
-- COLUMN: InternalName -> name_internal
-- COLUMN: ExternalName -> name_external
-- COLUMN: InternalIP4 -> ip4_internal
-- COLUMN: ExternalIP4 -> ip4_external
-- COLUMN: FreeDisk -> free_disk
-- COLUMN: Load -> load
-- COLUMN: Alive -> alive
CREATE TABLE `{table}` (
	`id` 				integer not null auto_increment,
	`name`	 			varchar(32) not null,
	`name_internal`		varchar(64) not null,
	`name_external`		varchar(64) not null,
	`ip4_internal`		integer unsigned not null,
	`ip4_external`		integer unsigned not null,
	`free_disk`			integer unsigned null,
	`load`	 			decimal(6,3) default '0',
	`alive`				timestamp not null default 0,
	primary key  (`id`),
	unique `name` (`name`),
	index `load` (`load`),
	index `free_disk` (`free_disk`),
	unique `ip4_internal` (`ip4_internal`),
	index `alive` (`alive`)
);

