-- Database: mysql
CREATE TABLE `{table}` (
	`id` 		integer unsigned NOT NULL auto_increment,
	`mail` 		integer unsigned NOT NULL,
	`type` 		integer unsigned NOT NULL,
	`hash`		varbinary(16) NOT NULL,
	`value` 	text,
	PRIMARY KEY (`id`),
	UNIQUE `u` (`mail`, `type`, `hash`)
);

