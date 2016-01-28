-- Database: mysql
create table `{table}` (
	`id` integer unsigned not null auto_increment,
	`login_email` varchar(64) not null default '',
	`login_password` varchar(32) default null,
	`name_first` varchar(64) default null,
	`name_last` varchar(64) default null,
	`company` integer default null,
	`is_active` tinyint default 1,
	`last_login` datetime default null,
	`this_login` datetime default null,
	`validated` datetime default null,
	`agreed` datetime default null,
	`created` datetime default null,
	`modified` datetime default null,
	primary key (`id`),
	unique key `login_email` (`login_email`)
);

INSERT INTO `{table}` ( login_email, login_password, name_first, name_last ) VALUES ( 'admin@localhost', MD5('123123'), 'System', 'Administrator');

