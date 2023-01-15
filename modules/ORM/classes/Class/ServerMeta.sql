-- Database: mysql
CREATE TABLE `{table}` (
	`id` integer unsigned not null PRIMARY KEY AUTO_INCREMENT,
	`server` integer unsigned not null,
	`name` varchar(64) not null,
	`value` longblob not null,
	unique `serverName` (`server`, `name`)
);
