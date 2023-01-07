-- Database: mysql

CREATE TABLE `{table}` (
	`id` int(11) unsigned not null auto_increment,
	`cookie` varchar(32) not null default '',
	`is_one_time` bit(1) not null default 0,
	`user` int(11) unsigned default null,
	`ip` int(11) unsigned null,
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	`expires` timestamp not null,
	`seen` timestamp null,
	`sequence_index` int(11) DEFAULT 0 NOT NULL,
	`data` text,
	primary key  (`id`),
	unique key `u` (`cookie`),
	key `exp` (`expires`),
	key `m` (`modified`)
);
