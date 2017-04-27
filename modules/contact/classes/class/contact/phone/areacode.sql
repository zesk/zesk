-- Database: mysql
CREATE TABLE `{table}` (
	`ID` integer unsigned NOT NULL auto_increment,
	`Code` varchar(4) NOT NULL default '',
	`Country` integer unsigned default NULL,
	`Province` integer unsigned default NULL,
	`Description` text,
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `u` (`Code`),
	INDEX sp (`Country`,`Province`)
);
