-- database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned not null,
	`label` varchar(64) default '',
	`value` varchar(128) not null default '',
	`created` timestamp not null default 0,
	`modified` timestamp not null default 0,
	primary key  (`id`),
	key contact (`contact`)
);
