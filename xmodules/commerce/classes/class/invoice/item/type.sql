-- Database: mysql
CREATE TABLE `{table}` (
	`id` 			integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`code` 			varchar(32) NOT NULL,
	`order_index`	integer NOT NULL DEFAULT 0,
	`name` 			varchar(255) NOT NULL,
	`object_class`	varchar(128) default null,
	`description`	text,
	unique key `u` (`code`)
) ENGINE=InnoDB;
