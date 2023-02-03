-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`user` integer unsigned not null,
	`type` integer unsigned not null,
	`value` blob,
	primary key  (`id`),
	unique key `u` (`user`,`type`)
);
