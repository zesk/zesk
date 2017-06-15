-- Database: mysql
CREATE TABLE `{table}` (
	`id` int(11) unsigned not null auto_increment,
	`code` char(2) default null,
	`name` varchar(64) default null,
	primary key (`id`),
	unique key `u` (`code`,`name`),
	key `codei` (`code`)
);

