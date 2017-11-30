-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	name				varchar(96) NOT NULL,
	UNIQUE name (name)
);
