-- Database: mysql

CREATE TABLE `{table}` (
	`id` int(11) unsigned not null auto_increment,
	`token` varchar(32) not null default '',
	`type` varchar(8) not null default '',
	`user` int(11) unsigned default null,
	`ip` int(11) unsigned null,
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	`expires` timestamp not null,
	`seen` timestamp null,
	`sequence_index` int(11) DEFAULT 0 NOT NULL,
	`data` text,
	primary key  (`id`),
	unique key `token` (`token`,`type`),
	key `exp` (`expires`),
	key `m` (`modified`)
);
