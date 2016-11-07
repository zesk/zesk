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
	`title` text NOT NULL default '',
	`description` text NOT NULL default '',
	`created` timestamp NOT NULL DEFAULT 0,
	`modified` timestamp NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id`),
	KEY `data` (`data`),
	KEY `path` (`path`)
);
