-- Database: mysql
CREATE TABLE `{table}` (
	`ID` integer NOT NULL auto_increment,
	`Parent` integer unsigned NULL,
	`Hash` varchar(32) NOT NULL,
	`Name` varchar(128) NULL,
	`Width` integer unsigned NULL,
	`Height` integer unsigned NULL,
	`URL` text NOT NULL default '',
	`FilePath` varchar(128) NOT NULL default '',
	`Description` text NOT NULL default '',
	`Created` datetime NOT NULL,
	`Modified` datetime NOT NULL,
	PRIMARY KEY  (`ID`),
	KEY `Hash` (`Hash`)
);
