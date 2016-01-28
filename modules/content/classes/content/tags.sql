-- Database: mysql
CREATE TABLE `{table}` (
	`Content_Tag`		integer unsigned NOT NULL,
	`Content_Tag_Type`	integer unsigned NOT NULL,
	`Content`			integer unsigned NOT NULL,
	`Created`			timestamp NOT NULL DEFAULT '0',
	PRIMARY KEY ( `Content_Tag`, `Content_Tag_Type`, `Content` )
	INDEX ( `Content`, `Content_Tag_Type` )
);
