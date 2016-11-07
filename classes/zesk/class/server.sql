-- Database: mysql
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

