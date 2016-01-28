-- Database: mysql
CREATE TABLE `{table}` (
	ID integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	Name varchar(32) NOT NULL,
	Whois text,
	Updated timestamp NOT NULL DEFAULT 0,
	INDEX n (Name)
);

