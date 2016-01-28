-- Database: mysql
CREATE TABLE `{table}` (
	`ID` integer unsigned NOT NULL auto_increment,
	`Name` varchar(64) NOT NULL default '',
	`Created` timestamp NOT NULL DEFAULT 0,
	`Modified` timestamp NOT NULL DEFAULT 0,
	PRIMARY KEY  (`ID`)
);
