-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`province` int(11) unsigned default null,
	`name` varchar(128) default null,
	primary key  (`id`),
	unique key `u` (`province`,`name`)
);
