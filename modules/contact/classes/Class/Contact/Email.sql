-- database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned not null,
	`label` integer unsigned null,
	`value` varchar(128) not null default '',
	`created` timestamp not null default 0,
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	`optout` enum('false','true') default 'false',
	primary key  (`id`),
	key contact (`contact`)
);
