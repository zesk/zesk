-- Database: MySQL
CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`tag_label`		integer unsigned NOT NULL,
	`object_class`	varchar(128) NOT NULL,
	`object_id`		integer unsigned NOT NULL,
	`created`		timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`modified`		timestamp NOT NULL DEFAULT 0,
	`value`			blob NOT NULL,
	UNIQUE `u` (`tag_label`,`object_class`,`object_id`)
	INDEX `object_class_id` (`object_class`,`object_id`)
);