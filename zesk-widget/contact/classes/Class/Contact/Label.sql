-- Database: mysql
CREATE TABLE `{table}` (
	`id`		integer unsigned not null auto_increment,
	`account`	integer unsigned null,
	`group`		integer unsigned null,
	`type`		tinyint unsigned not null default '0',
	`code`	 	varchar(16) not null default '',
	`name` 		varchar(32) not null default '',
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	primary key  (`id`),
	unique acode (`account`,`type`,`code`)
);
