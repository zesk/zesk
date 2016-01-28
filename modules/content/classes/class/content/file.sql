-- Database: mysql
CREATE TABLE `{table}` (
	`ID` integer unsigned NOT NULL AUTO_INCREMENT,
	`MIMEType` varchar(128) NULL,
	`Original` varchar(128) NOT NULL DEFAULT '',
	`Name` varchar(128) NOT NULL DEFAULT '',
	`Content_Data` integer unsigned NOT NULL,
	`Description` longtext,
	`User` integer unsigned DEFAULT NULL,
	`Created` datetime DEFAULT NULL,
	`Modified` datetime DEFAULT NULL,
	INDEX Name (`Name`),
	INDEX `Content_Data` (`Content_Data`),
	PRIMARY KEY (`ID`)
);
