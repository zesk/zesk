-- Database: mysql
CREATE TABLE `{table}` (
	ID integer unsigned NOT NULL AUTO_INCREMENT,
	Code varchar(80) NOT NULL,
	Name varchar(80) NOT NULL,
	Description longtext DEFAULT '',
	PRIMARY KEY (`ID`),
	UNIQUE `Code` (`Code`),
	INDEX `Name` (`Name`)
);
