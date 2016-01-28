-- database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned not null,
	`label` integer unsigned not null,
	`hash` integer not null,
	`value` text,
	primary key  (`id`),
	key contact (`contact`,`label`)
)
