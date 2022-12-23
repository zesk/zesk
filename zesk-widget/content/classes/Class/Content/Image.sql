-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Content_File -> data
-- COLUMN: Width -> width
-- COLUMN: Height -> height
-- COLUMN: ImagePath -> path
-- COLUMN: Title -> title
-- COLUMN: Description -> description
-- COLUMN: Created -> created
-- COLUMN: Modified -> modified
CREATE TABLE `{table}` (
	`id` integer NOT NULL auto_increment,
	`data` integer unsigned NOT NULL,
	`width` integer unsigned NULL,
	`height` integer unsigned NULL,
	`mime_type` varchar(32) NOT NULL default '',
	`path` varchar(128) NOT NULL default '',
	`title` text NULL,
	`description` text NULL,
	`created` timestamp NOT NULL,
	`modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id`),
	KEY `data` (`data`),
	KEY `path` (`path`)
);
