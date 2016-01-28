-- database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`user` integer unsigned not null,
	`name` varchar(64),
	primary key  (`id`),
	key `aname` (`user`,`name`)
);

create table `{table}_contact` (
	`contact_tag` integer unsigned not null,
	`contact` integer unsigned not null,
	key contact_tag (`contact_tag`),
	key contact (`contact`)
);

