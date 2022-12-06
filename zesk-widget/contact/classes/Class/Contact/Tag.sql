-- database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`user` integer unsigned not null,
	`name` varchar(64),
	primary key  (`id`),
	key `aname` (`user`,`name`)
);
