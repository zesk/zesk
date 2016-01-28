-- Database: MySQL
CREATE TABLE {table} (
	id 			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name		varchar(64) NOT NULL,
	title		varchar(64) NOT NULL,
	class		varchar(128) NULL,
	hook		varchar(128) NULL,
	options		text NULL,
	UNIQUE name (name),
	INDEX class (class)
);