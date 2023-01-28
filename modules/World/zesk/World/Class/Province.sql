-- Database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`country` integer unsigned not null,
	`code` char(2) not null,
	`name` varchar(64) default null,
	primary key  (`id`),
	key `country_code_name` (`country`,`code`),
	key `country_name` (`country`,`name`),
	key `code` (`code`)
);
