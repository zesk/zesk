-- Database: mysql
CREATE TABLE {table} (
	ID integer unsigned NOT NULL AUTO_INCREMENT,
	CodeName varchar(255),
	Name varchar(255),
	Body text,
	OrderMethod varchar(128) DEFAULT '0',
	ImagePath varchar(128),
	Created datetime NOT NULL,
	Modified datetime NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE `codename` (`CodeName`)
);
