-- Database: MySQL
CREATE TABLE `{table}` (
	id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name varchar(253) NOT NULL,
	tld varchar(64) NOT NULL,
	INDEX name (name),
	INDEX tld (tld)
);
