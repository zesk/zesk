-- Database: mysql
CREATE TABLE `{table}` (
	`ID` integer unsigned NOT NULL auto_increment,
	`Code` integer NOT NULL default '0',
	`AreaCode` integer unsigned default NULL,
	`Country` integer unsigned default NULL,
	`GlobalName` varchar(64) default NULL,
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `u` (`Code`,`AreaCode`,`Country`)
);
