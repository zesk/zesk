-- Database: MySQL
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`code` varchar(128) NOT NULL,
	`pid` integer unsigned NULL,
	`server` integer unsigned NULL,
	`locked` timestamp NULL,
	`used` timestamp NULL,
	UNIQUE code (`code`)
)
