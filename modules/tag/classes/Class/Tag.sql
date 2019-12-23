-- Database: MySQL
CREATE TABLE `{table}` (
	`tag_label`		integer unsigned NOT NULL,
	`foreign_id`	integer unsigned NOT NULL,
	PRIMARY KEY (`tag_label`,`foreign_id`)
);