-- Database: mysql
CREATE TABLE `{table}` (
	ID integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	External_ID varchar(32) NOT NULL,
	Name varchar(128) NOT NULL,
	Domain_Suffix integer unsigned NOT NULL,
	Created timestamp NOT NULL DEFAULT 0,
	Modified timestamp NOT NULL DEFAULT 0,
	INDEX External_ID (External_ID),
	INDEX Suffix (Domain_Suffix),
	INDEX Name (Name)
);

