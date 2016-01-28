-- Database: mysql
CREATE TABLE {table} (
	ID integer unsigned NOT NULL AUTO_INCREMENT,
	Parent integer unsigned NULL,
	Hash varbinary(32),
	Name varchar(64),
	URL text,
	Body text,
	ImagePath varchar(128),
	OrderIndex integer NOT NULL,
	ClickCount integer default '0',
	FirstClick datetime NULL,
	LastClick datetime NULL,
	Created datetime NOT NULL,
	Modified datetime NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE `hash` (`Parent`,`Hash`)
);
